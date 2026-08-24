<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Fornecedor extends Model
{
    protected string $tabela = 'fornecedores';
    protected array $preenchiveis = ['nome', 'nuit', 'telefone', 'email', 'endereco', 'ativo'];
    protected string $ordemOmissao = 'nome ASC';

    public function paginar(string $pesquisa, int $pagina): array
    {
        $onde = '1 = 1';
        $parametros = [];

        if ($pesquisa !== '') {
            $onde = $this->condicaoPesquisa(['f.nome', 'f.nuit', 'f.email'], $pesquisa, $parametros);
        }

        return $this->paginarConsulta(
            "SELECT f.*, (SELECT COUNT(*) FROM lotes l WHERE l.fornecedor_id = f.id) AS total_lotes
             FROM fornecedores f WHERE {$onde} ORDER BY f.nome ASC",
            "SELECT COUNT(*) FROM fornecedores f WHERE {$onde}",
            $parametros,
            $pagina
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function ativos(): array
    {
        return $this->bd()
            ->query('SELECT id, nome FROM fornecedores WHERE ativo = 1 ORDER BY nome ASC')
            ->fetchAll();
    }

    public function temLotes(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM lotes WHERE fornecedor_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
