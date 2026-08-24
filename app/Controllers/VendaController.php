<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RegraNegocioException;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\Medicamento;
use App\Models\Venda;

final class VendaController extends Controller
{
    private Venda $modelo;

    public function __construct()
    {
        $this->modelo = new Venda();
    }

    public function indice(Request $pedido): void
    {
        $this->ver('vendas.indice', [
            'titulo'   => 'Vendas',
            'pesquisa' => (string) $pedido->consulta('pesquisa', ''),
            'estado'   => (string) $pedido->consulta('estado', ''),
            'de'       => (string) $pedido->consulta('de', ''),
            'ate'      => (string) $pedido->consulta('ate', ''),
            'pagina'   => $this->modelo->paginar(
                (string) $pedido->consulta('pesquisa', ''),
                (string) $pedido->consulta('estado', ''),
                (string) $pedido->consulta('de', ''),
                (string) $pedido->consulta('ate', ''),
                (int) $pedido->consulta('pagina', 1)
            ),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('vendas.criar', [
            'titulo'       => 'Nova venda',
            'medicamentos' => (new Medicamento())->disponiveisParaVenda(),
            'clientes'     => (new Cliente())->todos(),
        ]);
    }

    public function guardar(Request $pedido): void
    {
        $itens = $this->extrairItens($pedido);

        if ($itens === []) {
            Session::flash('erro', 'Adicione pelo menos um medicamento ao carrinho antes de finalizar a venda.');
            $this->redirecionar('/vendas/criar');
        }

        $validador = new Validator(
            $pedido->todos(),
            [
                'forma_pagamento' => 'obrigatorio|em:' . implode(',', array_keys(Venda::FORMAS_PAGAMENTO)),
                'desconto'        => 'decimal|minimo:0',
                'observacoes'     => 'max:255',
            ],
            ['forma_pagamento' => 'Forma de pagamento', 'desconto' => 'Desconto', 'observacoes' => 'Observacoes']
        );

        if ($validador->falha()) {
            Session::flash('erro', (string) $validador->primeiroErro());
            $this->redirecionar('/vendas/criar');
        }

        try {
            $vendaId = $this->modelo->registar($itens, [
                'cliente_id'      => (int) $pedido->entrada('cliente_id', 0),
                'forma_pagamento' => (string) $pedido->entrada('forma_pagamento'),
                'desconto'        => (float) $pedido->entrada('desconto', 0),
                'observacoes'     => (string) $pedido->entrada('observacoes', ''),
            ], (int) Auth::id());
        } catch (RegraNegocioException $e) {
            Session::flash('erro', $e->getMessage());
            $this->redirecionar('/vendas/criar');
        }

        Session::flash('sucesso', 'Venda registada com sucesso.');
        $this->redirecionar("/vendas/{$vendaId}");
    }

    public function detalhe(Request $pedido): void
    {
        $id    = $pedido->parametroInteiro('id');
        $venda = $this->ouFalhar($this->modelo->comRelacoes($id), 'A venda indicada nao existe.');

        $this->ver('vendas.detalhe', [
            'titulo' => 'Venda ' . $venda['numero'],
            'venda'  => $venda,
            'itens'  => $this->modelo->itens($id),
        ]);
    }

    public function recibo(Request $pedido): void
    {
        $id    = $pedido->parametroInteiro('id');
        $venda = $this->ouFalhar($this->modelo->comRelacoes($id), 'A venda indicada nao existe.');

        $this->ver('vendas.recibo', [
            'titulo' => 'Recibo ' . $venda['numero'],
            'venda'  => $venda,
            'itens'  => $this->modelo->itens($id),
        ], 'limpo');
    }

    public function anular(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'A venda indicada nao existe.');

        $validador = new Validator(
            $pedido->todos(),
            ['motivo' => 'obrigatorio|min:5|max:200'],
            ['motivo' => 'Motivo da anulacao']
        );

        if ($validador->falha()) {
            Session::flash('erro', (string) $validador->primeiroErro());
            $this->redirecionar("/vendas/{$id}");
        }

        try {
            $this->modelo->anular($id, (int) Auth::id(), (string) $pedido->entrada('motivo'));
        } catch (RegraNegocioException $e) {
            Session::flash('erro', $e->getMessage());
            $this->redirecionar("/vendas/{$id}");
        }

        Session::flash('sucesso', 'Venda anulada e stock reposto nos lotes de origem.');
        $this->redirecionar("/vendas/{$id}");
    }

    /**
     * O carrinho chega como arrays paralelos: itens[medicamento_id][] e itens[quantidade][].
     * Quantidades repetidas do mesmo medicamento sao somadas.
     *
     * @return array<int, array{medicamento_id: int, quantidade: int}>
     */
    private function extrairItens(Request $pedido): array
    {
        $medicamentos = (array) $pedido->entrada('item_medicamento', []);
        $quantidades  = (array) $pedido->entrada('item_quantidade', []);

        $agregados = [];
        foreach ($medicamentos as $indice => $medicamentoId) {
            $medicamentoId = (int) $medicamentoId;
            $quantidade    = (int) ($quantidades[$indice] ?? 0);

            if ($medicamentoId <= 0 || $quantidade <= 0) {
                continue;
            }
            $agregados[$medicamentoId] = ($agregados[$medicamentoId] ?? 0) + $quantidade;
        }

        $itens = [];
        foreach ($agregados as $medicamentoId => $quantidade) {
            $itens[] = ['medicamento_id' => $medicamentoId, 'quantidade' => $quantidade];
        }

        return $itens;
    }
}
