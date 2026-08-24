<?php
$cartoes = [
    [
        'rota'      => '/relatorios/vendas',
        'icone'     => 'bi-graph-up-arrow',
        'titulo'    => 'Relatorio de vendas',
        'descricao' => 'Vendas concluidas num intervalo de datas, com totais por operador e forma de pagamento. Pronto a imprimir.',
    ],
    [
        'rota'      => '/relatorios/stock',
        'icone'     => 'bi-boxes',
        'titulo'    => 'Relatorio de stock',
        'descricao' => 'Produtos abaixo do stock minimo, lotes a expirar nos proximos 90 dias e lotes ja expirados.',
    ],
    [
        'rota'      => '/relatorios/movimentos',
        'icone'     => 'bi-arrow-left-right',
        'titulo'    => 'Movimentos de stock',
        'descricao' => 'Auditoria completa de entradas, saidas por venda, anulacoes, ajustes e quebras.',
    ],
];
?>
<h1 class="h3 mb-1">Relatorios</h1>
<p class="text-muted">Consulte a informacao consolidada do sistema e imprima os mapas de apoio a gestao.</p>

<div class="row g-3">
    <?php foreach ($cartoes as $cartao): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="mb-2 fs-2 text-success"><i class="bi <?= $cartao['icone'] ?>"></i></div>
                    <h2 class="h5"><?= e($cartao['titulo']) ?></h2>
                    <p class="text-muted small mb-0"><?= e($cartao['descricao']) ?></p>
                </div>
                <div class="card-footer bg-white">
                    <a href="<?= url($cartao['rota']) ?>" class="btn btn-sm btn-outline-success">
                        Abrir relatorio <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
