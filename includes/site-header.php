<?php
/**
 * SITE HEADER — Kallme
 *
 * Header HTML compartilhado entre páginas estáticas (sobre, contato, blog)
 * e artigos. Pre-sells NÃO devem usar este arquivo — elas têm renderização
 * própria nos templates/*.php.
 *
 * Variáveis esperadas (todas opcionais; o arquivo tem defaults):
 *   $lang             string  Idioma da página ('br' | 'en'). Default: getCurrentLanguage()
 *   $pageTitle        string  <title> da página. Default: getSetting('site_name')
 *   $pageDescription  string  meta description. Default: setting padrão por idioma
 *   $pageSlug         string  Slug atual (sem o prefixo /lang/), usado pra hreflang.
 *                              Default: detectado de $_GET['slug']
 *   $canonicalUrl     string  URL canônica completa. Default: construída automaticamente
 *   $extraHead        string  HTML extra para o <head> (opcional)
 *
 * O que esta header já faz:
 *   - charset, viewport
 *   - title + meta description
 *   - Google Fonts (Playfair Display + Inter)
 *   - Lucide icons (CDN)
 *   - Stylesheet site.css
 *   - hreflang BR/EN/x-default
 *   - canonical
 *   - Trackings globais (6 settings de tracking)
 *   - <header> visual com logo + nav + seletor de idioma
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/site-helpers.php';

// ---------- Defaults ----------
$lang = $lang ?? getCurrentLanguage();
$siteName = getSetting('site_name', 'Kallme');
$pageTitle = $pageTitle ?? $siteName;

// Default meta description por idioma (vem das settings)
$defaultMetaDesc = $lang === 'en'
    ? getSetting('default_meta_description_en', '')
    : getSetting('default_meta_description_br', '');
$pageDescription = $pageDescription ?? $defaultMetaDesc;

// Slug atual (sem prefixo de idioma) — usado para construir hreflang das outras línguas
$pageSlug = $pageSlug ?? trim($_GET['slug'] ?? '', '/');

// Detecta esquema e host para construir URLs absolutas (canonical / hreflang)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'kallme.online';
$siteOrigin = $scheme . '://' . $host;

// URL canônica
$canonicalUrl = $canonicalUrl ?? ($siteOrigin . '/' . $lang . ($pageSlug ? '/' . $pageSlug : '/'));

// Chave do item ativo no nav (passada pela página). Valores possíveis:
//   'home' | 'sobre' | 'contato' | 'privacidade' | 'termos' | 'divulgacao' | <slug-de-categoria>
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'br' ? 'pt-BR' : e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">

    <!-- hreflang multilíngue (SEO) -->
    <link rel="alternate" hreflang="pt-BR"   href="<?= e($siteOrigin . '/br' . ($pageSlug ? '/' . $pageSlug : '/')) ?>">
    <link rel="alternate" hreflang="en"      href="<?= e($siteOrigin . '/en' . ($pageSlug ? '/' . $pageSlug : '/')) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($siteOrigin . '/br' . ($pageSlug ? '/' . $pageSlug : '/')) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">

    <!-- Google Fonts: Playfair Display (display) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons (regular + light) — font-based, sem JS -->
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/light/style.css">

    <!-- Site stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site.css">

    <!-- Favicon (arquivos na raiz do site) -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#FAF6F0">

    <!-- ====== TRACKINGS GLOBAIS ====== -->
    <?php
    // Cada setting já contém o <script>...</script> ou <meta> completo.
    // Imprimimos cru (sem escape) — confiamos no admin pra colar código válido.
    foreach (['tracking_global_ga4', 'tracking_global_googleads', 'tracking_global_pinterest',
              'tracking_global_facebook', 'tracking_global_tiktok', 'tracking_global_custom'] as $tk):
        $code = getSetting($tk, '');
        if (trim($code) === '') continue;
    ?>
    <!-- <?= e($tk) ?> -->
    <?= $code ?>
    <?php endforeach; ?>

    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="site-body lang-<?= e($lang) ?>">

<!-- ===== HEADER HORIZONTAL (estilo Charlotte) ===== -->
<header class="site-header">
    <div class="container container-wide site-header__inner">

        <!-- ZONA ESQUERDA: botão hambúrguer (abre drawer) -->
        <button class="header-menu-btn" id="drawerOpen" type="button"
                aria-label="Abrir menu" aria-controls="drawer">
            <i class="ph ph-list icon-md"></i>
        </button>

        <!-- ZONA CENTRO: navegação inline.
             Em páginas que não são a home, o "Kallme" pequeno aparece entre os links. -->
        <nav class="site-nav" aria-label="Menu principal">
            <a href="<?= e(url('', $lang)) ?>"
               class="<?= $activeNav === 'home' ? 'is-active' : '' ?>">
                <?= $lang === 'en' ? 'Home' : 'Home' ?>
            </a>

            <?php if (($activeNav ?? '') !== 'home'): ?>
                <a href="<?= e(url('', $lang)) ?>" class="site-nav__brand"><?= e($siteName) ?></a>
            <?php endif; ?>

            <a href="<?= e(url($lang === 'en' ? 'about' : 'sobre', $lang)) ?>"
               class="<?= $activeNav === 'sobre' ? 'is-active' : '' ?>">
                <?= $lang === 'en' ? 'About' : 'Sobre' ?>
            </a>
            <a href="<?= e(url($lang === 'en' ? 'contact' : 'contato', $lang)) ?>"
               class="<?= $activeNav === 'contato' ? 'is-active' : '' ?>">
                <?= $lang === 'en' ? 'Contact' : 'Contato' ?>
            </a>
        </nav>

        <!-- ZONA DIREITA: Pinterest + busca -->
        <div class="header-actions">
            <a href="<?= e(getSetting('social_pinterest', '#')) ?>"
               target="_blank" rel="noopener"
               class="header-action" aria-label="Pinterest">
                <i class="ph ph-pinterest-logo icon-sm"></i>
            </a>
            <button class="header-action" id="searchBtn" type="button" aria-label="Buscar">
                <i class="ph ph-magnifying-glass icon-sm"></i>
            </button>
        </div>

    </div>
</header>

<?php include __DIR__ . '/site-drawer.php'; ?>
