<?php
/**
 * ADMIN — Deletar categoria
 *
 * Recusa se houver artigos publicados vinculados (a função
 * deleteCategory faz a verificação e retorna ['success', 'error']).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    header('Location: ' . BASE_URL . 'admin/categories.php');
    exit;
}

$result = deleteCategory($id);

if ($result['success']) {
    if (function_exists('clearCategoriesCache')) {
        clearCategoriesCache();
    }
    header('Location: ' . BASE_URL . 'admin/categories.php?msg=deleted');
} else {
    $msg = 'error:' . $result['error'];
    header('Location: ' . BASE_URL . 'admin/categories.php?msg=' . urlencode($msg));
}
exit;
