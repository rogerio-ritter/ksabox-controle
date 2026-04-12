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
            $stmt = $db->prepare('INSERT INTO clientes (nome,email,telefone,cpf_cnpj,cidade,estado,endereco,ativo) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                trim($input['nome']),
                trim($input['email']    ?? ''),
                trim($input['telefone'] ?? ''),
                trim($input['cpf_cnpj'] ?? ''),
                trim($input['cidade']   ?? ''),
                strtoupper(trim($input['estado'] ?? '')),
                trim($input['endereco'] ?? ''),
                (int)($input['ativo']   ?? 1)
            ]);
            jsonResponse(true, 'Cliente criado!', ['id' => $db->lastInsertId()]);

        case 'update':
            if (empty($input['id']) || empty($input['nome'])) jsonResponse(false, 'Dados inválidos.');
            db()->prepare('UPDATE clientes SET nome=?,email=?,telefone=?,cpf_cnpj=?,cidade=?,estado=?,endereco=?,ativo=? WHERE id=?')
               ->execute([
                   trim($input['nome']),
                   trim($input['email']    ?? ''),
                   trim($input['telefone'] ?? ''),
                   trim($input['cpf_cnpj'] ?? ''),
                   trim($input['cidade']   ?? ''),
                   strtoupper(trim($input['estado'] ?? '')),
                   trim($input['endereco'] ?? ''),
                   (int)($input['ativo']   ?? 1),
                   (int)$input['id']
               ]);
            jsonResponse(true, 'Cliente atualizado!');

        case 'delete':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            $count = db()->prepare('SELECT COUNT(*) FROM orcamentos WHERE cliente_id = ?');
            $count->execute([(int)$input['id']]);
            if ($count->fetchColumn() > 0) jsonResponse(false, 'Cliente possui orçamentos vinculados.');
            db()->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Cliente excluído!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro no banco de dados.', null, 500);
}
