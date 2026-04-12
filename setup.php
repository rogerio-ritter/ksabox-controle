<?php
/**
 * Setup Script — run ONCE to create tables and admin user.
 * Access: http://localhost/padrao-claude/setup.php
 * DELETE this file after running!
 */
require_once __DIR__ . '/includes/config.php';

try {
    // Connect without DB to create it
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    $sql = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tema ENUM('claro','escuro') DEFAULT 'claro',
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS empresa (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(200) NOT NULL,
        cnpj VARCHAR(20) DEFAULT '',
        endereco TEXT DEFAULT '',
        telefone VARCHAR(20) DEFAULT '',
        email VARCHAR(100) DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao TEXT,
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS produtos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categoria_id INT DEFAULT NULL,
        nome VARCHAR(200) NOT NULL,
        descricao TEXT,
        unidade VARCHAR(20) DEFAULT 'un',
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(200) NOT NULL,
        email VARCHAR(100) DEFAULT '',
        telefone VARCHAR(20) DEFAULT '',
        cpf_cnpj VARCHAR(20) DEFAULT '',
        endereco TEXT,
        cidade VARCHAR(100) DEFAULT '',
        estado VARCHAR(2) DEFAULT '',
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS tabelas_precos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao TEXT,
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS tabela_preco_itens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tabela_id INT NOT NULL,
        produto_id INT NOT NULL,
        preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        UNIQUE KEY uk_tabela_produto (tabela_id, produto_id),
        FOREIGN KEY (tabela_id) REFERENCES tabelas_precos(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS orcamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT DEFAULT NULL,
        usuario_id INT DEFAULT NULL,
        tabela_id INT DEFAULT NULL,
        numero VARCHAR(20) NOT NULL,
        status ENUM('rascunho','enviado','aprovado','rejeitado','cancelado') DEFAULT 'rascunho',
        data_criacao DATE,
        data_validade DATE DEFAULT NULL,
        observacoes TEXT,
        total DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        FOREIGN KEY (tabela_id) REFERENCES tabelas_precos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS orcamento_itens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        orcamento_id INT NOT NULL,
        produto_id INT DEFAULT NULL,
        descricao VARCHAR(255) NOT NULL DEFAULT '',
        quantidade DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
        if ($q) $pdo->exec($q);
    }

    // Admin user
    $check = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE email='admin@admin.com'")->fetchColumn();
    if (!$check) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tema) VALUES (?, ?, ?, 'claro')")
            ->execute(['Administrador', 'admin@admin.com', $hash]);
    }

    // Demo empresa
    $checkEmp = $pdo->query("SELECT COUNT(*) FROM empresa")->fetchColumn();
    if (!$checkEmp) {
        $pdo->exec("INSERT INTO empresa (nome, cnpj, telefone, email) VALUES ('Minha Empresa Ltda.', '00.000.000/0001-00', '(00) 0000-0000', 'contato@minhaempresa.com')");
    }

    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Setup</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script></head>';
    echo '<body class="min-h-screen bg-gray-50 flex items-center justify-center">';
    echo '<div class="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full text-center">';
    echo '<div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">';
    echo '<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>';
    echo '<h1 class="text-xl font-bold text-gray-800 mb-2">Setup concluído!</h1>';
    echo '<p class="text-gray-500 text-sm mb-1">Banco de dados: <strong>' . DB_NAME . '</strong></p>';
    echo '<p class="text-gray-500 text-sm mb-6">Login: <strong>admin@admin.com</strong> / Senha: <strong>admin123</strong></p>';
    echo '<div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-5 text-xs text-amber-700"><strong>Atenção:</strong> Exclua o arquivo <code>setup.php</code> após o primeiro acesso!</div>';
    echo '<a href="' . APP_URL . '/login.php" class="inline-block w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition-colors">Ir para o Login</a>';
    echo '</div></body></html>';

} catch (PDOException $e) {
    echo '<div style="font-family:sans-serif;padding:2rem;max-width:600px;margin:2rem auto;background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;">';
    echo '<h2 style="color:#dc2626">Erro de conexão</h2>';
    echo '<p style="color:#7f1d1d">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="color:#6b7280;font-size:.9rem">Verifique as configurações no arquivo <strong>.env</strong> e se o MySQL está rodando.</p>';
    echo '</div>';
}
