<?php

declare(strict_types=1);

/**
 * Configuracao da aplicacao. Os valores podem ser sobrepostos por um
 * ficheiro .env na raiz do projeto (ver .env.example).
 */

$env = [];
$ficheiroEnv = BASE_PATH . '/.env';
if (is_readable($ficheiroEnv)) {
    foreach (file($ficheiroEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }
        [$chave, $valor] = explode('=', $linha, 2);
        $env[trim($chave)] = trim($valor, " \t\n\r\0\x0B\"'");
    }
}

$ler = static fn (string $chave, string $omissao): string => $env[$chave] ?? $omissao;

return [
    'app' => [
        'nome'     => 'FarmaGest',
        'ambiente' => $ler('APP_ENV', 'desenvolvimento'),
        'debug'    => filter_var($ler('APP_DEBUG', '1'), FILTER_VALIDATE_BOOL),
        'moeda'    => 'MZN',
        'fuso'     => 'Africa/Maputo',
    ],
    'bd' => [
        'host'     => $ler('DB_HOST', '127.0.0.1'),
        'porta'    => (int) $ler('DB_PORT', '3306'),
        'nome'     => $ler('DB_NAME', 'farmagest'),
        'utilizador' => $ler('DB_USER', 'root'),
        'palavra_passe' => $ler('DB_PASS', ''),
        'charset'  => 'utf8mb4',
    ],
    'sessao' => [
        'nome'            => 'farmagest_sessao',
        'tempo_inatividade' => 1800, // 30 minutos
    ],
    'negocio' => [
        'dias_alerta_validade' => 90,
        'itens_por_pagina'     => 10,
    ],
];
