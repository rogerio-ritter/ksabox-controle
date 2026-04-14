<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Produtos';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Produtos</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Catálogo de produtos para orçamentos e estoque</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Novo Produto
    </button>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Buscar produto, referência ou categoria..."
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
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-20 text-center">Unidade</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28">Referência</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Fornecedor</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Produto -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="modal-title" class="font-semibold text-gray-800 dark:text-white">Novo Produto</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="modal-id" value="0">

            <div>
                <label class="lbl">Nome do Produto <span class="text-red-500">*</span></label>
                <input id="f-nome" type="text" placeholder="Nome completo do produto" class="inp">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="lbl">Categoria <span class="text-red-500">*</span></label>
                    <select id="f-categoria_id" class="inp">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div>
                    <label class="lbl">Unidade <span class="text-red-500">*</span></label>
                    <select id="f-unidade_sigla" class="inp">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div>
                    <label class="lbl">Fornecedor</label>
                    <select id="f-fornecedor_id" class="inp">
                        <option value="">Nenhum</option>
                    </select>
                </div>
                <div>
                    <label class="lbl">Referência</label>
                    <input id="f-referencia" type="text" placeholder="Código de referência" class="inp">
                </div>
            </div>

            <div>
                <label class="lbl">Descrição</label>
                <textarea id="f-descricao" rows="2" placeholder="Descrição resumida do produto"
                    class="inp resize-none"></textarea>
            </div>
            <div>
                <label class="lbl">Especificações Técnicas</label>
                <textarea id="f-especificacoes" rows="3" placeholder="Detalhes técnicos, dimensões, materiais..."
                    class="inp resize-none"></textarea>
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

<style>
.lbl { display:block; font-size:.875rem; font-weight:500; color:#374151; margin-bottom:.25rem; }
.dark .lbl { color:#d1d5db; }
.inp { width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:.5rem; font-size:.875rem; background:#fff; outline:none; transition:.15s; }
.inp:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.3); }
.dark .inp { background:#374151; border-color:#4b5563; color:#f3f4f6; }
</style>

<script>
let searchTimeout;
let selects = { categorias: [], unidades: [], fornecedores: [] };
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Carrega os selects de categoria/unidade/fornecedor
async function carregaSelects() {
    const res  = await fetch('api.php?selects=1');
    const json = await res.json();
    selects = json;

    const cat = document.getElementById('f-categoria_id');
    cat.innerHTML = '<option value="">Selecione...</option>' + json.categorias.map(c => `<option value="${c.id}">${esc(c.nome)}</option>`).join('');

    const uni = document.getElementById('f-unidade_sigla');
    uni.innerHTML = '<option value="">Selecione...</option>' + json.unidades.map(u => `<option value="${u.sigla}">${esc(u.sigla)} — ${esc(u.nome)}</option>`).join('');

    const forn = document.getElementById('f-fornecedor_id');
    forn.innerHTML = '<option value="">Nenhum</option>' + json.fornecedores.map(f => `<option value="${f.id}">${esc(f.nome)}</option>`).join('');
}

function loadData(q = '') {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        try {
            const res  = await fetch(`api.php?q=${encodeURIComponent(q)}`);
            const json = await res.json();
            const data = json.data || [];
            document.getElementById('count').textContent = `${data.length} produto(s)`;
            if (!data.length) { tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum produto encontrado.</td></tr>'; return; }
            tbody.innerHTML = data.map(r => `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(r.nome)}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.categoria_nome||'—')}</td>
                    <td class="px-4 py-3 text-center"><span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">${esc(r.unidade_sigla)}</span></td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono">${esc(r.referencia||'—')}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.fornecedor_nome||'—')}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${r.ativo==1?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'}">${r.ativo==1?'Ativo':'Inativo'}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="openModal(${r.id})" class="text-blue-500 hover:text-blue-700 mr-3"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteItem(${r.id},'${esc(r.nome)}')" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`).join('');
        } catch(e) { tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>'; }
    }, 200);
}

function openModal(id = 0) {
    document.getElementById('modal-id').value = id;
    document.getElementById('modal-title').textContent = id ? 'Editar Produto' : 'Novo Produto';
    ['nome','referencia','descricao','especificacoes'].forEach(f => document.getElementById('f-'+f).value = '');
    document.getElementById('f-categoria_id').value  = '';
    document.getElementById('f-unidade_sigla').value = '';
    document.getElementById('f-fornecedor_id').value = '';
    document.getElementById('f-ativo').checked = true;

    if (id) {
        fetch(`api.php?id=${id}`).then(r=>r.json()).then(({data}) => {
            document.getElementById('f-nome').value          = data.nome;
            document.getElementById('f-categoria_id').value  = data.categoria_id;
            document.getElementById('f-unidade_sigla').value = data.unidade_sigla;
            document.getElementById('f-fornecedor_id').value = data.fornecedor_id||'';
            document.getElementById('f-referencia').value    = data.referencia||'';
            document.getElementById('f-descricao').value     = data.descricao||'';
            document.getElementById('f-especificacoes').value = data.especificacoes||'';
            document.getElementById('f-ativo').checked = data.ativo==1;
            document.getElementById('modal').classList.remove('hidden');
        });
    } else { document.getElementById('modal').classList.remove('hidden'); setTimeout(()=>document.getElementById('f-nome').focus(),50); }
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const id = parseInt(document.getElementById('modal-id').value);
    const nome = document.getElementById('f-nome').value.trim();
    const categoria_id  = document.getElementById('f-categoria_id').value;
    const unidade_sigla = document.getElementById('f-unidade_sigla').value;
    if (!nome)          { showToast('Nome é obrigatório.','error'); return; }
    if (!categoria_id)  { showToast('Selecione a categoria.','error'); return; }
    if (!unidade_sigla) { showToast('Selecione a unidade.','error'); return; }
    const payload = {
        id, nome, categoria_id: parseInt(categoria_id), unidade_sigla,
        fornecedor_id: parseInt(document.getElementById('f-fornecedor_id').value)||null,
        referencia: document.getElementById('f-referencia').value,
        descricao: document.getElementById('f-descricao').value,
        especificacoes: document.getElementById('f-especificacoes').value,
        ativo: document.getElementById('f-ativo').checked?1:0
    };
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
    confirmDialog('Excluir Produto', `Deseja excluir "${label}"? Esta ação também removerá o estoque e custos associados.`, async () => {
        const res  = await fetch(`api.php?id=${id}`, {method:'DELETE'});
        const data = await res.json();
        data.success ? (showToast(data.message), loadData()) : showToast(data.message,'error');
    });
}

document.getElementById('modal').addEventListener('click', e => { if (e.target===e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });
document.addEventListener('DOMContentLoaded', () => { carregaSelects(); loadData(); });
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
