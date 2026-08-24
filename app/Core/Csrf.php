<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protecao contra Cross-Site Request Forgery. Todos os formularios POST
 * incluem o token gerado aqui e o Router valida-o antes do controlador.
 */
final class Csrf
{
    private const CHAVE = '_csrf_token';

    public static function token(): string
    {
        if (!Session::tem(self::CHAVE)) {
            Session::definir(self::CHAVE, bin2hex(random_bytes(32)));
        }
        return (string) Session::obter(self::CHAVE);
    }

    public static function campo(): string
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', self::CHAVE, self::token());
    }

    public static function valido(?string $enviado): bool
    {
        $esperado = Session::obter(self::CHAVE);
        return is_string($esperado)
            && is_string($enviado)
            && hash_equals($esperado, $enviado);
    }

    public static function nomeCampo(): string
    {
        return self::CHAVE;
    }
}
