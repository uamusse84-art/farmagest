-- =====================================================================
-- FarmaGest - Dados de demonstracao
-- Executar depois de schema.sql
-- Palavras-passe: Admin@123 / Farm@123 / Caixa@123
-- =====================================================================
USE farmagest;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE movimentos_stock;
TRUNCATE TABLE itens_venda;
TRUNCATE TABLE vendas;
TRUNCATE TABLE lotes;
TRUNCATE TABLE medicamentos;
TRUNCATE TABLE clientes;
TRUNCATE TABLE fornecedores;
TRUNCATE TABLE categorias;
TRUNCATE TABLE utilizadores;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------- Utilizadores ----------------------------
INSERT INTO utilizadores (id, nome, email, palavra_passe, perfil, telefone, ativo) VALUES
(1, 'Nordino Elias Jossias Uamusse', 'admin@farmagest.co.mz',
 '$2y$10$VE1XQlSFpNLTUXH5qwxbo.N.IUHCWuitp7GsQi85gjaJDg1Zhw38u', 'administrador', '+258 84 000 0001', 1),
(2, 'Ana Cristina Mabjaia', 'farmaceutico@farmagest.co.mz',
 '$2y$10$x3RXAAYbdkSjZs9VZZevpOeDdID5In7roze.NRIoB79shWncn1DgK', 'farmaceutico', '+258 84 000 0002', 1),
(3, 'Julio Alberto Cossa', 'caixa@farmagest.co.mz',
 '$2y$10$kIhfPAE0bt0muPIlXXfOrurLGeBbTdLpU.lyP8JTVpaXHK0ZRaLOi', 'caixa', '+258 84 000 0003', 1);

-- ---------------------------- Categorias -----------------------------
INSERT INTO categorias (id, nome, descricao) VALUES
(1, 'Analgesicos',      'Medicamentos para alivio da dor'),
(2, 'Antibioticos',     'Combate a infecoes bacterianas'),
(3, 'Antimalaricos',    'Tratamento e profilaxia da malaria'),
(4, 'Anti-inflamatorios','Reducao de processos inflamatorios'),
(5, 'Vitaminas',        'Suplementos vitaminicos e minerais'),
(6, 'Antihipertensores','Controlo da tensao arterial'),
(7, 'Dermatologicos',   'Aplicacao topica na pele');

-- --------------------------- Fornecedores ----------------------------
INSERT INTO fornecedores (id, nome, nuit, telefone, email, endereco, ativo) VALUES
(1, 'Medimoc, SA',            '400123456', '+258 21 400 100', 'geral@medimoc.co.mz',   'Av. 25 de Setembro, 1200, Maputo', 1),
(2, 'Farmac Distribuicao Lda','400987654', '+258 21 300 220', 'vendas@farmac.co.mz',   'Rua da Beira, 45, Matola',        1),
(3, 'Sociedade Sofarma Lda',  '400555321', '+258 21 490 780', 'comercial@sofarma.co.mz','Av. Julius Nyerere, 890, Maputo', 1),
(4, 'Global Pharma Mocambique','400777888','+258 84 512 3300','info@globalpharma.co.mz','Av. Eduardo Mondlane, 55, Beira', 1);

-- --------------------------- Medicamentos ----------------------------
INSERT INTO medicamentos (id, codigo, nome, categoria_id, principio_ativo, forma_farmaceutica, dosagem, preco_venda, stock_minimo, requer_receita, ativo) VALUES
(1,  'MED-0001', 'Paracetamol 500mg',        1, 'Paracetamol',              'comprimido', '500 mg',   45.00,  50, 0, 1),
(2,  'MED-0002', 'Ibuprofeno 400mg',         4, 'Ibuprofeno',               'comprimido', '400 mg',   85.00,  40, 0, 1),
(3,  'MED-0003', 'Amoxicilina 500mg',        2, 'Amoxicilina',              'capsula',    '500 mg',  210.00,  30, 1, 1),
(4,  'MED-0004', 'Azitromicina 250mg',       2, 'Azitromicina',             'comprimido', '250 mg',  350.00,  25, 1, 1),
(5,  'MED-0005', 'Coartem 20/120mg',         3, 'Artemeter + Lumefantrina', 'comprimido', '20/120 mg',480.00,  20, 1, 1),
(6,  'MED-0006', 'Acido Acetilsalicilico 100mg',1,'Acido acetilsalicilico', 'comprimido', '100 mg',   60.00,  40, 0, 1),
(7,  'MED-0007', 'Vitamina C 1000mg',        5, 'Acido ascorbico',          'comprimido', '1000 mg',  120.00, 35, 0, 1),
(8,  'MED-0008', 'Complexo B',               5, 'Vitaminas do complexo B',  'capsula',    '-',        150.00, 30, 0, 1),
(9,  'MED-0009', 'Xarope para a Tosse 100ml',1, 'Dextrometorfano',          'xarope',     '100 ml',   190.00, 20, 0, 1),
(10, 'MED-0010', 'Captopril 25mg',           6, 'Captopril',                'comprimido', '25 mg',    130.00, 25, 1, 1),
(11, 'MED-0011', 'Losartan 50mg',            6, 'Losartan potassico',       'comprimido', '50 mg',    240.00, 25, 1, 1),
(12, 'MED-0012', 'Diclofenac Gel 30g',       7, 'Diclofenac de dietilamonio','pomada',    '30 g',     175.00, 15, 0, 1),
(13, 'MED-0013', 'Hidrocortisona Creme 15g', 7, 'Hidrocortisona',           'pomada',     '15 g',     165.00, 15, 1, 1),
(14, 'MED-0014', 'Metronidazol 400mg',       2, 'Metronidazol',             'comprimido', '400 mg',   95.00,  30, 1, 1),
(15, 'MED-0015', 'Soro Oral (sais de rehidratacao)',5,'Sais de rehidratacao','outro',     '20,5 g',   35.00,  60, 0, 1);

