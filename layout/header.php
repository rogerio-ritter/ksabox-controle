<?php
/**
 * header.php — <head>, CDNs, navbar superior
 * Variável esperada: $pageTitle (string)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$dark = isDark();
$darkClass = $dark ? 'dark' : '';
$user = $_SESSION['user'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $darkClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= h($pageTitle) ?> — Ksabox</title>
    <link rel="icon" type="image/x-icon" href="<?= APP_URL; ?>/assets/img/favicon.svg">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#1d4ed8', dark: '#1e40af' }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Scrollbar sidebar */
        #sidebar { scrollbar-width: thin; scrollbar-color: #6b7280 transparent; }
        /* Transição de tema */
        * { transition: background-color .15s, color .15s, border-color .15s; }
        /* Toast */
        #toast-container { z-index: 9999; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen flex">

<!-- ===== SIDEBAR ===== -->
<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- ===== ÁREA PRINCIPAL ===== -->
<div class="flex-1 flex flex-col min-w-0">

    <!-- Navbar superior -->
    <header class="bg-white dark:bg-gray-800 shadow-sm h-14 flex items-center justify-between px-4 flex-shrink-0">

        <!-- Botão hambúrguer (mobile) -->
        <button id="btn-toggle-sidebar" class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-white mr-3 lg:hidden">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <h1 class="text-base font-semibold text-gray-700 dark:text-gray-200 truncate"><?= h($pageTitle) ?></h1>

        <div class="flex items-center gap-3">
            <!-- Toggle tema -->
            <button id="btn-toggle-tema" title="Alternar tema" class="text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                <i class="fas <?= $dark ? 'fa-sun' : 'fa-moon' ?> text-lg"></i>
            </button>

            <!-- Menu usuário -->
            <div class="relative" id="user-menu-wrapper">
                <button id="btn-user-menu" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-user-circle text-xl"></i>
                    <span class="hidden sm:inline"><?= h($user['nome'] ?? 'Usuário') ?></span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 text-sm z-50">
                    <a href="<?= APP_URL ?>/configuracoes/perfil/index.php" class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-user-cog w-4"></i> Meu Perfil
                    </a>
                    <hr class="my-1 border-gray-200 dark:border-gray-700">
                    <a href="<?= APP_URL ?>/logout.php" class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 dark:text-red-400">
                        <i class="fas fa-sign-out-alt w-4"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo da página -->
    <main class="flex-1 p-4 lg:p-6 overflow-auto">
<!-- (fechado no footer.php) -->
