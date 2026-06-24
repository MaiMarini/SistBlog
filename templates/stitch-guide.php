<?php
/**
 * TEMPLATE — Guia de pontos do crochê (Fase R-A2).
 *
 * Rota: /br/croche/guia-de-pontos
 * Carregado pelo page-router.php quando lang='br' && slug='croche/guia-de-pontos'.
 *
 * Renderiza lista pública de pontos cadastrados em `crochet_stitches`.
 * Anchors usam `tutorial_anchor` do banco (campo editável no admin) pra
 * bater com os links do Stitch Guide inline das receitas.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stitches.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/site-helpers.php';

$lang = $lang ?? 'br';
$stitches = getAllStitches(true);  // só ativos

// Variáveis pro site-header.php
$pageTitle       = 'Guia de pontos — ' . getSetting('site_name', 'Kallme');
$pageDescription = 'Lista de referência dos pontos básicos de crochê usados nas receitas do Kallme.';
$pageSlug        = 'croche/guia-de-pontos';
$activeNav       = 'croche';

include __DIR__ . '/../includes/site-header.php';
?>

<main class="stitch-guide-page">

    <!-- HEADER DA PÁGINA -->
    <header class="stitch-guide__header">
        <div class="stitch-guide__breadcrumb">
            <a href="<?= e(url('croche', $lang)) ?>">Crochê</a> &rsaquo; Guia de pontos
        </div>
        <h1 class="stitch-guide__title">Guia de pontos</h1>
        <p class="stitch-guide__subtitle">
            Os pontos básicos do crochê pra consultar quando precisar.
        </p>
        <span class="stitch-guide__count">
            <?= count($stitches) ?> pontos cadastrados
        </span>
    </header>

    <!-- LISTA EM GRID 2 COLUNAS -->
    <div class="stitch-guide__grid">
        <?php foreach ($stitches as $stitch): ?>
            <?php
                $name = $stitch['name_br'] ?? '';
                $abbr = $stitch['abbrev_br'] ?? '';
                $desc = $stitch['description_br'] ?? '';
                // photo_url = foto real (R-A3); image_url segue sendo o ícone SVG
                $photo = $stitch['photo_url'] ?? '';
                // Usa o tutorial_anchor (campo editável no admin) — bate com os
                // links do Stitch Guide inline das receitas. Fallback: slugify do nome.
                $anchor = !empty($stitch['tutorial_anchor'])
                    ? $stitch['tutorial_anchor']
                    : slugify($name);
                $hasPhoto = $photo !== '';
            ?>
            <article class="stitch-item" id="<?= e($anchor) ?>">
                <div class="stitch-item__header">
                    <h2 class="stitch-item__name"><?= e($name) ?></h2>
                    <?php if ($abbr !== ''): ?>
                        <span class="stitch-item__abbr"><?= e($abbr) ?></span>
                    <?php endif; ?>
                </div>
                <div class="stitch-item__body">
                    <?php if ($hasPhoto): ?>
                        <?php // Foto ANTES do texto pro float-right funcionar ?>
                        <div class="stitch-item__photo">
                            <img src="<?= e(BASE_URL . ltrim($photo, '/')) ?>"
                                 alt="<?= e($name) ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="stitch-item__desc">
                        <?php if ($desc !== ''): ?>
                            <?= nl2br(e($desc)) ?>
                        <?php else: ?>
                            <em style="color:var(--color-text-secondary)">Descrição em breve.</em>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- FOOTER -->
    <div class="stitch-guide__footer">
        <p>Esse guia está em construção. Cada vez que aparecer um ponto novo numa receita, ele é adicionado aqui.</p>
        <a href="<?= e(url('croche', $lang)) ?>" class="stitch-guide__back">&larr; Voltar pra Crochê</a>
    </div>

</main>

<?php include __DIR__ . '/../includes/site-footer.php'; ?>
