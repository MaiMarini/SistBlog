<?php
/**
 * TEMPLATE — Página de categoria
 *
 * Chamado pelo page-router.php (regra 5b) com:
 *   $category  array  Categoria localizada (do getCategory)
 *   $lang      string Idioma (br|en)
 *
 * Layout:
 *   - Header minimalista (ícone + título + descrição ou citação + ❀)
 *   - Estado vazio se 0 artigos
 *   - 1 artigo destaque (sempre o mais recente)
 *   - Grid de 3 colunas com os demais artigos
 */

if (!isset($category) || !is_array($category)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/recipes.php';
require_once __DIR__ . '/../includes/stitches.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/site-helpers.php';

$lang = $lang ?? getCurrentLanguage();

// Nome / descrição / citação — getCategory já preencheu `name` e `description`,
// mas continuo lendo dos campos i18n diretos pra ficar à prova de fallback.
$categoryName = $category['name']
    ?? (($lang === 'en' && !empty($category['name_en'])) ? $category['name_en'] : $category['name_br']);

$categoryDescription = ($lang === 'en' && !empty($category['description_en']))
    ? $category['description_en']
    : ($category['description_br'] ?? '');

$quoteText = ($lang === 'en' && !empty($category['quote_text_en']))
    ? $category['quote_text_en']
    : ($category['quote_text_br'] ?? '');

$quoteAuthor = ($lang === 'en' && !empty($category['quote_author_en']))
    ? $category['quote_author_en']
    : ($category['quote_author_br'] ?? '');

$hasQuote = trim((string) $quoteText) !== '';

$categoryIcon = $category['icon_value'] ?? 'ph-light ph-folder';
$isIconSVG = ($category['icon_type'] ?? 'phosphor') === 'svg';

// Conteúdo publicado da categoria (artigos + receitas, mais novos primeiro)
$articles = getCategoryContent($category['slug'], $lang);
$articleCount = count($articles);

// Variáveis pro site-header.php
$pageTitle = $categoryName . ' — ' . getSetting('site_name', 'Kallme');
$pageDescription = $categoryDescription ?: ($lang === 'en'
    ? "Articles on {$categoryName}"
    : "Artigos sobre {$categoryName}");
$pageSlug = $category['slug'];
$activeNav = $category['slug'];

include __DIR__ . '/../includes/site-header.php';
?>

<main class="category-page">

    <!-- ============================================ HEADER MINIMALISTA -->
    <header class="category-hero">
        <div class="category-hero__icon">
            <?php if ($isIconSVG): ?>
                <?= renderCategoryIcon($category) ?>
            <?php else: ?>
                <i class="<?= e($categoryIcon) ?>"></i>
            <?php endif; ?>
        </div>

        <h1 class="category-hero__title"><?= e($categoryName) ?></h1>

        <?php if ($hasQuote): ?>
            <blockquote class="category-hero__quote">
                <p>&ldquo;<?= e($quoteText) ?>&rdquo;</p>
                <?php if (!empty($quoteAuthor)): ?>
                    <cite>— <?= e($quoteAuthor) ?></cite>
                <?php endif; ?>
            </blockquote>
        <?php elseif (!empty($categoryDescription)): ?>
            <p class="category-hero__description"><?= e($categoryDescription) ?></p>
        <?php endif; ?>

        <hr class="category-hero__divider">

        <?php if ($articleCount > 0): ?>
            <div class="category-hero__count">
                <?php
                $unit = $articleCount === 1
                    ? ($lang === 'en' ? 'article' : 'leitura')
                    : ($lang === 'en' ? 'articles' : 'leituras');
                ?>
                <?= $articleCount ?> <?= $unit ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($articleCount === 0): ?>

        <!-- ============================================ ESTADO VAZIO -->
        <section class="category-empty">
            <div class="category-empty__inner">
                <i class="ph-light ph-coffee category-empty__icon"></i>
                <h2 class="category-empty__title"><?= $lang === 'en' ? 'Coming soon' : 'Em breve' ?></h2>
                <p class="category-empty__text">
                    <?= $lang === 'en'
                        ? 'No articles published in this category yet. Come back in a few days — I\'m writing.'
                        : 'Ainda não publiquei artigos nessa categoria. Volte daqui a uns dias — estou escrevendo.' ?>
                </p>
                <a href="<?= e(url('', $lang)) ?>" class="category-empty__cta">
                    <?= $lang === 'en' ? 'Back to home' : 'Voltar pra home' ?>
                </a>
            </div>
        </section>

    <?php else: ?>

        <!-- ============================================ GRID MISTO -->
        <section class="category-grid">

            <!-- Conteúdo destaque (primeiro/mais recente — artigo ou receita) -->
            <?php
                $featured = $articles[0];
                $isRecipe = ($featured['page_type'] ?? '') === 'recipe';
                $diffVisual = $isRecipe ? getDifficultyVisual($featured['difficulty'] ?? null) : null;

                // Em /br/croche o destaque é precedido por um card promocional do Guia de Pontos.
                // Em outras categorias o destaque ocupa a largura toda como antes.
                $isCrocheBr = ($lang === 'br' && ($category['slug'] ?? '') === 'croche');
            ?>

            <?php if ($isCrocheBr): ?>
                <div class="category-top-row">
                    <aside class="guide-promo-card">
                        <div class="guide-promo-card__icon">
                            <i class="ph-light ph-book"></i>
                        </div>
                        <div class="guide-promo-card__label">Referência</div>
                        <h2 class="guide-promo-card__title">Guia de pontos</h2>
                        <p class="guide-promo-card__desc">
                            Os pontos básicos do crochê pra consultar sempre que precisar enquanto faz uma receita.
                        </p>
                        <span class="guide-promo-card__count">
                            <?= getStitchCount(true) ?> pontos cadastrados
                        </span>
                        <a href="<?= e(url('croche/guia-de-pontos', $lang)) ?>" class="guide-promo-card__btn">
                            Acessar o guia <i class="ph-light ph-arrow-right"></i>
                        </a>
                    </aside>
            <?php endif; ?>

            <a href="<?= e(url($category['slug'] . '/' . $featured['slug'], $lang)) ?>"
               class="category-featured">

                <?php if (!empty($featured['featured_image'])): ?>
                    <div class="category-featured__image"
                         style="background-image: url('<?= e(BASE_URL . ltrim($featured['featured_image'], '/')) ?>');">
                    </div>
                <?php else: ?>
                    <div class="category-featured__image category-featured__image--placeholder">
                        <?php if ($isIconSVG): ?>
                            <?= renderCategoryIcon($category) ?>
                        <?php else: ?>
                            <i class="<?= e($categoryIcon) ?>"></i>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="category-featured__content">
                    <div class="category-featured__tags">
                        <?php if ($isRecipe): ?>
                            <span class="pill-recipe"
                                  style="background:<?= e($diffVisual['bg']) ?>; color:<?= e($diffVisual['text']) ?>;">
                                <i class="<?= e($diffVisual['icon']) ?>"></i>
                                <?= e($lang === 'en' ? $diffVisual['label_en'] : $diffVisual['label_br']) ?>
                            </span>
                            <?php if ($isRecipe && $featured['is_free'] !== null): ?>
                                <?php if ((int) $featured['is_free'] === 1): ?>
                                    <span class="pill-free">
                                        <i class="ph-fill ph-sparkle"></i>
                                        <?= $lang === 'en' ? 'Free' : 'Gratuita' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="pill-paid">
                                        <i class="ph-light ph-diamond"></i>
                                        <?= $lang === 'en' ? 'Paid' : 'Paga' ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="pill-article">
                                <i class="ph-light ph-article"></i>
                                <?= $lang === 'en' ? 'Article' : 'Artigo' ?>
                            </span>
                        <?php endif; ?>

                        <span class="category-featured__highlight">
                            📌 <?= $lang === 'en' ? 'Featured' : 'Em destaque' ?>
                        </span>
                    </div>

                    <h2 class="category-featured__title"><?= e($featured['title']) ?></h2>
                    <?php if (!empty($featured['excerpt'])): ?>
                        <p class="category-featured__excerpt"><?= e($featured['excerpt']) ?></p>
                    <?php endif; ?>
                    <div class="category-featured__meta">
                        <span>
                            <i class="ph-light ph-calendar"></i>
                            <?= date('d/m/Y', strtotime($featured['publish_date'] ?? $featured['created_at'])) ?>
                        </span>
                        <?php if ($isRecipe && !empty($featured['estimated_time'])): ?>
                            <span>
                                <i class="ph-light ph-clock"></i>
                                <?= e($featured['estimated_time']) ?>
                            </span>
                        <?php elseif (!$isRecipe && !empty($featured['reading_time'])): ?>
                            <span>
                                <i class="ph-light ph-clock"></i>
                                <?= (int) $featured['reading_time'] ?> min
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <?php if ($isCrocheBr): ?>
                </div><!-- /.category-top-row -->
            <?php endif; ?>

            <!-- Grid de cards (do 2º em diante) -->
            <?php
            $remaining = array_slice($articles, 1);
            if (!empty($remaining)):
            ?>
                <div class="category-cards">
                    <?php foreach ($remaining as $article): ?>
                        <?php
                            $isRecipe = ($article['page_type'] ?? '') === 'recipe';
                            $diffVisual = $isRecipe ? getDifficultyVisual($article['difficulty'] ?? null) : null;
                        ?>
                        <a href="<?= e(url($category['slug'] . '/' . $article['slug'], $lang)) ?>"
                           class="category-card">

                            <?php if (!empty($article['featured_image'])): ?>
                                <div class="category-card__image"
                                     style="background-image: url('<?= e(BASE_URL . ltrim($article['featured_image'], '/')) ?>');">
                                </div>
                            <?php else: ?>
                                <div class="category-card__image category-card__image--placeholder">
                                    <?php if ($isIconSVG): ?>
                                        <?= renderCategoryIcon($category) ?>
                                    <?php else: ?>
                                        <i class="<?= e($categoryIcon) ?>"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="category-card__content">
                                <?php if ($isRecipe): ?>
                                    <span class="pill-recipe"
                                          style="background:<?= e($diffVisual['bg']) ?>; color:<?= e($diffVisual['text']) ?>;">
                                        <i class="<?= e($diffVisual['icon']) ?>"></i>
                                        <?= e($lang === 'en' ? $diffVisual['label_en'] : $diffVisual['label_br']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="pill-article">
                                        <i class="ph-light ph-article"></i>
                                        <?= $lang === 'en' ? 'Article' : 'Artigo' ?>
                                    </span>
                                <?php endif; ?>

                                <h3 class="category-card__title"><?= e($article['title']) ?></h3>
                                <?php if (!empty($article['excerpt'])): ?>
                                    <p class="category-card__excerpt">
                                        <?= e(mb_substr($article['excerpt'], 0, 120)) ?><?= mb_strlen($article['excerpt']) > 120 ? '…' : '' ?>
                                    </p>
                                <?php endif; ?>
                                <div class="category-card__meta">
                                    <span>
                                        <i class="ph-light ph-calendar"></i>
                                        <?= date('d/m/Y', strtotime($article['publish_date'] ?? $article['created_at'])) ?>
                                    </span>
                                    <?php if ($isRecipe && !empty($article['estimated_time'])): ?>
                                        <span>
                                            <i class="ph-light ph-clock"></i>
                                            <?= e($article['estimated_time']) ?>
                                        </span>
                                    <?php elseif (!$isRecipe && !empty($article['reading_time'])): ?>
                                        <span>
                                            <i class="ph-light ph-clock"></i>
                                            <?= (int) $article['reading_time'] ?> min
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/site-footer.php'; ?>
