<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare("SELECT * FROM tabela_precos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT * FROM tabela_precos WHERE nome LIKE ? ORDER BY nome");
    $stmt->execute([$q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome          = trim($input['nome'] ?? '');
    $multiplicador = (float)($input['multiplicador'] ?? 1.00);
    $ativo         = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if ($multiplicador <= 0) jsonResponse(['success' => false, 'message' => 'Multiplicador deve ser maior que zero.'], 422);

    if ($id) {
        $stmt = db()->prepare("UPDATE tabela_precos SET nome=?, multiplicador=?, ativo=? WHERE id=?");
        $stmt->execute([$nome, $multiplicador, $ativo, $id]);
        jsonResponse(['success' => true, 'message' => 'Tabela de preço atualizada com sucesso.']);
    } else {
        $stmt = db()->prepare("INSERT INTO tabela_precos (nome, multiplicador, ativo) VALUES (?,?,?)");
        $stmt->execute([$nome, $multiplicador, $ativo]);
        jsonResponse(['success' => true, 'message' => 'Tabela de preço criada com sucesso.', 'id' => db()->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM tabela_precos WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Tabela excluída com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: tabela vinculada a orçamentos.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
