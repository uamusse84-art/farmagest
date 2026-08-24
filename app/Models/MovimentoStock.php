<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MovimentoStock extends Model
{
    protected string $tabela = 'movimentos_stock';
    protected array $preenchiveis = ['lote_id', 'utilizador_id', 'tipo', 'quantidade', 'referencia', 'observacao'];
    protected string $ordemOmissao = 'criado_em DESC';

    public const TIPOS = [
        'entrada'  => 'Entrada de stock',
        'saida'    => 'Saida por venda',
        'anulacao' => 'Reposicao por anulacao',
        'ajuste'   => 'Ajuste de inventario',
        'quebra'   => 'Quebra / produto danificado',
    ];

    public function registar(
        int $loteId,
        int $utilizadorId,
        string $tipo,
        int $quantidade,
        ?string $referencia = null,
        ?string $observacao = null
    ): int {
        return $this->criar([
            'lote_id'       => $loteId,
            'utilizador_id' => $utilizadorId,
            'tipo'          => $tipo,
            'quantidade'    => $quantidade,
            'referencia'    => $referencia,
            'observacao'    => $observacao,
        ]);
    }

    public function paginar(?string $tipo, int $pagina): array
    {
        $onde = $tipo !== null && $tipo !== '' ? 'ms.tipo = :tipo' : '1 = 1';
        $parametros = $tipo !== null && $tipo !== '' ? ['tipo' => $tipo] : [];

        $base = "SELECT ms.*, l.numero_lote, m.nome AS medicamento, u.nome AS utilizador
                 FROM movimentos_stock ms
                 INNER JOIN lotes l ON l.id = ms.lote_id
                 INNER JOIN medicamentos m ON m.id = l.medicamento_id
                 INNER JOIN utilizadores u ON u.id = ms.utilizador_id
                 WHERE {$onde}
                 ORDER BY ms.criado_em DESC, ms.id DESC";

        $contagem = "SELECT COUNT(*) FROM movimentos_stock ms WHERE {$onde}";

        return $this->paginarConsulta($base, $contagem, $parametros, $pagina, 15);
    }

    /** @return array<int, array<string, mixed>> */
    public function doLote(int $loteId): array
    {
        $stmt = $this->bd()->prepare(
            'SELECT ms.*, u.nome AS utilizador
             FROM movimentos_stock ms
             INNER JOIN utilizadores u ON u.id = ms.utilizador_id
             WHERE ms.lote_id = :id
             ORDER BY ms.criado_em DESC, ms.id DESC'
        );
        $stmt->execute(['id' => $loteId]);
        return $stmt->fetchAll();
    }
}
