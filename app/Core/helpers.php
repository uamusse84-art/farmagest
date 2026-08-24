<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;

/** Escapa saida HTML. Usada em todas as vistas para prevenir XSS. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $caminho = '/'): string
{
    return Url::para($caminho);
}

function ativo(string $prefixo, string $classe = 'active'): string
{
    return Url::ativo($prefixo) ? $classe : '';
}

function csrf(): string
{
    return Csrf::campo();
}

/** Formata um valor em meticais: 1234.5 -> "1 234,50 MZN". */
function moeda(float|string|null $valor): string
{
    return number_format((float) $valor, 2, ',', ' ') . ' MZN';
}

function numero(float|string|null $valor, int $casas = 0): string
{
    return number_format((float) $valor, $casas, ',', ' ');
}

/** Converte AAAA-MM-DD (ou datetime) para DD/MM/AAAA. */
function data(?string $valor, bool $comHora = false): string
{
    if ($valor === null || $valor === '' || str_starts_with($valor, '0000')) {
        return '-';
    }
    $ts = strtotime($valor);
    return $ts === false ? '-' : date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

/** Data por extenso em portugues, ex.: "Quinta-feira, 13 de Agosto de 2026". */
function dataExtenso(?string $valor = null): string
{
    $ts = $valor === null ? time() : (strtotime($valor) ?: time());

    $dias = ['Domingo', 'Segunda-feira', 'Terca-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado'];
    $meses = [
        1 => 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    return sprintf(
        '%s, %d de %s de %s',
        $dias[(int) date('w', $ts)],
        (int) date('j', $ts),
        $meses[(int) date('n', $ts)],
        date('Y', $ts)
    );
}

/** Numero de dias entre hoje e a data indicada (negativo = ja passou). */
function diasAte(?string $data): int
{
    if ($data === null || $data === '') {
        return 0;
    }
    $alvo = new DateTimeImmutable($data);
    $hoje = new DateTimeImmutable('today');
    return (int) $hoje->diff($alvo)->format('%r%a');
}

/** Valor anterior de um campo apos falha de validacao. */
function antigo(array $antigos, string $campo, mixed $omissao = ''): string
{
    return e($antigos[$campo] ?? $omissao);
}

/** Classe Bootstrap de estado invalido para o campo. */
function classeInvalida(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function mensagemErro(array $erros, string $campo): string
{
    return isset($erros[$campo])
        ? '<div class="invalid-feedback d-block">' . e($erros[$campo][0]) . '</div>'
        : '';
}

/** Traduz o tipo de flash para a classe de alerta do Bootstrap. */
function classeAlerta(string $tipo): string
{
    return match ($tipo) {
        'sucesso' => 'alert-success',
        'erro'    => 'alert-danger',
        'aviso'   => 'alert-warning',
        default   => 'alert-info',
    };
}

function iconeAlerta(string $tipo): string
{
    return match ($tipo) {
        'sucesso' => 'bi-check-circle-fill',
        'erro'    => 'bi-x-octagon-fill',
        'aviso'   => 'bi-exclamation-triangle-fill',
        default   => 'bi-info-circle-fill',
    };
}

/** Mantem os filtros atuais ao mudar de pagina. */
function ligacaoPaginacao(int $pagina): string
{
    $consulta = $_GET;
    $consulta['pagina'] = $pagina;
    return '?' . http_build_query($consulta);
}
