<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {

    // Lista de produtos que possuem custo calculado (pré-requisito para formação de preço)
    if (isset($_GET['selects'])) {
        $prods = db()->query("
            SELECT p.id, p.nome, p.unidade_sigla,
                   cp.valor_unitario AS custo_unitario,
                   (cp.valor_icms / cp.quantidade) AS icms_custo_unitario
            FROM produtos p
            JOIN custo_produtos cp ON cp.produto_id = p.id
            WHERE p.ativo = 1
            ORDER BY p.nome
        ")->fetchAll();
        jsonResponse(['success' => true, 'produtos' => $prods]);
    }

    // Buscar formacao_preco por produto_id (para carregar no formulário de edição)
    if (isset($_GET['produto_id'])) {
        $pid  = (int)$_GET['produto_id'];
        $stmt = db()->prepare("SELECT * FROM formacao_precos WHERE produto_id = ?");
        $stmt->execute([$pid]);
        $row  = $stmt->fetch();
        jsonResponse(['success' => true, 'data' => $row ?: null]);
    }

    // Buscar formacao_preco por id
    if ($id) {
        $stmt = db()->prepare("
            SELECT fp.*, p.nome AS produto_nome
            FROM formacao_precos fp
            JOIN produtos p ON p.id = fp.produto_id
            WHERE fp.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse(
            $row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'],
            $row ? 200 : 404
        );
    }

    // Listagem: produtos COM custo calculado + LEFT JOIN formacao_precos
    $q    = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("
        SELECT p.id AS produto_id, p.nome AS produto_nome, p.unidade_sigla,
               c.nome AS categoria_nome,
               cp.valor_unitario AS custo_unitario,
               fp.id,
               fp.valor_venda,
               fp.margem_liquida,
               fp.perc_margem_liquida,
               fp.updated_at
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        JOIN custo_produtos cp ON cp.produto_id = p.id
        LEFT JOIN formacao_precos fp ON fp.produto_id = p.id
        WHERE p.ativo = 1 AND (p.nome LIKE ? OR c.nome LIKE ?)
        ORDER BY p.nome
    ");
    $stmt->execute([$q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $produto_id               = (int)($input['produto_id'] ?? 0);
    $custo_unitario           = (float)($input['custo_unitario'] ?? 0);
    $valor_venda              = (float)($input['valor_venda'] ?? 0);
    $perc_material            = (float)($input['perc_material'] ?? 70.00);
    $perc_desp_admin          = (float)($input['perc_desp_admin'] ?? 5.00);
    $perc_desp_fixas          = (float)($input['perc_desp_fixas'] ?? 3.00);
    $perc_comissao_venda      = (float)($input['perc_comissao_venda'] ?? 2.00);
    $perc_pos_venda           = (float)($input['perc_pos_venda'] ?? 1.00);
    $perc_icms_venda          = (float)($input['perc_icms_venda'] ?? 19.50);
    $icms_custo_unitario      = (float)($input['icms_custo_unitario'] ?? 0);
    $valor_montagem           = (float)($input['valor_montagem'] ?? 0);
    $perc_imp_interno_material = (float)($input['perc_imp_interno_material'] ?? 3.65);
    $perc_imp_interno_servico = (float)($input['perc_imp_interno_servico'] ?? 5.00);

    if (!$produto_id)     jsonResponse(['success' => false, 'message' => 'Produto é obrigatório.'], 422);
    if ($valor_venda <= 0) jsonResponse(['success' => false, 'message' => 'Valor de venda deve ser maior que zero.'], 422);
    if ($perc_material < 0 || $perc_material > 100)
        jsonResponse(['success' => false, 'message' => '% Material deve estar entre 0 e 100.'], 422);

    $stmt = db()->prepare("
        INSERT INTO formacao_precos
            (produto_id, custo_unitario, valor_venda, perc_material,
             perc_desp_admin, perc_desp_fixas, perc_comissao_venda, perc_pos_venda,
             perc_icms_venda, icms_custo_unitario,
             valor_montagem,
             perc_imp_interno_material, perc_imp_interno_servico)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            custo_unitario            = VALUES(custo_unitario),
            valor_venda               = VALUES(valor_venda),
            perc_material             = VALUES(perc_material),
            perc_desp_admin           = VALUES(perc_desp_admin),
            perc_desp_fixas           = VALUES(perc_desp_fixas),
            perc_comissao_venda       = VALUES(perc_comissao_venda),
            perc_pos_venda            = VALUES(perc_pos_venda),
            perc_icms_venda           = VALUES(perc_icms_venda),
            icms_custo_unitario       = VALUES(icms_custo_unitario),
            valor_montagem            = VALUES(valor_montagem),
            perc_imp_interno_material = VALUES(perc_imp_interno_material),
            perc_imp_interno_servico  = VALUES(perc_imp_interno_servico)
    ");
    $stmt->execute([
        $produto_id, $custo_unitario, $valor_venda, $perc_material,
        $perc_desp_admin, $perc_desp_fixas, $perc_comissao_venda, $perc_pos_venda,
        $perc_icms_venda, $icms_custo_unitario,
        $valor_montagem,
        $perc_imp_interno_material, $perc_imp_interno_servico
    ]);

    jsonResponse(['success' => true, 'message' => 'Formação de preço salva com sucesso.']);
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    db()->prepare("DELETE FROM formacao_precos WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Formação de preço removida com sucesso.']);
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
