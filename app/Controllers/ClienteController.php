<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Cliente;

final class ClienteController extends Controller
{
    private Cliente $modelo;

    public function __construct()
    {
        $this->modelo = new Cliente();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa = (string) $pedido->consulta('pesquisa', '');

        $this->ver('clientes.indice', [
            'titulo'   => 'Clientes',
            'pesquisa' => $pesquisa,
            'pagina'   => $this->modelo->paginar($pesquisa, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('clientes.formulario', ['titulo' => 'Novo cliente', 'cliente' => null]);
    }

    public function guardar(Request $pedido): void
    {
        $dados = $this->validar($pedido, $this->regras(), $this->rotulos(), '/clientes/criar');

        $this->modelo->criar($this->normalizar($dados));

        Session::flash('sucesso', 'Cliente registado com sucesso.');
        $this->redirecionar('/clientes');
    }

    public function detalhe(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $cliente = $this->ouFalhar($this->modelo->encontrar($id), 'O cliente indicado nao existe.');

        $this->ver('clientes.detalhe', [
            'titulo'    => 'Ficha do cliente',
            'cliente'   => $cliente,
            'historico' => $this->modelo->historico($id),
        ]);
    }

    public function editar(Request $pedido): void
    {
        $cliente = $this->ouFalhar(
            $this->modelo->encontrar($pedido->parametroInteiro('id')),
            'O cliente indicado nao existe.'
        );

        $this->ver('clientes.formulario', ['titulo' => 'Editar cliente', 'cliente' => $cliente]);
    }

    public function atualizar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O cliente indicado nao existe.');

        $dados = $this->validar($pedido, $this->regras($id), $this->rotulos(), "/clientes/{$id}/editar");

        $this->modelo->atualizar($id, $this->normalizar($dados));

        Session::flash('sucesso', 'Cliente atualizado com sucesso.');
        $this->redirecionar('/clientes');
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O cliente indicado nao existe.');

        if ($this->modelo->temVendas($id)) {
            Session::flash('erro', 'Nao e possivel eliminar: o cliente tem vendas associadas no historico.');
            $this->redirecionar('/clientes');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Cliente eliminado com sucesso.');
        $this->redirecionar('/clientes');
    }

    private function normalizar(array $dados): array
    {
        return [
            'nome'     => $dados['nome'],
            'nuit'     => $dados['nuit'] ?: null,
            'telefone' => $dados['telefone'] ?: null,
            'email'    => $dados['email'] ?: null,
            'endereco' => $dados['endereco'] ?: null,
        ];
    }

    private function regras(int $ignorarId = 0): array
    {
        return [
            'nome'     => 'obrigatorio|min:3|max:150',
            'nuit'     => "inteiro|min:9|max:20|unico:clientes,nuit,{$ignorarId}",
            'telefone' => 'telefone',
            'email'    => 'email|max:150',
            'endereco' => 'max:200',
        ];
    }

    private function rotulos(): array
    {
        return [
            'nome'     => 'Nome',
            'nuit'     => 'NUIT',
            'telefone' => 'Telefone',
            'email'    => 'E-mail',
            'endereco' => 'Endereco',
        ];
    }
}
