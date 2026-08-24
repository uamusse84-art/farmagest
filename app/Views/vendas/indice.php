<?php

use App\Models\Venda;

/**
 * @var array  $pagina
 * @var string $pesquisa
 * @var string $estado
 * @var string $de
 * @var string $ate
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Vendas</h1>
    <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
        <i class="bi bi-cart-plus me-1"></i>Nova venda
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Numero ou cliente..." value="<?= e($pesquisa) ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Todos os estados</option>
                    <option value="concluida" <?= $estado === 'concluida' ? 'selected' : '' ?>>Concluidas</option>
                    <option value="anulada" <?= $estado === 'anulada' ? 'selected' : '' ?>>Anuladas</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="de" class="form-control" value="<?= e($de) ?>" title="Data inicial">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="ate" class="form-control" value="<?= e($ate) ?>" title="Data final">
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Data</th>
                        <th class="d-none d-md-table-cell">Cliente</th>
                        <th class="d-none d-lg-table-cell">Operador</th>
                        <th class="text-center">Itens</th>
                        <th class="d-none d-lg-table-cell">Pagamento</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="9" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontradas vendas com os filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $venda): ?>
                        <tr class="<?= $venda['estado'] === 'anulada' ? 'text-muted' : '' ?>">
                            <td>
                                <a href="<?= url('/vendas/' . $venda['id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($venda['numero']) ?>
                                </a>
                            </td>
                            <td class="small text-nowrap"><?= data($venda['data_venda'], true) ?></td>
                            <td class="d-none d-md-table-cell"><?= e($venda['cliente'] ?: 'Consumidor final') ?></td>
                            <td class="d-none d-lg-table-cell small"><?= e($venda['operador']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-suave"><?= (int) $venda['total_itens'] ?></span>
                            </td>
                            <td class="d-none d-lg-table-cell small">
                                <?= e(Venda::FORMAS_PAGAMENTO[$venda['forma_pagamento']] ?? $venda['forma_pagamento']) ?>
                            </td>
                            <td class="text-end fw-semibold"><?= moeda($venda['total']) ?></td>
                            <td class="text-center">
                                <span class="badge text-bg-<?= $venda['estado'] === 'concluida' ? 'success' : 'danger' ?>">
                                    <?= e(ucfirst($venda['estado'])) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/vendas/' . $venda['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Ver detalhe">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/vendas/' . $venda['id'] . '/recibo') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Recibo" target="_blank">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require BASE_PATH . '/app/Views/layouts/_paginacao.php'; ?>
    </div>
</div>
