<?php
$id = (int)($_GET['id'] ?? 0);
$pageTitle = $id ? 'Editar Orçamento' : 'Novo Orçamento';
require_once dirname(__DIR__) . '/layout/header.php';

$db       = db();
$clientes = $db->query('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
$tabelas  = $db->query('SELECT id, nome FROM tabelas_precos WHERE ativo = 1 ORDER BY nome')->fetchAll();

// id='' (string vazia) indica novo orçamento — evita que JS leia "0" como truthy
$orcamento = ['id' => '', 'numero' => '', 'cliente_id' => '', 'tabela_id' => '', 'status' => 'rascunho', 'data_criacao' => date('Y-m-d'), 'data_validade' => '', 'observacoes' => '', 'total' => 0];
$itens = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM orcamentos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $orcamento = $row;
        $stmtIt = $db->prepare('SELECT oi.*, p.nome AS produto_nome, p.unidade FROM orcamento_itens oi LEFT JOIN produtos p ON p.id = oi.produto_id WHERE oi.orcamento_id = ? ORDER BY oi.id');
        $stmtIt->execute([$id]);
        $itens = $stmtIt->fetchAll();
    }
}

$todosProdutos = $db->query('SELECT p.id, p.nome, p.unidade FROM produtos p WHERE p.ativo = 1 ORDER BY p.nome')->fetchAll();

$precosPorTabela = [];
$rows = $db->query('SELECT tabela_id, produto_id, preco FROM tabela_preco_itens')->fetchAll();
foreach ($rows as $r) {
    $precosPorTabela[$r['tabela_id']][$r['produto_id']] = $r['preco'];
}
?>

<div class="flex items-center justify-between mb-6">
    <a href="<?= APP_URL ?>/orcamentos/" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Voltar
    </a>
    <?php if ($id): ?>
    <a href="<?= APP_URL ?>/orcamentos/print.php?id=<?= $id ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
        Imprimir PDF
    </a>
    <?php endif; ?>
</div>

