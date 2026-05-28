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
<body class="template-advertorial">

    <!-- Header tipo portal de notícias -->
    <header class="adv-header">
        <div class="adv-container">
            <div class="adv-header-inner">
                <div class="adv-logo">
                    <span class="logo-icon">📰</span>
                    <span class="logo-text">Portal Saúde & Bem-Estar</span>
                </div>
                <div class="adv-header-date">
                    <?= date('d \d\e F \d\e Y') ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="adv-breadcrumb">
        <div class="adv-container">
            <span>Início</span> › <span>Saúde</span> › <span class="current"><?= e($page['title']) ?></span>
        </div>
    </div>

    <!-- Artigo Principal -->
    <article class="adv-article">
        <div class="adv-container">

            <!-- Headline -->
            <div class="adv-headline-section">
                <h1 class="adv-title"><?= e($page['title']) ?></h1>
                <?php if (!empty($page['subtitle'])): ?>
                    <p class="adv-subtitle"><?= e($page['subtitle']) ?></p>
                <?php endif; ?>

                <!-- Info do autor -->
                <div class="adv-author-info">
                    <?php if (!empty($page['author_avatar'])): ?>
                        <img src="<?= BASE_URL . e($page['author_avatar']) ?>" alt="<?= e($page['author_name']) ?>" class="adv-author-avatar">
                    <?php else: ?>
                        <div class="adv-author-avatar adv-avatar-placeholder">
                            <?= mb_strtoupper(mb_substr($page['author_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="adv-author-details">
                        <span class="adv-author-name">Por <?= e($page['author_name']) ?></span>
                        <span class="adv-publish-date">
                            <?php if ($page['publish_date']): ?>
                                Publicado em <?= date('d/m/Y', strtotime($page['publish_date'])) ?>
                            <?php endif; ?>
                            · Atualizado recentemente
                        </span>
                    </div>
                </div>

                <!-- Social share fake -->
                <div class="adv-social-bar">
                    <span class="social-btn social-fb">f Compartilhar</span>
                    <span class="social-btn social-tw">𝕏 Tweet</span>
                    <span class="social-btn social-wa">📱 WhatsApp</span>
                </div>
            </div>

            <!-- Imagem principal -->
            <?php if (!empty($page['main_image'])): ?>
            <div class="adv-main-image">
                <img src="<?= BASE_URL . e($page['main_image']) ?>" alt="<?= e($page['title']) ?>">
            </div>
            <?php endif; ?>

            <!-- Conteúdo do artigo -->
            <div class="adv-content">
                <?= $page['content'] ?>
            </div>

            <!-- Botão CTA -->
            <?php if (!empty($page['affiliate_link'])): ?>
            <div class="adv-cta-section">
                <a href="<?= e($page['affiliate_link']) ?>" class="adv-cta-button" style="background-color: <?= e($page['cta_color'] ?: '#e85d04') ?>" target="_blank" rel="noopener">
                    <?= e($page['cta_text'] ?: 'Saiba Mais') ?>
                    <span class="cta-arrow">→</span>
                </a>
                <p class="adv-cta-disclaimer">*Oferta por tempo limitado. Resultados podem variar.</p>
            </div>
            <?php endif; ?>

            <!-- Segundo CTA no meio do conteúdo -->
            <?php if (!empty($page['affiliate_link'])): ?>
            <div class="adv-cta-section adv-cta-secondary">
                <div class="adv-cta-box">
                    <p class="adv-cta-box-text">Não perca esta oportunidade exclusiva!</p>
                    <a href="<?= e($page['affiliate_link']) ?>" class="adv-cta-button" style="background-color: <?= e($page['cta_color'] ?: '#e85d04') ?>" target="_blank" rel="noopener">
                        <?= e($page['cta_text'] ?: 'Saiba Mais') ?>
                        <span class="cta-arrow">→</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </article>

    <!-- Seção de Comentários Fictícios -->
    <?php if (!empty($comments)): ?>
    <section class="adv-comments">
        <div class="adv-container">
            <h3 class="adv-comments-title"><?= count($comments) ?> Comentários</h3>
            <div class="adv-comments-list">
                <?php foreach ($comments as $comment): ?>
                <div class="adv-comment">
                    <div class="adv-comment-avatar">
                        <?= mb_strtoupper(mb_substr($comment['name'], 0, 1)) ?>
                    </div>
                    <div class="adv-comment-body">
                        <div class="adv-comment-header">
                            <strong class="adv-comment-name"><?= e($comment['name']) ?></strong>
                            <span class="adv-comment-date"><?= e($comment['date'] ?? '') ?></span>
                        </div>
                        <p class="adv-comment-text"><?= e($comment['text']) ?></p>
                        <div class="adv-comment-actions">
                            <span class="comment-like">👍 Curtir</span>
                            <span class="comment-reply">Responder</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="adv-footer">
        <div class="adv-container">
            <p>Este é um conteúdo publicitário. Os resultados podem variar de pessoa para pessoa.</p>
            <p class="adv-footer-disclaimer">Este site não faz parte do Facebook ou do Facebook Inc. Além disso, este site NÃO é endossado pelo Facebook de nenhuma forma.</p>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>assets/js/presell.js"></script>
</body>
</html>
