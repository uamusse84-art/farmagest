<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4 minimo. Evita a dependencia do Composer, mantendo o
 * projeto executavel em qualquer instalacao XAMPP sem passos extra.
 */
final class Autoloader
{
    public static function registar(string $prefixo, string $diretorioBase): void
    {
        spl_autoload_register(static function (string $classe) use ($prefixo, $diretorioBase): void {
            if (!str_starts_with($classe, $prefixo)) {
                return;
            }
            $relativo = substr($classe, strlen($prefixo));
            $caminho  = $diretorioBase . '/' . str_replace('\\', '/', $relativo) . '.php';
            if (is_file($caminho)) {
                require_once $caminho;
            }
        });
    }
}
