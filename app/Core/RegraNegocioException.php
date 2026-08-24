<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Violacao de uma regra de negocio (stock insuficiente, lote expirado, venda
 * ja anulada, ...). E apresentada ao utilizador como mensagem de erro, ao
 * contrario das falhas tecnicas, que sao registadas no log.
 */
final class RegraNegocioException extends RuntimeException
{
}
