<?php

use App\Core\Auth;
use App\Models\Medicamento;

/**
 * @var array $medicamento
 * @var array $lotes
 */
$podeGerir  = Auth::temPerfil('farmaceutico');
$disponivel = (int) $medicamento['stock_disponivel'];
$expirado   = (int) $medicamento['stock_expirado'];
$minimo     = (int) $medicamento['stock_minimo'];
$classe     = $disponivel === 0 ? 'danger' : ($disponivel <= $minimo ? 'warning' : 'success');
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/medicamentos') ?>">Medicamentos</a></li>
        <li class="breadcrumb-item active"><?= e($medicamento['nome']) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-capsule me-1"></i>Ficha do produto
            </div>
            <div class="card-body">
                <h2 class="h5 mb-1"><?= e($medicamento['nome']) ?></h2>
                <p class="text-muted small mb-3">
                    <code><?= e($medicamento['codigo']) ?></code>
                    <?php if ((int) $medicamento['requer_receita'] === 1): ?>
                        <span class="badge text-bg-warning ms-1">Rx</span>
                    <?php endif; ?>
                    <?php if ((int) $medicamento['ativo'] === 0): ?>
                        <span class="badge text-bg-secondary ms-1">Inativo</span>
                    <?php endif; ?>
                </p>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Categoria</dt>
                    <dd class="col-7"><?= e($medicamento['categoria']) ?></dd>

                    <dt class="col-5 text-muted">Principio ativo</dt>
                    <dd class="col-7"><?= e($medicamento['principio_ativo'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">Forma</dt>
                    <dd class="col-7"><?= e(Medicamento::FORMAS[$medicamento['forma_farmaceutica']] ?? '-') ?></dd>

                    <dt class="col-5 text-muted">Dosagem</dt>
                    <dd class="col-7"><?= e($medicamento['dosagem'] ?: '-') ?></dd>

                    <dt class="col-5 text-muted">Preco de venda</dt>
                    <dd class="col-7 fw-semibold"><?= moeda($medicamento['preco_venda']) ?></dd>

                    <dt class="col-5 text-muted">Stock minimo</dt>
                    <dd class="col-7"><?= $minimo ?> unidade(s)</dd>
                </dl>
            </div>
            <?php if ($podeGerir): ?>
                <div class="card-footer bg-white d-flex flex-wrap gap-2">
                    <a href="<?= url('/medicamentos/' . $medicamento['id'] . '/editar') ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <a href="<?= url('/lotes/criar?medicamento=' . $medicamento['id']) ?>"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Entrada de stock
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="card card-indicador h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Stock disponivel</p>
                        <p class="h3 mb-0 text-<?= $classe ?>"><?= $disponivel ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card card-indicador h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Em lotes expirados</p>
                        <p class="h3 mb-0 <?= $expirado > 0 ? 'text-danger' : '' ?>"><?= $expirado ?></p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card card-indicador h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Valor em stock</p>
                        <p class="h3 mb-0"><?= moeda($disponivel * (float) $medicamento['preco_venda']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-boxes me-1"></i>Lotes registados
            </div>
            <div class="card-body p-0">
                <?php if ($lotes === []): ?>
                    <p class="tabela-vazia mb-0">Ainda nao existem lotes para este medicamento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th class="d-none d-md-table-cell">Fornecedor</th>
                                    <th>Validade</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes as $lote): ?>
                                    <?php
                                    $dias = diasAte($lote['data_validade']);
                                    $quantidade = (int) $lote['quantidade_atual'];

                                    if ($dias < 0) {
                                        [$estadoClasse, $estadoTexto] = ['danger', 'Expirado'];
                                    } elseif ($quantidade === 0) {
                                        [$estadoClasse, $estadoTexto] = ['secondary', 'Esgotado'];
                                    } elseif ($dias <= 90) {
                                        [$estadoClasse, $estadoTexto] = ['warning', 'A expirar'];
                                    } else {
                                        [$estadoClasse, $estadoTexto] = ['success', 'Valido'];
                                    }
                                    ?>
                                    <tr>
                                        <td><code class="small"><?= e($lote['numero_lote']) ?></code></td>
                                        <td class="d-none d-md-table-cell small"><?= e($lote['fornecedor']) ?></td>
                                        <td class="small">
                                            <?= data($lote['data_validade']) ?>
                                            <?php if ($dias >= 0 && $dias <= 90): ?>
                                                <span class="text-muted d-block"><?= $dias ?> dia(s)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $quantidade ?>
                                            <span class="text-muted small">/ <?= (int) $lote['quantidade_inicial'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge text-bg-<?= $estadoClasse ?>"><?= $estadoTexto ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/lotes/' . $lote['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Ver lote">
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
