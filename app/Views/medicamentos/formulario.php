<?php

use App\Models\Medicamento;

/**
 * @var array|null $medicamento
 * @var array      $categorias
 * @var array      $erros
 * @var array      $antigos
 * @var string     $codigoSugerido
 */
$edicao = $medicamento !== null;
$accao  = $edicao ? url('/medicamentos/' . $medicamento['id']) : url('/medicamentos');

$valor = static fn (string $campo, string $omissao = ''): string =>
    antigo($antigos, $campo, $edicao ? (string) ($medicamento[$campo] ?? '') : $omissao);

$requerReceita = $antigos !== []
    ? isset($antigos['requer_receita'])
    : ($edicao && (int) $medicamento['requer_receita'] === 1);

$ativo = $antigos !== []
    ? isset($antigos['ativo'])
    : (!$edicao || (int) $medicamento['ativo'] === 1);
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/medicamentos') ?>">Medicamentos</a></li>
        <li class="breadcrumb-item active"><?= $edicao ? 'Editar' : 'Novo' ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-capsule me-1"></i><?= $edicao ? 'Editar medicamento' : 'Novo medicamento' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $accao ?>" class="needs-validation" novalidate>
                    <?= csrf() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Codigo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase<?= classeInvalida($erros, 'codigo') ?>"
                                   id="codigo" name="codigo" value="<?= $valor('codigo', $codigoSugerido) ?>"
                                   required minlength="3" maxlength="30">
                            <div class="form-text">Codigo interno unico do produto.</div>
                            <?= mensagemErro($erros, 'codigo') ?>
                        </div>

                        <div class="col-md-8">
                            <label for="nome" class="form-label">Nome comercial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'nome') ?>"
                                   id="nome" name="nome" value="<?= $valor('nome') ?>"
                                   required minlength="3" maxlength="150" autofocus>
                            <div class="invalid-feedback">Indique o nome do medicamento (minimo 3 caracteres).</div>
                            <?= mensagemErro($erros, 'nome') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select<?= classeInvalida($erros, 'categoria_id') ?>"
                                    id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>"
                                        <?= $valor('categoria_id') === (string) $categoria['id'] ? 'selected' : '' ?>>
                                        <?= e($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Escolha uma categoria.</div>
                            <?= mensagemErro($erros, 'categoria_id') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="principio_ativo" class="form-label">Principio ativo</label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'principio_ativo') ?>"
                                   id="principio_ativo" name="principio_ativo"
                                   value="<?= $valor('principio_ativo') ?>" maxlength="150">
                            <?= mensagemErro($erros, 'principio_ativo') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="forma_farmaceutica" class="form-label">Forma farmaceutica <span class="text-danger">*</span></label>
                            <select class="form-select<?= classeInvalida($erros, 'forma_farmaceutica') ?>"
                                    id="forma_farmaceutica" name="forma_farmaceutica" required>
                                <option value="">Seleccione...</option>
                                <?php foreach (Medicamento::FORMAS as $chave => $rotulo): ?>
                                    <option value="<?= e($chave) ?>"
                                        <?= $valor('forma_farmaceutica') === $chave ? 'selected' : '' ?>>
                                        <?= e($rotulo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Escolha a forma farmaceutica.</div>
                            <?= mensagemErro($erros, 'forma_farmaceutica') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="dosagem" class="form-label">Dosagem</label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'dosagem') ?>"
                                   id="dosagem" name="dosagem" value="<?= $valor('dosagem') ?>"
                                   placeholder="500 mg" maxlength="50">
                            <?= mensagemErro($erros, 'dosagem') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="preco_venda" class="form-label">Preco de venda (MZN) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control<?= classeInvalida($erros, 'preco_venda') ?>"
                                   id="preco_venda" name="preco_venda" value="<?= $valor('preco_venda') ?>"
                                   required step="0.01" min="0.01" max="1000000">
                            <div class="invalid-feedback">Indique um preco superior a zero.</div>
                            <?= mensagemErro($erros, 'preco_venda') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="stock_minimo" class="form-label">Stock minimo <span class="text-danger">*</span></label>
                            <input type="number" class="form-control<?= classeInvalida($erros, 'stock_minimo') ?>"
                                   id="stock_minimo" name="stock_minimo" value="<?= $valor('stock_minimo', '10') ?>"
                                   required step="1" min="0" max="100000">
                            <div class="form-text">Abaixo deste valor o sistema emite alerta.</div>
                            <?= mensagemErro($erros, 'stock_minimo') ?>
                        </div>

                        <div class="col-md-8 d-flex align-items-center">
                            <div class="w-100">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="requer_receita" name="requer_receita" value="1"
                                        <?= $requerReceita ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="requer_receita">
                                        Sujeito a receita medica
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="ativo" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ativo">
                                        Medicamento ativo (disponivel para venda)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/medicamentos') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $edicao ? 'Guardar alteracoes' : 'Registar medicamento' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
