<?php
/** @var array $pagina */
/** @var string $pesquisa */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Categorias</h1>
    <a href="<?= url('/categorias/criar') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>Nova categoria
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-sm-8 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Pesquisar por nome..." value="<?= e($pesquisa) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Pesquisar</button>
                </div>
            </div>
            <?php if ($pesquisa !== ''): ?>
                <div class="col-auto">
                    <a href="<?= url('/categorias') ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="d-none d-md-table-cell">Descricao</th>
                        <th class="text-center">Medicamentos</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="4" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontradas categorias.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $categoria): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($categoria['nome']) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e($categoria['descricao'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge badge-suave"><?= (int) $categoria['total_medicamentos'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/categorias/' . $categoria['id'] . '/editar') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/categorias/' . $categoria['id'] . '/eliminar') ?>"
                                      class="d-inline"
                                      data-confirmar="Eliminar a categoria &quot;<?= e($categoria['nome']) ?>&quot;?">
                                    <?= csrf() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require BASE_PATH . '/app/Views/layouts/_paginacao.php'; ?>
    </div>
</div>
