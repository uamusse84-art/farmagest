-- =====================================================================
-- FarmaGest - Sistema de Gestao de Farmacia
-- Esquema da base de dados relacional (MySQL / MariaDB)
-- Autor: Nordino Elias Jossias Uamusse (31240558)
-- =====================================================================

DROP DATABASE IF EXISTS farmagest;
CREATE DATABASE farmagest
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;
USE farmagest;

-- ---------------------------------------------------------------------
-- Tabela: utilizadores
-- Contas de acesso ao sistema. O perfil define as permissoes (RBAC).
-- ---------------------------------------------------------------------
CREATE TABLE utilizadores (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    palavra_passe   VARCHAR(255)  NOT NULL COMMENT 'hash bcrypt',
    perfil          ENUM('administrador','farmaceutico','caixa') NOT NULL DEFAULT 'caixa',
    telefone        VARCHAR(20)   NULL,
    ativo           TINYINT(1)    NOT NULL DEFAULT 1,
    ultimo_acesso   DATETIME      NULL,
    criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_utilizadores_email UNIQUE (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: categorias
-- Classificacao terapeutica dos medicamentos.
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(80)   NOT NULL,
    descricao       VARCHAR(255)  NULL,
    criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_categorias_nome UNIQUE (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: fornecedores
-- Entidades que fornecem os lotes de medicamentos.
-- ---------------------------------------------------------------------
CREATE TABLE fornecedores (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)  NOT NULL,
    nuit            VARCHAR(20)   NULL COMMENT 'Numero Unico de Identificacao Tributaria',
    telefone        VARCHAR(20)   NULL,
    email           VARCHAR(150)  NULL,
    endereco        VARCHAR(200)  NULL,
    ativo           TINYINT(1)    NOT NULL DEFAULT 1,
    criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_fornecedores_nuit UNIQUE (nuit)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: medicamentos
-- Catalogo de produtos. O stock nao vive aqui: vive nos lotes.
-- ---------------------------------------------------------------------
CREATE TABLE medicamentos (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo             VARCHAR(30)   NOT NULL,
    nome               VARCHAR(150)  NOT NULL,
    categoria_id       INT UNSIGNED  NOT NULL,
    principio_ativo    VARCHAR(150)  NULL,
    forma_farmaceutica ENUM('comprimido','capsula','xarope','injetavel','pomada','suspensao','gotas','outro')
                       NOT NULL DEFAULT 'comprimido',
    dosagem            VARCHAR(50)   NULL,
    preco_venda        DECIMAL(10,2) NOT NULL,
    stock_minimo       INT UNSIGNED  NOT NULL DEFAULT 10,
    requer_receita     TINYINT(1)    NOT NULL DEFAULT 0,
    ativo              TINYINT(1)    NOT NULL DEFAULT 1,
    criado_em          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_medicamentos_codigo UNIQUE (codigo),
    CONSTRAINT fk_medicamentos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_medicamentos_preco CHECK (preco_venda >= 0)
) ENGINE=InnoDB;

CREATE INDEX ix_medicamentos_nome ON medicamentos (nome);
CREATE INDEX ix_medicamentos_categoria ON medicamentos (categoria_id);

-- ---------------------------------------------------------------------
-- Tabela: lotes
-- Cada entrada de stock e rastreada por lote e data de validade (FEFO).
-- ---------------------------------------------------------------------
CREATE TABLE lotes (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicamento_id     INT UNSIGNED  NOT NULL,
    fornecedor_id      INT UNSIGNED  NOT NULL,
    numero_lote        VARCHAR(50)   NOT NULL,
    data_validade      DATE          NOT NULL,
    quantidade_inicial INT UNSIGNED  NOT NULL,
    quantidade_atual   INT UNSIGNED  NOT NULL,
    preco_compra       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    data_entrada       DATE          NOT NULL,
    criado_em          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_lotes_medicamento_numero UNIQUE (medicamento_id, numero_lote),
    CONSTRAINT fk_lotes_medicamento
        FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lotes_fornecedor
        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_lotes_quantidade CHECK (quantidade_atual <= quantidade_inicial)
) ENGINE=InnoDB;

CREATE INDEX ix_lotes_validade ON lotes (data_validade);
CREATE INDEX ix_lotes_medicamento ON lotes (medicamento_id);

-- ---------------------------------------------------------------------
-- Tabela: clientes
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150)  NOT NULL,
    nuit            VARCHAR(20)   NULL,
    telefone        VARCHAR(20)   NULL,
    email           VARCHAR(150)  NULL,
    endereco        VARCHAR(200)  NULL,
    criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_clientes_nuit UNIQUE (nuit)
) ENGINE=InnoDB;

CREATE INDEX ix_clientes_nome ON clientes (nome);

-- ---------------------------------------------------------------------
-- Tabela: vendas
-- Cabecalho da venda. O total e calculado a partir dos itens.
-- ---------------------------------------------------------------------
CREATE TABLE vendas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero          VARCHAR(20)   NOT NULL COMMENT 'Ex.: VD-2026-000001',
    cliente_id      INT UNSIGNED  NULL COMMENT 'NULL = consumidor final',
    utilizador_id   INT UNSIGNED  NOT NULL,
    data_venda      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    desconto        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    forma_pagamento ENUM('numerario','mpesa','emola','cartao','transferencia') NOT NULL DEFAULT 'numerario',
    estado          ENUM('concluida','anulada') NOT NULL DEFAULT 'concluida',
    observacoes     VARCHAR(255)  NULL,
    CONSTRAINT uq_vendas_numero UNIQUE (numero),
    CONSTRAINT fk_vendas_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_vendas_utilizador
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_vendas_desconto CHECK (desconto >= 0 AND desconto <= subtotal)
) ENGINE=InnoDB;

