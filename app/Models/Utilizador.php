<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Utilizador extends Model
{
    protected string $tabela = 'utilizadores';
    protected array $preenchiveis = ['nome', 'email', 'palavra_passe', 'perfil', 'telefone', 'ativo'];
    protected string $ordemOmissao = 'nome ASC';

    public const PERFIS = [
        'administrador' => 'Administrador',
        'farmaceutico'  => 'Farmaceutico',
        'caixa'         => 'Operador de caixa',
    ];

    public function porEmail(string $email): ?array
    {
        $stmt = $this->bd()->prepare('SELECT * FROM utilizadores WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $registo = $stmt->fetch();
        return $registo === false ? null : $registo;
    }

    public function registarAcesso(int $id): void
    {
        $this->bd()
            ->prepare('UPDATE utilizadores SET ultimo_acesso = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function paginar(string $pesquisa, ?string $perfil, int $pagina): array
    {
        $condicoes  = ['1 = 1'];
        $parametros = [];

        if ($pesquisa !== '') {
            $condicoes[] = $this->condicaoPesquisa(['nome', 'email'], $pesquisa, $parametros);
        }
        if ($perfil !== null && $perfil !== '') {
            $condicoes[] = 'perfil = :perfil';
            $parametros['perfil'] = $perfil;
        }

        $onde = implode(' AND ', $condicoes);

        return $this->paginarConsulta(
            "SELECT * FROM utilizadores WHERE {$onde} ORDER BY nome ASC",
            "SELECT COUNT(*) FROM utilizadores WHERE {$onde}",
            $parametros,
            $pagina
        );
    }

    public function contarAdministradoresAtivos(): int
    {
        return $this->contar("perfil = 'administrador' AND ativo = 1");
    }

    /** Impede que o sistema fique sem nenhum administrador ativo. */
    public function ehUltimoAdministrador(int $id): bool
    {
        $utilizador = $this->encontrar($id);
        if ($utilizador === null || $utilizador['perfil'] !== 'administrador') {
            return false;
        }
        return $this->contarAdministradoresAtivos() <= 1;
    }

    public function temMovimentos(int $id): bool
    {
        $stmt = $this->bd()->prepare('SELECT COUNT(*) FROM vendas WHERE utilizador_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
