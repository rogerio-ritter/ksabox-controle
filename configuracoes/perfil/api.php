<?php
/**
 * configuracoes/perfil/api.php
 * Endpoint de perfil do usuário.
 * action=toggle_tema: alterna claro/escuro na sessão e no banco.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$input  = getInput();
$action = $input['action'] ?? ($_GET['action'] ?? '');

// ─── toggle_tema ─────────────────────────────────────────────────────────────
if ($action === 'toggle_tema') {
    $atual  = $_SESSION['user']['tema'] ?? 'claro';
    $novo   = ($atual === 'claro') ? 'escuro' : 'claro';

    // Atualiza sessão imediatamente
    $_SESSION['user']['tema'] = $novo;

    // Persiste no banco
    $stmt = db()->prepare("UPDATE usuarios SET tema = ? WHERE id = ?");
    $stmt->execute([$novo, $_SESSION['user']['id']]);

    jsonResponse(['success' => true, 'tema' => $novo]);
}

jsonResponse(['success' => false, 'message' => 'Ação não reconhecida.'], 400);
