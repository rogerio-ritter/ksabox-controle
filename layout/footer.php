    </main><!-- /main -->
</div><!-- /main wrapper -->

<script>
const BASE_URL = '<?= APP_URL ?>';

/* ── Theme ── */
function toggleTheme() {
    const html  = document.documentElement;
    const isDark = html.classList.toggle('dark');
    document.getElementById('icon-sun').classList.toggle('hidden',  isDark);
    document.getElementById('icon-moon').classList.toggle('hidden', !isDark);
    fetch(BASE_URL + '/api/perfil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'update_tema', tema: isDark ? 'escuro' : 'claro'})
    });
}

/* ── Sidebar ── */
function toggleSidebar() {
    const s = document.getElementById('sidebar');
    const o = document.getElementById('sidebarOverlay');
    s.classList.toggle('-translate-x-full');
    o.classList.toggle('hidden');
}

/* ── User dropdown ── */
function toggleUserMenu() {
    document.getElementById('userDropdown').classList.toggle('hidden');
}
document.addEventListener('click', (e) => {
    if (!document.getElementById('userMenu')?.contains(e.target)) {
        document.getElementById('userDropdown')?.classList.add('hidden');
    }
});

/* ── Toast ── */
function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
    t.className = `fixed bottom-5 right-5 z-[999] ${colors[type] || colors.info} text-white px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}

/* ── Modal helpers ── */
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
function closeAllModals() {
    document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.add('hidden'));
}
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAllModals(); });

/* ── Confirm dialog ── */
function confirmAction(msg, callback) {
    const overlay = document.getElementById('confirmModal');
    document.getElementById('confirmMsg').textContent = msg;
    overlay.classList.remove('hidden');
    document.getElementById('confirmOk').onclick = () => { overlay.classList.add('hidden'); callback(); };
    document.getElementById('confirmCancel').onclick = () => overlay.classList.add('hidden');
}
</script>

<!-- Global Confirm Modal -->
<div id="confirmModal" class="modal-backdrop hidden fixed inset-0 bg-black/50 z-[998] flex items-center justify-center p-4">
    <div class="modal-box bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 4a8 8 0 1 0 0 16A8 8 0 0 0 12 4z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Confirmar ação</h3>
                <p id="confirmMsg" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button id="confirmCancel" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Cancelar</button>
            <button id="confirmOk" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Confirmar</button>
        </div>
    </div>
</div>

</body>
</html>
