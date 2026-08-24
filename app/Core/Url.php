<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Resolve URLs de forma independente do local de instalacao. O sistema funciona
 * nas tres formas de instalacao suportadas:
 *
 *   http://localhost:8000/            servidor embutido do PHP
 *   http://localhost/farmagest/       XAMPP, com o .htaccess da raiz do projecto
 *   http://localhost/farmagest/public/ XAMPP, sem reescrita de URLs
 */
final class Url
{
    public static function prefixoBase(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');

        // Quando o .htaccess da raiz reencaminha internamente /farmagest/login
        // para /farmagest/public/index.php, o SCRIPT_NAME passa a incluir
        // "/public" mas o endereco pedido pelo navegador nao o inclui. Nesse
        // caso o prefixo correcto e a pasta acima, para que as ligacoes geradas
        // continuem a apontar para /farmagest/... e nao para /farmagest/public/...
        if (str_ends_with($base, '/public') && !self::pedidoDentroDe($base)) {
            $base = substr($base, 0, -strlen('/public'));
        }

        return $base === '/' ? '' : $base;
    }

    /** O endereco pedido pelo navegador esta realmente dentro desta pasta? */
    private static function pedidoDentroDe(string $base): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return $uri === $base || str_starts_with($uri, $base . '/');
    }

    public static function para(string $caminho = '/'): string
    {
        return self::prefixoBase() . '/' . ltrim($caminho, '/');
    }

    public static function ativo(string $prefixo): bool
    {
        $atual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base  = self::prefixoBase();
        if ($base !== '' && str_starts_with($atual, $base)) {
            $atual = substr($atual, strlen($base));
        }
        return str_starts_with('/' . ltrim($atual, '/'), $prefixo);
    }
}
