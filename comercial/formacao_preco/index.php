<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Formação de Preço';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Formação de Preço</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Precificação com base no custo de importação</p>
    </div>
    <button onclick="openModal(0)"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Formar Preço
    </button>
</div>

<!-- Aviso: apenas produtos com custo calculado aparecem -->
<div id="aviso-sem-custo" class="hidden mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg text-sm text-yellow-700 dark:text-yellow-300">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Nenhum produto com custo calculado encontrado. <a href="<?= APP_URL ?>/comercial/custo/index.php" class="underline font-medium">Calcule o custo primeiro</a>.
</div>

<!-- Tabela -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input id="search" type="text" placeholder="Buscar produto ou categoria..."
                oninput="loadData(this.value)"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <span id="count" class="text-sm text-gray-500 dark:text-gray-400 ml-auto"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Custo Unitário</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Preço de Venda</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Margem Líquida</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-center w-28">% Margem</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Formação de Preço -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-5xl my-4">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10 rounded-t-xl">
            <h3 id="modal-title" class="font-semibold text-gray-800 dark:text-white">Formar Preço de Produto</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-6">
            <input type="hidden" id="modal-produto_id" value="0">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Formulário (2/3) -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Identificação -->
                    <div>
                        <h4 class="sec-title">Identificação</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="lbl">Produto <span class="text-red-500">*</span></label>
                                <select id="f-produto_id" class="inp" onchange="onProdutoChange()">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl">Custo Unitário (R$)</label>
                                <input id="f-custo_unitario" type="number" readonly tabindex="-1"
                                    class="inp bg-gray-100 dark:bg-gray-600 cursor-not-allowed text-gray-500 dark:text-gray-400">
                            </div>
                            <div>
                                <label class="lbl">ICMS Custo Unitário (R$)</label>
                                <input id="f-icms_custo_unitario" type="number" readonly tabindex="-1"
                                    class="inp bg-gray-100 dark:bg-gray-600 cursor-not-allowed text-gray-500 dark:text-gray-400">
                            </div>
                        </div>
                    </div>

                    <!-- Preço de Venda -->
                    <div>
                        <h4 class="sec-title">Preço de Venda</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="lbl">Valor de Venda (R$) <span class="text-red-500">*</span></label>
                                <input id="f-valor_venda" type="number" min="0.01" step="0.01" placeholder="0.00"
                                    class="inp text-lg font-semibold" oninput="recalcular()">
                            </div>
                            <div class="flex items-end">
                                <button type="button" onclick="sugerirVenda()"
                                    class="w-full px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-magic text-blue-500"></i>
                                    Sugerir (custo × 2)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Composição Material / Serviço -->
                    <div>
                        <h4 class="sec-title">Composição do Produto</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="lbl">% Material <span class="text-red-500">*</span></label>
                                <input id="f-perc_material" type="number" min="0" max="100" step="0.01" placeholder="70.00"
                                    class="inp" oninput="recalcular()">
                                <p class="text-xs text-gray-400 mt-1">% Serviço = <span id="info-perc_servico">30,00</span>%</p>
                            </div>
                            <div>
                                <label class="lbl">Valor de Montagem (R$)</label>
                                <input id="f-valor_montagem" type="number" min="0" step="0.01" placeholder="0.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <!-- Despesas de Venda -->
                    <div>
                        <h4 class="sec-title">Despesas de Venda</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="lbl">% Desp. Admin.</label>
                                <input id="f-perc_desp_admin" type="number" min="0" step="0.01" placeholder="5.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Desp. Fixas</label>
                                <input id="f-perc_desp_fixas" type="number" min="0" step="0.01" placeholder="3.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Comissão Venda</label>
                                <input id="f-perc_comissao_venda" type="number" min="0" step="0.01" placeholder="2.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Pós-venda</label>
                                <input id="f-perc_pos_venda" type="number" min="0" step="0.01" placeholder="1.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <!-- Impostos sobre Venda -->
                    <div>
                        <h4 class="sec-title">Impostos sobre Venda</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="lbl">% ICMS Venda</label>
                                <input id="f-perc_icms_venda" type="number" min="0" step="0.01" placeholder="19.50"
                                    class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Imp. Interno Material</label>
                                <input id="f-perc_imp_interno_material" type="number" min="0" step="0.01" placeholder="3.65"
                                    class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Imp. Interno Serviço</label>
                                <input id="f-perc_imp_interno_servico" type="number" min="0" step="0.01" placeholder="5.00"
                                    class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Resumo (1/3) -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 sticky top-24">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fas fa-chart-pie text-blue-500"></i> Demonstrativo
                        </h4>

                        <!-- Receita -->
                        <div class="mb-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Receita</p>
                            <div class="resumo-row text-sm font-semibold text-gray-700 dark:text-gray-200">
                                <span>Preço de Venda</span>
                                <span id="r-valor_venda">R$ 0,00</span>
                            </div>
                        </div>

                        <!-- Composição -->
                        <div class="mb-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Composição</p>
                            <div class="space-y-1 text-xs">
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Material</span><span id="r-valor_material">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Serviço</span><span id="r-valor_servico">R$ 0,00</span></div>
                            </div>
                        </div>

                        <!-- Deduções -->
                        <div class="mb-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">(-) Deduções</p>
                            <div class="space-y-1 text-xs">
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Custo Unitário</span><span id="r-custo_unitario">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Desp. Administrativas</span><span id="r-desp_admin">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Desp. Fixas</span><span id="r-desp_fixas">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Comissão Venda</span><span id="r-comissao_venda">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Pós-venda</span><span id="r-pos_venda">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">ICMS Venda</span><span id="r-icms_venda">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Imp. Int. Material</span><span id="r-imp_material">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Imp. Int. Serviço</span><span id="r-imp_servico">R$ 0,00</span></div>
                                <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Montagem</span><span id="r-montagem">R$ 0,00</span></div>
                            </div>
                        </div>

                        <!-- Totais -->
                        <div class="pt-3 border-t-2 border-gray-300 dark:border-gray-600 space-y-2">
                            <div class="resumo-row text-xs">
                                <span class="text-gray-600 dark:text-gray-400 font-medium">Total Despesas Venda</span>
                                <span id="r-total_desp" class="font-medium text-red-500">R$ 0,00</span>
                            </div>
                            <div class="resumo-row text-sm">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Margem Líquida</span>
                                <span id="r-margem_liquida" class="font-bold text-gray-800 dark:text-gray-200">R$ 0,00</span>
                            </div>
                            <!-- Barra de margem -->
                            <div class="mt-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500">% Margem Líquida</span>
                                    <span id="r-perc_margem_txt" class="font-bold">0,00%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                    <div id="r-perc_margem_bar" class="h-3 rounded-full transition-all duration-300 bg-gray-400"
                                         style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
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
.sec-title { font-size:.875rem; font-weight:600; color:#374151; padding-bottom:.375rem; border-bottom:1px solid #e5e7eb; margin-bottom:.75rem; }
.dark .sec-title { color:#d1d5db; border-color:#374151; }
.resumo-row { display:flex; justify-content:space-between; align-items:center; }
</style>

<script>
let produtosData = [];
let searchTimeout;

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtBRL(v) {
    v = parseFloat(v) || 0;
    return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function fmtPerc(v) { return (parseFloat(v) || 0).toFixed(2).replace('.', ',') + '%'; }
function fv(id) { return parseFloat(document.getElementById(id).value) || 0; }
function set(id, val) { document.getElementById(id).textContent = fmtBRL(val); }

/* ===== CÁLCULO (replica os GENERATED COLUMNS de formacao_precos) ===== */
function calcular() {
    const venda       = fv('f-valor_venda');
    const custo       = fv('f-custo_unitario');
    const icmsCusto   = fv('f-icms_custo_unitario');
    const pMat        = fv('f-perc_material');
    const pDespAdm    = fv('f-perc_desp_admin');
    const pDespFix    = fv('f-perc_desp_fixas');
    const pComis      = fv('f-perc_comissao_venda');
    const pPos        = fv('f-perc_pos_venda');
    const pICMSVenda  = fv('f-perc_icms_venda');
    const vMontagem   = fv('f-valor_montagem');
    const pImpMat     = fv('f-perc_imp_interno_material');
    const pImpSrv     = fv('f-perc_imp_interno_servico');

    const pServ       = 100 - pMat;
    const vMat        = venda * pMat / 100;
    const vServ       = venda - vMat;

    const vDespAdm    = venda * pDespAdm / 100;
    const vDespFix    = venda * pDespFix / 100;
    const vComis      = venda * pComis / 100;
    const vPos        = venda * pPos / 100;
    const vICMSVenda  = (venda * pICMSVenda / 100) - icmsCusto;
    const vImpMat     = vMat * pImpMat / 100;
    const vImpSrv     = vServ * pImpSrv / 100;

    const totalDesp   = vDespAdm + vDespFix + vComis + vPos + vICMSVenda + vImpMat + vImpSrv + vMontagem;
    const margem      = venda - custo - totalDesp;
    const percMargem  = venda > 0 ? (margem / venda) * 100 : 0;

    return { venda, custo, icmsCusto, pServ, vMat, vServ, vDespAdm, vDespFix, vComis, vPos, vICMSVenda, vImpMat, vImpSrv, vMontagem, totalDesp, margem, percMargem };
}

function recalcular() {
    const r = calcular();
    // % Serviço hint
    document.getElementById('info-perc_servico').textContent = (100 - fv('f-perc_material')).toFixed(2).replace('.', ',');

    set('r-valor_venda',    r.venda);
    set('r-valor_material', r.vMat);
    set('r-valor_servico',  r.vServ);
    set('r-custo_unitario', r.custo);
    set('r-desp_admin',     r.vDespAdm);
    set('r-desp_fixas',     r.vDespFix);
    set('r-comissao_venda', r.vComis);
    set('r-pos_venda',      r.vPos);
    set('r-icms_venda',     r.vICMSVenda);
    set('r-imp_material',   r.vImpMat);
    set('r-imp_servico',    r.vImpSrv);
    set('r-montagem',       r.vMontagem);
    set('r-total_desp',     r.totalDesp);
    set('r-margem_liquida', r.margem);

    // % Margem + barra colorida
    const pm = r.percMargem;
    document.getElementById('r-perc_margem_txt').textContent = fmtPerc(pm);
    const bar = document.getElementById('r-perc_margem_bar');
    bar.style.width = Math.min(Math.max(pm, 0), 100) + '%';
    if (pm >= 15)      { bar.className = 'h-3 rounded-full transition-all duration-300 bg-green-500'; }
    else if (pm >= 5)  { bar.className = 'h-3 rounded-full transition-all duration-300 bg-yellow-400'; }
    else               { bar.className = 'h-3 rounded-full transition-all duration-300 bg-red-500'; }
    document.getElementById('r-perc_margem_txt').className = pm >= 15 ? 'font-bold text-green-600 dark:text-green-400'
        : pm >= 5 ? 'font-bold text-yellow-600 dark:text-yellow-400' : 'font-bold text-red-600 dark:text-red-400';
}

function sugerirVenda() {
    const custo = fv('f-custo_unitario');
    if (custo > 0) {
        document.getElementById('f-valor_venda').value = (custo * 2).toFixed(2);
        recalcular();
    }
}

/* ===== SELECTS ===== */
async function carregaSelects() {
    const res  = await fetch('api.php?selects=1');
    const json = await res.json();
    produtosData = json.produtos || [];
    if (!produtosData.length) document.getElementById('aviso-sem-custo').classList.remove('hidden');
    const sel = document.getElementById('f-produto_id');
    sel.innerHTML = '<option value="">Selecione...</option>' +
        produtosData.map(p => `<option value="${p.id}">${esc(p.nome)}</option>`).join('');
}

function onProdutoChange() {
    const pid  = parseInt(document.getElementById('f-produto_id').value) || 0;
    const prod = produtosData.find(p => p.id == pid);
    if (!prod) {
        document.getElementById('f-custo_unitario').value     = '';
        document.getElementById('f-icms_custo_unitario').value = '';
        recalcular();
        return;
    }
    document.getElementById('f-custo_unitario').value      = parseFloat(prod.custo_unitario || 0).toFixed(4);
    document.getElementById('f-icms_custo_unitario').value = parseFloat(prod.icms_custo_unitario || 0).toFixed(4);
    recalcular();
}

/* ===== LISTAGEM ===== */
function loadData(q = '') {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        const tbody = document.getElementById('table-body');
        try {
            const res  = await fetch(`api.php?q=${encodeURIComponent(q)}`);
            const json = await res.json();
            const data = json.data || [];
            document.getElementById('count').textContent = `${data.length} produto(s)`;
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum produto com custo calculado.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(r => {
                const temPreco  = r.id != null;
                const custo     = fmtBRL(r.custo_unitario);
                const venda     = temPreco ? fmtBRL(r.valor_venda)     : '—';
                const margem    = temPreco ? fmtBRL(r.margem_liquida)  : '—';
                const pm        = temPreco ? parseFloat(r.perc_margem_liquida) : null;
                const badge     = temPreco
                    ? `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Formado</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pendente</span>`;
                let pmBadge = '—';
                if (pm !== null) {
                    const cls = pm >= 15 ? 'bg-green-100 text-green-700' : pm >= 5 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700';
                    pmBadge = `<span class="px-2 py-0.5 rounded-full text-xs font-semibold ${cls}">${pm.toFixed(2).replace('.', ',')}%</span>`;
                }
                const btnEdit  = temPreco
                    ? `<button onclick="openModal(${r.produto_id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Editar"><i class="fas fa-edit"></i></button>`
                    : `<button onclick="openModal(${r.produto_id})" class="text-green-500 hover:text-green-700 mr-3" title="Formar Preço"><i class="fas fa-chart-line"></i></button>`;
                const btnDel   = temPreco
                    ? `<button onclick="deleteItem(${r.id}, '${esc(r.produto_nome)}')" class="text-red-400 hover:text-red-600" title="Remover"><i class="fas fa-trash"></i></button>`
                    : '';
                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800 dark:text-gray-200">${esc(r.produto_nome)}</div>
                        <div class="text-xs mt-0.5">${badge}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.categoria_nome || '—')}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-600 dark:text-gray-400">${custo}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">${venda}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${margem}</td>
                    <td class="px-4 py-3 text-center">${pmBadge}</td>
                    <td class="px-4 py-3 text-right">${btnEdit}${btnDel}</td>
                </tr>`;
            }).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">Erro ao carregar dados.</td></tr>';
        }
    }, 200);
}

/* ===== MODAL ===== */
function resetForm() {
    ['f-produto_id','f-custo_unitario','f-icms_custo_unitario','f-valor_venda',
     'f-perc_material','f-valor_montagem',
     'f-perc_desp_admin','f-perc_desp_fixas','f-perc_comissao_venda','f-perc_pos_venda',
     'f-perc_icms_venda','f-perc_imp_interno_material','f-perc_imp_interno_servico'].forEach(id => {
        document.getElementById(id).value = '';
    });
    // Defaults
    document.getElementById('f-perc_material').value             = '70.00';
    document.getElementById('f-perc_desp_admin').value           = '5.00';
    document.getElementById('f-perc_desp_fixas').value           = '3.00';
    document.getElementById('f-perc_comissao_venda').value       = '2.00';
    document.getElementById('f-perc_pos_venda').value            = '1.00';
    document.getElementById('f-perc_icms_venda').value           = '19.50';
    document.getElementById('f-perc_imp_interno_material').value = '3.65';
    document.getElementById('f-perc_imp_interno_servico').value  = '5.00';
    document.getElementById('f-produto_id').disabled = false;
    recalcular();
}

async function openModal(produtoId = 0) {
    resetForm();
    document.getElementById('modal-produto_id').value = produtoId;

    if (produtoId) {
        document.getElementById('f-produto_id').value    = produtoId;
        document.getElementById('f-produto_id').disabled = true;

        // Preenche custo e icms do produto
        const prod = produtosData.find(p => p.id == produtoId);
        if (prod) {
            document.getElementById('f-custo_unitario').value      = parseFloat(prod.custo_unitario || 0).toFixed(4);
            document.getElementById('f-icms_custo_unitario').value = parseFloat(prod.icms_custo_unitario || 0).toFixed(4);
        }

        // Carrega formacao_preco existente
        const res  = await fetch(`api.php?produto_id=${produtoId}`);
        const json = await res.json();
        if (json.data) {
            const d = json.data;
            document.getElementById('f-valor_venda').value                = d.valor_venda;
            document.getElementById('f-perc_material').value              = d.perc_material;
            document.getElementById('f-valor_montagem').value             = d.valor_montagem;
            document.getElementById('f-perc_desp_admin').value            = d.perc_desp_admin;
            document.getElementById('f-perc_desp_fixas').value            = d.perc_desp_fixas;
            document.getElementById('f-perc_comissao_venda').value        = d.perc_comissao_venda;
            document.getElementById('f-perc_pos_venda').value             = d.perc_pos_venda;
            document.getElementById('f-perc_icms_venda').value            = d.perc_icms_venda;
            document.getElementById('f-perc_imp_interno_material').value  = d.perc_imp_interno_material;
            document.getElementById('f-perc_imp_interno_servico').value   = d.perc_imp_interno_servico;
        } else {
            // Produto sem formação: sugere venda = custo × 2
            sugerirVenda();
        }
        recalcular();
        document.getElementById('modal-title').textContent = 'Editar Formação de Preço';
    } else {
        document.getElementById('modal-title').textContent = 'Formar Preço de Produto';
    }

    document.getElementById('modal').classList.remove('hidden');
    setTimeout(() => {
        if (!produtoId) document.getElementById('f-produto_id').focus();
        else document.getElementById('f-valor_venda').focus();
    }, 50);
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const produtoId = parseInt(document.getElementById('f-produto_id').value) || parseInt(document.getElementById('modal-produto_id').value) || 0;
    const venda     = fv('f-valor_venda');
    const pMat      = fv('f-perc_material');

    if (!produtoId) { showToast('Selecione um produto.', 'error'); return; }
    if (venda <= 0) { showToast('Valor de venda deve ser maior que zero.', 'error'); return; }
    if (pMat < 0 || pMat > 100) { showToast('% Material deve estar entre 0 e 100.', 'error'); return; }

    const payload = {
        produto_id:               produtoId,
        custo_unitario:           fv('f-custo_unitario'),
        valor_venda:              venda,
        perc_material:            pMat,
        perc_desp_admin:          fv('f-perc_desp_admin'),
        perc_desp_fixas:          fv('f-perc_desp_fixas'),
        perc_comissao_venda:      fv('f-perc_comissao_venda'),
        perc_pos_venda:           fv('f-perc_pos_venda'),
        perc_icms_venda:          fv('f-perc_icms_venda'),
        icms_custo_unitario:      fv('f-icms_custo_unitario'),
        valor_montagem:           fv('f-valor_montagem'),
        perc_imp_interno_material: fv('f-perc_imp_interno_material'),
        perc_imp_interno_servico:  fv('f-perc_imp_interno_servico')
    };

    const btn = document.getElementById('btn-save');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) { showToast(data.message); closeModal(); loadData(document.getElementById('search').value); }
        else showToast(data.message, 'error');
    } finally { setLoading(btn, false); }
}

function deleteItem(id, label) {
    confirmDialog('Remover Formação de Preço', `Deseja remover o preço formado para "${label}"?`, async () => {
        const res  = await fetch(`api.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        data.success ? (showToast(data.message), loadData()) : showToast(data.message, 'error');
    });
}

document.getElementById('modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
document.addEventListener('DOMContentLoaded', () => { carregaSelects().then(() => loadData()); });
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
