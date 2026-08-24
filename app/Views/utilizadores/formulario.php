<?php

use App\Models\Utilizador;

/**
 * @var array|null $utilizador
 * @var array      $erros
 * @var array      $antigos
 */
$edicao = $utilizador !== null;
$accao  = $edicao ? url('/utilizadores/' . $utilizador['id']) : url('/utilizadores');

$valor = static fn (string $campo): string =>
    antigo($antigos, $campo, $edicao ? (string) ($utilizador[$campo] ?? '') : '');

$ativo = $antigos !== []
    ? isset($antigos['ativo'])
    : (!$edicao || (int) $utilizador['ativo'] === 1);
?>
<nav aria-label="Navegacao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/utilizadores') ?>">Utilizadores</a></li>
        <li class="breadcrumb-item active"><?= $edicao ? 'Editar' : 'Novo' ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-gear me-1"></i><?= $edicao ? 'Editar utilizador' : 'Novo utilizador' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $accao ?>" class="needs-validation" novalidate autocomplete="off">
                    <?= csrf() ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control<?= classeInvalida($erros, 'nome') ?>"
                                   id="nome" name="nome" value="<?= $valor('nome') ?>"
                                   required minlength="5" maxlength="120" autofocus>
                            <div class="invalid-feedback">Indique o nome completo (minimo 5 caracteres).</div>
                            <?= mensagemErro($erros, 'nome') ?>
                        </div>

                        <div class="col-md-5">
                            <label for="perfil" class="form-label">Perfil <span class="text-danger">*</span></label>
                            <select class="form-select<?= classeInvalida($erros, 'perfil') ?>"
                                    id="perfil" name="perfil" required>
                                <option value="">Seleccione...</option>
                                <?php foreach (Utilizador::PERFIS as $chave => $rotulo): ?>
                                    <option value="<?= e($chave) ?>" <?= $valor('perfil') === $chave ? 'selected' : '' ?>>
                                        <?= e($rotulo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Administrador gere tudo; farmaceutico gere stock e vendas; caixa apenas vende.
                            </div>
                            <?= mensagemErro($erros, 'perfil') ?>
                        </div>

                        <div class="col-md-7">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control<?= classeInvalida($erros, 'email') ?>"
                                   id="email" name="email" value="<?= $valor('email') ?>"
                                   required maxlength="150" autocomplete="off">
                            <div class="invalid-feedback">Indique um e-mail valido.</div>
                            <?= mensagemErro($erros, 'email') ?>
                        </div>

                        <div class="col-md-5">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control<?= classeInvalida($erros, 'telefone') ?>"
                                   id="telefone" name="telefone" value="<?= $valor('telefone') ?>"
                                   placeholder="+258 84 000 0000" maxlength="20">
                            <?= mensagemErro($erros, 'telefone') ?>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-6">
                            <label for="palavra_passe" class="form-label">
                                Palavra-passe <?= $edicao ? '' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" class="form-control<?= classeInvalida($erros, 'palavra_passe') ?>"
                                   id="palavra_passe" name="palavra_passe"
                                   <?= $edicao ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                            <div class="form-text">
                                <?= $edicao
                                    ? 'Deixe em branco para manter a palavra-passe atual.'
                                    : 'Minimo 8 caracteres, com maiuscula, minuscula e digito.' ?>
                            </div>
                            <?= mensagemErro($erros, 'palavra_passe') ?>
                        </div>

                        <div class="col-md-6">
                            <label for="palavra_passe_confirmacao" class="form-label">Confirmar palavra-passe</label>
                            <input type="password" class="form-control"
                                   id="palavra_passe_confirmacao" name="palavra_passe_confirmacao"
                                   <?= $edicao ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="ativo" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativo">
                                    Conta ativa (pode iniciar sessao)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/utilizadores') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $edicao ? 'Guardar alteracoes' : 'Criar utilizador' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
