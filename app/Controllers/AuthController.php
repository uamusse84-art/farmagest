<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

final class AuthController extends Controller
{
    public function mostrarLogin(Request $pedido): void
    {
        if (Auth::autenticado()) {
            $this->redirecionar('/painel');
        }

        $this->ver('auth.login', ['titulo' => 'Iniciar sessao'], 'limpo');
    }

    public function autenticar(Request $pedido): void
    {
        $validador = new Validator(
            $pedido->todos(),
            ['email' => 'obrigatorio|email', 'palavra_passe' => 'obrigatorio'],
            ['email' => 'E-mail', 'palavra_passe' => 'Palavra-passe']
        );

        if ($validador->falha()) {
            Session::guardarErros($validador->erros(), ['email' => $pedido->entrada('email')]);
            $this->redirecionar('/login');
        }

        if (Auth::bloqueado()) {
            $segundos = Auth::segundosAteDesbloqueio();
            Session::flash('erro', sprintf(
                'Demasiadas tentativas falhadas. Aguarde %d minuto(s) antes de tentar novamente.',
                (int) ceil($segundos / 60)
            ));
            $this->redirecionar('/login');
        }

        if (!Auth::tentar((string) $pedido->entrada('email'), (string) $pedido->entrada('palavra_passe'))) {
            Session::guardarErros(
                ['email' => ['As credenciais indicadas nao correspondem a nenhuma conta ativa.']],
                ['email' => $pedido->entrada('email')]
            );
            Session::flash('erro', 'Nao foi possivel iniciar sessao. Verifique o e-mail e a palavra-passe.');
            $this->redirecionar('/login');
        }

        $destino = (string) Session::obter('_destino_pos_login', '/painel');
        Session::remover('_destino_pos_login');

        Session::flash('sucesso', 'Bem-vindo(a), ' . Auth::utilizador()['nome'] . '.');
        $this->redirecionar($destino);
    }

    public function terminar(Request $pedido): void
    {
        Auth::terminar();
        Session::flash('info', 'Sessao terminada com seguranca.');
        $this->redirecionar('/login');
    }
}
