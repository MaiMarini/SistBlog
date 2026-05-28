<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '', '/');

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'admin/');
    exit;
}

$page = getPage($slug);

if (!$page) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Página não encontrada</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; color: #333; }
            .error-page { text-align: center; padding: 40px; }
            .error-page h1 { font-size: 72px; color: #ddd; margin-bottom: 10px; }
            .error-page p { font-size: 18px; color: #888; margin-bottom: 20px; }
            .error-page a { color: #e85d04; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="error-page">
            <h1>404</h1>
            <p>Página não encontrada.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Preparar dados
$comments = json_decode($page['comments_json'] ?? '[]', true) ?: [];
$metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : mb_substr(strip_tags($page['content']), 0, 160);

// Carregar template
$template = $page['template'] ?: 'advertorial';
$templateFile = __DIR__ . '/templates/' . $template . '.php';

if (!file_exists($templateFile)) {
    $templateFile = __DIR__ . '/templates/advertorial.php';
}

require $templateFile;
