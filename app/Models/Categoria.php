<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Categoria extends Model
{
    protected string $tabela = 'categorias';
    protected array $preenchiveis = ['nome', 'descricao'];
    protected string $ordemOmissao = 'nome ASC';

    public function paginar(string $pesquisa, int $pagina): array
    {
        $onde = $pesquisa !== '' ? 'c.nome LIKE :pesquisa' : '1 = 1';
        $parametros = $pesquisa !== '' ? ['pesquisa' => '%' . $pesquisa . '%'] : [];

        return $this->paginarConsulta(
            "SELECT c.*, (SELECT COUNT(*) FROM medicamentos m WHERE m.categoria_id = c.id) AS total_medicamentos
             FROM categorias c WHERE {$onde} ORDER BY c.nome ASC",
            "SELECT COUNT(*) FROM categorias c WHERE {$onde}",
            $parametros,
            $pagina
        );
    }

    public function temMedicamentos(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM medicamentos WHERE categoria_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
