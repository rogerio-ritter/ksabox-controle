<?php
$pageTitle = 'Meu Perfil';
require_once dirname(__DIR__) . '/layout/header.php';

$user = currentUser();
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'dados') {
        $nome  = trim($_POST['nome']  ?? '');
        $email = trim($_POST['email'] ?? '');
        if (!$nome || !$email) { $err = 'Nome e e-mail são obrigatórios.'; }
        else {
            db()->prepare('UPDATE usuarios SET nome=?, email=? WHERE id=?')->execute([$nome, $email, $user['id']]);
            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;
            $user = currentUser();
            $msg  = 'Dados atualizados!';
        }
    }

    if ($acao === 'senha') {
        $atual = $_POST['senha_atual'] ?? '';
        $nova  = $_POST['senha_nova']  ?? '';
        $conf  = $_POST['senha_conf']  ?? '';
        $dbUser = db()->prepare('SELECT senha FROM usuarios WHERE id=?');
        $dbUser->execute([$user['id']]);
        $hash = $dbUser->fetchColumn();
        if (!password_verify($atual, $hash))  { $err = 'Senha atual incorreta.'; }
        elseif (strlen($nova) < 6)            { $err = 'A nova senha deve ter ao menos 6 caracteres.'; }
        elseif ($nova !== $conf)              { $err = 'A confirmação não confere.'; }
        else {
            db()->prepare('UPDATE usuarios SET senha=? WHERE id=?')->execute([password_hash($nova, PASSWORD_DEFAULT), $user['id']]);
            $msg = 'Senha alterada!';
        }
    }
}

$stmt = db()->prepare('SELECT * FROM usuarios WHERE id=?');
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();
?>

<?php if ($msg): ?>
<div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl text-sm text-green-700 dark:text-green-400"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400"><?= h($err) ?></div>
<?php endif; ?>

<div class="max-w-2xl space-y-6">

    <!-- Avatar + tema -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shrink-0">
                <?= strtoupper(substr($userData['nome'], 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white"><?= h($userData['nome']) ?></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= h($userData['email']) ?></p>
            </div>
        </div>

        <!-- Aparência -->
        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Aparência</h3>
            <div class="flex gap-3">
                <button onclick="setTema('claro')" id="btnClaro"
                    class="flex-1 flex items-center gap-3 p-3 rounded-xl border-2 transition-colors <?= $userData['tema'] === 'claro' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' ?>">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.07-6.07-.71.71M6.34 17.66l-.71.71M17.66 17.66l.71.71M6.34 6.34l.71.71M12 5a7 7 0 1 0 0 14A7 7 0 0 0 12 5z"/></svg>
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Modo Claro</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fundo branco</p>
                    </div>
                    <?php if ($userData['tema'] === 'claro'): ?>
                    <svg class="w-4 h-4 text-indigo-500 ml-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    <?php endif; ?>
                </button>
                <button onclick="setTema('escuro')" id="btnEscuro"
                    class="flex-1 flex items-center gap-3 p-3 rounded-xl border-2 transition-colors <?= $userData['tema'] === 'escuro' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' ?>">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Modo Escuro</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fundo escuro</p>
                    </div>
                    <?php if ($userData['tema'] === 'escuro'): ?>
                    <svg class="w-4 h-4 text-indigo-500 ml-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Dados pessoais -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Dados Pessoais</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="dados">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                <input type="text" name="nome" value="<?= h($userData['nome']) ?>" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail *</label>
                <input type="email" name="email" value="<?= h($userData['email']) ?>" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Salvar</button>
        </form>
    </div>

    <!-- Alterar senha -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Alterar Senha</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="senha">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha Atual</label>
                <input type="password" name="senha_atual" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
                <input type="password" name="senha_nova" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nova Senha</label>
                <input type="password" name="senha_conf" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Alterar Senha</button>
        </form>
    </div>
</div>

<script>
async function setTema(tema) {
    const res  = await fetch(BASE_URL + '/perfil/api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'update_tema', tema})});
    const json = await res.json();
    if (json.success) { showToast('Tema atualizado!'); setTimeout(()=>location.reload(), 400); }
}
</script>

<?php require_once dirname(__DIR__) . '/layout/footer.php'; ?>
