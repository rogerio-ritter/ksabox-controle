<?php
$pageTitle = 'Produtos';
require_once dirname(__DIR__) . '/layout/header.php';

$produtos   = db()->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id ORDER BY p.nome")->fetchAll();
$categorias = db()->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($produtos) ?> produto(s) encontrado(s)</p>
    <button onclick="abrirModal()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Produto
    </button>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Nome</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden md:table-cell">Categoria</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden lg:table-cell">Unidade</th>
                    <th class="text-center px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                    <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($produtos)): ?>
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">Nenhum produto cadastrado.</td></tr>
                <?php else: ?>
                <?php foreach ($produtos as $p): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="font-medium text-gray-800 dark:text-gray-200"><?= h($p['nome']) ?></p>
                        <?php if ($p['descricao']): ?><p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs"><?= h($p['descricao']) ?></p><?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell"><?= h($p['categoria_nome'] ?? '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell"><?= h($p['unidade']) ?></td>
                    <td class="px-5 py-3.5 text-center">
                        <?php if ($p['ativo']): ?>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30">Ativo</span>
                        <?php else: ?>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editarProduto(<?= h(json_encode($p)) ?>)" class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button onclick="deletarProduto(<?= $p['id'] ?>, '<?= h(addslashes($p['nome'])) ?>')" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
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

<!-- Modal -->
<div id="modalProduto" class="modal-backdrop hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="modal-box bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 id="modalTitulo" class="text-base font-semibold text-gray-800 dark:text-white">Novo Produto</h3>
            <button onclick="closeModal('modalProduto')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form class="p-5 space-y-4" onsubmit="salvarProduto(event)">
            <input type="hidden" id="prodId">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                    <input type="text" id="prodNome" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                    <select id="prodCategoria" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unidade</label>
                    <input type="text" id="prodUnidade" value="un" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm" placeholder="un, m², kg...">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                    <textarea id="prodDescricao" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"></textarea>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="prodAtivo" checked class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                <label for="prodAtivo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modalProduto')" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalTitulo').textContent = 'Novo Produto';
    document.getElementById('prodId').value = '';
    document.getElementById('prodNome').value = '';
    document.getElementById('prodCategoria').value = '';
    document.getElementById('prodUnidade').value = 'un';
    document.getElementById('prodDescricao').value = '';
    document.getElementById('prodAtivo').checked = true;
    openModal('modalProduto');
}
function editarProduto(p) {
    document.getElementById('modalTitulo').textContent = 'Editar Produto';
    document.getElementById('prodId').value = p.id;
    document.getElementById('prodNome').value = p.nome;
    document.getElementById('prodCategoria').value = p.categoria_id || '';
    document.getElementById('prodUnidade').value = p.unidade || 'un';
    document.getElementById('prodDescricao').value = p.descricao || '';
    document.getElementById('prodAtivo').checked = p.ativo == 1;
    openModal('modalProduto');
}
async function salvarProduto(e) {
    e.preventDefault();
    const id = document.getElementById('prodId').value;
    const payload = {
        action: id ? 'update' : 'create', id,
        nome: document.getElementById('prodNome').value,
        categoria_id: document.getElementById('prodCategoria').value || null,
        unidade: document.getElementById('prodUnidade').value,
        descricao: document.getElementById('prodDescricao').value,
        ativo: document.getElementById('prodAtivo').checked ? 1 : 0
    };
    const res  = await fetch(BASE_URL + '/produtos/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const json = await res.json();
    if (json.success) { showToast(json.message); closeModal('modalProduto'); setTimeout(()=>location.reload(), 500); }
    else showToast(json.message, 'error');
}
function deletarProduto(id, nome) {
    confirmAction(`Deseja excluir o produto "${nome}"?`, async () => {
        const res  = await fetch(BASE_URL + '/produtos/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
        const json = await res.json();
        if (json.success) { showToast(json.message); setTimeout(()=>location.reload(), 500); }
        else showToast(json.message, 'error');
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
