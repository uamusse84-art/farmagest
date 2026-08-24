<?php

/**
 * @var array  $pagina
 * @var string $pesquisa
 * @var string $filtro
 */
$filtros = [
    ''          => 'Todos os lotes',
    'validos'   => 'Validos com stock',
    'a_expirar' => 'A expirar (90 dias)',
    'expirados' => 'Expirados',
    'esgotados' => 'Esgotados',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Lotes e stock</h1>
    <a href="<?= url('/lotes/criar') ?>" class="btn btn-success">
        <i class="bi bi-box-arrow-in-down me-1"></i>Entrada de stock
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="pesquisa" class="form-control"
                           placeholder="Numero do lote ou medicamento..." value="<?= e($pesquisa) ?>">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="filtro" class="form-select">
                    <?php foreach ($filtros as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $filtro === $chave ? 'selected' : '' ?>>
                            <?= e($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2 d-grid">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Medicamento</th>
                        <th class="d-none d-lg-table-cell">Fornecedor</th>
                        <th>Validade</th>
                        <th class="text-center">Stock</th>
                        <th class="d-none d-md-table-cell text-end">Preco compra</th>
                        <th class="text-end">Accoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="7" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao foram encontrados lotes com os filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $lote): ?>
                        <?php
                        $dias       = diasAte($lote['data_validade']);
                        $quantidade = (int) $lote['quantidade_atual'];

                        if ($dias < 0) {
                            [$classe, $estado] = ['danger', 'Expirado'];
                        } elseif ($quantidade === 0) {
                            [$classe, $estado] = ['secondary', 'Esgotado'];
                        } elseif ($dias <= 90) {
                            [$classe, $estado] = ['warning', 'A expirar'];
                        } else {
                            [$classe, $estado] = ['success', 'Valido'];
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url('/lotes/' . $lote['id']) ?>" class="text-decoration-none">
                                    <code class="small"><?= e($lote['numero_lote']) ?></code>
                                </a>
                            </td>
                            <td>
                                <a href="<?= url('/medicamentos/' . $lote['medicamento_id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($lote['medicamento']) ?>
                                </a>
                                <div class="small text-muted"><?= e($lote['codigo']) ?></div>
                            </td>
                            <td class="d-none d-lg-table-cell small"><?= e($lote['fornecedor']) ?></td>
                            <td class="small">
                                <?= data($lote['data_validade']) ?>
                                <span class="badge text-bg-<?= $classe ?> d-block mt-1"><?= $estado ?></span>
                            </td>
                            <td class="text-center">
                                <?= $quantidade ?>
                                <span class="text-muted small">/ <?= (int) $lote['quantidade_inicial'] ?></span>
                            </td>
                            <td class="d-none d-md-table-cell text-end"><?= moeda($lote['preco_compra']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= url('/lotes/' . $lote['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Ver detalhe">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/lotes/' . $lote['id'] . '/editar') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/lotes/' . $lote['id'] . '/eliminar') ?>"
                                      class="d-inline"
                                      data-confirmar="Eliminar o lote &quot;<?= e($lote['numero_lote']) ?>&quot;?">
                                    <?= csrf() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require BASE_PATH . '/app/Views/layouts/_paginacao.php'; ?>
    </div>
</div>
