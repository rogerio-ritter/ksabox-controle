<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = db()->prepare("
    SELECT o.*, c.nome AS cliente_nome, c.cnpj_cpf, c.email AS cliente_email,
           c.telefone AS cliente_tel, c.endereco, c.numero AS end_numero,
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

$stmt2 = db()->prepare("
    SELECT oi.*, p.nome AS produto_nome, p.unidade_sigla
    FROM orcamento_itens oi
    JOIN produtos p ON p.id = oi.produto_id
    WHERE oi.orcamento_id = ?
    ORDER BY oi.id
");
$stmt2->execute([$id]);
$itens = $stmt2->fetchAll();

$pageTitle = 'Orçamento ' . $orc['numero'];
require_once __DIR__ . '/../../layout/header.php';

$badgeCls = match($orc['status']) {
    'Aprovado'  => 'bg-green-100 text-green-700',
    'Enviado'   => 'bg-blue-100 text-blue-700',
    'Rejeitado' => 'bg-red-100 text-red-700',
    'Cancelado' => 'bg-yellow-100 text-yellow-700',
    default     => 'bg-gray-100 text-gray-700',
};
?>

<!-- Cabeçalho da página -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white font-mono"><?= h($orc['numero']) ?></h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeCls ?>"><?= h($orc['status']) ?></span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= h($orc['cliente_nome']) ?></p>
        </div>
    </div>
    <div class="flex gap-2 flex-wrap">
        <button onclick="abrirModalStatus()" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
            <i class="fas fa-exchange-alt mr-1"></i> Status
        </button>
        <a href="form.php?id=<?= $id ?>" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fas fa-edit mr-1"></i> Editar
        </a>
        <a href="pdf.php?id=<?= $id ?>" target="_blank" class="px-3 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg">
            <i class="fas fa-file-pdf mr-1"></i> PDF
        </a>
    </div>
</div>

<!-- Grid Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    <!-- Info Cliente -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cliente</h3>
        <p class="font-semibold text-gray-800 dark:text-white"><?= h($orc['cliente_nome']) ?></p>
        <?php if ($orc['cnpj_cpf']): ?><p class="text-sm text-gray-500 dark:text-gray-400"><?= h($orc['cnpj_cpf']) ?></p><?php endif; ?>
        <?php if ($orc['cliente_tel']): ?><p class="text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-phone w-4"></i> <?= h($orc['cliente_tel']) ?></p><?php endif; ?>
        <?php if ($orc['cliente_email']): ?><p class="text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-envelope w-4"></i> <?= h($orc['cliente_email']) ?></p><?php endif; ?>
        <?php if ($orc['cidade']): ?><p class="text-sm text-gray-500 dark:text-gray-400"><i class="fas fa-map-marker-alt w-4"></i> <?= h($orc['cidade']) ?>/<?= h($orc['uf']) ?></p><?php endif; ?>
    </div>

    <!-- Info Orçamento -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Orçamento</h3>
        <dl class="space-y-1.5 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Número</dt>
                <dd class="font-mono font-medium text-gray-800 dark:text-white"><?= h($orc['numero']) ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Data</dt>
                <dd class="text-gray-700 dark:text-gray-300"><?= dateBr($orc['data_criacao']) ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Validade</dt>
                <dd class="text-gray-700 dark:text-gray-300"><?= dateBr($orc['validade']) ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tabela de Preço</dt>
                <dd class="text-gray-700 dark:text-gray-300"><?= h($orc['tabela_nome']) ?></dd>
            </div>
            <?php if ($orc['prazo_entrega']): ?>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Prazo Entrega</dt>
                <dd class="text-gray-700 dark:text-gray-300"><?= h($orc['prazo_entrega']) ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    <!-- Totais resumo -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Resumo Financeiro</h3>
        <dl class="space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                <dt>Subtotal Material</dt><dd><?= moneyBr($orc['subtotal_material']) ?></dd>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                <dt>Subtotal Serviço</dt><dd><?= moneyBr($orc['subtotal_servico']) ?></dd>
            </div>
            <div class="flex justify-between font-medium text-gray-700 dark:text-gray-300 pt-1 border-t border-gray-200 dark:border-gray-700">
                <dt>Subtotal</dt><dd><?= moneyBr($orc['subtotal']) ?></dd>
            </div>
            <?php if ((float)$orc['total_ipi'] > 0): ?>
            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                <dt>IPI</dt><dd><?= moneyBr($orc['total_ipi']) ?></dd>
            </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold text-lg text-blue-600 dark:text-blue-400 pt-2 border-t-2 border-gray-300 dark:border-gray-600">
                <dt>Total Geral</dt><dd><?= moneyBr($orc['total_geral']) ?></dd>
            </div>
        </dl>
    </div>
</div>

<!-- Tabela de Itens -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-5">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Itens do Orçamento (<?= count($itens) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-16">Unid.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-24">Qtd</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Vlr Unit.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-20">% Desc.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Vlr c/ Desc.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Vlr Total</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-20">% Mat.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-24">% Margem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itens as $item):
                $pm = (float)$item['perc_margem_liquida'];
                $mCls = $pm >= 15 ? 'text-green-600' : ($pm >= 5 ? 'text-yellow-600' : 'text-red-500');
            ?>
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200"><?= h($item['produto_nome']) ?></td>
                    <td class="px-4 py-3 text-center text-xs font-mono text-gray-500 dark:text-gray-400"><?= h($item['unidade_sigla']) ?></td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400"><?= number_format((float)$item['quantidade'], 2, ',', '.') ?></td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400"><?= moneyBr($item['valor_unitario']) ?></td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        <?= (float)$item['perc_desconto'] > 0 ? number_format((float)$item['perc_desconto'], 2, ',', '.').'%' : '—' ?>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300"><?= moneyBr($item['valor_com_desconto'] ?? $item['valor_unitario']) ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200"><?= moneyBr($item['valor_total']) ?></td>
                    <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400"><?= number_format((float)$item['perc_material'], 1, ',', '.') ?>%</td>
                    <td class="px-4 py-3 text-center text-xs font-semibold <?= $mCls ?>"><?= number_format($pm, 1, ',', '.') ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Condições Comerciais -->
<?php if ($orc['condicao_pagamento'] || $orc['condicao_entrega'] || $orc['condicoes_gerais'] || $orc['observacoes']): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-5">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Condições Comerciais</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <?php if ($orc['condicao_pagamento']): ?>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Pagamento</p>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= h($orc['condicao_pagamento']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($orc['condicao_entrega']): ?>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Entrega</p>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= h($orc['condicao_entrega']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($orc['condicoes_gerais']): ?>
        <div class="sm:col-span-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Condições Gerais</p>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= h($orc['condicoes_gerais']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($orc['observacoes']): ?>
        <div class="sm:col-span-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
            <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase tracking-wider mb-1">Observações Internas</p>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-xs"><?= h($orc['observacoes']) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Alterar Status -->
<div id="modal-status" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xs p-6">
        <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Alterar Status</h3>
        <div class="grid grid-cols-1 gap-2">
            <?php foreach(['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'] as $s): ?>
            <button onclick="alterarStatus('<?= $s ?>')"
                class="w-full text-left px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600
                       hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300
                       <?= $orc['status'] === $s ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300' : '' ?>">
                <?= $s === $orc['status'] ? '✓ ' : '' ?><?= $s ?>
            </button>
            <?php endforeach; ?>
        </div>
        <button onclick="document.getElementById('modal-status').classList.add('hidden')"
            class="mt-4 w-full py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400">
            Cancelar
        </button>
    </div>
</div>

<script>
function abrirModalStatus() { document.getElementById('modal-status').classList.remove('hidden'); }

async function alterarStatus(novoStatus) {
    const res  = await fetch(`api.php?action=status&id=<?= $id ?>`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ status: novoStatus })
    });
    const data = await res.json();
    document.getElementById('modal-status').classList.add('hidden');
    if (data.success) { showToast(data.message); setTimeout(() => location.reload(), 700); }
    else showToast(data.message, 'error');
}
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
