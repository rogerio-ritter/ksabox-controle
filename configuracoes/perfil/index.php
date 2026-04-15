<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Meu Perfil';
require_once __DIR__ . '/../../layout/header.php';
$user = $_SESSION['user'];
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Meu Perfil</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gerencie suas informações pessoais e preferências</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Coluna principal -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Dados pessoais -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-user text-blue-500"></i> Dados Pessoais
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="lbl">Nome completo <span class="text-red-500">*</span></label>
                    <input id="f-nome" type="text" value="<?= h($user['nome'] ?? '') ?>" class="inp">
                </div>
                <div class="sm:col-span-2">
                    <label class="lbl">E-mail <span class="text-red-500">*</span></label>
                    <input id="f-email" type="email" value="<?= h($user['email'] ?? '') ?>" class="inp">
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button id="btn-perfil" onclick="salvarPerfil()"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Salvar Dados
                </button>
            </div>
        </div>

        <!-- Alterar senha -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-lock text-blue-500"></i> Alterar Senha
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="lbl">Senha atual <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="f-senha-atual" type="password" placeholder="••••••••" class="inp pr-10">
                        <button type="button" onclick="toggleSenha('f-senha-atual', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl">Nova senha <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input id="f-nova-senha" type="password" placeholder="Mínimo 6 caracteres" class="inp pr-10" oninput="verificaSenhas()">
                            <button type="button" onclick="toggleSenha('f-nova-senha', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="lbl">Confirmar nova senha <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input id="f-confirma-senha" type="password" placeholder="Repita a nova senha" class="inp pr-10" oninput="verificaSenhas()">
                            <button type="button" onclick="toggleSenha('f-confirma-senha', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Indicador de força da senha -->
                <div id="senha-status" class="hidden text-xs flex items-center gap-1.5"></div>
            </div>
            <div class="flex justify-end mt-4">
                <button id="btn-senha" onclick="salvarSenha()"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-key mr-1"></i> Alterar Senha
                </button>
            </div>
        </div>

    </div>

    <!-- Coluna lateral -->
    <div class="space-y-6">

        <!-- Avatar / resumo -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-center">
            <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user text-blue-500 text-3xl"></i>
            </div>
            <p id="display-nome" class="font-semibold text-gray-800 dark:text-white text-lg"><?= h($user['nome'] ?? '') ?></p>
            <p id="display-email" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= h($user['email'] ?? '') ?></p>
        </div>

        <!-- Tema -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-palette text-blue-500"></i> Aparência
            </h3>

            <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div id="tema-icon" class="w-9 h-9 rounded-lg flex items-center justify-center <?= isDark() ? 'bg-gray-700' : 'bg-yellow-100' ?>">
                        <i class="<?= isDark() ? 'fas fa-moon text-blue-300' : 'fas fa-sun text-yellow-500' ?> text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Tema</p>
                        <p id="tema-label" class="text-xs text-gray-500 dark:text-gray-400"><?= isDark() ? 'Escuro' : 'Claro' ?></p>
                    </div>
                </div>
                <!-- Toggle switch -->
                <button id="toggle-tema" onclick="alternarTema()"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?= isDark() ? 'bg-blue-600' : 'bg-gray-300' ?>">
                    <span id="toggle-dot"
                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform <?= isDark() ? 'translate-x-6' : 'translate-x-1' ?>">
                    </span>
                </button>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-center">A preferência é salva automaticamente.</p>
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
/* ── Mostrar / ocultar senha ─────────────────────────────────── */
function toggleSenha(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = `<i class="fas fa-${isText ? 'eye' : 'eye-slash'} text-sm"></i>`;
}

