<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/** Erro que corresponde a um codigo de estado HTTP (404, 403, ...). */
class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $estado,
        string $mensagem = '',
        ?Throwable $anterior = null,
    ) {
        parent::__construct($mensagem, $estado, $anterior);
    }

    public function estado(): int
    {
        return $this->estado;
    }

    public static function naoEncontrado(string $mensagem = 'O recurso solicitado nao foi encontrado.'): self
    {
        return new self(404, $mensagem);
    }

    public static function proibido(string $mensagem = 'Nao tem permissao para aceder a esta area.'): self
    {
        return new self(403, $mensagem);
    }

    public static function pedidoInvalido(string $mensagem = 'O pedido enviado e invalido.'): self
    {
        return new self(400, $mensagem);
    }
}
