<?php

use App\Models\Venda;

/**
 * @var string $de
 * @var string $ate
 * @var array  $linhas
 * @var float  $total
 */
$porPagamento = [];
$porOperador  = [];

foreach ($linhas as $linha) {
    $forma = (string) $linha['forma_pagamento'];
    $porPagamento[$forma] = ($porPagamento[$forma] ?? 0.0) + (float) $linha['total'];

    $operador = (string) $linha['operador'];
    $porOperador[$operador] = ($porOperador[$operador] ?? 0.0) + (float) $linha['total'];
}
arsort($porPagamento);
arsort($porOperador);

$numero = count($linhas);
?>
<nav aria-label="Navegacao" class="sem-impressao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/relatorios') ?>">Relatorios</a></li>
        <li class="breadcrumb-item active">Vendas</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-0">Relatorio de vendas</h1>
        <p class="text-muted small mb-0">
            Periodo de <?= data($de) ?> a <?= data($ate) ?> &middot; vendas concluidas
        </p>
    </div>
    <button type="button" class="btn btn-outline-primary sem-impressao" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<div class="card mb-3 sem-impressao">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="de" class="form-label">Data inicial</label>
                <input type="date" id="de" name="de" class="form-control" value="<?= e($de) ?>" required>
            </div>
            <div class="col-6 col-md-3">
                <label for="ate" class="form-label">Data final</label>
                <input type="date" id="ate" name="ate" class="form-control" value="<?= e($ate) ?>" required>
            </div>
            <div class="col-12 col-md-3 d-grid">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Gerar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Vendas no periodo</p>
                <p class="h3 mb-0"><?= $numero ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Receita total</p>
                <p class="h3 mb-0 text-success"><?= moeda($total) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Ticket medio</p>
                <p class="h3 mb-0"><?= moeda($numero > 0 ? $total / $numero : 0) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Por forma de pagamento</div>
            <div class="card-body p-0">
                <?php if ($porPagamento === []): ?>
                    <p class="tabela-vazia mb-0">Sem dados.</p>
                <?php else: ?>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <?php foreach ($porPagamento as $forma => $valor): ?>
                                <tr>
                                    <td><?= e(Venda::FORMAS_PAGAMENTO[$forma] ?? $forma) ?></td>
                                    <td class="text-end"><?= moeda($valor) ?></td>
                                    <td class="text-end text-muted small" style="width: 70px;">
                                        <?= $total > 0 ? numero($valor / $total * 100, 1) : '0,0' ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Por operador</div>
            <div class="card-body p-0">
                <?php if ($porOperador === []): ?>
                    <p class="tabela-vazia mb-0">Sem dados.</p>
                <?php else: ?>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <?php foreach ($porOperador as $operador => $valor): ?>
                                <tr>
                                    <td><?= e($operador) ?></td>
                                    <td class="text-end"><?= moeda($valor) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Detalhe das vendas</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th class="d-none d-md-table-cell">Operador</th>
                        <th class="d-none d-md-table-cell">Pagamento</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($linhas === []): ?>
                        <tr>
                            <td colspan="6" class="tabela-vazia">
                                Nao existem vendas concluidas no periodo seleccionado.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($linhas as $linha): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($linha['numero']) ?></td>
                            <td class="small text-nowrap"><?= data($linha['data_venda'], true) ?></td>
                            <td><?= e($linha['cliente']) ?></td>
                            <td class="d-none d-md-table-cell small"><?= e($linha['operador']) ?></td>
                            <td class="d-none d-md-table-cell small">
                                <?= e(Venda::FORMAS_PAGAMENTO[$linha['forma_pagamento']] ?? $linha['forma_pagamento']) ?>
                            </td>
                            <td class="text-end"><?= moeda($linha['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-semibold">Total do periodo</td>
                        <td class="text-end fw-semibold"><?= moeda($total) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
