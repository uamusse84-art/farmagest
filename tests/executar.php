<?php

declare(strict_types=1);

/**
 * Testes automatizados do FarmaGest.
 *
 * Executar a partir da raiz do projeto:
 *     php tests/executar.php
 *
 * Os testes correm sobre a base de dados configurada e deixam-na no estado
 * inicial (cada teste que altera dados desfaz as suas alteracoes no fim).
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\RegraNegocioException;
use App\Core\Validator;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\Utilizador;
use App\Models\Venda;

final class Testes
{
    private int $passados = 0;
    private int $falhados = 0;
    private string $grupoAtual = '';

    public function grupo(string $nome): void
    {
        $this->grupoAtual = $nome;
        echo PHP_EOL . "== {$nome} ==" . PHP_EOL;
    }

    public function verdadeiro(string $descricao, bool $condicao): void
    {
        $this->registar($descricao, $condicao, '');
    }

    public function igual(string $descricao, mixed $esperado, mixed $obtido): void
    {
        $this->registar(
            $descricao,
            $esperado === $obtido,
            sprintf('esperado %s, obtido %s', var_export($esperado, true), var_export($obtido, true))
        );
    }

    /** Verifica que o codigo lanca RegraNegocioException com a mensagem esperada. */
    public function lanca(string $descricao, callable $accao, string $fragmento = ''): void
    {
        try {
            $accao();
            $this->registar($descricao, false, 'nao foi lancada nenhuma excecao');
        } catch (RegraNegocioException $e) {
            $ok = $fragmento === '' || str_contains($e->getMessage(), $fragmento);
            $this->registar($descricao, $ok, "mensagem obtida: {$e->getMessage()}");
        }
    }

    private function registar(string $descricao, bool $ok, string $detalhe): void
    {
        if ($ok) {
            $this->passados++;
            echo "  [OK]    {$descricao}" . PHP_EOL;
            return;
        }

        $this->falhados++;
        echo "  [FALHA] {$descricao}" . PHP_EOL;
        if ($detalhe !== '') {
            echo "          {$detalhe}" . PHP_EOL;
        }
    }

    public function resumo(): int
    {
        $total = $this->passados + $this->falhados;
        echo PHP_EOL . str_repeat('-', 60) . PHP_EOL;
        echo "Total: {$total} | Passados: {$this->passados} | Falhados: {$this->falhados}" . PHP_EOL;

        return $this->falhados === 0 ? 0 : 1;
    }
}

$t = new Testes();
$bd = Database::ligacao();

/* ------------------------------------------------------------------ */
$t->grupo('Validador');

$v = new Validator(['nome' => ''], ['nome' => 'obrigatorio'], ['nome' => 'Nome']);
$t->verdadeiro('campo obrigatorio vazio e rejeitado', $v->falha());

$v = new Validator(['email' => 'invalido'], ['email' => 'email'], []);
$t->verdadeiro('e-mail mal formado e rejeitado', $v->falha());

$v = new Validator(['email' => 'a@b.co'], ['email' => 'email'], []);
$t->verdadeiro('e-mail valido e aceite', !$v->falha());

$v = new Validator(['preco' => '-5'], ['preco' => 'decimal|minimo:0'], []);
$t->verdadeiro('valor abaixo do minimo e rejeitado', $v->falha());

$v = new Validator(
    ['pw' => 'Abc12345', 'pw_confirmacao' => 'Abc12345'],
    ['pw' => 'palavra_passe_forte|confirmado'],
    []
);
$t->verdadeiro('palavra-passe forte e confirmada e aceite', !$v->falha());

$v = new Validator(
    ['pw' => 'Abc12345', 'pw_confirmacao' => 'diferente'],
    ['pw' => 'confirmado'],
    []
);
$t->verdadeiro('confirmacao divergente e rejeitada', $v->falha());

$v = new Validator(['email' => 'admin@farmagest.co.mz'], ['email' => 'unico:utilizadores,email'], []);
$t->verdadeiro('e-mail ja existente e rejeitado pela regra unico', $v->falha());

$v = new Validator(['id' => '999999'], ['id' => 'existe:categorias'], []);
$t->verdadeiro('chave estrangeira inexistente e rejeitada', $v->falha());

/* ------------------------------------------------------------------ */
$t->grupo('Autenticacao e perfis');

$utilizadores = new Utilizador();
$admin = $utilizadores->porEmail('admin@farmagest.co.mz');

$t->verdadeiro('utilizador administrador existe na base de dados', $admin !== null);
$t->verdadeiro(
    'palavra-passe do administrador confere com o hash bcrypt',
    password_verify('Admin@123', (string) $admin['palavra_passe'])
);
$t->verdadeiro(
    'palavra-passe errada e recusada',
    !password_verify('errada', (string) $admin['palavra_passe'])
);
$t->verdadeiro('palavra-passe nao esta guardada em texto simples', $admin['palavra_passe'] !== 'Admin@123');
$t->verdadeiro('existe pelo menos um administrador ativo', $utilizadores->contarAdministradoresAtivos() >= 1);

