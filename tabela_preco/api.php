<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$input  = getInput();
$action = $input['action'] ?? ($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'create':
            if (empty($input['nome'])) jsonResponse(false, 'Nome é obrigatório.');
            $db = db();
            $db->prepare('INSERT INTO tabelas_precos (nome, descricao, ativo) VALUES (?,?,?)')
               ->execute([trim($input['nome']), trim($input['descricao'] ?? ''), (int)($input['ativo'] ?? 1)]);
            jsonResponse(true, 'Tabela criada!', ['id' => $db->lastInsertId()]);

        case 'update':
            if (empty($input['id']) || empty($input['nome'])) jsonResponse(false, 'Dados inválidos.');
            db()->prepare('UPDATE tabelas_precos SET nome=?,descricao=?,ativo=? WHERE id=?')
               ->execute([trim($input['nome']), trim($input['descricao'] ?? ''), (int)($input['ativo'] ?? 1), (int)$input['id']]);
            jsonResponse(true, 'Tabela atualizada!');

        case 'delete':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            db()->prepare('DELETE FROM tabelas_precos WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Tabela excluída!');

        case 'itens':
            $tabId = (int)($_GET['tabela_id'] ?? $input['tabela_id'] ?? 0);
            if (!$tabId) jsonResponse(false, 'tabela_id inválido.');
            $stmt = db()->prepare(
                'SELECT tpi.*, p.nome AS produto_nome, p.unidade FROM tabela_preco_itens tpi
                 JOIN produtos p ON p.id = tpi.produto_id WHERE tpi.tabela_id = ? ORDER BY p.nome'
            );
            $stmt->execute([$tabId]);
            jsonResponse(true, '', $stmt->fetchAll());

        case 'add_item':
            if (empty($input['tabela_id']) || empty($input['produto_id'])) jsonResponse(false, 'Dados inválidos.');
            $db   = db();
            $stmt = $db->prepare('SELECT id FROM tabela_preco_itens WHERE tabela_id=? AND produto_id=?');
            $stmt->execute([(int)$input['tabela_id'], (int)$input['produto_id']]);
            $exists = $stmt->fetchColumn();
            if ($exists) {
                $db->prepare('UPDATE tabela_preco_itens SET preco=? WHERE id=?')->execute([(float)$input['preco'], $exists]);
            } else {
                $db->prepare('INSERT INTO tabela_preco_itens (tabela_id, produto_id, preco) VALUES (?,?,?)')
                   ->execute([(int)$input['tabela_id'], (int)$input['produto_id'], (float)$input['preco']]);
            }
            jsonResponse(true, 'Item adicionado!');

        case 'update_item':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            db()->prepare('UPDATE tabela_preco_itens SET preco=? WHERE id=?')->execute([(float)$input['preco'], (int)$input['id']]);
            jsonResponse(true, 'Preço atualizado!');

        case 'delete_item':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            db()->prepare('DELETE FROM tabela_preco_itens WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Item removido!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro no banco de dados.', null, 500);
}
