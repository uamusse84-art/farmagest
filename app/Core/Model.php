<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;

/**
 * Modelo base com as operacoes CRUD comuns. As subclasses declaram a tabela
 * e as colunas preenchiveis; tudo o resto e herdado.
 */
abstract class Model
{
    protected string $tabela = '';
    /** @var string[] */
    protected array $preenchiveis = [];
    protected string $chave = 'id';
    protected string $ordemOmissao = 'id DESC';

    protected function bd(): PDO
    {
        return Database::ligacao();
    }

    // ------------------------------ Leitura ------------------------------

    /** @return array<string, mixed>|null */
    public function encontrar(int $id): ?array
    {
        $stmt = $this->bd()->prepare("SELECT * FROM `{$this->tabela}` WHERE `{$this->chave}` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo === false ? null : $registo;
    }

    /** @return array<int, array<string, mixed>> */
    public function todos(?string $ordem = null): array
    {
        $ordem = $this->ordemSegura($ordem ?? $this->ordemOmissao);
        return $this->bd()->query("SELECT * FROM `{$this->tabela}` ORDER BY {$ordem}")->fetchAll();
    }

    public function contar(string $condicao = '1', array $parametros = []): int
    {
        $stmt = $this->bd()->prepare("SELECT COUNT(*) FROM `{$this->tabela}` WHERE {$condicao}");
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Constroi a condicao "(col1 LIKE :p OR col2 LIKE :p ...)" para uma pesquisa livre.
     *
     * Com prepares nativos (ATTR_EMULATE_PREPARES = false) o PDO nao aceita o mesmo
     * placeholder repetido, por isso cada coluna recebe um parametro proprio.
     *
     * @param array<int, string> $colunas    nomes de coluna ja qualificados
     * @param array<string, mixed> $parametros preenchido por referencia
     */
    protected function condicaoPesquisa(array $colunas, string $termo, array &$parametros): string
    {
        $partes = [];

        foreach (array_values($colunas) as $indice => $coluna) {
            $nome = 'pesquisa_' . $indice;
            $partes[] = "{$coluna} LIKE :{$nome}";
            $parametros[$nome] = '%' . $termo . '%';
        }

        return '(' . implode(' OR ', $partes) . ')';
    }

    /**
     * Paginacao generica sobre uma consulta SQL ja construida.
     *
     * @return array{dados: array, pagina: int, paginas: int, total: int, porPagina: int}
     */
    protected function paginarConsulta(string $sqlBase, string $sqlContagem, array $parametros, int $pagina, ?int $porPagina = null): array
    {
        $porPagina = $porPagina ?? (int) Config::obter('negocio.itens_por_pagina', 10);
        $pagina    = max(1, $pagina);

        $stmt = $this->bd()->prepare($sqlContagem);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina  = min($pagina, $paginas);
        $inicio  = ($pagina - 1) * $porPagina;

        $stmt = $this->bd()->prepare($sqlBase . " LIMIT {$porPagina} OFFSET {$inicio}");
        $stmt->execute($parametros);

        return [
            'dados'     => $stmt->fetchAll(),
            'pagina'    => $pagina,
            'paginas'   => $paginas,
            'total'     => $total,
            'porPagina' => $porPagina,
        ];
    }

    // ------------------------------ Escrita ------------------------------

    public function criar(array $dados): int
    {
        $dados = $this->filtrar($dados);
        if ($dados === []) {
            throw new InvalidArgumentException('Nao ha dados preenchiveis para inserir.');
        }

        $colunas     = array_keys($dados);
        $marcadores  = array_map(static fn (string $c): string => ':' . $c, $colunas);
        $listaColunas = '`' . implode('`, `', $colunas) . '`';

        $sql = "INSERT INTO `{$this->tabela}` ({$listaColunas}) VALUES (" . implode(', ', $marcadores) . ')';
        $this->bd()->prepare($sql)->execute($dados);

        return (int) $this->bd()->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $dados = $this->filtrar($dados);
        if ($dados === []) {
            return false;
        }

        $atribuicoes = implode(', ', array_map(
            static fn (string $c): string => "`{$c}` = :{$c}",
            array_keys($dados)
        ));

        $dados['__id'] = $id;
        $sql = "UPDATE `{$this->tabela}` SET {$atribuicoes} WHERE `{$this->chave}` = :__id";

        return $this->bd()->prepare($sql)->execute($dados);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->bd()->prepare("DELETE FROM `{$this->tabela}` WHERE `{$this->chave}` = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** Mantem apenas as chaves declaradas como preenchiveis (mass-assignment). */
    protected function filtrar(array $dados): array
    {
        return array_intersect_key($dados, array_flip($this->preenchiveis));
    }

    /** Aceita apenas "coluna ASC|DESC" para impedir injecao na clausula ORDER BY. */
    protected function ordemSegura(string $ordem): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?( (ASC|DESC))?$/i', $ordem)) {
            throw new InvalidArgumentException("Clausula ORDER BY invalida: {$ordem}");
        }
        return $ordem;
    }
}
