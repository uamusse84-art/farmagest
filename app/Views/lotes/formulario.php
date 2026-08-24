<?php

/**
 * @var array|null $lote
 * @var array      $medicamentos
 * @var array      $fornecedores
 * @var int        $preSelecionado
 * @var array      $erros
 * @var array      $antigos
 */
$edicao = $lote !== null;
$accao  = $edicao ? url('/lotes/' . $lote['id']) : url('/lotes');

$valor = static fn (string $campo, string $omissao = ''): string =>
    antigo($antigos, $campo, $edicao ? (string) ($lote[$campo] ?? '') : $omissao);

$medicamentoAtual = $valor('medicamento_id', $preSelecionado > 0 ? (string) $preSelecionado : '');
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/lotes') ?>">Lotes e stock</a></li>
        <li class="breadcrumb-item active"><?= $edicao ? 'Editar' : 'Entrada de stock' ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-box-seam me-1"></i><?= $edicao ? 'Editar lote' : 'Nova entrada de stock' ?>
            </div>
            <div class="card-body">
                <?php if ($edicao): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        A quantidade em stock nao se altera aqui. Use o ajuste de inventario no
                        <a href="<?= url('/lotes/' . $lote['id']) ?>" class="alert-link">detalhe do lote</a>
                        para deixar rasto na auditoria.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $accao ?>" class="needs-validation" novalidate>
                    <?= csrf() ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="medicamento_id" class="form-label">Medicamento <span class="text-danger">*</span></label>
                            <select class="form-select<?= classeInvalida($erros, 'medicamento_id') ?>"
                                    id="medicamento_id" name="medicamento_id" required
                                <?= $edicao ? 'disabled' : '' ?>>
                                <option value="">Seleccione...</option>
                                <?php foreach ($medicamentos as $item): ?>
                                    <option value="<?= (int) $item['id'] ?>"
                                        <?= $medicamentoAtual === (string) $item['id'] ? 'selected' : '' ?>>
                                        <?= e($item['codigo'] . ' - ' . $item['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($edicao): ?>
                                <input type="hidden" name="medicamento_id" value="<?= (int) $lote['medicamento_id'] ?>">
                                <div class="form-text">O medicamento de um lote existente nao pode ser alterado.</div>
                            <?php endif; ?>
                            <div class="invalid-feedback">Escolha o medicamento.</div>
                            <?= mensagemErro($erros, 'medicamento_id') ?>
                        </div>

                        <div class="col-md-5">
                            <label for="fornecedor_id" class="form-label">Fornecedor <span class="text-danger">*</span></label>
                            <select class="form-select<?= classeInvalida($erros, 'fornecedor_id') ?>"
                                    id="fornecedor_id" name="fornecedor_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= (int) $fornecedor['id'] ?>"
                                        <?= $valor('fornecedor_id') === (string) $fornecedor['id'] ? 'selected' : '' ?>>
                                        <?= e($fornecedor['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Escolha o fornecedor.</div>
                            <?= mensagemErro($erros, 'fornecedor_id') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="numero_lote" class="form-label">Numero do lote <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase<?= classeInvalida($erros, 'numero_lote') ?>"
                                   id="numero_lote" name="numero_lote" value="<?= $valor('numero_lote') ?>"
                                   required minlength="3" maxlength="50" placeholder="LT-PAR-2601">
                            <div class="invalid-feedback">Indique o numero do lote (minimo 3 caracteres).</div>
                            <?= mensagemErro($erros, 'numero_lote') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="data_validade" class="form-label">Data de validade <span class="text-danger">*</span></label>
                            <input type="date" class="form-control<?= classeInvalida($erros, 'data_validade') ?>"
                                   id="data_validade" name="data_validade" value="<?= $valor('data_validade') ?>"
                                   required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="invalid-feedback">A validade tem de ser uma data futura.</div>
                            <?= mensagemErro($erros, 'data_validade') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="data_entrada" class="form-label">Data de entrada <span class="text-danger">*</span></label>
                            <input type="date" class="form-control<?= classeInvalida($erros, 'data_entrada') ?>"
                                   id="data_entrada" name="data_entrada"
                                   value="<?= $valor('data_entrada', date('Y-m-d')) ?>"
                                   required max="<?= date('Y-m-d') ?>">
                            <div class="invalid-feedback">A entrada nao pode ser no futuro.</div>
                            <?= mensagemErro($erros, 'data_entrada') ?>
                        </div>

                        <?php if (!$edicao): ?>
                            <div class="col-md-6">
                                <label for="quantidade_inicial" class="form-label">Quantidade recebida <span class="text-danger">*</span></label>
                                <input type="number" class="form-control<?= classeInvalida($erros, 'quantidade_inicial') ?>"
                                       id="quantidade_inicial" name="quantidade_inicial"
                                       value="<?= $valor('quantidade_inicial') ?>"
                                       required step="1" min="1" max="1000000">
                                <div class="invalid-feedback">Indique uma quantidade igual ou superior a 1.</div>
                                <?= mensagemErro($erros, 'quantidade_inicial') ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label for="preco_compra" class="form-label">Preco de compra unitario (MZN) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control<?= classeInvalida($erros, 'preco_compra') ?>"
                                   id="preco_compra" name="preco_compra" value="<?= $valor('preco_compra') ?>"
                                   required step="0.01" min="0" max="1000000">
                            <div class="invalid-feedback">Indique o preco de compra.</div>
                            <?= mensagemErro($erros, 'preco_compra') ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/lotes') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $edicao ? 'Guardar alteracoes' : 'Registar entrada' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
