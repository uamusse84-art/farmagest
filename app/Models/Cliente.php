<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Cliente extends Model
{
    protected string $tabela = 'clientes';
    protected array $preenchiveis = ['nome', 'nuit', 'telefone', 'email', 'endereco'];
    protected string $ordemOmissao = 'nome ASC';

    public function paginar(string $pesquisa, int $pagina): array
    {
        $onde = '1 = 1';
        $parametros = [];

        if ($pesquisa !== '') {
            $onde = $this->condicaoPesquisa(['c.nome', 'c.nuit', 'c.telefone'], $pesquisa, $parametros);
        }

        return $this->paginarConsulta(
            "SELECT c.*, (SELECT COUNT(*) FROM vendas v WHERE v.cliente_id = c.id AND v.estado = 'concluida') AS total_compras
             FROM clientes c WHERE {$onde} ORDER BY c.nome ASC",
            "SELECT COUNT(*) FROM clientes c WHERE {$onde}",
            $parametros,
            $pagina
        );
    }

    public function temVendas(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM vendas WHERE cliente_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function historico(int $id, int $limite = 20): array
    {
        $stmt = $this->bd()->prepare(
            'SELECT v.id, v.numero, v.data_venda, v.total, v.estado
             FROM vendas v WHERE v.cliente_id = :id
             ORDER BY v.data_venda DESC LIMIT ' . max(1, $limite)
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
