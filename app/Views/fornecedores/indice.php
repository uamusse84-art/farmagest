<?php
/** @var array $pagina */
/** @var string $pesquisa */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Fornecedores</h1>
    <a href="<?= url('/fornecedores/criar') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>Novo fornecedor
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-sm-8 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Nome, NUIT ou e-mail..." value="<?= e($pesquisa) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Pesquisar</button>
                </div>
            </div>
            <?php if ($pesquisa !== ''): ?>
                <div class="col-auto">
                    <a href="<?= url('/fornecedores') ?>" class="btn btn-outline-secondary">Limpar</a>
                </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="d-none d-lg-table-cell">NUIT</th>
                        <th class="d-none d-md-table-cell">Contacto</th>
                        <th class="text-center">Lotes</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="6" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontrados fornecedores.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $fornecedor): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($fornecedor['nome']) ?></div>
                                <div class="small text-muted d-md-none"><?= e($fornecedor['telefone'] ?: '-') ?></div>
                            </td>
                            <td class="d-none d-lg-table-cell"><?= e($fornecedor['nuit'] ?: '-') ?></td>
                            <td class="d-none d-md-table-cell small">
                                <?= e($fornecedor['telefone'] ?: '-') ?><br>
                                <span class="text-muted"><?= e($fornecedor['email'] ?: '') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-suave"><?= (int) $fornecedor['total_lotes'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ((int) $fornecedor['ativo'] === 1): ?>
                                    <span class="badge text-bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/fornecedores/' . $fornecedor['id'] . '/editar') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/fornecedores/' . $fornecedor['id'] . '/eliminar') ?>"
                                      class="d-inline"
                                      data-confirmar="Eliminar o fornecedor &quot;<?= e($fornecedor['nome']) ?>&quot;?">
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
