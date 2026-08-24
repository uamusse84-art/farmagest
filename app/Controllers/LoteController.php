<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Fornecedor;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\MovimentoStock;

final class LoteController extends Controller
{
    private Lote $modelo;

    public function __construct()
    {
        $this->modelo = new Lote();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa = (string) $pedido->consulta('pesquisa', '');
        $filtro   = (string) $pedido->consulta('filtro', '');

        $this->ver('lotes.indice', [
            'titulo'   => 'Lotes e stock',
            'pesquisa' => $pesquisa,
            'filtro'   => $filtro,
            'pagina'   => $this->modelo->paginar($pesquisa, $filtro, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('lotes.formulario', [
            'titulo'        => 'Entrada de stock',
            'lote'          => null,
            'medicamentos'  => (new Medicamento())->todos(),
            'fornecedores'  => (new Fornecedor())->ativos(),
            'preSelecionado' => (int) $pedido->consulta('medicamento', 0),
        ]);
    }

    public function guardar(Request $pedido): void
    {
        $dados = $this->validar($pedido, $this->regras(), $this->rotulos(), '/lotes/criar');
        $this->garantirNumeroUnico($dados, '/lotes/criar');

        $quantidade = (int) $dados['quantidade_inicial'];

        $id = $this->modelo->criar([
            'medicamento_id'     => (int) $dados['medicamento_id'],
            'fornecedor_id'      => (int) $dados['fornecedor_id'],
            'numero_lote'        => strtoupper((string) $dados['numero_lote']),
            'data_validade'      => $dados['data_validade'],
            'quantidade_inicial' => $quantidade,
            'quantidade_atual'   => $quantidade,
            'preco_compra'       => round((float) $dados['preco_compra'], 2),
            'data_entrada'       => $dados['data_entrada'],
        ]);

        (new MovimentoStock())->registar(
            $id,
            (int) Auth::id(),
            'entrada',
            $quantidade,
            'Lote ' . strtoupper((string) $dados['numero_lote']),
            'Entrada inicial de stock'
        );

        Session::flash('sucesso', "Lote registado com sucesso: {$quantidade} unidades adicionadas ao stock.");
        $this->redirecionar('/lotes');
    }

    public function detalhe(Request $pedido): void
    {
        $id   = $pedido->parametroInteiro('id');
        $lote = $this->ouFalhar($this->modelo->comRelacoes($id), 'O lote indicado nao existe.');

        $this->ver('lotes.detalhe', [
            'titulo'     => 'Lote ' . $lote['numero_lote'],
            'lote'       => $lote,
            'movimentos' => (new MovimentoStock())->doLote($id),
        ]);
    }

    public function editar(Request $pedido): void
    {
        $lote = $this->ouFalhar(
            $this->modelo->comRelacoes($pedido->parametroInteiro('id')),
            'O lote indicado nao existe.'
        );

        $this->ver('lotes.formulario', [
            'titulo'        => 'Editar lote',
            'lote'          => $lote,
            'medicamentos'  => (new Medicamento())->todos(),
            'fornecedores'  => (new Fornecedor())->ativos(),
            'preSelecionado' => (int) $lote['medicamento_id'],
        ]);
    }

    public function atualizar(Request $pedido): void
    {
        $id   = $pedido->parametroInteiro('id');
        $lote = $this->ouFalhar($this->modelo->encontrar($id), 'O lote indicado nao existe.');

        $regras = $this->regras($id);
        // A quantidade de um lote existente so muda por venda ou por ajuste de inventario.
        unset($regras['quantidade_inicial']);

        $dados = $this->validar($pedido, $regras, $this->rotulos(), "/lotes/{$id}/editar");
        $this->garantirNumeroUnico($dados, "/lotes/{$id}/editar", $id);

        $this->modelo->atualizar($id, [
            'fornecedor_id' => (int) $dados['fornecedor_id'],
            'numero_lote'   => strtoupper((string) $dados['numero_lote']),
            'data_validade' => $dados['data_validade'],
            'preco_compra'  => round((float) $dados['preco_compra'], 2),
            'data_entrada'  => $dados['data_entrada'],
        ]);

        Session::flash('sucesso', 'Lote atualizado com sucesso.');
        $this->redirecionar("/lotes/{$id}");
    }

    /** Correcao de inventario: acerta a quantidade e deixa rasto na auditoria. */
    public function ajustar(Request $pedido): void
    {
        $id   = $pedido->parametroInteiro('id');
        $lote = $this->ouFalhar($this->modelo->encontrar($id), 'O lote indicado nao existe.');

        $dados = $this->validar(
            $pedido,
            [
                'nova_quantidade' => 'obrigatorio|inteiro|minimo:0|maximo:' . $lote['quantidade_inicial'],
                'tipo'            => 'obrigatorio|em:ajuste,quebra',
                'motivo'          => 'obrigatorio|min:5|max:255',
            ],
            ['nova_quantidade' => 'Nova quantidade', 'tipo' => 'Tipo de movimento', 'motivo' => 'Motivo'],
            "/lotes/{$id}"
        );

        $nova      = (int) $dados['nova_quantidade'];
        $diferenca = $nova - (int) $lote['quantidade_atual'];

        if ($diferenca === 0) {
            Session::flash('aviso', 'A quantidade indicada e igual a atual: nenhum ajuste foi registado.');
            $this->redirecionar("/lotes/{$id}");
        }

        $this->modelo->atualizar($id, ['quantidade_atual' => $nova]);

        (new MovimentoStock())->registar(
            $id,
            (int) Auth::id(),
            (string) $dados['tipo'],
            $diferenca,
            'Ajuste manual',
            (string) $dados['motivo']
        );

        Session::flash('sucesso', sprintf(
            'Stock ajustado de %d para %d unidades (%+d).',
            (int) $lote['quantidade_atual'],
            $nova,
            $diferenca
        ));
        $this->redirecionar("/lotes/{$id}");
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O lote indicado nao existe.');

        if ($this->modelo->foiUtilizadoEmVendas($id)) {
            Session::flash('erro', 'Nao e possivel eliminar: este lote ja foi usado em vendas. Use um ajuste de inventario.');
            $this->redirecionar('/lotes');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Lote eliminado com sucesso.');
        $this->redirecionar('/lotes');
    }

    private function garantirNumeroUnico(array $dados, string $rotaFalha, int $ignorarId = 0): void
    {
        $numero = strtoupper((string) $dados['numero_lote']);

        if (!$this->modelo->numeroJaUsado((int) $dados['medicamento_id'], $numero, $ignorarId)) {
            return;
        }

        Session::guardarErros(
            ['numero_lote' => ['Ja existe um lote com este numero para o medicamento selecionado.']],
            $dados
        );
        Session::flash('erro', 'Corrija os campos assinalados e submeta novamente.');
        $this->redirecionar($rotaFalha);
    }

    private function regras(int $ignorarId = 0): array
    {
        return [
            'medicamento_id'     => 'obrigatorio|inteiro|existe:medicamentos',
            'fornecedor_id'      => 'obrigatorio|inteiro|existe:fornecedores',
            'numero_lote'        => 'obrigatorio|min:3|max:50',
            'data_validade'      => 'obrigatorio|data_futura',
            'quantidade_inicial' => 'obrigatorio|inteiro|minimo:1|maximo:1000000',
            'preco_compra'       => 'obrigatorio|decimal|minimo:0|maximo:1000000',
            'data_entrada'       => 'obrigatorio|data|data_passada_ou_hoje',
        ];
    }

    private function rotulos(): array
    {
        return [
            'medicamento_id'     => 'Medicamento',
            'fornecedor_id'      => 'Fornecedor',
            'numero_lote'        => 'Numero do lote',
            'data_validade'      => 'Data de validade',
            'quantidade_inicial' => 'Quantidade',
            'preco_compra'       => 'Preco de compra',
            'data_entrada'       => 'Data de entrada',
        ];
    }
}
