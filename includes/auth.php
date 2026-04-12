<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function login(string $email, string $senha): bool {
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id']   = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_email']= $user['email'];
        $_SESSION['tema']         = $user['tema'];
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['usuario_id']    ?? 0,
        'nome'  => $_SESSION['usuario_nome']  ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
        'tema'  => $_SESSION['tema']          ?? 'claro',
    ];
}

function isDark(): bool {
    return ($_SESSION['tema'] ?? 'claro') === 'escuro';
}
