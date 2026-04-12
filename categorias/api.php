<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$input  = getInput();
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            if (empty($input['nome'])) jsonResponse(false, 'Nome é obrigatório.');
            $db = db();
            $stmt = $db->prepare('INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, ?)');
            $stmt->execute([trim($input['nome']), trim($input['descricao'] ?? ''), (int)($input['ativo'] ?? 1)]);
            jsonResponse(true, 'Categoria criada!', ['id' => $db->lastInsertId()]);

        case 'update':
            if (empty($input['id']) || empty($input['nome'])) jsonResponse(false, 'Dados inválidos.');
            db()->prepare('UPDATE categorias SET nome=?, descricao=?, ativo=? WHERE id=?')
               ->execute([trim($input['nome']), trim($input['descricao'] ?? ''), (int)($input['ativo'] ?? 1), (int)$input['id']]);
            jsonResponse(true, 'Categoria atualizada!');

        case 'delete':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            $count = db()->prepare('SELECT COUNT(*) FROM produtos WHERE categoria_id = ?');
            $count->execute([(int)$input['id']]);
            if ($count->fetchColumn() > 0) jsonResponse(false, 'Categoria possui produtos vinculados.');
            db()->prepare('DELETE FROM categorias WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Categoria excluída!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro no banco de dados.', null, 500);
}
