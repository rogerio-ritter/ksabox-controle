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

// ─── get_perfil ──────────────────────────────────────────────────────────────
if ($action === 'get_perfil') {
    $stmt = db()->prepare("SELECT id, nome, email, tema FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    jsonResponse(['success' => true, 'data' => $user ?: []]);
}

// ─── update_perfil ────────────────────────────────────────────────────────────
if ($action === 'update_perfil') {
    $nome  = trim($input['nome']  ?? '');
    $email = trim($input['email'] ?? '');

    if (!$nome)  jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if (!$email) jsonResponse(['success' => false, 'message' => 'E-mail é obrigatório.'], 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['success' => false, 'message' => 'E-mail inválido.'], 422);

    /* Verifica duplicidade de e-mail */
    $check = db()->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $check->execute([$email, $_SESSION['user']['id']]);
    if ($check->fetch())
        jsonResponse(['success' => false, 'message' => 'Este e-mail já está em uso por outro usuário.'], 422);

    $stmt = db()->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $_SESSION['user']['id']]);

    /* Atualiza sessão */
    $_SESSION['user']['nome']  = $nome;
    $_SESSION['user']['email'] = $email;

    jsonResponse(['success' => true, 'message' => 'Perfil atualizado com sucesso.']);
}

// ─── update_senha ─────────────────────────────────────────────────────────────
if ($action === 'update_senha') {
    $senhaAtual   = $input['senha_atual']   ?? '';
    $novaSenha    = $input['nova_senha']    ?? '';
    $confirmaSenha = $input['confirma_senha'] ?? '';

    if (!$senhaAtual || !$novaSenha || !$confirmaSenha)
        jsonResponse(['success' => false, 'message' => 'Preencha todos os campos de senha.'], 422);
    if (strlen($novaSenha) < 6)
        jsonResponse(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.'], 422);
    if ($novaSenha !== $confirmaSenha)
        jsonResponse(['success' => false, 'message' => 'A confirmação não confere com a nova senha.'], 422);

    /* Verifica senha atual */
    $stmt = db()->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($senhaAtual, $hash))
        jsonResponse(['success' => false, 'message' => 'Senha atual incorreta.'], 422);

    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = db()->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->execute([$novoHash, $_SESSION['user']['id']]);

    jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso.']);
}

jsonResponse(['success' => false, 'message' => 'Ação não reconhecida.'], 400);
