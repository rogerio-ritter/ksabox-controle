<?php
/**
 * export.php — Relatório de Estoque
 * format=json  → usado pela página interativa
 * format=pdf   → página de impressão (abre nova aba)
 * format=xls   → download Excel (HTML table trick)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$format = trim($_GET['format'] ?? 'json'); // json | pdf | xls
$q      = '%' . trim($_GET['q']    ?? '') . '%';
$cat    = (int)($_GET['cat']   ?? 0);
$filtro = trim($_GET['filtro'] ?? 'todos'); // todos | com_saldo | sem_saldo

/* ── Query ─────────────────────────────────────────────────── */
$sql = "
    SELECT p.id, p.nome AS produto_nome, p.unidade_sigla,
           c.nome AS categoria_nome,
           COALESCE(e.quantidade, 0) AS saldo,
           cp.valor_unitario AS custo_unitario,
           CASE WHEN cp.valor_unitario IS NOT NULL
                THEN COALESCE(e.quantidade, 0) * cp.valor_unitario
                ELSE NULL END AS valor_estoque,
           e.updated_at
    FROM produtos p
    LEFT JOIN categorias c  ON c.id = p.categoria_id
    LEFT JOIN estoque e     ON e.produto_id = p.id
    LEFT JOIN custo_produtos cp ON cp.produto_id = p.id
    WHERE p.ativo = 1
      AND (p.nome LIKE ? OR c.nome LIKE ?)
";
$params = [$q, $q];

if ($cat) { $sql .= ' AND p.categoria_id = ?'; $params[] = $cat; }
if ($filtro === 'com_saldo') $sql .= ' AND COALESCE(e.quantidade, 0) > 0';
if ($filtro === 'sem_saldo') $sql .= ' AND COALESCE(e.quantidade, 0) <= 0';
$sql .= ' ORDER BY p.nome';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* Totalizadores */
$totalItens    = count($rows);
$totalComSaldo = 0;
$valorTotal    = 0.0;
foreach ($rows as $r) {
    if ((float)$r['saldo'] > 0) $totalComSaldo++;
    if ($r['valor_estoque'] !== null) $valorTotal += (float)$r['valor_estoque'];
}

/* ── JSON ───────────────────────────────────────────────────── */
if ($format === 'json') {
    /* Categorias para o filtro select */
    $categorias = db()->query(
        "SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY nome"
    )->fetchAll();

    jsonResponse([
        'success'    => true,
        'data'       => $rows,
        'categorias' => $categorias,
        'totais'     => [
            'total_itens' => $totalItens,
            'com_saldo'   => $totalComSaldo,
            'sem_saldo'   => $totalItens - $totalComSaldo,
            'valor_total' => $valorTotal,
        ],
    ]);
}

