<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\MovimentoStock;
use App\Models\Venda;

final class RelatorioController extends Controller
{
    public function indice(Request $pedido): void
    {
        $this->ver('relatorios.indice', ['titulo' => 'Relatorios']);
    }

    public function vendas(Request $pedido): void
    {
        $de  = (string) $pedido->consulta('de', date('Y-m-01'));
        $ate = (string) $pedido->consulta('ate', date('Y-m-d'));

        if (strtotime($de) === false) {
            $de = date('Y-m-01');
        }
        if (strtotime($ate) === false || strtotime($ate) < strtotime($de)) {
            $ate = date('Y-m-d');
        }

        $linhas = (new Venda())->periodo($de, $ate);

        $this->ver('relatorios.vendas', [
            'titulo' => 'Relatorio de vendas',
            'de'     => $de,
            'ate'    => $ate,
            'linhas' => $linhas,
            'total'  => array_sum(array_map(static fn (array $l): float => (float) $l['total'], $linhas)),
        ]);
    }

    public function stock(Request $pedido): void
    {
        $lotes = new Lote();

        $this->ver('relatorios.stock', [
            'titulo'         => 'Relatorio de stock',
            'stockBaixo'     => (new Medicamento())->abaixoDoMinimo(),
            'lotesAExpirar'  => $lotes->aExpirar(),
            'lotesExpirados' => $lotes->expirados(),
            'valorStock'     => $lotes->valorTotalStock(),
        ]);
    }

    public function movimentos(Request $pedido): void
    {
        $tipo = (string) $pedido->consulta('tipo', '');

        $this->ver('relatorios.movimentos', [
            'titulo' => 'Movimentos de stock',
            'tipo'   => $tipo,
            'pagina' => (new MovimentoStock())->paginar($tipo, (int) $pedido->consulta('pagina', 1)),
        ]);
    }
}
