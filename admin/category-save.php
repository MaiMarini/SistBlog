<?php
/**
 * ADMIN — Salvar categoria (POST handler)
 *
 * Recebe o submit do category-form.php, valida e redireciona
 * pra categories.php com ?msg=... refletindo o resultado.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Token de segurança inválido. Volte e tente novamente.');
}

$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : null;

// ---------- Validações ----------
$errors = [];

$name_br    = trim($_POST['name_br'] ?? '');
$slug       = trim($_POST['slug'] ?? '');
$icon_value = trim($_POST['icon_value'] ?? '');
$icon_type  = in_array($_POST['icon_type'] ?? '', ['phosphor', 'svg'], true)
                ? $_POST['icon_type']
                : 'phosphor';

if ($name_br === '') {
    $errors[] = 'Nome (PT) é obrigatório';
}
if ($slug === '') {
    $errors[] = 'Slug é obrigatório';
} elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    $errors[] = 'Slug pode conter apenas letras minúsculas, números e hífens';
} elseif (!isCategorySlugAvailable($slug, $id)) {
    $errors[] = 'Este slug já está em uso';
}
if ($icon_value === '') {
    $errors[] = 'Ícone é obrigatório';
}

if (!empty($errors)) {
    $msg = 'error:' . implode('; ', $errors);
    header('Location: ' . BASE_URL . 'admin/categories.php?msg=' . urlencode($msg));
    exit;
}

// Cores (validar formato hex — qualquer valor inválido vira NULL → fallback no template)
$colorBg   = trim($_POST['color_bg'] ?? '');
$colorText = trim($_POST['color_text'] ?? '');
if ($colorBg !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $colorBg)) {
    $colorBg = '';
}
if ($colorText !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $colorText)) {
    $colorText = '';
}

// ---------- Montagem do payload (whitelist) ----------
$data = [
    'slug'             => $slug,
    'name_br'          => $name_br,
    'name_en'          => trim($_POST['name_en'] ?? '') ?: null,
    'description_br'   => trim($_POST['description_br'] ?? '') ?: null,
    'description_en'   => trim($_POST['description_en'] ?? '') ?: null,
    'quote_text_br'    => trim($_POST['quote_text_br'] ?? '') ?: null,
    'quote_text_en'    => trim($_POST['quote_text_en'] ?? '') ?: null,
    'quote_author_br'  => trim($_POST['quote_author_br'] ?? '') ?: null,
    'quote_author_en'  => trim($_POST['quote_author_en'] ?? '') ?: null,
    'icon_type'        => $icon_type,
    'icon_value'       => $icon_value,
    'color_bg'         => $colorBg ?: null,
    'color_text'       => $colorText ?: null,
    'display_order'    => max(1, min(99, (int) ($_POST['display_order'] ?? 1))),
    'is_active'        => (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0,
];

$savedId = saveCategory($data, $id);

if (!$savedId) {
    header('Location: ' . BASE_URL . 'admin/categories.php?msg=' . urlencode('error:Erro ao salvar'));
    exit;
}

// Invalida o cache do drawer público
if (function_exists('clearCategoriesCache')) {
    clearCategoriesCache();
}

header('Location: ' . BASE_URL . 'admin/categories.php?msg=saved');
exit;
