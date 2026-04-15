<?php
/**
 * setup.php — Instalador do banco de dados
 * Acesse UMA ÚNICA VEZ: http://localhost/ksabox-controle/setup.php
 * Após a instalação, DELETE este arquivo!
 */

require_once __DIR__ . '/includes/config.php';

// Conectar sem selecionar banco para poder criá-lo
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('<pre style="color:red">Erro de conexão MySQL: ' . $e->getMessage() . '</pre>');
}

$log = [];
$errors = [];

function run(PDO $pdo, string $sql, string $label, array &$log, array &$errors): void {
    try {
        $pdo->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        $errors[] = "❌ $label — " . $e->getMessage();
    }
}

// ─── 1. Criar banco ───────────────────────────────────────────────────────────
run($pdo,
    "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "Banco de dados '" . DB_NAME . "' criado/verificado", $log, $errors
);
$pdo->exec("USE `" . DB_NAME . "`");

// ─── 2. Tabelas ───────────────────────────────────────────────────────────────

run($pdo, "
CREATE TABLE IF NOT EXISTS `usuarios` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL COMMENT 'password_hash()',
    tema ENUM('claro','escuro') DEFAULT 'claro',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela usuarios", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `categorias` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ncm VARCHAR(10) NOT NULL COMMENT 'Formato: 9999.99.99',
    perc_seguro DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    perc_ii DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_pis DECIMAL(5,2) NOT NULL DEFAULT 2.10,
    perc_cofins DECIMAL(5,2) NOT NULL DEFAULT 9.65,
    perc_ipi DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_antidumping DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_icms DECIMAL(5,2) NOT NULL DEFAULT 19.50,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela categorias", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `unidades` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL,
    sigla VARCHAR(5) NOT NULL UNIQUE,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela unidades", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `fornecedores` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(50) NULL,
    contato VARCHAR(50) NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela fornecedores", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `clientes` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj_cpf VARCHAR(18) NULL COMMENT 'Máscara: 99.999.999/9999-99 ou 999.999.999-99',
    cep VARCHAR(10) NULL,
    endereco VARCHAR(100) NULL,
    numero VARCHAR(10) NULL,
    complemento VARCHAR(50) NULL,
    bairro VARCHAR(60) NULL,
    cidade VARCHAR(70) NULL,
    uf CHAR(2) NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(50) NULL,
    contato VARCHAR(50) NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela clientes", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `tabela_precos` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    multiplicador DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Ex: 1.00 = 100%, 0.90 = 90%',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela tabela_precos", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `produtos` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    unidade_sigla VARCHAR(5) NOT NULL,
    fornecedor_id INT NULL,
    referencia VARCHAR(50) NULL,
    descricao TEXT NULL,
    especificacoes TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria_id),
    INDEX idx_fornecedor (fornecedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela produtos", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `custo_produtos` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    valor_prod_usd DECIMAL(15,2) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    cotacao_dolar DECIMAL(10,4) NOT NULL,
    valor_prod_brl DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_usd * quantidade * cotacao_dolar) STORED,

    frete_usd DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    frete_brl DECIMAL(15,2) GENERATED ALWAYS AS (frete_usd * cotacao_dolar) STORED,

    perc_seguro DECIMAL(5,2) NOT NULL,
    valor_seguro DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_seguro / 100) STORED,

    perc_ii DECIMAL(5,2) NOT NULL,
    valor_ii DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_ii / 100) * perc_ii / 100) STORED,

    perc_pis DECIMAL(5,2) NOT NULL,
    valor_pis DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_pis / 100) * perc_pis / 100) STORED,

    perc_cofins DECIMAL(5,2) NOT NULL,
    valor_cofins DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_cofins / 100) * perc_cofins / 100) STORED,

    perc_ipi DECIMAL(5,2) NOT NULL,
    valor_ipi DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_ipi / 100) * perc_ipi / 100) STORED,

    perc_desp_aduaneiras DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    valor_comissao_compra DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    valor_desp_aduaneiras DECIMAL(15,2) GENERATED ALWAYS AS (
        (valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_comissao_compra)
        * perc_desp_aduaneiras / 100
    ) STORED,

    perc_antidumping DECIMAL(5,2) NOT NULL,
    valor_antidumping DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) * perc_antidumping / 100) STORED,

    base_icms DECIMAL(15,2) GENERATED ALWAYS AS (
        valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_antidumping
    ) STORED,

    perc_icms DECIMAL(5,2) NOT NULL,
    valor_icms DECIMAL(15,2) GENERATED ALWAYS AS (base_icms / (1 - perc_icms / 100) * perc_icms / 100) STORED,

    perc_custo_financeiro DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    valor_custo_financeiro DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_custo_financeiro / 100) STORED,

    perc_iof DECIMAL(5,2) NOT NULL DEFAULT 0.38,
    valor_iof DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_iof / 100) STORED,

    frete_regional DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    custo_total DECIMAL(15,2) GENERATED ALWAYS AS (
        base_icms + valor_icms + frete_regional + valor_comissao_compra
        + valor_custo_financeiro + valor_iof + valor_desp_aduaneiras
    ) STORED,
    valor_unitario DECIMAL(15,2) GENERATED ALWAYS AS (custo_total / quantidade) STORED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela custo_produtos (com GENERATED COLUMNS)", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `formacao_precos` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    custo_unitario DECIMAL(15,2) NOT NULL COMMENT 'Vem de custo_produtos.valor_unitario',
    valor_venda DECIMAL(15,2) NOT NULL,

    perc_material DECIMAL(5,2) NOT NULL DEFAULT 70.00,
    valor_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_material / 100) STORED,
    perc_servico DECIMAL(5,2) GENERATED ALWAYS AS (100 - perc_material) STORED,
    valor_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda - valor_material) STORED,

    perc_desp_admin DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    valor_desp_admin DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_desp_admin / 100) STORED,

    perc_desp_fixas DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    valor_desp_fixas DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_desp_fixas / 100) STORED,

    perc_comissao_venda DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    valor_comissao_venda DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_comissao_venda / 100) STORED,

    perc_pos_venda DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    valor_pos_venda DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_pos_venda / 100) STORED,

    perc_icms_venda DECIMAL(5,2) NOT NULL DEFAULT 19.50,
    icms_custo_unitario DECIMAL(15,2) NOT NULL COMMENT 'Vem de custo_produtos (valor_icms / quantidade)',
    valor_icms_venda DECIMAL(15,2) GENERATED ALWAYS AS ((valor_venda * perc_icms_venda / 100) - icms_custo_unitario) STORED,

    valor_montagem DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    perc_imp_interno_material DECIMAL(5,2) NOT NULL DEFAULT 3.65,
    valor_imp_interno_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_material * perc_imp_interno_material / 100) STORED,

    perc_imp_interno_servico DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    valor_imp_interno_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_servico * perc_imp_interno_servico / 100) STORED,

    total_desp_venda DECIMAL(15,2) GENERATED ALWAYS AS (
        valor_desp_admin + valor_desp_fixas + valor_comissao_venda + valor_pos_venda
        + valor_icms_venda + valor_imp_interno_material + valor_imp_interno_servico + valor_montagem
    ) STORED,

    margem_liquida DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda - custo_unitario - total_desp_venda) STORED,
    perc_margem_liquida DECIMAL(5,2) GENERATED ALWAYS AS ((margem_liquida / valor_venda) * 100) STORED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela formacao_precos (com GENERATED COLUMNS)", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `orcamentos` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL COMMENT 'Auto-gerado: ORC-YYYYMMDD-001',
    cliente_id INT NOT NULL,
    tabela_preco_id INT NOT NULL,
    data_criacao DATE NOT NULL,
    validade DATE NOT NULL,
    status ENUM('Rascunho','Enviado','Aprovado','Rejeitado','Cancelado') DEFAULT 'Rascunho',
    observacoes TEXT NULL,
    subtotal_material DECIMAL(15,2) DEFAULT 0.00,
    subtotal_servico DECIMAL(15,2) DEFAULT 0.00,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    total_ipi DECIMAL(15,2) DEFAULT 0.00,
    tipo_desconto ENUM('valor','percentual') DEFAULT 'percentual',
    desconto_valor DECIMAL(15,2) DEFAULT 0.00,
    desconto_percentual DECIMAL(5,2) DEFAULT 0.00,
    total_geral DECIMAL(15,2) DEFAULT 0.00,
    prazo_entrega VARCHAR(100) NULL,
    condicao_pagamento TEXT NULL,
    condicao_entrega TEXT NULL,
    condicoes_gerais TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (tabela_preco_id) REFERENCES tabela_precos(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_data_criacao (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela orcamentos", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `orcamento_itens` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orcamento_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    valor_unitario DECIMAL(15,2) NOT NULL COMMENT 'Editável, vem de formacao_precos * multiplicador tabela',
    valor_total DECIMAL(15,2) GENERATED ALWAYS AS (quantidade * valor_unitario) STORED,
    perc_material DECIMAL(5,2) NOT NULL COMMENT 'Vem de formacao_precos',
    valor_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_total * perc_material / 100) STORED,
    perc_servico DECIMAL(5,2) GENERATED ALWAYS AS (100 - perc_material) STORED,
    valor_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_total - valor_material) STORED,
    perc_margem_liquida DECIMAL(5,2) NOT NULL COMMENT 'Recalculado com base no valor_unitario editado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela orcamento_itens", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `estoque` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela estoque", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `movimentacao_estoque` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    referencia VARCHAR(100) NULL COMMENT 'NF, Orçamento, etc',
    observacao TEXT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_produto (produto_id),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela movimentacao_estoque", $log, $errors);

