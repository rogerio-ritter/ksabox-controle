<?php
/**
 * pdf.php — Versão para impressão/PDF do orçamento
 * Renderiza HTML otimizado para print. O usuário usa Ctrl+P / "Salvar como PDF".
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

/* ── Orçamento ── */
$stmt = db()->prepare("
    SELECT o.*,
           c.nome AS cliente_nome, c.cnpj_cpf, c.email AS cliente_email,
           c.telefone AS cliente_tel,
           c.endereco, c.numero AS end_numero, c.complemento,
           c.bairro, c.cidade, c.uf, c.cep,
           tp.nome AS tabela_nome
    FROM orcamentos o
    JOIN clientes c   ON c.id  = o.cliente_id
    JOIN tabela_precos tp ON tp.id = o.tabela_preco_id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$orc = $stmt->fetch();
if (!$orc) { header('Location: index.php'); exit; }

/* ── Itens ── */
$stmt2 = db()->prepare("
    SELECT oi.*, p.nome AS produto_nome, p.unidade_sigla
    FROM orcamento_itens oi
    JOIN produtos p ON p.id = oi.produto_id
    WHERE oi.orcamento_id = ? ORDER BY oi.id
");
$stmt2->execute([$id]);
$itens = $stmt2->fetchAll();

/* ── Empresa ── */
$empresa = db()->query("SELECT * FROM empresa LIMIT 1")->fetch() ?: [];

/* ── Desconto calculado ── */
$desconto = $orc['tipo_desconto'] === 'valor'
    ? (float)$orc['desconto_valor']
    : ((float)$orc['subtotal'] * (float)$orc['desconto_percentual'] / 100);

$statusCor = match($orc['status']) {
    'Aprovado'  => '#16a34a',
    'Enviado'   => '#2563eb',
    'Rejeitado' => '#dc2626',
    'Cancelado' => '#ca8a04',
    default     => '#6b7280',
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orçamento <?= h($orc['numero']) ?> — Ksabox</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'Helvetica Neue', Arial, sans-serif;
    font-size: 11pt;
    color: #1f2937;
    background: #f3f4f6;
    margin: 0;
    padding: 20px;
}
.page {
    background: #fff;
    max-width: 210mm;
    margin: 0 auto;
    padding: 20mm 18mm 15mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
}

/* ── Cabeçalho ── */
.header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #1d4ed8; padding-bottom:12px; margin-bottom:16px; }
.empresa-nome { font-size:16pt; font-weight:700; color:#1d4ed8; }
.empresa-info { font-size:8.5pt; color:#6b7280; line-height:1.5; margin-top:3px; }
.orc-titulo { text-align:right; }
.orc-titulo h1 { font-size:18pt; font-weight:700; color:#1f2937; margin:0 0 4px; }
.orc-numero { font-size:11pt; font-weight:600; color:#374151; font-family:monospace; }
.status-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:9pt; font-weight:600; color:#fff; margin-top:6px; }

/* ── Seções ── */
.sec { margin-bottom:14px; }
.sec-label { font-size:8pt; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.info-box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; }
.info-box p { margin:0; line-height:1.6; font-size:9.5pt; }
.info-box .bold { font-weight:600; font-size:10pt; }

/* ── Tabela de Itens ── */
table { width:100%; border-collapse:collapse; margin-bottom:14px; font-size:9.5pt; }
thead th { background:#1d4ed8; color:#fff; padding:7px 8px; text-align:left; font-weight:600; }
thead th.right { text-align:right; }
thead th.center { text-align:center; }
tbody tr:nth-child(even) { background:#f8faff; }
tbody tr:hover { background:#eff6ff; }
tbody td { padding:6px 8px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
tbody td.right { text-align:right; font-variant-numeric:tabular-nums; }
tbody td.center { text-align:center; }
.product-name { font-weight:500; }

/* ── Totais ── */
.totals { display:flex; justify-content:flex-end; margin-bottom:14px; }
.totals-box { width:260px; }
.tot-row { display:flex; justify-content:space-between; padding:3px 0; font-size:9.5pt; color:#6b7280; }
.tot-row.divider { border-top:1px solid #e5e7eb; margin-top:4px; padding-top:6px; font-weight:600; color:#374151; }
.tot-row.total-geral { border-top:2px solid #1d4ed8; margin-top:6px; padding-top:8px; font-size:13pt; font-weight:700; color:#1d4ed8; }
.tot-row .neg { color:#dc2626; }

/* ── Condições ── */
.cond-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.cond-box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; }
.cond-label { font-size:8pt; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:4px; }
.cond-text { font-size:9pt; color:#374151; white-space:pre-wrap; line-height:1.5; }

/* ── Rodapé ── */
.footer { margin-top:20px; border-top:1px solid #e5e7eb; padding-top:12px; display:flex; justify-content:space-between; align-items:flex-end; }
.signature-line { text-align:center; }
.signature-line .line { border-top:1px solid #374151; width:200px; margin:40px auto 4px; }
.signature-line p { font-size:8.5pt; color:#6b7280; margin:0; }
.footer-note { font-size:8pt; color:#9ca3af; text-align:right; }

/* ── Barra de ações (apenas tela) ── */
.action-bar { display:flex; gap:8px; justify-content:flex-end; max-width:210mm; margin:0 auto 12px; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:10pt; font-weight:600; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
.btn-primary { background:#1d4ed8; color:#fff; }
.btn-primary:hover { background:#1e40af; }
.btn-secondary { background:#fff; color:#374151; border:1px solid #d1d5db; }
.btn-secondary:hover { background:#f9fafb; }

@media print {
    body { background:#fff; padding:0; }
    .page { box-shadow:none; padding:12mm 14mm 10mm; }
    .action-bar { display:none; }
    thead th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    tbody tr:nth-child(even) { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .status-badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
</head>
<body>

<!-- Barra de ações (somente tela) -->
<div class="action-bar">
    <a href="visualizar.php?id=<?= $id ?>" class="btn btn-secondary">
        ← Voltar
    </a>
    <a href="form.php?id=<?= $id ?>" class="btn btn-secondary">
        ✏️ Editar
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        🖨️ Imprimir / Salvar PDF
    </button>
</div>

<!-- Página do documento -->
<div class="page">

    <!-- Cabeçalho -->
    <div class="header">
        <div>
            <div> <img width="310" height='81'  src="<?= APP_URL; ?>/assets/img/logo-orc.svg" /></div>
           <!-- <div class="empresa-nome"><?= h($empresa['nome'] ?? 'Ksabox') ?></div> -->
            <div class="empresa-info">
                <?php if (!empty($empresa['cnpj'])): ?>CNPJ: <?= h($empresa['cnpj']) ?><br><?php endif; ?>
                <?php if (!empty($empresa['telefone'])): ?>Tel.: <?= h($empresa['telefone']) ?><?php endif; ?>
                <?php if (!empty($empresa['email'])): ?> &nbsp;|&nbsp; <?= h($empresa['email']) ?><?php endif; ?>
                <?php if (!empty($empresa['cidade'])): ?><br><?= h($empresa['cidade']) ?>/<?= h($empresa['uf']) ?><?php endif; ?>
            </div>
        </div>
        <div class="orc-titulo">
            <h1>ORÇAMENTO</h1>
            <div class="orc-numero"><?= h($orc['numero']) ?></div>
            <div>
                <span class="status-badge" style="background:<?= $statusCor ?>;"><?= h($orc['status']) ?></span>
            </div>
        </div>
    </div>

    <!-- Cliente + Datas -->
    <div class="grid-2 sec">
        <div class="info-box">
            <div class="sec-label">Cliente</div>
            <p class="bold"><?= h($orc['cliente_nome']) ?></p>
            <?php if ($orc['cnpj_cpf']): ?><p><?= h($orc['cnpj_cpf']) ?></p><?php endif; ?>
            <?php if ($orc['cliente_tel']): ?><p>Tel.: <?= h($orc['cliente_tel']) ?></p><?php endif; ?>
            <?php if ($orc['cliente_email']): ?><p><?= h($orc['cliente_email']) ?></p><?php endif; ?>
            <?php if ($orc['cidade']): ?>
            <p><?= h($orc['cidade']) ?>/<?= h($orc['uf']) ?></p>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <div class="sec-label">Detalhes</div>
            <p><strong>Data de emissão:</strong> <?= dateBr($orc['data_criacao']) ?></p>
            <p><strong>Válido até:</strong> <?= dateBr($orc['validade']) ?: '—' ?></p>
           <!-- <p><strong>Tabela de Preço:</strong> <?= h($orc['tabela_nome']) ?></p> -->
            <?php if ($orc['prazo_entrega']): ?>
            <p><strong>Prazo de entrega:</strong> <?= h($orc['prazo_entrega']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Itens -->
    <div class="sec-label">Itens do Orçamento</div>
    <table>
        <thead>
            <tr>
                <th style="width:40%">Produto</th>
                <th class="center" style="width:8%">Unid.</th>
                <th class="right" style="width:10%">Qtd</th>
                <th class="right" style="width:14%">Vlr Unit.</th>
                <th class="right" style="width:14%">Vlr Total</th>
              <!--  <th class="right" style="width:14%">% Margem</th> -->
            </tr>
        </thead>
        <tbody>
        <?php foreach ($itens as $item):
            $pm = (float)$item['perc_margem_liquida'];
            $mCor = $pm >= 15 ? '#16a34a' : ($pm >= 5 ? '#ca8a04' : '#dc2626');
        ?>
            <tr>
                <td class="product-name"><?= h($item['produto_nome']) ?></td>
                <td class="center"><?= h($item['unidade_sigla']) ?></td>
                <td class="right"><?= number_format((float)$item['quantidade'], 2, ',', '.') ?></td>
                <td class="right"><?= moneyBr($item['valor_unitario']) ?></td>
                <td class="right"><strong><?= moneyBr($item['valor_total']) ?></strong></td>
               <!-- <td class="right" style="color:<?= $mCor ?>; font-weight:600;">
                    <?= number_format($pm, 1, ',', '.') ?>%
                </td> -->
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totais -->
    <div class="totals">
        <div class="totals-box">
            <div class="tot-row"><span>Subtotal Material</span><span><?= moneyBr($orc['subtotal_material']) ?></span></div>
            <div class="tot-row"><span>Subtotal Serviço</span><span><?= moneyBr($orc['subtotal_servico']) ?></span></div>
            <div class="tot-row divider"><span>Subtotal</span><span><?= moneyBr($orc['subtotal']) ?></span></div>
            <?php if ((float)$orc['total_ipi'] > 0): ?>
            <div class="tot-row"><span>IPI</span><span><?= moneyBr($orc['total_ipi']) ?></span></div>
            <?php endif; ?>
            <?php if ($desconto > 0): ?>
            <div class="tot-row">
                <span>Desconto<?= $orc['tipo_desconto'] === 'percentual' ? ' (' . number_format((float)$orc['desconto_percentual'], 2, ',', '.') . '%)' : '' ?></span>
                <span class="neg">- <?= moneyBr($desconto) ?></span>
            </div>
            <?php endif; ?>
            <div class="tot-row total-geral"><span>TOTAL GERAL</span><span><?= moneyBr($orc['total_geral']) ?></span></div>
        </div>
    </div>

    <!-- Condições Comerciais -->
    <?php if ($orc['condicao_pagamento'] || $orc['condicao_entrega'] || $orc['condicoes_gerais']): ?>
    <div class="cond-grid sec">
        <?php if ($orc['condicao_pagamento']): ?>
        <div class="cond-box">
            <div class="cond-label">Condição de Pagamento</div>
            <div class="cond-text"><?= h($orc['condicao_pagamento']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($orc['condicao_entrega']): ?>
        <div class="cond-box">
            <div class="cond-label">Condição de Entrega</div>
            <div class="cond-text"><?= h($orc['condicao_entrega']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($orc['condicoes_gerais']): ?>
        <div class="cond-box" style="grid-column:span 2">
            <div class="cond-label">Condições Gerais</div>
            <div class="cond-text"><?= h($orc['condicoes_gerais']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Rodapé / Assinatura -->
    <div class="footer">
        <div class="footer-note">
            Documento gerado em <?= date('d/m/Y H:i') ?><br>
            <?= h($empresa['nome'] ?? 'Ksabox') ?>
        </div>
        <div class="signature-line">
            <div class="line"></div>
            <p><?= h($empresa['nome'] ?? 'Ksabox') ?></p>
            <p>Assinatura / Carimbo</p>
        </div>
    </div>

</div><!-- /.page -->

</body>
</html>
