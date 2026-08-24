<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;

final class Lote extends Model
{
    protected string $tabela = 'lotes';
    protected array $preenchiveis = [
        'medicamento_id', 'fornecedor_id', 'numero_lote', 'data_validade',
        'quantidade_inicial', 'quantidade_atual', 'preco_compra', 'data_entrada',
    ];
    protected string $ordemOmissao = 'data_validade ASC';

    public function paginar(string $pesquisa, string $filtro, int $pagina): array
    {
        $condicoes  = ['1 = 1'];
        $parametros = [];

        if ($pesquisa !== '') {
            $condicoes[] = $this->condicaoPesquisa(['l.numero_lote', 'm.nome', 'm.codigo'], $pesquisa, $parametros);
        }

        $dias = (int) Config::obter('negocio.dias_alerta_validade', 90);

        if ($filtro === 'expirados') {
            $condicoes[] = 'l.data_validade < CURDATE()';
        } elseif ($filtro === 'a_expirar') {
            $condicoes[] = "l.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)";
        } elseif ($filtro === 'esgotados') {
            $condicoes[] = 'l.quantidade_atual = 0';
        } elseif ($filtro === 'validos') {
            $condicoes[] = 'l.data_validade >= CURDATE() AND l.quantidade_atual > 0';
        }

        $onde = implode(' AND ', $condicoes);

        $base = "SELECT l.*, m.nome AS medicamento, m.codigo, f.nome AS fornecedor
                 FROM lotes l
                 INNER JOIN medicamentos m ON m.id = l.medicamento_id
                 INNER JOIN fornecedores f ON f.id = l.fornecedor_id
                 WHERE {$onde}
                 ORDER BY l.data_validade ASC";

        $contagem = "SELECT COUNT(*) FROM lotes l
                     INNER JOIN medicamentos m ON m.id = l.medicamento_id
                     INNER JOIN fornecedores f ON f.id = l.fornecedor_id
                     WHERE {$onde}";

        return $this->paginarConsulta($base, $contagem, $parametros, $pagina);
    }

    public function comRelacoes(int $id): ?array
    {
        $stmt = $this->bd()->prepare(
            'SELECT l.*, m.nome AS medicamento, m.codigo, f.nome AS fornecedor
             FROM lotes l
             INNER JOIN medicamentos m ON m.id = l.medicamento_id
             INNER JOIN fornecedores f ON f.id = l.fornecedor_id
             WHERE l.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo === false ? null : $registo;
    }

    /** @return array<int, array<string, mixed>> */
    public function doMedicamento(int $medicamentoId): array
    {
        $stmt = $this->bd()->prepare(
            'SELECT l.*, f.nome AS fornecedor
             FROM lotes l
             INNER JOIN fornecedores f ON f.id = l.fornecedor_id
             WHERE l.medicamento_id = :id
             ORDER BY l.data_validade ASC'
        );
        $stmt->execute(['id' => $medicamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Lotes elegiveis para venda por ordem FEFO (First-Expired, First-Out):
     * o que expira primeiro sai primeiro, reduzindo desperdicio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function disponiveisFefo(int $medicamentoId): array
    {
        $stmt = $this->bd()->prepare(
            'SELECT id, numero_lote, data_validade, quantidade_atual
             FROM lotes
             WHERE medicamento_id = :id
               AND quantidade_atual > 0
               AND data_validade >= CURDATE()
             ORDER BY data_validade ASC, id ASC'
        );
        $stmt->execute(['id' => $medicamentoId]);
        return $stmt->fetchAll();
    }

    public function stockDisponivel(int $medicamentoId): int
    {
        $stmt = $this->bd()->prepare(
            'SELECT COALESCE(SUM(quantidade_atual), 0) FROM lotes
             WHERE medicamento_id = :id AND data_validade >= CURDATE()'
        );
        $stmt->execute(['id' => $medicamentoId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function aExpirar(?int $dias = null): array
    {
        $dias = $dias ?? (int) Config::obter('negocio.dias_alerta_validade', 90);
        $stmt = $this->bd()->prepare(
            "SELECT l.id, l.numero_lote, l.data_validade, l.quantidade_atual, m.nome AS medicamento
             FROM lotes l
             INNER JOIN medicamentos m ON m.id = l.medicamento_id
             WHERE l.quantidade_atual > 0
               AND l.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)
             ORDER BY l.data_validade ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function expirados(): array
    {
        return $this->bd()->query(
            'SELECT l.id, l.numero_lote, l.data_validade, l.quantidade_atual, m.nome AS medicamento
             FROM lotes l
             INNER JOIN medicamentos m ON m.id = l.medicamento_id
             WHERE l.quantidade_atual > 0 AND l.data_validade < CURDATE()
             ORDER BY l.data_validade ASC'
        )->fetchAll();
    }

    /** O numero de lote so precisa de ser unico dentro do mesmo medicamento. */
    public function numeroJaUsado(int $medicamentoId, string $numeroLote, int $ignorarId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM lotes WHERE medicamento_id = :medicamento AND numero_lote = :numero';
        $parametros = ['medicamento' => $medicamentoId, 'numero' => $numeroLote];

        if ($ignorarId > 0) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $ignorarId;
        }

        $stmt = $this->bd()->prepare($sql);
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function foiUtilizadoEmVendas(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM itens_venda WHERE lote_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function valorTotalStock(): float
    {
        return (float) $this->bd()->query(
            'SELECT COALESCE(SUM(l.quantidade_atual * m.preco_venda), 0)
             FROM lotes l
             INNER JOIN medicamentos m ON m.id = l.medicamento_id
             WHERE l.data_validade >= CURDATE()'
        )->fetchColumn();
    }
}
