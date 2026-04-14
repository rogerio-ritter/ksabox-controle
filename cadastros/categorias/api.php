<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT * FROM categorias WHERE nome LIKE ? OR ncm LIKE ? ORDER BY nome");
    $stmt->execute([$q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome             = trim($input['nome'] ?? '');
    $ncm              = trim($input['ncm'] ?? '');
    $perc_seguro      = (float)($input['perc_seguro'] ?? 1.00);
    $perc_ii          = (float)($input['perc_ii'] ?? 0.00);
    $perc_pis         = (float)($input['perc_pis'] ?? 2.10);
    $perc_cofins      = (float)($input['perc_cofins'] ?? 9.65);
    $perc_ipi         = (float)($input['perc_ipi'] ?? 0.00);
    $perc_antidumping = (float)($input['perc_antidumping'] ?? 0.00);
    $perc_icms        = (float)($input['perc_icms'] ?? 19.50);
    $ativo            = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if (!$ncm)  jsonResponse(['success' => false, 'message' => 'NCM é obrigatório.'], 422);

    $fields = [$nome, $ncm, $perc_seguro, $perc_ii, $perc_pis, $perc_cofins, $perc_ipi, $perc_antidumping, $perc_icms, $ativo];

    if ($id) {
        $stmt = db()->prepare("UPDATE categorias SET nome=?, ncm=?, perc_seguro=?, perc_ii=?, perc_pis=?, perc_cofins=?, perc_ipi=?, perc_antidumping=?, perc_icms=?, ativo=? WHERE id=?");
        $stmt->execute([...$fields, $id]);
        jsonResponse(['success' => true, 'message' => 'Categoria atualizada com sucesso.']);
    } else {
        $stmt = db()->prepare("INSERT INTO categorias (nome,ncm,perc_seguro,perc_ii,perc_pis,perc_cofins,perc_ipi,perc_antidumping,perc_icms,ativo) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute($fields);
        jsonResponse(['success' => true, 'message' => 'Categoria criada com sucesso.', 'id' => db()->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Categoria excluída com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: categoria vinculada a produtos.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
