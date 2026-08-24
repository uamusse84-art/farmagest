<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $parametros = [];

    public function __construct(
        private readonly string $metodo,
        private readonly string $caminho,
        private readonly array $consulta,
        private readonly array $corpo,
    ) {
    }

    public static function apartirDeGlobais(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Permite <input name="_metodo" value="PUT|DELETE"> em formularios HTML.
        if ($metodo === 'POST' && isset($_POST['_metodo'])) {
            $substituto = strtoupper((string) $_POST['_metodo']);
            if (in_array($substituto, ['PUT', 'PATCH', 'DELETE'], true)) {
                $metodo = $substituto;
            }
        }

        return new self($metodo, self::calcularCaminho(), $_GET, $_POST);
    }

    private static function calcularCaminho(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = Url::prefixoBase();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function caminho(): string
    {
        return $this->caminho;
    }

    public function ehPost(): bool
    {
        return $this->metodo === 'POST';
    }

    /** Valor do corpo (POST). */
    public function entrada(string $chave, mixed $omissao = null): mixed
    {
        $valor = $this->corpo[$chave] ?? $omissao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    /** Valor da query string (GET). */
    public function consulta(string $chave, mixed $omissao = null): mixed
    {
        $valor = $this->consulta[$chave] ?? $omissao;
        return is_string($valor) ? trim($valor) : $valor;
    }

    public function todos(): array
    {
        return $this->corpo;
    }

    /** Parametros extraidos da rota, ex.: /medicamentos/{id}/editar. */
    public function definirParametros(array $parametros): void
    {
        $this->parametros = $parametros;
    }

    public function parametro(string $chave, mixed $omissao = null): mixed
    {
        return $this->parametros[$chave] ?? $omissao;
    }

    public function parametroInteiro(string $chave): int
    {
        return (int) $this->parametro($chave, 0);
    }

    public function ehAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}
