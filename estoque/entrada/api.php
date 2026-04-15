<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method   = $_SERVER['REQUEST_METHOD'];
$input    = getInput();
$id       = (int)($_GET['id'] ?? $input['id'] ?? 0);
$userId   = (int)($_SESSION['user']['id'] ?? 0);

/* ── GET ───────────────────────────────────────────────────── */
if ($method === 'GET') {

    /* Produtos para o select (com saldo atual) */
    if (isset($_GET['selects'])) {
        $rows = db()->query("
            SELECT p.id, p.nome, p.unidade_sigla,
                   COALESCE(e.quantidade, 0) AS saldo
            FROM produtos p
            LEFT JOIN estoque e ON e.produto_id = p.id
            WHERE p.ativo = 1
            ORDER BY p.nome
        ")->fetchAll();
        jsonResponse(['success' => true, 'produtos' => $rows]);
    }

    /* Listagem de entradas com filtros */
    $q    = '%' . trim($_GET['q'] ?? '') . '%';
    $de   = trim($_GET['de']   ?? '');
    $ate  = trim($_GET['ate']  ?? '');
    $sql  = "SELECT m.id, m.quantidade, m.data_movimentacao, m.referencia, m.observacao, m.created_at,
                    p.nome AS produto_nome, p.unidade_sigla,
                    u.nome AS usuario_nome
             FROM movimentacao_estoque m
             JOIN produtos p ON p.id = m.produto_id
             JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.tipo = 'entrada'
               AND (p.nome LIKE ? OR m.referencia LIKE ?)";
    $params = [$q, $q];
    if ($de)  { $sql .= ' AND m.data_movimentacao >= ?'; $params[] = $de; }
    if ($ate) { $sql .= ' AND m.data_movimentacao <= ?'; $params[] = $ate; }
    $sql .= ' ORDER BY m.data_movimentacao DESC, m.id DESC LIMIT 500';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

/* ── POST: registrar entrada ───────────────────────────────── */
if ($method === 'POST') {
    $produto_id  = (int)($input['produto_id']  ?? 0);
    $quantidade  = (float)($input['quantidade'] ?? 0);
    $data_mov    = trim($input['data_movimentacao'] ?? date('Y-m-d'));
    $referencia  = trim($input['referencia']  ?? '') ?: null;
    $observacao  = trim($input['observacao']  ?? '') ?: null;

    if (!$produto_id)   jsonResponse(['success' => false, 'message' => 'Produto é obrigatório.'], 422);
    if ($quantidade <= 0) jsonResponse(['success' => false, 'message' => 'Quantidade deve ser maior que zero.'], 422);
    if (!$data_mov)     jsonResponse(['success' => false, 'message' => 'Data é obrigatória.'], 422);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* Garante que existe registro em estoque */
        $pdo->prepare(
            "INSERT IGNORE INTO estoque (produto_id, quantidade) VALUES (?, 0)"
        )->execute([$produto_id]);

        /* Incrementa saldo */
        $pdo->prepare(
            "UPDATE estoque SET quantidade = quantidade + ? WHERE produto_id = ?"
        )->execute([$quantidade, $produto_id]);

        /* Registra movimentação */
        $pdo->prepare(
            "INSERT INTO movimentacao_estoque
             (produto_id, tipo, quantidade, data_movimentacao, referencia, observacao, usuario_id)
             VALUES (?, 'entrada', ?, ?, ?, ?, ?)"
        )->execute([$produto_id, $quantidade, $data_mov, $referencia, $observacao, $userId]);

        /* Saldo novo */
        $saldo = (float)$pdo->prepare("SELECT quantidade FROM estoque WHERE produto_id=?")
                             ->execute([$produto_id]) ? null : null;
        $stmt = $pdo->prepare("SELECT quantidade FROM estoque WHERE produto_id=?");
        $stmt->execute([$produto_id]);
        $saldo = (float)$stmt->fetchColumn();

        $pdo->commit();
        jsonResponse(['success' => true,
            'message' => "Entrada registrada. Saldo atual: {$saldo} {$_POST['unidade_sigla']}",
            'saldo'   => $saldo]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Erro ao registrar: ' . $e->getMessage()], 500);
    }
}

/* ── DELETE: cancelar entrada ──────────────────────────────── */
if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);

    $stmt = db()->prepare("SELECT * FROM movimentacao_estoque WHERE id=? AND tipo='entrada'");
    $stmt->execute([$id]);
    $mov = $stmt->fetch();
    if (!$mov) jsonResponse(['success' => false, 'message' => 'Movimentação não encontrada.'], 404);

    /* Valida que o saldo atual suporta o estorno */
    $saldoStmt = db()->prepare("SELECT quantidade FROM estoque WHERE produto_id=?");
    $saldoStmt->execute([$mov['produto_id']]);
    $saldo = (float)$saldoStmt->fetchColumn();

    if ($saldo < (float)$mov['quantidade'])
        jsonResponse(['success' => false,
            'message' => 'Não é possível cancelar: o saldo atual (' . number_format($saldo, 2, ',', '.') . ') é menor que a quantidade da entrada.'], 422);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE estoque SET quantidade = quantidade - ? WHERE produto_id=?")
            ->execute([$mov['quantidade'], $mov['produto_id']]);
        $pdo->prepare("DELETE FROM movimentacao_estoque WHERE id=?")->execute([$id]);
        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Entrada cancelada com sucesso.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Erro ao cancelar: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