run($pdo, "
CREATE TABLE IF NOT EXISTS `empresa` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18) NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    cep VARCHAR(10) NULL,
    endereco VARCHAR(100) NULL,
    numero VARCHAR(10) NULL,
    complemento VARCHAR(50) NULL,
    bairro VARCHAR(60) NULL,
    cidade VARCHAR(70) NULL,
    uf CHAR(2) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "Tabela empresa", $log, $errors);

// ─── 3. Dados iniciais ────────────────────────────────────────────────────────

// Usuário admin (senha: admin123)
$hash = password_hash('admin123', PASSWORD_DEFAULT);
run($pdo,
    "INSERT IGNORE INTO `usuarios` (nome, email, senha) VALUES ('Administrador', 'admin@admin.com', '$hash')",
    "Usuário admin criado", $log, $errors
);

// Empresa padrão
run($pdo,
    "INSERT IGNORE INTO `empresa` (id, nome) VALUES (1, 'Ksabox - Arquitetura Modular')",
    "Registro de empresa criado", $log, $errors
);

// Unidades padrão
$unidades = [
    ['Metro','m'], ['Metro Quadrado','m²'], ['Peça','pc'],
    ['Caixa','cx'], ['Conjunto','cj'], ['Unidade','un'],
    ['Quilograma','kg'], ['Palete','plt']
];
foreach ($unidades as [$nome, $sigla]) {
    run($pdo,
        "INSERT IGNORE INTO `unidades` (nome, sigla) VALUES ('$nome', '$sigla')",
        "Unidade: $sigla", $log, $errors
    );
}

