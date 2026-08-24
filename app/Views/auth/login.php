<?php
/** @var array $erros */
/** @var array $antigos */
?>
<div class="container d-flex align-items-center" style="min-height: 92vh;">
    <div class="caixa-login w-100">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4 marca-login">
                    <i class="bi bi-heart-pulse-fill"></i>
                    <h1 class="h4 mt-2 mb-1">FarmaGest</h1>
                    <p class="text-muted small mb-0">Sistema de Gestao de Farmacia</p>
                </div>

                <form method="post" action="<?= url('/login') ?>" class="needs-validation" novalidate>
                    <?= csrf() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control<?= classeInvalida($erros, 'email') ?>"
                                   id="email" name="email" value="<?= antigo($antigos, 'email') ?>"
                                   placeholder="nome@farmagest.co.mz" required autofocus autocomplete="username">
                            <div class="invalid-feedback">Indique um endereco de e-mail valido.</div>
                        </div>
                        <?= mensagemErro($erros, 'email') ?>
                    </div>

                    <div class="mb-4">
                        <label for="palavra_passe" class="form-label">Palavra-passe</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control<?= classeInvalida($erros, 'palavra_passe') ?>"
                                   id="palavra_passe" name="palavra_passe" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="const c=document.getElementById('palavra_passe'); c.type = c.type === 'password' ? 'text' : 'password';">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">Introduza a palavra-passe.</div>
                        </div>
                        <?= mensagemErro($erros, 'palavra_passe') ?>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                    </button>
                </form>

                <hr class="my-4">

                <div class="small text-muted">
                    <p class="fw-semibold mb-2">Contas de demonstracao:</p>
                    <ul class="list-unstyled mb-0">
                        <li><code>admin@farmagest.co.mz</code> / <code>Admin@123</code> &mdash; Administrador</li>
                        <li><code>farmaceutico@farmagest.co.mz</code> / <code>Farm@123</code> &mdash; Farmaceutico</li>
                        <li><code>caixa@farmagest.co.mz</code> / <code>Caixa@123</code> &mdash; Caixa</li>
                    </ul>
                </div>
            </div>
        </div>
        <p class="text-center text-white-50 small mt-3 mb-0">
            UniSCED &middot; Nordino Elias Jossias Uamusse (31240558)
        </p>
    </div>
</div>
