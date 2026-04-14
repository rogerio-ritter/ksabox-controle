<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare("SELECT * FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT * FROM fornecedores WHERE nome LIKE ? OR contato LIKE ? OR email LIKE ? ORDER BY nome");
    $stmt->execute([$q, $q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome     = trim($input['nome'] ?? '');
    $telefone = trim($input['telefone'] ?? '');
    $email    = trim($input['email'] ?? '');
    $contato  = trim($input['contato'] ?? '');
    $ativo    = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);

    if ($id) {
        $stmt = db()->prepare("UPDATE fornecedores SET nome=?, telefone=?, email=?, contato=?, ativo=? WHERE id=?");
        $stmt->execute([$nome, $telefone ?: null, $email ?: null, $contato ?: null, $ativo, $id]);
        jsonResponse(['success' => true, 'message' => 'Fornecedor atualizado com sucesso.']);
    } else {
        $stmt = db()->prepare("INSERT INTO fornecedores (nome, telefone, email, contato, ativo) VALUES (?,?,?,?,?)");
        $stmt->execute([$nome, $telefone ?: null, $email ?: null, $contato ?: null, $ativo]);
        jsonResponse(['success' => true, 'message' => 'Fornecedor criado com sucesso.', 'id' => db()->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM fornecedores WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Fornecedor excluído com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: fornecedor vinculado a produtos.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
