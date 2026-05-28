<?php
// Helpers do template structured
$content2Images = !empty($page['content2_images_json']) ? json_decode($page['content2_images_json'], true) : [];
$content2Images = is_array($content2Images) ? array_filter($content2Images) : [];

$footerButtons = !empty($page['footer_buttons_json']) ? json_decode($page['footer_buttons_json'], true) : [];
$footerButtons = is_array($footerButtons) ? $footerButtons : [];

// Background do cabeçalho
$headerBgType = $page['header_bg_type'] ?? 'solid';
$headerBgDir = $page['header_bg_direction'] ?? 'to bottom';
$c1 = $page['header_bg_color1'] ?? '#ffffff';
$c2 = $page['header_bg_color2'] ?? '';
$c3 = $page['header_bg_color3'] ?? '';

if ($headerBgType === 'linear' && !empty($c2)) {
    $colors = array_filter([$c1, $c2, $c3]);
    $headerBg = "linear-gradient($headerBgDir, " . implode(', ', $colors) . ")";
} elseif ($headerBgType === 'radial' && !empty($c2)) {
    $colors = array_filter([$c1, $c2, $c3]);
    $headerBg = "radial-gradient(circle, " . implode(', ', $colors) . ")";
} else {
    $headerBg = $c1;
}

// Background do Conteúdo 1
$content1Style = '';
if (!empty($page['content1_bg_image'])) {
    $content1Style = "background-image: url('" . BASE_URL . e($page['content1_bg_image']) . "'); background-size: cover; background-position: center;";
} elseif (!empty($page['content1_bg_color'])) {
    $content1Style = "background-color: " . e($page['content1_bg_color']) . ";";
}

// Background do Conteúdo 2
$content2Style = '';
if (!empty($page['content2_bg_image'])) {
    $content2Style = "background-image: url('" . BASE_URL . e($page['content2_bg_image']) . "'); background-size: cover; background-position: center;";
} elseif (!empty($page['content2_bg_color'])) {
    $content2Style = "background-color: " . e($page['content2_bg_color']) . ";";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/presell.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    <?php if (!empty($page['tracking_code'])): ?>
        <?= $page['tracking_code'] ?>
    <?php endif; ?>
</head>
<body class="template-structured">

    <!-- ============= CABEÇALHO ============= -->
    <section class="struct-header" style="background: <?= e($headerBg) ?>;">
        <div class="struct-container">
            <?php if (!empty($page['header_text'])): ?>
                <div class="struct-header-text">
                    <?= $page['header_text'] ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($page['header_image'])): ?>
                <div class="struct-header-image">
                    <img src="<?= BASE_URL . e($page['header_image']) ?>" alt="">
                </div>
            <?php endif; ?>
            <?php if (!empty($page['header_text_below'])): ?>
                <div class="struct-header-text struct-header-text-below">
                    <?= $page['header_text_below'] ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============= CONTEÚDO 1 ============= -->
    <?php if (!empty($page['content1_text']) || !empty($page['content1_image'])): ?>
    <section class="struct-content-1" style="<?= $content1Style ?>">
        <?php if (!empty($page['content1_bg_image'])): ?>
            <div class="struct-content-1-overlay"></div>
        <?php endif; ?>
        <div class="struct-container struct-content-1-grid">
            <div class="struct-content-1-text">
                <?= $page['content1_text'] ?? '' ?>
            </div>
            <?php if (!empty($page['content1_image'])): ?>
                <div class="struct-content-1-image">
                    <img src="<?= BASE_URL . e($page['content1_image']) ?>" alt="">
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= CONTEÚDO 2 ============= -->
    <?php if (!empty($content2Images) || !empty($page['content2_text'])): ?>
    <section class="struct-content-2" style="<?= $content2Style ?>">
        <?php if (!empty($page['content2_bg_image'])): ?>
            <div class="struct-content-2-overlay"></div>
        <?php endif; ?>
        <div class="struct-container" style="position:relative;z-index:1;">
            <?php if (!empty($content2Images)): ?>
                <div class="struct-content-2-images count-<?= count($content2Images) ?>">
                    <?php foreach ($content2Images as $img): ?>
                        <div class="struct-c2-img">
                            <img src="<?= BASE_URL . e($img) ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($page['content2_text'])): ?>
                <div class="struct-content-2-text">
                    <?= $page['content2_text'] ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($page['affiliate_link'])): ?>
                <div class="struct-content-2-cta">
                    <a href="<?= e($page['affiliate_link']) ?>" target="_blank" rel="noopener"
                       class="struct-cta-button"
                       style="background-color: <?= e($page['content2_cta_color'] ?? '#e85d04') ?>; color: <?= e($page['content2_cta_text_color'] ?? '#ffffff') ?> !important;">
                        <?= e($page['content2_cta_text'] ?? 'Saiba Mais') ?>
                        <span class="cta-arrow">→</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= RODAPÉ ============= -->
    <footer class="struct-footer" style="background-color: <?= e($page['footer_bg_color'] ?? '#1a1a2e') ?>;">
        <div class="struct-container">
            <?php if (!empty($page['footer_alerts'])): ?>
                <div class="struct-footer-alerts">
                    <?= $page['footer_alerts'] ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($footerButtons)): ?>
                <?php
                $btnColor = $page['footer_btn_color'] ?? '#ffffff';
                $btnSize = $page['footer_btn_size'] ?? '13px';
                ?>
                <div class="struct-footer-buttons">
                    <?php foreach ($footerButtons as $btn): ?>
                        <?php if (!empty($btn['text'])): ?>
                            <a href="<?= e($btn['link'] ?? '#') ?>" class="struct-footer-btn" target="_blank" rel="noopener"
                               style="color: <?= e($btnColor) ?> !important; font-size: <?= e($btnSize) ?>;">
                                <?= e($btn['text']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Botão flutuante CTA (segue rolagem) -->
    <?php if (!empty($page['affiliate_link'])): ?>
    <a href="<?= e($page['affiliate_link']) ?>" target="_blank" rel="noopener"
       class="struct-floating-cta"
       id="struct-floating-cta"
       style="background-color: <?= e($page['content2_cta_color'] ?? '#e85d04') ?>; color: <?= e($page['content2_cta_text_color'] ?? '#ffffff') ?> !important;">
        <?= e($page['content2_cta_text'] ?? 'Saiba Mais') ?>
        <span class="cta-arrow">→</span>
    </a>
    <?php endif; ?>

    <script src="<?= BASE_URL ?>assets/js/presell.js"></script>
</body>
</html>
