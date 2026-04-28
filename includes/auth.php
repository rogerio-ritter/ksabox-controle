<?php
/**
 * auth.php — Autenticação e controle de sessão
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Exige que o usuário esteja logado.
 * Redireciona para login.php caso contrário.
 */
function requireLogin(): void {
    if (empty($_SESSION['user']['id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Tenta autenticar o usuário com email e senha.
 * Retorna array com dados do usuário ou false em caso de falha.
 */
function login(string $email, string $senha): array|false {
    $stmt = db()->prepare("SELECT id, nome, email, senha, tema, tipo FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        unset($user['senha']);
        $_SESSION['user'] = $user;
        session_regenerate_id(true);
        return $user;
    }

    return false;
}

/**
 * Encerra a sessão do usuário.
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Retorna os dados do usuário logado.
 */
function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

/**
 * Verifica se o usuário está logado.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user']['id']);
}

/**
 * Verifica se o usuário logado é Administrador.
 */
function isAdmin(): bool {
    return ($_SESSION['user']['tipo'] ?? '') === 'administrador';
}

/**
 * Exige que o usuário seja Administrador.
 * Em APIs JSON retorna 403; em páginas redireciona para o dashboard.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $isApi = str_ends_with($_SERVER['SCRIPT_FILENAME'] ?? '', 'api.php')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso restrito a Administradores.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Você não tem permissão para acessar esta página.';
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}
