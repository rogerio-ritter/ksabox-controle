<?php
/**
 * login.php — Página de autenticação
 */

require_once __DIR__ . '/includes/auth.php';

// Já logado? Redirecionar para dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha o e-mail e a senha.';
    } else {
        $user = login($email, $senha);
        if ($user) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Ksabox</title>
    <link rel="icon" type="image/x-icon" href="<?= APP_URL; ?>/assets/img/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-600 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">

        <!-- Logo / Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-700 rounded-full mb-4">
                <img src="<?= APP_URL; ?>/assets/img/simbolo_ksabox.png" />
                <!-- <i class="fas fa-box-open text-blue-600 text-3xl"></i> -->
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Ksabox</h1>
            <p class="text-gray-500 text-sm mt-1">Sistema de Gestão</p>
        </div>

        <!-- Erro -->
        <?php if ($erro): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($erro) ?>
        </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form method="POST" action="" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="email">
                    E-mail
                </label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autofocus
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="seu@email.com"
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               transition-colors">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="senha">
                    Senha
                </label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        required
                        placeholder="••••••••"
                        class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               transition-colors">
                    <button type="button" id="btn-show-pass"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="icon-pass"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg
                           transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                Entrar
            </button>
        </form>

    </div>

    <p class="text-center text-blue-200 text-xs mt-6">
        &copy; <?= date('Y') ?> Ksabox — Arquitetura Inteligente
    </p>
</div>

<script>
document.getElementById('btn-show-pass').addEventListener('click', function() {
    const input = document.getElementById('senha');
    const icon  = document.getElementById('icon-pass');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
});
</script>
</body>
</html>
