<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$id     = (int)($_GET['id'] ?? $input['id'] ?? 0);

/* ──────────────────────────────────────────────── GET ──────── */
if ($method === 'GET') {

    /* Selects: clientes, tabelas, produtos com formação de preço */
    if (isset($_GET['selects'])) {
        $clientes = db()->query(
            "SELECT id, nome FROM clientes WHERE ativo=1 ORDER BY nome"
        )->fetchAll();

        $tabelas = db()->query(
            "SELECT id, nome, multiplicador FROM tabela_precos WHERE ativo=1 ORDER BY nome"
        )->fetchAll();

        $produtos = db()->query("
            SELECT p.id, p.nome, p.unidade_sigla,
                   c.perc_ipi,
                   fp.valor_venda, fp.perc_material,
                   fp.custo_unitario, fp.icms_custo_unitario,
                   fp.perc_desp_admin, fp.perc_desp_fixas,
                   fp.perc_comissao_venda, fp.perc_pos_venda,
                   fp.perc_icms_venda,
                   fp.perc_imp_interno_material, fp.perc_imp_interno_servico,
                   fp.valor_montagem
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            JOIN formacao_precos fp ON fp.produto_id = p.id
            WHERE p.ativo = 1
            ORDER BY p.nome
        ")->fetchAll();

        jsonResponse(['success' => true, 'clientes' => $clientes, 'tabelas' => $tabelas, 'produtos' => $produtos]);
    }

    /* Orçamento com itens */
    if ($id) {
        $stmt = db()->prepare("
            SELECT o.*, c.nome AS cliente_nome,
                   tp.nome AS tabela_nome, tp.multiplicador
            FROM orcamentos o
            JOIN clientes c  ON c.id  = o.cliente_id
            JOIN tabela_precos tp ON tp.id = o.tabela_preco_id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        $orc = $stmt->fetch();
        if (!$orc) jsonResponse(['success' => false, 'message' => 'Não encontrado.'], 404);

        $stmt2 = db()->prepare("
            SELECT oi.*, p.nome AS produto_nome, p.unidade_sigla
            FROM orcamento_itens oi
            JOIN produtos p ON p.id = oi.produto_id
            WHERE oi.orcamento_id = ?
            ORDER BY oi.id
        ");
        $stmt2->execute([$id]);
        $itens = $stmt2->fetchAll();

        jsonResponse(['success' => true, 'data' => $orc, 'itens' => $itens]);
    }

    /* Listagem com filtros */
    $q      = '%' . trim($_GET['q'] ?? '') . '%';
    $status = trim($_GET['status'] ?? '');
    $sql    = "SELECT o.id, o.numero, o.data_criacao, o.validade, o.status,
                      o.subtotal, o.total_geral, c.nome AS cliente_nome
               FROM orcamentos o
               JOIN clientes c ON c.id = o.cliente_id
               WHERE (o.numero LIKE ? OR c.nome LIKE ?)";
    $params = [$q, $q];
    if ($status) { $sql .= ' AND o.status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY o.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

/* ──────────────────── POST: alterar status ────────────────── */
if ($method === 'POST' && ($_GET['action'] ?? '') === 'status') {
    $novo   = trim($input['status'] ?? '');
    $validos = ['Rascunho', 'Enviado', 'Aprovado', 'Rejeitado', 'Cancelado'];
    if (!$id || !in_array($novo, $validos))
        jsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 422);
    db()->prepare("UPDATE orcamentos SET status=? WHERE id=?")->execute([$novo, $id]);
    jsonResponse(['success' => true, 'message' => "Status alterado para \"$novo\"."]);
}

/* ──────────────────── POST: salvar orçamento ───────────────── */
if ($method === 'POST') {
    $cliente_id      = (int)($input['cliente_id']      ?? 0);
    $tabela_id       = (int)($input['tabela_preco_id'] ?? 0);
    $data_criacao    = trim($input['data_criacao']      ?? date('Y-m-d'));
    $validade        = trim($input['validade']          ?? '') ?: null;
    $status          = trim($input['status']            ?? 'Rascunho');
    $observacoes     = trim($input['observacoes']       ?? '') ?: null;
    $tipo_desc       = trim($input['tipo_desconto']     ?? 'percentual');
    $desc_val        = (float)($input['desconto_valor']       ?? 0);
    $desc_perc       = (float)($input['desconto_percentual']  ?? 0);
    $prazo           = trim($input['prazo_entrega']     ?? '') ?: null;
    $cond_pag        = trim($input['condicao_pagamento'] ?? '') ?: null;
    $cond_ent        = trim($input['condicao_entrega']  ?? '') ?: null;
    $conds_gerais    = trim($input['condicoes_gerais']  ?? '') ?: null;
    $items           = $input['items'] ?? [];

    if (!$cliente_id) jsonResponse(['success' => false, 'message' => 'Cliente é obrigatório.'], 422);
    if (!$tabela_id)  jsonResponse(['success' => false, 'message' => 'Tabela de preço é obrigatória.'], 422);
    if (empty($items)) jsonResponse(['success' => false, 'message' => 'Adicione pelo menos um item.'], 422);

    $validos = ['Rascunho', 'Enviado', 'Aprovado', 'Rejeitado', 'Cancelado'];
    if (!in_array($status, $validos)) $status = 'Rascunho';

    /* Busca dados de formação p/ calcular perc_margem_liquida de cada item */
    $pids   = array_values(array_unique(array_filter(array_map(fn($i) => (int)($i['produto_id'] ?? 0), $items))));
    $formacoes = [];
    if ($pids) {
        $in    = implode(',', array_fill(0, count($pids), '?'));
        $fStmt = db()->prepare(
            "SELECT produto_id, custo_unitario, icms_custo_unitario,
                    perc_desp_admin, perc_desp_fixas, perc_comissao_venda, perc_pos_venda,
                    perc_icms_venda, perc_imp_interno_material, perc_imp_interno_servico, valor_montagem
             FROM formacao_precos WHERE produto_id IN ($in)"
        );
        $fStmt->execute($pids);
        foreach ($fStmt->fetchAll() as $row) $formacoes[$row['produto_id']] = $row;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* ---- Cabeçalho ---- */
        $campos = "cliente_id,tabela_preco_id,data_criacao,validade,status,observacoes,
                   tipo_desconto,prazo_entrega,condicao_pagamento,condicao_entrega,condicoes_gerais";
        $vals   = [$cliente_id, $tabela_id, $data_criacao, $validade, $status, $observacoes,
                   $tipo_desc, $prazo, $cond_pag, $cond_ent, $conds_gerais];

        if ($id) {
            $sets = implode(',', array_map(fn($c) => trim($c).'=?', explode(',', $campos)));
            $pdo->prepare("UPDATE orcamentos SET $sets WHERE id=?")->execute([...$vals, $id]);
            $orcId = $id;
            $pdo->prepare("DELETE FROM orcamento_itens WHERE orcamento_id=?")->execute([$orcId]);
        } else {
            $numero  = gerarNumeroOrcamento();
            $holders = implode(',', array_fill(0, count($vals) + 1, '?'));
            $pdo->prepare("INSERT INTO orcamentos (numero,$campos) VALUES ($holders)")
                ->execute([$numero, ...$vals]);
            $orcId = (int)$pdo->lastInsertId();
        }

        /* ---- Itens ---- */
        $insItem = $pdo->prepare(
            "INSERT INTO orcamento_itens
             (orcamento_id,produto_id,quantidade,valor_unitario,perc_material,perc_margem_liquida)
             VALUES (?,?,?,?,?,?)"
        );
        foreach ($items as $item) {
            $pid  = (int)($item['produto_id']    ?? 0);
            $qtd  = (float)($item['quantidade']  ?? 0);
            $vlr  = (float)($item['valor_unitario'] ?? 0);
            $pMat = (float)($item['perc_material']  ?? 0);
            if (!$pid || $qtd <= 0 || $vlr <= 0) continue;
            $margem = calcMargemItemPHP($vlr, $pMat, $formacoes[$pid] ?? null);
            $insItem->execute([$orcId, $pid, $qtd, $vlr, $pMat, $margem]);
        }

        /* ---- Totais (usa GENERATED COLUMNS do MySQL) ---- */
        $totStmt = $pdo->prepare("
            SELECT COALESCE(SUM(oi.valor_material),0) AS subtotal_material,
                   COALESCE(SUM(oi.valor_servico),0)  AS subtotal_servico,
                   COALESCE(SUM(oi.valor_total),0)    AS subtotal
            FROM orcamento_itens oi WHERE oi.orcamento_id=?
        ");
        $totStmt->execute([$orcId]);
        $tots = $totStmt->fetch();

        $ipiStmt = $pdo->prepare("
            SELECT COALESCE(SUM(oi.valor_total * c.perc_ipi / 100), 0) AS total_ipi
            FROM orcamento_itens oi
            JOIN produtos p  ON p.id  = oi.produto_id
            JOIN categorias c ON c.id = p.categoria_id
            WHERE oi.orcamento_id=? AND c.perc_ipi > 0
        ");
        $ipiStmt->execute([$orcId]);
        $totalIPI = (float)$ipiStmt->fetchColumn();

        $subtotal   = (float)$tots['subtotal'];
        $desconto   = $tipo_desc === 'valor' ? $desc_val : ($subtotal * $desc_perc / 100);
        $totalGeral = $subtotal + $totalIPI - $desconto;

        $pdo->prepare("UPDATE orcamentos SET subtotal_material=?,subtotal_servico=?,subtotal=?,
                       total_ipi=?,desconto_valor=?,desconto_percentual=?,total_geral=? WHERE id=?")
            ->execute([$tots['subtotal_material'], $tots['subtotal_servico'], $subtotal,
                       $totalIPI, $desc_val, $desc_perc, $totalGeral, $orcId]);

        $pdo->commit();
        jsonResponse(['success' => true, 'id' => $orcId,
            'message' => $id ? 'Orçamento atualizado com sucesso.' : 'Orçamento criado com sucesso.']);

    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()], 500);
    }
}

/* ─────────────────────── DELETE ────────────────────────────── */
if ($method === 'DELETE') {
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
    try {
        db()->prepare("DELETE FROM orcamentos WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Orçamento excluído com sucesso.']);
    } catch (PDOException) {
        jsonResponse(['success' => false, 'message' => 'Não foi possível excluir o orçamento.'], 422);
    }
}

jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);

/* ──────────── Helpers ─────────────────────────────────────── */
function calcMargemItemPHP(float $vlr, float $pMat, ?array $f): float {
    if (!$f || $vlr <= 0) return 0.0;
    $vMat  = $vlr * $pMat / 100;
    $vSrv  = $vlr - $vMat;
    $desp  = $vlr * ((float)$f['perc_desp_admin'] + (float)$f['perc_desp_fixas']
                   + (float)$f['perc_comissao_venda'] + (float)$f['perc_pos_venda']
                   + (float)$f['perc_icms_venda']) / 100
           + $vMat * (float)$f['perc_imp_interno_material'] / 100
           + $vSrv * (float)$f['perc_imp_interno_servico']  / 100
           + (float)($f['valor_montagem'] ?? 0)
           - (float)($f['icms_custo_unitario'] ?? 0);
    return $vlr > 0 ? (($vlr - (float)$f['custo_unitario'] - $desp) / $vlr) * 100 : 0.0;
}
