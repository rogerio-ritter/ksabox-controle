<?php
$pageTitle = 'Dados da Empresa';
require_once dirname(__DIR__) . '/layout/header.php';

$empresa = db()->query('SELECT * FROM empresa LIMIT 1')->fetch() ?: [];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nome','cnpj','telefone','email','endereco'];
    $data = [];
    foreach ($campos as $c) $data[$c] = trim($_POST[$c] ?? '');

    if (empty($empresa)) {
        db()->prepare("INSERT INTO empresa (nome,cnpj,telefone,email,endereco) VALUES (:nome,:cnpj,:telefone,:email,:endereco)")->execute($data);
    } else {
        db()->prepare("UPDATE empresa SET nome=:nome,cnpj=:cnpj,telefone=:telefone,email=:email,endereco=:endereco WHERE id={$empresa['id']}")->execute($data);
    }
    $empresa = db()->query('SELECT * FROM empresa LIMIT 1')->fetch();
    $msg = 'Dados salvos com sucesso!';
}
?>

<?php if ($msg): ?>
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl text-sm text-green-700 dark:text-green-400"><?= h($msg) ?></div>
<?php endif; ?>

<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-5">Informações da Empresa</h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da Empresa *</label>
                <input type="text" name="nome" value="<?= h($empresa['nome'] ?? '') ?>" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CNPJ</label>
                    <input type="text" name="cnpj" value="<?= h($empresa['cnpj'] ?? '') ?>"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                    <input type="text" name="telefone" value="<?= h($empresa['telefone'] ?? '') ?>"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                <input type="email" name="email" value="<?= h($empresa['email'] ?? '') ?>"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                <textarea name="endereco" rows="3" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"><?= h($empresa['endereco'] ?? '') ?></textarea>
            </div>
            <div class="pt-2">
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">Salvar Dados</button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
