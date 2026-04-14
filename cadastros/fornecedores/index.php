<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Fornecedores';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Fornecedores</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Cadastro de fornecedores de produtos</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Novo Fornecedor
    </button>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Buscar fornecedor..."
                oninput="loadData(this.value)"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Contato</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Telefone</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">E-mail</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-24">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-24">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 id="modal-title" class="font-semibold text-gray-800 dark:text-white">Novo Fornecedor</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="modal-id" value="0">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome <span class="text-red-500">*</span></label>
                <input id="f-nome" type="text" placeholder="Razão social ou nome"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                    <input id="f-telefone" type="text" placeholder="(00) 00000-0000"
                        oninput="maskPhone(this)"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input id="f-email" type="email" placeholder="contato@empresa.com"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Contato</label>
                <input id="f-contato" type="text" placeholder="Nome da pessoa de contato"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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

function loadData(q = '') {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        try {
            const res  = await fetch(`api.php?q=${encodeURIComponent(q)}`);
            const json = await res.json();
            const data = json.data || [];
            document.getElementById('count').textContent = `${data.length} registro(s)`;
            if (!data.length) { tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum fornecedor encontrado.</td></tr>'; return; }
            tbody.innerHTML = data.map(r => `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(r.nome)}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.contato||'—')}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.telefone||'—')}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.email||'—')}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${r.ativo==1?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'}">${r.ativo==1?'Ativo':'Inativo'}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="openModal(${r.id})" class="text-blue-500 hover:text-blue-700 mr-3"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteItem(${r.id},'${esc(r.nome)}')" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`).join('');
        } catch(e) { tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>'; }
    }, 200);
}

function openModal(id = 0) {
    document.getElementById('modal-id').value = id;
    document.getElementById('modal-title').textContent = id ? 'Editar Fornecedor' : 'Novo Fornecedor';
    ['nome','telefone','email','contato'].forEach(f => document.getElementById('f-'+f).value = '');
    document.getElementById('f-ativo').checked = true;
    if (id) {
        fetch(`api.php?id=${id}`).then(r=>r.json()).then(({data}) => {
            document.getElementById('f-nome').value     = data.nome;
            document.getElementById('f-telefone').value = data.telefone||'';
            document.getElementById('f-email').value    = data.email||'';
            document.getElementById('f-contato').value  = data.contato||'';
            document.getElementById('f-ativo').checked  = data.ativo==1;
            document.getElementById('modal').classList.remove('hidden');
        });
    } else { document.getElementById('modal').classList.remove('hidden'); setTimeout(()=>document.getElementById('f-nome').focus(),50); }
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const id   = parseInt(document.getElementById('modal-id').value);
    const nome = document.getElementById('f-nome').value.trim();
    if (!nome) { showToast('Nome é obrigatório.','error'); return; }
    const payload = { id, nome, telefone: document.getElementById('f-telefone').value, email: document.getElementById('f-email').value, contato: document.getElementById('f-contato').value, ativo: document.getElementById('f-ativo').checked?1:0 };
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
    confirmDialog('Excluir Fornecedor', `Deseja excluir "${label}"?`, async () => {
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
