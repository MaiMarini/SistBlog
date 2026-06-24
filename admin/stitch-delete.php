<?php
/**
 * ADMIN — Deletar ponto de crochê.
 *
 * Sem proteção por uso (Fase A — receitas ainda não vinculam pontos).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stitches.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/stitches.php');
    exit;
}

// Antes de deletar do banco, apaga o arquivo da foto (se houver) pra não
// deixar lixo em /assets/img/stitches/photos/. Path traversal protection
// via realpath() + str_starts_with() na raiz de assets/img.
$stitch = getStitchById($id);
if ($stitch && !empty($stitch['photo_url'])) {
    $abs        = __DIR__ . '/../' . ltrim($stitch['photo_url'], '/');
    $assetsRoot = realpath(__DIR__ . '/../assets/img');
    $realPath   = realpath($abs);
    if ($realPath && $assetsRoot && str_starts_with($realPath, $assetsRoot . DIRECTORY_SEPARATOR) && is_file($realPath)) {
        @unlink($realPath);
    }
}

$result = deleteStitch($id);

if ($result['success']) {
    // Deletar ponto pode zerar `has_stitch_guide_br` da categoria crochê.
    if (function_exists('clearCategoriesCache')) {
        clearCategoriesCache();
    }
    header('Location: ' . BASE_URL . 'admin/stitches.php?msg=deleted');
} else {
    $msg = 'error:' . ($result['error'] ?? 'Erro ao excluir');
    header('Location: ' . BASE_URL . 'admin/stitches.php?msg=' . urlencode($msg));
}
exit;
