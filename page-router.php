<?php
/**
 * PAGE ROUTER — Kallme
 *
 * Substitui o antigo page.php. Recebe lang e slug do .htaccess:
 *   .htaccess: RewriteRule ^(br|en)/?(.*)$ page-router.php?lang=$1&slug=$2
 *
 * Decisão de roteamento:
 *   1. Idioma inválido       → força default (br)
 *   2. Slug vazio            → /pages/<lang>/home.php
 *   3. Slug com "/"          → categoria/artigo (busca no DB)
 *   4. Slug simples          → testa em ordem:
 *        4a. /pages/<lang>/<slug>.php (página estática como arquivo)
 *        4b. Categoria? (lista artigos da categoria)
 *        4c. Página no DB (slug + lang, sem categoria)
 *   5. Não achou             → /pages/<lang>/404.php ou 404 inline
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site-helpers.php';

// ---------- 1. Idioma ----------
$lang = $_GET['lang'] ?? '';
if (!in_array($lang, KALLME_LANGS, true)) {
    $lang = KALLME_DEFAULT_LANG;
}

// ---------- 2. Slug ----------
$slug = trim($_GET['slug'] ?? '', '/');

// Sanitização leve: aceitamos letras, números, hífens, e UMA barra (categoria/artigo)
if ($slug !== '' && !preg_match('#^[a-z0-9]+(?:[-a-z0-9]+)*(?:/[a-z0-9]+(?:[-a-z0-9]+)*)?$#i', $slug)) {
    renderNotFound($lang);
    exit;
}

// ---------- 3. Homepage do idioma ----------
if ($slug === '') {
    $homeFile = __DIR__ . "/pages/{$lang}/home.php";
    if (file_exists($homeFile)) {
        require $homeFile;
        exit;
    }
    renderPlaceholderHome($lang);
    exit;
}

// ---------- 4. Categoria + Artigo (slug com '/') ----------
if (strpos($slug, '/') !== false) {
    [$categorySlug, $articleSlug] = explode('/', $slug, 2);

    $category = getCategory($categorySlug, $lang);
    if (!$category) {
        renderNotFound($lang);
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM pages
        WHERE slug = ? AND category = ? AND language = ? AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$articleSlug, $categorySlug, $lang]);
    $page = $stmt->fetch();

    if (!$page) {
        renderNotFound($lang);
        exit;
    }

    renderDbPage($page, $lang, $category);
    exit;
}

// ---------- 5. Slug simples ----------

// 5a. Arquivo estático em /pages/<lang>/<slug>.php?
$staticFile = __DIR__ . "/pages/{$lang}/{$slug}.php";
if (file_exists($staticFile)) {
    require $staticFile;
    exit;
}

// 5b. É uma categoria? (mostra listagem de artigos dessa categoria)
$category = getCategory($slug, $lang);
if ($category) {
    $categoryListFile = __DIR__ . "/pages/{$lang}/_category.php";
    if (file_exists($categoryListFile)) {
        // O arquivo _category.php tem acesso a $category e $lang
        $categoryArticles = getArticles([
            'language' => $lang,
            'category' => $category['slug'],
        ]);
        require $categoryListFile;
        exit;
    }
    renderCategoryPlaceholder($category, $lang);
    exit;
}

// 5c. Página no banco (sem categoria) — ex: presell sem prefixo de categoria
$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT * FROM pages
    WHERE slug = ? AND language = ? AND (category IS NULL OR category = '') AND status = 'published'
    LIMIT 1
");
$stmt->execute([$slug, $lang]);
$page = $stmt->fetch();

if ($page) {
    renderDbPage($page, $lang, null);
    exit;
}

// ---------- 6. Não achou nada ----------
renderNotFound($lang);
exit;

// ============================================================
// HELPERS DE RENDER
// ============================================================

/**
 * Renderiza uma página vinda do DB usando o template apropriado.
 *
 *   page_type = 'article'   → templates/article.php (se existir; senão usa o `template` field)
 *   page_type = 'presell'   → templates/<page.template>.php (advertorial, structured, etc.)
 *   page_type = 'static'    → templates/static.php (se existir; senão fallback)
 *   page_type = 'home'      → não esperado aqui (homepage vem de arquivo PHP)
 *
 * @param array       $page     Linha da tabela `pages`.
 * @param string      $lang     Idioma corrente.
 * @param array|null  $category Categoria (se a página pertence a uma).
 */
