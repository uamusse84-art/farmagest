<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Utilizador;

final class UtilizadorController extends Controller
{
    private Utilizador $modelo;

    public function __construct()
    {
        $this->modelo = new Utilizador();
    }

    public function indice(Request $pedido): void
    {
        $pesquisa = (string) $pedido->consulta('pesquisa', '');
        $perfil   = (string) $pedido->consulta('perfil', '');

        $this->ver('utilizadores.indice', [
            'titulo'   => 'Utilizadores',
            'pesquisa' => $pesquisa,
            'perfil'   => $perfil,
            'pagina'   => $this->modelo->paginar($pesquisa, $perfil, (int) $pedido->consulta('pagina', 1)),
        ]);
    }

    public function criar(Request $pedido): void
    {
        $this->ver('utilizadores.formulario', ['titulo' => 'Novo utilizador', 'utilizador' => null]);
    }

    public function guardar(Request $pedido): void
    {
        $regras = $this->regras();
        $regras['palavra_passe'] = 'obrigatorio|palavra_passe_forte|confirmado';

        $dados = $this->validar($pedido, $regras, $this->rotulos(), '/utilizadores/criar');

        $this->modelo->criar([
            'nome'          => $dados['nome'],
            'email'         => strtolower((string) $dados['email']),
            'palavra_passe' => password_hash((string) $dados['palavra_passe'], PASSWORD_BCRYPT),
            'perfil'        => $dados['perfil'],
            'telefone'      => $dados['telefone'] ?: null,
            'ativo'         => isset($dados['ativo']) ? 1 : 0,
        ]);

        Session::flash('sucesso', 'Utilizador criado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    public function editar(Request $pedido): void
    {
        $utilizador = $this->ouFalhar(
            $this->modelo->encontrar($pedido->parametroInteiro('id')),
            'O utilizador indicado nao existe.'
        );

        $this->ver('utilizadores.formulario', [
            'titulo'     => 'Editar utilizador',
            'utilizador' => $utilizador,
        ]);
    }

    public function atualizar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $utilizador = $this->ouFalhar($this->modelo->encontrar($id), 'O utilizador indicado nao existe.');

        $regras = $this->regras($id);
        if ((string) $pedido->entrada('palavra_passe', '') !== '') {
            $regras['palavra_passe'] = 'palavra_passe_forte|confirmado';
        }

        $dados = $this->validar($pedido, $regras, $this->rotulos(), "/utilizadores/{$id}/editar");

        $ativo  = isset($dados['ativo']) ? 1 : 0;
        $perfil = (string) $dados['perfil'];

        // Salvaguarda: o sistema tem de manter sempre um administrador ativo.
        $deixaDeSerAdmin = $utilizador['perfil'] === 'administrador'
            && ($perfil !== 'administrador' || $ativo === 0);

        if ($deixaDeSerAdmin && $this->modelo->ehUltimoAdministrador($id)) {
            Session::flash('erro', 'Esta e a unica conta de administrador ativa: nao pode ser desativada nem despromovida.');
            $this->redirecionar("/utilizadores/{$id}/editar");
        }

        $atualizacao = [
            'nome'     => $dados['nome'],
            'email'    => strtolower((string) $dados['email']),
            'perfil'   => $perfil,
            'telefone' => $dados['telefone'] ?: null,
            'ativo'    => $ativo,
        ];

        if ((string) ($dados['palavra_passe'] ?? '') !== '') {
            $atualizacao['palavra_passe'] = password_hash((string) $dados['palavra_passe'], PASSWORD_BCRYPT);
        }

        $this->modelo->atualizar($id, $atualizacao);

        Session::flash('sucesso', 'Utilizador atualizado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    public function eliminar(Request $pedido): void
    {
        $id = $pedido->parametroInteiro('id');
        $this->ouFalhar($this->modelo->encontrar($id), 'O utilizador indicado nao existe.');

        if ($id === Auth::id()) {
            Session::flash('erro', 'Nao pode eliminar a sua propria conta enquanto tem sessao iniciada.');
            $this->redirecionar('/utilizadores');
        }

        if ($this->modelo->ehUltimoAdministrador($id)) {
            Session::flash('erro', 'Esta e a unica conta de administrador ativa e nao pode ser eliminada.');
            $this->redirecionar('/utilizadores');
        }

        if ($this->modelo->temMovimentos($id)) {
            Session::flash('erro', 'Nao e possivel eliminar: o utilizador tem vendas registadas. Desative a conta em alternativa.');
            $this->redirecionar('/utilizadores');
        }

        $this->modelo->eliminar($id);
        Session::flash('sucesso', 'Utilizador eliminado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    private function regras(int $ignorarId = 0): array
    {
        return [
            'nome'     => 'obrigatorio|min:5|max:120',
            'email'    => "obrigatorio|email|max:150|unico:utilizadores,email,{$ignorarId}",
            'perfil'   => 'obrigatorio|em:' . implode(',', array_keys(Utilizador::PERFIS)),
            'telefone' => 'telefone',
        ];
    }

    private function rotulos(): array
    {
        return [
            'nome'          => 'Nome completo',
            'email'         => 'E-mail',
            'perfil'        => 'Perfil',
            'telefone'      => 'Telefone',
            'palavra_passe' => 'Palavra-passe',
        ];
    }
}
