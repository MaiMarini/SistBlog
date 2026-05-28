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
<body class="template-blog-personal">

    <!-- Header simples de blog -->
    <header class="blog-header">
        <div class="blog-container">
            <h2><?= e($page['author_name']) ?></h2>
        </div>
    </header>

    <!-- Artigo -->
    <article class="blog-article">
        <div class="blog-container">

            <h1 class="blog-title"><?= e($page['title']) ?></h1>

            <!-- Meta do autor -->
            <div class="blog-meta">
                <?php if (!empty($page['author_avatar'])): ?>
                    <img src="<?= BASE_URL . e($page['author_avatar']) ?>" alt="<?= e($page['author_name']) ?>" class="blog-author-img">
                <?php else: ?>
                    <div class="blog-author-img-placeholder">
                        <?= mb_strtoupper(mb_substr($page['author_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="blog-author-text">
                    <strong><?= e($page['author_name']) ?></strong>
                    <?php if ($page['publish_date']): ?>
                        <?= date('d \d\e F \d\e Y', strtotime($page['publish_date'])) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Imagem principal -->
            <?php if (!empty($page['main_image'])): ?>
            <div class="blog-main-image">
                <img src="<?= BASE_URL . e($page['main_image']) ?>" alt="<?= e($page['title']) ?>">
            </div>
            <?php endif; ?>

            <!-- Subtítulo -->
            <?php if (!empty($page['subtitle'])): ?>
                <p style="font-size:19px;color:#555;font-style:italic;margin-bottom:25px;"><?= e($page['subtitle']) ?></p>
            <?php endif; ?>

            <!-- Conteúdo -->
            <div class="blog-content">
                <?= $page['content'] ?>
            </div>

            <!-- CTA -->
            <?php if (!empty($page['affiliate_link'])): ?>
            <div class="blog-cta-section">
                <p class="blog-cta-text">Clique no botão abaixo para saber mais:</p>
                <a href="<?= e($page['affiliate_link']) ?>" class="blog-cta-button" style="background-color: <?= e($page['cta_color'] ?: '#667eea') ?>" target="_blank" rel="noopener">
                    <?= e($page['cta_text'] ?: 'Saiba Mais') ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </article>

    <!-- Comentários -->
    <?php if (!empty($comments)): ?>
    <section class="blog-comments">
        <div class="blog-container">
            <h3 class="comments-title"><?= count($comments) ?> Comentários</h3>
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
                    <div class="comment-actions">
                        <span>👍 Curtir</span>
                        <span>Responder</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="blog-footer">
        <div class="blog-container">
            <p>Este é um conteúdo publicitário. Os resultados podem variar de pessoa para pessoa.</p>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>assets/js/presell.js"></script>
</body>
</html>
