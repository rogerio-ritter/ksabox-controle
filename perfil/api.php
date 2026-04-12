<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$input  = getInput();
$action = $input['action'] ?? '';
$user   = currentUser();

try {
    switch ($action) {
        case 'update_tema':
            $tema = in_array($input['tema'] ?? '', ['claro', 'escuro']) ? $input['tema'] : 'claro';
            db()->prepare('UPDATE usuarios SET tema=? WHERE id=?')->execute([$tema, $user['id']]);
            $_SESSION['tema'] = $tema;
            jsonResponse(true, 'Tema atualizado!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro.', null, 500);
}
