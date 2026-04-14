    </main>
</div><!-- /.flex-1 -->

<!-- Toast container -->
<div id="toast-container" class="fixed bottom-5 right-5 flex flex-col gap-2 pointer-events-none"></div>

<!-- Modal de confirmação global -->
<div id="modal-confirm" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white" id="modal-confirm-title">Confirmar ação</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400" id="modal-confirm-text">Tem certeza?</p>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <button id="modal-confirm-cancel" class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                Cancelar
            </button>
            <button id="modal-confirm-ok" class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white">
                Confirmar
            </button>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<!-- JS Global -->
<script src="<?= APP_URL ?>/assets/js/global.js"></script>

</body>
</html>
