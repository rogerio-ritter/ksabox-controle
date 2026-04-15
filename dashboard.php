<?php
/**
 * dashboard.php — Painel principal (Fase 9: Dashboard Completo)
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pageTitle = 'Dashboard';

try {
    $pdo = db();

    /* ── Contadores gerais ─────────────────────────────────── */
    $clientes   = (int) $pdo->query("SELECT COUNT(*) FROM clientes  WHERE ativo = 1")->fetchColumn();
    $produtos   = (int) $pdo->query("SELECT COUNT(*) FROM produtos  WHERE ativo = 1")->fetchColumn();
    $totalOrc   = (int) $pdo->query("SELECT COUNT(*) FROM orcamentos")->fetchColumn();

    /* ── Orçamentos — financeiro ───────────────────────────── */
    $totalAprovado = (float) $pdo->query(
        "SELECT COALESCE(SUM(total_geral),0) FROM orcamentos WHERE status='Aprovado'"
    )->fetchColumn();

    $orcMes = $pdo->query(
        "SELECT COUNT(*) AS qtd, COALESCE(SUM(total_geral),0) AS valor
         FROM orcamentos
         WHERE DATE_FORMAT(data_criacao,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')"
    )->fetch();

    $ticketMedio = $pdo->query(
        "SELECT COALESCE(AVG(total_geral),0) FROM orcamentos WHERE status='Aprovado'"
    )->fetchColumn();

    /* ── Estoque ──────────────────────────────────────────── */
    $estoqueRow = $pdo->query(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN e.quantidade > 0 THEN 1 ELSE 0 END) AS com_saldo,
                COALESCE(SUM(e.quantidade * cp.valor_unitario),0) AS valor_total
         FROM produtos p
         LEFT JOIN estoque e ON e.produto_id = p.id
         LEFT JOIN custo_produtos cp ON cp.produto_id = p.id
         WHERE p.ativo = 1"
    )->fetch();

    /* ── Alertas ──────────────────────────────────────────── */
    // Orçamentos enviados sem resposta (aguardando)
    $orcAguardando = (int) $pdo->query(
        "SELECT COUNT(*) FROM orcamentos WHERE status = 'Enviado'"
    )->fetchColumn();

    // Orçamentos vencendo nos próximos 7 dias (status não terminal)
    $orcVencendo = $pdo->query(
        "SELECT o.id, o.numero, c.nome AS cliente_nome, o.validade, o.total_geral
         FROM orcamentos o
         JOIN clientes c ON c.id = o.cliente_id
         WHERE o.status NOT IN ('Aprovado','Rejeitado','Cancelado')
           AND o.validade IS NOT NULL
           AND o.validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         ORDER BY o.validade ASC
         LIMIT 5"
    )->fetchAll();

    // Produtos sem estoque (zerado ou negativo)
    $prodSemEstoque = $pdo->query(
        "SELECT p.nome, COALESCE(e.quantidade, 0) AS saldo
         FROM produtos p
         LEFT JOIN estoque e ON e.produto_id = p.id
         WHERE p.ativo = 1 AND COALESCE(e.quantidade, 0) <= 0
         ORDER BY p.nome LIMIT 5"
    )->fetchAll();
    $totalSemEstoque = (int) $pdo->query(
        "SELECT COUNT(*) FROM produtos p
         LEFT JOIN estoque e ON e.produto_id = p.id
         WHERE p.ativo = 1 AND COALESCE(e.quantidade, 0) <= 0"
    )->fetchColumn();

    /* ── Últimos 10 orçamentos ────────────────────────────── */
    $ultimosOrcamentos = $pdo->query(
        "SELECT o.id, o.numero, c.nome AS cliente_nome,
                o.data_criacao, o.total_geral, o.status, o.validade
         FROM orcamentos o
         JOIN clientes c ON c.id = o.cliente_id
         ORDER BY o.created_at DESC LIMIT 10"
    )->fetchAll();

    /* ── Dados para gráfico de linha (6 meses) ────────────── */
    $meses = []; $valoresMeses = []; $contagensMeses = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts   = strtotime("-$i months");
        $meses[]    = date('M/y', $ts);
        $ym         = date('Y-m', $ts);
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS qtd, COALESCE(SUM(total_geral),0) AS valor
             FROM orcamentos WHERE DATE_FORMAT(data_criacao,'%Y-%m')=?"
        );
        $stmt->execute([$ym]);
        $row = $stmt->fetch();
        $contagensMeses[] = (int)   $row['qtd'];
        $valoresMeses[]   = (float) $row['valor'];
    }

    /* ── Dados para gráfico de rosca (status) ─────────────── */
    $statusLabels = ['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'];
    $statusCounts = [];
    foreach ($statusLabels as $s) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE status=?");
        $stmt->execute([$s]);
        $statusCounts[] = (int) $stmt->fetchColumn();
    }

    /* ── Gráfico de barras — estoque por categoria ─────────── */
    $estoqueCateg = $pdo->query(
        "SELECT c.nome AS categoria,
                COUNT(DISTINCT p.id) AS total,
                SUM(CASE WHEN COALESCE(e.quantidade,0) > 0 THEN 1 ELSE 0 END) AS com_saldo
         FROM categorias c
         JOIN produtos p ON p.categoria_id = c.id AND p.ativo = 1
         LEFT JOIN estoque e ON e.produto_id = p.id
         WHERE c.ativo = 1
         GROUP BY c.id, c.nome
         ORDER BY total DESC LIMIT 6"
    )->fetchAll();

    $dbOk = true;
} catch (Exception $e) {
    $clientes = $produtos = $totalOrc = 0;
    $totalAprovado = $ticketMedio = 0;
    $orcMes = ['qtd' => 0, 'valor' => 0];
    $estoqueRow = ['total' => 0, 'com_saldo' => 0, 'valor_total' => 0];
    $orcAguardando = 0;
    $orcVencendo = $prodSemEstoque = $ultimosOrcamentos = [];
    $totalSemEstoque = 0;
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun'];
    $valoresMeses = $contagensMeses = [0,0,0,0,0,0];
    $statusCounts = [0,0,0,0,0];
    $estoqueCateg = [];
    $dbOk = false;
}