CREATE INDEX ix_vendas_data ON vendas (data_venda);
CREATE INDEX ix_vendas_estado ON vendas (estado);

-- ---------------------------------------------------------------------
-- Tabela: itens_venda
-- Linhas da venda. Guarda o lote para rastreabilidade e o preco praticado.
-- ---------------------------------------------------------------------
CREATE TABLE itens_venda (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venda_id        INT UNSIGNED  NOT NULL,
    lote_id         INT UNSIGNED  NOT NULL,
    medicamento_id  INT UNSIGNED  NOT NULL,
    quantidade      INT UNSIGNED  NOT NULL,
    preco_unitario  DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_itens_venda
        FOREIGN KEY (venda_id) REFERENCES vendas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_lote
        FOREIGN KEY (lote_id) REFERENCES lotes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_itens_medicamento
        FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_itens_quantidade CHECK (quantidade > 0)
) ENGINE=InnoDB;

CREATE INDEX ix_itens_venda ON itens_venda (venda_id);

-- ---------------------------------------------------------------------
-- Tabela: movimentos_stock
-- Trilha de auditoria de todas as alteracoes de stock.
-- ---------------------------------------------------------------------
CREATE TABLE movimentos_stock (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lote_id         INT UNSIGNED  NOT NULL,
    utilizador_id   INT UNSIGNED  NOT NULL,
    tipo            ENUM('entrada','saida','anulacao','ajuste','quebra') NOT NULL,
    quantidade      INT           NOT NULL COMMENT 'positiva = entrada, negativa = saida',
    referencia      VARCHAR(60)   NULL COMMENT 'Ex.: numero da venda',
    observacao      VARCHAR(255)  NULL,
    criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentos_lote
        FOREIGN KEY (lote_id) REFERENCES lotes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_movimentos_utilizador
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_movimentos_lote ON movimentos_stock (lote_id);
CREATE INDEX ix_movimentos_data ON movimentos_stock (criado_em);

-- ---------------------------------------------------------------------
-- Vista: vw_stock_medicamentos
-- Stock consolidado por medicamento, ignorando lotes ja expirados.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_stock_medicamentos AS
SELECT
    m.id                AS medicamento_id,
    m.codigo,
    m.nome,
    m.preco_venda,
    m.stock_minimo,
    m.requer_receita,
    m.ativo,
    c.nome              AS categoria,
    COALESCE(SUM(CASE WHEN l.data_validade >= CURDATE() THEN l.quantidade_atual END), 0) AS stock_disponivel,
    COALESCE(SUM(CASE WHEN l.data_validade <  CURDATE() THEN l.quantidade_atual END), 0) AS stock_expirado,
    MIN(CASE WHEN l.data_validade >= CURDATE() AND l.quantidade_atual > 0 THEN l.data_validade END) AS proxima_validade
FROM medicamentos m
INNER JOIN categorias c ON c.id = m.categoria_id
LEFT  JOIN lotes l      ON l.medicamento_id = m.id
GROUP BY m.id, m.codigo, m.nome, m.preco_venda, m.stock_minimo, m.requer_receita, m.ativo, c.nome;

-- ---------------------------------------------------------------------
-- Vista: vw_vendas_diarias
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_vendas_diarias AS
SELECT
    DATE(v.data_venda) AS dia,
    COUNT(*)           AS total_vendas,
    SUM(v.total)       AS valor_total
FROM vendas v
WHERE v.estado = 'concluida'
GROUP BY DATE(v.data_venda);
