<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Categoria;

final class CategoriaController extends Controller
{
    private Categoria $modelo;

    public function __construct()
    {
        $this->modelo = new Categoria();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa = (string) $pedido->consulta('pesquisa', '');

        $this->ver('categorias.indice', [
            'titulo'   => 'Categorias',
            'pesquisa' => $pesquisa,
            'pagina'   => $this->modelo->paginar($pesquisa, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('categorias.formulario', [
            'titulo'    => 'Nova categoria',
            'categoria' => null,
        ]);
    }

    public function guardar(Request $pedido): void
    {
        $dados = $this->validar($pedido, $this->regras(), $this->rotulos(), '/categorias/criar');

        $this->modelo->criar([
            'nome'      => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
        ]);

        Session::flash('sucesso', 'Categoria criada com sucesso.');
        $this->redirecionar('/categorias');
    }

    public function editar(Request $pedido): void
    {
        $categoria = $this->ouFalhar(
            $this->modelo->encontrar($pedido->parametroInteiro('id')),
            'A categoria indicada nao existe.'
        );

        $this->ver('categorias.formulario', [
            'titulo'    => 'Editar categoria',
            'categoria' => $categoria,
        ]);
    }

    public function atualizar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'A categoria indicada nao existe.');

        $dados = $this->validar($pedido, $this->regras($id), $this->rotulos(), "/categorias/{$id}/editar");

        $this->modelo->atualizar($id, [
            'nome'      => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
        ]);

        Session::flash('sucesso', 'Categoria atualizada com sucesso.');
        $this->redirecionar('/categorias');
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'A categoria indicada nao existe.');

        if ($this->modelo->temMedicamentos($id)) {
            Session::flash('erro', 'Nao e possivel eliminar: existem medicamentos associados a esta categoria.');
            $this->redirecionar('/categorias');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Categoria eliminada com sucesso.');
        $this->redirecionar('/categorias');
    }

    private function regras(int $ignorarId = 0): array
    {
        return [
            'nome'      => "obrigatorio|min:3|max:80|unico:categorias,nome,{$ignorarId}",
            'descricao' => 'max:255',
        ];
    }

    private function rotulos(): array
    {
        return ['nome' => 'Nome', 'descricao' => 'Descricao'];
    }
}