require_once __DIR__ . '/layout/header.php';
?>

<?php if (!$dbOk): ?>
<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-6 flex items-center gap-3 text-sm text-red-700 dark:text-red-300">
    <i class="fas fa-exclamation-triangle"></i>
    Erro ao conectar ao banco de dados. Verifique as configurações em <code>.env</code>.
</div>
<?php endif; ?>

<!-- ── Métricas principais (linha 1) ──────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-users text-blue-600 dark:text-blue-400 text-lg"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= number_format($clientes) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Clientes ativos</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-boxes text-indigo-600 dark:text-indigo-400 text-lg"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= number_format($produtos) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Produtos ativos</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-file-invoice text-emerald-600 dark:text-emerald-400 text-lg"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= number_format($totalOrc) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Orçamentos totais</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
        </div>
        <div>
            <p class="text-lg font-bold text-green-700 dark:text-green-400"><?= moneyBr($totalAprovado) ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total aprovado</p>
        </div>
    </div>
</div>

<!-- ── Métricas secundárias (linha 2) ─────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Orçamentos este mês</p>
            <i class="fas fa-calendar-alt text-gray-300 dark:text-gray-600 text-sm"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $orcMes['qtd'] ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= moneyBr($orcMes['valor']) ?></p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ticket Médio</p>
            <i class="fas fa-chart-bar text-gray-300 dark:text-gray-600 text-sm"></i>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white"><?= moneyBr($ticketMedio) ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">por orçamento aprovado</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Estoque</p>
            <i class="fas fa-warehouse text-gray-300 dark:text-gray-600 text-sm"></i>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $estoqueRow['com_saldo'] ?><span class="text-sm font-normal text-gray-400"> / <?= $estoqueRow['total'] ?></span></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">produtos com saldo</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor em Estoque</p>
            <i class="fas fa-cubes text-gray-300 dark:text-gray-600 text-sm"></i>
        </div>
        <p class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= moneyBr($estoqueRow['valor_total']) ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">itens c/ custo calculado</p>
    </div>
</div>

<!-- ── Gráficos ────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Linha — últimos 6 meses -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-500"></i> Orçamentos — Últimos 6 meses
            </h2>
            <div class="flex gap-1 text-xs">
                <button id="btn-chart-valor" onclick="toggleChartMode('valor')"
                    class="px-2 py-1 rounded bg-blue-600 text-white font-medium">Valor</button>
                <button id="btn-chart-qtd" onclick="toggleChartMode('qtd')"
                    class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Qtd</button>
            </div>
        </div>
        <canvas id="chart-linha" height="100"></canvas>
    </div>

    <!-- Rosca — status -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex flex-col">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2 mb-4">
            <i class="fas fa-chart-pie text-purple-500"></i> Status dos Orçamentos
        </h2>
        <div class="flex-1 flex items-center justify-center">
            <canvas id="chart-rosca" style="max-height:220px;"></canvas>
        </div>
    </div>
