<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Utilizador;

/**
 * Autenticacao por sessao e controlo de acesso baseado em perfis (RBAC).
 *
 * Perfis:
 *   administrador - acesso total, incluindo gestao de utilizadores
 *   farmaceutico  - catalogo, stock, fornecedores, vendas e relatorios
 *   caixa         - vendas e clientes; consulta o catalogo em modo leitura
 */
final class Auth
{
    private const CHAVE_SESSAO = '_utilizador_id';
    private const MAX_TENTATIVAS = 5;
    private const JANELA_BLOQUEIO = 300; // 5 minutos

    public static function tentar(string $email, string $palavraPasse): bool
    {
        if (self::bloqueado()) {
            return false;
        }

        $utilizador = (new Utilizador())->porEmail($email);

        if ($utilizador === null || (int) $utilizador['ativo'] !== 1) {
            self::registarFalha();
            return false;
        }

        if (!password_verify($palavraPasse, $utilizador['palavra_passe'])) {
            self::registarFalha();
            return false;
        }

        Session::regenerar();
        Session::definir(self::CHAVE_SESSAO, (int) $utilizador['id']);
        Session::remover('_tentativas');
        (new Utilizador())->registarAcesso((int) $utilizador['id']);

        return true;
    }

    public static function terminar(): void
    {
        Session::destruir();
        Session::iniciar();
    }

    public static function autenticado(): bool
    {
        return Session::obter(self::CHAVE_SESSAO) !== null;
    }

    /** @return array<string, mixed>|null */
    public static function utilizador(): ?array
    {
        static $cache = null;

        $id = Session::obter(self::CHAVE_SESSAO);
        if ($id === null) {
            return null;
        }
        if ($cache !== null && (int) $cache['id'] === (int) $id) {
            return $cache;
        }

        $utilizador = (new Utilizador())->encontrar((int) $id);

        // A conta pode ter sido desativada ou removida durante a sessao.
        if ($utilizador === null || (int) $utilizador['ativo'] !== 1) {
            self::terminar();
            return null;
        }

        return $cache = $utilizador;
    }

    public static function id(): ?int
    {
        $u = self::utilizador();
        return $u === null ? null : (int) $u['id'];
    }

    public static function perfil(): ?string
    {
        $u = self::utilizador();
        return $u === null ? null : (string) $u['perfil'];
    }

    public static function ehAdministrador(): bool
    {
        return self::perfil() === 'administrador';
    }

    /** Verifica se o utilizador atual tem um dos perfis indicados. */
    public static function temPerfil(string ...$perfis): bool
    {
        $perfil = self::perfil();
        return $perfil !== null && ($perfil === 'administrador' || in_array($perfil, $perfis, true));
    }

    // -------------------- Limitacao de tentativas --------------------

    private static function registarFalha(): void
    {
        $tentativas = Session::obter('_tentativas', ['contador' => 0, 'momento' => time()]);
        if (time() - (int) $tentativas['momento'] > self::JANELA_BLOQUEIO) {
            $tentativas = ['contador' => 0, 'momento' => time()];
        }
        $tentativas['contador']++;
        $tentativas['momento'] = time();
        Session::definir('_tentativas', $tentativas);
    }

    public static function bloqueado(): bool
    {
        $tentativas = Session::obter('_tentativas');
        if (!is_array($tentativas)) {
            return false;
        }
        if (time() - (int) $tentativas['momento'] > self::JANELA_BLOQUEIO) {
            Session::remover('_tentativas');
            return false;
        }
        return (int) $tentativas['contador'] >= self::MAX_TENTATIVAS;
    }

    public static function segundosAteDesbloqueio(): int
    {
        $tentativas = Session::obter('_tentativas');
        if (!is_array($tentativas)) {
            return 0;
        }
        return max(0, self::JANELA_BLOQUEIO - (time() - (int) $tentativas['momento']));
    }
}
