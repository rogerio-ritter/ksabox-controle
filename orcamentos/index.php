<?php
$pageTitle = 'Orçamentos';
require_once dirname(__DIR__) . '/layout/header.php';

$filtroStatus = $_GET['status'] ?? '';
$where = $filtroStatus ? 'WHERE o.status = ' . db()->quote($filtroStatus) : '';

$orcamentos = db()->query(
    "SELECT o.*, c.nome AS cliente_nome FROM orcamentos o
     LEFT JOIN clientes c ON c.id = o.cliente_id
     $where ORDER BY o.created_at DESC"
)->fetchAll();

$statusInfo = [
    'rascunho'  => ['Rascunho', 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700'],
    'enviado'   => ['Enviado',  'text-blue-700 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30'],
    'aprovado'  => ['Aprovado', 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30'],
    'rejeitado' => ['Rejeitado','text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30'],
    'cancelado' => ['Cancelado','text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700'],
];
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($orcamentos) ?> orçamento(s)</p>
        <div class="flex gap-1 flex-wrap">
            <a href="<?= APP_URL ?>/orcamentos/" class="px-3 py-1 text-xs rounded-full font-medium transition-colors <?= !$filtroStatus ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' ?>">Todos</a>
            <?php foreach ($statusInfo as $key => [$label, $cls]): ?>
            <a href="<?= APP_URL ?>/orcamentos/?status=<?= $key ?>" class="px-3 py-1 text-xs rounded-full font-medium transition-colors <?= $filtroStatus === $key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <a href="<?= APP_URL ?>/orcamentos/form.php" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Orçamento
    </a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Nº</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Cliente</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden md:table-cell">Data</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden lg:table-cell">Validade</th>
                    <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Total</th>
                    <th class="text-center px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                    <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($orcamentos)): ?>
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Nenhum orçamento encontrado.</td></tr>
                <?php else: ?>
                <?php foreach ($orcamentos as $o):
                    [$sl, $sc] = $statusInfo[$o['status']] ?? ['?', ''];
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-5 py-3.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                        <a href="<?= APP_URL ?>/orcamentos/form.php?id=<?= $o['id'] ?>"><?= h($o['numero']) ?></a>
                    </td>
                    <td class="px-5 py-3.5 text-gray-800 dark:text-gray-200"><?= h($o['cliente_nome'] ?? '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell"><?= dateBr($o['data_criacao']) ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell"><?= dateBr($o['data_validade']) ?></td>
                    <td class="px-5 py-3.5 text-right font-semibold text-gray-800 dark:text-gray-200"><?= moneyBr($o['total']) ?></td>
                    <td class="px-5 py-3.5 text-center">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sc ?>"><?= $sl ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="<?= APP_URL ?>/orcamentos/form.php?id=<?= $o['id'] ?>" class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>
                            <a href="<?= APP_URL ?>/orcamentos/print.php?id=<?= $o['id'] ?>" target="_blank" class="p-1.5 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Imprimir PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                            </a>
                            <button onclick="deletarOrcamento(<?= $o['id'] ?>, '<?= h(addslashes($o['numero'])) ?>')" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Excluir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deletarOrcamento(id, num) {
    confirmAction(`Deseja excluir o orçamento "${num}"?`, async () => {
        const res  = await fetch(BASE_URL + '/orcamentos/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
        const json = await res.json();
        if (json.success) { showToast(json.message); setTimeout(()=>location.reload(), 500); }
        else showToast(json.message, 'error');
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
