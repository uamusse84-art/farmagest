<?php

use App\Core\Auth;

/**
 * @var array  $pagina
 * @var array  $categorias
 * @var string $pesquisa
 * @var int    $categoriaId
 * @var string $stock
 */
$podeGerir = Auth::temPerfil('farmaceutico');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Medicamentos</h1>
    <?php if ($podeGerir): ?>
        <a href="<?= url('/medicamentos/criar') ?>" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i>Novo medicamento
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Nome ou codigo..." value="<?= e($pesquisa) ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="categoria" class="form-select">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id'] ?>"
                            <?= $categoriaId === (int) $categoria['id'] ? 'selected' : '' ?>>
                            <?= e($categoria['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="stock" class="form-select">
                    <option value="">Todo o stock</option>
                    <option value="baixo" <?= $stock === 'baixo' ? 'selected' : '' ?>>Stock baixo</option>
                    <option value="esgotado" <?= $stock === 'esgotado' ? 'selected' : '' ?>>Esgotados</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Medicamento</th>
                        <th class="d-none d-lg-table-cell">Categoria</th>
                        <th class="text-end">Preco</th>
                        <th class="text-center">Stock</th>
                        <th class="d-none d-md-table-cell">Prox. validade</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="7" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontrados medicamentos com os filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $medicamento): ?>
                        <?php
                        $disponivel = (int) $medicamento['stock_disponivel'];
                        $minimo     = (int) $medicamento['stock_minimo'];
                        $classe     = $disponivel === 0 ? 'danger' : ($disponivel <= $minimo ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td><code class="small"><?= e($medicamento['codigo']) ?></code></td>
                            <td>
                                <a href="<?= url('/medicamentos/' . $medicamento['medicamento_id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($medicamento['nome']) ?>
                                </a>
                                <?php if ((int) $medicamento['requer_receita'] === 1): ?>
                                    <span class="badge text-bg-warning ms-1" title="Sujeito a receita medica">Rx</span>
                                <?php endif; ?>
                                <?php if ((int) $medicamento['ativo'] === 0): ?>
                                    <span class="badge text-bg-secondary ms-1">Inativo</span>
                                <?php endif; ?>
                                <div class="small text-muted d-lg-none"><?= e($medicamento['categoria']) ?></div>
                            </td>
                            <td class="d-none d-lg-table-cell"><?= e($medicamento['categoria']) ?></td>
                            <td class="text-end"><?= moeda($medicamento['preco_venda']) ?></td>
                            <td class="text-center">
                                <span class="badge text-bg-<?= $classe ?>" title="Minimo: <?= $minimo ?>">
                                    <?= $disponivel ?>
                                </span>
                                <?php if ((int) $medicamento['stock_expirado'] > 0): ?>
                                    <span class="badge text-bg-dark" title="Unidades em lotes expirados">
                                        <?= (int) $medicamento['stock_expirado'] ?> exp.
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell small"><?= data($medicamento['proxima_validade']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/medicamentos/' . $medicamento['medicamento_id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Ver detalhe">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($podeGerir): ?>
                                    <a href="<?= url('/medicamentos/' . $medicamento['medicamento_id'] . '/editar') ?>"
                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('/lotes/criar?medicamento=' . $medicamento['medicamento_id']) ?>"
                                       class="btn btn-sm btn-outline-success" title="Entrada de stock">
                                        <i class="bi bi-box-arrow-in-down"></i>
                                    </a>
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
