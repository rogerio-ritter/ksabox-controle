<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/orcamentos/'); exit; }

$db   = db();
$stmt = $db->prepare(
    "SELECT o.*, c.nome AS cliente_nome, c.cpf_cnpj, c.telefone AS cli_tel, c.email AS cli_email,
            c.endereco AS cli_end, c.cidade, c.estado
     FROM orcamentos o LEFT JOIN clientes c ON c.id = o.cliente_id WHERE o.id = ?"
);
$stmt->execute([$id]);
$orc = $stmt->fetch();
if (!$orc) { header('Location: ' . APP_URL . '/orcamentos/'); exit; }

$stmtIt = $db->prepare(
    "SELECT oi.*, p.nome AS prod_nome, p.unidade FROM orcamento_itens oi
     LEFT JOIN produtos p ON p.id = oi.produto_id WHERE oi.orcamento_id = ? ORDER BY oi.id"
);
$stmtIt->execute([$id]);
$itens = $stmtIt->fetchAll();

$empresa = getEmpresa();
$statusLabel = ['rascunho'=>'Rascunho','enviado'=>'Enviado','aprovado'=>'Aprovado','rejeitado'=>'Rejeitado','cancelado'=>'Cancelado'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento <?= h($orc['numero']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 1.5cm; }
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<!-- Ações -->
<div class="no-print flex items-center gap-3 mb-6 max-w-4xl mx-auto">
    <a href="<?= APP_URL ?>/orcamentos/" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Voltar
    </a>
    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
        Imprimir / Salvar PDF
    </button>
</div>

<!-- Folha -->
<div class="bg-white shadow-xl max-w-4xl mx-auto rounded-xl overflow-hidden">

    <div class="bg-indigo-600 text-white p-8">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold"><?= h($empresa['nome']) ?></h1>
                <?php if ($empresa['cnpj']): ?><p class="text-indigo-200 text-sm mt-1">CNPJ: <?= h($empresa['cnpj']) ?></p><?php endif; ?>
                <?php if ($empresa['telefone']): ?><p class="text-indigo-200 text-sm">Tel: <?= h($empresa['telefone']) ?></p><?php endif; ?>
                <?php if ($empresa['email']): ?><p class="text-indigo-200 text-sm"><?= h($empresa['email']) ?></p><?php endif; ?>
                <?php if ($empresa['endereco']): ?><p class="text-indigo-200 text-sm"><?= h($empresa['endereco']) ?></p><?php endif; ?>
            </div>
            <div class="text-right">
                <div class="bg-white/20 rounded-xl p-4">
                    <p class="text-indigo-100 text-xs uppercase font-semibold tracking-wider mb-1">Orçamento</p>
                    <p class="text-3xl font-bold"><?= h($orc['numero']) ?></p>
                    <div class="mt-2 inline-flex px-3 py-1 bg-white/20 rounded-full text-xs font-medium">
                        <?= $statusLabel[$orc['status']] ?? $orc['status'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-8">
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cliente</h2>
                <p class="font-semibold text-gray-800 text-base"><?= h($orc['cliente_nome'] ?? 'Não informado') ?></p>
                <?php if ($orc['cpf_cnpj']): ?><p class="text-sm text-gray-500 mt-1">CPF/CNPJ: <?= h($orc['cpf_cnpj']) ?></p><?php endif; ?>
                <?php if ($orc['cli_tel']): ?><p class="text-sm text-gray-500">Tel: <?= h($orc['cli_tel']) ?></p><?php endif; ?>
                <?php if ($orc['cli_email']): ?><p class="text-sm text-gray-500"><?= h($orc['cli_email']) ?></p><?php endif; ?>
                <?php if ($orc['cli_end']): ?><p class="text-sm text-gray-500"><?= h($orc['cli_end']) ?><?= $orc['cidade'] ? ', ' . h($orc['cidade']) : '' ?><?= $orc['estado'] ? '/' . h($orc['estado']) : '' ?></p><?php endif; ?>
            </div>
            <div class="text-right">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Datas</h2>
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Emissão:</span>
                        <span class="font-medium text-gray-800"><?= dateBr($orc['data_criacao']) ?></span>
                    </div>
                    <?php if ($orc['data_validade']): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Validade:</span>
                        <span class="font-medium text-gray-800"><?= dateBr($orc['data_validade']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <table class="w-full mb-6 text-sm">
            <thead>
                <tr class="bg-gray-50 border-y border-gray-200">
                    <th class="text-left px-4 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">#</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Descrição</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Qtd</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Preço Unit.</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $i => $item): ?>
                <tr class="border-b border-gray-100 <?= $i % 2 === 0 ? '' : 'bg-gray-50/50' ?>">
                    <td class="px-4 py-3 text-gray-400 text-xs"><?= $i + 1 ?></td>
                    <td class="px-4 py-3 text-gray-800 font-medium">
                        <?= h($item['descricao'] ?: ($item['prod_nome'] ?? '—')) ?>
                        <?php if ($item['prod_nome'] && $item['descricao'] && $item['descricao'] !== $item['prod_nome']): ?>
                        <span class="text-xs text-gray-400 ml-1">(<?= h($item['prod_nome']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600"><?= number_format($item['quantidade'],2,',','.') ?><?= $item['unidade'] ? ' '.h($item['unidade']) : '' ?></td>
                    <td class="px-4 py-3 text-right text-gray-600"><?= moneyBr($item['preco_unitario']) ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800"><?= moneyBr($item['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-end mb-6">
            <div class="bg-indigo-50 rounded-xl p-4 min-w-48 text-right">
                <p class="text-sm text-gray-500 mb-1">Total Geral</p>
                <p class="text-2xl font-bold text-indigo-700"><?= moneyBr($orc['total']) ?></p>
            </div>
        </div>

        <?php if ($orc['observacoes']): ?>
        <div class="border-t border-gray-100 pt-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Observações</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line"><?= h($orc['observacoes']) ?></p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-12 mt-12 pt-8 border-t border-gray-100">
            <div class="text-center"><div class="border-t-2 border-gray-300 pt-2">
                <p class="text-sm text-gray-500"><?= h($empresa['nome']) ?></p>
                <p class="text-xs text-gray-400">Vendedor</p>
            </div></div>
            <div class="text-center"><div class="border-t-2 border-gray-300 pt-2">
                <p class="text-sm text-gray-500"><?= h($orc['cliente_nome'] ?? 'Cliente') ?></p>
                <p class="text-xs text-gray-400">Aprovação</p>
            </div></div>
        </div>
    </div>

    <div class="bg-gray-50 border-t border-gray-100 px-8 py-4 text-center text-xs text-gray-400">
        Gerado em <?= date('d/m/Y H:i') ?> — <?= h($empresa['nome']) ?>
    </div>
</div>

<script>
if (new URLSearchParams(window.location.search).get('print') === '1') setTimeout(() => window.print(), 800);
</script>
</body>
</html>
