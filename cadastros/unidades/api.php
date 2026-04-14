<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

// GET — lista ou registro único
if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare("SELECT * FROM unidades WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT * FROM unidades WHERE nome LIKE ? OR sigla LIKE ? ORDER BY nome");
    $stmt->execute([$q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// POST — criar ou atualizar
if ($method === 'POST') {
    $nome  = trim($input['nome'] ?? '');
    $sigla = strtoupper(trim($input['sigla'] ?? ''));
    $ativo = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if (!$sigla) jsonResponse(['success' => false, 'message' => 'Sigla é obrigatória.'], 422);
    if (strlen($sigla) > 5) jsonResponse(['success' => false, 'message' => 'Sigla deve ter no máximo 5 caracteres.'], 422);

    try {
        if ($id) {
            $stmt = db()->prepare("UPDATE unidades SET nome = ?, sigla = ?, ativo = ? WHERE id = ?");
            $stmt->execute([$nome, $sigla, $ativo, $id]);
            jsonResponse(['success' => true, 'message' => 'Unidade atualizada com sucesso.']);
        } else {
            $stmt = db()->prepare("INSERT INTO unidades (nome, sigla, ativo) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $sigla, $ativo]);
            jsonResponse(['success' => true, 'message' => 'Unidade criada com sucesso.', 'id' => db()->lastInsertId()]);
        }
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate'))
            jsonResponse(['success' => false, 'message' => 'Sigla já cadastrada.'], 422);
        throw $e;
    }
}

// DELETE
if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM unidades WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Unidade excluída com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: unidade vinculada a produtos.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
