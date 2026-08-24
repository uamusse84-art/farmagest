<?php

use App\Core\Auth;
use App\Models\Utilizador;

/**
 * @var array  $pagina
 * @var string $pesquisa
 * @var string $perfil
 */
$cores = ['administrador' => 'danger', 'farmaceutico' => 'primary', 'caixa' => 'secondary'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Utilizadores</h1>
    <a href="<?= url('/utilizadores/criar') ?>" class="btn btn-success">
        <i class="bi bi-person-plus me-1"></i>Novo utilizador
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Nome ou e-mail..." value="<?= e($pesquisa) ?>">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="perfil" class="form-select">
                    <option value="">Todos os perfis</option>
                    <?php foreach (Utilizador::PERFIS as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $perfil === $chave ? 'selected' : '' ?>>
                            <?= e($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2 d-grid">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="d-none d-md-table-cell">E-mail</th>
                        <th>Perfil</th>
                        <th class="d-none d-lg-table-cell">Ultimo acesso</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="6" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontrados utilizadores.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $utilizador): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold"><?= e($utilizador['nome']) ?></span>
                                <?php if ((int) $utilizador['id'] === Auth::id()): ?>
                                    <span class="badge badge-suave ms-1">Voce</span>
                                <?php endif; ?>
                                <div class="small text-muted d-md-none"><?= e($utilizador['email']) ?></div>
                            </td>
                            <td class="d-none d-md-table-cell small"><?= e($utilizador['email']) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $cores[$utilizador['perfil']] ?? 'secondary' ?>">
                                    <?= e(Utilizador::PERFIS[$utilizador['perfil']] ?? $utilizador['perfil']) ?>
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell small">
                                <?= $utilizador['ultimo_acesso'] ? data($utilizador['ultimo_acesso'], true) : 'Nunca' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-<?= (int) $utilizador['ativo'] === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int) $utilizador['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/utilizadores/' . $utilizador['id'] . '/editar') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int) $utilizador['id'] !== Auth::id()): ?>
                                    <form method="post" action="<?= url('/utilizadores/' . $utilizador['id'] . '/eliminar') ?>"
                                          class="d-inline"
                                          data-confirmar="Eliminar o utilizador &quot;<?= e($utilizador['nome']) ?>&quot;?">
                                        <?= csrf() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require BASE_PATH . '/app/Views/layouts/_paginacao.php'; ?>
    </div>
</div>
