<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id        = (int)($_GET['id'] ?? 0);
$orcamento = null;
$itensJson = '[]';

if ($id) {
    $stmt = db()->prepare("SELECT * FROM orcamentos WHERE id = ?");
    $stmt->execute([$id]);
    $orcamento = $stmt->fetch();
    if ($orcamento) {
        $stmt2 = db()->prepare(
            "SELECT produto_id, quantidade, valor_unitario, perc_desconto, perc_material, perc_margem_liquida
             FROM orcamento_itens WHERE orcamento_id = ? ORDER BY id"
        );
        $stmt2->execute([$id]);
        $itensJson = json_encode($stmt2->fetchAll(), JSON_UNESCAPED_UNICODE);
    }
}

$pageTitle = $id ? 'Editar Orçamento' : 'Novo Orçamento';
require_once __DIR__ . '/../../layout/header.php';
?>

<!-- Breadcrumb + Ações -->
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
            <a href="index.php" class="hover:text-blue-600 dark:hover:text-blue-400">Orçamentos</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span><?= $id ? 'Editar' : 'Novo' ?></span>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white"><?= h($pageTitle) ?></h2>
    </div>
    <div class="flex gap-3">
        <a href="index.php" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
        <button id="btn-save" onclick="salvar()"
            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-save mr-1"></i> Salvar
        </button>
    </div>
</div>