// Tabela de preço padrão
run($pdo,
    "INSERT IGNORE INTO `tabela_precos` (nome, multiplicador) VALUES ('Tabela Padrão', 1.00)",
    "Tabela de preço padrão", $log, $errors
);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Setup — Ksabox</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
<div class="bg-white rounded-2xl shadow-lg max-w-2xl w-full p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Setup — Ksabox</h1>
            <p class="text-gray-500 text-sm">Instalação do banco de dados</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <p class="font-semibold text-red-700 mb-2">Erros encontrados:</p>
        <ul class="text-sm space-y-1">
            <?php foreach ($errors as $e): ?>
                <li class="text-red-600"><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="bg-gray-50 rounded-lg p-4 mb-6 max-h-96 overflow-y-auto">
        <?php foreach ($log as $l): ?>
            <p class="text-sm py-0.5 <?= str_starts_with($l, '✅') ? 'text-green-700' : 'text-red-600' ?>"><?= htmlspecialchars($l) ?></p>
        <?php endforeach; ?>
    </div>

    <?php if (empty($errors)): ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <p class="font-semibold text-green-800">Instalação concluída com sucesso!</p>
        <p class="text-sm text-green-700 mt-1">
            <strong>Login:</strong> admin@admin.com &nbsp;|&nbsp; <strong>Senha:</strong> admin123
        </p>
    </div>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6 text-sm text-yellow-800">
        ⚠️ <strong>Importante:</strong> Delete o arquivo <code>setup.php</code> antes de usar o sistema!
    </div>
    <a href="<?= APP_URL ?>/login.php"
       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
        Ir para o Login →
    </a>
    <?php else: ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800 font-semibold">Instalação concluída com erros. Verifique as mensagens acima.</p>
        <p class="text-sm text-red-600 mt-1">Confira as configurações no arquivo <code>.env</code> e tente novamente.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