/* ── XLS ────────────────────────────────────────────────────── */
if ($format === 'xls') {
    $filename = 'relatorio_estoque_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo '<table border="1">';
    echo '<tr style="background:#f0f0f0;font-weight:bold;">';
    echo '<th>Produto</th><th>Categoria</th><th>Unid.</th>';
    echo '<th>Saldo Atual</th><th>Custo Unit.</th><th>Valor Estoque</th><th>Status</th>';
    echo '</tr>';
    foreach ($rows as $r) {
        $s = (float)$r['saldo'];
        $status = $s > 0 ? 'Em estoque' : ($s == 0 ? 'Zerado' : 'Negativo');
        echo '<tr>';
        echo '<td>' . h($r['produto_nome']) . '</td>';
        echo '<td>' . h($r['categoria_nome'] ?? '—') . '</td>';
        echo '<td>' . h($r['unidade_sigla']) . '</td>';
        echo '<td>' . number_format($s, 2, ',', '.') . '</td>';
        echo '<td>' . ($r['custo_unitario'] !== null ? moneyBr($r['custo_unitario']) : '—') . '</td>';
        echo '<td>' . ($r['valor_estoque']  !== null ? moneyBr($r['valor_estoque'])  : '—') . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    echo '<tr style="font-weight:bold;background:#f0f0f0;">';
    echo '<td colspan="5">Total (itens com custo calculado)</td>';
    echo '<td>' . moneyBr($valorTotal) . '</td><td></td>';
    echo '</tr></table>';
    exit;
}

/* ── PDF ────────────────────────────────────────────────────── */
if ($format === 'pdf') {
    $catNome = '';
    if ($cat) {
        $st = db()->prepare("SELECT nome FROM categorias WHERE id=?");
        $st->execute([$cat]);
        $catNome = $st->fetchColumn() ?: '';
    }
    $filtroLabel = match($filtro) {
        'com_saldo' => 'Somente com saldo',
        'sem_saldo' => 'Somente sem saldo',
        default     => '',
    };
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .action-bar { margin-bottom: 14px; display: flex; gap: 8px; }
        .btn-print { background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .btn-close  { background: #e5e7eb; color: #374151; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        h1 { font-size: 17px; margin-bottom: 3px; }
        .sub { font-size: 10px; color: #666; margin-bottom: 14px; }
        .cards { display: flex; gap: 10px; margin-bottom: 14px; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 8px 12px; flex: 1; }
        .card-label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: .05em; }
        .card-value { font-size: 17px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; font-size: 10px; font-weight: 600; }
        td { border: 1px solid #e5e7eb; padding: 4px 8px; }
        tr:nth-child(even) td { background: #fafafa; }
        .r { text-align: right; }
        .c { text-align: center; }
        .badge { border-radius: 9999px; padding: 1px 8px; font-size: 10px; font-weight: 500; }
        .g  { background: #dcfce7; color: #166534; }
        .y  { background: #fef9c3; color: #854d0e; }
        .rd { background: #fee2e2; color: #991b1b; }
        .total-row td { font-weight: bold; background: #f3f4f6; border-top: 2px solid #9ca3af; }
        @media print {
            .action-bar { display: none !important; }
            body { padding: 0; }
            @page { size: A4 landscape; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="action-bar">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
        <button class="btn-close"  onclick="window.close()">✕ Fechar</button>
    </div>

    <h1>Relatório de Estoque — Saldo Atual</h1>
    <p class="sub">
        Gerado em: <?= date('d/m/Y \à\s H:i') ?>
        <?= $catNome    ? ' &nbsp;·&nbsp; Categoria: ' . h($catNome)    : '' ?>
        <?= $filtroLabel? ' &nbsp;·&nbsp; Filtro: '   . h($filtroLabel) : '' ?>
    </p>

    <div class="cards">
        <div class="card">
            <div class="card-label">Total de Produtos</div>
            <div class="card-value"><?= $totalItens ?></div>
        </div>
        <div class="card">
            <div class="card-label">Com Saldo</div>
            <div class="card-value" style="color:#16a34a;"><?= $totalComSaldo ?></div>
        </div>
        <div class="card">
            <div class="card-label">Sem Saldo</div>
            <div class="card-value" style="color:#dc2626;"><?= $totalItens - $totalComSaldo ?></div>
        </div>
        <div class="card">
            <div class="card-label">Valor Total em Estoque</div>
            <div class="card-value" style="color:#2563eb; font-size:14px;"><?= moneyBr($valorTotal) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th class="c">Unid.</th>
                <th class="r">Saldo Atual</th>
                <th class="r">Custo Unit.</th>
                <th class="r">Valor Estoque</th>
                <th class="c">Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $s = (float)$r['saldo'];
            [$bc, $bt] = $s > 0 ? ['g','Em estoque'] : ($s == 0 ? ['y','Zerado'] : ['rd','Negativo']);
        ?>
            <tr>
                <td><?= h($r['produto_nome']) ?></td>
                <td><?= h($r['categoria_nome'] ?? '—') ?></td>
                <td class="c" style="font-family:monospace; font-size:10px;"><?= h($r['unidade_sigla']) ?></td>
                <td class="r"><?= number_format($s, 2, ',', '.') ?></td>
                <td class="r"><?= $r['custo_unitario'] !== null ? moneyBr($r['custo_unitario']) : '<span style="color:#9ca3af">sem custo</span>' ?></td>
                <td class="r"><?= $r['valor_estoque']  !== null ? moneyBr($r['valor_estoque'])  : '<span style="color:#9ca3af">—</span>' ?></td>
                <td class="c"><span class="badge <?= $bc ?>"><?= $bt ?></span></td>
            </tr>
        <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="5" class="r">Total (itens com custo calculado):</td>
                <td class="r"><?= moneyBr($valorTotal) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
    exit;
}
