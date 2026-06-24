<?php
/**
 * TEMPLATE — Artigo editorial
 *
 * Carregado pelo page-router.php (renderDbPage) com estas variáveis disponíveis:
 *   $page           array  Linha completa da tabela pages
 *   $lang           string Idioma (br|en)
 *   $category       ?array Categoria localizada (do site-helpers) ou null
 *   $metaTitle      string Já resolvido (page.meta_title || page.title)
 *   $metaDescription string Já resolvido (page.meta_description || strip_tags(content))
 *
 * Variantes de hero (campo `template` no banco):
 *   - hero-classic  (default) — imagem horizontal grande no topo
 *   - hero-side    — imagem ao lado do título
 *   - hero-minimal — sem imagem, só tipografia
 */

// Defesa contra acesso direto a /templates/article.php (sem $page injetado).
if (!isset($page) || !is_array($page)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../includes/categories.php';

// ---------- Resolução de variáveis ----------
$heroVariant = $page['template'] ?? 'hero-classic';
if (!in_array($heroVariant, ['hero-classic', 'hero-side', 'hero-minimal'], true)) {
    $heroVariant = 'hero-classic';
}

// Categoria com fallback do cache (caso $category não venha do router)
$categoryData = $category ?? getCategoryBySlug($page['category'] ?? '');
$categoryName = $categoryData['name'] ?? ($categoryData['name_br'] ?? ($page['category'] ?? ''));
$categoryIcon = $categoryData['icon_value'] ?? 'ph-light ph-folder';
$categoryUrl  = url($page['category'] ?? '', $lang);

$displayDate = $page['publish_date'] ?? $page['created_at'];
$authorName  = !empty($page['author_name']) ? $page['author_name'] : 'Mai Marini';

$pageTitle       = $metaTitle ?? $page['title'];
$pageDescription = $metaDescription ?? ($page['excerpt'] ?? '');
$pageSlug        = ($page['category'] ?? '') . '/' . ($page['slug'] ?? '');
$activeNav       = $page['category'] ?? '';

// Artigos relacionados (mesma categoria, exclui o atual)
$relatedArticles = getRelatedArticles(
    (int) $page['id'],
    $page['category'] ?? '',
    $page['language'] ?? $lang,
    3
);

// Tracking_code específico desta página entra no <head> via $extraHead
$extraHead = !empty($page['tracking_code']) ? $page['tracking_code'] : '';

include __DIR__ . '/../includes/site-header.php';
?>

<main class="article-page article-page--<?= e($heroVariant) ?>">

    <?php // ============================================ HERO CLÁSSICO ?>
    <?php if ($heroVariant === 'hero-classic'): ?>
        <header class="article-hero article-hero--classic">
            <?php if (!empty($page['featured_image'])): ?>
                <div class="article-hero__image"
                     style="background-image: url('<?= e(BASE_URL . ltrim($page['featured_image'], '/')) ?>');">
                </div>
            <?php else: ?>
                <div class="article-hero__image article-hero__image--placeholder"></div>
            <?php endif; ?>

            <div class="article-hero__content">
                <a href="<?= e($categoryUrl) ?>" class="article-hero__category">
                    <i class="<?= e($categoryIcon) ?>"></i>
                    <?= e($categoryName) ?>
                </a>

                <h1 class="article-hero__title"><?= e($page['title']) ?></h1>

                <?php if (!empty($page['excerpt'])): ?>
                    <p class="article-hero__excerpt"><?= e($page['excerpt']) ?></p>
                <?php endif; ?>

                <div class="article-hero__meta">
                    <span class="article-hero__meta-item">
                        <i class="ph-light ph-user"></i>
                        <?= e($authorName) ?>
                    </span>
                    <span class="article-hero__meta-item">
                        <i class="ph-light ph-calendar"></i>
                        <?= date('d/m/Y', strtotime($displayDate)) ?>
                    </span>
                    <?php if (!empty($page['reading_time'])): ?>
                        <span class="article-hero__meta-item">
                            <i class="ph-light ph-clock"></i>
                            <?= (int) $page['reading_time'] ?> min de leitura
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

    <?php // ============================================ HERO LATERAL ?>
    <?php elseif ($heroVariant === 'hero-side'): ?>
        <header class="article-hero article-hero--side">
            <div class="article-hero__content">
                <a href="<?= e($categoryUrl) ?>" class="article-hero__category">
                    <i class="<?= e($categoryIcon) ?>"></i>
                    <?= e($categoryName) ?>
                </a>

                <h1 class="article-hero__title"><?= e($page['title']) ?></h1>

                <?php if (!empty($page['excerpt'])): ?>
                    <p class="article-hero__excerpt"><?= e($page['excerpt']) ?></p>
                <?php endif; ?>

                <div class="article-hero__meta">
                    <span class="article-hero__meta-item">
                        <i class="ph-light ph-user"></i>
                        <?= e($authorName) ?>
                    </span>
                    <span class="article-hero__meta-item">
                        <i class="ph-light ph-calendar"></i>
                        <?= date('d/m/Y', strtotime($displayDate)) ?>
                    </span>
                    <?php if (!empty($page['reading_time'])): ?>
                        <span class="article-hero__meta-item">
                            <i class="ph-light ph-clock"></i>
                            <?= (int) $page['reading_time'] ?> min
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($page['featured_image'])): ?>
                <div class="article-hero__image"
                     style="background-image: url('<?= e(BASE_URL . ltrim($page['featured_image'], '/')) ?>');">
                </div>
            <?php else: ?>
                <div class="article-hero__image article-hero__image--placeholder"></div>
            <?php endif; ?>
        </header>

    <?php // ============================================ HERO MINIMAL ?>
    <?php else: ?>
        <header class="article-hero article-hero--minimal">
            <a href="<?= e($categoryUrl) ?>" class="article-hero__category">
                <i class="<?= e($categoryIcon) ?>"></i>
                <?= e($categoryName) ?>
            </a>

            <h1 class="article-hero__title"><?= e($page['title']) ?></h1>

            <?php if (!empty($page['excerpt'])): ?>
                <p class="article-hero__excerpt"><?= e($page['excerpt']) ?></p>
            <?php endif; ?>

            <hr class="article-hero__divider">

            <div class="article-hero__meta">
                <span class="article-hero__meta-item">
                    <i class="ph-light ph-user"></i>
                    <?= e($authorName) ?>
                </span>
                <span class="article-hero__meta-item">
                    <i class="ph-light ph-calendar"></i>
                    <?= date('d/m/Y', strtotime($displayDate)) ?>
                </span>
                <?php if (!empty($page['reading_time'])): ?>
                    <span class="article-hero__meta-item">
                        <i class="ph-light ph-clock"></i>
                        <?= (int) $page['reading_time'] ?> min de leitura
                    </span>
                <?php endif; ?>
            </div>
        </header>
    <?php endif; ?>

    <?php // ============================================ CORPO ?>
    <article class="article-body">
        <?= $page['content'] ?? '' ?>
    </article>

    <?php // ============================================ AUTORA ?>
    <section class="article-author">
        <div class="article-author__avatar">
            <img src="<?= BASE_URL ?>assets/img/avatar.jpg"
                 alt="<?= e($authorName) ?>"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2240%22 r=%2218%22 fill=%22%23DB8084%22/><circle cx=%2250%22 cy=%22100%22 r=%2240%22 fill=%22%23DB8084%22/></svg>'">
        </div>
        <div class="article-author__bio">
            <h3 class="article-author__name"><?= e($authorName) ?></h3>
            <p class="article-author__text">
                Desenvolvedora full stack, neta de costureira, mãe de dois meninos. Cresci com agulha na mão e planta no quintal. No Kallme divido o que sei e o que descubro sobre trabalhos manuais, jardim e leitura.
            </p>
            <a href="<?= e(url($lang === 'en' ? 'about' : 'sobre', $lang)) ?>" class="article-author__cta">
                Saiba mais <i class="ph ph-arrow-right"></i>
            </a>
        </div>
    </section>

    <?php // ============================================ RELACIONADOS ?>
    <?php if (!empty($relatedArticles)): ?>
        <section class="article-related">
            <div class="article-related__inner">
                <h2 class="article-related__title">Continue lendo</h2>
                <p class="article-related__subtitle">Mais sobre <?= e($categoryName) ?></p>

                <div class="article-related__grid">
                    <?php foreach ($relatedArticles as $related): ?>
                        <a href="<?= e(url(($page['category'] ?? '') . '/' . $related['slug'], $lang)) ?>"
                           class="related-card">
                            <?php if (!empty($related['featured_image'])): ?>
                                <div class="related-card__image"
                                     style="background-image: url('<?= e(BASE_URL . ltrim($related['featured_image'], '/')) ?>');"></div>
                            <?php else: ?>
                                <div class="related-card__image related-card__image--placeholder">
                                    <i class="<?= e($categoryIcon) ?>"></i>
                                </div>
                            <?php endif; ?>

                            <div class="related-card__content">
                                <h3 class="related-card__title"><?= e($related['title']) ?></h3>
                                <?php if (!empty($related['excerpt'])): ?>
                                    <p class="related-card__excerpt">
                                        <?= e(mb_substr($related['excerpt'], 0, 100)) ?><?= mb_strlen($related['excerpt']) > 100 ? '…' : '' ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($related['reading_time'])): ?>
                                    <span class="related-card__meta">
                                        <i class="ph-light ph-clock"></i>
                                        <?= (int) $related['reading_time'] ?> min
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/site-footer.php'; ?>
