<?php
/**
 * TEMPLATE — Receita de crochê (Fase Receita B3)
 *
 * Carregado pelo page-router.php (renderDbPage) com:
 *   $page            array  Linha da tabela `pages` com page_type='recipe'
 *   $lang            string Idioma (br|en)
 *   $category        ?array Categoria localizada (do getCategory) ou null
 *   $metaTitle       string Já resolvido pelo router
 *   $metaDescription string Já resolvido pelo router
 *
 * Layout:
 *   - Header centralizado (selos + título + excerpt + meta)
 *   - Foto + materiais lado a lado (alturas iguais)
 *   - Grid de pontos (clicáveis pro guia de pontos)
 *   - Divisor floral
 *   - Corpo da receita (TinyMCE)
 *   - Footer com CTA
 */

if (!isset($page) || !is_array($page)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/recipes.php';
require_once __DIR__ . '/../includes/recipe-blocks.php';
require_once __DIR__ . '/../includes/stitches.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/site-helpers.php';

$lang = $lang ?? getCurrentLanguage();

// Visual de dificuldade (sempre tem default 'beginner' se vier null)
$diffVisual = getDifficultyVisual($page['difficulty'] ?? null);
$diffLabel  = $lang === 'en' ? $diffVisual['label_en'] : $diffVisual['label_br'];

// Tipo de peça (label localizada)
$pieceLabel = getPieceTypeLabel($page['piece_type'] ?? null, $lang);

// Pontos vinculados (ordenados pelo display_order da tabela N:N)
$linkedStitches = getRecipeStitches((int) $page['id']);

// Blocos editoriais (R-C1.1+) agrupados por tipo pra renderizar
$blocksByType = [];
foreach (getRecipeBlocks((int) $page['id']) as $b) {
    $blocksByType[$b['block_type']][] = $b;
}

// Categoria (vem do router ou resolve aqui)
$categoryData = $category ?? getCategoryBySlug($page['category'] ?? '');
$categoryName = $categoryData['name'] ?? ($categoryData['name_br'] ?? ($page['category'] ?? ''));

// Variáveis pro site-header.php
$pageTitle       = ($metaTitle ?? $page['title']) . ' — ' . getSetting('site_name', 'Kallme');
$pageDescription = $metaDescription ?? '';
$pageSlug        = ($page['category'] ?? '') . '/' . ($page['slug'] ?? '');
$activeNav       = $page['category'] ?? '';
$extraHead       = !empty($page['tracking_code']) ? $page['tracking_code'] : '';

// URL base do guia de pontos (ainda não existe — clique vira 404 amigável até a próxima fase)
$stitchGuideUrl = '/' . $lang . '/croche/guia-de-pontos';

// Meta items pro header (só renderiza os preenchidos)
$metaItems = [];
if (!empty($page['estimated_time'])) {
    $metaItems[] = ['icon' => 'ph-light ph-clock', 'text' => $page['estimated_time']];
}
if (!empty($page['hook_size'])) {
    $metaItems[] = ['icon' => 'ph-light ph-needle', 'text' => $page['hook_size']];
}
if (!empty($page['yarn_recommended'])) {
    // Resumo curto pro header: primeira linha (ou primeiro item antes da vírgula)
    $firstLine = trim(strtok($page['yarn_recommended'], "\r\n"));
    $yarnShort = trim(explode(',', $firstLine)[0]);
    if ($yarnShort !== '') {
        $metaItems[] = ['icon' => 'ph-light ph-leaf', 'text' => $yarnShort];
    }
}
if (!empty($page['final_size'])) {
    $metaItems[] = ['icon' => 'ph-light ph-ruler', 'text' => $page['final_size']];
}
if (!empty($pieceLabel)) {
    $metaItems[] = ['icon' => 'ph-light ph-tag', 'text' => $pieceLabel];
}

// Lista de materiais (uma linha por material; aceita separadores \n ou \r\n)
$materialsList = [];
if (!empty($page['yarn_recommended'])) {
    $materialsList = array_values(array_filter(
        array_map('trim', preg_split('/[\r\n]+/', $page['yarn_recommended'])),
        fn ($s) => $s !== ''
    ));
}

include __DIR__ . '/../includes/site-header.php';
?>

<article class="recipe-page">

    <!-- ============================================ HEADER CENTRALIZADO -->
    <header class="recipe-header">
        <div class="recipe-header__pills">
            <span class="pill-difficulty"
                  style="background:<?= e($diffVisual['bg']) ?>; color:<?= e($diffVisual['text']) ?>;">
                <i class="<?= e($diffVisual['icon']) ?>"></i>
                <?= e($diffLabel) ?>
            </span>

            <?php if ($page['is_free'] !== null): // null = não-receita (caso defensivo) ?>
                <?php if ((int) $page['is_free'] === 1): ?>
                    <span class="pill-free">
                        <i class="ph-fill ph-sparkle"></i>
                        <?= $lang === 'en' ? 'Free Pattern' : 'Gratuita' ?>
                    </span>
                <?php else: ?>
                    <span class="pill-paid">
                        <i class="ph-light ph-diamond"></i>
                        <?= $lang === 'en' ? 'Paid' : 'Paga' ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <h1 class="recipe-header__title"><?= e($page['title']) ?></h1>

        <?php if (!empty($page['excerpt'])): ?>
            <p class="recipe-header__subtitle"><?= e($page['excerpt']) ?></p>
        <?php endif; ?>

        <?php if (!empty($metaItems)): ?>
            <div class="recipe-header__meta">
                <?php foreach ($metaItems as $item): ?>
                    <span class="recipe-meta-item">
                        <i class="<?= e($item['icon']) ?>"></i>
                        <?= e($item['text']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>

    <!-- ============================================ FOTO + MATERIAIS -->
    <div class="recipe-hero-row">
        <div class="recipe-photo">
            <?php if (!empty($page['featured_image'])): ?>
                <img src="<?= e(BASE_URL . ltrim($page['featured_image'], '/')) ?>"
                     alt="<?= e($page['title']) ?>"
                     class="recipe-photo__img">
            <?php else: ?>
                <div class="recipe-photo__placeholder">
                    <i class="ph-light ph-image-square"></i>
                </div>
            <?php endif; ?>
        </div>

        <aside class="materials-box">
            <h3>
                <i class="ph-light ph-shopping-bag"></i>
                <?= $lang === 'en' ? 'Materials' : 'Materiais' ?>
            </h3>
            <?php if (!empty($materialsList)): ?>
                <ul class="materials-list">
                    <?php foreach ($materialsList as $material): ?>
                        <li><?= e($material) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="materials-empty">
                    <em><?= $lang === 'en'
                        ? 'Materials will be listed here.'
                        : 'Lista de materiais em breve.' ?></em>
                </p>
            <?php endif; ?>
        </aside>
    </div>

    <!-- ============================================ PONTOS USADOS -->
    <?php if (!empty($linkedStitches)): ?>
        <section class="stitch-guide-section">
            <div class="stitch-guide-section__inner">
                <h3>
                    <i class="ph-light ph-book-open"></i>
                    <?= $lang === 'en' ? 'Stitches used' : 'Pontos usados' ?>
                </h3>

                <div class="stitches-grid">
                    <?php foreach ($linkedStitches as $stitch): ?>
                        <?php
                            $stitchName = $lang === 'en' && !empty($stitch['name_en'])
                                ? $stitch['name_en'] : $stitch['name_br'];

                            // Mostra abreviação local + (se BR) abreviação EN entre · ·
                            if ($lang === 'en' && !empty($stitch['abbrev_en'])) {
                                $abbrevs = $stitch['abbrev_en'];
                            } elseif ($lang === 'br' && !empty($stitch['abbrev_en'])) {
                                $abbrevs = $stitch['abbrev_br'] . ' · ' . $stitch['abbrev_en'];
                            } else {
                                $abbrevs = $stitch['abbrev_br'];
                            }

                            $anchor  = !empty($stitch['tutorial_anchor']) ? '#' . $stitch['tutorial_anchor'] : '';
                            $linkUrl = $stitchGuideUrl . $anchor;

                            $hover = $lang === 'en'
                                ? 'See tutorial for this stitch'
                                : 'Ver tutorial deste ponto';
                        ?>
                        <a class="stitch-card-mini"
                           href="<?= e($linkUrl) ?>"
                           title="<?= e($hover) ?>">
                            <div class="stitch-card-mini__icon">
                                <?= renderStitchIcon($stitch) ?>
                            </div>
                            <div class="stitch-card-mini__name"><?= e($stitchName) ?></div>
                            <div class="stitch-card-mini__abbrev"><?= e($abbrevs) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============================================ CORPO DA RECEITA -->
    <div class="recipe-body">
        <hr class="floral-divider">

        <h2 class="recipe-body__title">
            <i class="ph-light ph-info"></i>
            <?= $lang === 'en' ? 'Before you start' : 'Antes de começar' ?>
        </h2>

        <div class="recipe-body__content">
            <?= $page['content'] ?? '' /* HTML do TinyMCE — confiável (admin autenticado) */ ?>
        </div>
    </div>

    <!-- ============================================ BLOCOS EDITORIAIS -->

    <?php // 1) PASSOS DA RECEITA ?>
    <?php if (!empty($blocksByType['steps'])): ?>
        <section class="recipe-blocks recipe-blocks--steps">
            <h2 class="recipe-blocks__title">
                <i class="ph-light ph-list-checks"></i>
                <?= $lang === 'en' ? 'Step by step' : 'Passo a passo' ?>
            </h2>

            <div class="step-sections-grid">
                <?php foreach ($blocksByType['steps'] as $idx => $block): ?>
                    <?php
                        $section      = $block['data']['section'] ?? '';
                        $content      = $block['data']['content'] ?? '';
                        $photo        = $block['data']['photo'] ?? '';
                        $photoCaption = $block['data']['photo_caption'] ?? '';
                        $hasPhoto     = $photo !== '';
                        $lines        = parseStepsContent($content);
                        $sectionNum   = $idx + 1;
                    ?>
                    <div class="step-section <?= $hasPhoto ? 'step-section--with-photo' : 'step-section--no-photo' ?>">

                        <?php if ($section !== ''): ?>
                            <div class="step-section__title-wrap">
                                <span class="step-section__number"><?= $sectionNum ?></span>
                                <h3 class="step-section__title"><?= e($section) ?></h3>
                            </div>
                        <?php endif; ?>

                        <div class="step-section__body">
                            <div class="step-section__main">
                                <?php if (!empty($lines)): ?>
                                    <div class="step-section__table">
                                        <?php foreach ($lines as $line): ?>
                                            <?php if (isset($line['free'])): ?>
                                                <div class="step-line step-line--free">
                                                    <?= e($line['free']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="step-line step-line--structured">
                                                    <span class="step-line__row"><?= e($line['row']) ?></span>
                                                    <span class="step-line__instruction"><?= e($line['instruction']) ?></span>
                                                    <span class="step-line__total"><?= e($line['total']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasPhoto): ?>
                                <div class="step-section__photo">
                                    <div class="step-section__photo-wrap">
                                        <img src="<?= e(BASE_URL . ltrim($photo, '/')) ?>"
                                             alt="<?= e($section) ?>"
                                             loading="lazy">
                                    </div>
                                    <?php if ($photoCaption !== ''): ?>
                                        <span class="step-section__photo-caption"><?= e($photoCaption) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php // 2) DICAS / ALERTAS / IMPORTANTE ?>
    <?php if (!empty($blocksByType['tip'])): ?>
        <?php $tipTypes = getTipTypes(); ?>
        <section class="recipe-blocks recipe-blocks--tips">
            <?php foreach ($blocksByType['tip'] as $block): ?>
                <?php
                    $type = $block['data']['type'] ?? 'tip';
                    if (!isset($tipTypes[$type])) $type = 'tip';
                    $text = $block['data']['text'] ?? '';
                    $cfg  = $tipTypes[$type];
                    $label = $lang === 'en' ? $cfg['label_en'] : $cfg['label_br'];
                ?>
                <div class="tip-card tip-card--<?= e($type) ?>"
                     style="background:<?= e($cfg['bg']) ?>; color:<?= e($cfg['text']) ?>; border-left-color:<?= e($cfg['text']) ?>;">
                    <span class="tip-card__icon"><?= $cfg['icon_emoji'] ?></span>
                    <div class="tip-card__body">
                        <strong class="tip-card__label"><?= e($label) ?></strong>
                        <p class="tip-card__text"><?= nl2br(e($text)) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php // 3) COLOR GUIDE — lista de cores usadas ?>
    <?php if (!empty($blocksByType['color_guide'])): ?>
        <section class="recipe-blocks recipe-blocks--colors">
            <h2 class="recipe-blocks__title">
                <i class="ph-light ph-palette"></i>
                <?= $lang === 'en' ? 'Colors used' : 'Cores usadas' ?>
            </h2>

            <?php foreach ($blocksByType['color_guide'] as $block): ?>
                <?php $colors = $block['data']['colors'] ?? []; ?>
                <?php if (!empty($colors)): ?>
                    <div class="color-guide-grid">
                        <?php foreach ($colors as $color): ?>
                            <?php
                                $name  = $lang === 'en' && !empty($color['name_en'])
                                    ? $color['name_en']
                                    : ($color['name_br'] ?? '');
                                $usage = $lang === 'en' && !empty($color['usage_en'])
                                    ? $color['usage_en']
                                    : ($color['usage_br'] ?? '');
                                $hex   = $color['hex'] ?? '#F5C0C0';
                            ?>
                            <div class="color-row">
                                <span class="color-swatch" style="background:<?= e($hex) ?>;"></span>
                                <span class="color-info">
                                    <strong class="color-name"><?= e($name) ?></strong>
                                    <?php if ($usage !== ''): ?>
                                        <span class="color-usage"><?= e($usage) ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php // 4) STITCH GUIDE INLINE — mini-guia de pontos no meio da receita ?>
    <?php if (!empty($blocksByType['stitch_guide_inline'])): ?>
        <section class="recipe-blocks recipe-blocks--stitch-inline">
            <h2 class="recipe-blocks__title">
                <i class="ph-light ph-book"></i>
                <?= $lang === 'en' ? 'Stitch guide' : 'Guia de pontos' ?>
            </h2>

            <?php foreach ($blocksByType['stitch_guide_inline'] as $block): ?>
                <?php
                    $sid = (int) ($block['data']['stitch_id'] ?? 0);
                    $obs = $block['data']['observation'] ?? '';
                    if ($sid <= 0) continue;
                    $st = getStitchById($sid);
                    if (!$st) continue;

                    $name = ($lang === 'en' && !empty($st['name_en']))
                        ? $st['name_en']
                        : ($st['name_br'] ?? '');
                    $desc = ($lang === 'en' && !empty($st['description_en']))
                        ? $st['description_en']
                        : ($st['description_br'] ?? '');
                    $abbr = ($lang === 'en' && !empty($st['abbrev_en']))
                        ? $st['abbrev_en']
                        : ($st['abbrev_br'] ?? '');
                ?>
                <div class="stitch-card">
                    <span class="stitch-card__icon">🧶</span>
                    <div class="stitch-card__body">
                        <div class="stitch-card__name"><?= e($name) ?></div>
                        <?php if ($abbr !== ''): ?>
                            <span class="stitch-card__abbr"><?= e($abbr) ?></span>
                        <?php endif; ?>
                        <?php if ($desc !== '' || $obs !== ''): ?>
                            <p class="stitch-card__desc">
                                <?= e($desc) ?>
                                <?php if ($obs !== ''): ?>
                                    <em><?= e($obs) ?></em>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php // 5) STEP PHOTOS — galeria do processo ?>
    <?php if (!empty($blocksByType['step_photos'])): ?>
        <section class="recipe-blocks recipe-blocks--gallery">
            <h2 class="recipe-blocks__title">
                <i class="ph-light ph-images"></i>
                <?= $lang === 'en' ? 'Step photos' : 'Foto a foto' ?>
            </h2>

            <?php foreach ($blocksByType['step_photos'] as $block): ?>
                <?php $photos = $block['data']['photos'] ?? []; ?>
                <?php if (!empty($photos)): ?>
                    <div class="step-photos-grid">
                        <?php foreach ($photos as $i => $photo): ?>
                            <?php $url = $photo['url'] ?? ''; $cap = $photo['caption'] ?? ''; ?>
                            <?php if ($url === '') continue; ?>
                            <figure class="step-photo">
                                <img src="<?= e(BASE_URL . ltrim($url, '/')) ?>"
                                     alt="<?= e($cap) ?>"
                                     loading="lazy">
                                <figcaption>
                                    <span class="step-photo__num"><?= $i + 1 ?>.</span>
                                    <?= e($cap) ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php // 6) NOTAS / VARIAÇÕES — último bloco da receita ?>
    <?php if (!empty($blocksByType['notes'])): ?>
        <section class="recipe-blocks recipe-blocks--notes">
            <?php foreach ($blocksByType['notes'] as $block): ?>
                <?php
                    $notesText = trim((string) ($block['data']['notes'] ?? ''));
                    if ($notesText === '') continue;
                    $noteLines = array_values(array_filter(
                        array_map('trim', preg_split('/[\r\n]+/', $notesText)),
                        fn ($l) => $l !== ''
                    ));
                ?>
                <?php if (!empty($noteLines)): ?>
                    <div class="notes-card">
                        <h2 class="notes-card__title">
                            <i class="ph-light ph-note"></i>
                            <?= $lang === 'en' ? 'Notes and variations' : 'Notas e variações' ?>
                        </h2>
                        <ul class="notes-card__list">
                            <?php foreach ($noteLines as $line): ?>
                                <li><?= e($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- ============================================ FOOTER DA RECEITA -->
    <footer class="recipe-footer">
        <p class="recipe-footer__cta">
            <i class="ph-fill ph-heart"></i>
            <?= $lang === 'en'
                ? "Made this? I'd love to see — tag @kallme.online"
                : 'Fez esta receita? Marca @kallme.online — vou amar ver' ?>
        </p>
    </footer>

</article>

<?php include __DIR__ . '/../includes/site-footer.php'; ?>
