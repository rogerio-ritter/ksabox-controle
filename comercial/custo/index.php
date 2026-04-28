<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$pageTitle = 'Custo de Produto';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Custo de Produto</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Cálculo de custo de importação por produto</p>
    </div>
    <button onclick="openModal(0)"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus"></i> Calcular Custo
    </button>
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
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">USD × Qtd</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Cotação</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Custo Total</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-right">Vlr. Unitário</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 w-28 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Custo de Produto -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-5xl my-4">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10 rounded-t-xl">
            <h3 id="modal-title" class="font-semibold text-gray-800 dark:text-white">Calcular Custo de Produto</h3>
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
                                <label class="lbl">Cotação do Dólar (R$/USD) <span class="text-red-500">*</span></label>
                                <input id="f-cotacao_dolar" type="number" min="0.0001" step="0.0001" placeholder="5.8500" class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <!-- Produto Importado -->
                    <div>
                        <h4 class="sec-title">Produto Importado</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="lbl">Valor Unitário (USD) <span class="text-red-500">*</span></label>
                                <input id="f-valor_prod_usd" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">Quantidade <span class="text-red-500">*</span></label>
                                <input id="f-quantidade" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                                <p id="f-unidade" class="text-xs text-gray-400 mt-1"></p>
                            </div>
                            <div>
                                <label class="lbl">Frete Internacional (USD)</label>
                                <input id="f-frete_usd" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <!-- Tributos de Importação -->
                    <div>
                        <h4 class="sec-title">Tributos de Importação <span class="text-xs font-normal text-gray-400">(pré-carregados da categoria)</span></h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="lbl">% Seguro</label>
                                <input id="f-perc_seguro" type="number" min="0" step="0.01" placeholder="1.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% II</label>
                                <input id="f-perc_ii" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% PIS</label>
                                <input id="f-perc_pis" type="number" min="0" step="0.01" placeholder="2.10" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% COFINS</label>
                                <input id="f-perc_cofins" type="number" min="0" step="0.01" placeholder="9.65" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% IPI</label>
                                <input id="f-perc_ipi" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Antidumping</label>
                                <input id="f-perc_antidumping" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% ICMS</label>
                                <input id="f-perc_icms" type="number" min="0" step="0.01" placeholder="19.50" class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                    <!-- Outros Custos -->
                    <div>
                        <h4 class="sec-title">Outros Custos</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="lbl">% Desp. Aduaneiras</label>
                                <input id="f-perc_desp_aduaneiras" type="number" min="0" step="0.01" placeholder="2.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">Comissão de Compra (R$)</label>
                                <input id="f-valor_comissao_compra" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% Custo Financeiro</label>
                                <input id="f-perc_custo_financeiro" type="number" min="0" step="0.01" placeholder="3.00" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">% IOF</label>
                                <input id="f-perc_iof" type="number" min="0" step="0.01" placeholder="0.38" class="inp" oninput="recalcular()">
                            </div>
                            <div>
                                <label class="lbl">Frete Regional (R$)</label>
                                <input id="f-frete_regional" type="number" min="0" step="0.01" placeholder="0.00" class="inp" oninput="recalcular()">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Resumo (1/3) -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 sticky top-24">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <i class="fas fa-calculator text-blue-500"></i> Resumo do Cálculo
                        </h4>
                        <div class="space-y-1.5 text-xs" id="resumo-linhas">
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Valor Produto (BRL)</span><span id="r-vp_brl">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Frete (BRL)</span><span id="r-frete_brl">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Seguro</span><span id="r-seguro">R$ 0,00</span></div>
                            <div class="resumo-row border-t border-gray-200 dark:border-gray-700 pt-1.5 mt-1.5"><span class="text-gray-500 dark:text-gray-400">II</span><span id="r-ii">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">PIS</span><span id="r-pis">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">COFINS</span><span id="r-cofins">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">IPI</span><span id="r-ipi">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Antidumping</span><span id="r-antidumping">R$ 0,00</span></div>
                            <div class="resumo-row border-t border-gray-200 dark:border-gray-700 pt-1.5 mt-1.5"><span class="font-medium text-gray-600 dark:text-gray-300">Base ICMS</span><span id="r-base_icms" class="font-medium">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">ICMS</span><span id="r-icms">R$ 0,00</span></div>
                            <div class="resumo-row border-t border-gray-200 dark:border-gray-700 pt-1.5 mt-1.5"><span class="text-gray-500 dark:text-gray-400">Desp. Aduaneiras</span><span id="r-desp_aduan">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Custo Financeiro</span><span id="r-custo_fin">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">IOF</span><span id="r-iof">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Frete Regional</span><span id="r-frete_reg">R$ 0,00</span></div>
                            <div class="resumo-row"><span class="text-gray-500 dark:text-gray-400">Comissão Compra</span><span id="r-comissao">R$ 0,00</span></div>
                        </div>
                        <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Custo Total</span>
                                <span id="r-custo_total" class="text-sm font-bold text-blue-600 dark:text-blue-400">R$ 0,00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Vlr. Unitário</span>
                                <span id="r-valor_unitario" class="text-lg font-bold text-green-600 dark:text-green-400">R$ 0,00</span>
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
.resumo-row { display:flex; justify-content:space-between; align-items:center; color:#6b7280; }
.dark .resumo-row { color:#9ca3af; }
</style>

<script>
let produtosData = []; // cache dos dados de produtos (com percentuais de categoria)
let searchTimeout;

function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
function fmtBRL(v) {
    v = parseFloat(v) || 0;
    return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function fv(id) { return parseFloat(document.getElementById(id).value) || 0; }
function set(id, val) { document.getElementById(id).textContent = fmtBRL(val); }

/* ===== CÁLCULO (replica exatamente os GENERATED COLUMNS do MySQL) ===== */
function calcular() {
    const vpUSD    = fv('f-valor_prod_usd');
    const qtd      = fv('f-quantidade');
    const cotacao  = fv('f-cotacao_dolar');
    const freteUSD = fv('f-frete_usd');
    const pSeguro  = fv('f-perc_seguro');
    const pII      = fv('f-perc_ii');
    const pPIS     = fv('f-perc_pis');
    const pCOFINS  = fv('f-perc_cofins');
    const pIPI     = fv('f-perc_ipi');
    const pDesp    = fv('f-perc_desp_aduaneiras');
    const vComis   = fv('f-valor_comissao_compra');
    const pAnti    = fv('f-perc_antidumping');
    const pICMS    = fv('f-perc_icms');
    const pCusFin  = fv('f-perc_custo_financeiro');
    const pIOF     = fv('f-perc_iof');
    const freteReg = fv('f-frete_regional');

    const vpBRL    = vpUSD * qtd * cotacao;
    const freteBRL = freteUSD * cotacao;
    const seguro   = vpBRL * pSeguro / 100;

    const safe = p => p < 100 ? p : 0; // evita divisão por zero se perc = 100
    const ii       = (vpBRL + freteBRL + seguro) / (1 - safe(pII) / 100) * (pII / 100);
    const pis      = (vpBRL + freteBRL + seguro) / (1 - safe(pPIS) / 100) * (pPIS / 100);
    const cofins   = (vpBRL + freteBRL + seguro) / (1 - safe(pCOFINS) / 100) * (pCOFINS / 100);
    const ipi      = (vpBRL + freteBRL + seguro) / (1 - safe(pIPI) / 100) * (pIPI / 100);

    const despAduan   = (vpBRL + freteBRL + seguro + ii + pis + cofins + ipi + vComis) * pDesp / 100;
    const antidumping = (vpBRL + freteBRL + seguro) * pAnti / 100;
    const baseICMS    = vpBRL + freteBRL + seguro + ii + pis + cofins + ipi + antidumping;
    const icms        = baseICMS / (1 - safe(pICMS) / 100) * (pICMS / 100);
    const custoFin    = vpBRL * pCusFin / 100;
    const iof         = vpBRL * pIOF / 100;
    const custoTotal  = baseICMS + icms + freteReg + vComis + custoFin + iof + despAduan;
    const vlrUnit     = qtd > 0 ? custoTotal / qtd : 0;

    return { vpBRL, freteBRL, seguro, ii, pis, cofins, ipi, despAduan, antidumping, baseICMS, icms, custoFin, iof, freteReg, vComis, custoTotal, vlrUnit };
}

function recalcular() {
    const r = calcular();
    set('r-vp_brl',       r.vpBRL);
    set('r-frete_brl',    r.freteBRL);
    set('r-seguro',       r.seguro);
    set('r-ii',           r.ii);
    set('r-pis',          r.pis);
    set('r-cofins',       r.cofins);
    set('r-ipi',          r.ipi);
    set('r-antidumping',  r.antidumping);
    set('r-base_icms',    r.baseICMS);
    set('r-icms',         r.icms);
    set('r-desp_aduan',   r.despAduan);
    set('r-custo_fin',    r.custoFin);
    set('r-iof',          r.iof);
    set('r-frete_reg',    r.freteReg);
    set('r-comissao',     r.vComis);
    set('r-custo_total',  r.custoTotal);
    set('r-valor_unitario', r.vlrUnit);
}

/* ===== SELECTS ===== */
async function carregaSelects() {
    const res  = await fetch('api.php?selects=1');
    const json = await res.json();
    produtosData = json.produtos || [];
    const sel = document.getElementById('f-produto_id');
    sel.innerHTML = '<option value="">Selecione...</option>' +
        produtosData.map(p => `<option value="${p.id}">${esc(p.nome)}</option>`).join('');
}

/* Preenche percentuais com dados da categoria ao selecionar produto */
function onProdutoChange() {
    const pid  = parseInt(document.getElementById('f-produto_id').value) || 0;
    const prod = produtosData.find(p => p.id == pid);
    if (!prod) return;
    document.getElementById('f-unidade').textContent = prod.unidade_sigla ? `Unidade: ${prod.unidade_sigla}` : '';
    document.getElementById('f-perc_seguro').value    = prod.perc_seguro;
    document.getElementById('f-perc_ii').value        = prod.perc_ii;
    document.getElementById('f-perc_pis').value       = prod.perc_pis;
    document.getElementById('f-perc_cofins').value    = prod.perc_cofins;
    document.getElementById('f-perc_ipi').value       = prod.perc_ipi;
    document.getElementById('f-perc_antidumping').value = prod.perc_antidumping;
    document.getElementById('f-perc_icms').value      = prod.perc_icms;
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
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Nenhum produto encontrado.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(r => {
                const temCusto = r.id != null;
                const usdQtd   = temCusto ? `$${parseFloat(r.valor_prod_usd).toFixed(2)} × ${parseFloat(r.quantidade).toFixed(2)}` : '—';
                const cotacao  = temCusto ? `R$ ${parseFloat(r.cotacao_dolar).toFixed(4)}` : '—';
                const total    = temCusto ? fmtBRL(r.custo_total) : '—';
                const unitario = temCusto ? fmtBRL(r.valor_unitario) : '—';
                const badge    = temCusto
                    ? `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Calculado</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pendente</span>`;
                const btnEdit  = temCusto
                    ? `<button onclick="openModal(${r.produto_id})" class="text-blue-500 hover:text-blue-700 mr-3" title="Editar"><i class="fas fa-edit"></i></button>`
                    : `<button onclick="openModal(${r.produto_id})" class="text-green-500 hover:text-green-700 mr-3" title="Calcular"><i class="fas fa-calculator"></i></button>`;
                const btnDel   = temCusto
                    ? `<button onclick="deleteItem(${r.id}, '${esc(r.produto_nome)}')" class="text-red-400 hover:text-red-600" title="Remover"><i class="fas fa-trash"></i></button>`
                    : '';
                return `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800 dark:text-gray-200">${esc(r.produto_nome)}</div>
                        <div class="text-xs text-gray-400">${badge}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${esc(r.categoria_nome || '—')}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-600 dark:text-gray-400">${usdQtd}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-600 dark:text-gray-400">${cotacao}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">${total}</td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600 dark:text-blue-400">${unitario}</td>
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
    ['f-produto_id','f-cotacao_dolar','f-valor_prod_usd','f-quantidade','f-frete_usd',
     'f-perc_seguro','f-perc_ii','f-perc_pis','f-perc_cofins','f-perc_ipi',
     'f-perc_antidumping','f-perc_icms','f-perc_desp_aduaneiras','f-valor_comissao_compra',
     'f-perc_custo_financeiro','f-perc_iof','f-frete_regional'].forEach(id => {
        document.getElementById(id).value = '';
    });
    // Defaults
    document.getElementById('f-perc_desp_aduaneiras').value  = '2.00';
    document.getElementById('f-perc_custo_financeiro').value = '3.00';
    document.getElementById('f-perc_iof').value              = '0.38';
    document.getElementById('f-produto_id').disabled = false;
    document.getElementById('f-unidade').textContent = '';
    recalcular();
}

async function openModal(produtoId = 0) {
    resetForm();
    document.getElementById('modal-produto_id').value = produtoId;

    if (produtoId) {
        document.getElementById('f-produto_id').value    = produtoId;
        document.getElementById('f-produto_id').disabled = true; // produto fixo ao editar
        const prod = produtosData.find(p => p.id == produtoId);
        if (prod) document.getElementById('f-unidade').textContent = prod.unidade_sigla ? `Unidade: ${prod.unidade_sigla}` : '';

        // Carrega custo existente
        const res  = await fetch(`api.php?produto_id=${produtoId}`);
        const json = await res.json();
        if (json.data) {
            const d = json.data;
            document.getElementById('f-cotacao_dolar').value         = d.cotacao_dolar;
            document.getElementById('f-valor_prod_usd').value        = d.valor_prod_usd;
            document.getElementById('f-quantidade').value            = d.quantidade;
            document.getElementById('f-frete_usd').value             = d.frete_usd;
            document.getElementById('f-perc_seguro').value           = d.perc_seguro;
            document.getElementById('f-perc_ii').value               = d.perc_ii;
            document.getElementById('f-perc_pis').value              = d.perc_pis;
            document.getElementById('f-perc_cofins').value           = d.perc_cofins;
            document.getElementById('f-perc_ipi').value              = d.perc_ipi;
            document.getElementById('f-perc_antidumping').value      = d.perc_antidumping;
            document.getElementById('f-perc_icms').value             = d.perc_icms;
            document.getElementById('f-perc_desp_aduaneiras').value  = d.perc_desp_aduaneiras;
            document.getElementById('f-valor_comissao_compra').value = d.valor_comissao_compra;
            document.getElementById('f-perc_custo_financeiro').value = d.perc_custo_financeiro;
            document.getElementById('f-perc_iof').value              = d.perc_iof;
            document.getElementById('f-frete_regional').value        = d.frete_regional;
            recalcular();
        } else {
            // Produto sem custo: pré-carrega percentuais da categoria
            onProdutoChange();
        }
        document.getElementById('modal-title').textContent = 'Editar Custo de Produto';
    } else {
        document.getElementById('modal-title').textContent = 'Calcular Custo de Produto';
    }

    document.getElementById('modal').classList.remove('hidden');
    setTimeout(() => {
        if (!produtoId) document.getElementById('f-produto_id').focus();
        else document.getElementById('f-cotacao_dolar').focus();
    }, 50);
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

async function saveForm() {
    const produtoId  = parseInt(document.getElementById('f-produto_id').value) || parseInt(document.getElementById('modal-produto_id').value) || 0;
    const cotacao    = fv('f-cotacao_dolar');
    const vpUSD      = fv('f-valor_prod_usd');
    const quantidade = fv('f-quantidade');

    if (!produtoId)   { showToast('Selecione um produto.', 'error'); return; }
    if (vpUSD <= 0)   { showToast('Valor USD deve ser maior que zero.', 'error'); return; }
    if (quantidade <= 0) { showToast('Quantidade deve ser maior que zero.', 'error'); return; }
    if (cotacao <= 0) { showToast('Informe a cotação do dólar.', 'error'); return; }

    const payload = {
        produto_id:            produtoId,
        valor_prod_usd:        vpUSD,
        quantidade:            quantidade,
        cotacao_dolar:         cotacao,
        frete_usd:             fv('f-frete_usd'),
        perc_seguro:           fv('f-perc_seguro'),
        perc_ii:               fv('f-perc_ii'),
        perc_pis:              fv('f-perc_pis'),
        perc_cofins:           fv('f-perc_cofins'),
        perc_ipi:              fv('f-perc_ipi'),
        perc_antidumping:      fv('f-perc_antidumping'),
        perc_icms:             fv('f-perc_icms'),
        perc_desp_aduaneiras:  fv('f-perc_desp_aduaneiras'),
        valor_comissao_compra: fv('f-valor_comissao_compra'),
        perc_custo_financeiro: fv('f-perc_custo_financeiro'),
        perc_iof:              fv('f-perc_iof'),
        frete_regional:        fv('f-frete_regional')
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
    confirmDialog('Remover Custo', `Deseja remover o custo calculado para "${label}"?`, async () => {
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
