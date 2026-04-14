<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Tabelas de Preço';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Tabelas de Preço</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Multiplicadores aplicados nos orçamentos</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Nova Tabela
    </button>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Buscar tabela..."
                oninput="loadData(this.value)"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400" style="width: 30%;">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-32 text-right">Multiplicador</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-right">Equivale a</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="modal-title" class="font-semibold text-gray-800 dark:text-white">Nova Tabela</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="modal-id" value="0">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome <span class="text-red-500">*</span></label>
                <input id="f-nome" type="text" placeholder="Ex: Tabela Revendedor"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Multiplicador <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 font-normal ml-1">(1.00 = 100%, 0.90 = 10% desconto, 1.10 = 10% acréscimo)</span>
                </label>
                <input id="f-multiplicador" type="number" step="0.01" min="0.01" value="1.00"
                    oninput="atualizaEquivalente()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-right">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="equivalente">Equivale a 100% do preço de venda</p>
            </div>
            <div class="flex items-center gap-2">
                <input id="f-ativo" type="checkbox" checked class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <label for="f-ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeModal()" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">Cancelar</button>
            <button id="btn-save" onclick="saveForm()" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">Salvar</button>
        </div>
    </div>
</div>

<script>
let searchTimeout;
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function atualizaEquivalente() {
    const v = parseFloat(document.getElementById('f-multiplicador').value) || 0;
    const perc = (v * 100).toFixed(0);
    let msg = `Equivale a ${perc}% do preço de venda`;
    if (v < 1) msg += ` (${(100 - v*100).toFixed(0)}% de desconto)`;
    if (v > 1) msg += ` (${((v-1)*100).toFixed(0)}% de acréscimo)`;
    document.getElementById('equivalente').textContent = msg;
}

function loadData(q = '') {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        const res  = await fetch(`api.php?q=${encodeURIComponent(q)}`);
        const json = await res.json();
        const data = json.data || [];
        document.getElementById('count').textContent = `${data.length} registro(s)`;
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhuma tabela encontrada.</td></tr>'; return; }
        tbody.innerHTML = data.map(r => {
            const mult = parseFloat(r.multiplicador);
            const perc = (mult * 100).toFixed(0) + '%';
            return `<tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(r.nome)}</td>
                <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300">${parseFloat(r.multiplicador).toFixed(2)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">${perc}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium ${r.ativo==1?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'}">${r.ativo==1?'Ativo':'Inativo'}</span>
                </td>
                <td class="px-4 py-3 text-right">
                    <button onclick="openModal(${r.id})" class="text-blue-500 hover:text-blue-700 mr-3"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteItem(${r.id},'${esc(r.nome)}')" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    }, 200);
}

function openModal(id = 0) {
    document.getElementById('modal-id').value = id;
    document.getElementById('modal-title').textContent = id ? 'Editar Tabela' : 'Nova Tabela';
    document.getElementById('f-nome').value = '';
    document.getElementById('f-multiplicador').value = '1.00';
    document.getElementById('f-ativo').checked = true;
    atualizaEquivalente();
    if (id) {
        fetch(`api.php?id=${id}`).then(r=>r.json()).then(({data}) => {
            document.getElementById('f-nome').value = data.nome;
            document.getElementById('f-multiplicador').value = parseFloat(data.multiplicador).toFixed(2);
            document.getElementById('f-ativo').checked = data.ativo==1;
            atualizaEquivalente();
            document.getElementById('modal').classList.remove('hidden');
        });
    } else { document.getElementById('modal').classList.remove('hidden'); setTimeout(()=>document.getElementById('f-nome').focus(),50); }
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const id   = parseInt(document.getElementById('modal-id').value);
    const nome = document.getElementById('f-nome').value.trim();
    const mult = parseFloat(document.getElementById('f-multiplicador').value);
    if (!nome)         { showToast('Nome é obrigatório.','error'); return; }
    if (isNaN(mult)||mult<=0) { showToast('Multiplicador inválido.','error'); return; }
    const payload = { id, nome, multiplicador: mult, ativo: document.getElementById('f-ativo').checked?1:0 };
    const btn = document.getElementById('btn-save');
    setLoading(btn, true);
    try {
        const res  = await fetch(id?`api.php?id=${id}`:'api.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) { showToast(data.message); closeModal(); loadData(document.getElementById('search').value); }
        else showToast(data.message,'error');
    } finally { setLoading(btn,false); }
}

function deleteItem(id, label) {
    confirmDialog('Excluir Tabela', `Deseja excluir "${label}"?`, async () => {
        const res  = await fetch(`api.php?id=${id}`, {method:'DELETE'});
        const data = await res.json();
        data.success ? (showToast(data.message), loadData()) : showToast(data.message,'error');
    });
}

document.getElementById('modal').addEventListener('click', e => { if (e.target===e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });
document.addEventListener('DOMContentLoaded', () => loadData());
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
