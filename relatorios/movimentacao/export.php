<?php
/**
 * export.php — Relatório de Movimentação de Estoque
 * format=json  → usado pela página interativa
 * format=pdf   → página de impressão
 * format=xls   → download Excel
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$format = trim($_GET['format'] ?? 'json');
$q      = '%' . trim($_GET['q']    ?? '') . '%';
$de     = trim($_GET['de']    ?? '');
$ate    = trim($_GET['ate']   ?? '');
$tipo   = trim($_GET['tipo']  ?? 'todos'); // todos | entrada | saida

/* ── Query ─────────────────────────────────────────────────── */
$sql = "
    SELECT m.id, m.tipo, m.quantidade, m.data_movimentacao, m.referencia, m.observacao, m.created_at,
           p.nome AS produto_nome, p.unidade_sigla,
           u.nome AS usuario_nome
    FROM movimentacao_estoque m
    JOIN produtos  p ON p.id = m.produto_id
    JOIN usuarios  u ON u.id = m.usuario_id
    WHERE (p.nome LIKE ? OR m.referencia LIKE ?)
";
$params = [$q, $q];

if ($tipo === 'entrada' || $tipo === 'saida') {
    $sql .= ' AND m.tipo = ?';
    $params[] = $tipo;
}
if ($de)  { $sql .= ' AND m.data_movimentacao >= ?'; $params[] = $de;  }
if ($ate) { $sql .= ' AND m.data_movimentacao <= ?'; $params[] = $ate; }

$sql .= ' ORDER BY m.data_movimentacao DESC, m.id DESC LIMIT 2000';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* Totalizadores */
$totalEntradas = 0; $qtdEntradas = 0.0;
$totalSaidas   = 0; $qtdSaidas   = 0.0;
foreach ($rows as $r) {
    if ($r['tipo'] === 'entrada') { $totalEntradas++; $qtdEntradas += (float)$r['quantidade']; }
    else                         { $totalSaidas++;   $qtdSaidas   += (float)$r['quantidade']; }
}

/* ── JSON ───────────────────────────────────────────────────── */
if ($format === 'json') {
    jsonResponse([
        'success' => true,
        'data'    => $rows,
        'totais'  => [
            'total_entradas' => $totalEntradas,
            'qtd_entradas'   => $qtdEntradas,
            'total_saidas'   => $totalSaidas,
            'qtd_saidas'     => $qtdSaidas,
            'total_geral'    => count($rows),
        ],
    ]);
}

/* ── XLS ────────────────────────────────────────────────────── */
if ($format === 'xls') {
    $filename = 'relatorio_movimentacao_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";
    echo '<table border="1">';
    echo '<tr style="background:#f0f0f0;font-weight:bold;">';
    echo '<th>Data</th><th>Tipo</th><th>Produto</th><th>Unid.</th>';
    echo '<th>Quantidade</th><th>Referência</th><th>Observação</th><th>Registrado por</th>';
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . dateBr($r['data_movimentacao']) . '</td>';
        echo '<td>' . ucfirst($r['tipo']) . '</td>';
        echo '<td>' . h($r['produto_nome']) . '</td>';
        echo '<td>' . h($r['unidade_sigla']) . '</td>';
        echo '<td>' . number_format((float)$r['quantidade'], 2, ',', '.') . '</td>';
        echo '<td>' . h($r['referencia'] ?? '') . '</td>';
        echo '<td>' . h($r['observacao'] ?? '') . '</td>';
        echo '<td>' . h($r['usuario_nome']) . '</td>';
        echo '</tr>';
    }
    echo '<tr style="font-weight:bold;background:#f0f0f0;">';
    echo '<td colspan="4">Entradas: ' . $totalEntradas . ' reg. / Saídas: ' . $totalSaidas . ' reg.</td>';
    echo '<td>E: +' . number_format($qtdEntradas,2,',','.') . ' / S: -' . number_format($qtdSaidas,2,',','.') . '</td>';
    echo '<td colspan="3"></td>';
    echo '</tr></table>';
    exit;
}

