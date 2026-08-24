<?php
/** @var array|null $cliente */
/** @var array $erros */
/** @var array $antigos */

$edicao = $cliente !== null;
$accao  = $edicao ? url('/clientes/' . $cliente['id']) : url('/clientes');

$valor = static fn (string $campo): string =>
    antigo($antigos, $campo, $edicao ? ($cliente[$campo] ?? '') : '');
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/clientes') ?>">Clientes</a></li>
        <li class="breadcrumb-item active"><?= $edicao ? 'Editar' : 'Novo' ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-vcard me-1"></i><?= $edicao ? 'Editar cliente' : 'Novo cliente' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $accao ?>" class="needs-validation" novalidate>
                    <?= csrf() ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'nome') ?>"
                                   id="nome" name="nome" value="<?= $valor('nome') ?>"
                                   required minlength="3" maxlength="150" autofocus>
                            <div class="invalid-feedback">Indique o nome do cliente (minimo 3 caracteres).</div>
                            <?= mensagemErro($erros, 'nome') ?>
                        </div>

                        <div class="col-md-4">
                            <label for="nuit" class="form-label">NUIT</label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'nuit') ?>"
                                   id="nuit" name="nuit" value="<?= $valor('nuit') ?>"
                                   pattern="[0-9]{9,20}" maxlength="20" inputmode="numeric">
                            <?= mensagemErro($erros, 'nuit') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control<?= classeInvalida($erros, 'telefone') ?>"
                                   id="telefone" name="telefone" value="<?= $valor('telefone') ?>"
                                   placeholder="+258 84 000 0000" maxlength="20">
                            <?= mensagemErro($erros, 'telefone') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control<?= classeInvalida($erros, 'email') ?>"
                                   id="email" name="email" value="<?= $valor('email') ?>" maxlength="150">
                            <div class="invalid-feedback">Indique um e-mail valido.</div>
                            <?= mensagemErro($erros, 'email') ?>
                        </div>

                        <div class="col-12">
                            <label for="endereco" class="form-label">Endereco</label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'endereco') ?>"
                                   id="endereco" name="endereco" value="<?= $valor('endereco') ?>" maxlength="200">
                            <?= mensagemErro($erros, 'endereco') ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $edicao ? 'Guardar alteracoes' : 'Registar cliente' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