<form id="formOrcamento" onsubmit="salvarOrcamento(event)" class="space-y-6">
    <!-- BUG FIX: valor vazio para novo orçamento — "0" seria truthy em JS -->
    <input type="hidden" id="orcId" value="<?= $orcamento['id'] ?>">

    <!-- Informações gerais -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-5">Informações do Orçamento</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número</label>
                <input type="text" id="orcNumero" value="<?= h($orcamento['numero']) ?>" placeholder="Gerado automaticamente"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                <select id="orcCliente" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    <option value="">Selecione o cliente</option>
                    <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $orcamento['cliente_id'] == $c['id'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tabela de Preços</label>
                <select id="orcTabela" onchange="atualizarPrecos()" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    <option value="">Sem tabela</option>
                    <?php foreach ($tabelas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $orcamento['tabela_id'] == $t['id'] ? 'selected' : '' ?>><?= h($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de Criação</label>
                <input type="date" id="orcDataCriacao" value="<?= h($orcamento['data_criacao']) ?>"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Validade</label>
                <input type="date" id="orcDataValidade" value="<?= h($orcamento['data_validade'] ?? '') ?>"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select id="orcStatus" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                    <option value="rascunho" <?= $orcamento['status'] === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="enviado"  <?= $orcamento['status'] === 'enviado'  ? 'selected' : '' ?>>Enviado</option>
                    <option value="aprovado" <?= $orcamento['status'] === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="rejeitado"<?= $orcamento['status'] === 'rejeitado'? 'selected' : '' ?>>Rejeitado</option>
                    <option value="cancelado"<?= $orcamento['status'] === 'cancelado'? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
            <textarea id="orcObs" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"><?= h($orcamento['observacoes'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Itens -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white">Itens do Orçamento</h2>
            <button type="button" onclick="adicionarLinha()" class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Adicionar Item
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-gray-500 dark:text-gray-400 font-medium">Produto / Descrição</th>
                        <th class="text-right px-4 py-3 text-gray-500 dark:text-gray-400 font-medium w-24">Qtd</th>
                        <th class="text-right px-4 py-3 text-gray-500 dark:text-gray-400 font-medium w-32">Preço Unit.</th>
                        <th class="text-right px-4 py-3 text-gray-500 dark:text-gray-400 font-medium w-32">Total</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody id="tbodyItens" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
            </table>
        </div>
        <div class="flex justify-end p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Geral</p>
                <p id="totalGeral" class="text-2xl font-bold text-gray-800 dark:text-white">R$ 0,00</p>
            </div>
        </div>
    </div>

    <div class="flex gap-3 justify-end">
        <a href="<?= APP_URL ?>/orcamentos/" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</a>
        <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">Salvar Orçamento</button>
    </div>
</form>

<script>
const PRODUTOS   = <?= json_encode($todosProdutos) ?>;
const PRECOS_TAB = <?= json_encode($precosPorTabela) ?>;
const ITENS_INIT = <?= json_encode($itens) ?>;
let linhas = [];

function getTabela() { return document.getElementById('orcTabela').value; }

function produtoOpts(selectedId = '') {
    return '<option value="">— Descrição livre —</option>' +
        PRODUTOS.map(p => `<option value="${p.id}" data-un="${escH(p.unidade)}" ${p.id == selectedId ? 'selected' : ''}>${escH(p.nome)} (${escH(p.unidade)})</option>`).join('');
}
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function adicionarLinha(item = null) {
    const idx = linhas.length;
    const it  = item || {produto_id:'', descricao:'', quantidade:1, preco_unitario:0, total:0};
    linhas.push({...it});

    const tr = document.createElement('tr');
    tr.id    = `linha_${idx}`;
    tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors';
    tr.innerHTML = `
        <td class="px-4 py-2">
            <select onchange="produtoSelecionado(${idx}, this)"
                class="w-full mb-1.5 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500">${produtoOpts(it.produto_id)}</select>
            <input type="text" value="${escH(it.descricao)}" placeholder="Descrição do item"
                oninput="linhas[${idx}].descricao=this.value"
                class="w-full px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500">
        </td>
        <td class="px-4 py-2">
            <input type="number" value="${it.quantidade}" min="0.01" step="0.01"
                oninput="atualizarLinha(${idx},'quantidade',this.value)"
                class="w-full text-right px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500">
        </td>
        <td class="px-4 py-2">
            <input type="number" id="preco_${idx}" value="${parseFloat(it.preco_unitario).toFixed(2)}" min="0" step="0.01"
                oninput="atualizarLinha(${idx},'preco_unitario',this.value)"
                class="w-full text-right px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500">
        </td>
        <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200" id="total_${idx}">${fmtMoney(it.total)}</td>
        <td class="px-4 py-2 text-center">
            <button type="button" onclick="removerLinha(${idx})" class="p-1 text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>`;
    document.getElementById('tbodyItens').appendChild(tr);
    recalcTotal();
}

function produtoSelecionado(idx, sel) {
    linhas[idx].produto_id = sel.value;
    if (sel.value) {
        const p = PRODUTOS.find(x => x.id == sel.value);
        if (p) {
            const descInput = document.querySelector(`#linha_${idx} input[type="text"]`);
            if (descInput && !descInput.value) { descInput.value = p.nome; linhas[idx].descricao = p.nome; }
        }
        const tabId = getTabela();
        if (tabId && PRECOS_TAB[tabId]?.[sel.value] !== undefined) {
            const preco = PRECOS_TAB[tabId][sel.value];
            document.getElementById(`preco_${idx}`).value = parseFloat(preco).toFixed(2);
            linhas[idx].preco_unitario = parseFloat(preco);
            atualizarLinha(idx, 'preco_unitario', preco);
            return;
        }
    }
    recalcTotal();
}

function atualizarLinha(idx, campo, val) {
    linhas[idx][campo] = parseFloat(val) || 0;
    const tot = (linhas[idx].quantidade || 0) * (linhas[idx].preco_unitario || 0);
    linhas[idx].total = tot;
    document.getElementById(`total_${idx}`).textContent = fmtMoney(tot);
    recalcTotal();
}

function removerLinha(idx) {
    document.getElementById(`linha_${idx}`)?.remove();
    linhas[idx] = null;
    recalcTotal();
}

function recalcTotal() {
    const tot = linhas.filter(Boolean).reduce((acc, l) => acc + (parseFloat(l.total) || 0), 0);
    document.getElementById('totalGeral').textContent = fmtMoneyBr(tot);
}

function atualizarPrecos() {
    const tabId = getTabela();
    linhas.forEach((l, idx) => {
        if (!l || !l.produto_id) return;
        if (tabId && PRECOS_TAB[tabId]?.[l.produto_id] !== undefined) {
            const preco = PRECOS_TAB[tabId][l.produto_id];
            const inp = document.getElementById(`preco_${idx}`);
            if (inp) { inp.value = parseFloat(preco).toFixed(2); }
            atualizarLinha(idx, 'preco_unitario', preco);
        }
    });
}

function fmtMoney(v)   { return 'R$ ' + parseFloat(v||0).toFixed(2).replace('.',','); }
function fmtMoneyBr(v) { return 'R$ ' + parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }

async function salvarOrcamento(e) {
    e.preventDefault();
    const orcIdVal = document.getElementById('orcId').value;
    // FIX: parseInt para garantir que "0" ou "" sejam tratados como falsy
    const isEdit   = parseInt(orcIdVal) > 0;

    const itensFiltrados = linhas.filter(Boolean).filter(l => l.descricao || l.produto_id);
    const payload = {
        action:        isEdit ? 'update' : 'create',
        id:            isEdit ? orcIdVal : '',
        numero:        document.getElementById('orcNumero').value,
        cliente_id:    document.getElementById('orcCliente').value     || null,
        tabela_id:     document.getElementById('orcTabela').value      || null,
        status:        document.getElementById('orcStatus').value,
        data_criacao:  document.getElementById('orcDataCriacao').value,
        data_validade: document.getElementById('orcDataValidade').value || null,
        observacoes:   document.getElementById('orcObs').value,
        itens:         itensFiltrados
    };
    const btn = e.submitter;
    btn.disabled = true; btn.textContent = 'Salvando...';
    const res  = await fetch(BASE_URL + '/orcamentos/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const json = await res.json();
    btn.disabled = false; btn.textContent = 'Salvar Orçamento';
    if (json.success) {
        showToast(json.message);
        setTimeout(() => window.location.href = BASE_URL + '/orcamentos/form.php?id=' + json.data.id, 600);
    } else {
        showToast(json.message, 'error');
    }
}

ITENS_INIT.forEach(it => adicionarLinha(it));
if (ITENS_INIT.length === 0) adicionarLinha();
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
