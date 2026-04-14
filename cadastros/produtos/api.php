<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    // Retornar listas de apoio para selects
    if (isset($_GET['selects'])) {
        $cats  = db()->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
        $units = db()->query("SELECT sigla, nome FROM unidades WHERE ativo = 1 ORDER BY nome")->fetchAll();
        $fors  = db()->query("SELECT id, nome FROM fornecedores WHERE ativo = 1 ORDER BY nome")->fetchAll();
        jsonResponse(['success' => true, 'categorias' => $cats, 'unidades' => $units, 'fornecedores' => $fors]);
    }

    if ($id) {
        $stmt = db()->prepare("SELECT p.*, c.nome AS categoria_nome, f.nome AS fornecedor_nome
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
            WHERE p.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }

    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("
        SELECT p.id, p.nome, p.referencia, p.unidade_sigla, p.ativo,
               c.nome AS categoria_nome,
               f.nome AS fornecedor_nome
        FROM produtos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
        WHERE p.nome LIKE ? OR p.referencia LIKE ? OR c.nome LIKE ?
        ORDER BY p.nome
    ");
    $stmt->execute([$q, $q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome           = trim($input['nome'] ?? '');
    $categoria_id   = (int)($input['categoria_id'] ?? 0);
    $unidade_sigla  = trim($input['unidade_sigla'] ?? '');
    $fornecedor_id  = (int)($input['fornecedor_id'] ?? 0) ?: null;
    $referencia     = trim($input['referencia'] ?? '');
    $descricao      = trim($input['descricao'] ?? '');
    $especificacoes = trim($input['especificacoes'] ?? '');
    $ativo          = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome)         jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if (!$categoria_id) jsonResponse(['success' => false, 'message' => 'Categoria é obrigatória.'], 422);
    if (!$unidade_sigla) jsonResponse(['success' => false, 'message' => 'Unidade é obrigatória.'], 422);

    $fields = [$nome, $categoria_id, $unidade_sigla, $fornecedor_id, $referencia ?: null, $descricao ?: null, $especificacoes ?: null, $ativo];

    if ($id) {
        $stmt = db()->prepare("UPDATE produtos SET nome=?,categoria_id=?,unidade_sigla=?,fornecedor_id=?,referencia=?,descricao=?,especificacoes=?,ativo=? WHERE id=?");
        $stmt->execute([...$fields, $id]);
        jsonResponse(['success' => true, 'message' => 'Produto atualizado com sucesso.']);
    } else {
        $stmt = db()->prepare("INSERT INTO produtos (nome,categoria_id,unidade_sigla,fornecedor_id,referencia,descricao,especificacoes,ativo) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute($fields);
        $newId = db()->lastInsertId();
        // Cria registro de estoque zerado
        $stmt2 = db()->prepare("INSERT IGNORE INTO estoque (produto_id, quantidade) VALUES (?, 0)");
        $stmt2->execute([$newId]);
        jsonResponse(['success' => true, 'message' => 'Produto criado com sucesso.', 'id' => $newId]);
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM produtos WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Produto excluído com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: produto em uso no sistema.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
