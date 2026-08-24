<?php

use App\Core\Config;
use App\Models\Venda;

/**
 * @var array $venda
 * @var array $itens
 */
?>
<div class="container py-4" style="max-width: 560px;">
    <div class="d-flex justify-content-between mb-3 sem-impressao">
        <a href="<?= url('/vendas/' . $venda['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>

    <div class="card">
        <div class="card-body recibo">
            <div class="text-center mb-3">
                <h1 class="h5 mb-0"><?= e(Config::obter('app.nome', 'FarmaGest')) ?></h1>
                <div class="small">Sistema de Gestao de Farmacia</div>
                <div class="small">Maputo &middot; Mocambique</div>
            </div>

            <hr>

            <div class="d-flex justify-content-between">
                <span>Recibo</span><span><strong><?= e($venda['numero']) ?></strong></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Data</span><span><?= data($venda['data_venda'], true) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Cliente</span><span><?= e($venda['cliente'] ?: 'Consumidor final') ?></span>
            </div>
            <?php if ($venda['cliente_nuit']): ?>
                <div class="d-flex justify-content-between">
                    <span>NUIT</span><span><?= e($venda['cliente_nuit']) ?></span>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
                <span>Operador</span><span><?= e($venda['operador']) ?></span>
            </div>

            <hr>

            <?php foreach ($itens as $item): ?>
                <div class="mb-2">
                    <div><?= e($item['medicamento']) ?></div>
                    <div class="d-flex justify-content-between">
                        <span><?= (int) $item['quantidade'] ?> x <?= moeda($item['preco_unitario']) ?></span>
                        <span><?= moeda($item['subtotal']) ?></span>
                    </div>
                    <div class="small">Lote <?= e($item['numero_lote']) ?></div>
                </div>
            <?php endforeach; ?>

            <hr>

            <div class="d-flex justify-content-between">
                <span>Subtotal</span><span><?= moeda($venda['subtotal']) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Desconto</span><span>- <?= moeda($venda['desconto']) ?></span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-6">
                <span>TOTAL</span><span><?= moeda($venda['total']) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Pagamento</span>
                <span><?= e(Venda::FORMAS_PAGAMENTO[$venda['forma_pagamento']] ?? $venda['forma_pagamento']) ?></span>
            </div>

            <?php if ($venda['estado'] === 'anulada'): ?>
                <hr>
                <p class="text-center fw-bold text-danger mb-0">*** VENDA ANULADA ***</p>
            <?php endif; ?>

            <hr>

            <p class="text-center small mb-0">
                Obrigado pela preferencia.<br>
                Documento sem valor fiscal &middot; emitido em <?= data(date('Y-m-d H:i:s'), true) ?>
            </p>
        </div>
    </div>
</div>
