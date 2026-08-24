<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Validacao de formularios no servidor. A validacao no cliente (HTML5 +
 * Bootstrap) e apenas uma conveniencia: esta classe e a fonte de verdade.
 *
 * Uso:
 *   $v = new Validator($dados, ['nome' => 'obrigatorio|min:3'], ['nome' => 'Nome']);
 *   if (!$v->passa()) { ... $v->erros() ... }
 */
final class Validator
{
    /** @var array<string, string[]> */
    private array $erros = [];

    public function __construct(
        private readonly array $dados,
        private readonly array $regras,
        private readonly array $rotulos = [],
    ) {
        $this->executar();
    }

    public function passa(): bool
    {
        return $this->erros === [];
    }

    public function falha(): bool
    {
        return !$this->passa();
    }

    /** @return array<string, string[]> */
    public function erros(): array
    {
        return $this->erros;
    }

    public function primeiroErro(): ?string
    {
        foreach ($this->erros as $mensagens) {
            return $mensagens[0];
        }
        return null;
    }

    private function rotulo(string $campo): string
    {
        return $this->rotulos[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
    }

    private function adicionar(string $campo, string $mensagem): void
    {
        $this->erros[$campo][] = $mensagem;
    }

    private function executar(): void
    {
        foreach ($this->regras as $campo => $cadeia) {
            $valor = $this->dados[$campo] ?? null;
            $valor = is_string($valor) ? trim($valor) : $valor;
            $regras = explode('|', $cadeia);

            $opcionalVazio = !in_array('obrigatorio', $regras, true)
                && ($valor === null || $valor === '');

            foreach ($regras as $regra) {
                if ($regra === '') {
                    continue;
                }
                [$nome, $argumentos] = $this->separarRegra($regra);

                if ($opcionalVazio && $nome !== 'obrigatorio') {
                    continue;
                }

                $this->aplicar($nome, $campo, $valor, $argumentos);

                // Nao acumula varios erros para o mesmo campo: mostra o primeiro.
                if (isset($this->erros[$campo])) {
                    break;
                }
            }
        }
    }

    /** @return array{0: string, 1: string[]} */
    private function separarRegra(string $regra): array
    {
        if (!str_contains($regra, ':')) {
            return [$regra, []];
        }
        [$nome, $args] = explode(':', $regra, 2);
        return [$nome, explode(',', $args)];
    }

    private function aplicar(string $nome, string $campo, mixed $valor, array $args): void
    {
        $rotulo = $this->rotulo($campo);

        switch ($nome) {
            case 'obrigatorio':
                if ($valor === null || $valor === '' || (is_array($valor) && $valor === [])) {
                    $this->adicionar($campo, "O campo {$rotulo} e obrigatorio.");
                }
                break;

            case 'email':
                if (!filter_var((string) $valor, FILTER_VALIDATE_EMAIL)) {
                    $this->adicionar($campo, "O campo {$rotulo} deve conter um endereco de e-mail valido.");
                }
                break;

            case 'min':
                $minimo = (int) ($args[0] ?? 0);
                if (mb_strlen((string) $valor) < $minimo) {
                    $this->adicionar($campo, "O campo {$rotulo} deve ter pelo menos {$minimo} caracteres.");
                }
                break;

            case 'max':
                $maximo = (int) ($args[0] ?? 0);
                if (mb_strlen((string) $valor) > $maximo) {
                    $this->adicionar($campo, "O campo {$rotulo} nao pode exceder {$maximo} caracteres.");
                }
                break;

            case 'inteiro':
                if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
                    $this->adicionar($campo, "O campo {$rotulo} deve ser um numero inteiro.");
                }
                break;

            case 'decimal':
                if (!is_numeric($valor)) {
                    $this->adicionar($campo, "O campo {$rotulo} deve ser um valor numerico.");
                }
                break;

            case 'minimo':
                if (!is_numeric($valor) || (float) $valor < (float) ($args[0] ?? 0)) {
                    $this->adicionar($campo, "O campo {$rotulo} nao pode ser inferior a {$args[0]}.");
                }
                break;

            case 'maximo':
                if (!is_numeric($valor) || (float) $valor > (float) ($args[0] ?? 0)) {
                    $this->adicionar($campo, "O campo {$rotulo} nao pode ser superior a {$args[0]}.");
                }
                break;

            case 'em':
                if (!in_array((string) $valor, $args, true)) {
                    $this->adicionar($campo, "O valor selecionado em {$rotulo} nao e valido.");
                }
                break;

            case 'data':
                if (!$this->ehData((string) $valor)) {
                    $this->adicionar($campo, "O campo {$rotulo} deve conter uma data valida (AAAA-MM-DD).");
                }
                break;

            case 'data_futura':
                if (!$this->ehData((string) $valor)) {
                    $this->adicionar($campo, "O campo {$rotulo} deve conter uma data valida.");
                } elseif (new DateTimeImmutable((string) $valor) <= new DateTimeImmutable('today')) {
                    $this->adicionar($campo, "O campo {$rotulo} deve ser uma data futura.");
                }
                break;

            case 'data_passada_ou_hoje':
                if (!$this->ehData((string) $valor)) {
                    $this->adicionar($campo, "O campo {$rotulo} deve conter uma data valida.");
                } elseif (new DateTimeImmutable((string) $valor) > new DateTimeImmutable('today')) {
                    $this->adicionar($campo, "O campo {$rotulo} nao pode ser uma data futura.");
                }
                break;

            case 'telefone':
                if (!preg_match('/^\+?[0-9 ()\-]{6,20}$/', (string) $valor)) {
                    $this->adicionar($campo, "O campo {$rotulo} contem um numero de telefone invalido.");
                }
                break;

            case 'confirmado':
                if ((string) $valor !== (string) ($this->dados[$campo . '_confirmacao'] ?? '')) {
                    $this->adicionar($campo, "A confirmacao de {$rotulo} nao coincide.");
                }
                break;

            case 'palavra_passe_forte':
                if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', (string) $valor)) {
                    $this->adicionar(
                        $campo,
                        "O campo {$rotulo} deve ter no minimo 8 caracteres, incluindo letras e numeros."
                    );
                }
                break;

            case 'unico':
                // unico:tabela,coluna[,idAIgnorar]
                if (count($args) < 2) {
                    throw new InvalidArgumentException('A regra "unico" exige tabela e coluna.');
                }
                if ($this->jaExiste($args[0], $args[1], (string) $valor, (int) ($args[2] ?? 0))) {
                    $this->adicionar($campo, "Ja existe um registo com este {$rotulo}.");
                }
                break;

            case 'existe':
                // existe:tabela[,coluna]
                if ($args === []) {
                    throw new InvalidArgumentException('A regra "existe" exige a tabela.');
                }
                if (!$this->existeRegisto($args[0], $args[1] ?? 'id', (string) $valor)) {
                    $this->adicionar($campo, "O valor selecionado em {$rotulo} nao existe.");
                }
                break;

            default:
                throw new InvalidArgumentException("Regra de validacao desconhecida: {$nome}");
        }
    }

    private function ehData(string $valor): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $valor);
        return $d !== false && $d->format('Y-m-d') === $valor;
    }

    private function jaExiste(string $tabela, string $coluna, string $valor, int $ignorarId): bool
    {
        $tabela = $this->identificadorSeguro($tabela);
        $coluna = $this->identificadorSeguro($coluna);

        $sql = "SELECT COUNT(*) FROM `{$tabela}` WHERE `{$coluna}` = :valor";
        $parametros = ['valor' => $valor];

        if ($ignorarId > 0) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $ignorarId;
        }

        $stmt = Database::ligacao()->prepare($sql);
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function existeRegisto(string $tabela, string $coluna, string $valor): bool
    {
        $tabela = $this->identificadorSeguro($tabela);
        $coluna = $this->identificadorSeguro($coluna);

        $stmt = Database::ligacao()->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE `{$coluna}` = :valor");
        $stmt->execute(['valor' => $valor]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Identificadores nao podem ser parametrizados por PDO: valida-se o formato. */
    private function identificadorSeguro(string $identificador): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identificador)) {
            throw new InvalidArgumentException("Identificador SQL invalido: {$identificador}");
        }
        return $identificador;
    }
}
