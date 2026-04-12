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
            $stmt = $db->prepare('INSERT INTO produtos (nome, categoria_id, unidade, descricao, ativo) VALUES (?,?,?,?,?)');
            $stmt->execute([
                trim($input['nome']),
                $input['categoria_id'] ?: null,
                trim($input['unidade'] ?? 'un'),
                trim($input['descricao'] ?? ''),
                (int)($input['ativo'] ?? 1)
            ]);
            jsonResponse(true, 'Produto criado!', ['id' => $db->lastInsertId()]);

        case 'update':
            if (empty($input['id']) || empty($input['nome'])) jsonResponse(false, 'Dados inválidos.');
            db()->prepare('UPDATE produtos SET nome=?, categoria_id=?, unidade=?, descricao=?, ativo=? WHERE id=?')
               ->execute([
                   trim($input['nome']),
                   $input['categoria_id'] ?: null,
                   trim($input['unidade'] ?? 'un'),
                   trim($input['descricao'] ?? ''),
                   (int)($input['ativo'] ?? 1),
                   (int)$input['id']
               ]);
            jsonResponse(true, 'Produto atualizado!');

        case 'delete':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            db()->prepare('DELETE FROM produtos WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Produto excluído!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro no banco de dados.', null, 500);
}
