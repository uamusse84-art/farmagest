<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Fornecedor;

final class FornecedorController extends Controller
{
    private Fornecedor $modelo;

    public function __construct()
    {
        $this->modelo = new Fornecedor();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa = (string) $pedido->consulta('pesquisa', '');

        $this->ver('fornecedores.indice', [
            'titulo'   => 'Fornecedores',
            'pesquisa' => $pesquisa,
            'pagina'   => $this->modelo->paginar($pesquisa, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('fornecedores.formulario', [
            'titulo'     => 'Novo fornecedor',
            'fornecedor' => null,
        ]);
    }

    public function guardar(Request $pedido): void
    {
        $dados = $this->validar($pedido, $this->regras(), $this->rotulos(), '/fornecedores/criar');

        $this->modelo->criar($this->normalizar($dados));

        Session::flash('sucesso', 'Fornecedor registado com sucesso.');
        $this->redirecionar('/fornecedores');
    }

    public function editar(Request $pedido): void
    {
        $fornecedor = $this->ouFalhar(
            $this->modelo->encontrar($pedido->parametroInteiro('id')),
            'O fornecedor indicado nao existe.'
        );

        $this->ver('fornecedores.formulario', [
            'titulo'     => 'Editar fornecedor',
            'fornecedor' => $fornecedor,
        ]);
    }

    public function atualizar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O fornecedor indicado nao existe.');

        $dados = $this->validar($pedido, $this->regras($id), $this->rotulos(), "/fornecedores/{$id}/editar");

        $this->modelo->atualizar($id, $this->normalizar($dados));

        Session::flash('sucesso', 'Fornecedor atualizado com sucesso.');
        $this->redirecionar('/fornecedores');
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O fornecedor indicado nao existe.');

        if ($this->modelo->temLotes($id)) {
            Session::flash('erro', 'Nao e possivel eliminar: existem lotes fornecidos por esta entidade. Desative-a em alternativa.');
            $this->redirecionar('/fornecedores');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Fornecedor eliminado com sucesso.');
        $this->redirecionar('/fornecedores');
    }

    private function normalizar(array $dados): array
    {
        return [
            'nome'     => $dados['nome'],
            'nuit'     => $dados['nuit'] ?: null,
            'telefone' => $dados['telefone'] ?: null,
            'email'    => $dados['email'] ?: null,
            'endereco' => $dados['endereco'] ?: null,
            'ativo'    => isset($dados['ativo']) ? 1 : 0,
        ];
    }

    private function regras(int $ignorarId = 0): array
    {
        return [
            'nome'     => 'obrigatorio|min:3|max:150',
            'nuit'     => "inteiro|min:9|max:20|unico:fornecedores,nuit,{$ignorarId}",
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
