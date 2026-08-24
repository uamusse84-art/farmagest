<?php

use App\Core\Auth;

/** @var array $menu */
$perfilAtual = (string) Auth::perfil();
?>
<ul class="nav flex-column py-3">
    <?php foreach ($menu as $item): ?>
        <?php if (!in_array($perfilAtual, $item['perfis'], true)) { continue; } ?>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 <?= ativo($item['rota']) ?>"
               href="<?= url($item['rota']) ?>">
                <i class="bi <?= e($item['icone']) ?>"></i>
                <span><?= e($item['texto']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