/* ── Verificação de senhas ───────────────────────────────────── */
function verificaSenhas() {
    const nova     = document.getElementById('f-nova-senha').value;
    const confirma = document.getElementById('f-confirma-senha').value;
    const status   = document.getElementById('senha-status');

    if (!nova) { status.classList.add('hidden'); return; }
    status.classList.remove('hidden');

    if (nova.length < 6) {
        status.innerHTML = '<i class="fas fa-times-circle text-red-500"></i><span class="text-red-500">Mínimo de 6 caracteres</span>';
    } else if (confirma && nova !== confirma) {
        status.innerHTML = '<i class="fas fa-times-circle text-red-500"></i><span class="text-red-500">As senhas não conferem</span>';
    } else if (confirma && nova === confirma) {
        status.innerHTML = '<i class="fas fa-check-circle text-green-500"></i><span class="text-green-600 dark:text-green-400">Senhas conferem</span>';
    } else {
        status.innerHTML = '<i class="fas fa-circle text-gray-300"></i><span class="text-gray-400">Aguardando confirmação...</span>';
    }
}

/* ── Salvar perfil ───────────────────────────────────────────── */
async function salvarPerfil() {
    const nome  = document.getElementById('f-nome').value.trim();
    const email = document.getElementById('f-email').value.trim();
    if (!nome)  { showToast('Informe seu nome.',   'error'); return; }
    if (!email) { showToast('Informe seu e-mail.', 'error'); return; }

    const btn = document.getElementById('btn-perfil');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'update_perfil', nome, email })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            document.getElementById('display-nome').textContent  = nome;
            document.getElementById('display-email').textContent = email;
        } else {
            showToast(data.message, 'error');
        }
    } catch(e) {
        showToast('Erro ao salvar.', 'error');
    } finally {
        setLoading(btn, false);
    }
}

/* ── Alterar senha ───────────────────────────────────────────── */
async function salvarSenha() {
    const senhaAtual    = document.getElementById('f-senha-atual').value;
    const novaSenha     = document.getElementById('f-nova-senha').value;
    const confirmaSenha = document.getElementById('f-confirma-senha').value;

    if (!senhaAtual || !novaSenha || !confirmaSenha) {
        showToast('Preencha todos os campos de senha.', 'error'); return;
    }
    if (novaSenha !== confirmaSenha) {
        showToast('A confirmação não confere com a nova senha.', 'error'); return;
    }
    if (novaSenha.length < 6) {
        showToast('A nova senha deve ter pelo menos 6 caracteres.', 'error'); return;
    }

    const btn = document.getElementById('btn-senha');
    setLoading(btn, true);
    try {
        const res  = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'update_senha', senha_atual: senhaAtual, nova_senha: novaSenha, confirma_senha: confirmaSenha })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            ['f-senha-atual','f-nova-senha','f-confirma-senha'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('senha-status').classList.add('hidden');
        } else {
            showToast(data.message, 'error');
        }
    } catch(e) {
        showToast('Erro ao alterar senha.', 'error');
    } finally {
        setLoading(btn, false);
    }
}

/* ── Alternar tema ───────────────────────────────────────────── */
async function alternarTema() {
    try {
        const res  = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'toggle_tema' })
        });
        const data = await res.json();
        if (data.success) {
            /* Aplica imediatamente sem reload */
            const isDark = data.tema === 'escuro';
            document.documentElement.classList.toggle('dark', isDark);

            document.getElementById('toggle-tema').className =
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors ' +
                (isDark ? 'bg-blue-600' : 'bg-gray-300');
            document.getElementById('toggle-dot').className =
                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ' +
                (isDark ? 'translate-x-6' : 'translate-x-1');
            document.getElementById('tema-label').textContent = isDark ? 'Escuro' : 'Claro';
            document.getElementById('tema-icon').className =
                'w-9 h-9 rounded-lg flex items-center justify-center ' +
                (isDark ? 'bg-gray-700' : 'bg-yellow-100');
            document.getElementById('tema-icon').innerHTML =
                `<i class="${isDark ? 'fas fa-moon text-blue-300' : 'fas fa-sun text-yellow-500'} text-lg"></i>`;
        }
    } catch(e) {
        showToast('Erro ao alternar tema.', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
