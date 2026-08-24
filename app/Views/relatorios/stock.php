<?php

/**
 * @var array $stockBaixo
 * @var array $lotesAExpirar
 * @var array $lotesExpirados
 * @var float $valorStock
 */
?>
<nav aria-label="Navegacao" class="sem-impressao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/relatorios') ?>">Relatorios</a></li>
        <li class="breadcrumb-item active">Stock</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-0">Relatorio de stock</h1>
        <p class="text-muted small mb-0">Emitido em <?= dataExtenso() ?></p>
    </div>
    <button type="button" class="btn btn-outline-primary sem-impressao" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Valor do stock valido</p>
                <p class="h4 mb-0 text-success"><?= moeda($valorStock) ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Abaixo do minimo</p>
                <p class="h4 mb-0 text-warning"><?= count($stockBaixo) ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Lotes a expirar</p>
                <p class="h4 mb-0 text-warning"><?= count($lotesAExpirar) ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-indicador h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Lotes expirados</p>
                <p class="h4 mb-0 text-danger"><?= count($lotesExpirados) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>Medicamentos abaixo do stock minimo
    </div>
    <div class="card-body p-0">
        <?php if ($stockBaixo === []): ?>
            <p class="tabela-vazia mb-0">Todos os medicamentos estao acima do stock minimo definido.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Medicamento</th>
                            <th class="text-center">Disponivel</th>
                            <th class="text-center">Minimo</th>
                            <th class="text-center">Em falta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockBaixo as $item): ?>
                            <?php $falta = max(0, (int) $item['stock_minimo'] - (int) $item['stock_disponivel']); ?>
                            <tr>
                                <td><code class="small"><?= e($item['codigo']) ?></code></td>
                                <td>
                                    <a href="<?= url('/medicamentos/' . $item['medicamento_id']) ?>"
                                       class="text-decoration-none"><?= e($item['nome']) ?></a>
                                </td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= (int) $item['stock_disponivel'] === 0 ? 'danger' : 'warning' ?>">
                                        <?= (int) $item['stock_disponivel'] ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= (int) $item['stock_minimo'] ?></td>
                                <td class="text-center fw-semibold"><?= $falta ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-hourglass-split me-1 text-warning"></i>Lotes a expirar nos proximos 90 dias
    </div>
    <div class="card-body p-0">
        <?php if ($lotesAExpirar === []): ?>
            <p class="tabela-vazia mb-0">Nenhum lote expira nos proximos 90 dias.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Medicamento</th>
                            <th>Validade</th>
                            <th class="text-center">Dias</th>
                            <th class="text-center">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lotesAExpirar as $lote): ?>
                            <?php $dias = diasAte($lote['data_validade']); ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/lotes/' . $lote['id']) ?>" class="text-decoration-none">
                                        <code class="small"><?= e($lote['numero_lote']) ?></code>
                                    </a>
                                </td>
                                <td><?= e($lote['medicamento']) ?></td>
                                <td class="small"><?= data($lote['data_validade']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= $dias <= 30 ? 'danger' : 'warning' ?>"><?= $dias ?></span>
                                </td>
                                <td class="text-center"><?= (int) $lote['quantidade_atual'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-x-octagon me-1 text-danger"></i>Lotes expirados com stock por abater
    </div>
    <div class="card-body p-0">
        <?php if ($lotesExpirados === []): ?>
            <p class="tabela-vazia mb-0">Nao existem lotes expirados com stock.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Medicamento</th>
                            <th>Expirou em</th>
                            <th class="text-center">Dias</th>
                            <th class="text-center">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lotesExpirados as $lote): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/lotes/' . $lote['id']) ?>" class="text-decoration-none">
                                        <code class="small"><?= e($lote['numero_lote']) ?></code>
                                    </a>
                                </td>
                                <td><?= e($lote['medicamento']) ?></td>
                                <td class="small"><?= data($lote['data_validade']) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-danger"><?= abs(diasAte($lote['data_validade'])) ?></span>
                                </td>
                                <td class="text-center"><?= (int) $lote['quantidade_atual'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top small text-muted">
                Estas unidades estao bloqueadas para venda. Registe uma quebra no detalhe do lote para as retirar do inventario.
            </div>
        <?php endif; ?>
    </div>
</div>
