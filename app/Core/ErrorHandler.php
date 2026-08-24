<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Controlo de erros centralizado: converte avisos do PHP em excecoes, regista
 * tudo em ficheiro e apresenta ao utilizador uma pagina de erro tratada em vez
 * de um stack trace.
 */
final class ErrorHandler
{
    public static function registar(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler(static function (int $severidade, string $mensagem, string $ficheiro, int $linha): bool {
            if (!(error_reporting() & $severidade)) {
                return false;
            }
            throw new ErrorException($mensagem, 0, $severidade, $ficheiro, $linha);
        });

        set_exception_handler([self::class, 'tratar']);

        register_shutdown_function(static function (): void {
            $erro = error_get_last();
            if ($erro !== null && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::tratar(new ErrorException(
                    $erro['message'],
                    0,
                    $erro['type'],
                    $erro['file'],
                    $erro['line']
                ));
            }
        });
    }

    public static function tratar(Throwable $e): void
    {
        $estado = $e instanceof HttpException ? $e->estado() : 500;

        self::registarEmFicheiro($e, $estado);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, sprintf(
                "%s: %s em %s:%d%s",
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                PHP_EOL
            ));
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code($estado);
        }

        $depuracao = (bool) Config::obter('app.debug', false);

        $mensagem = match (true) {
            $e instanceof HttpException => $e->getMessage(),
            $depuracao                  => $e->getMessage(),
            default                     => 'Ocorreu um erro inesperado. A equipa tecnica foi notificada.',
        };

        $caminhoVista = BASE_PATH . '/app/Views/errors/erro.php';
        if (is_file($caminhoVista)) {
            $titulo   = self::titulo($estado);
            $detalhe  = $depuracao && !$e instanceof HttpException
                ? $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()
                : null;
            require $caminhoVista;
            return;
        }

        echo 'Erro ' . $estado . ': ' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
    }

    private static function titulo(int $estado): string
    {
        return match ($estado) {
            400 => 'Pedido invalido',
            403 => 'Acesso negado',
            404 => 'Pagina nao encontrada',
            405 => 'Metodo nao permitido',
            419 => 'Sessao expirada',
            default => 'Erro interno do servidor',
        };
    }

    private static function registarEmFicheiro(Throwable $e, int $estado): void
    {
        // Erros 4xx sao esperados (rota inexistente, sem permissao) e nao poluem o log.
        if ($estado < 500) {
            return;
        }

        $pasta = BASE_PATH . '/storage/logs';
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0777, true);
        }

        $linha = sprintf(
            "[%s] %s: %s em %s:%d%s%s%s",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL,
            $e->getTraceAsString(),
            PHP_EOL . str_repeat('-', 80) . PHP_EOL
        );

        @file_put_contents($pasta . '/erros.log', $linha, FILE_APPEND | LOCK_EX);
    }
}
