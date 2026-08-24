<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Medicamento;

final class MedicamentoController extends Controller
{
    private Medicamento $modelo;

    public function __construct()
    {
        $this->modelo = new Medicamento();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa    = (string) $pedido->consulta('pesquisa', '');
        $categoriaId = (int) $pedido->consulta('categoria', 0);
        $stock       = (string) $pedido->consulta('stock', '');

        $this->ver('medicamentos.indice', [
            'titulo'      => 'Medicamentos',
            'pesquisa'    => $pesquisa,
            'categoriaId' => $categoriaId,
            'stock'       => $stock,
            'categorias'  => (new Categoria())->todos(),
            'pagina'      => $this->modelo->paginar($pesquisa, $categoriaId, $stock, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('medicamentos.formulario', [
            'titulo'      => 'Novo medicamento',
            'medicamento' => null,
            'categorias'  => (new Categoria())->todos(),
            'codigoSugerido' => $this->modelo->proximoCodigo(),
        ]);
    }

    public function guardar(Request $pedido): void
    {
        $dados = $this->validar($pedido, $this->regras(), $this->rotulos(), '/medicamentos/criar');

        $id = $this->modelo->criar($this->normalizar($dados));

        Session::flash('sucesso', 'Medicamento registado com sucesso. Adicione um lote para criar stock.');
        $this->redirecionar("/medicamentos/{$id}");
    }

    public function detalhe(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $medicamento = $this->ouFalhar($this->modelo->comStock($id), 'O medicamento indicado nao existe.');

        $this->ver('medicamentos.detalhe', [
            'titulo'      => $medicamento['nome'],
            'medicamento' => $medicamento,
            'lotes'       => (new Lote())->doMedicamento($id),
        ]);
    }

    public function editar(Request $pedido): void
    {
        $medicamento = $this->ouFalhar(
            $this->modelo->encontrar($pedido->parametroInteiro('id')),
            'O medicamento indicado nao existe.'
        );

        $this->ver('medicamentos.formulario', [
            'titulo'      => 'Editar medicamento',
            'medicamento' => $medicamento,
            'categorias'  => (new Categoria())->todos(),
            'codigoSugerido' => $medicamento['codigo'],
        ]);
    }

    public function atualizar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O medicamento indicado nao existe.');

        $dados = $this->validar($pedido, $this->regras($id), $this->rotulos(), "/medicamentos/{$id}/editar");

        $this->modelo->atualizar($id, $this->normalizar($dados));

        Session::flash('sucesso', 'Medicamento atualizado com sucesso.');
        $this->redirecionar("/medicamentos/{$id}");
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O medicamento indicado nao existe.');

        if ($this->modelo->temLotes($id)) {
            Session::flash(
                'erro',
                'Nao e possivel eliminar: existem lotes registados para este medicamento. Desative-o em alternativa.'
            );
            $this->redirecionar('/medicamentos');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Medicamento eliminado com sucesso.');
        $this->redirecionar('/medicamentos');
    }

    private function normalizar(array $dados): array
    {
        return [
            'codigo'             => strtoupper((string) $dados['codigo']),
            'nome'               => $dados['nome'],
            'categoria_id'       => (int) $dados['categoria_id'],
            'principio_ativo'    => $dados['principio_ativo'] ?: null,
            'forma_farmaceutica' => $dados['forma_farmaceutica'],
            'dosagem'            => $dados['dosagem'] ?: null,
            'preco_venda'        => round((float) $dados['preco_venda'], 2),
            'stock_minimo'       => (int) $dados['stock_minimo'],
            'requer_receita'     => isset($dados['requer_receita']) ? 1 : 0,
            'ativo'              => isset($dados['ativo']) ? 1 : 0,
        ];
    }

    private function regras(int $ignorarId = 0): array
    {
        $formas = implode(',', array_keys(Medicamento::FORMAS));

        return [
            'codigo'             => "obrigatorio|min:3|max:30|unico:medicamentos,codigo,{$ignorarId}",
            'nome'               => 'obrigatorio|min:3|max:150',
            'categoria_id'       => 'obrigatorio|inteiro|existe:categorias',
            'principio_ativo'    => 'max:150',
            'forma_farmaceutica' => "obrigatorio|em:{$formas}",
            'dosagem'            => 'max:50',
            'preco_venda'        => 'obrigatorio|decimal|minimo:0.01|maximo:1000000',
            'stock_minimo'       => 'obrigatorio|inteiro|minimo:0|maximo:100000',
        ];
    }

    private function rotulos(): array
    {
        return [
            'codigo'             => 'Codigo',
            'nome'               => 'Nome',
            'categoria_id'       => 'Categoria',
            'principio_ativo'    => 'Principio ativo',
            'forma_farmaceutica' => 'Forma farmaceutica',
            'dosagem'            => 'Dosagem',
            'preco_venda'        => 'Preco de venda',
            'stock_minimo'       => 'Stock minimo',
        ];
    }
}
