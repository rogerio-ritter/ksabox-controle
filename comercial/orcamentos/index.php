<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Orçamentos';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Orçamentos</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Criação e acompanhamento de propostas comerciais</p>
    </div>
    <a href="form.php"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Novo Orçamento
    </a>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-4">
    <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Número ou cliente..."
                oninput="loadData()"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select id="filter-status" onchange="loadData()"
            class="py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos os status</option>
            <option value="Rascunho">Rascunho</option>
            <option value="Enviado">Enviado</option>
            <option value="Aprovado">Aprovado</option>
            <option value="Rejeitado">Rejeitado</option>
            <option value="Cancelado">Cancelado</option>
        </select>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
</div>

<!-- Tabela -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-40">Número</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Data</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Validade</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Total Geral</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-32 text-center">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-32 text-right">Ações</th>
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

<!-- Modal Alterar Status -->
<div id="modal-status" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Alterar Status</h3>
        <p id="ms-numero" class="text-sm text-gray-500 dark:text-gray-400 mb-4"></p>
        <input type="hidden" id="ms-id">
        <div class="grid grid-cols-1 gap-2">
            <?php foreach(['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'] as $s): ?>
            <button onclick="confirmarStatus('<?= $s ?>')"
                class="w-full text-left px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600
                       hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300
                       flex items-center gap-3 transition-colors">
                <span class="status-dot-<?= strtolower($s) ?> w-2.5 h-2.5 rounded-full inline-block"></span>
                <?= $s ?>
            </button>
            <?php endforeach; ?>
        </div>
        <button onclick="document.getElementById('modal-status').classList.add('hidden')"
            class="mt-4 w-full py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg
                   hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400">
            Cancelar
        </button>
    </div>
</div>

<style>
.status-dot-rascunho  { background:#6b7280; }
.status-dot-enviado   { background:#3b82f6; }
.status-dot-aprovado  { background:#22c55e; }
.status-dot-rejeitado { background:#ef4444; }
.status-dot-cancelado { background:#eab308; }
</style>

<script>
let searchTimeout;
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtBRL(v){ v=parseFloat(v)||0; return 'R$ '+v.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }

const BADGE = {
    Rascunho:  'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    Enviado:   'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    Aprovado:  'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    Rejeitado: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    Cancelado: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
};

function loadData() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody  = document.getElementById('table-body');
        const q      = encodeURIComponent(document.getElementById('search').value);
        const status = encodeURIComponent(document.getElementById('filter-status').value);
        try {
            const res  = await fetch(`api.php?q=${q}&status=${status}`);
            const json = await res.json();
            const data = json.data || [];
            document.getElementById('count').textContent = `${data.length} orçamento(s)`;
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum orçamento encontrado.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(r => {
                const cls = BADGE[r.status] || BADGE.Rascunho;
                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-mono font-medium text-gray-800 dark:text-gray-200">${esc(r.numero)}</td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">${esc(r.cliente_nome)}</td>
                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">${dateBR(r.data_criacao)}</td>
                    <td class="px-4 py-3 text-center ${isVencido(r.validade, r.status) ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400'}">${dateBR(r.validade)}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">${fmtBRL(r.total_geral)}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="abrirModalStatus(${r.id},'${esc(r.numero)}','${esc(r.status)}')"
                            class="px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer hover:opacity-80 ${cls}">
                            ${esc(r.status)}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="visualizar.php?id=${r.id}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" title="Visualizar">
                            <i class="fas fa-eye"></i></a>
                        <a href="form.php?id=${r.id}" class="text-blue-500 hover:text-blue-700" title="Editar">
                            <i class="fas fa-edit"></i></a>
                        <a href="pdf.php?id=${r.id}" target="_blank" class="text-green-500 hover:text-green-700" title="PDF">
                            <i class="fas fa-file-pdf"></i></a>
                        <button onclick="excluir(${r.id},'${esc(r.numero)}')" class="text-red-400 hover:text-red-600" title="Excluir">
                            <i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            }).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>';
        }
    }, 200);
}

function dateBR(d) {
    if (!d) return '—';
    const [y,m,dia] = d.split('-');
    return `${dia}/${m}/${y}`;
}

function isVencido(validade, status) {
    if (!validade || ['Aprovado','Cancelado','Rejeitado'].includes(status)) return false;
    return new Date(validade) < new Date();
}

function abrirModalStatus(id, numero, statusAtual) {
    document.getElementById('ms-id').value = id;
    document.getElementById('ms-numero').textContent = `Orçamento ${numero} — atual: ${statusAtual}`;
    document.getElementById('modal-status').classList.remove('hidden');
}

async function confirmarStatus(novoStatus) {
    const id = parseInt(document.getElementById('ms-id').value);
    const res = await fetch(`api.php?action=status&id=${id}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: novoStatus })
    });
    const data = await res.json();
    document.getElementById('modal-status').classList.add('hidden');
    if (data.success) { showToast(data.message); loadData(); }
    else showToast(data.message, 'error');
}

function excluir(id, numero) {
    confirmDialog('Excluir Orçamento', `Excluir o orçamento "${numero}"? Todos os itens serão removidos.`, async () => {
        const res  = await fetch(`api.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        data.success ? (showToast(data.message), loadData()) : showToast(data.message, 'error');
    });
}

document.addEventListener('DOMContentLoaded', loadData);
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
