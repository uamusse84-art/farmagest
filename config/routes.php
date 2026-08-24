<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\ClienteController;
use App\Controllers\DashboardController;
use App\Controllers\FornecedorController;
use App\Controllers\LoteController;
use App\Controllers\MedicamentoController;
use App\Controllers\RelatorioController;
use App\Controllers\UtilizadorController;
use App\Controllers\VendaController;
use App\Core\Router;

$router = new Router();

// Perfis com permissao de escrita sobre catalogo e stock.
$gestao = ['perfis' => ['farmaceutico']];
// Area exclusiva do administrador (Auth::temPerfil deixa sempre passar o admin).
$admin  = ['perfis' => ['administrador']];

// ------------------------------ Autenticacao ------------------------------
$router->get('/login',   [AuthController::class, 'mostrarLogin'], ['auth' => false]);
$router->post('/login',  [AuthController::class, 'autenticar'],   ['auth' => false]);
$router->post('/logout', [AuthController::class, 'terminar']);

// --------------------------------- Painel ---------------------------------
$router->get('/',       [DashboardController::class, 'indice']);
$router->get('/painel', [DashboardController::class, 'indice']);

// ------------------------------- Categorias -------------------------------
$router->get('/categorias',                  [CategoriaController::class, 'indice'], $gestao);
$router->get('/categorias/criar',            [CategoriaController::class, 'criar'], $gestao);
$router->post('/categorias',                 [CategoriaController::class, 'guardar'], $gestao);
$router->get('/categorias/{id}/editar',      [CategoriaController::class, 'editar'], $gestao);
$router->post('/categorias/{id}',            [CategoriaController::class, 'atualizar'], $gestao);
$router->post('/categorias/{id}/eliminar',   [CategoriaController::class, 'eliminar'], $gestao);

// ------------------------------ Fornecedores ------------------------------
$router->get('/fornecedores',                [FornecedorController::class, 'indice'], $gestao);
$router->get('/fornecedores/criar',          [FornecedorController::class, 'criar'], $gestao);
$router->post('/fornecedores',               [FornecedorController::class, 'guardar'], $gestao);
$router->get('/fornecedores/{id}/editar',    [FornecedorController::class, 'editar'], $gestao);
$router->post('/fornecedores/{id}',          [FornecedorController::class, 'atualizar'], $gestao);
$router->post('/fornecedores/{id}/eliminar', [FornecedorController::class, 'eliminar'], $gestao);

// ------------------------------ Medicamentos ------------------------------
// A consulta esta disponivel a todos os perfis; a escrita e restrita.
$router->get('/medicamentos',                [MedicamentoController::class, 'indice']);
$router->get('/medicamentos/criar',          [MedicamentoController::class, 'criar'], $gestao);
$router->post('/medicamentos',               [MedicamentoController::class, 'guardar'], $gestao);
$router->get('/medicamentos/{id}',           [MedicamentoController::class, 'detalhe']);
$router->get('/medicamentos/{id}/editar',    [MedicamentoController::class, 'editar'], $gestao);
$router->post('/medicamentos/{id}',          [MedicamentoController::class, 'atualizar'], $gestao);
$router->post('/medicamentos/{id}/eliminar', [MedicamentoController::class, 'eliminar'], $gestao);

// ---------------------------------- Lotes ---------------------------------
$router->get('/lotes',                [LoteController::class, 'indice'], $gestao);
$router->get('/lotes/criar',          [LoteController::class, 'criar'], $gestao);
$router->post('/lotes',               [LoteController::class, 'guardar'], $gestao);
$router->get('/lotes/{id}',           [LoteController::class, 'detalhe'], $gestao);
$router->get('/lotes/{id}/editar',    [LoteController::class, 'editar'], $gestao);
$router->post('/lotes/{id}',          [LoteController::class, 'atualizar'], $gestao);
$router->post('/lotes/{id}/ajustar',  [LoteController::class, 'ajustar'], $gestao);
$router->post('/lotes/{id}/eliminar', [LoteController::class, 'eliminar'], $gestao);

// -------------------------------- Clientes --------------------------------
$router->get('/clientes',                [ClienteController::class, 'indice'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->get('/clientes/criar',          [ClienteController::class, 'criar'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->post('/clientes',               [ClienteController::class, 'guardar'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->get('/clientes/{id}',           [ClienteController::class, 'detalhe'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->get('/clientes/{id}/editar',    [ClienteController::class, 'editar'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->post('/clientes/{id}',          [ClienteController::class, 'atualizar'], ['perfis' => ['farmaceutico', 'caixa']]);
$router->post('/clientes/{id}/eliminar', [ClienteController::class, 'eliminar'], $gestao);

// --------------------------------- Vendas ---------------------------------
$vendasPerfis = ['perfis' => ['farmaceutico', 'caixa']];
$router->get('/vendas',              [VendaController::class, 'indice'], $vendasPerfis);
$router->get('/vendas/criar',        [VendaController::class, 'criar'], $vendasPerfis);
$router->post('/vendas',             [VendaController::class, 'guardar'], $vendasPerfis);
$router->get('/vendas/{id}',         [VendaController::class, 'detalhe'], $vendasPerfis);
$router->get('/vendas/{id}/recibo',  [VendaController::class, 'recibo'], $vendasPerfis);
$router->post('/vendas/{id}/anular', [VendaController::class, 'anular'], $gestao);

// -------------------------------- Relatorios ------------------------------
$router->get('/relatorios',            [RelatorioController::class, 'indice'], $gestao);
$router->get('/relatorios/vendas',     [RelatorioController::class, 'vendas'], $gestao);
$router->get('/relatorios/stock',      [RelatorioController::class, 'stock'], $gestao);
$router->get('/relatorios/movimentos', [RelatorioController::class, 'movimentos'], $gestao);

// ------------------------------ Utilizadores ------------------------------
$router->get('/utilizadores',                [UtilizadorController::class, 'indice'], $admin);
$router->get('/utilizadores/criar',          [UtilizadorController::class, 'criar'], $admin);
$router->post('/utilizadores',               [UtilizadorController::class, 'guardar'], $admin);
$router->get('/utilizadores/{id}/editar',    [UtilizadorController::class, 'editar'], $admin);
$router->post('/utilizadores/{id}',          [UtilizadorController::class, 'atualizar'], $admin);
$router->post('/utilizadores/{id}/eliminar', [UtilizadorController::class, 'eliminar'], $admin);

return $router;
