<?php
function jsonResponse(bool $success, string $message = '', mixed $data = null, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function getInput(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
    }
    return $_POST;
}

function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function moneyBr(mixed $v): string {
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function dateBr(?string $d): string {
    if (!$d) return '-';
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt ? $dt->format('d/m/Y') : $d;
}

function activeMenu(string $segment): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    if ($segment === 'dashboard') {
        $isActive = str_ends_with($uri, '/dashboard.php');
    } else {
        $isActive = str_contains($uri, "/$segment/");
    }
    return $isActive
        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
}

function getEmpresa(): array {
    static $empresa = null;
    if ($empresa === null) {
        $stmt = db()->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch() ?: ['nome' => 'Minha Empresa', 'cnpj' => '', 'telefone' => '', 'email' => '', 'endereco' => ''];
    }
    return $empresa;
}
