<?php
/**
 * HOMEPAGE — BR
 *
 * URL: kallme.online/br/
 *
 * Estrutura:
 *   - Hero (título + tagline + 2 CTAs)
 *   - Últimos 6 artigos (grid 3 col) ou empty state
 *   - Mini "Sobre" com link pra /br/sobre
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Kallme · Crochê, jardinagem e trabalhos manuais';
$pageDescription = 'Guias honestos de crochê, jardinagem e crafts caseiros para quem quer começar do zero ou aprofundar um hobby acolhedor.';
$pageSlug = '';            // root da homepage (sem slug) — hreflang precisa disso
$activeNav = 'home';

include __DIR__ . '/../../includes/site-header.php';

// Últimos conteúdos publicados em BR (artigos + receitas, default do helper).
// Estáticas (page_type='static') ficam fora porque getArticles() restringe
// a IN ('article','recipe').
$articles = getArticles([
    'language' => 'br',
    'limit' => 6,
]);
?>

<main>
    <!-- ====================== HERO BANNER (imagem + "Kallme" sobreposto) ====================== -->
    <!-- h1 da página = "Kallme" (semântico p/ SEO). A imagem de fundo vem de
         /assets/img/hero-banner.jpg; enquanto não existir, cai no rosa pétala. -->
    <section class="hero-banner">
        <div class="hero-banner__content">
            <h1 class="hero-banner__title">Kallme</h1>
            <p class="hero-banner__tagline">Trabalhos manuais com você</p>
        </div>
    </section>

    <!-- ====================== ÚLTIMOS ARTIGOS ====================== -->
    <section id="latest-articles" class="section-articles">
        <div class="container container-wide">
            <header class="section-header">
                <h2>Últimos artigos</h2>
                <p class="section-subtitle">
                    Conteúdo novo a cada semana — pesquisado, testado e curado.
                </p>
            </header>

            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <p>Os primeiros artigos chegam em breve. 🌷</p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article):
                        // Resolve a URL do artigo (com ou sem categoria)
                        $articleUrl = !empty($article['category'])
                            ? url($article['category'] . '/' . $article['slug'], $lang)
                            : url($article['slug'], $lang);

                        // Resolve o nome + cores da categoria para o badge
                        $categoryName = '';
                        $catBg = null;
                        $catText = null;
                        if (!empty($article['category'])) {
                            $cat = getCategory($article['category'], $lang);
                            $categoryName = $cat['name'] ?? $article['category'];
                            $catBg = $cat['color_bg'] ?? null;
                            $catText = $cat['color_text'] ?? null;
                        }

                        // Tempo de leitura: usa o valor salvo, senão calcula on-the-fly
                        $readingTime = !empty($article['reading_time'])
                            ? (int) $article['reading_time']
                            : calculateReadingTime($article['content'] ?? '');

                        $coverImage = getCoverImage($article);
                        $publishDate = $article['publish_date'] ?? $article['created_at'];
                        ?>
                        <article class="card-article">
                            <a href="<?= e($articleUrl) ?>" style="text-decoration:none;color:inherit;">
                                <img src="<?= e(BASE_URL . $coverImage) ?>" alt="<?= e($article['title']) ?>"
                                    class="card-article__image" loading="lazy">
                                <div class="card-article__content">
                                    <?php if ($categoryName !== ''): ?>
                                        <?php
                                            $badgeStyle = '';
                                            if ($catBg)   $badgeStyle .= 'background:' . e($catBg) . ';';
                                            if ($catText) $badgeStyle .= 'color:' . e($catText) . ';';
                                        ?>
                                        <span class="card-article__badge"<?= $badgeStyle ? ' style="' . $badgeStyle . '"' : '' ?>><?= e($categoryName) ?></span>
                                    <?php endif; ?>
                                    <h3 class="card-article__title"><?= e($article['title']) ?></h3>
                                    <?php $pageType = $article['page_type'] ?? 'article'; ?>
                                    <p class="card-article__meta">
                                        <?php if ($pageType === 'recipe'): ?>
                                            <span class="meta-type meta-type--recipe">Receita</span>
                                            <span class="separator">·</span>
                                        <?php else: ?>
                                            <span class="meta-type meta-type--article">Artigo</span>
                                            <span class="separator">·</span>
                                        <?php endif; ?>
                                        <i class="ph-light ph-clock icon-xs"></i>
                                        <?php if ($pageType === 'recipe' && !empty($article['estimated_time'])): ?>
                                            <?= e($article['estimated_time']) ?>
                                        <?php else: ?>
                                            <?= $readingTime ?> min de leitura
                                        <?php endif; ?>
                                        <span class="separator">·</span>
                                        <?= e(formatDate($publishDate, $lang)) ?>
                                    </p>
                                    <p class="card-article__excerpt">
                                        <?= e(getArticleExcerpt($article)) ?>
                                    </p>
                                    <span class="card-article__link">
                                        <?= $pageType === 'recipe' ? 'Fazer receita' : 'Ler artigo' ?>
                                        <i class="ph-light ph-arrow-right icon-xs"></i>
                                    </span>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====================== MINI SOBRE ====================== -->
    <section class="section-about-mini">
        <div class="container container-medium">
            <div class="about-mini__content">

                <h2>Sobre o Kallme</h2>

                <p>
                    Oi, sou Mai Marini. Aprendi a fazer ponto-cruz aos 11 anos com
                    minha mãe, e desde então nunca mais parei — passei pelo crochê,
                    pela costura, pelo macramê. Cresci num quintal cheio de planta
                    com meu pai. Sou neta de costureira, bisneta de fazedora de
                    macarrão artesanal, mãe de dois meninos. Trabalho como
                    desenvolvedora, mas é nas mãos que descanso a cabeça.
                </p>

                <p>
                    Criei o Kallme pra reunir num lugar só os guias e recomendações
                    honestas que eu mesma teria adorado encontrar quando comecei a
                    explorar trabalhos manuais. Sem promessa milagrosa. Sem
                    propaganda piscando. Só conteúdo pesquisado com cuidado e
                    contado com calma.
                </p>

                <a href="/br/sobre" class="btn btn-text">
                    Conhecer minha história
                    <i class="ph-light ph-arrow-right icon-xs"></i>
                </a>

            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>