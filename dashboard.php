<?php
/**
 * dashboard.php — Painel principal
 * Fase 1: estrutura com placeholders (métricas reais na Fase 9)
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'Dashboard';

// ─── Métricas básicas ────────────────────────────────────────────────────────
try {
    $clientes  = (int) db()->query("SELECT COUNT(*) FROM clientes  WHERE ativo = 1")->fetchColumn();
    $produtos  = (int) db()->query("SELECT COUNT(*) FROM produtos  WHERE ativo = 1")->fetchColumn();
    $categorias = (int) db()->query("SELECT COUNT(*) FROM categorias WHERE ativo = 1")->fetchColumn();
    $totalOrc  = (int) db()->query("SELECT COUNT(*) FROM orcamentos")->fetchColumn();
    $totalAprovado = (float) db()->query(
        "SELECT COALESCE(SUM(total_geral),0) FROM orcamentos WHERE status = 'Aprovado'"
    )->fetchColumn();

    $ultimosOrcamentos = db()->query("
        SELECT o.numero, c.nome AS cliente_nome, o.data_criacao, o.total_geral, o.status
        FROM orcamentos o
        JOIN clientes c ON c.id = o.cliente_id
        ORDER BY o.created_at DESC LIMIT 10
    ")->fetchAll();

    $dbOk = true;
} catch (Exception $e) {
    $clientes = $produtos = $categorias = $totalOrc = 0;
    $totalAprovado = 0;
    $ultimosOrcamentos = [];
    $dbOk = false;
}

require_once __DIR__ . '/layout/header.php';
?>

<!-- ── Cards de métricas ─────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <?php
    $cards = [
        ['label' => 'Clientes Ativos',  'value' => $clientes,   'icon' => 'fas fa-users',       'color' => 'blue'],
        ['label' => 'Produtos Ativos',  'value' => $produtos,   'icon' => 'fas fa-boxes',       'color' => 'indigo'],
        ['label' => 'Categorias',       'value' => $categorias, 'icon' => 'fas fa-tags',        'color' => 'purple'],
        ['label' => 'Total Orçamentos', 'value' => $totalOrc,   'icon' => 'fas fa-file-invoice','color' => 'emerald'],
    ];
    foreach ($cards as $card):
        $c = $card['color'];
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-<?= $c ?>-100 dark:bg-<?= $c ?>-900 flex items-center justify-center flex-shrink-0">
            <i class="<?= $card['icon'] ?> text-<?= $c ?>-600 dark:text-<?= $c ?>-400 text-xl"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= number_format($card['value']) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?= $card['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Card total aprovado -->
<div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl shadow-sm p-5 mb-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-check-circle text-white text-xl"></i>
    </div>
    <div>
        <p class="text-2xl font-bold text-white"><?= moneyBr($totalAprovado) ?></p>
        <p class="text-sm text-green-100">Total em Orçamentos Aprovados</p>
    </div>
</div>

<!-- ── Gráficos ───────────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Gráfico de linha — últimos 6 meses -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
            <i class="fas fa-chart-line text-blue-500 mr-1"></i> Orçamentos — Últimos 6 meses
        </h2>
        <canvas id="chart-linha" height="100"></canvas>
    </div>

    <!-- Gráfico de rosca — status -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
            <i class="fas fa-chart-pie text-purple-500 mr-1"></i> Status dos Orçamentos
        </h2>
        <canvas id="chart-rosca" height="180"></canvas>
    </div>
</div>

<!-- ── Últimos orçamentos ─────────────────────────────────────────────────── -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
        <i class="fas fa-clock text-gray-400 mr-1"></i> Últimos Orçamentos
    </h2>

    <?php if (empty($ultimosOrcamentos)): ?>
        <div class="text-center py-10 text-gray-400 dark:text-gray-600">
            <i class="fas fa-file-invoice text-4xl mb-3 block"></i>
            <p class="text-sm">Nenhum orçamento cadastrado ainda.</p>
            <a href="<?= APP_URL ?>/comercial/orcamentos/form.php"
               class="inline-block mt-3 text-blue-600 dark:text-blue-400 text-sm hover:underline">
                Criar primeiro orçamento →
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Número</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Cliente</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Data</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-gray-400 text-right">Total</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-gray-400 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($ultimosOrcamentos as $orc): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="py-2.5">
                            <a href="<?= APP_URL ?>/comercial/orcamentos/visualizar.php?id=<?= $orc['id'] ?? '' ?>"
                               class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                <?= h($orc['numero']) ?>
                            </a>
                        </td>
                        <td class="py-2.5 text-gray-700 dark:text-gray-300"><?= h($orc['cliente_nome']) ?></td>
                        <td class="py-2.5 text-gray-500 dark:text-gray-400"><?= dateBr($orc['data_criacao']) ?></td>
                        <td class="py-2.5 text-right font-medium text-gray-800 dark:text-gray-200">
                            <?= moneyBr($orc['total_geral']) ?>
                        </td>
                        <td class="py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= badgeOrcamento($orc['status']) ?>">
                                <?= h($orc['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// ─── Dados para gráficos ──────────────────────────────────────────────────────
try {
    // Últimos 6 meses
    $meses = [];
    $totaisMeses = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts    = strtotime("-$i months");
        $label = date('M/y', $ts);
        $ym    = date('Y-m', $ts);
        $meses[] = $label;

        $stmt = db()->prepare("SELECT COALESCE(SUM(total_geral),0) FROM orcamentos WHERE DATE_FORMAT(data_criacao,'%Y-%m') = ?");
        $stmt->execute([$ym]);
        $totaisMeses[] = (float) $stmt->fetchColumn();
    }

    // Status
    $statusLabels = ['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'];
    $statusCounts = [];
    foreach ($statusLabels as $s) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM orcamentos WHERE status = ?");
        $stmt->execute([$s]);
        $statusCounts[] = (int) $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun'];
    $totaisMeses = [0,0,0,0,0,0];
    $statusCounts = [0,0,0,0,0];
}
?>

<script>
const isDarkMode = document.documentElement.classList.contains('dark');
const gridColor  = isDarkMode ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
const textColor  = isDarkMode ? '#9ca3af' : '#6b7280';

// Gráfico de linha
new Chart(document.getElementById('chart-linha'), {
    type: 'line',
    data: {
        labels: <?= json_encode($meses) ?>,
        datasets: [{
            label: 'Total (R$)',
            data: <?= json_encode($totaisMeses) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,.1)',
            borderWidth: 2,
            pointRadius: 4,
            tension: .3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor } },
            y: {
                grid: { color: gridColor }, ticks: { color: textColor },
                callback: v => 'R$ ' + v.toLocaleString('pt-BR')
            }
        }
    }
});

// Gráfico de rosca
new Chart(document.getElementById('chart-rosca'), {
    type: 'doughnut',
    data: {
        labels: ['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'],
        datasets: [{
            data: <?= json_encode($statusCounts) ?>,
            backgroundColor: ['#9ca3af','#3b82f6','#22c55e','#ef4444','#f59e0b'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { color: textColor, boxWidth: 12, padding: 10 } }
        },
        cutout: '65%'
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
