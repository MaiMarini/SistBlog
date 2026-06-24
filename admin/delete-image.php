<?php
/**
 * ADMIN — Deletar imagem (POST AJAX).
 *
 * Recebe: delete_url = URL pública relativa (ex.: "/assets/img/stitches/photos/corrente-1685.jpg")
 * Aceita CSRF token (mesmo padrão de upload-image.php).
 *
 * Path traversal protection: a URL precisa resolver pra um caminho real
 * DENTRO de /assets/img/. Qualquer tentativa de escapar (`../../`) é
 * bloqueada via realpath() + str_starts_with().
 *
 * Retorna JSON:
 *   { "success": true }
 *   { "success": false, "error": "..." }
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

function jsonOut(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => false, 'error' => 'Método inválido'], 405);
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonOut(['success' => false, 'error' => 'Token CSRF inválido'], 403);
}

$deleteUrl = trim($_POST['delete_url'] ?? '');
if ($deleteUrl === '') {
    jsonOut(['success' => false, 'error' => 'URL não fornecida']);
}

// Path traversal protection: tem que estar DENTRO de assets/img
$abs        = __DIR__ . '/../' . ltrim($deleteUrl, '/');
$assetsRoot = realpath(__DIR__ . '/../assets/img');
$realPath   = realpath($abs);

if (!$realPath || !$assetsRoot || !str_starts_with($realPath, $assetsRoot . DIRECTORY_SEPARATOR)) {
    jsonOut(['success' => false, 'error' => 'Caminho inválido (fora de assets/img)']);
}

if (!is_file($realPath)) {
    // Não é erro pro frontend — provavelmente o arquivo já foi deletado
    jsonOut(['success' => true, 'note' => 'arquivo não existia']);
}

if (!@unlink($realPath)) {
    jsonOut(['success' => false, 'error' => 'Falha ao apagar arquivo']);
}

jsonOut(['success' => true]);
