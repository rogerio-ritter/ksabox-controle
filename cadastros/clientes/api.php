<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {
    // Busca CEP via ViaCEP (proxy interno)
    if (isset($_GET['cep'])) {
        $cep = preg_replace('/\D/', '', $_GET['cep']);
        if (strlen($cep) !== 8) jsonResponse(['success' => false, 'message' => 'CEP inválido.'], 422);
        $ch = curl_init("https://viacep.com.br/ws/$cep/json/");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!$data || isset($data['erro'])) jsonResponse(['success' => false, 'message' => 'CEP não encontrado.'], 404);
        jsonResponse(['success' => true, 'data' => [
            'endereco' => $data['logradouro'] ?? '',
            'bairro'   => $data['bairro'] ?? '',
            'cidade'   => $data['localidade'] ?? '',
            'uf'       => $data['uf'] ?? '',
        ]]);
    }

    if ($id) {
        $stmt = db()->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'], $row ? 200 : 404);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR cnpj_cpf LIKE ? OR cidade LIKE ? ORDER BY nome");
    $stmt->execute([$q, $q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $nome        = trim($input['nome'] ?? '');
    $cnpj_cpf    = trim($input['cnpj_cpf'] ?? '');
    $cep         = trim($input['cep'] ?? '');
    $endereco    = trim($input['endereco'] ?? '');
    $numero      = trim($input['numero'] ?? '');
    $complemento = trim($input['complemento'] ?? '');
    $bairro      = trim($input['bairro'] ?? '');
    $cidade      = trim($input['cidade'] ?? '');
    $uf          = strtoupper(trim($input['uf'] ?? ''));
    $telefone    = trim($input['telefone'] ?? '');
    $email       = trim($input['email'] ?? '');
    $contato     = trim($input['contato'] ?? '');
    $ativo       = isset($input['ativo']) ? (int)$input['ativo'] : 1;

    if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 422);

    $fields = [$nome, $cnpj_cpf ?: null, $cep ?: null, $endereco ?: null, $numero ?: null, $complemento ?: null, $bairro ?: null, $cidade ?: null, $uf ?: null, $telefone ?: null, $email ?: null, $contato ?: null, $ativo];

    if ($id) {
        $stmt = db()->prepare("UPDATE clientes SET nome=?,cnpj_cpf=?,cep=?,endereco=?,numero=?,complemento=?,bairro=?,cidade=?,uf=?,telefone=?,email=?,contato=?,ativo=? WHERE id=?");
        $stmt->execute([...$fields, $id]);
        jsonResponse(['success' => true, 'message' => 'Cliente atualizado com sucesso.']);
    } else {
        $stmt = db()->prepare("INSERT INTO clientes (nome,cnpj_cpf,cep,endereco,numero,complemento,bairro,cidade,uf,telefone,email,contato,ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute($fields);
        jsonResponse(['success' => true, 'message' => 'Cliente criado com sucesso.', 'id' => db()->lastInsertId()]);
    }
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Cliente excluído com sucesso.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Não é possível excluir: cliente vinculado a orçamentos.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
