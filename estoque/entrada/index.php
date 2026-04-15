<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Entrada de Estoque';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Entrada de Estoque</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Registro de entradas e reposição de produtos</p>
    </div>
    <button onclick="openModal()"
        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-arrow-down mr-1"></i> Nova Entrada
    </button>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-4">
    <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[180px] max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Produto ou referência..."
                oninput="loadData()"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <input id="f-de" type="date" onchange="loadData()"
            class="py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span class="text-sm text-gray-400">até</span>
        <input id="f-ate" type="date" onchange="loadData()"
            class="py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
</div>

<!-- Tabela -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Data</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-28">Quantidade</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-36">Referência</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Observação</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-32">Registrado por</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-16 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova Entrada -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="fas fa-arrow-down text-green-500"></i> Nova Entrada de Estoque
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">

            <div>
                <label class="lbl">Produto <span class="text-red-500">*</span></label>
                <select id="f-produto_id" class="inp" onchange="onProdutoChange()">
                    <option value="">Selecione...</option>
                </select>
                <p id="saldo-hint" class="text-xs text-gray-400 mt-1 hidden">
                    Saldo atual: <span id="saldo-atual" class="font-medium text-gray-600 dark:text-gray-300"></span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lbl">Quantidade <span class="text-red-500">*</span></label>
                    <input id="f-quantidade" type="number" min="0.01" step="0.01" placeholder="0.00" class="inp">
                </div>
                <div>
                    <label class="lbl">Data <span class="text-red-500">*</span></label>
                    <input id="f-data" type="date" class="inp">
                </div>
            </div>

            <div>
                <label class="lbl">Referência <span class="text-xs text-gray-400">(NF, pedido, etc.)</span></label>
                <input id="f-referencia" type="text" placeholder="Ex: NF 001234" class="inp">
            </div>

            <div>
                <label class="lbl">Observação</label>
                <textarea id="f-observacao" rows="2" placeholder="Notas adicionais..." class="inp resize-none"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeModal()" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                Cancelar
            </button>
            <button id="btn-save" onclick="saveForm()"
                class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-check mr-1"></i> Registrar Entrada
            </button>
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
let produtosData = [];
let searchTimeout;
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function dateBR(d){ if(!d) return '—'; const [y,m,dia]=d.split('-'); return `${dia}/${m}/${y}`; }

async function carregaSelects() {
    const res  = await fetch('api.php?selects=1');
    const json = await res.json();
    produtosData = json.produtos || [];
    const sel = document.getElementById('f-produto_id');
    sel.innerHTML = '<option value="">Selecione...</option>' +
        produtosData.map(p =>
            `<option value="${p.id}" data-saldo="${p.saldo}" data-unidade="${esc(p.unidade_sigla)}">${esc(p.nome)}</option>`
        ).join('');
}

function onProdutoChange() {
    const sel = document.getElementById('f-produto_id');
    const opt = sel.selectedOptions[0];
    const saldo = opt?.dataset.saldo;
    const unid  = opt?.dataset.unidade || '';
    const hint  = document.getElementById('saldo-hint');
    if (saldo !== undefined && opt?.value) {
        document.getElementById('saldo-atual').textContent = parseFloat(saldo).toFixed(2) + ' ' + unid;
        hint.classList.remove('hidden');
    } else {
        hint.classList.add('hidden');
    }
}

function loadData() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        const q   = encodeURIComponent(document.getElementById('search').value);
        const de  = document.getElementById('f-de').value;
        const ate = document.getElementById('f-ate').value;
        try {
            const res  = await fetch(`api.php?q=${q}&de=${de}&ate=${ate}`);
            const json = await res.json();
            const data = json.data || [];
            document.getElementById('count').textContent = `${data.length} registro(s)`;
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhuma entrada encontrada.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(r => `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">${dateBR(r.data_movimentacao)}</td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(r.produto_nome)}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="font-semibold text-green-600 dark:text-green-400">+${parseFloat(r.quantidade).toFixed(2)}</span>
                        <span class="text-xs text-gray-400 ml-1">${esc(r.unidade_sigla)}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">${esc(r.referencia||'—')}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.observacao||'—')}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.usuario_nome)}</td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="cancelar(${r.id}, '${esc(r.produto_nome)}', ${r.quantidade})"
                            class="text-red-400 hover:text-red-600" title="Cancelar entrada">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </td>
                </tr>`).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>';
        }
    }, 200);
}

function openModal() {
    ['f-produto_id','f-quantidade','f-referencia','f-observacao'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('f-data').value = new Date().toISOString().split('T')[0];
    document.getElementById('saldo-hint').classList.add('hidden');
    document.getElementById('modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('f-produto_id').focus(), 50);
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const pid = parseInt(document.getElementById('f-produto_id').value) || 0;
    const qtd = parseFloat(document.getElementById('f-quantidade').value) || 0;
    const dt  = document.getElementById('f-data').value;
    if (!pid) { showToast('Selecione um produto.', 'error'); return; }
    if (qtd <= 0) { showToast('Quantidade deve ser maior que zero.', 'error'); return; }
    if (!dt)  { showToast('Informe a data.', 'error'); return; }

    const payload = {
        produto_id:         pid,
        quantidade:         qtd,
        data_movimentacao:  dt,
        referencia:         document.getElementById('f-referencia').value,
        observacao:         document.getElementById('f-observacao').value,
    };

    const btn = document.getElementById('btn-save');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            closeModal();
            carregaSelects(); // atualiza saldos nos selects
            loadData();
        } else showToast(data.message, 'error');
    } finally { setLoading(btn, false); }
}

function cancelar(id, nome, qtd) {
    confirmDialog('Cancelar Entrada',
        `Cancelar a entrada de ${parseFloat(qtd).toFixed(2)} de "${nome}"? O saldo será ajustado.`,
        async () => {
            const res  = await fetch(`api.php?id=${id}`, { method:'DELETE' });
            const data = await res.json();
            if (data.success) { showToast(data.message); carregaSelects(); loadData(); }
            else showToast(data.message, 'error');
        });
}

document.getElementById('modal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
document.addEventListener('DOMContentLoaded', () => { carregaSelects(); loadData(); });
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
