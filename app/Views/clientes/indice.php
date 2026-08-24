<?php
/** @var array $pagina */
/** @var string $pesquisa */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Clientes</h1>
    <a href="<?= url('/clientes/criar') ?>" class="btn btn-success">
        <i class="bi bi-person-plus me-1"></i>Novo cliente
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-sm-8 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Nome, NUIT ou telefone..." value="<?= e($pesquisa) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Pesquisar</button>
                </div>
            </div>
            <?php if ($pesquisa !== ''): ?>
                <div class="col-auto">
                    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">Limpar</a>
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
                        <th class="text-center">Compras</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="5" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontrados clientes.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $cliente): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/clientes/' . $cliente['id']) ?>" class="fw-semibold text-decoration-none">
                                    <?= e($cliente['nome']) ?>
                                </a>
                            </td>
                            <td class="d-none d-lg-table-cell"><?= e($cliente['nuit'] ?: '-') ?></td>
                            <td class="d-none d-md-table-cell small">
                                <?= e($cliente['telefone'] ?: '-') ?><br>
                                <span class="text-muted"><?= e($cliente['email'] ?: '') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-suave"><?= (int) $cliente['total_compras'] ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/clientes/' . $cliente['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Ver ficha">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/clientes/' . $cliente['id'] . '/editar') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/clientes/' . $cliente['id'] . '/eliminar') ?>"
                                      class="d-inline"
                                      data-confirmar="Eliminar o cliente &quot;<?= e($cliente['nome']) ?>&quot;?">
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
