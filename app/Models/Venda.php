<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\RegraNegocioException;
use Throwable;

/**
 * Agregado de venda. Concentra a regra de negocio central do sistema:
 * o abate de stock por FEFO dentro de uma transacao atomica.
 */
final class Venda extends Model
{
    protected string $tabela = 'vendas';
    protected array $preenchiveis = [
        'numero', 'cliente_id', 'utilizador_id', 'subtotal', 'desconto',
        'total', 'forma_pagamento', 'estado', 'observacoes',
    ];
    protected string $ordemOmissao = 'data_venda DESC';

    public const FORMAS_PAGAMENTO = [
        'numerario'     => 'Numerario',
        'mpesa'         => 'M-Pesa',
        'emola'         => 'e-Mola',
        'cartao'        => 'Cartao',
        'transferencia' => 'Transferencia bancaria',
    ];

    /**
     * Regista uma venda completa.
     *
     * @param array<int, array{medicamento_id: int, quantidade: int}> $itens
     * @throws RegraNegocioException quando falta stock ou o carrinho e invalido
     */
    public function registar(array $itens, array $cabecalho, int $utilizadorId): int
    {
        if ($itens === []) {
            throw new RegraNegocioException('A venda tem de conter pelo menos um item.');
        }

        $bd = $this->bd();
        $bd->beginTransaction();

        try {
            $numero  = $this->gerarNumero();
            $vendaId = $this->criar([
                'numero'          => $numero,
                'cliente_id'      => ($cabecalho['cliente_id'] ?? 0) ?: null,
                'utilizador_id'   => $utilizadorId,
                'subtotal'        => 0,
                'desconto'        => 0,
                'total'           => 0,
                'forma_pagamento' => $cabecalho['forma_pagamento'],
                'estado'          => 'concluida',
                'observacoes'     => ($cabecalho['observacoes'] ?? '') ?: null,
            ]);

            $subtotal = 0.0;
            foreach ($itens as $item) {
                $subtotal += $this->abaterStock($vendaId, $numero, $item, $utilizadorId);
            }

            $desconto = round((float) ($cabecalho['desconto'] ?? 0), 2);
            if ($desconto < 0) {
                throw new RegraNegocioException('O desconto nao pode ser negativo.');
            }
            if ($desconto > $subtotal) {
                throw new RegraNegocioException('O desconto nao pode ser superior ao subtotal da venda.');
            }

            $stmt = $bd->prepare(
                'UPDATE vendas SET subtotal = :subtotal, desconto = :desconto, total = :total WHERE id = :id'
            );
            $stmt->execute([
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'total'    => $subtotal - $desconto,
                'id'       => $vendaId,
            ]);

            $bd->commit();
            return $vendaId;
        } catch (Throwable $e) {
            $bd->rollBack();
            throw $e;
        }
    }

    /**
     * Consome o stock do medicamento seguindo FEFO e devolve o valor do item.
     * Pode originar varias linhas de itens_venda se a quantidade atravessar lotes.
     */
    private function abaterStock(int $vendaId, string $numero, array $item, int $utilizadorId): float
    {
        $medicamentoId = (int) $item['medicamento_id'];
        $quantidade    = (int) $item['quantidade'];

        if ($quantidade <= 0) {
            throw new RegraNegocioException('A quantidade de cada item tem de ser superior a zero.');
        }

        $stmt = $this->bd()->prepare('SELECT nome, preco_venda, ativo FROM medicamentos WHERE id = :id');
        $stmt->execute(['id' => $medicamentoId]);
        $medicamento = $stmt->fetch();

        if ($medicamento === false) {
            throw new RegraNegocioException('O medicamento indicado nao existe.');
        }
        if ((int) $medicamento['ativo'] !== 1) {
            throw new RegraNegocioException("O medicamento \"{$medicamento['nome']}\" esta inativo e nao pode ser vendido.");
        }

        // FOR UPDATE bloqueia as linhas ate ao fim da transacao, evitando que
        // duas vendas simultaneas vendam o mesmo stock.
        $stmt = $this->bd()->prepare(
            'SELECT id, numero_lote, quantidade_atual
             FROM lotes
             WHERE medicamento_id = :id AND quantidade_atual > 0 AND data_validade >= CURDATE()
             ORDER BY data_validade ASC, id ASC
             FOR UPDATE'
        );
        $stmt->execute(['id' => $medicamentoId]);
        $lotes = $stmt->fetchAll();

        $disponivel = array_sum(array_column($lotes, 'quantidade_atual'));
        if ($disponivel < $quantidade) {
            throw new RegraNegocioException(sprintf(
                'Stock insuficiente para "%s": pedidas %d unidades, disponiveis %d (lotes validos).',
                $medicamento['nome'],
                $quantidade,
                $disponivel
            ));
        }

        $preco = (float) $medicamento['preco_venda'];
        $valor = 0.0;
        $porAtribuir = $quantidade;

        foreach ($lotes as $lote) {
            if ($porAtribuir === 0) {
                break;
            }

            $retirar = min($porAtribuir, (int) $lote['quantidade_atual']);
            $linha   = round($retirar * $preco, 2);

            $this->bd()->prepare(
                'INSERT INTO itens_venda (venda_id, lote_id, medicamento_id, quantidade, preco_unitario, subtotal)
                 VALUES (:venda, :lote, :medicamento, :quantidade, :preco, :subtotal)'
            )->execute([
                'venda'       => $vendaId,
                'lote'        => $lote['id'],
                'medicamento' => $medicamentoId,
                'quantidade'  => $retirar,
                'preco'       => $preco,
                'subtotal'    => $linha,
            ]);

            $this->bd()->prepare(
                'UPDATE lotes SET quantidade_atual = quantidade_atual - :q WHERE id = :id'
            )->execute(['q' => $retirar, 'id' => $lote['id']]);

            (new MovimentoStock())->registar(
                (int) $lote['id'],
                $utilizadorId,
                'saida',
                -$retirar,
                $numero,
                'Venda de ' . $medicamento['nome']
            );

            $valor       += $linha;
            $porAtribuir -= $retirar;
        }

        return $valor;
    }

