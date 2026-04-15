<?php
/**
 * configuracoes/empresa/api.php
 * GET  → retorna dados da empresa (id=1)
 * POST → atualiza dados da empresa
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();

/* ── GET ─────────────────────────────────────────────────────── */
if ($method === 'GET') {
    $row = db()->query("SELECT * FROM empresa WHERE id = 1")->fetch();
    jsonResponse(['success' => true, 'data' => $row ?: []]);
}

/* ── POST ────────────────────────────────────────────────────── */
if ($method === 'POST') {
    $nome        = trim($input['nome']        ?? '');
    $cnpj        = trim($input['cnpj']        ?? '') ?: null;
    $telefone    = trim($input['telefone']    ?? '') ?: null;
    $email       = trim($input['email']       ?? '') ?: null;
    $cep         = trim($input['cep']         ?? '') ?: null;
    $endereco    = trim($input['endereco']    ?? '') ?: null;
    $numero      = trim($input['numero']      ?? '') ?: null;
    $complemento = trim($input['complemento'] ?? '') ?: null;
    $bairro      = trim($input['bairro']      ?? '') ?: null;
    $cidade      = trim($input['cidade']      ?? '') ?: null;
    $uf          = trim($input['uf']          ?? '') ?: null;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome da empresa é obrigatório.'], 422);
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['success' => false, 'message' => 'E-mail inválido.'], 422);

    /* Garante que existe um registro (id=1) e atualiza */
    $pdo = db();
    $exists = $pdo->query("SELECT COUNT(*) FROM empresa WHERE id = 1")->fetchColumn();

    if ($exists) {
        $pdo->prepare("
            UPDATE empresa SET
                nome=?, cnpj=?, telefone=?, email=?,
                cep=?, endereco=?, numero=?, complemento=?,
                bairro=?, cidade=?, uf=?
            WHERE id = 1
        ")->execute([$nome, $cnpj, $telefone, $email, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $uf]);
    } else {
        $pdo->prepare("
            INSERT INTO empresa (id, nome, cnpj, telefone, email, cep, endereco, numero, complemento, bairro, cidade, uf)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$nome, $cnpj, $telefone, $email, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $uf]);
    }

    jsonResponse(['success' => true, 'message' => 'Dados da empresa salvos com sucesso.']);
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
