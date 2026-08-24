<?php
/**
 * Navegacao de paginacao reutilizada por todas as listagens.
 * @var array{pagina: int, paginas: int, total: int, porPagina: int} $pagina
 */
if (($pagina['paginas'] ?? 1) <= 1) {
    $inicio = $pagina['total'] > 0 ? 1 : 0;
    echo '<p class="text-muted small mb-0">Total: ' . (int) $pagina['total'] . ' registo(s).</p>';
    return;
}

$atual   = (int) $pagina['pagina'];
$paginas = (int) $pagina['paginas'];
$de      = ($atual - 1) * $pagina['porPagina'] + 1;
$ate     = min($atual * $pagina['porPagina'], $pagina['total']);

$primeira = max(1, $atual - 2);
$ultima   = min($paginas, $atual + 2);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
    <p class="text-muted small mb-0">
        A mostrar <?= $de ?>&ndash;<?= $ate ?> de <?= (int) $pagina['total'] ?> registo(s).
    </p>

    <nav aria-label="Paginacao">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $atual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(ligacaoPaginacao($atual - 1)) ?>" aria-label="Anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <?php if ($primeira > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= e(ligacaoPaginacao(1)) ?>">1</a></li>
                <?php if ($primeira > 2): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $primeira; $i <= $ultima; $i++): ?>
                <li class="page-item <?= $i === $atual ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(ligacaoPaginacao($i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($ultima < $paginas): ?>
                <?php if ($ultima < $paginas - 1): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= e(ligacaoPaginacao($paginas)) ?>"><?= $paginas ?></a></li>
            <?php endif; ?>

            <li class="page-item <?= $atual >= $paginas ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(ligacaoPaginacao($atual + 1)) ?>" aria-label="Seguinte">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