    /**
     * Anula uma venda concluida e devolve o stock aos lotes de origem.
     * A venda nao e apagada: mantem-se para auditoria com estado "anulada".
     */
    public function anular(int $vendaId, int $utilizadorId, string $motivo): void
    {
        $venda = $this->encontrar($vendaId);

        if ($venda === null) {
            throw new RegraNegocioException('A venda indicada nao existe.');
        }
        if ($venda['estado'] === 'anulada') {
            throw new RegraNegocioException('Esta venda ja se encontra anulada.');
        }

        $bd = $this->bd();
        $bd->beginTransaction();

        try {
            $stmt = $bd->prepare('SELECT lote_id, quantidade FROM itens_venda WHERE venda_id = :id');
            $stmt->execute(['id' => $vendaId]);

            foreach ($stmt->fetchAll() as $item) {
                $bd->prepare('UPDATE lotes SET quantidade_atual = quantidade_atual + :q WHERE id = :id')
                   ->execute(['q' => $item['quantidade'], 'id' => $item['lote_id']]);

                (new MovimentoStock())->registar(
                    (int) $item['lote_id'],
                    $utilizadorId,
                    'anulacao',
                    (int) $item['quantidade'],
                    $venda['numero'],
                    'Anulacao: ' . $motivo
                );
            }

            $bd->prepare("UPDATE vendas SET estado = 'anulada', observacoes = :obs WHERE id = :id")
               ->execute([
                   'obs' => trim(($venda['observacoes'] ?? '') . ' | ANULADA: ' . $motivo),
                   'id'  => $vendaId,
               ]);

            $bd->commit();
        } catch (Throwable $e) {
            $bd->rollBack();
            throw $e;
        }
    }

    private function gerarNumero(): string
    {
        $ano = date('Y');
        $stmt = $this->bd()->prepare(
            "SELECT MAX(CAST(SUBSTRING(numero, 9) AS UNSIGNED)) FROM vendas WHERE numero LIKE :prefixo"
        );
        $stmt->execute(['prefixo' => "VD-{$ano}-%"]);

        return sprintf('VD-%s-%06d', $ano, ((int) $stmt->fetchColumn()) + 1);
    }

    // ------------------------------ Consultas ------------------------------