function renderDbPage(array $page, string $lang, ?array $category): void {
    // Variáveis padrão expostas aos templates
    $comments = !empty($page['comments_json']) ? (json_decode($page['comments_json'], true) ?: []) : [];

    $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];
    $metaDescription = !empty($page['meta_description'])
        ? $page['meta_description']
        : mb_substr(strip_tags($page['content'] ?? ''), 0, 160);

    // Resolve qual template usar
    $pageType = $page['page_type'] ?? 'presell';
    $templateName = match ($pageType) {
        'article' => 'article',
        'static'  => 'static',
        default   => ($page['template'] ?: 'advertorial'),  // presell e demais
    };

    $templateFile = __DIR__ . '/templates/' . $templateName . '.php';
    if (!file_exists($templateFile)) {
        // Fallbacks razoáveis
        $templateFile = __DIR__ . '/templates/advertorial.php';
    }

    // $page, $comments, $metaTitle, $metaDescription, $lang, $category ficam disponíveis
    require $templateFile;
}

/**
 * Tela 404 — tenta arquivo customizado /pages/<lang>/404.php, senão inline.
 */
function renderNotFound(string $lang): void {
    http_response_code(404);
    $custom = __DIR__ . "/pages/{$lang}/404.php";
    if (file_exists($custom)) {
        require $custom;
        return;
    }
    $title = $lang === 'br' ? 'Página não encontrada' : 'Page not found';
    $msg = $lang === 'br'
        ? 'A página que você procura não existe ou foi movida.'
        : 'The page you are looking for does not exist or has been moved.';
    echo render404Html($title, $msg, $lang);
}

/**
 * Placeholder de homepage enquanto /pages/<lang>/home.php não existir.
 * Permite testar o roteamento sem ter homepage definitiva ainda.
 */
function renderPlaceholderHome(string $lang): void {
    $title = $lang === 'br' ? 'Em construção — Kallme' : 'Coming soon — Kallme';
    $msg = $lang === 'br'
        ? 'A homepage será criada na próxima fase. Por enquanto, navegue por slugs publicados.'
        : 'The homepage will be created in the next phase.';
    echo render404Html($title, $msg, $lang, 200);
}

/**
 * Placeholder de listagem de categoria enquanto /pages/<lang>/_category.php não existir.
 */
function renderCategoryPlaceholder(array $category, string $lang): void {
    $title = htmlspecialchars($category['name']);
    $msg = $lang === 'br'
        ? 'Listagem desta categoria será exibida aqui em breve.'
        : 'This category listing will be displayed here soon.';
    echo render404Html($title, $msg, $lang, 200);
}

/**
 * HTML mínimo de página simples — usado para 404 e placeholders.
 */
function render404Html(string $title, string $msg, string $lang, int $status = 404): string {
    if ($status !== 404) {
        http_response_code($status);
    }
    $titleEsc = htmlspecialchars($title);
    $msgEsc = htmlspecialchars($msg);
    $home = $lang === 'br' ? 'Voltar para a home' : 'Back to home';
    $homeUrl = url('', $lang);
    return <<<HTML
<!DOCTYPE html>
<html lang="$lang">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$titleEsc</title>
    <style>
        body{font-family:'Inter',system-ui,sans-serif;background:#FAF6F0;color:#23314A;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
        .box{max-width:520px;text-align:center}
        h1{font-family:'Playfair Display',Georgia,serif;font-size:42px;margin:0 0 16px}
        p{font-size:17px;line-height:1.6;margin:0 0 24px;color:#7E8AA0}
        a{display:inline-block;padding:14px 28px;background:#D03B47;color:#FAF6F0;text-decoration:none;border-radius:8px;font-weight:600;text-transform:uppercase;letter-spacing:1px}
        a:hover{background:#C03A3E}
    </style>
</head>
<body>
    <div class="box">
        <h1>$titleEsc</h1>
        <p>$msgEsc</p>
        <a href="$homeUrl">$home</a>
    </div>
</body>
</html>
HTML;
}
