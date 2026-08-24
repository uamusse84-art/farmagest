<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Ponto unico de acesso ao SGBD. Usa PDO com prepared statements em todo o
 * sistema, o que elimina a possibilidade de injecao de SQL.
 */
final class Database
{
    private static ?PDO $ligacao = null;

    private function __construct()
    {
    }

    public static function ligacao(): PDO
    {
        if (self::$ligacao instanceof PDO) {
            return self::$ligacao;
        }

        $cfg = Config::obter('bd');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['porta'],
            $cfg['nome'],
            $cfg['charset']
        );

        try {
            self::$ligacao = new PDO($dsn, $cfg['utilizador'], $cfg['palavra_passe'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // A mensagem indica o destino efectivamente tentado: sem isso, um
            // simples porto errado no .env e indistinguivel de o MySQL estar
            // parado ou de a base de dados nao existir.
            $mensagem = 'Nao foi possivel ligar a base de dados. Verifique se o MySQL esta em '
                . 'execucao e se DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS no ficheiro '
                . '.env estao corretos.';

            if ((bool) Config::obter('app.debug', false)) {
                $mensagem .= sprintf(
                    ' Foi tentada a ligacao a %s:%d, base de dados "%s", utilizador "%s". '
                    . 'O servidor respondeu: %s',
                    $cfg['host'],
                    $cfg['porta'],
                    $cfg['nome'],
                    $cfg['utilizador'],
                    $e->getMessage()
                );
            }

            throw new RuntimeException($mensagem, (int) $e->getCode(), $e);
        }

        return self::$ligacao;
    }

    /** Fecha a ligacao. Utilizado pelos testes para reiniciar o estado. */
    public static function fechar(): void
    {
        self::$ligacao = null;
    }
}
