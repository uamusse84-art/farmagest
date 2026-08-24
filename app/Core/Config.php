<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $valores = [];

    public static function carregar(array $valores): void
    {
        self::$valores = $valores;
    }

    /** Aceita notacao por pontos: Config::obter('bd.host'). */
    public static function obter(string $chave, mixed $omissao = null): mixed
    {
        $atual = self::$valores;
        foreach (explode('.', $chave) as $segmento) {
            if (!is_array($atual) || !array_key_exists($segmento, $atual)) {
                return $omissao;
            }
            $atual = $atual[$segmento];
        }
        return $atual;
    }
}
