<?php

/**
 * Router para o servidor embutido do PHP:
 *   php -S localhost:8000 -t public public/router.php
 * Devolve false para ficheiros estaticos existentes (CSS, JS, imagens),
 * encaminhando tudo o resto para o front controller.
 */

$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$ficheiro = __DIR__ . $caminho;

if ($caminho !== '/' && is_file($ficheiro)) {
    return false;
}

require __DIR__ . '/index.php';
