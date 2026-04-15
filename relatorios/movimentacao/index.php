<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Relatório de Movimentação';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Relatório de Movimentação</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Histórico de entradas e saídas de estoque</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="exportar('pdf')"
            class="flex items-center gap-2 text-sm border border-red-300 dark:border-red-700 rounded-lg px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <button onclick="exportar('xls')"
            class="flex items-center gap-2 text-sm border border-green-300 dark:border-green-700 rounded-lg px-3 py-2 hover:bg-green-50 dark:hover:bg-green-900/30 text-green-600 dark:text-green-400">
            <i class="fas fa-file-excel"></i> XLS
        </button>
        <button onclick="loadData()"
            class="flex items-center gap-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
    </div>
</div>

<!-- Cards de resumo -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total de Registros</p>
        <p id="card-total" class="text-2xl font-bold text-gray-800 dark:text-white">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Entradas</p>
        <p id="card-entradas" class="text-2xl font-bold text-green-600 dark:text-green-400">—</p>
        <p id="card-qtd-entradas" class="text-xs text-gray-400 mt-0.5">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Saídas</p>
        <p id="card-saidas" class="text-2xl font-bold text-orange-500 dark:text-orange-400">—</p>
        <p id="card-qtd-saidas" class="text-xs text-gray-400 mt-0.5">—</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Saldo do Período</p>
        <p id="card-saldo" class="text-xl font-bold text-gray-800 dark:text-white">—</p>
    </div>
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
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
            <button id="btn-todos"   onclick="setTipo('todos')"   class="px-3 py-2 bg-blue-600 text-white font-medium">Todos</button>
            <button id="btn-entrada" onclick="setTipo('entrada')" class="px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Entradas</button>
            <button id="btn-saida"   onclick="setTipo('saida')"   class="px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Saídas</button>
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
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Data</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">Tipo</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-28">Quantidade</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-36">Referência</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Observação</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-32">Registrado por</th>
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

<script>
let tipoAtual = 'todos';
let searchTimeout;
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function dateBR(d){ if(!d) return '—'; const [y,m,dia]=d.split('-'); return `${dia}/${m}/${y}`; }
function fmtNum(v){ return parseFloat(v||0).toFixed(2).replace('.',','); }

function setTipo(t) {
    tipoAtual = t;
    ['todos','entrada','saida'].forEach(k => {
        const btn = document.getElementById(`btn-${k}`);
        btn.className = k === t
            ? 'px-3 py-2 bg-blue-600 text-white font-medium'
            : 'px-3 py-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600';
    });
    loadData();
}

function buildParams() {
    const q   = encodeURIComponent(document.getElementById('search').value);
    const de  = document.getElementById('f-de').value;
    const ate = document.getElementById('f-ate').value;
    return `q=${q}&de=${de}&ate=${ate}&tipo=${tipoAtual}`;
}

async function loadData() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        try {
            const res  = await fetch(`export.php?format=json&${buildParams()}`);
            const json = await res.json();
            const data = json.data   || [];
            const tots = json.totais || {};

            document.getElementById('card-total').textContent        = tots.total_geral    ?? '—';
            document.getElementById('card-entradas').textContent     = tots.total_entradas ?? '—';
            document.getElementById('card-saidas').textContent       = tots.total_saidas   ?? '—';
            document.getElementById('card-qtd-entradas').textContent = `Qtd: +${fmtNum(tots.qtd_entradas)}`;
            document.getElementById('card-qtd-saidas').textContent   = `Qtd: -${fmtNum(tots.qtd_saidas)}`;

            const saldo = (parseFloat(tots.qtd_entradas||0) - parseFloat(tots.qtd_saidas||0));
            const cardSaldo = document.getElementById('card-saldo');
            cardSaldo.textContent = (saldo >= 0 ? '+' : '') + fmtNum(saldo);
            cardSaldo.className   = saldo >= 0
                ? 'text-xl font-bold text-green-600 dark:text-green-400'
                : 'text-xl font-bold text-red-500 dark:text-red-400';

            document.getElementById('count').textContent = `${data.length} registro(s)`;

            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhuma movimentação encontrada.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(r => {
                const isEnt = r.tipo === 'entrada';
                const qtdFmt = (isEnt ? '+' : '-') + fmtNum(r.quantidade);
                const qtdCls = isEnt
                    ? 'font-semibold text-green-600 dark:text-green-400'
                    : 'font-semibold text-orange-500 dark:text-orange-400';
                const badge = isEnt
                    ? '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Entrada</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Saída</span>';
                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">${dateBR(r.data_movimentacao)}</td>
                    <td class="px-4 py-3 text-center">${badge}</td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                        ${esc(r.produto_nome)}
                        <span class="text-xs text-gray-400 ml-1">${esc(r.unidade_sigla)}</span>
                    </td>
                    <td class="px-4 py-3 text-right ${qtdCls}">${qtdFmt}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">${esc(r.referencia||'—')}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.observacao||'—')}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.usuario_nome)}</td>
                </tr>`;
            }).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>';
        }
    }, 200);
}

function exportar(format) {
    if (format === 'pdf') {
        window.open(`export.php?format=pdf&${buildParams()}`, '_blank');
    } else {
        window.location.href = `export.php?format=xls&${buildParams()}`;
    }
}

document.addEventListener('DOMContentLoaded', loadData);
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
