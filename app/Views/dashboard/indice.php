<?php

use App\Core\Auth;

/**
 * @var array $resumoDia
 * @var array $resumoMes
 * @var int   $totalMedicamentos
 * @var int   $totalClientes
 * @var float $valorStock
 * @var array $stockBaixo
 * @var array $lotesAExpirar
 * @var array $lotesExpirados
 * @var array $maisVendidos
 * @var array $serie
 */
$podeGerir = Auth::temPerfil('farmaceutico');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h1 class="h3 mb-1">Painel de controlo</h1>
        <p class="text-muted mb-0"><?= e(dataExtenso()) ?></p>
    </div>
    <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
        <i class="bi bi-cart-plus me-1"></i>Nova venda
    </a>
</div>

<!-- Indicadores -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card card-indicador h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small mb-1">Vendas de hoje</p>
                    <p class="valor mb-0"><?= moeda($resumoDia['receita']) ?></p>
                    <p class="small text-muted mb-0"><?= (int) $resumoDia['vendas'] ?> transacao(oes)</p>
                </div>
                <i class="bi bi-cash-coin icone text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card card-indicador h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small mb-1">Receita do mes</p>
                    <p class="valor mb-0"><?= moeda($resumoMes['receita']) ?></p>
                    <p class="small text-muted mb-0"><?= (int) $resumoMes['vendas'] ?> transacao(oes)</p>
                </div>
                <i class="bi bi-graph-up-arrow icone text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card card-indicador h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small mb-1">Valor do stock</p>
                    <p class="valor mb-0"><?= moeda($valorStock) ?></p>
                    <p class="small text-muted mb-0"><?= (int) $totalMedicamentos ?> medicamentos ativos</p>
                </div>
                <i class="bi bi-boxes icone text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card card-indicador h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small mb-1">Clientes</p>
                    <p class="valor mb-0"><?= numero($totalClientes) ?></p>
                    <p class="small text-muted mb-0">registados no sistema</p>
                </div>
                <i class="bi bi-people icone text-info"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Grafico -->
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i>Vendas dos ultimos 7 dias
            </div>
            <div class="card-body">
                <div class="grafico-vendas">
                    <canvas id="graficoVendas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Mais vendidos -->
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-trophy me-1"></i>Medicamentos mais vendidos
            </div>
            <div class="card-body p-0">
                <?php if ($maisVendidos === []): ?>
                    <p class="tabela-vazia mb-0">Ainda nao existem vendas registadas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Medicamento</th>
                                    <th class="text-end">Unidades</th>
                                    <th class="text-end">Receita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maisVendidos as $linha): ?>
                                    <tr>
                                        <td><?= e($linha['nome']) ?></td>
                                        <td class="text-end"><?= numero($linha['unidades']) ?></td>
                                        <td class="text-end"><?= moeda($linha['receita']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alertas operacionais -->
<div class="row g-3 mt-1">
    <div class="col-12 col-lg-4">
        <div class="card h-100 border-warning-subtle">
            <div class="card-header bg-warning-subtle fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-1"></i>Stock abaixo do minimo</span>
                <span class="badge text-bg-warning"><?= count($stockBaixo) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($stockBaixo === []): ?>
                    <p class="tabela-vazia mb-0">Todos os medicamentos tem stock suficiente.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($stockBaixo, 0, 6) as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="<?= url('/medicamentos/' . $item['medicamento_id']) ?>"
                                   class="text-decoration-none text-body">
                                    <?= e($item['nome']) ?>
                                </a>
                                <span class="badge text-bg-<?= (int) $item['stock_disponivel'] === 0 ? 'danger' : 'warning' ?>">
                                    <?= (int) $item['stock_disponivel'] ?> / <?= (int) $item['stock_minimo'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($stockBaixo) > 6 && $podeGerir): ?>
                        <div class="card-footer bg-white text-center py-2">
                            <a href="<?= url('/medicamentos?stock=baixo') ?>" class="small">Ver todos</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100 border-info-subtle">
            <div class="card-header bg-info-subtle fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-1"></i>Lotes a expirar (90 dias)</span>
                <span class="badge text-bg-info"><?= count($lotesAExpirar) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($lotesAExpirar === []): ?>
                    <p class="tabela-vazia mb-0">Nenhum lote expira nos proximos 90 dias.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($lotesAExpirar, 0, 6) as $lote): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold small"><?= e($lote['medicamento']) ?></div>
                                        <div class="text-muted small">Lote <?= e($lote['numero_lote']) ?> &middot;
                                            <?= (int) $lote['quantidade_atual'] ?> un.</div>
                                    </div>
                                    <span class="badge text-bg-secondary"><?= diasAte($lote['data_validade']) ?> dias</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($podeGerir): ?>
                        <div class="card-footer bg-white text-center py-2">
                            <a href="<?= url('/lotes?filtro=a_expirar') ?>" class="small">Ver todos</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100 border-danger-subtle">
            <div class="card-header bg-danger-subtle fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-x-octagon me-1"></i>Lotes expirados em stock</span>
                <span class="badge text-bg-danger"><?= count($lotesExpirados) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if ($lotesExpirados === []): ?>
                    <p class="tabela-vazia mb-0">Nao ha lotes expirados por retirar.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($lotesExpirados, 0, 6) as $lote): ?>
                            <li class="list-group-item">
                                <div class="fw-semibold small"><?= e($lote['medicamento']) ?></div>
                                <div class="text-muted small">
                                    Lote <?= e($lote['numero_lote']) ?> &middot; expirou em
                                    <?= data($lote['data_validade']) ?> &middot; <?= (int) $lote['quantidade_atual'] ?> un.
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($podeGerir): ?>
                        <div class="card-footer bg-white text-center py-2">
                            <a href="<?= url('/lotes?filtro=expirados') ?>" class="small">Tratar lotes expirados</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('/assets/vendor/chart.umd.min.js') ?>"></script>
<script>
    (function () {
        const dados = <?= json_encode($serie, JSON_THROW_ON_ERROR) ?>;
        const etiquetas = dados.map(function (d) {
            const partes = d.dia.split('-');
            return partes[2] + '/' + partes[1];
        });

        new Chart(document.getElementById('graficoVendas'), {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Receita (MZN)',
                    data: dados.map(function (d) { return d.valor; }),
                    backgroundColor: 'rgba(13, 110, 95, .75)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v + ' MZN'; } } } }
            }
        });
    })();
</script>
