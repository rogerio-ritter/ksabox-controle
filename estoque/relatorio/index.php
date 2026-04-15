<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Saldo de Estoque';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Saldo de Estoque</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Posição atual de inventário por produto</p>
    </div>
    <button onclick="loadData()" class="flex items-center gap-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
        <i class="fas fa-sync-alt"></i> Atualizar
    </button>
</div>

<!-- Cards de resumo -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total de Produtos</p>
        <p id="card-total" class="text-2xl font-bold text-gray-800 dark:text-white">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Com Saldo</p>
        <p id="card-com-saldo" class="text-2xl font-bold text-green-600 dark:text-green-400">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Sem Saldo</p>
        <p id="card-sem-saldo" class="text-2xl font-bold text-red-500 dark:text-red-400">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Valor do Estoque</p>
        <p id="card-valor" class="text-xl font-bold text-blue-600 dark:text-blue-400">—</p>
        <p class="text-xs text-gray-400 mt-0.5">itens c/ custo calculado</p>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-4">
    <div class="p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[180px] max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Produto ou categoria..."
                oninput="loadData()"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
            <button id="btn-todos"      onclick="setFiltro('todos')"      class="px-3 py-2 bg-blue-600 text-white font-medium">Todos</button>
            <button id="btn-com_saldo"  onclick="setFiltro('com_saldo')"  class="px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Com saldo</button>
            <button id="btn-sem_saldo"  onclick="setFiltro('sem_saldo')"  class="px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Sem saldo</button>
        </div>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
</div>

<!-- Tabela -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-16">Unid.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-28">Saldo Atual</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Custo Unit.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-36">Valor Estoque</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-28">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-28">Movimentar</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let filtroAtual = 'todos';
let searchTimeout;
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtBRL(v){ v=parseFloat(v)||0; return 'R$ '+v.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }

function setFiltro(f) {
    filtroAtual = f;
    ['todos','com_saldo','sem_saldo'].forEach(k => {
        const btn = document.getElementById(`btn-${k}`);
        btn.className = k === f
            ? 'px-3 py-2 bg-blue-600 text-white font-medium'
            : 'px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600';
    });
    loadData();
}

async function loadData() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        const q = encodeURIComponent(document.getElementById('search').value);
        try {
            const res  = await fetch(`api.php?q=${q}&filtro=${filtroAtual}`);
            const json = await res.json();
            const data = json.data  || [];
            const tots = json.totais || {};

            // Cards
            document.getElementById('card-total').textContent     = tots.total_itens ?? '—';
            document.getElementById('card-com-saldo').textContent = tots.com_saldo  ?? '—';
            document.getElementById('card-sem-saldo').textContent = tots.sem_saldo  ?? '—';
            document.getElementById('card-valor').textContent     = fmtBRL(tots.valor_total);
            document.getElementById('count').textContent          = `${data.length} produto(s)`;

            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum produto encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(r => {
                const saldo = parseFloat(r.saldo);
                const saldoFmt = saldo.toFixed(2).replace('.', ',');

                // Badge e cor de saldo
                let saldoCls, badgeTxt, badgeCls;
                if (saldo > 0) {
                    saldoCls = 'text-green-600 dark:text-green-400 font-bold';
                    badgeTxt = 'Em estoque';
                    badgeCls = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                } else if (saldo === 0) {
                    saldoCls = 'text-yellow-600 dark:text-yellow-400 font-bold';
                    badgeTxt = 'Zerado';
                    badgeCls = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                } else {
                    saldoCls = 'text-red-500 font-bold';
                    badgeTxt = 'Negativo';
                    badgeCls = 'bg-red-100 text-red-700';
                }

                const custoFmt = r.custo_unitario !== null ? fmtBRL(r.custo_unitario) : '<span class="text-gray-400 text-xs">sem custo</span>';
                const valorFmt = r.valor_estoque  !== null ? `<span class="font-semibold text-blue-600 dark:text-blue-400">${fmtBRL(r.valor_estoque)}</span>` : '<span class="text-gray-400 text-xs">—</span>';

                const APP_URL = '<?= APP_URL ?>';
                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(r.produto_nome)}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${esc(r.categoria_nome||'—')}</td>
                    <td class="px-4 py-3 text-center font-mono text-xs text-gray-500 dark:text-gray-400">${esc(r.unidade_sigla)}</td>
                    <td class="px-4 py-3 text-right ${saldoCls}">${saldoFmt}</td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">${custoFmt}</td>
                    <td class="px-4 py-3 text-right">${valorFmt}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${badgeCls}">${badgeTxt}</span>
                    </td>
                    <td class="px-4 py-3 text-center space-x-1">
                        <a href="${APP_URL}/estoque/entrada/index.php" title="Entrada"
                            class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 hover:bg-green-200 text-green-600 text-xs">
                            <i class="fas fa-plus"></i>
                        </a>
                        <a href="${APP_URL}/estoque/saida/index.php" title="Saída"
                            class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-orange-100 hover:bg-orange-200 text-orange-600 text-xs">
                            <i class="fas fa-minus"></i>
                        </a>
                    </td>
                </tr>`;
            }).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>';
        }
    }, 200);
}

document.addEventListener('DOMContentLoaded', loadData);
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
