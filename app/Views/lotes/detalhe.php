<?php

use App\Models\MovimentoStock;

/**
 * @var array $lote
 * @var array $movimentos
 * @var array $erros
 * @var array $antigos
 */
$dias       = diasAte($lote['data_validade']);
$quantidade = (int) $lote['quantidade_atual'];

if ($dias < 0) {
    [$classe, $estado] = ['danger', 'Expirado'];
} elseif ($quantidade === 0) {
    [$classe, $estado] = ['secondary', 'Esgotado'];
} elseif ($dias <= 90) {
    [$classe, $estado] = ['warning', 'A expirar'];
} else {
    [$classe, $estado] = ['success', 'Valido'];
}
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/lotes') ?>">Lotes e stock</a></li>
        <li class="breadcrumb-item active"><?= e($lote['numero_lote']) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-box-seam me-1"></i>Dados do lote
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h2 class="h5 mb-0"><code><?= e($lote['numero_lote']) ?></code></h2>
                    <span class="badge text-bg-<?= $classe ?>"><?= $estado ?></span>
                </div>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Medicamento</dt>
                    <dd class="col-7">
                        <a href="<?= url('/medicamentos/' . $lote['medicamento_id']) ?>" class="text-decoration-none">
                            <?= e($lote['medicamento']) ?>
                        </a>
                        <span class="text-muted d-block"><?= e($lote['codigo']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted">Fornecedor</dt>
                    <dd class="col-7"><?= e($lote['fornecedor']) ?></dd>

                    <dt class="col-5 text-muted">Data de entrada</dt>
                    <dd class="col-7"><?= data($lote['data_entrada']) ?></dd>

                    <dt class="col-5 text-muted">Data de validade</dt>
                    <dd class="col-7">
                        <?= data($lote['data_validade']) ?>
                        <span class="text-muted d-block">
                            <?= $dias < 0 ? abs($dias) . ' dia(s) expirado' : 'Faltam ' . $dias . ' dia(s)' ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Quantidade inicial</dt>
                    <dd class="col-7"><?= (int) $lote['quantidade_inicial'] ?></dd>

                    <dt class="col-5 text-muted">Quantidade atual</dt>
                    <dd class="col-7 fw-semibold"><?= $quantidade ?></dd>

                    <dt class="col-5 text-muted">Preco de compra</dt>
                    <dd class="col-7"><?= moeda($lote['preco_compra']) ?></dd>

                    <dt class="col-5 text-muted">Valor em stock</dt>
                    <dd class="col-7"><?= moeda($quantidade * (float) $lote['preco_compra']) ?></dd>
                </dl>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= url('/lotes/' . $lote['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-sliders me-1"></i>Ajuste de inventario
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Use este formulario para corrigir a contagem fisica ou registar quebras.
                    Todos os ajustes ficam registados no historico.
                </p>
                <form method="post" action="<?= url('/lotes/' . $lote['id'] . '/ajustar') ?>"
                      class="needs-validation" novalidate
                      data-confirmar="Confirma o ajuste de inventario deste lote?">
                    <?= csrf() ?>

                    <div class="mb-3">
                        <label for="nova_quantidade" class="form-label">Nova quantidade</label>
                        <input type="number" class="form-control<?= classeInvalida($erros, 'nova_quantidade') ?>"
                               id="nova_quantidade" name="nova_quantidade"
                               value="<?= antigo($antigos, 'nova_quantidade', (string) $quantidade) ?>"
                               required step="1" min="0" max="<?= (int) $lote['quantidade_inicial'] ?>">
                        <div class="form-text">Entre 0 e <?= (int) $lote['quantidade_inicial'] ?> (quantidade inicial).</div>
                        <?= mensagemErro($erros, 'nova_quantidade') ?>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de movimento</label>
                        <select class="form-select<?= classeInvalida($erros, 'tipo') ?>" id="tipo" name="tipo" required>
                            <option value="ajuste" <?= antigo($antigos, 'tipo') === 'ajuste' ? 'selected' : '' ?>>
                                Ajuste de inventario
                            </option>
                            <option value="quebra" <?= antigo($antigos, 'tipo') === 'quebra' ? 'selected' : '' ?>>
                                Quebra / produto danificado
                            </option>
                        </select>
                        <?= mensagemErro($erros, 'tipo') ?>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo</label>
                        <textarea class="form-control<?= classeInvalida($erros, 'motivo') ?>"
                                  id="motivo" name="motivo" rows="2" required
                                  minlength="5" maxlength="255"
                                  placeholder="Ex.: contagem fisica trimestral"><?= antigo($antigos, 'motivo') ?></textarea>
                        <div class="invalid-feedback">Descreva o motivo (minimo 5 caracteres).</div>
                        <?= mensagemErro($erros, 'motivo') ?>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-check2-square me-1"></i>Registar ajuste
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clock-history me-1"></i>Historico de movimentos
            </div>
            <div class="card-body p-0">
                <?php if ($movimentos === []): ?>
                    <p class="tabela-vazia mb-0">Ainda nao existem movimentos para este lote.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Qtd.</th>
                                    <th class="d-none d-md-table-cell">Utilizador</th>
                                    <th class="d-none d-lg-table-cell">Observacao</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentos as $movimento): ?>
                                    <?php
                                    $qtd = (int) $movimento['quantidade'];
                                    $corTipo = match ($movimento['tipo']) {
                                        'entrada'  => 'success',
                                        'saida'    => 'primary',
                                        'anulacao' => 'info',
                                        'quebra'   => 'danger',
                                        default    => 'secondary',
                                    };
                                    ?>
                                    <tr>
                                        <td class="small text-nowrap"><?= data($movimento['criado_em'], true) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $corTipo ?>">
                                                <?= e(MovimentoStock::TIPOS[$movimento['tipo']] ?? $movimento['tipo']) ?>
                                            </span>
                                            <?php if ($movimento['referencia'] !== null && $movimento['referencia'] !== ''): ?>
                                                <div class="small text-muted"><?= e($movimento['referencia']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-semibold <?= $qtd < 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= sprintf('%+d', $qtd) ?>
                                        </td>
                                        <td class="d-none d-md-table-cell small"><?= e($movimento['utilizador']) ?></td>
                                        <td class="d-none d-lg-table-cell small text-muted">
                                            <?= e($movimento['observacao'] ?: '-') ?>
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