-- ------------------------------- Lotes -------------------------------
-- Datas de validade relativas a data de execucao para o cenario ficar sempre valido.
INSERT INTO lotes (medicamento_id, fornecedor_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual, preco_compra, data_entrada) VALUES
(1,  1, 'LT-PAR-2601', DATE_ADD(CURDATE(), INTERVAL 400 DAY), 500, 500, 28.00, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(1,  2, 'LT-PAR-2602', DATE_ADD(CURDATE(), INTERVAL  45 DAY), 200, 180, 27.50, DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
(2,  1, 'LT-IBU-2601', DATE_ADD(CURDATE(), INTERVAL 300 DAY), 300, 300, 55.00, DATE_SUB(CURDATE(), INTERVAL 40 DAY)),
(3,  3, 'LT-AMO-2601', DATE_ADD(CURDATE(), INTERVAL 210 DAY), 150, 150, 140.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(4,  3, 'LT-AZI-2601', DATE_ADD(CURDATE(), INTERVAL 250 DAY),  90,  90, 230.00, DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
(5,  1, 'LT-COA-2601', DATE_ADD(CURDATE(), INTERVAL 500 DAY), 120, 120, 320.00, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(6,  2, 'LT-AAS-2601', DATE_ADD(CURDATE(), INTERVAL 365 DAY), 250, 250, 38.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(7,  4, 'LT-VTC-2601', DATE_ADD(CURDATE(), INTERVAL 180 DAY), 200, 200, 78.00, DATE_SUB(CURDATE(), INTERVAL 50 DAY)),
(8,  4, 'LT-CXB-2601', DATE_ADD(CURDATE(), INTERVAL 220 DAY), 160, 160, 95.00, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
(9,  2, 'LT-XAR-2601', DATE_ADD(CURDATE(), INTERVAL  70 DAY),  80,  60, 120.00, DATE_SUB(CURDATE(), INTERVAL 80 DAY)),
(10, 1, 'LT-CAP-2601', DATE_ADD(CURDATE(), INTERVAL 330 DAY), 140, 140, 82.00, DATE_SUB(CURDATE(), INTERVAL 35 DAY)),
(11, 3, 'LT-LOS-2601', DATE_ADD(CURDATE(), INTERVAL 400 DAY), 110, 110, 158.00, DATE_SUB(CURDATE(), INTERVAL 28 DAY)),
(12, 4, 'LT-DIC-2601', DATE_ADD(CURDATE(), INTERVAL 150 DAY),  60,  12, 110.00, DATE_SUB(CURDATE(), INTERVAL 70 DAY)),
(13, 4, 'LT-HID-2601', DATE_ADD(CURDATE(), INTERVAL  25 DAY),  50,  40, 105.00, DATE_SUB(CURDATE(), INTERVAL 95 DAY)),
(14, 2, 'LT-MET-2601', DATE_ADD(CURDATE(), INTERVAL 280 DAY), 180, 180, 60.00, DATE_SUB(CURDATE(), INTERVAL 22 DAY)),
(15, 1, 'LT-SOR-2601', DATE_ADD(CURDATE(), INTERVAL 600 DAY), 400, 400, 20.00, DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
-- Lote ja expirado, para demonstrar o bloqueio de venda e o alerta
(2,  1, 'LT-IBU-2501', DATE_SUB(CURDATE(), INTERVAL  20 DAY), 100,  35, 54.00, DATE_SUB(CURDATE(), INTERVAL 400 DAY));

-- ----------------------------- Clientes ------------------------------
INSERT INTO clientes (id, nome, nuit, telefone, email, endereco) VALUES
(1, 'Maria Joana Sitoe',    '100234567', '+258 82 345 6789', 'mjsitoe@gmail.com',    'Bairro Polana Cimento, Maputo'),
(2, 'Carlos Alberto Nhaca', '100987123', '+258 84 765 4321', 'canhaca@gmail.com',    'Bairro Malhangalene, Maputo'),
(3, 'Clinica Sao Lucas',    '400321987', '+258 21 486 900',  'geral@saolucas.co.mz', 'Av. Vladimir Lenine, 320, Maputo'),
(4, 'Isabel Muianga',       '100445566', '+258 87 221 3344', 'isabelm@outlook.com',  'Bairro Costa do Sol, Maputo');
