<?php

use App\Core\Auth;
use App\Models\Venda;

/**
 * @var array $venda
 * @var array $itens
 */
$anulada   = $venda['estado'] === 'anulada';
$podeAnular = Auth::temPerfil('farmaceutico') && !$anulada;
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/vendas') ?>">Vendas</a></li>
        <li class="breadcrumb-item active"><?= e($venda['numero']) ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-0">
            <?= e($venda['numero']) ?>
            <span class="badge text-bg-<?= $anulada ? 'danger' : 'success' ?> align-middle">
                <?= e(ucfirst($venda['estado'])) ?>
            </span>
        </h1>
        <p class="text-muted small mb-0"><?= data($venda['data_venda'], true) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/vendas/' . $venda['id'] . '/recibo') ?>" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-receipt me-1"></i>Recibo
        </a>
        <a href="<?= url('/vendas/criar') ?>" class="btn btn-success">
            <i class="bi bi-cart-plus me-1"></i>Nova venda
        </a>
    </div>
</div>

<?php if ($anulada): ?>
    <div class="alert alert-danger">
        <i class="bi bi-x-octagon me-1"></i>
        Esta venda foi anulada e o stock reposto nos lotes de origem. O registo mantem-se para efeitos de auditoria.
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-basket me-1"></i>Itens vendidos
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th class="d-none d-md-table-cell">Lote</th>
                                <th class="text-center">Qtd.</th>
                                <th class="text-end">Preco unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('/medicamentos/' . $item['medicamento_id']) ?>"
                                           class="fw-semibold text-decoration-none">
                                            <?= e($item['medicamento']) ?>
                                        </a>
                                        <div class="small text-muted"><?= e($item['codigo']) ?></div>
                                    </td>
                                    <td class="d-none d-md-table-cell small">
                                        <code><?= e($item['numero_lote']) ?></code>
                                        <div class="text-muted">Val. <?= data($item['data_validade']) ?></div>
                                    </td>
                                    <td class="text-center"><?= (int) $item['quantidade'] ?></td>
                                    <td class="text-end"><?= moeda($item['preco_unitario']) ?></td>
                                    <td class="text-end fw-semibold"><?= moeda($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end text-muted">Subtotal</td>
                                <td class="text-end"><?= moeda($venda['subtotal']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Desconto</td>
                                <td class="text-end">- <?= moeda($venda['desconto']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-semibold h5 mb-0"><?= moeda($venda['total']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-1"></i>Dados da venda
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Cliente</dt>
                    <dd class="col-7">
                        <?php if ($venda['cliente_id'] !== null): ?>
                            <a href="<?= url('/clientes/' . $venda['cliente_id']) ?>" class="text-decoration-none">
                                <?= e($venda['cliente']) ?>
                            </a>
                            <?php if ($venda['cliente_nuit']): ?>
                                <span class="text-muted d-block">NUIT <?= e($venda['cliente_nuit']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            Consumidor final
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Operador</dt>
                    <dd class="col-7"><?= e($venda['operador']) ?></dd>

                    <dt class="col-5 text-muted">Pagamento</dt>
                    <dd class="col-7">
                        <?= e(Venda::FORMAS_PAGAMENTO[$venda['forma_pagamento']] ?? $venda['forma_pagamento']) ?>
                    </dd>

                    <dt class="col-5 text-muted">Observacoes</dt>
                    <dd class="col-7"><?= e($venda['observacoes'] ?: '-') ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($podeAnular): ?>
            <div class="card border-danger">
                <div class="card-header bg-white fw-semibold text-danger">
                    <i class="bi bi-x-octagon me-1"></i>Anular venda
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        A anulacao repoe as quantidades nos lotes de origem e fica registada no historico de movimentos.
                        A venda nao e eliminada.
                    </p>
                    <form method="post" action="<?= url('/vendas/' . $venda['id'] . '/anular') ?>"
                          class="needs-validation" novalidate
                          data-confirmar="Confirma a anulacao desta venda? O stock sera reposto.">
                        <?= csrf() ?>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo da anulacao</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="2"
                                      required minlength="5" maxlength="200"
                                      placeholder="Ex.: devolucao do cliente"></textarea>
                            <div class="invalid-feedback">Indique o motivo (minimo 5 caracteres).</div>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i>Anular venda
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
