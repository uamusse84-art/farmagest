<?php

use App\Models\Venda;

/**
 * @var array $medicamentos
 * @var array $clientes
 */
$catalogo = array_map(static fn (array $m): array => [
    'id'      => (int) $m['medicamento_id'],
    'nome'    => (string) $m['nome'],
    'codigo'  => (string) $m['codigo'],
    'preco'   => (float) $m['preco_venda'],
    'stock'   => (int) $m['stock_disponivel'],
    'receita' => (int) $m['requer_receita'] === 1,
], $medicamentos);
?>
<div id="ecra-venda">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h3 mb-0">Nova venda</h1>
        <a href="<?= url('/vendas') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar as vendas
        </a>
    </div>

    <?php if ($medicamentos === []): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Nao existe stock valido disponivel para venda. Registe entradas de stock antes de continuar.
        </div>
    <?php endif; ?>

    <script type="application/json" id="catalogo-medicamentos"><?= json_encode(
        $catalogo,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?></script>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" placeholder="Filtrar medicamentos..."
                               data-filtra-tabela="#tabela-catalogo">
                    </div>
                </div>
                <div class="card-body p-0 lista-produtos">
                    <table class="table table-hover table-sm align-middle mb-0" id="tabela-catalogo">
                        <tbody>
                            <?php foreach ($medicamentos as $medicamento): ?>
                                <?php $stock = (int) $medicamento['stock_disponivel']; ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($medicamento['nome']) ?></div>
                                        <div class="small text-muted">
                                            <?= e($medicamento['codigo']) ?> &middot; <?= e($medicamento['categoria']) ?>
                                            <?php if ((int) $medicamento['requer_receita'] === 1): ?>
                                                <span class="badge text-bg-warning ms-1">Rx</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold"><?= moeda($medicamento['preco_venda']) ?></div>
                                        <div class="small text-muted"><?= $stock ?> un.</div>
                                    </td>
                                    <td class="text-end" style="width: 56px;">
                                        <button type="button" class="btn btn-sm btn-success"
                                                data-adicionar="<?= (int) $medicamento['medicamento_id'] ?>"
                                                title="Adicionar ao carrinho">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <form method="post" action="<?= url('/vendas') ?>" id="formulario-venda">
                <?= csrf() ?>

                <div class="card mb-3">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-cart3 me-1"></i>Carrinho
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Medicamento</th>
                                        <th class="text-end">Preco</th>
                                        <th>Qtd.</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="carrinho-corpo"></tbody>
                            </table>
                        </div>
                        <p class="tabela-vazia mb-0" id="carrinho-vazio">
                            <i class="bi bi-cart-x fs-3 d-block mb-2"></i>
                            Carrinho vazio. Escolha os medicamentos na lista ao lado.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-receipt me-1"></i>Fecho da venda
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="0">Consumidor final</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= (int) $cliente['id'] ?>"><?= e($cliente['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="forma_pagamento" class="form-label">Forma de pagamento <span class="text-danger">*</span></label>
                                <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                                    <?php foreach (Venda::FORMAS_PAGAMENTO as $chave => $rotulo): ?>
                                        <option value="<?= e($chave) ?>"><?= e($rotulo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="desconto" class="form-label">Desconto (MZN)</label>
                                <input type="number" class="form-control" id="desconto" name="desconto"
                                       value="0" step="0.01" min="0">
                            </div>

                            <div class="col-md-6">
                                <label for="observacoes" class="form-label">Observacoes</label>
                                <input type="text" class="form-control" id="observacoes" name="observacoes"
                                       maxlength="255" placeholder="Opcional">
                            </div>
                        </div>

                        <hr>

                        <dl class="row mb-0">
                            <dt class="col-7 text-muted fw-normal">Subtotal</dt>
                            <dd class="col-5 text-end" id="resumo-subtotal">0,00 MZN</dd>

                            <dt class="col-7 text-muted fw-normal">Desconto</dt>
                            <dd class="col-5 text-end" id="resumo-desconto">0,00 MZN</dd>

                            <dt class="col-7 h5">Total a pagar</dt>
                            <dd class="col-5 text-end h5 text-success" id="resumo-total">0,00 MZN</dd>
                        </dl>
                    </div>
                    <div class="card-footer bg-white d-grid">
                        <button type="submit" class="btn btn-success btn-lg" id="botao-finalizar" disabled>
                            <i class="bi bi-check-circle me-1"></i>Finalizar venda
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
