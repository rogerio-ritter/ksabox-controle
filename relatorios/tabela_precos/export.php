<?php
/**
 * export.php — Relatório de Tabela de Preços
 * Mostra preços finais (valor_venda × multiplicador) por produto e tabela.
 *
 * format=json  → usado pela página interativa
 * format=pdf   → página de impressão
 * format=xls   → download Excel
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$format   = trim($_GET['format']   ?? 'json');
$q        = '%' . trim($_GET['q']        ?? '') . '%';
$cat      = (int)($_GET['cat']      ?? 0);
$tabelaId = (int)($_GET['tabela_id'] ?? 0);

/* ── Tabelas ativas ──────────────────────────────────────────── */
$tabelas = db()->query(
    "SELECT id, nome, multiplicador FROM tabela_precos WHERE ativo = 1 ORDER BY nome"
)->fetchAll();

/* ── Produtos com formação de preço ─────────────────────────── */
$sql = "
    SELECT fp.produto_id, p.nome AS produto_nome, p.unidade_sigla,
           c.nome AS categoria_nome,
           fp.valor_venda, fp.perc_margem_liquida, fp.perc_material, fp.perc_servico,
           cp.valor_unitario AS custo_unitario
    FROM formacao_precos fp
    JOIN produtos p         ON p.id  = fp.produto_id
    LEFT JOIN categorias c  ON c.id  = p.categoria_id
    LEFT JOIN custo_produtos cp ON cp.produto_id = fp.produto_id
    WHERE p.ativo = 1
      AND (p.nome LIKE ? OR c.nome LIKE ?)
";
$params = [$q, $q];
if ($cat) { $sql .= ' AND p.categoria_id = ?'; $params[] = $cat; }
$sql .= ' ORDER BY p.nome';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

/* Para cada produto, calcula o preço por tabela */
foreach ($produtos as &$p) {
    $p['precos'] = [];
    foreach ($tabelas as $t) {
        $p['precos'][$t['id']] = round((float)$p['valor_venda'] * (float)$t['multiplicador'], 2);
    }
}
unset($p);

/* Filtra apenas a tabela selecionada (para XLS/PDF de tabela específica) */
$tabelaAtiva = null;
if ($tabelaId) {
    foreach ($tabelas as $t) {
        if ((int)$t['id'] === $tabelaId) { $tabelaAtiva = $t; break; }
    }
}

/* ── JSON ───────────────────────────────────────────────────── */
if ($format === 'json') {
    $categorias = db()->query(
        "SELECT id, nome FROM categorias WHERE ativo=1 ORDER BY nome"
    )->fetchAll();

    jsonResponse([
        'success'    => true,
        'tabelas'    => $tabelas,
        'categorias' => $categorias,
        'data'       => $produtos,
    ]);
}

/* ── XLS ────────────────────────────────────────────────────── */
if ($format === 'xls') {
    $filename = 'relatorio_tabela_precos_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";
    echo '<table border="1">';
    echo '<tr style="background:#f0f0f0;font-weight:bold;">';
    echo '<th>Produto</th><th>Categoria</th><th>Unid.</th>';
    echo '<th>Custo Unit.</th><th>Preço Base</th><th>Margem %</th>';

    $tabelasExibir = $tabelaId ? [$tabelaAtiva] : $tabelas;
    foreach ($tabelasExibir as $t) {
        echo '<th>' . h($t['nome']) . ' (×' . number_format((float)$t['multiplicador'],2,',','.') . ')</th>';
    }
    echo '</tr>';

    foreach ($produtos as $p) {
        echo '<tr>';
        echo '<td>' . h($p['produto_nome']) . '</td>';
        echo '<td>' . h($p['categoria_nome'] ?? '—') . '</td>';
        echo '<td>' . h($p['unidade_sigla']) . '</td>';
        echo '<td>' . ($p['custo_unitario'] !== null ? moneyBr($p['custo_unitario']) : '—') . '</td>';
        echo '<td>' . moneyBr($p['valor_venda']) . '</td>';
        echo '<td>' . number_format((float)$p['perc_margem_liquida'], 2, ',', '.') . '%</td>';
        foreach ($tabelasExibir as $t) {
            echo '<td>' . moneyBr($p['precos'][$t['id']]) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
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
    $tabelasExibir = $tabelaId ? [$tabelaAtiva] : $tabelas;
    $tabelaLabel   = $tabelaAtiva ? h($tabelaAtiva['nome']) : 'Todas as tabelas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Tabela de Preços</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .action-bar { margin-bottom: 14px; display: flex; gap: 8px; }
        .btn-print { background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .btn-close  { background: #e5e7eb; color: #374151; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        h1 { font-size: 17px; margin-bottom: 3px; }
        .sub { font-size: 10px; color: #666; margin-bottom: 14px; }
        .summary { display: flex; gap: 10px; margin-bottom: 14px; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: 8px 12px; }
        .card-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .card-value { font-size: 16px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; font-size: 10px; font-weight: 600; white-space: nowrap; }
        td { border: 1px solid #e5e7eb; padding: 4px 8px; }
        tr:nth-child(even) td { background: #fafafa; }
        .r { text-align: right; }
        .c { text-align: center; }
        .margem-high { color: #16a34a; font-weight: bold; }
        .margem-mid  { color: #d97706; font-weight: bold; }
        .margem-low  { color: #dc2626; font-weight: bold; }
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

    <h1>Relatório de Tabela de Preços</h1>
    <p class="sub">
        Gerado em: <?= date('d/m/Y \à\s H:i') ?>
        &nbsp;·&nbsp; Tabela: <?= $tabelaLabel ?>
        <?= $catNome ? ' &nbsp;·&nbsp; Categoria: ' . h($catNome) : '' ?>
        &nbsp;·&nbsp; <?= count($produtos) ?> produto(s)
    </p>

    <div class="summary">
        <div class="card">
            <div class="card-label">Produtos com preço</div>
            <div class="card-value"><?= count($produtos) ?></div>
        </div>
        <div class="card">
            <div class="card-label">Tabelas exibidas</div>
            <div class="card-value"><?= count($tabelasExibir) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th class="c">Unid.</th>
                <th class="r">Custo Unit.</th>
                <th class="r">Preço Base</th>
                <th class="r">Margem %</th>
                <?php foreach ($tabelasExibir as $t): ?>
                <th class="r"><?= h($t['nome']) ?> <span style="font-weight:normal;color:#6b7280;">(×<?= number_format((float)$t['multiplicador'],2,',','.') ?>)</span></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p):
            $m = (float)$p['perc_margem_liquida'];
            $mCls = $m >= 15 ? 'margem-high' : ($m >= 5 ? 'margem-mid' : 'margem-low');
        ?>
            <tr>
                <td><?= h($p['produto_nome']) ?></td>
                <td><?= h($p['categoria_nome'] ?? '—') ?></td>
                <td class="c" style="font-family:monospace;font-size:10px;"><?= h($p['unidade_sigla']) ?></td>
                <td class="r"><?= $p['custo_unitario'] !== null ? moneyBr($p['custo_unitario']) : '<span style="color:#9ca3af">sem custo</span>' ?></td>
                <td class="r" style="font-weight:bold;"><?= moneyBr($p['valor_venda']) ?></td>
                <td class="r <?= $mCls ?>"><?= number_format($m, 2, ',', '.') ?>%</td>
                <?php foreach ($tabelasExibir as $t): ?>
                <td class="r" style="font-weight:bold; color:#1d4ed8;"><?= moneyBr($p['precos'][$t['id']]) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
    exit;
}