/* ── PDF ────────────────────────────────────────────────────── */
if ($format === 'pdf') {
    $tipoLabel = match($tipo) { 'entrada' => 'Entradas', 'saida' => 'Saídas', default => 'Entradas + Saídas' };
    $periodoLabel = '';
    if ($de && $ate) $periodoLabel = ' · ' . dateBr($de) . ' a ' . dateBr($ate);
    elseif ($de)     $periodoLabel = ' · A partir de ' . dateBr($de);
    elseif ($ate)    $periodoLabel = ' · Até ' . dateBr($ate);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Movimentação</title>
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
        .card-sub   { font-size: 9px; color: #888; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; font-size: 10px; font-weight: 600; }
        td { border: 1px solid #e5e7eb; padding: 4px 8px; }
        tr:nth-child(even) td { background: #fafafa; }
        .r { text-align: right; }
        .c { text-align: center; }
        .badge { border-radius: 9999px; padding: 1px 8px; font-size: 10px; font-weight: 500; }
        .e { background: #dcfce7; color: #166534; }
        .s { background: #ffedd5; color: #9a3412; }
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

    <h1>Relatório de Movimentação de Estoque</h1>
    <p class="sub">
        Gerado em: <?= date('d/m/Y \à\s H:i') ?>
        &nbsp;·&nbsp; <?= h($tipoLabel) ?>
        <?= h($periodoLabel) ?>
    </p>

    <div class="cards">
        <div class="card">
            <div class="card-label">Total de Registros</div>
            <div class="card-value"><?= count($rows) ?></div>
        </div>
        <div class="card">
            <div class="card-label">Entradas</div>
            <div class="card-value" style="color:#16a34a;"><?= $totalEntradas ?></div>
            <div class="card-sub">Qtd total: +<?= number_format($qtdEntradas, 2, ',', '.') ?></div>
        </div>
        <div class="card">
            <div class="card-label">Saídas</div>
            <div class="card-value" style="color:#ea580c;"><?= $totalSaidas ?></div>
            <div class="card-sub">Qtd total: -<?= number_format($qtdSaidas, 2, ',', '.') ?></div>
        </div>
        <div class="card">
            <div class="card-label">Saldo do Período</div>
            <?php $saldoPer = $qtdEntradas - $qtdSaidas; $saldoCor = $saldoPer >= 0 ? '#16a34a' : '#dc2626'; ?>
            <div class="card-value" style="color:<?= $saldoCor ?>; font-size:14px;">
                <?= ($saldoPer >= 0 ? '+' : '') . number_format($saldoPer, 2, ',', '.') ?>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="c">Data</th>
                <th class="c">Tipo</th>
                <th>Produto</th>
                <th class="c">Unid.</th>
                <th class="r">Quantidade</th>
                <th>Referência</th>
                <th>Observação</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="c"><?= dateBr($r['data_movimentacao']) ?></td>
                <td class="c">
                    <span class="badge <?= $r['tipo'] === 'entrada' ? 'e' : 's' ?>">
                        <?= $r['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?>
                    </span>
                </td>
                <td><?= h($r['produto_nome']) ?></td>
                <td class="c" style="font-family:monospace; font-size:10px;"><?= h($r['unidade_sigla']) ?></td>
                <td class="r" style="color:<?= $r['tipo']==='entrada' ? '#16a34a' : '#ea580c' ?>; font-weight:bold;">
                    <?= ($r['tipo']==='entrada' ? '+' : '-') . number_format((float)$r['quantidade'], 2, ',', '.') ?>
                </td>
                <td style="font-family:monospace; font-size:10px;"><?= h($r['referencia'] ?? '—') ?></td>
                <td><?= h($r['observacao'] ?? '—') ?></td>
                <td><?= h($r['usuario_nome']) ?></td>
            </tr>
        <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4" class="r">Totais:</td>
                <td class="r">
                    <span style="color:#16a34a;">+<?= number_format($qtdEntradas,2,',','.') ?></span>
                    / <span style="color:#ea580c;">-<?= number_format($qtdSaidas,2,',','.') ?></span>
                </td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
    exit;
}
