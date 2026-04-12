<?php
$pageTitle = 'Tabelas de Preços';
require_once dirname(__DIR__) . '/layout/header.php';

$tabelas  = db()->query('SELECT * FROM tabelas_precos ORDER BY nome')->fetchAll();
$produtos = db()->query('SELECT p.id, p.nome, p.unidade FROM produtos p WHERE p.ativo = 1 ORDER BY p.nome')->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($tabelas) ?> tabela(s)</p>
    <button onclick="abrirModalTabela()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova Tabela
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php if (empty($tabelas)): ?>
    <div class="col-span-full bg-white dark:bg-gray-800 rounded-2xl p-10 text-center text-gray-400 border border-gray-100 dark:border-gray-700">
        Nenhuma tabela de preços cadastrada.
    </div>
    <?php else: ?>
    <?php foreach ($tabelas as $t):
        $stmt = db()->prepare('SELECT COUNT(*) FROM tabela_preco_itens WHERE tabela_id = ?');
        $stmt->execute([$t['id']]);
        $qtd = $stmt->fetchColumn();
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-white"><?= h($t['nome']) ?></h3>
                <?php if ($t['descricao']): ?><p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= h($t['descricao']) ?></p><?php endif; ?>
            </div>
            <?php if ($t['ativo']): ?>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30 shrink-0">Ativa</span>
            <?php else: ?>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium text-gray-600 bg-gray-100 shrink-0">Inativa</span>
            <?php endif; ?>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><?= $qtd ?> produto(s)</p>
        <div class="flex gap-2">
            <button onclick="gerenciarItens(<?= $t['id'] ?>, '<?= h(addslashes($t['nome'])) ?>')" class="flex-1 py-2 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                Gerenciar Preços
            </button>
            <button onclick="editarTabela(<?= h(json_encode($t)) ?>)" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>
            <button onclick="deletarTabela(<?= $t['id'] ?>, '<?= h(addslashes($t['nome'])) ?>')" class="p-2 text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Tabela -->
<div id="modalTabela" class="modal-backdrop hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="modal-box bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 id="modalTituloTabela" class="text-base font-semibold text-gray-800 dark:text-white">Nova Tabela</h3>
            <button onclick="closeModal('modalTabela')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form class="p-5 space-y-4" onsubmit="salvarTabela(event)">
            <input type="hidden" id="tabId">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                <input type="text" id="tabNome" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                <textarea id="tabDescricao" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="tabAtivo" checked class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                <label for="tabAtivo" class="text-sm text-gray-700 dark:text-gray-300">Ativa</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modalTabela')" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Itens -->
<div id="modalItens" class="modal-backdrop hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="modal-box bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Gerenciar Preços</h3>
                <p id="tabItensNome" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="closeModal('modalItens')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <div class="flex gap-3">
                <select id="itProduto" class="flex-1 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos as $prod): ?>
                    <option value="<?= $prod['id'] ?>"><?= h($prod['nome']) ?> (<?= h($prod['unidade']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="itPreco" min="0" step="0.01" placeholder="Preço (R$)" class="w-36 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                <button onclick="adicionarItem()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">Adicionar</button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                    <tr>
                        <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Produto</th>
                        <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Preço</th>
                        <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodyItens" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400">Nenhum produto nesta tabela</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let tabelaAtualId = null;

function abrirModalTabela() {
    document.getElementById('modalTituloTabela').textContent = 'Nova Tabela';
    document.getElementById('tabId').value = '';
    document.getElementById('tabNome').value = '';
    document.getElementById('tabDescricao').value = '';
    document.getElementById('tabAtivo').checked = true;
    openModal('modalTabela');
}
function editarTabela(t) {
    document.getElementById('modalTituloTabela').textContent = 'Editar Tabela';
    document.getElementById('tabId').value = t.id;
    document.getElementById('tabNome').value = t.nome;
    document.getElementById('tabDescricao').value = t.descricao || '';
    document.getElementById('tabAtivo').checked = t.ativo == 1;
    openModal('modalTabela');
}
async function salvarTabela(e) {
    e.preventDefault();
    const id = document.getElementById('tabId').value;
    const payload = {action: id ? 'update' : 'create', id, nome: document.getElementById('tabNome').value, descricao: document.getElementById('tabDescricao').value, ativo: document.getElementById('tabAtivo').checked ? 1 : 0};
    const res = await fetch(BASE_URL + '/tabela_preco/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const json = await res.json();
    if (json.success) { showToast(json.message); closeModal('modalTabela'); setTimeout(()=>location.reload(), 500); }
    else showToast(json.message, 'error');
}
function deletarTabela(id, nome) {
    confirmAction(`Deseja excluir a tabela "${nome}"?`, async () => {
        const res = await fetch(BASE_URL + '/tabela_preco/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
        const json = await res.json();
        if (json.success) { showToast(json.message); setTimeout(()=>location.reload(), 500); }
        else showToast(json.message, 'error');
    });
}
async function gerenciarItens(id, nome) {
    tabelaAtualId = id;
    document.getElementById('tabItensNome').textContent = nome;
    await carregarItens();
    openModal('modalItens');
}
async function carregarItens() {
    const res  = await fetch(`${BASE_URL}/tabela_preco/api.php?action=itens&tabela_id=${tabelaAtualId}`);
    const json = await res.json();
    const tbody = document.getElementById('tbodyItens');
    if (!json.data || json.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-5 py-8 text-center text-gray-400">Nenhum produto nesta tabela</td></tr>';
        return;
    }
    tbody.innerHTML = json.data.map(it => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">${it.produto_nome}</td>
            <td class="px-5 py-3 text-right">
                <input type="number" value="${it.preco}" min="0" step="0.01" onchange="atualizarPreco(${it.id}, this.value)"
                    class="w-28 px-2 py-1 text-right rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </td>
            <td class="px-5 py-3 text-right">
                <button onclick="removerItem(${it.id})" class="p-1 text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </td>
        </tr>`).join('');
}
async function adicionarItem() {
    const prodId = document.getElementById('itProduto').value;
    const preco  = document.getElementById('itPreco').value;
    if (!prodId || preco === '') return showToast('Selecione produto e informe o preço.', 'error');
    const res = await fetch(BASE_URL + '/tabela_preco/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'add_item', tabela_id: tabelaAtualId, produto_id: prodId, preco})});
    const json = await res.json();
    if (json.success) { showToast(json.message); document.getElementById('itProduto').value = ''; document.getElementById('itPreco').value = ''; carregarItens(); }
    else showToast(json.message, 'error');
}
async function atualizarPreco(itemId, preco) {
    await fetch(BASE_URL + '/tabela_preco/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'update_item', id: itemId, preco})});
}
async function removerItem(itemId) {
    const res = await fetch(BASE_URL + '/tabela_preco/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete_item', id: itemId})});
    const json = await res.json();
    if (json.success) carregarItens();
    else showToast(json.message, 'error');
}
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
