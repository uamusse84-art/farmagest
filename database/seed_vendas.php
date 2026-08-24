<?php

/**
 * FarmaGest - Historico de vendas de demonstracao.
 *
 * Executar depois de schema.sql e seed.sql:
 *     php database/seed_vendas.php [--forcar]
 *
 * As vendas sao criadas atraves do proprio modelo (Venda::registar) para que o
 * abate FEFO, os itens e os movimentos de stock fiquem exactamente coerentes
 * com o que a aplicacao produz em utilizacao real. So a data e recuada, para
 * que os graficos e relatorios tenham um historico com que trabalhar.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Venda;

$bd      = Database::ligacao();
$forcar  = in_array('--forcar', $argv, true);
$modelo  = new Venda();
$jaExiste = (int) $bd->query('SELECT COUNT(*) FROM vendas')->fetchColumn();

if ($jaExiste > 0 && !$forcar) {
    echo "Ja existem {$jaExiste} vendas registadas. Use --forcar para recriar o historico.\n";
    exit(0);
}

if ($jaExiste > 0) {
    echo "A remover o historico anterior e a repor o stock...\n";
    $bd->exec("DELETE FROM movimentos_stock WHERE tipo IN ('saida', 'anulacao')");
    $bd->exec('DELETE FROM vendas');
    $bd->exec('UPDATE lotes SET quantidade_atual = quantidade_inicial');
    // Reposicao das excepcoes do seed: lotes parcialmente consumidos ou expirados.
    $bd->exec("UPDATE lotes SET quantidade_atual = 180 WHERE numero_lote = 'LT-PAR-2602'");
    $bd->exec("UPDATE lotes SET quantidade_atual =  60 WHERE numero_lote = 'LT-XAR-2601'");
    $bd->exec("UPDATE lotes SET quantidade_atual =  12 WHERE numero_lote = 'LT-DIC-2601'");
    $bd->exec("UPDATE lotes SET quantidade_atual =  40 WHERE numero_lote = 'LT-HID-2601'");
    $bd->exec("UPDATE lotes SET quantidade_atual =  35 WHERE numero_lote = 'LT-IBU-2501'");
}

// Semente fixa: o historico gerado e sempre o mesmo, o que torna as capturas
// de ecra do relatorio reproduziveis.
mt_srand(31240558);

/** Medicamentos elegiveis. O 12 (Diclofenac) fica de fora para preservar o
 *  cenario de alerta de stock minimo definido no seed. */
$catalogo = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14, 15];
$pesos    = [1 => 6, 2 => 5, 3 => 4, 4 => 3, 5 => 4, 6 => 3, 7 => 4, 8 => 3, 9 => 2, 10 => 3, 11 => 2, 13 => 1, 14 => 3, 15 => 5];

$roleta = [];
foreach ($catalogo as $id) {
    $roleta = array_merge($roleta, array_fill(0, $pesos[$id], $id));
}

$formas       = ['numerario', 'numerario', 'numerario', 'mpesa', 'mpesa', 'emola', 'cartao', 'transferencia'];
$operadores   = [3, 3, 3, 2, 2, 1];
$clientes     = [0, 0, 1, 2, 3, 4];
$criadas      = [];

for ($diasAtras = 29; $diasAtras >= 0; $diasAtras--) {
    // Fim-de-semana com menos movimento, para o grafico nao ficar plano.
    $diaSemana = (int) date('N', strtotime("-{$diasAtras} days"));
    $vendasDia = $diaSemana >= 6 ? mt_rand(1, 2) : mt_rand(2, 4);

    for ($n = 0; $n < $vendasDia; $n++) {
        // Sorteio ponderado sem repeticoes: tira-se da roleta ate juntar
        // medicamentos distintos que cheguem para o carrinho.
        $escolhidos = [];
        $quantos    = mt_rand(1, 3);

        for ($tentativa = 0; count($escolhidos) < $quantos && $tentativa < 30; $tentativa++) {
            $sorteado = $roleta[array_rand($roleta)];
            if (!in_array($sorteado, $escolhidos, true)) {
                $escolhidos[] = $sorteado;
            }
        }

        $itens = array_map(
            static fn (int $id): array => ['medicamento_id' => $id, 'quantidade' => mt_rand(1, 5)],
            $escolhidos
        );

        $clienteId = $clientes[array_rand($clientes)];
        $cabecalho = [
            'cliente_id'      => $clienteId,
            'forma_pagamento' => $formas[array_rand($formas)],
            'desconto'        => 0,
            'observacoes'     => '',
        ];

        try {
            $vendaId = $modelo->registar($itens, $cabecalho, $operadores[array_rand($operadores)]);
        } catch (Throwable $e) {
            echo "  aviso: venda ignorada ({$e->getMessage()})\n";
            continue;
        }

        // Desconto aplicado depois, para poder ser proporcional ao subtotal real.
        if (mt_rand(1, 5) === 1) {
            $venda    = $modelo->encontrar($vendaId);
            $desconto = round(((float) $venda['subtotal']) * (mt_rand(3, 10) / 100), 2);
            $bd->prepare('UPDATE vendas SET desconto = :d, total = subtotal - :d2 WHERE id = :id')
               ->execute(['d' => $desconto, 'd2' => $desconto, 'id' => $vendaId]);
        }

        $momento = date('Y-m-d H:i:s', strtotime(
            sprintf('-%d days %d:%02d:%02d', $diasAtras, mt_rand(8, 18), mt_rand(0, 59), mt_rand(0, 59))
        ));

        $bd->prepare('UPDATE vendas SET data_venda = :d WHERE id = :id')
           ->execute(['d' => $momento, 'id' => $vendaId]);
        $bd->prepare('UPDATE movimentos_stock SET criado_em = :d WHERE referencia = :ref')
           ->execute(['d' => $momento, 'ref' => $modelo->encontrar($vendaId)['numero']]);

        $criadas[] = $vendaId;
    }
}

// Duas anulacoes, para o estado "anulada" e a reposicao de stock ficarem
// visiveis na listagem e nos movimentos.
$paraAnular = [$criadas[3] ?? null, $criadas[count($criadas) - 6] ?? null];
$motivos    = ['Cliente desistiu da compra apos o registo.', 'Erro do operador: medicamento trocado.'];

foreach (array_filter($paraAnular) as $indice => $vendaId) {
    $modelo->anular((int) $vendaId, 1, $motivos[$indice] ?? 'Anulacao administrativa.');
}

$total    = (int) $bd->query("SELECT COUNT(*) FROM vendas WHERE estado = 'concluida'")->fetchColumn();
$anuladas = (int) $bd->query("SELECT COUNT(*) FROM vendas WHERE estado = 'anulada'")->fetchColumn();
$receita  = (float) $bd->query("SELECT COALESCE(SUM(total), 0) FROM vendas WHERE estado = 'concluida'")->fetchColumn();

echo "Historico criado com sucesso.\n";
echo "  Vendas concluidas : {$total}\n";
echo "  Vendas anuladas   : {$anuladas}\n";
echo '  Receita acumulada : ' . number_format($receita, 2, ',', ' ') . " MZN\n";
