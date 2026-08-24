<?php

use App\Core\Auth;
use App\Core\Session;

/** @var string $conteudo */
/** @var string $titulo */

$utilizador = Auth::utilizador();
$mensagens  = Session::consumirFlash();

$menu = [
    ['rota' => '/painel',       'icone' => 'bi-speedometer2',    'texto' => 'Painel',        'perfis' => ['administrador', 'farmaceutico', 'caixa']],
    ['rota' => '/vendas',       'icone' => 'bi-cart-check',      'texto' => 'Vendas',        'perfis' => ['administrador', 'farmaceutico', 'caixa']],
    ['rota' => '/medicamentos', 'icone' => 'bi-capsule',         'texto' => 'Medicamentos',  'perfis' => ['administrador', 'farmaceutico', 'caixa']],
    ['rota' => '/lotes',        'icone' => 'bi-boxes',           'texto' => 'Lotes e stock', 'perfis' => ['administrador', 'farmaceutico']],
    ['rota' => '/categorias',   'icone' => 'bi-tags',            'texto' => 'Categorias',    'perfis' => ['administrador', 'farmaceutico']],
    ['rota' => '/fornecedores', 'icone' => 'bi-truck',           'texto' => 'Fornecedores',  'perfis' => ['administrador', 'farmaceutico']],
    ['rota' => '/clientes',     'icone' => 'bi-people',          'texto' => 'Clientes',      'perfis' => ['administrador', 'farmaceutico', 'caixa']],
    ['rota' => '/relatorios',   'icone' => 'bi-graph-up-arrow',  'texto' => 'Relatorios',    'perfis' => ['administrador', 'farmaceutico']],
    ['rota' => '/utilizadores', 'icone' => 'bi-person-gear',     'texto' => 'Utilizadores',  'perfis' => ['administrador']],
];
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
<body>
<nav class="navbar navbar-expand-lg barra-topo sticky-top">
    <div class="container-fluid">
        <button class="btn btn-link text-white d-lg-none px-2" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-label="Abrir menu">
            <i class="bi bi-list fs-3"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/painel') ?>">
            <i class="bi bi-heart-pulse-fill"></i>
            <span class="fw-semibold">FarmaGest</span>
        </a>

        <div class="dropdown ms-auto">
            <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr((string) $utilizador['nome'], 0, 1))) ?></span>
                <span class="d-none d-sm-inline text-start lh-sm">
                    <span class="d-block small fw-semibold"><?= e($utilizador['nome']) ?></span>
                    <span class="d-block small opacity-75"><?= e(App\Models\Utilizador::PERFIS[$utilizador['perfil']]) ?></span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li class="px-3 py-2 small text-muted">
                    <?= e($utilizador['email']) ?><br>
                    Ultimo acesso: <?= data($utilizador['ultimo_acesso'], true) ?>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= url('/logout') ?>" class="px-2">
                        <?= csrf() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-1"></i>Terminar sessao
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Menu lateral: fixo em ecras grandes, offcanvas em telemovel -->
        <aside class="col-lg-2 d-none d-lg-block menu-lateral p-0">
            <?php require __DIR__ . '/_menu.php'; ?>
        </aside>

        <div class="offcanvas offcanvas-start menu-lateral d-lg-none" tabindex="-1" id="menuLateral">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title text-white">Menu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0">
                <?php require __DIR__ . '/_menu.php'; ?>
            </div>
        </div>

        <main class="col-lg-10 ms-sm-auto px-3 px-md-4 py-4">
            <?php foreach ($mensagens as $mensagem): ?>
                <div class="alert <?= classeAlerta($mensagem['tipo']) ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
                    <i class="bi <?= iconeAlerta($mensagem['tipo']) ?> mt-1"></i>
                    <div class="flex-grow-1"><?= e($mensagem['mensagem']) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endforeach; ?>

            <?= $conteudo ?>

            <footer class="text-center text-muted small mt-5 pt-3 border-top">
                FarmaGest &copy; <?= date('Y') ?> &middot; Nordino Elias Jossias Uamusse (31240558) &middot;
                UniSCED &ndash; Desenvolvimento de Aplicativos Web Empresariais
            </footer>
        </main>
    </div>
</div>

<script src="<?= url('/assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('/assets/js/app.js') ?>"></script>
<?php if (!empty($scripts)): ?>
    <?= $scripts ?>
<?php endif; ?>
</body>
</html>
