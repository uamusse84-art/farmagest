<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Camada fina sobre $_SESSION, com mensagens flash e expiracao por inatividade.
 */
final class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) Config::obter('sessao.nome', 'farmagest_sessao'));
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
        session_start();

        self::aplicarTempoInatividade();
    }

    private static function aplicarTempoInatividade(): void
    {
        $limite = (int) Config::obter('sessao.tempo_inatividade', 1800);
        $ultima = $_SESSION['_ultima_atividade'] ?? null;

        if ($ultima !== null && (time() - (int) $ultima) > $limite) {
            self::destruir();
            session_start();
            self::flash('aviso', 'A sessao expirou por inatividade. Inicie sessao novamente.');
        }

        $_SESSION['_ultima_atividade'] = time();
    }

    public static function definir(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public static function obter(string $chave, mixed $omissao = null): mixed
    {
        return $_SESSION[$chave] ?? $omissao;
    }

    public static function tem(string $chave): bool
    {
        return isset($_SESSION[$chave]);
    }

    public static function remover(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    // ------------------------------ Flash ------------------------------

    /** Tipos usados: sucesso, erro, aviso, info. */
    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /** @return array<int, array{tipo: string, mensagem: string}> */
    public static function consumirFlash(): array
    {
        $mensagens = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $mensagens;
    }

    /** Guarda erros de validacao e os dados submetidos para repopular o formulario. */
    public static function guardarErros(array $erros, array $antigos = []): void
    {
        $_SESSION['_erros']  = $erros;
        $_SESSION['_antigos'] = $antigos;
    }

    public static function consumirErros(): array
    {
        $erros = $_SESSION['_erros'] ?? [];
        unset($_SESSION['_erros']);
        return $erros;
    }

    public static function consumirAntigos(): array
    {
        $antigos = $_SESSION['_antigos'] ?? [];
        unset($_SESSION['_antigos']);
        return $antigos;
    }
}
