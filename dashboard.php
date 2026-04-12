<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/layout/header.php';

$db = db();

$totalClientes  = $db->query('SELECT COUNT(*) FROM clientes WHERE ativo = 1')->fetchColumn();
$totalProdutos  = $db->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1')->fetchColumn();
$totalCategorias= $db->query('SELECT COUNT(*) FROM categorias WHERE ativo = 1')->fetchColumn();
$totalOrcamentos= $db->query('SELECT COUNT(*) FROM orcamentos')->fetchColumn();

$totalGeral = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM orcamentos WHERE status = 'aprovado'")->fetchColumn();

$orcamentosRecentes = $db->query(
    "SELECT o.*, c.nome AS cliente_nome FROM orcamentos o
     LEFT JOIN clientes c ON c.id = o.cliente_id
     ORDER BY o.created_at DESC LIMIT 8"
)->fetchAll();

$statusCount = $db->query(
    "SELECT status, COUNT(*) AS total FROM orcamentos GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$meses = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS qtd, COALESCE(SUM(total),0) AS valor
     FROM orcamentos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes"
)->fetchAll();
?>

<!-- Stats cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">
    <?php
    $cards = [
        ['label' => 'Clientes',   'value' => $totalClientes,   'icon' => 'M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z', 'color' => 'indigo', 'link' => 'clientes/'],
        ['label' => 'Produtos',   'value' => $totalProdutos,   'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'purple', 'link' => 'produtos/'],
        ['label' => 'Categorias', 'value' => $totalCategorias, 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z', 'color' => 'pink', 'link' => 'categorias/'],
        ['label' => 'Orçamentos', 'value' => $totalOrcamentos, 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z', 'color' => 'orange', 'link' => 'orcamentos/'],
        ['label' => 'Aprovados', 'value' => moneyBr($totalGeral), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'color' => 'green', 'link' => 'orcamentos/'],
    ];
    $colorMap = [
        'indigo' => 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400',
        'purple' => 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400',
        'pink'   => 'bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400',
        'orange' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
        'green'  => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
    ];
    foreach ($cards as $card): ?>
    <a href="<?= APP_URL ?>/<?= $card['link'] ?>" class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 <?= $colorMap[$card['color']] ?> rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $card['icon'] ?>"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $card['value'] ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= $card['label'] ?></p>
    </a>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
    <!-- Gráfico de orçamentos por mês -->
    <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Orçamentos nos últimos 6 meses</h2>
        <canvas id="chartMeses" height="80"></canvas>
    </div>

    <!-- Status dos orçamentos -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Status dos Orçamentos</h2>
        <?php
        $statusLabels = ['rascunho' => ['Rascunho','bg-gray-400'], 'enviado' => ['Enviado','bg-blue-500'], 'aprovado' => ['Aprovado','bg-green-500'], 'rejeitado' => ['Rejeitado','bg-red-500'], 'cancelado' => ['Cancelado','bg-gray-500']];
        $total = max(1, array_sum($statusCount));
        foreach ($statusLabels as $key => [$label, $color]): $cnt = $statusCount[$key] ?? 0; $pct = round($cnt/$total*100); ?>
        <div class="mb-3">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400"><?= $label ?></span>
                <span class="font-medium text-gray-800 dark:text-gray-200"><?= $cnt ?></span>
            </div>
            <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full <?= $color ?> rounded-full" style="width:<?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Orçamentos recentes -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Orçamentos Recentes</h2>
        <a href="<?= APP_URL ?>/orcamentos.php" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Ver todos</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Nº</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Cliente</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden md:table-cell">Data</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden lg:table-cell">Validade</th>
                    <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Total</th>
                    <th class="text-center px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($orcamentosRecentes)): ?>
                <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">Nenhum orçamento cadastrado</td></tr>
                <?php else: ?>
                <?php foreach ($orcamentosRecentes as $o):
                    $statusInfo = ['rascunho' => ['Rascunho','text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700'], 'enviado' => ['Enviado','text-blue-700 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30'], 'aprovado' => ['Aprovado','text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30'], 'rejeitado' => ['Rejeitado','text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30'], 'cancelado' => ['Cancelado','text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700']];
                    [$sl, $sc] = $statusInfo[$o['status']] ?? ['?',''];
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-5 py-3.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                        <a href="<?= APP_URL ?>/orcamentos/form.php?id=<?= $o['id'] ?>"><?= h($o['numero']) ?></a>
                    </td>
                    <td class="px-5 py-3.5 text-gray-800 dark:text-gray-200"><?= h($o['cliente_nome'] ?? '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell"><?= dateBr($o['data_criacao']) ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell"><?= dateBr($o['data_validade']) ?></td>
                    <td class="px-5 py-3.5 text-right font-medium text-gray-800 dark:text-gray-200"><?= moneyBr($o['total']) ?></td>
                    <td class="px-5 py-3.5 text-center"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sc ?>"><?= $sl ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const isDarkChart = document.documentElement.classList.contains('dark');
const gridColor   = isDarkChart ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
const textColor   = isDarkChart ? '#94a3b8' : '#64748b';

const mesesData = <?= json_encode($meses) ?>;
const labels = mesesData.map(m => {
    const [y, mn] = m.mes.split('-');
    return new Date(y, mn-1).toLocaleString('pt-BR', {month:'short', year:'2-digit'});
});

new Chart(document.getElementById('chartMeses'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Orçamentos',
            data: mesesData.map(m => m.qtd),
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
