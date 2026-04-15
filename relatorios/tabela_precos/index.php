<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Relatório de Tabela de Preços';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Relatório de Tabela de Preços</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Preços finais por produto e tabela de precificação</p>
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

<!-- Aviso sem dados -->
<div id="no-precos-warning" class="hidden bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-4 mb-5 flex items-start gap-3">
    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
    <div>
        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Nenhum produto com formação de preço cadastrada.</p>
        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-0.5">
            Acesse <a href="<?= APP_URL ?>/comercial/formacao_preco/index.php" class="underline font-medium">Formação de Preço</a> para configurar os preços dos produtos.
        </p>
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
        <select id="f-categoria" onchange="loadData()"
            class="py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todas as categorias</option>
        </select>
        <select id="f-tabela" onchange="loadData()"
            class="py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todas as tabelas</option>
        </select>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
</div>

<!-- Tabela (colunas dinâmicas) -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="main-table">
            <thead id="table-head">
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-16">Unid.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Custo Unit.</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Preço Base</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-24">Margem %</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let tabelasCache  = [];
let searchTimeout;
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtBRL(v){ v=parseFloat(v)||0; return 'R$ '+v.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
function fmtPct(v){ return parseFloat(v||0).toFixed(2).replace('.',',')+' %'; }

function buildParams() {
    const q   = encodeURIComponent(document.getElementById('search').value);
    const cat = document.getElementById('f-categoria').value;
    const tab = document.getElementById('f-tabela').value;
    return `q=${q}&cat=${cat}&tabela_id=${tab}`;
}

async function loadData() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        const thead = document.getElementById('table-head');
        try {
            const res  = await fetch(`export.php?format=json&${buildParams()}`);
            const json = await res.json();
            const data     = json.data       || [];
            const tabelas  = json.tabelas    || [];
            const cats     = json.categorias || [];
            tabelasCache = tabelas;

            /* Preenche selects na 1ª carga */
            const selCat = document.getElementById('f-categoria');
            if (selCat.options.length === 1 && cats.length) {
                cats.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id; o.textContent = c.nome;
                    selCat.appendChild(o);
                });
            }
            const selTab = document.getElementById('f-tabela');
            if (selTab.options.length === 1 && tabelas.length) {
                tabelas.forEach(t => {
                    const o = document.createElement('option');
                    o.value = t.id;
                    o.textContent = t.nome + ' (×' + parseFloat(t.multiplicador).toFixed(2).replace('.',',') + ')';
                    selTab.appendChild(o);
                });
            }

            /* Aviso sem dados */
            document.getElementById('no-precos-warning').classList.toggle('hidden', data.length > 0 || !json.success);

            /* Determina quais tabelas exibir */
            const tabFiltro = parseInt(document.getElementById('f-tabela').value) || 0;
            const tabelasExibir = tabFiltro ? tabelas.filter(t => t.id == tabFiltro) : tabelas;

            /* Reconstrói cabeçalho com colunas dinâmicas */
            thead.innerHTML = `<tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Categoria</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-16">Unid.</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Custo Unit.</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-32">Preço Base</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right w-24">Margem %</th>
                ${tabelasExibir.map(t => `
                <th class="px-4 py-3 font-medium text-blue-500 dark:text-blue-400 text-right whitespace-nowrap">
                    ${esc(t.nome)}
                    <span class="text-gray-400 font-normal text-xs ml-1">×${parseFloat(t.multiplicador).toFixed(2).replace('.',',')}</span>
                </th>`).join('')}
            </tr>`;

            document.getElementById('count').textContent = `${data.length} produto(s)`;

            if (!data.length) {
                const cols = 6 + tabelasExibir.length;
                tbody.innerHTML = `<tr><td colspan="${cols}" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum produto encontrado.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(p => {
                const m = parseFloat(p.perc_margem_liquida) || 0;
                let margemCls;
                if (m >= 15)     margemCls = 'text-green-600 dark:text-green-400 font-bold';
                else if (m >= 5) margemCls = 'text-yellow-600 dark:text-yellow-400 font-bold';
                else             margemCls = 'text-red-500 font-bold';

                const custoFmt = p.custo_unitario !== null
                    ? fmtBRL(p.custo_unitario)
                    : '<span class="text-gray-400 text-xs">sem custo</span>';

                const precosCols = tabelasExibir.map(t => {
                    const preco = p.precos ? p.precos[t.id] : (parseFloat(p.valor_venda) * parseFloat(t.multiplicador));
                    return `<td class="px-4 py-3 text-right font-semibold text-blue-600 dark:text-blue-400">${fmtBRL(preco)}</td>`;
                }).join('');

                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(p.produto_nome)}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${esc(p.categoria_nome||'—')}</td>
                    <td class="px-4 py-3 text-center font-mono text-xs text-gray-500 dark:text-gray-400">${esc(p.unidade_sigla)}</td>
                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">${custoFmt}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">${fmtBRL(p.valor_venda)}</td>
                    <td class="px-4 py-3 text-right ${margemCls}">${fmtPct(m)}</td>
                    ${precosCols}
                </tr>`;
            }).join('');
        } catch(e) {
            const cols = 6 + tabelasCache.length;
            tbody.innerHTML = `<tr><td colspan="${cols}" class="px-4 py-8 text-center text-red-400">Erro ao carregar.</td></tr>`;
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
