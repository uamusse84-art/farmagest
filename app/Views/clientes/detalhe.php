<?php
/** @var array $cliente */
/** @var array $historico */
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/clientes') ?>">Clientes</a></li>
        <li class="breadcrumb-item active"><?= e($cliente['nome']) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-vcard me-1"></i>Ficha do cliente
            </div>
            <div class="card-body">
                <h2 class="h5"><?= e($cliente['nome']) ?></h2>
                <dl class="row small mb-0 mt-3">
                    <dt class="col-5 text-muted">NUIT</dt>
                    <dd class="col-7"><?= e($cliente['nuit'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">Telefone</dt>
                    <dd class="col-7"><?= e($cliente['telefone'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">E-mail</dt>
                    <dd class="col-7"><?= e($cliente['email'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">Endereco</dt>
                    <dd class="col-7"><?= e($cliente['endereco'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">Registado em</dt>
                    <dd class="col-7"><?= data($cliente['criado_em']) ?></dd>
                </dl>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= url('/clientes/' . $cliente['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clock-history me-1"></i>Historico de compras
            </div>
            <div class="card-body p-0">
                <?php if ($historico === []): ?>
                    <p class="tabela-vazia mb-0">Este cliente ainda nao efetuou compras.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Data</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $venda): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($venda['numero']) ?></td>
                                        <td><?= data($venda['data_venda'], true) ?></td>
                                        <td class="text-end"><?= moeda($venda['total']) ?></td>
                                        <td class="text-center">
                                            <span class="badge text-bg-<?= $venda['estado'] === 'concluida' ? 'success' : 'danger' ?>">
                                                <?= e(ucfirst($venda['estado'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/vendas/' . $venda['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
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
