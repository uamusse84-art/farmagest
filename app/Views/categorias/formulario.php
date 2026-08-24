<?php
/** @var array|null $categoria */
/** @var array $erros */
/** @var array $antigos */

$edicao = $categoria !== null;
$accao  = $edicao ? url('/categorias/' . $categoria['id']) : url('/categorias');

$valor = static fn (string $campo): string =>
    antigo($antigos, $campo, $edicao ? ($categoria[$campo] ?? '') : '');
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/categorias') ?>">Categorias</a></li>
        <li class="breadcrumb-item active"><?= $edicao ? 'Editar' : 'Nova' ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-tags me-1"></i><?= $edicao ? 'Editar categoria' : 'Nova categoria' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $accao ?>" class="needs-validation" novalidate>
                    <?= csrf() ?>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= classeInvalida($erros, 'nome') ?>"
                               id="nome" name="nome" value="<?= $valor('nome') ?>"
                               required minlength="3" maxlength="80" autofocus>
                        <div class="invalid-feedback">Indique um nome com pelo menos 3 caracteres.</div>
                        <?= mensagemErro($erros, 'nome') ?>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descricao</label>
                        <textarea class="form-control<?= classeInvalida($erros, 'descricao') ?>"
                                  id="descricao" name="descricao" rows="3" maxlength="255"><?= $valor('descricao') ?></textarea>
                        <div class="form-text">Opcional. Maximo de 255 caracteres.</div>
                        <?= mensagemErro($erros, 'descricao') ?>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= url('/categorias') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $edicao ? 'Guardar alteracoes' : 'Criar categoria' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
