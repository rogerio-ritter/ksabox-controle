/**
 * global.js — Funções JavaScript compartilhadas
 * Toast, Modal de confirmação, Toggle sidebar/tema, Máscaras de input
 */

/* ============================================================
   TOAST NOTIFICATIONS
   ============================================================ */
function showToast(message, type = 'success', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: 'bg-green-600',
        error:   'bg-red-600',
        warning: 'bg-yellow-500',
        info:    'bg-blue-600',
    };
    const icons = {
        success: 'fa-check-circle',
        error:   'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle',
    };

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg text-white text-sm shadow-lg
        ${colors[type] || colors.info} translate-x-0 transition-all duration-300`;
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info}"></i>
        <span>${message}</span>
        <button onclick="this.closest('div').remove()" class="ml-auto hover:opacity-70">
            <i class="fas fa-times text-xs"></i>
        </button>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ============================================================
   MODAL DE CONFIRMAÇÃO
   ============================================================ */
function confirmDialog(title, text, onConfirm) {
    const modal   = document.getElementById('modal-confirm');
    const btnOk   = document.getElementById('modal-confirm-ok');
    const btnCancel = document.getElementById('modal-confirm-cancel');
    const titleEl = document.getElementById('modal-confirm-title');
    const textEl  = document.getElementById('modal-confirm-text');

    titleEl.textContent = title || 'Confirmar ação';
    textEl.textContent  = text  || 'Tem certeza?';
    modal.classList.remove('hidden');

    const close = () => modal.classList.add('hidden');

    const okHandler = () => { close(); onConfirm(); cleanup(); };
    const cancelHandler = () => { close(); cleanup(); };

    function cleanup() {
        btnOk.removeEventListener('click', okHandler);
        btnCancel.removeEventListener('click', cancelHandler);
    }

    btnOk.addEventListener('click', okHandler);
    btnCancel.addEventListener('click', cancelHandler);
}

/* ============================================================
   TOGGLE SIDEBAR (mobile)
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const btnToggle = document.getElementById('btn-toggle-sidebar');

    function openSidebar() {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    }
    function closeSidebar() {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    }

    btnToggle?.addEventListener('click', () => {
        sidebar?.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);

    /* ---- Toggle tema claro/escuro ---- */
    const btnTema = document.getElementById('btn-toggle-tema');
    btnTema?.addEventListener('click', () => {
        const html    = document.documentElement;
        const isDark  = html.classList.contains('dark');
        const novoTema = isDark ? 'claro' : 'escuro';

        // Aplica imediatamente no DOM (sem piscar)
        html.classList.toggle('dark', !isDark);

        // Atualiza ícone imediatamente
        const icon = btnTema.querySelector('i');
        if (icon) {
            icon.className = novoTema === 'escuro' ? 'fas fa-sun text-lg' : 'fas fa-moon text-lg';
        }

        // Persiste no servidor (sessão + banco)
        fetch('/ksabox-controle/configuracoes/perfil/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_tema' })
        }).catch(() => {/* silencioso se API falhar */});
    });

    /* ---- Menu usuário dropdown ---- */
    const btnUserMenu   = document.getElementById('btn-user-menu');
    const userDropdown  = document.getElementById('user-dropdown');
    btnUserMenu?.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown?.classList.toggle('hidden');
    });
    document.addEventListener('click', () => userDropdown?.classList.add('hidden'));
});

/* ============================================================
   MÁSCARAS DE INPUT
   ============================================================ */

/** Formata campo como moeda BRL enquanto o usuário digita */
function maskMoney(input) {
    let v = input.value.replace(/\D/g, '');
    if (!v) { input.value = ''; return; }
    v = (parseInt(v, 10) / 100).toFixed(2);
    input.value = 'R$ ' + v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/** Formata campo como percentual (ex: 19,50) */
function maskPercent(input) {
    let v = input.value.replace(/[^\d,\.]/g, '');
    input.value = v;
}

/** Máscara de CNPJ: 99.999.999/9999-99 */
function maskCNPJ(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    input.value = v;
}

/** Máscara de CPF: 999.999.999-99 */
function maskCPF(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = v;
}

/** Máscara CNPJ ou CPF automática (detecta pelo tamanho) */
function maskCpfCnpj(input) {
    let v = input.value.replace(/\D/g, '');
    if (v.length <= 11) {
        maskCPF(input);
    } else {
        input.value = v; // reseta para aplicar CNPJ
        maskCNPJ(input);
    }
}

/** Máscara de CEP: 99999-999 */
function maskCEP(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = v;
}

/** Máscara de telefone: (99) 9999-9999 ou (99) 99999-9999 */
function maskPhone(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
    }
    input.value = v;
}

/** Máscara de NCM: 9999.99.99 */
function maskNCM(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{4})(\d)/, '$1.$2');
    v = v.replace(/(\d{4}\.\d{2})(\d)/, '$1.$2');
    input.value = v;
}

/* ============================================================
   UTILITÁRIOS GERAIS
   ============================================================ */

/** Converte string monetária BRL para float */
function parseMoney(str) {
    if (!str) return 0;
    return parseFloat(String(str).replace('R$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
}

/** Formata número como BRL sem o prefixo "R$ " */
function formatNumber(value, decimals = 2) {
    return parseFloat(value || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/** Faz requisição JSON e retorna Promise */
async function apiRequest(url, method = 'GET', body = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (body) options.body = JSON.stringify(body);
    const res = await fetch(url, options);
    return res.json();
}

/** Mostra/esconde loading em botão */
function setLoading(btn, loading) {
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Aguarde...';
        btn.disabled = true;
    } else {
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        btn.disabled = false;
    }
}
