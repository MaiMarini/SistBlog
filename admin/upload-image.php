<?php
/**
 * ADMIN — Upload AJAX de imagens.
 *
 * Suporta DOIS modos (detectados pelos parâmetros enviados):
 *
 * MODO RECEITAS (legado, R-C1.2)
 *   POST:
 *     file         O arquivo binário (JPG/PNG/WEBP, ≤ 10MB)
 *     csrf_token   Token CSRF da sessão
 *     recipe_slug  Slug da receita (opcional; cai pra "temp-..." se vazio)
 *   → salva em /assets/img/recipes/<slug>/img-<uniqid>.<ext>
 *
 * MODO GENÉRICO (R-A3 — foto dos pontos e outros usos)
 *   POST:
 *     image          O arquivo binário (JPG/PNG, ≤ 10MB) [também aceita "file"]
 *     csrf_token     Token CSRF da sessão
 *     folder         Subpasta dentro de /assets/img/ (ex.: "stitches/photos")
 *     filename_base  Nome base do arquivo (sem extensão; será sanitizado)
 *     delete_old     [opcional] URL pública do arquivo antigo a deletar
 *   → salva em /assets/img/<folder>/<base>-<timestamp>.<ext>
 *
 * Retorna JSON:
 *   { "success": true,  "url": "/assets/img/...", "filename": "..." }
 *   { "success": false, "error": "..." }
 *
 * Em ambos os modos:
 *   1. Valida tipo (PNG/JPG sempre; WEBP só no modo receitas)
 *   2. Valida tamanho ≤ 10 MB
 *   3. Cria pasta destino se necessário
 *   4. Redimensiona pra max 1600px de largura (preserva alpha em PNG/WEBP)
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

// ---------- Detecta arquivo (image OU file) ----------
$fileKey = isset($_FILES['image']) ? 'image'
        : (isset($_FILES['file']) ? 'file' : null);
if (!$fileKey) {
    jsonOut(['success' => false, 'error' => 'Nenhum arquivo enviado']);
}
$file = $_FILES[$fileKey];

// ---------- Detecta modo ----------
$folder        = trim($_POST['folder'] ?? '');
$filenameBase  = trim($_POST['filename_base'] ?? '');
$isGenericMode = $folder !== '' && $filenameBase !== '';

// ---------- Erros nativos do PHP de upload ----------
if ($file['error'] !== UPLOAD_ERR_OK) {
    $iniMax = ini_get('upload_max_filesize') ?: 'desconhecido';
    $errorMap = [
        UPLOAD_ERR_INI_SIZE   => "Arquivo maior que o limite do servidor ({$iniMax}). Reduza o tamanho da foto.",
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo maior que o limite do formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Servidor sem pasta temporária.',
        UPLOAD_ERR_CANT_WRITE => 'Sem permissão pra escrever no servidor.',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão PHP.',
    ];
    jsonOut(['success' => false, 'error' => $errorMap[$file['error']] ?? 'Erro desconhecido no upload']);
}

// ---------- Tamanho (10 MB) ----------
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    $sizeMb = round($file['size'] / 1024 / 1024, 1);
    jsonOut([
        'success' => false,
        'error'   => "Arquivo muito grande ({$sizeMb} MB). Máximo: 10 MB.",
    ]);
}

// ---------- Tipo ----------
$fileType = mime_content_type($file['tmp_name']);
$allowedTypes = $isGenericMode
    ? ['image/jpeg', 'image/png']             // modo genérico: só JPG/PNG (não SVG, não WEBP)
    : ['image/jpeg', 'image/png', 'image/webp']; // modo receitas: WEBP permitido
if (!in_array($fileType, $allowedTypes, true)) {
    $msg = $isGenericMode
        ? "Tipo inválido. Use JPG ou PNG. Recebido: {$fileType}"
        : "Apenas JPG, PNG ou WEBP. Recebido: {$fileType}";
    jsonOut(['success' => false, 'error' => $msg]);
}

$ext = match ($fileType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
};

// ---------- Resolve pasta e nome do arquivo conforme o modo ----------
if ($isGenericMode) {
    // Folder pode ter subpastas (ex.: "stitches/photos"). Sanitiza estritamente.
    if (!preg_match('#^[a-z0-9_\-]+(?:/[a-z0-9_\-]+)*$#', $folder)) {
        jsonOut(['success' => false, 'error' => 'Pasta inválida (use apenas letras minúsculas/números/hífen/barra)']);
    }
    // Sanitiza base: apenas letras minúsculas/números/hífen
    $filenameBase = preg_replace('/[^a-z0-9\-]/', '', strtolower($filenameBase));
    if ($filenameBase === '') {
        jsonOut(['success' => false, 'error' => 'Nome base inválido']);
    }
    $filename  = $filenameBase . '-' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/img/' . $folder . '/';
    $publicUrl = '/assets/img/' . $folder . '/' . $filename;

    // Deleta arquivo antigo (se informado) — com path traversal protection
    $deleteOld = trim($_POST['delete_old'] ?? '');
    if ($deleteOld !== '') {
        $oldAbs        = __DIR__ . '/../' . ltrim($deleteOld, '/');
        $assetsRoot    = realpath(__DIR__ . '/../assets/img');
        $oldReal       = realpath($oldAbs);
        if ($oldReal && $assetsRoot && str_starts_with($oldReal, $assetsRoot . DIRECTORY_SEPARATOR) && is_file($oldReal)) {
            @unlink($oldReal);
        }
    }
} else {
    // Modo legado (receitas)
    $recipeSlug = trim($_POST['recipe_slug'] ?? '');
    if ($recipeSlug === '' || !preg_match('/^[a-z0-9\-]+$/', $recipeSlug)) {
        $recipeSlug = 'temp-' . date('Ymd-His');
    }
    $filename  = uniqid('img-', false) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/img/recipes/' . $recipeSlug . '/';
    $publicUrl = '/assets/img/recipes/' . $recipeSlug . '/' . $filename;
}

// ---------- Cria pasta destino se preciso ----------
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        jsonOut(['success' => false, 'error' => 'Erro ao criar pasta de destino']);
    }
}

$targetPath = $targetDir . $filename;

// ---------- Redimensiona (se > 1600px) ou move direto ----------
$info = @getimagesize($file['tmp_name']);
if (!$info) {
    jsonOut(['success' => false, 'error' => 'Arquivo não é uma imagem válida']);
}
[$origWidth, $origHeight] = $info;
$maxWidth = 1600;

if ($origWidth > $maxWidth && function_exists('imagecreatefromjpeg')) {
    $ratio = $maxWidth / $origWidth;
    $newW  = $maxWidth;
    $newH  = (int) round($origHeight * $ratio);

    $source = match ($fileType) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
    };

    if (!$source) {
        jsonOut(['success' => false, 'error' => 'Erro ao processar imagem']);
    }

    $canvas = imagecreatetruecolor($newW, $newH);

    if (in_array($fileType, ['image/png', 'image/webp'], true)) {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
    }

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origWidth, $origHeight);

    $saved = match ($fileType) {
        'image/jpeg' => imagejpeg($canvas, $targetPath, 85),
        'image/png'  => imagepng($canvas, $targetPath, 6),
        'image/webp' => imagewebp($canvas, $targetPath, 85),
    };

    imagedestroy($source);
    imagedestroy($canvas);

    if (!$saved) {
        jsonOut(['success' => false, 'error' => 'Erro ao salvar imagem redimensionada']);
    }
} else {
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonOut(['success' => false, 'error' => 'Erro ao mover arquivo']);
    }
}

@chmod($targetPath, 0644);

jsonOut([
    'success'  => true,
    'url'      => $publicUrl,
    'filename' => $filename,
]);
