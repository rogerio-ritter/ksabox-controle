<?php
/**
 * functions.php — Funções utilitárias globais
 */

/**
 * Escapa HTML para prevenir XSS.
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formata valor monetário em Real Brasileiro.
 * Ex: 1234.56 => "R$ 1.234,56"
 */
function moneyBr(float|string|null $value): string {
    return 'R$ ' . number_format((float)($value ?? 0), 2, ',', '.');
}

/**
 * Formata data do formato MySQL (Y-m-d) para BR (d/m/Y).
 */
function dateBr(?string $date): string {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Formata data e hora do MySQL para BR.
 */
function dateTimeBr(?string $datetime): string {
    if (!$datetime) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Envia resposta JSON padronizada e encerra a execução.
 */
function jsonResponse(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Lê o body da requisição.
 * Suporta application/json e application/x-www-form-urlencoded / multipart.
 */
function getInput(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
    return $_POST;
}

/**
 * Verifica se o tema do usuário logado é escuro.
 */
function isDark(): bool {
    return ($_SESSION['user']['tema'] ?? 'claro') === 'escuro';
}

/**
 * Retorna a classe CSS de badge para status de orçamento.
 */
function badgeOrcamento(string $status): string {
    return match ($status) {
        'Rascunho'  => 'bg-gray-100 text-gray-700',
        'Enviado'   => 'bg-blue-100 text-blue-700',
        'Aprovado'  => 'bg-green-100 text-green-700',
        'Rejeitado' => 'bg-red-100 text-red-700',
        'Cancelado' => 'bg-yellow-100 text-yellow-700',
        default     => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Converte valor em formato BR (1.234,56) para float.
 */
function parseMoney(string $value): float {
    $value = str_replace(['R$', ' ', '.'], '', $value);
    $value = str_replace(',', '.', $value);
    return (float)$value;
}

/**
 * Gera número sequencial de orçamento: ORC-YYYYMMDD-NNN
 */
function gerarNumeroOrcamento(): string {
    $hoje = date('Ymd');
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM orcamentos WHERE numero LIKE ?"
    );
    $stmt->execute(["ORC-$hoje-%"]);
    $count = (int)$stmt->fetchColumn();
    return sprintf('ORC-%s-%03d', $hoje, $count + 1);
}
