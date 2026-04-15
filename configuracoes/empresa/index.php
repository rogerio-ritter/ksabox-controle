<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Dados da Empresa';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Dados da Empresa</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informações utilizadas nos orçamentos e relatórios</p>
    </div>
    <button id="btn-save" onclick="salvar()"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-save mr-1"></i> Salvar Alterações
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Coluna principal -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Identificação -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-building text-blue-500"></i> Identificação
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="lbl">Razão Social / Nome <span class="text-red-500">*</span></label>
                    <input id="f-nome" type="text" placeholder="Ex: Ksabox - Arquitetura Modular" class="inp">
                </div>
                <div>
                    <label class="lbl">CNPJ</label>
                    <input id="f-cnpj" type="text" placeholder="00.000.000/0000-00" maxlength="18" class="inp">
                </div>
                <div>
                    <label class="lbl">E-mail</label>
                    <input id="f-email" type="email" placeholder="contato@empresa.com" class="inp">
                </div>
                <div>
                    <label class="lbl">Telefone</label>
                    <input id="f-telefone" type="text" placeholder="(00) 00000-0000" maxlength="15" class="inp">
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-blue-500"></i> Endereço
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                <div class="sm:col-span-2">
                    <label class="lbl">CEP</label>
                    <input id="f-cep" type="text" placeholder="00000-000" maxlength="9" class="inp" oninput="buscaCep(this.value)">
                </div>
                <div class="sm:col-span-4">
                    <label class="lbl">Logradouro</label>
                    <input id="f-endereco" type="text" placeholder="Rua, Avenida..." class="inp">
                </div>
                <div class="sm:col-span-2">
                    <label class="lbl">Número</label>
                    <input id="f-numero" type="text" placeholder="Nº" class="inp">
                </div>
                <div class="sm:col-span-4">
                    <label class="lbl">Complemento</label>
                    <input id="f-complemento" type="text" placeholder="Sala, bloco, andar..." class="inp">
                </div>
                <div class="sm:col-span-2">
                    <label class="lbl">Bairro</label>
                    <input id="f-bairro" type="text" placeholder="Bairro" class="inp">
                </div>
                <div class="sm:col-span-3">
                    <label class="lbl">Cidade</label>
                    <input id="f-cidade" type="text" placeholder="Cidade" class="inp">
                </div>
                <div class="sm:col-span-1">
                    <label class="lbl">UF</label>
                    <input id="f-uf" type="text" placeholder="SP" maxlength="2" class="inp uppercase">
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna lateral — Preview -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-eye text-blue-500"></i> Preview
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Como aparecerá nos orçamentos e PDFs:</p>

            <div id="preview" class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-xs text-gray-700 dark:text-gray-300 leading-relaxed space-y-0.5">
                <p id="pv-nome"     class="font-bold text-sm text-gray-900 dark:text-white">—</p>
                <p id="pv-cnpj"    class="text-gray-500 dark:text-gray-400"></p>
                <p id="pv-end"     class="mt-1"></p>
                <p id="pv-contato" class="mt-1 text-gray-500 dark:text-gray-400"></p>
            </div>

            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-300">
                <i class="fas fa-info-circle mr-1"></i>
                Os dados são exibidos no cabeçalho dos PDFs de orçamentos.
            </div>
        </div>
    </div>

</div>

<style>
.lbl { display:block; font-size:.875rem; font-weight:500; color:#374151; margin-bottom:.25rem; }
.dark .lbl { color:#d1d5db; }
.inp { width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:.5rem; font-size:.875rem; background:#fff; outline:none; transition:.15s; }
.inp:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.3); }
.dark .inp { background:#374151; border-color:#4b5563; color:#f3f4f6; }
.uppercase { text-transform: uppercase; }
</style>

<script>
const campos = ['nome','cnpj','email','telefone','cep','endereco','numero','complemento','bairro','cidade','uf'];

/* ── Carrega dados atuais ─────────────────────────────────────── */
async function carregarDados() {
    try {
        const res  = await fetch('api.php');
        const json = await res.json();
        const d    = json.data || {};
        campos.forEach(c => {
            const el = document.getElementById(`f-${c}`);
            if (el) el.value = d[c] || '';
        });
        atualizaPreview();
    } catch(e) {
        showToast('Erro ao carregar dados.', 'error');
    }
}

/* ── Preview em tempo real ───────────────────────────────────── */
function atualizaPreview() {
    const v = (id) => document.getElementById(`f-${id}`)?.value?.trim() || '';
    document.getElementById('pv-nome').textContent  = v('nome') || '—';
    document.getElementById('pv-cnpj').textContent  = v('cnpj') ? 'CNPJ: ' + v('cnpj') : '';

    const end = [v('endereco'), v('numero') ? 'nº ' + v('numero') : '', v('complemento')].filter(Boolean).join(', ');
    const loc = [v('bairro'), v('cidade') && v('uf') ? v('cidade') + '/' + v('uf').toUpperCase() : (v('cidade') || v('uf').toUpperCase()), v('cep') ? 'CEP ' + v('cep') : ''].filter(Boolean).join(' — ');
    document.getElementById('pv-end').textContent = [end, loc].filter(Boolean).join('\n');

    const contato = [v('telefone'), v('email')].filter(Boolean).join('  |  ');
    document.getElementById('pv-contato').textContent = contato;
}

campos.forEach(c => {
    const el = document.getElementById(`f-${c}`);
    if (el) el.addEventListener('input', atualizaPreview);
});

/* ── Busca CEP (ViaCEP) ──────────────────────────────────────── */
let cepTimer;
function buscaCep(cep) {
    clearTimeout(cepTimer);
    cep = cep.replace(/\D/g,'');
    if (cep.length !== 8) return;
    cepTimer = setTimeout(async () => {
        try {
            const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const d = await r.json();
            if (d.erro) return;
            document.getElementById('f-endereco').value  = d.logradouro || '';
            document.getElementById('f-bairro').value    = d.bairro     || '';
            document.getElementById('f-cidade').value    = d.localidade || '';
            document.getElementById('f-uf').value        = d.uf         || '';
            atualizaPreview();
            document.getElementById('f-numero').focus();
        } catch(e) { /* silencia erro de rede */ }
    }, 400);
}

/* ── Salvar ──────────────────────────────────────────────────── */
async function salvar() {
    const nome = document.getElementById('f-nome').value.trim();
    if (!nome) { showToast('Informe o nome da empresa.', 'error'); return; }

    const payload = {};
    campos.forEach(c => {
        payload[c] = document.getElementById(`f-${c}`)?.value?.trim() || '';
    });

    const btn = document.getElementById('btn-save');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) showToast(data.message);
        else showToast(data.message, 'error');
    } catch(e) {
        showToast('Erro ao salvar.', 'error');
    } finally {
        setLoading(btn, false);
    }
}

document.addEventListener('DOMContentLoaded', carregarDados);
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
