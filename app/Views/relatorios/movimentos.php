<?php

use App\Models\MovimentoStock;

/**
 * @var array  $pagina
 * @var string $tipo
 */
?>
<nav aria-label="Navegacao" class="sem-impressao">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/relatorios') ?>">Relatorios</a></li>
        <li class="breadcrumb-item active">Movimentos</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="h3 mb-0">Movimentos de stock</h1>
        <p class="text-muted small mb-0">Registo de auditoria de todas as alteracoes ao inventario.</p>
    </div>
    <button type="button" class="btn btn-outline-primary sem-impressao" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3 sem-impressao">
            <div class="col-8 col-md-4">
                <select name="tipo" class="form-select">
                    <option value="">Todos os tipos</option>
                    <?php foreach (MovimentoStock::TIPOS as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $tipo === $chave ? 'selected' : '' ?>>
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
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Medicamento</th>
                        <th class="d-none d-md-table-cell">Lote</th>
                        <th class="text-center">Qtd.</th>
                        <th class="d-none d-lg-table-cell">Utilizador</th>
                        <th class="d-none d-lg-table-cell">Observacao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pagina['dados'] === []): ?>
                        <tr>
                            <td colspan="7" class="tabela-vazia">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nao existem movimentos registados com este filtro.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($pagina['dados'] as $movimento): ?>
                        <?php
                        $qtd = (int) $movimento['quantidade'];
                        $cor = match ($movimento['tipo']) {
                            'entrada'  => 'success',
                            'saida'    => 'primary',
                            'anulacao' => 'info',
                            'quebra'   => 'danger',
                            default    => 'secondary',
                        };
                        ?>
                        <tr>
                            <td class="small text-nowrap"><?= data($movimento['criado_em'], true) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $cor ?>">
                                    <?= e(MovimentoStock::TIPOS[$movimento['tipo']] ?? $movimento['tipo']) ?>
                                </span>
                            </td>
                            <td><?= e($movimento['medicamento']) ?></td>
                            <td class="d-none d-md-table-cell">
                                <a href="<?= url('/lotes/' . $movimento['lote_id']) ?>" class="text-decoration-none">
                                    <code class="small"><?= e($movimento['numero_lote']) ?></code>
                                </a>
                            </td>
                            <td class="text-center fw-semibold <?= $qtd < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= sprintf('%+d', $qtd) ?>
                            </td>
                            <td class="d-none d-lg-table-cell small"><?= e($movimento['utilizador']) ?></td>
                            <td class="d-none d-lg-table-cell small text-muted">
                                <?= e($movimento['referencia'] ?: '') ?>
                                <?php if ($movimento['observacao']): ?>
                                    <div><?= e($movimento['observacao']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require BASE_PATH . '/app/Views/layouts/_paginacao.php'; ?>
    </div>
</div>