</div>

<!-- ── Alertas e atenção ───────────────────────────────────────── -->
<?php
$temAlerta = $orcAguardando > 0 || count($orcVencendo) > 0 || $totalSemEstoque > 0;
if ($temAlerta):
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <?php if ($orcAguardando > 0): ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-paper-plane text-blue-500"></i>
            <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Aguardando Resposta</span>
        </div>
        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?= $orcAguardando ?></p>
        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">orçamento(s) enviado(s) sem retorno</p>
        <a href="<?= APP_URL ?>/comercial/orcamentos/index.php"
            class="inline-block mt-3 text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
            Ver orçamentos →
        </a>
    </div>
    <?php endif; ?>

    <?php if (count($orcVencendo) > 0): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-clock text-yellow-500"></i>
            <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">Vencendo em 7 dias</span>
        </div>
        <ul class="space-y-1 mt-1">
        <?php foreach ($orcVencendo as $v): ?>
            <li class="text-xs flex items-center justify-between">
                <a href="<?= APP_URL ?>/comercial/orcamentos/visualizar.php?id=<?= $v['id'] ?>"
                    class="text-yellow-700 dark:text-yellow-300 hover:underline font-medium truncate max-w-[120px]">
                    <?= h($v['numero']) ?>
                </a>
                <span class="text-yellow-600 dark:text-yellow-400 ml-2 flex-shrink-0"><?= dateBr($v['validade']) ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($totalSemEstoque > 0): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-exclamation-triangle text-red-500"></i>
            <span class="text-sm font-semibold text-red-700 dark:text-red-300">Sem Estoque</span>
        </div>
        <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?= $totalSemEstoque ?></p>
        <ul class="mt-1 space-y-0.5">
        <?php foreach ($prodSemEstoque as $ps): ?>
            <li class="text-xs text-red-600 dark:text-red-400 truncate">· <?= h($ps['nome']) ?></li>
        <?php endforeach; ?>
        <?php if ($totalSemEstoque > 5): ?>
            <li class="text-xs text-red-500">+ <?= $totalSemEstoque - 5 ?> outros</li>
        <?php endif; ?>
        </ul>
        <a href="<?= APP_URL ?>/estoque/relatorio/index.php"
            class="inline-block mt-2 text-xs text-red-600 dark:text-red-400 hover:underline font-medium">
            Ver estoque →
        </a>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<!-- ── Últimos orçamentos + atalhos ──────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Últimos 10 orçamentos -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <i class="fas fa-clock text-gray-400"></i> Últimos Orçamentos
            </h2>
            <a href="<?= APP_URL ?>/comercial/orcamentos/index.php"
                class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Ver todos →</a>
        </div>

        <?php if (empty($ultimosOrcamentos)): ?>
            <div class="text-center py-8 text-gray-400 dark:text-gray-600">
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
                        <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Número</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Cliente</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400 text-right">Total</th>
                            <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimosOrcamentos as $orc): ?>
                        <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-3 py-2.5">
                                <a href="<?= APP_URL ?>/comercial/orcamentos/visualizar.php?id=<?= $orc['id'] ?>"
                                   class="text-blue-600 dark:text-blue-400 hover:underline font-medium text-xs">
                                    <?= h($orc['numero']) ?>
                                </a>
                                <p class="text-xs text-gray-400"><?= dateBr($orc['data_criacao']) ?></p>
                            </td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 max-w-[160px] truncate"><?= h($orc['cliente_nome']) ?></td>
                            <td class="px-3 py-2.5 text-right font-medium text-gray-800 dark:text-gray-200 whitespace-nowrap"><?= moneyBr($orc['total_geral']) ?></td>
                            <td class="px-3 py-2.5 text-center">
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

    <!-- Atalhos rápidos -->
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-500"></i> Ações Rápidas
            </h2>
            <div class="space-y-2">
                <a href="<?= APP_URL ?>/comercial/orcamentos/form.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 transition-colors text-sm font-medium">
                    <i class="fas fa-plus-circle w-5 text-center"></i> Novo Orçamento
                </a>
                <a href="<?= APP_URL ?>/estoque/entrada/index.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 text-green-700 dark:text-green-300 transition-colors text-sm font-medium">
                    <i class="fas fa-arrow-down w-5 text-center"></i> Entrada de Estoque
                </a>
                <a href="<?= APP_URL ?>/estoque/saida/index.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/40 text-orange-700 dark:text-orange-300 transition-colors text-sm font-medium">
                    <i class="fas fa-arrow-up w-5 text-center"></i> Saída de Estoque
                </a>
                <a href="<?= APP_URL ?>/cadastros/clientes/index.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 text-purple-700 dark:text-purple-300 transition-colors text-sm font-medium">
                    <i class="fas fa-user-plus w-5 text-center"></i> Novo Cliente
                </a>
                <a href="<?= APP_URL ?>/cadastros/produtos/index.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 transition-colors text-sm font-medium">
                    <i class="fas fa-box w-5 text-center"></i> Novo Produto
                </a>
            </div>
        </div>

        <!-- Mini-gráfico: estoque por categoria -->
        <?php if (!empty($estoqueCateg)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <i class="fas fa-layer-group text-indigo-500"></i> Estoque por Categoria
            </h2>
            <div class="space-y-2">
            <?php foreach ($estoqueCateg as $ec):
                $pct = $ec['total'] > 0 ? round($ec['com_saldo'] / $ec['total'] * 100) : 0;
                $barColor = $pct >= 70 ? 'bg-green-500' : ($pct >= 30 ? 'bg-yellow-500' : 'bg-red-500');
            ?>
                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 dark:text-gray-400 truncate max-w-[140px]"><?= h($ec['categoria']) ?></span>
                        <span class="text-gray-500 dark:text-gray-500 ml-2 flex-shrink-0"><?= $ec['com_saldo'] ?>/<?= $ec['total'] ?></span>
                    </div>
                    <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full <?= $barColor ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
const isDark   = document.documentElement.classList.contains('dark');
const gridClr  = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.05)';
const textClr  = isDark ? '#9ca3af' : '#6b7280';