/* ------------------------------------------------------------------ */
$t->grupo('Stock e consultas');

$medicamentos = new Medicamento();
$lotes = new Lote();

$paracetamol = $bd->query("SELECT id FROM medicamentos WHERE codigo = 'MED-0001'")->fetchColumn();
$ibuprofeno  = $bd->query("SELECT id FROM medicamentos WHERE codigo = 'MED-0002'")->fetchColumn();
$diclofenac  = $bd->query("SELECT id FROM medicamentos WHERE codigo = 'MED-0012'")->fetchColumn();

$comStock = $medicamentos->comStock((int) $paracetamol);
$t->verdadeiro('vista de stock devolve quantidade disponivel', (int) $comStock['stock_disponivel'] > 0);

$t->verdadeiro(
    'lote expirado nao entra no stock disponivel do ibuprofeno',
    (int) $medicamentos->comStock((int) $ibuprofeno)['stock_expirado'] > 0
);

$fefo = $lotes->disponiveisFefo((int) $paracetamol);
$t->verdadeiro('existem lotes elegiveis por FEFO', count($fefo) > 1);

$ordenado = true;
for ($i = 1, $n = count($fefo); $i < $n; $i++) {
    if ($fefo[$i - 1]['data_validade'] > $fefo[$i]['data_validade']) {
        $ordenado = false;
    }
}
$t->verdadeiro('lotes FEFO vem ordenados por validade crescente', $ordenado);

$semExpirados = true;
foreach ($fefo as $lote) {
    if ($lote['data_validade'] < date('Y-m-d')) {
        $semExpirados = false;
    }
}
$t->verdadeiro('lotes expirados sao excluidos da lista FEFO', $semExpirados);

$abaixo = $medicamentos->abaixoDoMinimo();
$t->verdadeiro('alerta de stock minimo deteta o diclofenac', array_reduce(
    $abaixo,
    static fn (bool $c, array $m): bool => $c || (int) $m['medicamento_id'] === (int) $diclofenac,
    false
));

$t->verdadeiro('existem lotes expirados sinalizados', $lotes->expirados() !== []);
$t->verdadeiro('valor total do stock e positivo', $lotes->valorTotalStock() > 0);

/* ------------------------------------------------------------------ */
$t->grupo('Venda: regras de negocio');

$vendas = new Venda();
$utilizadorId = (int) $admin['id'];

$t->lanca(
    'venda sem itens e recusada',
    static fn () => $vendas->registar([], ['forma_pagamento' => 'numerario'], $utilizadorId),
    'pelo menos um item'
);

$t->lanca(
    'quantidade zero e recusada',
    static fn () => $vendas->registar(
        [['medicamento_id' => (int) $paracetamol, 'quantidade' => 0]],
        ['forma_pagamento' => 'numerario'],
        $utilizadorId
    ),
    'superior a zero'
);

$t->lanca(
    'stock insuficiente e recusado',
    static fn () => $vendas->registar(
        [['medicamento_id' => (int) $paracetamol, 'quantidade' => 999999]],
        ['forma_pagamento' => 'numerario'],
        $utilizadorId
    ),
    'Stock insuficiente'
);

$t->lanca(
    'desconto superior ao subtotal e recusado',
    static fn () => $vendas->registar(
        [['medicamento_id' => (int) $paracetamol, 'quantidade' => 1]],
        ['forma_pagamento' => 'numerario', 'desconto' => 999999],
        $utilizadorId
    ),
    'desconto'
);

$stockAntes = (int) $medicamentos->comStock((int) $paracetamol)['stock_disponivel'];
$t->igual(
    'transacao falhada nao consome stock (rollback)',
    $stockAntes,
    (int) $medicamentos->comStock((int) $paracetamol)['stock_disponivel']
);

// Medicamento inativo nao pode ser vendido.
$bd->prepare('UPDATE medicamentos SET ativo = 0 WHERE id = :id')->execute(['id' => $diclofenac]);
$t->lanca(
    'medicamento inativo nao pode ser vendido',
    static fn () => $vendas->registar(
        [['medicamento_id' => (int) $diclofenac, 'quantidade' => 1]],
        ['forma_pagamento' => 'numerario'],
        $utilizadorId
    ),
    'inativo'
);
$bd->prepare('UPDATE medicamentos SET ativo = 1 WHERE id = :id')->execute(['id' => $diclofenac]);

/* ------------------------------------------------------------------ */
$t->grupo('Venda: fluxo completo e FEFO');

$primeiroLote = $fefo[0];
$quantidadePedida = (int) $primeiroLote['quantidade_atual'] + 5; // atravessa dois lotes

