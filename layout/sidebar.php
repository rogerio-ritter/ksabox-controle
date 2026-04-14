<?php
/**
 * sidebar.php — Menu lateral de navegação
 */

$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

function sidebarLink(string $href, string $icon, string $label, string $currentUrl): string {
    $active = str_contains($currentUrl, parse_url($href, PHP_URL_PATH))
        ? 'bg-blue-700 text-white'
        : 'text-gray-300 hover:bg-gray-700 hover:text-white';
    return sprintf(
        '<a href="%s" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium %s transition-colors">
            <i class="%s w-5 text-center"></i>
            <span class="sidebar-label">%s</span>
        </a>',
        $href, $active, $icon, $label
    );
}
?>

<aside id="sidebar" class="w-64 bg-gray-900 flex flex-col flex-shrink-0 overflow-y-auto
    fixed inset-y-0 left-0 z-40 transform -translate-x-full transition-transform duration-200
    lg:relative lg:translate-x-0">

    <!-- Logo -->
    <div class="h-14 flex items-center justify-center px-4 bg-gray-950 flex-shrink-0">
      <!--  <span class="text-white font-bold text-xl tracking-wide">
            <i class="fas fa-box-open text-blue-400 mr-2"></i>Ksabox
        </span> -->
        <a href="<?= APP_URL ?>./dashboard.php" >
            <img width="310" height='81'  src="<?= APP_URL; ?>/assets/img/logo.png" /> 
        </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">

        <!-- Dashboard -->
        <?= sidebarLink(APP_URL . '/dashboard.php', 'fas fa-tachometer-alt', 'Dashboard', $currentUrl) ?>

        <!-- CADASTROS -->
        <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Cadastros</p>
        <?= sidebarLink(APP_URL . '/cadastros/categorias/index.php',   'fas fa-tags',          'Categorias',      $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/unidades/index.php',     'fas fa-ruler',         'Unidades',        $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/fornecedores/index.php', 'fas fa-truck',         'Fornecedores',    $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/clientes/index.php',     'fas fa-users',         'Clientes',        $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/tabela_precos/index.php','fas fa-percent',       'Tabelas de Preço',$currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/produtos/index.php',     'fas fa-boxes',         'Produtos',        $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/cadastros/usuarios/index.php',     'fas fa-user-shield',   'Usuários',        $currentUrl) ?>

        <!-- COMERCIAL -->
        <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Comercial</p>
        <?= sidebarLink(APP_URL . '/comercial/custo/index.php',           'fas fa-dollar-sign',  'Custo de Produto', $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/comercial/formacao_preco/index.php',  'fas fa-chart-line',   'Formação de Preço',$currentUrl) ?>
        <?= sidebarLink(APP_URL . '/comercial/orcamentos/index.php',      'fas fa-file-invoice', 'Orçamentos',       $currentUrl) ?>

        <!-- ESTOQUE -->
        <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Estoque</p>
        <?= sidebarLink(APP_URL . '/estoque/entrada/index.php',  'fas fa-arrow-down', 'Entrada',       $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/estoque/saida/index.php',    'fas fa-arrow-up',   'Saída',         $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/estoque/relatorio/index.php','fas fa-warehouse',  'Saldo Estoque', $currentUrl) ?>

        <!-- RELATÓRIOS -->
        <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Relatórios</p>
        <?= sidebarLink(APP_URL . '/relatorios/estoque/index.php',       'fas fa-cubes',      'Estoque',        $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/relatorios/movimentacao/index.php',  'fas fa-exchange-alt','Movimentação',  $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/relatorios/tabela_precos/index.php', 'fas fa-list-alt',   'Tabela de Preços',$currentUrl) ?>

        <!-- CONFIGURAÇÕES -->
        <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Configurações</p>
        <?= sidebarLink(APP_URL . '/configuracoes/empresa/index.php', 'fas fa-building', 'Empresa', $currentUrl) ?>
        <?= sidebarLink(APP_URL . '/configuracoes/perfil/index.php',  'fas fa-user-cog', 'Meu Perfil', $currentUrl) ?>

    </nav>

    <div class="px-3 py-3 border-t border-gray-800 text-xs text-gray-600 text-center">
        v1.0 &copy; <?= date('Y') ?> Ksabox
    </div>
</aside>

<!-- Overlay para fechar sidebar no mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
