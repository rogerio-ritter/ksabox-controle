<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare("SELECT id, nome, email, tema, ativo, created_at FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT id, nome, email, tema, ativo, created_at FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY nome");
    $stmt->execute([$q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome  = trim($input['nome'] ?? '');
    $email = trim($input['email'] ?? '');
    $senha = $input['senha'] ?? '';
    $tema  = in_array($input['tema'] ?? 'claro', ['claro','escuro']) ? $input['tema'] : 'claro';
    $ativo = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome)  jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);
    if (!$email) jsonResponse(['success' => false, 'message' => 'E-mail é obrigatório.'], 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'message' => 'E-mail inválido.'], 422);

    try {
        if ($id) {
            // Atualização — senha opcional
            if ($senha) {
                if (strlen($senha) < 6) jsonResponse(['success' => false, 'message' => 'Senha deve ter ao menos 6 caracteres.'], 422);
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = db()->prepare("UPDATE usuarios SET nome=?, email=?, senha=?, tema=?, ativo=? WHERE id=?");
                $stmt->execute([$nome, $email, $hash, $tema, $ativo, $id]);
            } else {
                $stmt = db()->prepare("UPDATE usuarios SET nome=?, email=?, tema=?, ativo=? WHERE id=?");
                $stmt->execute([$nome, $email, $tema, $ativo, $id]);
            }
            // Atualiza sessão se for o próprio usuário
            if ((int)($_SESSION['user']['id'] ?? 0) === $id) {
                $_SESSION['user']['nome']  = $nome;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['tema']  = $tema;
            }
            jsonResponse(['success' => true, 'message' => 'Usuário atualizado com sucesso.']);
        } else {
            if (!$senha) jsonResponse(['success' => false, 'message' => 'Senha é obrigatória.'], 422);
            if (strlen($senha) < 6) jsonResponse(['success' => false, 'message' => 'Senha deve ter ao menos 6 caracteres.'], 422);
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO usuarios (nome, email, senha, tema, ativo) VALUES (?,?,?,?,?)");
            $stmt->execute([$nome, $email, $hash, $tema, $ativo]);
            jsonResponse(['success' => true, 'message' => 'Usuário criado com sucesso.', 'id' => db()->lastInsertId()]);
        }
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate'))
            jsonResponse(['success' => false, 'message' => 'E-mail já cadastrado.'], 422);
        throw $e;
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    if ($id === (int)($_SESSION['user']['id'] ?? 0))
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir o próprio usuário.'], 422);
    try {
        db()->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Usuário excluído com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir este usuário.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
