<?php
/**
 * SITE DRAWER — Kallme
 *
 * Menu lateral que abre da esquerda ao clicar no hambúrguer do header.
 * Mostra: logo pequena + navegação principal + categorias (com "em breve").
 *
 * Esperado em escopo (definido por site-header.php):
 *   $lang  string  Idioma atual ('br' | 'en')
 *
 * JS de abrir/fechar: /assets/js/header.js (incluído em site-footer.php).
 */

$lang = $lang ?? 'br';

// Categorias vêm do banco (schema bilíngue) com contagem de artigos.
// A disponibilidade (link ativo vs "em breve") é calculada por idioma.
$drawerCats = getCategoriesWithArticleCount();

// Labels por idioma
$navTitle = $lang === 'en' ? 'Navigation' : 'Navegação';
$catTitle = $lang === 'en' ? 'Categories' : 'Categorias';
$comingSoon = $lang === 'en' ? 'soon' : 'em breve';
?>

<aside class="drawer" id="drawer" aria-hidden="true" aria-label="<?= e($navTitle) ?>">
    <button class="drawer__close" type="button" aria-label="Fechar menu">
        <i class="ph-light ph-x icon-md"></i>
    </button>

    <div class="drawer__content">

        <!-- LOGO PEQUENA -->
        <a href="<?= e(url('', $lang)) ?>" class="drawer__logo" aria-label="Kallme">
            <img src="<?= BASE_URL ?>assets/img/logo-icon.png" alt="Kallme" class="drawer__logo-img"
                 onerror="this.style.display='none'">
        </a>

        <!-- NAVEGAÇÃO PRINCIPAL -->
        <nav class="drawer__nav" aria-label="<?= e($navTitle) ?>">
            <h3 class="drawer__section-title"><?= e($navTitle) ?></h3>
            <a href="<?= e(url('', $lang)) ?>"><?= $lang === 'en' ? 'Home' : 'Home' ?></a>
            <a href="<?= e(url($lang === 'en' ? 'about' : 'sobre', $lang)) ?>"><?= $lang === 'en' ? 'About' : 'Sobre' ?></a>
            <a href="<?= e(url($lang === 'en' ? 'contact' : 'contato', $lang)) ?>"><?= $lang === 'en' ? 'Contact' : 'Contato' ?></a>
        </nav>

        <!-- CATEGORIAS: link ativo se tem artigos publicados; senão "em breve" -->
        <nav class="drawer__nav" aria-label="<?= e($catTitle) ?>">
            <h3 class="drawer__section-title"><?= e($catTitle) ?></h3>
            <?php foreach ($drawerCats as $cat): ?>
                <?php
                $isAvailable = $lang === 'en' ? $cat['is_available_en'] : $cat['is_available_br'];
                $name = ($lang === 'en' && !empty($cat['name_en'])) ? $cat['name_en'] : $cat['name_br'];
                ?>
                <?php if ($isAvailable): ?>
                    <a href="<?= e(url($cat['slug'], $lang)) ?>">
                        <?= renderCategoryIcon($cat, 'icon-sm') ?>
                        <span><?= e($name) ?></span>
                    </a>
                <?php else: ?>
                    <span class="drawer__category-placeholder">
                        <?= renderCategoryIcon($cat, 'icon-sm') ?>
                        <span><?= e($name) ?></span>
                        <small class="drawer__coming-soon"><?= e($comingSoon) ?></small>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

    </div>
</aside>

<div class="drawer-overlay" id="drawerOverlay" aria-hidden="true"></div>
