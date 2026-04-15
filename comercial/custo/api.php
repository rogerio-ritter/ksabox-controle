<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

if ($method === 'GET') {

    // Lista de produtos com dados de categoria para preencher o formulário
    if (isset($_GET['selects'])) {
        $prods = db()->query("
            SELECT p.id, p.nome, p.unidade_sigla,
                   c.perc_seguro, c.perc_ii, c.perc_pis, c.perc_cofins,
                   c.perc_ipi, c.perc_antidumping, c.perc_icms
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE p.ativo = 1
            ORDER BY p.nome
        ")->fetchAll();
        jsonResponse(['success' => true, 'produtos' => $prods]);
    }

    // Buscar custo por produto_id (para carregar no formulário de edição)
    if (isset($_GET['produto_id'])) {
        $pid  = (int)$_GET['produto_id'];
        $stmt = db()->prepare("SELECT * FROM custo_produtos WHERE produto_id = ?");
        $stmt->execute([$pid]);
        $row  = $stmt->fetch();
        jsonResponse(['success' => true, 'data' => $row ?: null]);
    }

    // Buscar custo por id
    if ($id) {
        $stmt = db()->prepare("
            SELECT cp.*, p.nome AS produto_nome
            FROM custo_produtos cp
            JOIN produtos p ON p.id = cp.produto_id
            WHERE cp.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        jsonResponse(
            $row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Não encontrado.'],
            $row ? 200 : 404
        );
    }

    // Listagem: todos os produtos ativos com LEFT JOIN em custo_produtos
    $q    = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = db()->prepare("
        SELECT p.id AS produto_id, p.nome AS produto_nome, p.unidade_sigla,
               c.nome AS categoria_nome,
               cp.id,
               cp.valor_prod_usd, cp.quantidade, cp.cotacao_dolar,
               cp.custo_total, cp.valor_unitario,
               cp.updated_at
        FROM produtos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN custo_produtos cp ON cp.produto_id = p.id
        WHERE p.ativo = 1 AND (p.nome LIKE ? OR c.nome LIKE ?)
        ORDER BY p.nome
    ");
    $stmt->execute([$q, $q]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $produto_id            = (int)($input['produto_id'] ?? 0);
    $valor_prod_usd        = (float)($input['valor_prod_usd'] ?? 0);
    $quantidade            = (float)($input['quantidade'] ?? 0);
    $cotacao_dolar         = (float)($input['cotacao_dolar'] ?? 0);
    $frete_usd             = (float)($input['frete_usd'] ?? 0);
    $perc_seguro           = (float)($input['perc_seguro'] ?? 1.00);
    $perc_ii               = (float)($input['perc_ii'] ?? 0);
    $perc_pis              = (float)($input['perc_pis'] ?? 2.10);
    $perc_cofins           = (float)($input['perc_cofins'] ?? 9.65);
    $perc_ipi              = (float)($input['perc_ipi'] ?? 0);
    $perc_desp_aduaneiras  = (float)($input['perc_desp_aduaneiras'] ?? 2.00);
    $valor_comissao_compra = (float)($input['valor_comissao_compra'] ?? 0);
    $perc_antidumping      = (float)($input['perc_antidumping'] ?? 0);
    $perc_icms             = (float)($input['perc_icms'] ?? 19.50);
    $perc_custo_financeiro = (float)($input['perc_custo_financeiro'] ?? 3.00);
    $perc_iof              = (float)($input['perc_iof'] ?? 0.38);
    $frete_regional        = (float)($input['frete_regional'] ?? 0);

    if (!$produto_id)        jsonResponse(['success' => false, 'message' => 'Produto é obrigatório.'], 422);
    if ($valor_prod_usd <= 0) jsonResponse(['success' => false, 'message' => 'Valor USD deve ser maior que zero.'], 422);
    if ($quantidade <= 0)    jsonResponse(['success' => false, 'message' => 'Quantidade deve ser maior que zero.'], 422);
    if ($cotacao_dolar <= 0) jsonResponse(['success' => false, 'message' => 'Cotação do dólar deve ser maior que zero.'], 422);

    $stmt = db()->prepare("
        INSERT INTO custo_produtos
            (produto_id, valor_prod_usd, quantidade, cotacao_dolar, frete_usd,
             perc_seguro, perc_ii, perc_pis, perc_cofins, perc_ipi,
             perc_desp_aduaneiras, valor_comissao_compra,
             perc_antidumping, perc_icms,
             perc_custo_financeiro, perc_iof, frete_regional)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            valor_prod_usd        = VALUES(valor_prod_usd),
            quantidade            = VALUES(quantidade),
            cotacao_dolar         = VALUES(cotacao_dolar),
            frete_usd             = VALUES(frete_usd),
            perc_seguro           = VALUES(perc_seguro),
            perc_ii               = VALUES(perc_ii),
            perc_pis              = VALUES(perc_pis),
            perc_cofins           = VALUES(perc_cofins),
            perc_ipi              = VALUES(perc_ipi),
            perc_desp_aduaneiras  = VALUES(perc_desp_aduaneiras),
            valor_comissao_compra = VALUES(valor_comissao_compra),
            perc_antidumping      = VALUES(perc_antidumping),
            perc_icms             = VALUES(perc_icms),
            perc_custo_financeiro = VALUES(perc_custo_financeiro),
            perc_iof              = VALUES(perc_iof),
            frete_regional        = VALUES(frete_regional)
    ");
    $stmt->execute([
        $produto_id, $valor_prod_usd, $quantidade, $cotacao_dolar, $frete_usd,
        $perc_seguro, $perc_ii, $perc_pis, $perc_cofins, $perc_ipi,
        $perc_desp_aduaneiras, $valor_comissao_compra,
        $perc_antidumping, $perc_icms,
        $perc_custo_financeiro, $perc_iof, $frete_regional
    ]);

    jsonResponse(['success' => true, 'message' => 'Custo salvo com sucesso.']);
}

if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    db()->prepare("DELETE FROM custo_produtos WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Custo removido com sucesso.']);
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
