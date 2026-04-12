<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$input  = getInput();
$action = $input['action'] ?? '';
$user   = currentUser();

function gerarNumero(PDO $db): string {
    $ano  = date('Y');
    $stmt = $db->query("SELECT COUNT(*) FROM orcamentos WHERE YEAR(created_at) = $ano");
    $seq  = (int)$stmt->fetchColumn() + 1;
    return sprintf('ORC-%s-%04d', $ano, $seq);
}

try {
    switch ($action) {
        case 'create':
        case 'update':
            $db    = db();
            $isNew = ($action === 'create');

            // Para update, validar ID
            $orcId = 0;
            if (!$isNew) {
                $orcId = (int)($input['id'] ?? 0);
                if (!$orcId) jsonResponse(false, 'ID do orçamento inválido.');
            }

            $numero = trim($input['numero'] ?? '');
            if (!$numero) $numero = gerarNumero($db);

            $fields = [
                'numero'        => $numero,
                'cliente_id'    => $input['cliente_id']    ?: null,
                'usuario_id'    => $user['id'],
                'tabela_id'     => $input['tabela_id']     ?: null,
                'status'        => $input['status']        ?? 'rascunho',
                'data_criacao'  => $input['data_criacao']  ?: date('Y-m-d'),
                'data_validade' => $input['data_validade'] ?: null,
                'observacoes'   => trim($input['observacoes'] ?? ''),
            ];

            // Calcular total
            $total = 0;
            $itens = $input['itens'] ?? [];
            foreach ($itens as &$it) {
                $it['quantidade']     = (float)($it['quantidade']     ?? 1);
                $it['preco_unitario'] = (float)($it['preco_unitario'] ?? 0);
                $it['total']          = round($it['quantidade'] * $it['preco_unitario'], 2);
                $total               += $it['total'];
            }
            unset($it);
            $fields['total'] = round($total, 2);

            if ($isNew) {
                $cols = implode(',', array_keys($fields));
                $plch = implode(',', array_fill(0, count($fields), '?'));
                $db->prepare("INSERT INTO orcamentos ($cols) VALUES ($plch)")->execute(array_values($fields));
                $orcId = (int)$db->lastInsertId();
            } else {
                $set = implode(',', array_map(fn($k) => "$k=?", array_keys($fields)));
                $db->prepare("UPDATE orcamentos SET $set WHERE id=?")->execute([...array_values($fields), $orcId]);
                $db->prepare('DELETE FROM orcamento_itens WHERE orcamento_id=?')->execute([$orcId]);
            }

            // Gravar itens
            $stmtIt = $db->prepare('INSERT INTO orcamento_itens (orcamento_id,produto_id,descricao,quantidade,preco_unitario,total) VALUES (?,?,?,?,?,?)');
            foreach ($itens as $it) {
                $desc = trim($it['descricao'] ?? '');
                if (!$desc && !$it['produto_id']) continue;
                $stmtIt->execute([$orcId, $it['produto_id'] ?: null, $desc, $it['quantidade'], $it['preco_unitario'], $it['total']]);
            }

            jsonResponse(true, $isNew ? 'Orçamento criado!' : 'Orçamento atualizado!', ['id' => $orcId, 'numero' => $numero]);

        case 'delete':
            if (empty($input['id'])) jsonResponse(false, 'ID inválido.');
            db()->prepare('DELETE FROM orcamentos WHERE id=?')->execute([(int)$input['id']]);
            jsonResponse(true, 'Orçamento excluído!');

        default:
            jsonResponse(false, 'Ação inválida.', null, 400);
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Erro: ' . $e->getMessage(), null, 500);
}
