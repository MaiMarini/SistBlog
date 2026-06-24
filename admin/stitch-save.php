<?php
/**
 * ADMIN — Salvar ponto de crochê (POST handler).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stitches.php';
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

// Validações
$errors = [];

$name_br   = trim($_POST['name_br'] ?? '');
$slug      = trim($_POST['slug'] ?? '');
$abbrev_br = trim($_POST['abbrev_br'] ?? '');

if ($name_br === '') {
    $errors[] = 'Nome (PT) é obrigatório';
}
if ($abbrev_br === '') {
    $errors[] = 'Abreviação (PT) é obrigatória';
}
if ($slug === '') {
    $errors[] = 'Slug é obrigatório';
} elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    $errors[] = 'Slug pode conter apenas letras minúsculas, números e hífens';
} elseif (!isStitchSlugAvailable($slug, $id)) {
    $errors[] = 'Este slug já está em uso';
}

if (!empty($errors)) {
    $msg = 'error:' . implode('; ', $errors);
    header('Location: ' . BASE_URL . 'admin/stitches.php?msg=' . urlencode($msg));
    exit;
}

// Payload (whitelist)
$data = [
    'slug'            => $slug,
    'name_br'         => $name_br,
    'name_en'         => trim($_POST['name_en'] ?? '') ?: null,
    'abbrev_br'       => $abbrev_br,
    'abbrev_en'       => trim($_POST['abbrev_en'] ?? '') ?: null,
    'image_url'       => trim($_POST['image_url'] ?? '') ?: null,
    'photo_url'       => trim($_POST['photo_url'] ?? '') ?: null,
    'description_br'  => trim($_POST['description_br'] ?? '') ?: null,
    'description_en'  => trim($_POST['description_en'] ?? '') ?: null,
    'tutorial_anchor' => trim($_POST['tutorial_anchor'] ?? '') ?: null,
    'display_order'   => max(1, min(99, (int) ($_POST['display_order'] ?? 1))),
    'is_active'       => (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0,
];

$savedId = saveStitch($data, $id);
if (!$savedId) {
    header('Location: ' . BASE_URL . 'admin/stitches.php?msg=' . urlencode('error:Erro ao salvar'));
    exit;
}

// Salvar/editar ponto pode mudar `has_stitch_guide_br` da categoria crochê.
if (function_exists('clearCategoriesCache')) {
    clearCategoriesCache();
}

header('Location: ' . BASE_URL . 'admin/stitches.php?msg=saved');
exit;
