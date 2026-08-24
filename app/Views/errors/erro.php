<?php
/**
 * Pagina de erro. Renderizada directamente pelo ErrorHandler, pelo que nao
 * depende do layout nem do estado da sessao.
 *
 * @var int         $estado
 * @var string      $titulo
 * @var string      $mensagem
 * @var string|null $detalhe
 */
$base = App\Core\Url::prefixoBase();
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro <?= (int) $estado ?> &middot; FarmaGest</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/vendor/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <i class="bi <?= $estado >= 500 ? 'bi-bug' : 'bi-shield-exclamation' ?> text-secondary"
                       style="font-size: 3.5rem;"></i>
                    <h1 class="display-5 fw-bold mt-3 mb-1"><?= (int) $estado ?></h1>
                    <p class="h5 text-muted mb-3"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-4"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>

                    <a href="<?= $base ?>/painel" class="btn btn-success">
                        <i class="bi bi-house me-1"></i>Voltar ao painel
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Pagina anterior
                    </a>
                </div>
            </div>

            <?php if (!empty($detalhe)): ?>
                <div class="card mt-3 border-danger-subtle">
                    <div class="card-header bg-danger-subtle small fw-semibold">
                        Detalhe tecnico (visivel apenas em modo de desenvolvimento)
                    </div>
                    <pre class="card-body small mb-0" style="white-space: pre-wrap;"><?=
                        htmlspecialchars($detalhe, ENT_QUOTES, 'UTF-8')
                    ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