    public function paginar(string $pesquisa, ?string $estado, ?string $de, ?string $ate, int $pagina): array
    {
        $condicoes  = ['1 = 1'];
        $parametros = [];

        if ($pesquisa !== '') {
            $condicoes[] = $this->condicaoPesquisa(['v.numero', 'c.nome'], $pesquisa, $parametros);
        }
        if ($estado !== null && $estado !== '') {
            $condicoes[] = 'v.estado = :estado';
            $parametros['estado'] = $estado;
        }
        if ($de !== null && $de !== '') {
            $condicoes[] = 'DATE(v.data_venda) >= :de';
            $parametros['de'] = $de;
        }
        if ($ate !== null && $ate !== '') {
            $condicoes[] = 'DATE(v.data_venda) <= :ate';
            $parametros['ate'] = $ate;
        }

        $onde = implode(' AND ', $condicoes);

        $base = "SELECT v.*, c.nome AS cliente, u.nome AS operador,
                        (SELECT COUNT(*) FROM itens_venda i WHERE i.venda_id = v.id) AS total_itens
                 FROM vendas v
                 LEFT  JOIN clientes c ON c.id = v.cliente_id
                 INNER JOIN utilizadores u ON u.id = v.utilizador_id
                 WHERE {$onde}
                 ORDER BY v.data_venda DESC";

        $contagem = "SELECT COUNT(*) FROM vendas v
                     LEFT  JOIN clientes c ON c.id = v.cliente_id
                     INNER JOIN utilizadores u ON u.id = v.utilizador_id
                     WHERE {$onde}";

        return $this->paginarConsulta($base, $contagem, $parametros, $pagina);
    }

    public function comRelacoes(int $id): ?array
    {
        $stmt = $this->bd()->prepare(
            'SELECT v.*, c.nome AS cliente, c.nuit AS cliente_nuit, c.telefone AS cliente_telefone,
                    u.nome AS operador
             FROM vendas v
             LEFT  JOIN clientes c ON c.id = v.cliente_id
             INNER JOIN utilizadores u ON u.id = v.utilizador_id
             WHERE v.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo === false ? null : $registo;
    }

    /** @return array<int, array<string, mixed>> */
    public function itens(int $vendaId): array
    {
        $stmt = $this->bd()->prepare(
            'SELECT i.*, m.nome AS medicamento, m.codigo, l.numero_lote, l.data_validade
             FROM itens_venda i
             INNER JOIN medicamentos m ON m.id = i.medicamento_id
             INNER JOIN lotes l ON l.id = i.lote_id
             WHERE i.venda_id = :id
             ORDER BY i.id'
        );
        $stmt->execute(['id' => $vendaId]);
        return $stmt->fetchAll();
    }

    /** @return array{vendas: int, receita: float} */
    public function resumoDoDia(?string $dia = null): array
    {
        $stmt = $this->bd()->prepare(
            "SELECT COUNT(*) AS vendas, COALESCE(SUM(total), 0) AS receita
             FROM vendas WHERE estado = 'concluida' AND DATE(data_venda) = :dia"
        );
        $stmt->execute(['dia' => $dia ?? date('Y-m-d')]);
        $linha = $stmt->fetch();

        return ['vendas' => (int) $linha['vendas'], 'receita' => (float) $linha['receita']];
    }

    /** @return array{vendas: int, receita: float} */
    public function resumoDoMes(): array
    {
        $linha = $this->bd()->query(
            "SELECT COUNT(*) AS vendas, COALESCE(SUM(total), 0) AS receita
             FROM vendas
             WHERE estado = 'concluida'
               AND YEAR(data_venda) = YEAR(CURDATE())
               AND MONTH(data_venda) = MONTH(CURDATE())"
        )->fetch();

        return ['vendas' => (int) $linha['vendas'], 'receita' => (float) $linha['receita']];
    }

    /** Serie dos ultimos N dias para o grafico do painel. */
    public function serieUltimosDias(int $dias = 7): array
    {
        $stmt = $this->bd()->prepare(
            "SELECT DATE(data_venda) AS dia, COALESCE(SUM(total), 0) AS valor
             FROM vendas
             WHERE estado = 'concluida' AND data_venda >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
             GROUP BY DATE(data_venda)"
        );
        $stmt->execute(['dias' => $dias - 1]);

        $porDia = [];
        foreach ($stmt->fetchAll() as $linha) {
            $porDia[$linha['dia']] = (float) $linha['valor'];
        }

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-{$i} days"));
            $serie[] = ['dia' => $dia, 'valor' => $porDia[$dia] ?? 0.0];
        }

        return $serie;
    }

    /** @return array<int, array<string, mixed>> */
    public function periodo(string $de, string $ate): array
    {
        $stmt = $this->bd()->prepare(
            "SELECT v.numero, v.data_venda, v.total, v.forma_pagamento,
                    COALESCE(c.nome, 'Consumidor final') AS cliente, u.nome AS operador
             FROM vendas v
             LEFT  JOIN clientes c ON c.id = v.cliente_id
             INNER JOIN utilizadores u ON u.id = v.utilizador_id
             WHERE v.estado = 'concluida' AND DATE(v.data_venda) BETWEEN :de AND :ate
             ORDER BY v.data_venda ASC"
        );
        $stmt->execute(['de' => $de, 'ate' => $ate]);
        return $stmt->fetchAll();
    }
}
