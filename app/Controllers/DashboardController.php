<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\Venda;

final class DashboardController extends Controller
{
    public function indice(Request $pedido): void
    {
        $medicamentos = new Medicamento();
        $lotes        = new Lote();
        $vendas       = new Venda();

        $this->ver('dashboard.indice', [
            'titulo'          => 'Painel de controlo',
            'resumoDia'       => $vendas->resumoDoDia(),
            'resumoMes'       => $vendas->resumoDoMes(),
            'totalMedicamentos' => $medicamentos->contar('ativo = 1'),
            'totalClientes'   => (new Cliente())->contar(),
            'valorStock'      => $lotes->valorTotalStock(),
            'stockBaixo'      => $medicamentos->abaixoDoMinimo(),
            'lotesAExpirar'   => $lotes->aExpirar(),
            'lotesExpirados'  => $lotes->expirados(),
            'maisVendidos'    => $medicamentos->maisVendidos(),
            'serie'           => $vendas->serieUltimosDias(7),
        ]);
    }
}
