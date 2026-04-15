<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $q      = '%' . trim($_GET['q'] ?? '') . '%';
    $filtro = trim($_GET['filtro'] ?? 'todos'); // todos | com_saldo | sem_saldo

    $sql = "
        SELECT p.id AS produto_id, p.nome AS produto_nome, p.unidade_sigla,
               c.nome AS categoria_nome,
               COALESCE(e.quantidade, 0) AS saldo,
               cp.valor_unitario AS custo_unitario,
               CASE
                   WHEN cp.valor_unitario IS NOT NULL
                   THEN COALESCE(e.quantidade, 0) * cp.valor_unitario
                   ELSE NULL
               END AS valor_estoque,
               e.updated_at
        FROM produtos p
        LEFT JOIN categorias c  ON c.id = p.categoria_id
        LEFT JOIN estoque e     ON e.produto_id = p.id
        LEFT JOIN custo_produtos cp ON cp.produto_id = p.id
        WHERE p.ativo = 1
          AND (p.nome LIKE ? OR c.nome LIKE ?)
    ";
    $params = [$q, $q];

    if ($filtro === 'com_saldo') {
        $sql .= ' AND COALESCE(e.quantidade, 0) > 0';
    } elseif ($filtro === 'sem_saldo') {
        $sql .= ' AND COALESCE(e.quantidade, 0) <= 0';
    }

    $sql .= ' ORDER BY p.nome';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    /* Totalizadores */
    $totalItens    = count($rows);
    $totalComSaldo = 0;
    $valorTotal    = 0.0;
    foreach ($rows as $r) {
        if ((float)$r['saldo'] > 0) $totalComSaldo++;
        if ($r['valor_estoque'] !== null) $valorTotal += (float)$r['valor_estoque'];
    }

    jsonResponse([
        'success'       => true,
        'data'          => $rows,
        'totais'        => [
            'total_itens'     => $totalItens,
            'com_saldo'       => $totalComSaldo,
            'sem_saldo'       => $totalItens - $totalComSaldo,
            'valor_total'     => $valorTotal,
        ],
    ]);
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