<!-- Card: Dados Gerais -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-5">
    <h3 class="sec-title mb-4">Dados Gerais</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
            <label class="lbl">Cliente <span class="text-red-500">*</span></label>
            <select id="f-cliente_id" class="inp">
                <option value="">Selecione...</option>
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="lbl">Tabela de Preço <span class="text-red-500">*</span></label>
            <select id="f-tabela_preco_id" class="inp" onchange="onTabelaChange()">
                <option value="">Selecione...</option>
            </select>
        </div>
        <div>
            <label class="lbl">Data de Emissão <span class="text-red-500">*</span></label>
            <input id="f-data_criacao" type="date" class="inp">
        </div>
        <div>
            <label class="lbl">Validade</label>
            <input id="f-validade" type="date" class="inp">
        </div>
        <div>
            <label class="lbl">Status</label>
            <select id="f-status" class="inp">
                <?php foreach(['Rascunho','Enviado','Aprovado','Rejeitado','Cancelado'] as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="lbl">Prazo de Entrega</label>
            <input id="f-prazo_entrega" type="text" placeholder="Ex: 30 dias úteis" class="inp">
        </div>
    </div>
</div>

<!-- Card: Itens -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-5">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="sec-title mb-0">Itens do Orçamento</h3>
        <button onclick="addItem()"
            class="flex items-center gap-2 text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Adicionar Item
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-20 text-center">Unid.</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-24">Qtd</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-32">Vlr Unitário</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">% Desc.</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-32 text-right">Vlr c/ Desc.</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-32 text-right">Vlr Total</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">% Mat.</th>
                    <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">% Margem</th>
                    <th class="px-3 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody id="items-tbody">
                <tr id="row-empty">
                    <td colspan="10" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                        Nenhum item adicionado. Clique em "Adicionar Item".
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Totais -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-end">
            <div class="w-full max-w-sm space-y-2 text-sm">
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Subtotal Material</span><span id="tot-mat">R$ 0,00</span>
                </div>
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Subtotal Serviço</span><span id="tot-srv">R$ 0,00</span>
                </div>
                <div class="flex justify-between font-medium text-gray-700 dark:text-gray-300 pt-1 border-t border-gray-200 dark:border-gray-600">
                    <span>Subtotal</span><span id="tot-sub">R$ 0,00</span>
                </div>
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>IPI</span><span id="tot-ipi">R$ 0,00</span>
                </div>
                <div class="flex justify-between font-bold text-base text-gray-900 dark:text-white pt-2 border-t-2 border-gray-300 dark:border-gray-500">
                    <span>Total Geral</span>
                    <span id="tot-geral" class="text-blue-600 dark:text-blue-400">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card: Condições Comerciais -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-5">
    <h3 class="sec-title mb-4">Condições Comerciais</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="lbl">Condição de Pagamento</label>
            <textarea id="f-condicao_pagamento" rows="3" placeholder="Ex: 50% entrada, 50% na entrega"
                class="inp resize-none"></textarea>
        </div>
        <div>
            <label class="lbl">Condição de Entrega</label>
            <textarea id="f-condicao_entrega" rows="3" placeholder="Ex: FOB fábrica, entrega em 30 dias"
                class="inp resize-none"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="lbl">Condições Gerais</label>
            <textarea id="f-condicoes_gerais" rows="3" placeholder="Informações adicionais sobre o orçamento..."
                class="inp resize-none"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="lbl">Observações Internas</label>
            <textarea id="f-observacoes" rows="2" placeholder="Notas internas (não aparecem no PDF)"
                class="inp resize-none"></textarea>
        </div>
    </div>
</div>

<!-- Botão salvar inferior -->
<div class="flex justify-end gap-3 pb-4">
    <a href="index.php" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
        Cancelar
    </a>
    <button onclick="salvar()" class="px-6 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
        <i class="fas fa-save mr-1"></i> Salvar Orçamento
    </button>
</div>

<style>
.lbl { display:block; font-size:.875rem; font-weight:500; color:#374151; margin-bottom:.25rem; }
.dark .lbl { color:#d1d5db; }
.inp { width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:.5rem; font-size:.875rem; background:#fff; outline:none; transition:.15s; }
.inp:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.3); }
.dark .inp { background:#374151; border-color:#4b5563; color:#f3f4f6; }
.sec-title { font-size:.875rem; font-weight:600; color:#374151; }
.dark .sec-title { color:#d1d5db; }
</style>

<script>
/* ─── Dados carregados via API ─── */
let produtosMap = {};
let tabelasMap  = {};
let items       = [];
let idxCounter  = 0;

/* ─── Dados PHP injetados (edição) ─── */
const EDIT_ID    = <?= $id ?>;
const EDIT_ORC   = <?= $orcamento ? json_encode($orcamento, JSON_UNESCAPED_UNICODE) : 'null' ?>;
const EDIT_ITENS = <?= $itensJson ?>;

function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmtBRL(v){ v=parseFloat(v)||0; return 'R$ '+v.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
function set(id, v){ const el=document.getElementById(id); if(el) el.textContent=fmtBRL(v); }

/* ─── Carregar selects via API ─── */
async function carregaSelects() {
    const res  = await fetch('api.php?selects=1');
    const json = await res.json();

    produtosMap = {};
    (json.produtos || []).forEach(p => produtosMap[p.id] = p);

    tabelasMap = {};
    const selTabela = document.getElementById('f-tabela_preco_id');
    selTabela.innerHTML = '<option value="">Selecione...</option>' +
        (json.tabelas || []).map(t => {
            tabelasMap[t.id] = t;
            return `<option value="${t.id}">${esc(t.nome)} (×${parseFloat(t.multiplicador).toFixed(2)})</option>`;
        }).join('');

    const selCli = document.getElementById('f-cliente_id');
    selCli.innerHTML = '<option value="">Selecione...</option>' +
        (json.clientes || []).map(c => `<option value="${c.id}">${esc(c.nome)}</option>`).join('');

    if (EDIT_ORC) {
        selCli.value = EDIT_ORC.cliente_id;
        selTabela.value = EDIT_ORC.tabela_preco_id;
        document.getElementById('f-data_criacao').value        = EDIT_ORC.data_criacao       || '';
        document.getElementById('f-validade').value            = EDIT_ORC.validade           || '';
        document.getElementById('f-status').value              = EDIT_ORC.status             || 'Rascunho';
        document.getElementById('f-prazo_entrega').value       = EDIT_ORC.prazo_entrega      || '';
        document.getElementById('f-condicao_pagamento').value  = EDIT_ORC.condicao_pagamento || '';
        document.getElementById('f-condicao_entrega').value    = EDIT_ORC.condicao_entrega   || '';
        document.getElementById('f-condicoes_gerais').value    = EDIT_ORC.condicoes_gerais   || '';
        document.getElementById('f-observacoes').value         = EDIT_ORC.observacoes        || '';

        EDIT_ITENS.forEach(item => {
            const idx = ++idxCounter;
            items.push({
                idx,
                produto_id:     parseInt(item.produto_id),
                quantidade:     parseFloat(item.quantidade),
                valor_unitario: parseFloat(item.valor_unitario),
                perc_desconto:  parseFloat(item.perc_desconto  || 0),
                perc_material:  parseFloat(item.perc_material),
            });
        });
        renderItems();
        calcTotals();
    } else {
        const hoje = new Date();
        document.getElementById('f-data_criacao').value = hoje.toISOString().split('T')[0];
        const validade = new Date(hoje);
        validade.setDate(validade.getDate() + 30);
        document.getElementById('f-validade').value = validade.toISOString().split('T')[0];
    }
}

/* ─── Tabela de preço mudou → recalcular preços dos itens ─── */
function onTabelaChange() {
    const tid  = parseInt(document.getElementById('f-tabela_preco_id').value) || 0;
    const mult = tabelasMap[tid]?.multiplicador || 1;
    items.forEach(item => {
        const p = produtosMap[item.produto_id];
        if (p) {
            item.valor_unitario = Math.round(parseFloat(p.valor_venda) * parseFloat(mult) * 100) / 100;
        }
    });
    renderItems();
    calcTotals();
}

/* ─── Produto selecionado numa linha ─── */
function onItemProdutoChange(idx) {
    const item = items.find(i => i.idx === idx);
    const pid  = parseInt(document.getElementById(`ip-${idx}`).value) || 0;
    item.produto_id   = pid;
    item.perc_desconto = 0;
    if (pid && produtosMap[pid]) {
        const p    = produtosMap[pid];
        const tid  = parseInt(document.getElementById('f-tabela_preco_id').value) || 0;
        const mult = tabelasMap[tid]?.multiplicador || 1;
        item.valor_unitario = Math.round(parseFloat(p.valor_venda) * parseFloat(mult) * 100) / 100;
        item.perc_material  = parseFloat(p.perc_material);
        document.getElementById(`iu-${idx}`).value = item.valor_unitario.toFixed(2);
        document.getElementById(`iq-${idx}`).value = item.quantidade || 1;
        document.getElementById(`id-${idx}`).value = '0.00';
        document.getElementById(`im-${idx}`).value = item.perc_material.toFixed(1);
        item.quantidade = parseFloat(document.getElementById(`iq-${idx}`).value);
    }
    renderItemCells(idx);
    calcTotals();
}

/* ─── Qtd, preço, desconto ou % mat mudaram numa linha ─── */
function onItemChange(idx) {
    const item = items.find(i => i.idx === idx);
    item.quantidade     = parseFloat(document.getElementById(`iq-${idx}`).value) || 0;
    item.valor_unitario = parseFloat(document.getElementById(`iu-${idx}`).value) || 0;
    item.perc_desconto  = Math.min(100, Math.max(0,   parseFloat(document.getElementById(`id-${idx}`).value) || 0));
    item.perc_material  = Math.min(100, Math.max(0,   parseFloat(document.getElementById(`im-${idx}`).value) || 0));
    renderItemCells(idx);
    calcTotals();
}

/* ─── Calcular margem sobre o valor com desconto ─── */
function calcMargem(vlr, percDesc, pMat, p) {
    const vEfetivo = vlr * (1 - (percDesc || 0) / 100);
    if (!p || vEfetivo <= 0) return 0;
    const vMat = vEfetivo * pMat / 100;
    const vSrv = vEfetivo - vMat;
    const desp = vEfetivo * (parseFloat(p.perc_desp_admin) + parseFloat(p.perc_desp_fixas)
                           + parseFloat(p.perc_comissao_venda) + parseFloat(p.perc_pos_venda)
                           + parseFloat(p.perc_icms_venda)) / 100
               + vMat * parseFloat(p.perc_imp_interno_material) / 100
               + vSrv * parseFloat(p.perc_imp_interno_servico)  / 100
               + parseFloat(p.valor_montagem || 0)
               - parseFloat(p.icms_custo_unitario || 0);
    const margem = vEfetivo - parseFloat(p.custo_unitario) - desp;
    return vEfetivo > 0 ? (margem / vEfetivo) * 100 : 0;
}

/* ─── Renderiza toda a tbody ─── */
function renderItems() {
    const tbody = document.getElementById('items-tbody');
    if (!items.length) {
        tbody.innerHTML = `<tr id="row-empty"><td colspan="10" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
            Nenhum item adicionado. Clique em "Adicionar Item".</td></tr>`;
        return;
    }
    const opts = Object.values(produtosMap).map(p =>
        `<option value="${p.id}">${esc(p.nome)}</option>`
    ).join('');
    tbody.innerHTML = items.map(item => buildRow(item, opts)).join('');
}

/* ─── Atualiza células calculadas de uma linha ─── */
function renderItemCells(idx) {
    const item   = items.find(i => i.idx === idx);
    const p      = produtosMap[item.produto_id];
    const percD  = item.perc_desconto || 0;
    const vDisc  = item.valor_unitario * (1 - percD / 100);
    const total  = item.quantidade * vDisc;
    const margem = calcMargem(item.valor_unitario, percD, item.perc_material, p);
    const mCls   = margem >= 15 ? 'text-green-600 dark:text-green-400'
                 : margem >= 5  ? 'text-yellow-600 dark:text-yellow-400'
                 : 'text-red-500 dark:text-red-400';

    const elVDisc  = document.getElementById(`ivc-${idx}`);
    const elTotal  = document.getElementById(`it-${idx}`);
    const elMargem = document.getElementById(`imrg-${idx}`);
    const elUnit   = document.getElementById(`iunit-${idx}`);

    if (elVDisc)  elVDisc.textContent  = fmtBRL(vDisc);
    if (elTotal)  elTotal.textContent  = fmtBRL(total);
    if (elMargem) {
        elMargem.textContent = item.produto_id && p ? margem.toFixed(1)+'%' : '—';
        elMargem.className   = `text-center text-xs font-semibold ${p ? mCls : 'text-gray-400'}`;
    }
    if (elUnit) elUnit.textContent = p ? p.unidade_sigla : '—';
}

function buildRow(item, opts) {
    const p      = produtosMap[item.produto_id];
    const percD  = item.perc_desconto || 0;
    const vDisc  = item.valor_unitario * (1 - percD / 100);
    const total  = item.quantidade * vDisc;
    const margem = calcMargem(item.valor_unitario, percD, item.perc_material, p);
    const mCls   = margem >= 15 ? 'text-green-600 dark:text-green-400'
                 : margem >= 5  ? 'text-yellow-600 dark:text-yellow-400'
                 : 'text-red-500 dark:text-red-400';
    const inpCls = `w-full py-1.5 px-2 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg
                    bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500`;
    return `
    <tr class="border-b border-gray-100 dark:border-gray-700" id="row-${item.idx}">
        <td class="px-3 py-2">
            <select id="ip-${item.idx}" onchange="onItemProdutoChange(${item.idx})"
                class="w-full py-1.5 px-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg
                       bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">Selecione...</option>
                ${opts.replace(`value="${item.produto_id}"`, `value="${item.produto_id}" selected`)}
            </select>
        </td>
        <td id="iunit-${item.idx}" class="px-3 py-2 text-center text-xs text-gray-500 dark:text-gray-400 font-mono">
            ${p ? p.unidade_sigla : '—'}
        </td>
        <td class="px-3 py-2">
            <input id="iq-${item.idx}" type="number" min="0.01" step="0.01"
                value="${item.quantidade}" oninput="onItemChange(${item.idx})" class="${inpCls}">
        </td>
        <td class="px-3 py-2">
            <input id="iu-${item.idx}" type="number" min="0" step="0.01"
                value="${item.valor_unitario.toFixed(2)}" oninput="onItemChange(${item.idx})" class="${inpCls}">
        </td>
        <td class="px-3 py-2">
            <input id="id-${item.idx}" type="number" min="0" max="100" step="0.01"
                value="${percD.toFixed(2)}" oninput="onItemChange(${item.idx})"
                class="w-full py-1.5 px-2 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg
                       bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td id="ivc-${item.idx}" class="px-3 py-2 text-right text-sm font-medium text-gray-700 dark:text-gray-300">
            ${fmtBRL(vDisc)}
        </td>
        <td id="it-${item.idx}" class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">
            ${fmtBRL(total)}
        </td>
        <td class="px-3 py-2">
            <input id="im-${item.idx}" type="number" min="0" max="100" step="0.1"
                value="${parseFloat(item.perc_material || 0).toFixed(1)}"
                oninput="onItemChange(${item.idx})"
                class="w-full py-1.5 px-2 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg
                       bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td id="imrg-${item.idx}" class="px-3 py-2 text-center text-xs font-semibold ${p ? mCls : 'text-gray-400'}">
            ${p ? margem.toFixed(1)+'%' : '—'}
        </td>
        <td class="px-3 py-2 text-center">
            <button onclick="removeItem(${item.idx})" class="text-red-400 hover:text-red-600 text-xs">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>`;
}

/* ─── Adicionar / Remover itens ─── */
function addItem() {
    if (!Object.keys(produtosMap).length) {
        showToast('Nenhum produto com preço formado disponível.', 'warning'); return;
    }
    const idx = ++idxCounter;
    items.push({ idx, produto_id: 0, quantidade: 1, valor_unitario: 0, perc_desconto: 0, perc_material: 70 });
    renderItems();
    setTimeout(() => document.getElementById(`ip-${idx}`)?.focus(), 50);
}

function removeItem(idx) {
    items = items.filter(i => i.idx !== idx);
    renderItems();
    calcTotals();
}

/* ─── Calcular totais globais (desconto já embutido nos valores por item) ─── */
function calcTotals() {
    let sMat = 0, sSrv = 0, sIPI = 0;
    items.forEach(item => {
        const p     = produtosMap[item.produto_id];
        const percD = item.perc_desconto || 0;
        const vDisc = item.valor_unitario * (1 - percD / 100);
        const tot   = item.quantidade * vDisc;
        const mat   = tot * item.perc_material / 100;
        sMat += mat;
        sSrv += tot - mat;
        if (p) sIPI += mat * parseFloat(p.perc_ipi || 0) / 100;
    });
    const sub   = sMat + sSrv;
    const total = sub + sIPI;

    set('tot-mat',  sMat);
    set('tot-srv',  sSrv);
    set('tot-sub',  sub);
    set('tot-ipi',  sIPI);
    set('tot-geral', total);
}

/* ─── Salvar ─── */
async function salvar() {
    const clienteId = parseInt(document.getElementById('f-cliente_id').value) || 0;
    const tabelaId  = parseInt(document.getElementById('f-tabela_preco_id').value) || 0;

    if (!clienteId) { showToast('Selecione um cliente.', 'error'); return; }
    if (!tabelaId)  { showToast('Selecione a tabela de preço.', 'error'); return; }
    if (!items.length) { showToast('Adicione pelo menos um item.', 'error'); return; }
    const itensFiltrados = items.filter(i => i.produto_id && i.quantidade > 0 && i.valor_unitario > 0);
    if (!itensFiltrados.length) { showToast('Preencha todos os itens corretamente.', 'error'); return; }

    const payload = {
        id:                 EDIT_ID,
        cliente_id:         clienteId,
        tabela_preco_id:    tabelaId,
        data_criacao:       document.getElementById('f-data_criacao').value,
        validade:           document.getElementById('f-validade').value,
        status:             document.getElementById('f-status').value,
        prazo_entrega:      document.getElementById('f-prazo_entrega').value,
        condicao_pagamento: document.getElementById('f-condicao_pagamento').value,
        condicao_entrega:   document.getElementById('f-condicao_entrega').value,
        condicoes_gerais:   document.getElementById('f-condicoes_gerais').value,
        observacoes:        document.getElementById('f-observacoes').value,
        items: itensFiltrados.map(i => ({
            produto_id:     i.produto_id,
            quantidade:     i.quantidade,
            valor_unitario: i.valor_unitario,
            perc_desconto:  i.perc_desconto  || 0,
            perc_material:  i.perc_material,
        }))
    };

    const btn = document.getElementById('btn-save');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            setTimeout(() => window.location.href = `visualizar.php?id=${data.id}`, 800);
        } else showToast(data.message, 'error');
    } finally { setLoading(btn, false); }
}

document.addEventListener('DOMContentLoaded', carregaSelects);
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
