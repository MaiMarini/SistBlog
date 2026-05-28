<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die('Acesso inválido. Esta página só pode ser acessada via formulário.');
}

// Processar POST/FILES (não salva no banco)
$errors = [];
$data = buildPageDataFromPost($_POST, $_FILES, $errors);

// Montar $page como se viesse do banco
$page = $data;

// Preparar dados que o template espera
$metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];
$metaDescription = !empty($page['meta_description'])
    ? $page['meta_description']
    : mb_substr(strip_tags($page['content'] ?? ''), 0, 160);

$comments = json_decode($page['comments_json'] ?? '[]', true) ?: [];

// Selecionar template
$template = $page['template'] ?: 'structured';
$templateFile = __DIR__ . '/../templates/' . $template . '.php';

if (!file_exists($templateFile)) {
    $templateFile = __DIR__ . '/../templates/structured.php';
}

// Banner de "Modo Preview" no topo
echo '<div style="background:#e94560;color:#fff;padding:10px 20px;text-align:center;font-family:sans-serif;font-size:13px;font-weight:600;position:sticky;top:0;z-index:9999;">
    👁️ MODO PREVIEW — Esta visualização NÃO foi salva. Volte e clique em "Atualizar Página" para salvar.
</div>';

require $templateFile;
