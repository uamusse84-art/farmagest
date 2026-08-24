<?php

use App\Core\Session;

/** @var string $conteudo */
/** @var string $titulo */

$mensagens = Session::consumirFlash();
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'FarmaGest') ?> &middot; FarmaGest</title>
    <link rel="stylesheet" href="<?= url('/assets/vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/vendor/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body class="pagina-limpa">
<?php if ($mensagens !== []): ?>
    <div class="container" style="max-width: 640px;">
        <div class="pt-4">
            <?php foreach ($mensagens as $mensagem): ?>
                <div class="alert <?= classeAlerta($mensagem['tipo']) ?> d-flex align-items-start gap-2" role="alert">
                    <i class="bi <?= iconeAlerta($mensagem['tipo']) ?> mt-1"></i>
                    <div><?= e($mensagem['mensagem']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?= $conteudo ?>

<script src="<?= url('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
