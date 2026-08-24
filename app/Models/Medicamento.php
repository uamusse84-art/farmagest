<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Medicamento extends Model
{
    protected string $tabela = 'medicamentos';
    protected array $preenchiveis = [
        'codigo', 'nome', 'categoria_id', 'principio_ativo', 'forma_farmaceutica',
        'dosagem', 'preco_venda', 'stock_minimo', 'requer_receita', 'ativo',
    ];
    protected string $ordemOmissao = 'nome ASC';

    public const FORMAS = [
        'comprimido' => 'Comprimido',
        'capsula'    => 'Capsula',
        'xarope'     => 'Xarope',
        'injetavel'  => 'Injetavel',
        'pomada'     => 'Pomada/Creme',
        'suspensao'  => 'Suspensao',
        'gotas'      => 'Gotas',
        'outro'      => 'Outro',
    ];

    /** Lista paginada ja com o stock consolidado por medicamento. */
    public function paginar(string $pesquisa, ?int $categoriaId, string $filtroStock, int $pagina): array
    {
        $condicoes  = ['1 = 1'];
        $parametros = [];

        if ($pesquisa !== '') {
            $condicoes[] = $this->condicaoPesquisa(['v.nome', 'v.codigo'], $pesquisa, $parametros);
        }
        if ($categoriaId !== null && $categoriaId > 0) {
            $condicoes[] = 'm.categoria_id = :categoria';
            $parametros['categoria'] = $categoriaId;
        }
        if ($filtroStock === 'baixo') {
            $condicoes[] = 'v.stock_disponivel <= v.stock_minimo';
        } elseif ($filtroStock === 'esgotado') {
            $condicoes[] = 'v.stock_disponivel = 0';
        }

        $onde = implode(' AND ', $condicoes);

        $base = "SELECT v.*, m.categoria_id, m.forma_farmaceutica, m.dosagem, m.principio_ativo
                 FROM vw_stock_medicamentos v
                 INNER JOIN medicamentos m ON m.id = v.medicamento_id
                 WHERE {$onde}
                 ORDER BY v.nome ASC";

        $contagem = "SELECT COUNT(*) FROM vw_stock_medicamentos v
                     INNER JOIN medicamentos m ON m.id = v.medicamento_id
                     WHERE {$onde}";

        return $this->paginarConsulta($base, $contagem, $parametros, $pagina);
    }

    /** Medicamento com a categoria e o stock ja resolvidos. */
    public function comStock(int $id): ?array
    {
        $stmt = $this->bd()->prepare(
            'SELECT m.*, c.nome AS categoria,
                    COALESCE(SUM(CASE WHEN l.data_validade >= CURDATE() THEN l.quantidade_atual END), 0) AS stock_disponivel,
                    COALESCE(SUM(CASE WHEN l.data_validade <  CURDATE() THEN l.quantidade_atual END), 0) AS stock_expirado
             FROM medicamentos m
             INNER JOIN categorias c ON c.id = m.categoria_id
             LEFT  JOIN lotes l ON l.medicamento_id = m.id
             WHERE m.id = :id
             GROUP BY m.id, c.nome'
        );
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo === false ? null : $registo;
    }

    /** Medicamentos com stock utilizavel, para o ecra de venda. */
    public function disponiveisParaVenda(): array
    {
        return $this->bd()->query(
            "SELECT v.medicamento_id, v.codigo, v.nome, v.preco_venda, v.requer_receita,
                    v.stock_disponivel, v.proxima_validade, v.categoria
             FROM vw_stock_medicamentos v
             WHERE v.ativo = 1 AND v.stock_disponivel > 0
             ORDER BY v.nome ASC"
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function abaixoDoMinimo(): array
    {
        return $this->bd()->query(
            'SELECT medicamento_id, codigo, nome, stock_disponivel, stock_minimo
             FROM vw_stock_medicamentos
             WHERE ativo = 1 AND stock_disponivel <= stock_minimo
             ORDER BY (stock_disponivel - stock_minimo) ASC, nome ASC'
        )->fetchAll();
    }

    public function proximoCodigo(): string
    {
        $ultimo = (int) $this->bd()
            ->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) FROM medicamentos WHERE codigo LIKE 'MED-%'")
            ->fetchColumn();

        return sprintf('MED-%04d', $ultimo + 1);
    }

    public function temLotes(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM lotes WHERE medicamento_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function maisVendidos(int $limite = 5): array
    {
        $stmt = $this->bd()->prepare(
            "SELECT m.nome, SUM(i.quantidade) AS unidades, SUM(i.subtotal) AS receita
             FROM itens_venda i
             INNER JOIN vendas v ON v.id = i.venda_id AND v.estado = 'concluida'
             INNER JOIN medicamentos m ON m.id = i.medicamento_id
             GROUP BY m.id, m.nome
             ORDER BY unidades DESC
             LIMIT " . max(1, $limite)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