$vendaId = $vendas->registar(
    [['medicamento_id' => (int) $paracetamol, 'quantidade' => $quantidadePedida]],
    ['forma_pagamento' => 'mpesa', 'desconto' => 10.0, 'observacoes' => 'Teste automatizado'],
    $utilizadorId
);

$t->verdadeiro('venda foi criada', $vendaId > 0);

$venda = $vendas->comRelacoes($vendaId);
$t->verdadeiro('numero da venda segue o formato VD-AAAA-NNNNNN', (bool) preg_match('/^VD-\d{4}-\d{6}$/', (string) $venda['numero']));
$t->igual('estado inicial da venda e concluida', 'concluida', $venda['estado']);

$itens = $vendas->itens($vendaId);
$t->verdadeiro('a venda foi dividida por mais do que um lote (FEFO)', count($itens) > 1);
$t->igual(
    'o primeiro lote consumido e o de validade mais proxima',
    (int) $primeiroLote['id'],
    (int) $itens[0]['lote_id']
);
$t->igual(
    'o primeiro lote foi esgotado antes de passar ao seguinte',
    (int) $primeiroLote['quantidade_atual'],
    (int) $itens[0]['quantidade']
);
$t->igual(
    'a soma das quantidades dos itens iguala a quantidade pedida',
    $quantidadePedida,
    array_sum(array_map(static fn (array $i): int => (int) $i['quantidade'], $itens))
);

$subtotalEsperado = array_sum(array_map(static fn (array $i): float => (float) $i['subtotal'], $itens));
$t->igual('subtotal da venda bate com a soma dos itens', round($subtotalEsperado, 2), round((float) $venda['subtotal'], 2));
$t->igual('total = subtotal - desconto', round($subtotalEsperado - 10.0, 2), round((float) $venda['total'], 2));

$stockDepois = (int) $medicamentos->comStock((int) $paracetamol)['stock_disponivel'];
$t->igual('o stock foi abatido exactamente na quantidade vendida', $stockAntes - $quantidadePedida, $stockDepois);

$movimentos = (int) $bd->query(
    "SELECT COUNT(*) FROM movimentos_stock WHERE tipo = 'saida' AND referencia = '{$venda['numero']}'"
)->fetchColumn();
$t->igual('foi registado um movimento de saida por cada lote consumido', count($itens), $movimentos);

/* ------------------------------------------------------------------ */
$t->grupo('Venda: anulacao e reposicao de stock');

$vendas->anular($vendaId, $utilizadorId, 'Teste automatizado de anulacao');

$vendaAnulada = $vendas->comRelacoes($vendaId);
$t->igual('a venda passa ao estado anulada', 'anulada', $vendaAnulada['estado']);
$t->verdadeiro('o motivo da anulacao fica registado', str_contains((string) $vendaAnulada['observacoes'], 'ANULADA'));
$t->igual(
    'o stock e reposto na totalidade',
    $stockAntes,
    (int) $medicamentos->comStock((int) $paracetamol)['stock_disponivel']
);

$t->lanca(
    'nao e possivel anular duas vezes a mesma venda',
    static fn () => $vendas->anular($vendaId, $utilizadorId, 'Segunda tentativa'),
    'ja se encontra anulada'
);

$t->verdadeiro(
    'a venda anulada nao e apagada (auditoria)',
    $vendas->encontrar($vendaId) !== null
);
$t->verdadeiro(
    'a venda anulada nao conta para a receita do dia',
    !str_contains(json_encode($vendas->resumoDoDia()), 'null')
);

// Limpeza: remove a venda de teste e os respetivos movimentos.
$bd->prepare("DELETE FROM movimentos_stock WHERE referencia = :n")->execute(['n' => $venda['numero']]);
$bd->prepare('DELETE FROM vendas WHERE id = :id')->execute(['id' => $vendaId]);
$t->verdadeiro('estado da base de dados reposto apos os testes', $vendas->encontrar($vendaId) === null);

/* ------------------------------------------------------------------ */
$t->grupo('Integridade referencial');

$t->verdadeiro('categoria com medicamentos nao pode ser eliminada', (new App\Models\Categoria())->temMedicamentos(
    (int) $bd->query('SELECT categoria_id FROM medicamentos LIMIT 1')->fetchColumn()
));
$t->verdadeiro('medicamento com lotes e detetado antes de eliminar', $medicamentos->temLotes((int) $paracetamol));
$t->verdadeiro('numero de lote duplicado no mesmo medicamento e detetado', $lotes->numeroJaUsado(
    (int) $paracetamol,
    (string) $bd->query("SELECT numero_lote FROM lotes WHERE medicamento_id = {$paracetamol} LIMIT 1")->fetchColumn()
));

exit($t->resumo());
