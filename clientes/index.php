<?php
$pageTitle = 'Clientes';
require_once dirname(__DIR__) . '/layout/header.php';

$clientes = db()->query('SELECT * FROM clientes ORDER BY nome')->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($clientes) ?> cliente(s) encontrado(s)</p>
    <button onclick="abrirModal()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Cliente
    </button>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Nome</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden md:table-cell">Telefone</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden lg:table-cell">CPF/CNPJ</th>
                    <th class="text-left px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hidden xl:table-cell">Cidade/UF</th>
                    <th class="text-center px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                    <th class="text-right px-5 py-3 text-gray-500 dark:text-gray-400 font-medium">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($clientes)): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Nenhum cliente cadastrado.</td></tr>
                <?php else: ?>
                <?php foreach ($clientes as $c): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="font-medium text-gray-800 dark:text-gray-200"><?= h($c['nome']) ?></p>
                        <?php if ($c['email']): ?><p class="text-xs text-gray-400 mt-0.5"><?= h($c['email']) ?></p><?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell"><?= h($c['telefone'] ?: '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell font-mono text-xs"><?= h($c['cpf_cnpj'] ?: '—') ?></td>
                    <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden xl:table-cell">
                        <?= $c['cidade'] ? h($c['cidade']) . ($c['estado'] ? '/' . h($c['estado']) : '') : '—' ?>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <?php if ($c['ativo']): ?>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30">Ativo</span>
                        <?php else: ?>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editarCliente(<?= h(json_encode($c)) ?>)" class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button onclick="deletarCliente(<?= $c['id'] ?>, '<?= h(addslashes($c['nome'])) ?>')" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modalCliente" class="modal-backdrop hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="modal-box bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="modalTitulo" class="text-base font-semibold text-gray-800 dark:text-white">Novo Cliente</h3>
            <button onclick="closeModal('modalCliente')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form class="p-5" onsubmit="salvarCliente(event)">
            <input type="hidden" id="cliId">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                    <input type="text" id="cliNome" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" id="cliEmail" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                    <input type="text" id="cliTelefone" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CPF / CNPJ</label>
                    <input type="text" id="cliCpfCnpj" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cidade</label>
                    <input type="text" id="cliCidade" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado (UF)</label>
                    <input type="text" id="cliEstado" maxlength="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm uppercase">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                    <textarea id="cliEndereco" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="cliAtivo" checked class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                    <label for="cliAtivo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="button" onclick="closeModal('modalCliente')" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancelar</button>
                <button type="submit" class="flex-1 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalTitulo').textContent = 'Novo Cliente';
    ['cliId','cliNome','cliEmail','cliTelefone','cliCpfCnpj','cliCidade','cliEstado','cliEndereco'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('cliAtivo').checked = true;
    openModal('modalCliente');
}
function editarCliente(c) {
    document.getElementById('modalTitulo').textContent = 'Editar Cliente';
    document.getElementById('cliId').value = c.id;
    document.getElementById('cliNome').value = c.nome;
    document.getElementById('cliEmail').value = c.email || '';
    document.getElementById('cliTelefone').value = c.telefone || '';
    document.getElementById('cliCpfCnpj').value = c.cpf_cnpj || '';
    document.getElementById('cliCidade').value = c.cidade || '';
    document.getElementById('cliEstado').value = c.estado || '';
    document.getElementById('cliEndereco').value = c.endereco || '';
    document.getElementById('cliAtivo').checked = c.ativo == 1;
    openModal('modalCliente');
}
async function salvarCliente(e) {
    e.preventDefault();
    const id = document.getElementById('cliId').value;
    const payload = {
        action: id ? 'update' : 'create', id,
        nome: document.getElementById('cliNome').value,
        email: document.getElementById('cliEmail').value,
        telefone: document.getElementById('cliTelefone').value,
        cpf_cnpj: document.getElementById('cliCpfCnpj').value,
        cidade: document.getElementById('cliCidade').value,
        estado: document.getElementById('cliEstado').value.toUpperCase(),
        endereco: document.getElementById('cliEndereco').value,
        ativo: document.getElementById('cliAtivo').checked ? 1 : 0
    };
    const res = await fetch(BASE_URL + '/clientes/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    const json = await res.json();
    if (json.success) { showToast(json.message); closeModal('modalCliente'); setTimeout(()=>location.reload(), 500); }
    else showToast(json.message, 'error');
}
function deletarCliente(id, nome) {
    confirmAction(`Deseja excluir o cliente "${nome}"?`, async () => {
        const res = await fetch(BASE_URL + '/clientes/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
        const json = await res.json();
        if (json.success) { showToast(json.message); setTimeout(()=>location.reload(), 500); }
        else showToast(json.message, 'error');
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
