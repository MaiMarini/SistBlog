<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/presell.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,400&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($page['tracking_code'])): ?>
        <?= $page['tracking_code'] ?>
    <?php endif; ?>
</head>
<body class="template-landing">

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="landing-container">
            <h1 class="landing-hero-title"><?= e($page['title']) ?></h1>
            <?php if (!empty($page['subtitle'])): ?>
                <p class="landing-hero-subtitle"><?= e($page['subtitle']) ?></p>
            <?php endif; ?>

            <?php if (!empty($page['affiliate_link'])): ?>
            <a href="<?= e($page['affiliate_link']) ?>" class="landing-hero-cta" style="background-color: <?= e($page['cta_color'] ?: '#e94560') ?>" target="_blank" rel="noopener">
                <?= e($page['cta_text'] ?: 'Saiba Mais') ?> →
            </a>
            <?php endif; ?>

            <?php if (!empty($page['main_image'])): ?>
            <div class="landing-hero-image">
                <img src="<?= BASE_URL . e($page['main_image']) ?>" alt="<?= e($page['title']) ?>">
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Conteúdo -->
    <section class="landing-section">
        <div class="landing-container">
            <div class="landing-content">
                <?= $page['content'] ?>
            </div>
        </div>
    </section>

    <!-- CTA do meio -->
    <?php if (!empty($page['affiliate_link'])): ?>
    <section class="landing-mid-cta">
        <div class="landing-container">
            <p class="landing-mid-cta-text">Não perca esta oportunidade!</p>
            <a href="<?= e($page['affiliate_link']) ?>" class="landing-hero-cta" style="background-color: <?= e($page['cta_color'] ?: '#e94560') ?>" target="_blank" rel="noopener">
                <?= e($page['cta_text'] ?: 'Saiba Mais') ?> →
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Comentários / Depoimentos -->
    <?php if (!empty($comments)): ?>
    <section class="landing-section landing-comments">
        <div class="landing-container">
            <h2 class="landing-section-title">O que as pessoas estão dizendo</h2>
            <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <div class="comment-avatar">
                    <?= mb_strtoupper(mb_substr($comment['name'], 0, 1)) ?>
                </div>
                <div class="comment-body">
                    <div class="comment-meta">
                        <strong><?= e($comment['name']) ?></strong>
                        <span><?= e($comment['date'] ?? '') ?></span>
                    </div>
                    <p><?= e($comment['text']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Final -->
    <?php if (!empty($page['affiliate_link'])): ?>
    <section class="landing-hero" style="padding: 60px 20px;">
        <div class="landing-container">
            <h2 style="font-size:28px;font-weight:700;margin-bottom:20px;">Comece Agora Mesmo</h2>
            <a href="<?= e($page['affiliate_link']) ?>" class="landing-hero-cta" style="background-color: <?= e($page['cta_color'] ?: '#e94560') ?>" target="_blank" rel="noopener">
                <?= e($page['cta_text'] ?: 'Saiba Mais') ?> →
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="landing-container">
            <p>Este é um conteúdo publicitário. Os resultados podem variar de pessoa para pessoa.</p>
            <p style="margin-top:8px;font-size:11px;color:#666;">Este site não faz parte do Facebook ou do Facebook Inc.</p>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>assets/js/presell.js"></script>
</body>
</html>