const valoresMeses   = <?= json_encode($valoresMeses) ?>;
const contagensMeses = <?= json_encode($contagensMeses) ?>;
const meses          = <?= json_encode($meses) ?>;

/* ── Gráfico de linha ────────────────────────────────────────── */
const ctxLinha = document.getElementById('chart-linha').getContext('2d');
const chartLinha = new Chart(ctxLinha, {
    type: 'line',
    data: {
        labels: meses,
        datasets: [{
            label: 'Valor (R$)',
            data: valoresMeses,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,.1)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6',
            tension: .35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label === 'Valor (R$)'
                        ? 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2})
                        : ctx.parsed.y + ' orçamento(s)'
                }
            }
        },
        scales: {
            x: { grid: { color: gridClr }, ticks: { color: textClr } },
            y: {
                grid: { color: gridClr }, ticks: { color: textClr },
                beginAtZero: true,
                ticks: {
                    color: textClr,
                    callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                }
            }
        }
    }
});

let chartMode = 'valor';
function toggleChartMode(mode) {
    chartMode = mode;
    const isValor = mode === 'valor';
    chartLinha.data.datasets[0].data        = isValor ? valoresMeses : contagensMeses;
    chartLinha.data.datasets[0].label       = isValor ? 'Valor (R$)' : 'Quantidade';
    chartLinha.data.datasets[0].borderColor = isValor ? '#3b82f6' : '#8b5cf6';
    chartLinha.data.datasets[0].backgroundColor = isValor ? 'rgba(59,130,246,.1)' : 'rgba(139,92,246,.1)';
    chartLinha.data.datasets[0].pointBackgroundColor = isValor ? '#3b82f6' : '#8b5cf6';
    chartLinha.options.scales.y.ticks.callback = isValor
        ? v => 'R$ ' + v.toLocaleString('pt-BR')
        : v => v;
    chartLinha.update();

    document.getElementById('btn-chart-valor').className = isValor
        ? 'px-2 py-1 rounded bg-blue-600 text-white font-medium text-xs'
        : 'px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs';
    document.getElementById('btn-chart-qtd').className = !isValor
        ? 'px-2 py-1 rounded bg-purple-600 text-white font-medium text-xs'
        : 'px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs';
}

/* ── Gráfico de rosca ────────────────────────────────────────── */
new Chart(document.getElementById('chart-rosca'), {
    type: 'doughnut',
    data: {
        labels: ['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'],
        datasets: [{
            data: <?= json_encode($statusCounts) ?>,
            backgroundColor: ['#9ca3af','#3b82f6','#22c55e','#ef4444','#f59e0b'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: textClr, boxWidth: 11, padding: 8, font: { size: 11 } }
            }
        },
        cutout: '68%'
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
