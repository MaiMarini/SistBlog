<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $page = getPageById($id);
    if ($page) {
        // Remover imagens associadas
        if (!empty($page['main_image']) && file_exists(__DIR__ . '/../' . $page['main_image'])) {
            unlink(__DIR__ . '/../' . $page['main_image']);
        }
        if (!empty($page['author_avatar']) && file_exists(__DIR__ . '/../' . $page['author_avatar'])) {
            unlink(__DIR__ . '/../' . $page['author_avatar']);
        }
        deletePage($id);
        // Invalida o cache de categorias (contagem/disponibilidade no drawer)
        if (function_exists('clearCategoriesCache')) {
            clearCategoriesCache();
        }
    }
}

header('Location: ' . BASE_URL . 'admin/pages.php?msg=deleted');
exit;
