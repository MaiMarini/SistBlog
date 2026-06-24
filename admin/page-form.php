<?php
/**
 * ADMIN — FORMULÁRIO DE PÁGINA
 *
 * Editor TinyMCE 6 + toolbar de blocos editoriais modulares
 * (citação, dica, atenção, galeria, produto afiliado, etc.).
 * Os blocos são injetados como HTML estruturado e renderizados
 * pelo site público via assets/css/site.css.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/site-helpers.php';
require_once __DIR__ . '/../includes/stitches.php';
require_once __DIR__ . '/../includes/recipes.php';
require_once __DIR__ . '/../includes/recipe-blocks.php';
requireLogin();

$activePage = 'new-page';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$page = null;
$errors = [];
$success = false;

if ($id) {
    $page = getPageById($id);
    if (!$page) {
        header('Location: ' . BASE_URL . 'admin/pages.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $data = buildPageDataFromPost($_POST, $_FILES, $errors);

        // Verifica slug único por (slug, language)
        if (empty($errors)) {
            $pdo = getDB();
            $slugCheck = $pdo->prepare("
                SELECT id FROM pages
                WHERE slug = ? AND language = ? AND id != ?
            ");
            $slugCheck->execute([$data['slug'], $data['language'], $id ?? 0]);
            if ($slugCheck->fetch()) {
                $errors[] = 'Este slug já está em uso neste idioma. Escolha outro.';
            }
        }

        if (empty($errors)) {
            $savedId = savePage($data, $id);
            if ($savedId) {
                // Receita: persistir vínculo de pontos (substitui todos os atuais)
                if (($data['page_type'] ?? '') === 'recipe') {
                    $rawIds = $_POST['stitch_ids'] ?? [];
                    if (is_array($rawIds)) {
                        $stitchIds = array_values(array_filter(
                            array_map('intval', $rawIds),
                            fn($v) => $v > 0
                        ));
                        saveRecipeStitches((int) $savedId, $stitchIds);
                    }

                    // Blocos editoriais (steps + tips). Monta o array no formato
                    // que saveRecipeBlocks() espera e deixa ela cuidar de delete+insert.
                    $blocks = [];

                    $stepsSections = $_POST['steps_section'] ?? [];
                    $stepsContents = $_POST['steps_content'] ?? [];
                    $stepsPhotos = $_POST['steps_photo'] ?? [];
                    $stepsPhotoCaptions = $_POST['steps_photo_caption'] ?? [];
                    if (is_array($stepsSections)) {
                        foreach ($stepsSections as $i => $section) {
                            $section = trim((string) $section);
                            $content = trim((string) ($stepsContents[$i] ?? ''));
                            $photo = trim((string) ($stepsPhotos[$i] ?? ''));
                            $photoCaption = trim((string) ($stepsPhotoCaptions[$i] ?? ''));
                            if ($section === '' && $content === '' && $photo === '')
                                continue;

                            $stepData = ['section' => $section, 'content' => $content];
                            if ($photo !== '') {
                                $stepData['photo'] = $photo;
                                if ($photoCaption !== '') {
                                    $stepData['photo_caption'] = $photoCaption;
                                }
                            }
                            $blocks[] = ['type' => 'steps', 'data' => $stepData];
                        }
                    }

                    $tipTypes = $_POST['tip_type'] ?? [];
                    $tipTexts = $_POST['tip_text'] ?? [];
                    if (is_array($tipTypes)) {
                        $validTipTypes = ['tip', 'alert', 'important'];
                        foreach ($tipTypes as $i => $type) {
                            $text = trim((string) ($tipTexts[$i] ?? ''));
                            if ($text === '')
                                continue;
                            if (!in_array($type, $validTipTypes, true))
                                $type = 'tip';
                            $blocks[] = [
                                'type' => 'tip',
                                'data' => ['type' => $type, 'text' => $text],
                            ];
                        }
                    }

                    // Step Photos (galeria final) — agrupa todas as fotos num único bloco
                    $photoUrls = $_POST['step_photos_url'] ?? [];
                    $photoCaptions = $_POST['step_photos_caption'] ?? [];
                    $galleryPhotos = [];
                    if (is_array($photoUrls)) {
                        foreach ($photoUrls as $i => $u) {
                            $u = trim((string) $u);
                            if ($u === '')
                                continue;
                            $galleryPhotos[] = [
                                'url' => $u,
                                'caption' => trim((string) ($photoCaptions[$i] ?? '')),
                            ];
                        }
                    }
                    if (!empty($galleryPhotos)) {
                        $blocks[] = ['type' => 'step_photos', 'data' => ['photos' => $galleryPhotos]];
                    }

                    // Color Guide — agrupa todas as cores num único bloco
                    $colorHex = $_POST['color_hex'] ?? [];
                    $colorNames = $_POST['color_name_br'] ?? [];
                    $colorUsages = $_POST['color_usage_br'] ?? [];
                    $colors = [];
                    if (is_array($colorHex)) {
                        foreach ($colorHex as $i => $hex) {
                            $hex = trim((string) $hex);
                            $name = trim((string) ($colorNames[$i] ?? ''));
                            if ($name === '')
                                continue; // cor sem nome = ignorada
                            // Sanitiza hex (#rrggbb ou #rgb); fallback rosa
                            if (!preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex)) {
                                $hex = '#F5C0C0';
                            }
                            $colors[] = [
                                'hex' => $hex,
                                'name_br' => $name,
                                'usage_br' => trim((string) ($colorUsages[$i] ?? '')),
                            ];
                        }
                    }
                    if (!empty($colors)) {
                        $blocks[] = ['type' => 'color_guide', 'data' => ['colors' => $colors]];
                    }

                    // Stitch Guide inline (lista de pontos selecionados, na ordem do POST)
                    $stitchInlineIds = $_POST['stitch_inline_id'] ?? [];
                    $stitchInlineObs = $_POST['stitch_inline_observation'] ?? [];
                    if (is_array($stitchInlineIds)) {
                        foreach ($stitchInlineIds as $i => $sid) {
                            $sid = (int) $sid;
                            if ($sid <= 0)
                                continue;
                            $blocks[] = [
                                'type' => 'stitch_guide_inline',
                                'data' => [
                                    'stitch_id' => $sid,
                                    'observation' => trim((string) ($stitchInlineObs[$i] ?? '')),
                                ],
                            ];
                        }
                    }

                    // Notas / Variações (textarea livre, um único bloco)
                    $notesContent = trim((string) ($_POST['notes_content'] ?? ''));
                    if ($notesContent !== '') {
                        $blocks[] = [
                            'type' => 'notes',
                            'data' => ['notes' => $notesContent],
                        ];
                    }

                    saveRecipeBlocks((int) $savedId, $blocks);
                }
                if (function_exists('clearCategoriesCache'))
                    clearCategoriesCache();
                header('Location: ' . BASE_URL . 'admin/page-form.php?id=' . $savedId . '&saved=1');
                exit;
            }
            $errors[] = 'Erro ao salvar a página.';
        }

        // Preservar dados em caso de erro
        $page = array_merge($page ?? [], $data);
        $page['id'] = $id;
    }
}

if (!empty($_GET['saved']))
    $success = true;

$categories = getAllCategories();

// === Receita: pontos vinculados (se editando uma receita) + lista completa ===
$linkedStitches = [];
if ($id && ($page['page_type'] ?? '') === 'recipe') {
    $linkedStitches = getRecipeStitches($id);
}
$allStitches = getAllStitches(true);
$linkedStitchIds = array_column($linkedStitches, 'id');

// === Receita: blocos editoriais (steps + tips + step_photos + color_guide + stitch_guide_inline + notes) ===
$stepsBlocks = [];
$tipsBlocks = [];
$stepPhotosBlocks = [];
$colorGuideBlocks = [];
$stitchInlineBlocks = [];
$notesBlocks = [];
if ($id && ($page['page_type'] ?? '') === 'recipe') {
    foreach (getRecipeBlocks($id) as $b) {
        switch ($b['block_type']) {
            case 'steps':
                $stepsBlocks[] = $b;
                break;
            case 'tip':
                $tipsBlocks[] = $b;
                break;
            case 'step_photos':
                $stepPhotosBlocks[] = $b;
                break;
            case 'color_guide':
                $colorGuideBlocks[] = $b;
                break;
            case 'stitch_guide_inline':
                $stitchInlineBlocks[] = $b;
                break;
            case 'notes':
                $notesBlocks[] = $b;
                break;
        }
    }
}

// Achatar step_photos e color_guide (podem vir distribuídos em mais de um bloco)
$allStepPhotos = [];
foreach ($stepPhotosBlocks as $b) {
    if (!empty($b['data']['photos']) && is_array($b['data']['photos'])) {
        $allStepPhotos = array_merge($allStepPhotos, $b['data']['photos']);
    }
}

$allColors = [];
foreach ($colorGuideBlocks as $b) {
    if (!empty($b['data']['colors']) && is_array($b['data']['colors'])) {
        $allColors = array_merge($allColors, $b['data']['colors']);
    }
}

$pageTitle = $id ? 'Editar Página' : 'Nova Página';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
    <script src="https://cdn.tiny.cloud/1/<?= e(TINYMCE_KEY) ?>/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1><?= e($pageTitle) ?></h1>
                <a href="<?= BASE_URL ?>admin/pages.php" class="btn btn-secondary">← Voltar</a>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success">Página salva com sucesso.</div>
            <?php endif; ?>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data" class="page-form" id="page-form">
                <?= csrfField() ?>
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-main">

                        <!-- CONTEÚDO PRINCIPAL -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Conteúdo</h2>
                            </div>

                            <div class="form-group">
                                <label for="title">Título *</label>
                                <input type="text" id="title" name="title" required
                                    value="<?= e($page['title'] ?? '') ?>" placeholder="Título do artigo ou página">
                            </div>

                            <div class="form-group">
                                <label for="slug">Slug (URL) *</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">/<?= e($page['language'] ?? 'br') ?>/</span>
                                    <input type="text" id="slug" name="slug" value="<?= e($page['slug'] ?? '') ?>"
                                        placeholder="meu-artigo">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="excerpt">Resumo (excerpt)</label>
                                <textarea id="excerpt" name="excerpt" rows="3"
                                    placeholder="Resumo curto que aparece em cards e listagens"><?= e($page['excerpt'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="content">Conteúdo</label>

                                <!-- Toolbar de blocos editoriais -->
                                <div class="editorial-blocks-toolbar" id="editorial-blocks-toolbar">
                                    <span class="toolbar-label">Inserir bloco:</span>
                                    <button type="button" class="editorial-btn" data-block="image"
                                        title="Imagem com legenda">🖼 Imagem</button>
                                    <button type="button" class="editorial-btn" data-block="quote"
                                        title="Citação destacada">❝ Citação</button>
                                    <button type="button" class="editorial-btn" data-block="tip"
                                        title="Caixa de dica (rosa)">💡 Dica</button>
                                    <button type="button" class="editorial-btn" data-block="warning"
                                        title="Caixa de atenção (amarela)">⚠ Atenção</button>
                                    <button type="button" class="editorial-btn" data-block="list"
                                        title="Lista com bullets">≡ Lista</button>
                                    <button type="button" class="editorial-btn" data-block="gallery"
                                        title="Galeria de 3 imagens">🖼🖼 Galeria</button>
                                    <button type="button" class="editorial-btn" data-block="product"
                                        title="Produto afiliado">🛒 Produto</button>
                                    <button type="button" class="editorial-btn" data-block="highlight"
                                        title="Frase em destaque grande">★ Destaque</button>
                                    <button type="button" class="editorial-btn" data-block="divider"
                                        title="Divisor decorativo">— Divisor</button>
                                    <button type="button" class="editorial-btn" data-block="table"
                                        title="Tabela editorial">⊞ Tabela</button>
                                </div>

                                <textarea id="content" name="content"><?= e($page['content'] ?? '') ?></textarea>
                                <small id="reading-time-display"
                                    style="color:#888;display:block;margin-top:6px;"></small>
                            </div>
                        </div>

                        <!-- PONTOS USADOS NA RECEITA (só pra page_type='recipe') -->
                        <div class="card recipe-only" id="recipe-stitches-block" style="display:none;">
                            <div class="card-header">
                                <h2>🧶 Pontos usados</h2>
                            </div>
                            <div class="form-group">
                                <small style="color:#888;display:block;margin-bottom:10px;">
                                    Marque os pontos que aparecem nesta receita. Use as setas pra reordenar — eles
                                    aparecem nessa ordem no guia da receita.
                                </small>
                                <div id="stitches-list" class="stitches-list">
                                    <?php // Primeiro os pontos JÁ VINCULADOS, na ordem salva ?>
                                    <?php foreach ($linkedStitches as $stitch): ?>
                                        <div class="stitch-item is-checked" data-stitch-id="<?= (int) $stitch['id'] ?>">
                                            <input type="checkbox" name="stitch_ids[]" value="<?= (int) $stitch['id'] ?>"
                                                id="stitch-<?= (int) $stitch['id'] ?>" checked>
                                            <label for="stitch-<?= (int) $stitch['id'] ?>" class="stitch-item__label">
                                                <span class="stitch-item__icon"><?= renderStitchIcon($stitch) ?></span>
                                                <span class="stitch-item__info">
                                                    <strong><?= e($stitch['name_br']) ?></strong>
                                                    <code><?= e($stitch['abbrev_br']) ?></code>
                                                </span>
                                            </label>
                                            <div class="stitch-item__controls">
                                                <button type="button" class="btn-move-up" title="Subir">↑</button>
                                                <button type="button" class="btn-move-down" title="Descer">↓</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php // Depois os pontos NÃO vinculados, desmarcados ?>
                                    <?php foreach ($allStitches as $stitch): ?>
                                        <?php if (in_array($stitch['id'], $linkedStitchIds, true))
                                            continue; ?>
                                        <div class="stitch-item" data-stitch-id="<?= (int) $stitch['id'] ?>">
                                            <input type="checkbox" name="stitch_ids[]" value="<?= (int) $stitch['id'] ?>"
                                                id="stitch-<?= (int) $stitch['id'] ?>">
                                            <label for="stitch-<?= (int) $stitch['id'] ?>" class="stitch-item__label">
                                                <span class="stitch-item__icon"><?= renderStitchIcon($stitch) ?></span>
                                                <span class="stitch-item__info">
                                                    <strong><?= e($stitch['name_br']) ?></strong>
                                                    <code><?= e($stitch['abbrev_br']) ?></code>
                                                </span>
                                            </label>
                                            <div class="stitch-item__controls">
                                                <button type="button" class="btn-move-up" title="Subir">↑</button>
                                                <button type="button" class="btn-move-down" title="Descer">↓</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small style="color:#888;display:block;margin-top:10px;">
                                    💡 Não tem o ponto que precisa? <a href="<?= BASE_URL ?>admin/stitch-form.php"
                                        target="_blank" style="color:#e94560;">Cadastra aqui</a> e recarrega esta
                                    página.
                                </small>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: PASSOS DA RECEITA (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-steps-block" style="display:none;">
                            <div class="card-header">
                                <h2>📋 Passos da Receita</h2>
                            </div>
                            <div class="form-group">
                                <small style="display:block;color:#888;margin-bottom:6px;">
                                    Organize a receita em seções (Corpo, Folhas, Montagem…). Use o formato
                                    <code>R1: 6 pb no anel (6)</code> pra virar tabela; texto livre vira linha solta.
                                </small>

                                <div id="steps-sections-list" class="blocks-list">
                                    <?php foreach ($stepsBlocks as $idx => $b): ?>
                                        <?php
                                        $section = $b['data']['section'] ?? '';
                                        $content = $b['data']['content'] ?? '';
                                        $lineCount = $content === '' ? 0 : count(array_filter(preg_split('/[\r\n]+/', $content), fn($l) => trim($l) !== ''));
                                        ?>
                                        <div class="collapse-card open" data-block-type="steps">
                                            <div class="collapse-card__header">
                                                <span class="collapse-card__title">
                                                    <span class="chevron">▼</span>
                                                    <strong>Seção <?= $idx + 1 ?>:</strong>
                                                    <span
                                                        class="section-name-preview"><?= e($section !== '' ? $section : '(sem nome)') ?></span>
                                                    <span class="line-count">— <?= $lineCount ?> linha(s)</span>
                                                </span>
                                                <span class="collapse-card__controls">
                                                    <button type="button" class="btn-move-up" title="Subir">↑</button>
                                                    <button type="button" class="btn-move-down" title="Descer">↓</button>
                                                    <button type="button" class="btn-remove"
                                                        title="Remover seção">×</button>
                                                </span>
                                            </div>
                                            <div class="collapse-card__body">
                                                <div class="form-group">
                                                    <label>Nome da seção</label>
                                                    <input type="text" name="steps_section[]" value="<?= e($section) ?>"
                                                        placeholder="Ex: Corpo (vermelho/rosa)" class="section-name-input">
                                                </div>
                                                <div class="form-group">
                                                    <label>Linhas da receita (1 por linha)</label>
                                                    <textarea name="steps_content[]" rows="6"
                                                        placeholder="R1: 6 pb no anel mágico (6)&#10;R2: aum em todos os pontos (12)&#10;R3: (1 pb, aum) x 6 (18)"
                                                        class="steps-content-input"><?= e($content) ?></textarea>
                                                    <small style="color:#888;">Formato <code>Rxx: … (total)</code> vira
                                                        tabela.</small>
                                                </div>
                                                <?php
                                                $stepPhoto = $b['data']['photo'] ?? '';
                                                $stepPhotoCaption = $b['data']['photo_caption'] ?? '';
                                                ?>
                                                <div class="form-group">
                                                    <label>🖼️ Foto da seção (opcional)</label>
                                                    <div class="photo-upload-wrapper">
                                                        <div
                                                            class="photo-preview <?= $stepPhoto !== '' ? 'has-photo' : '' ?>">
                                                            <?php if ($stepPhoto !== ''): ?>
                                                                <img src="<?= e($stepPhoto) ?>" alt="">
                                                            <?php else: ?>
                                                                <span class="photo-placeholder">Sem foto</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="photo-controls">
                                                            <input type="hidden" name="steps_photo[]"
                                                                value="<?= e($stepPhoto) ?>" class="photo-url">
                                                            <label class="btn btn-secondary btn-photo-pick">
                                                                Escolher foto
                                                                <input type="file" accept="image/jpeg,image/png,image/webp"
                                                                    class="photo-file-input" style="display:none;">
                                                            </label>
                                                            <button type="button" class="btn btn-danger btn-photo-remove"
                                                                style="<?= $stepPhoto === '' ? 'display:none;' : '' ?>">
                                                                Remover
                                                            </button>
                                                            <span class="photo-status"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Legenda da foto (opcional)</label>
                                                    <input type="text" name="steps_photo_caption[]"
                                                        value="<?= e($stepPhotoCaption) ?>" placeholder="Ex: Após R5">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="add-steps-section" class="btn btn-secondary btn-block"
                                    style="margin-top:14px;">
                                    + Adicionar seção de passos
                                </button>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: DICAS / ALERTAS / IMPORTANTE (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-tips-block" style="display:none;">
                            <div class="card-header">
                                <h2>💡 Dicas, Alertas e Avisos</h2>
                            </div>
                            <div class="form-group">
                                <small style="display:block;color:#888;margin-bottom:6px;">
                                    Notas pra leitora — escolha o tipo (Dica · Alerta · Importante).
                                </small>

                                <div id="tips-list" class="blocks-list">
                                    <?php
                                    $tipLabelMap = ['tip' => '💡 Dica', 'alert' => '⚠️ Alerta', 'important' => '❗ Importante'];
                                    ?>
                                    <?php foreach ($tipsBlocks as $b): ?>
                                        <?php
                                        $tipType = $b['data']['type'] ?? 'tip';
                                        if (!isset($tipLabelMap[$tipType]))
                                            $tipType = 'tip';
                                        $tipText = $b['data']['text'] ?? '';
                                        $preview = mb_substr($tipText, 0, 50);
                                        if (mb_strlen($tipText) > 50)
                                            $preview .= '…';
                                        ?>
                                        <div class="collapse-card open" data-block-type="tip">
                                            <div class="collapse-card__header">
                                                <span class="collapse-card__title">
                                                    <span class="chevron">▼</span>
                                                    <span class="tip-type-preview"><?= $tipLabelMap[$tipType] ?></span>
                                                    <span class="line-count">— <?= e($preview ?: '(vazio)') ?></span>
                                                </span>
                                                <span class="collapse-card__controls">
                                                    <button type="button" class="btn-move-up" title="Subir">↑</button>
                                                    <button type="button" class="btn-move-down" title="Descer">↓</button>
                                                    <button type="button" class="btn-remove" title="Remover">×</button>
                                                </span>
                                            </div>
                                            <div class="collapse-card__body">
                                                <div class="form-group">
                                                    <label>Tipo</label>
                                                    <select name="tip_type[]" class="tip-type-input">
                                                        <option value="tip" <?= $tipType === 'tip' ? 'selected' : '' ?>>💡 Dica
                                                            (sage)</option>
                                                        <option value="alert" <?= $tipType === 'alert' ? 'selected' : '' ?>>⚠️
                                                            Alerta (honey)</option>
                                                        <option value="important" <?= $tipType === 'important' ? 'selected' : '' ?>>❗ Importante (rose)</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Texto</label>
                                                    <textarea name="tip_text[]" rows="3"
                                                        placeholder="Ex: Use marcador de pontos pra não perder a conta."
                                                        class="tip-text-input"><?= e($tipText) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="add-tip" class="btn btn-secondary btn-block"
                                    style="margin-top:14px;">
                                    + Adicionar nota
                                </button>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: STEP PHOTOS GALERIA (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-step-photos-block" style="display:none;">
                            <div class="card-header">
                                <h2>📸 Step Photos (galeria final)</h2>
                            </div>
                            <div class="form-group">
                                <small style="display:block;color:#888;margin-bottom:6px;">
                                    Sequência de fotos do processo, ao final da receita. 6–8 fotos costuma ficar melhor.
                                </small>

                                <div id="step-photos-list" class="step-photos-list">
                                    <?php foreach ($allStepPhotos as $i => $photo): ?>
                                        <?php $pUrl = $photo['url'] ?? '';
                                        $pCap = $photo['caption'] ?? ''; ?>
                                        <div class="step-photo-item" data-index="<?= $i ?>">
                                            <div class="step-photo-number"><?= $i + 1 ?></div>
                                            <div class="step-photo-preview <?= $pUrl !== '' ? 'has-photo' : '' ?>">
                                                <?php if ($pUrl !== ''): ?>
                                                    <img src="<?= e($pUrl) ?>" alt="">
                                                <?php else: ?>
                                                    <span class="photo-placeholder">Sem foto</span>
                                                <?php endif; ?>
                                            </div>
                                            <input type="hidden" name="step_photos_url[]" value="<?= e($pUrl) ?>"
                                                class="step-photo-url">
                                            <input type="text" name="step_photos_caption[]" value="<?= e($pCap) ?>"
                                                placeholder="Legenda (ex: Anel mágico)" class="step-photo-caption">
                                            <div class="step-photo-controls">
                                                <label class="btn-icon btn-photo-pick" title="Trocar foto">
                                                    📷
                                                    <input type="file" accept="image/jpeg,image/png,image/webp"
                                                        class="photo-file-input" style="display:none;">
                                                </label>
                                                <button type="button" class="btn-icon btn-move-up" title="Subir">↑</button>
                                                <button type="button" class="btn-icon btn-move-down"
                                                    title="Descer">↓</button>
                                                <button type="button" class="btn-icon btn-photo-remove"
                                                    title="Remover">×</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="add-step-photo" class="btn btn-secondary btn-block"
                                    style="margin-top:14px;">
                                    + Adicionar foto à galeria
                                </button>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: COLOR GUIDE (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-color-guide-block" style="display:none;">
                            <div class="card-header">
                                <h2>🎨 Cores usadas</h2>
                            </div>
                            <div class="form-group">
                                <small style="display:block;color:#888;margin-bottom:6px;">
                                    Pra peças coloridas. Use o color picker e escreva nome + uso.
                                </small>

                                <div id="color-guide-list" class="color-guide-list">
                                    <?php foreach ($allColors as $color): ?>
                                        <div class="color-item">
                                            <input type="color" name="color_hex[]"
                                                value="<?= e($color['hex'] ?? '#F5C0C0') ?>" class="color-picker">
                                            <input type="text" name="color_name_br[]"
                                                value="<?= e($color['name_br'] ?? '') ?>" placeholder="Nome (ex: Rosa)"
                                                class="color-name">
                                            <input type="text" name="color_usage_br[]"
                                                value="<?= e($color['usage_br'] ?? '') ?>" placeholder="Uso (ex: Corpo)"
                                                class="color-usage">
                                            <button type="button" class="btn-icon btn-remove" title="Remover">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="add-color" class="btn btn-secondary btn-block"
                                    style="margin-top:14px;">
                                    + Adicionar cor
                                </button>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: STITCH GUIDE INLINE (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-stitch-inline-block" style="display:none;">
                            <div class="card-header">
                                <h2>📖 Stitch Guide inline</h2>
                            </div>
                            <div class="form-group">
                                <small style="display:block;color:#888;margin-bottom:6px;">
                                    Mini-guia explicando pontos específicos no meio da receita.
                                    Use quando a leitora precisa entender 1–2 pontos novos pra seguir.
                                </small>

                                <div id="stitch-inline-list" class="blocks-list">
                                    <?php foreach ($stitchInlineBlocks as $b): ?>
                                        <?php
                                        $sid = (int) ($b['data']['stitch_id'] ?? 0);
                                        $obs = $b['data']['observation'] ?? '';

                                        // Nome pra preview no header
                                        $sname = '(escolher ponto)';
                                        if ($sid > 0) {
                                            foreach ($allStitches as $s) {
                                                if ((int) $s['id'] === $sid) {
                                                    $sname = $s['name_br'];
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="collapse-card open" data-block-type="stitch_inline">
                                            <div class="collapse-card__header">
                                                <span class="collapse-card__title">
                                                    <span class="chevron">▼</span>
                                                    <strong>Ponto:</strong>
                                                    <span class="stitch-name-preview"><?= e($sname) ?></span>
                                                </span>
                                                <span class="collapse-card__controls">
                                                    <button type="button" class="btn-move-up" title="Subir">↑</button>
                                                    <button type="button" class="btn-move-down" title="Descer">↓</button>
                                                    <button type="button" class="btn-remove" title="Remover">×</button>
                                                </span>
                                            </div>
                                            <div class="collapse-card__body">
                                                <div class="form-group">
                                                    <label>Ponto cadastrado</label>
                                                    <select name="stitch_inline_id[]" class="stitch-inline-select">
                                                        <option value="">— Selecionar ponto —</option>
                                                        <?php foreach ($allStitches as $s): ?>
                                                            <option value="<?= (int) $s['id'] ?>" <?= $sid === (int) $s['id'] ? 'selected' : '' ?>>
                                                                <?= e($s['name_br']) ?>
                                                                <?= !empty($s['abbrev_br']) ? ' (' . e($s['abbrev_br']) . ')' : '' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Observação extra (opcional)</label>
                                                    <textarea name="stitch_inline_observation[]" rows="2"
                                                        placeholder="Ex: Lembre de fechar com o último laço pra não soltar."><?= e($obs) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="add-stitch-inline" class="btn btn-secondary btn-block"
                                    style="margin-top:14px;">
                                    + Adicionar ponto ao guia
                                </button>
                            </div>
                        </div>

                        <!-- ==========================================
                             BLOCO: NOTAS / VARIAÇÕES (recipe-only)
                             ========================================== -->
                        <div class="card recipe-only" id="recipe-notes-block" style="display:none;">
                            <div class="card-header">
                                <h2>📝 Notas e Variações</h2>
                            </div>
                            <?php
                            // Junta texto de todos os blocos de notas (espera-se só 1, mas é defensivo)
                            $allNotesText = '';
                            foreach ($notesBlocks as $b) {
                                if (!empty($b['data']['notes'])) {
                                    $allNotesText .= ($allNotesText !== '' ? "\n" : '') . $b['data']['notes'];
                                }
                            }
                            ?>
                            <div class="form-group">
                                <label for="notes_content">Notas (uma por linha)</label>
                                <textarea id="notes_content" name="notes_content" rows="6"
                                    placeholder="Pra fazer maior: aumente 6 pb em cada carreira de aumento&#10;Pode substituir o anel mágico por 2 c + fechar com pbx&#10;Versão sem amigurumi: use ponto baixo dobrado"><?= e($allNotesText) ?></textarea>
                                <small style="color:#888;">Observações extras, variações, dicas avançadas. Aparece no
                                    final da receita.</small>
                            </div>
                        </div>

                        <!-- IMAGEM DESTACADA -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Imagem Destacada</h2>
                            </div>
                            <div class="form-group">
                                <label for="featured_image">Upload</label>
                                <input type="file" id="featured_image" name="featured_image" accept="image/*">
                                <?php if (!empty($page['featured_image'])): ?>
                                    <div style="margin-top:10px;">
                                        <img src="<?= BASE_URL . e($page['featured_image']) ?>"
                                            style="max-width:200px;border-radius:8px;">
                                    </div>
                                    <input type="hidden" name="featured_image_existing"
                                        value="<?= e($page['featured_image']) ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-sidebar">

                        <!-- PUBLICAÇÃO -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Publicação</h2>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="page_type">Tipo</label>
                                <select id="page_type" name="page_type">
                                    <option value="article" <?= ($page['page_type'] ?? 'article') === 'article' ? 'selected' : '' ?>>📝 Artigo</option>
                                    <option value="recipe" <?= ($page['page_type'] ?? '') === 'recipe' ? 'selected' : '' ?>>🧶 Receita</option>
                                    <option value="static" <?= ($page['page_type'] ?? '') === 'static' ? 'selected' : '' ?>>📄 Estática</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="language">Idioma</label>
                                <select id="language" name="language">
                                    <option value="br" <?= ($page['language'] ?? 'br') === 'br' ? 'selected' : '' ?>>
                                        Português (br)</option>
                                    <option value="en" <?= ($page['language'] ?? '') === 'en' ? 'selected' : '' ?>>English
                                        (en)</option>
                                </select>
                            </div>
                            <div class="form-group" id="category-group">
                                <label for="category">Categoria <span style="color:#D03B47;">*</span></label>
                                <select id="category" name="category">
                                    <option value="">— Selecione —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat['slug']) ?>" <?= ($page['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= e($cat['name_br']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="category-hint" style="display:none;color:#888;">
                                    Páginas estáticas não têm categoria.
                                </small>
                            </div>
                            <div class="form-group" id="hero-style-group">
                                <label for="template">Estilo de hero</label>
                                <select id="template" name="template">
                                    <option value="hero-classic" <?= ($page['template'] ?? 'hero-classic') === 'hero-classic' ? 'selected' : '' ?>>Clássico (imagem grande)
                                    </option>
                                    <option value="hero-side" <?= ($page['template'] ?? '') === 'hero-side' ? 'selected' : '' ?>>Lateral (imagem ao lado)</option>
                                    <option value="hero-minimal" <?= ($page['template'] ?? '') === 'hero-minimal' ? 'selected' : '' ?>>Minimalista (sem imagem)</option>
                                </select>
                                <small style="color:#888;display:block;margin-top:4px;">
                                    Disponível apenas pra artigos. Padrão: Clássico.
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="publish_date">Data de Publicação</label>
                                <input type="date" id="publish_date" name="publish_date"
                                    value="<?= e($page['publish_date'] ?? '') ?>">
                                <small style="color:#888;display:block;margin-top:4px;">
                                    Se deixar em branco ao publicar, será preenchida automaticamente com a data de hoje. Em rascunho, fica em branco.
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="author_name">Autor</label>
                                <input type="text" id="author_name" name="author_name"
                                    value="<?= e($page['author_name'] ?? '') ?>" placeholder="Mai Marini">
                            </div>
                            <div class="form-group">
                                <label for="reading_time">Tempo de leitura (min, auto se vazio)</label>
                                <input type="number" id="reading_time" name="reading_time" min="0"
                                    value="<?= e($page['reading_time'] ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?= $id ? 'Atualizar' : 'Criar' ?>
                            </button>
                        </div>

                        <!-- CAMPOS ESPECÍFICOS DE RECEITA -->
                        <div class="card recipe-only" id="recipe-fields" style="display:none;">
                            <div class="card-header">
                                <h2>🧶 Receita</h2>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">Dificuldade *</label>
                                <select id="difficulty" name="difficulty">
                                    <option value="">— Selecione —</option>
                                    <?php foreach (getRecipeDifficulties() as $key => $labels): ?>
                                        <option value="<?= e($key) ?>" <?= ($page['difficulty'] ?? '') === $key ? 'selected' : '' ?>>
                                            <?= e($labels['br']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="piece_type">Tipo de peça</label>
                                <select id="piece_type" name="piece_type">
                                    <option value="">— Selecione —</option>
                                    <?php foreach (getRecipePieceTypes() as $key => $labels): ?>
                                        <option value="<?= e($key) ?>" <?= ($page['piece_type'] ?? '') === $key ? 'selected' : '' ?>>
                                            <?= e($labels['br']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="estimated_time">Tempo estimado</label>
                                <input type="text" id="estimated_time" name="estimated_time"
                                    value="<?= e($page['estimated_time'] ?? '') ?>"
                                    placeholder="Ex: 2 horas, 1 fim de semana" maxlength="100">
                            </div>

                            <div class="form-group">
                                <label for="final_size">Tamanho final</label>
                                <input type="text" id="final_size" name="final_size"
                                    value="<?= e($page['final_size'] ?? '') ?>" placeholder="Ex: 10cm de altura"
                                    maxlength="200">
                            </div>

                            <div class="form-group">
                                <label for="yarn_recommended">Linha / fio recomendado</label>
                                <textarea id="yarn_recommended" name="yarn_recommended" rows="2"
                                    placeholder="Ex: Fio de algodão tipo Anne, 100g"><?= e($page['yarn_recommended'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="hook_size">Agulha</label>
                                <input type="text" id="hook_size" name="hook_size"
                                    value="<?= e($page['hook_size'] ?? '') ?>" placeholder="Ex: 2.5mm" maxlength="20">
                            </div>

                            <div class="form-group">
                                <label
                                    style="display:flex;align-items:center;gap:8px;cursor:pointer;text-transform:none;letter-spacing:0;font-weight:500;">
                                    <input type="checkbox" id="is_free" name="is_free" value="1"
                                        <?= !empty($page['is_free']) || (!$id && ($page['is_free'] ?? 1)) ? 'checked' : '' ?>>
                                    <span>Receita gratuita (mostra selo "Free Pattern")</span>
                                </label>
                            </div>
                        </div>

                        <!-- SEO -->
                        <div class="card">
                            <div class="card-header">
                                <h2>SEO</h2>
                            </div>
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title"
                                    value="<?= e($page['meta_title'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description"
                                    rows="3"><?= e($page['meta_description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- TRACKING -->
                        <div class="card">
                            <div class="card-header">
                                <h2>📊 Tracking</h2>
                            </div>
                            <div class="form-group">
                                <label for="tracking_code">Código adicional (só desta página)</label>
                                <textarea id="tracking_code" name="tracking_code" rows="6"
                                    style="font-family:monospace;font-size:12px;"
                                    placeholder="<script>...</script>"><?= e($page['tracking_code'] ?? '') ?></textarea>
                                <small style="color:#888;display:block;margin-top:4px;">Trackings globais ficam em <a
                                        href="<?= BASE_URL ?>admin/settings.php#tracking"
                                        style="color:#e94560;">Configurações</a>.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script src="<?= BASE_URL ?>admin/assets/page-form.js"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 600,
            menubar: false,
            language: 'pt_BR',
            language_url: 'https://cdn.tiny.cloud/1/<?= e(TINYMCE_KEY) ?>/tinymce/6/langs/pt_BR.js',

            plugins: [
                'lists', 'link', 'image', 'autolink', 'searchreplace',
                'visualblocks', 'wordcount', 'code', 'fullscreen', 'help'
            ],

            // TOOLBAR NÍVEL MÉDIO (configurável depois pela Maíra)
            toolbar: 'undo redo | blocks | bold italic underline | ' +
                'bullist numlist | link image blockquote | ' +
                'code fullscreen',

            // Permitir classes/atributos dos blocos editoriais
            valid_classes: {
                '*': 'editorial-* article-*'
            },

            // Permitir HTML que TinyMCE removeria por padrão
            extended_valid_elements: 'aside[class],figure[class],figcaption,' +
                'blockquote[class|cite],cite,' +
                'div[class],hr[class]',

            paste_data_images: true,

            // Estilo do conteúdo no editor (preview interno)
            content_style: `
                body {
                    font-family: 'Inter', -apple-system, sans-serif;
                    font-size: 16px;
                    line-height: 1.7;
                    color: #23314A;
                    max-width: 720px;
                    margin: 20px auto;
                    padding: 20px;
                }
                h1, h2, h3, h4 {
                    font-family: 'Playfair Display', serif;
                    color: #23314A;
                    margin-top: 28px;
                }
                p { margin: 16px 0; }

                /* Preview dos blocos editoriais dentro do TinyMCE */
                .editorial-quote {
                    border-left: 4px solid #DB8084;
                    padding: 20px 28px;
                    font-style: italic;
                    background: #F5E1DC;
                    border-radius: 8px;
                    font-family: 'Playfair Display', serif;
                    margin: 24px 0;
                }
                .editorial-quote cite {
                    display: block;
                    margin-top: 10px;
                    font-size: 13px;
                    color: #7E8AA0;
                }
                .editorial-tip {
                    background: #F5E1DC;
                    border-left: 4px solid #DB8084;
                    padding: 18px 22px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .editorial-warning {
                    background: #FFF3E0;
                    border-left: 4px solid #E8A87C;
                    padding: 18px 22px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .editorial-product {
                    background: #FAF6F0;
                    border: 1px solid #E8DCD3;
                    padding: 20px;
                    border-radius: 12px;
                    margin: 24px 0;
                }
                .editorial-highlight {
                    font-family: 'Playfair Display', serif;
                    font-size: 26px;
                    text-align: center;
                    color: #D03B47;
                    margin: 32px 0;
                    font-style: italic;
                }
                .editorial-divider {
                    border: 0;
                    height: 1px;
                    background: #E8DCD3;
                    margin: 36px auto;
                    max-width: 200px;
                }
                .editorial-gallery {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                    gap: 12px;
                    margin: 24px 0;
                }
                .editorial-gallery img { width: 100%; border-radius: 6px; }
                .editorial-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .editorial-table th,
                .editorial-table td {
                    padding: 10px 14px;
                    border: 1px solid #E8DCD3;
                }
                .editorial-table th {
                    background: #F5E1DC;
                    font-family: 'Playfair Display', serif;
                }
            `,

            // Hook ao mudar conteúdo: atualizar tempo de leitura
            setup: function (editor) {
                editor.on('input change', debounce(function () {
                    if (typeof window.updateReadingTime === 'function') {
                        window.updateReadingTime(editor.getContent({ format: 'text' }));
                    }
                }, 500));
            },

            // Carregar conteúdo existente ao iniciar (caso seja preciso via window.initialContent)
            init_instance_callback: function (editor) {
                if (typeof window.initialContent !== 'undefined') {
                    editor.setContent(window.initialContent);
                }
            }
        });

        // Debounce utility (necessário pelo setup acima)
        function debounce(fn, delay) {
            let timer;
            return function () {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, arguments), delay);
            };
        }
    </script>
</body>

</html>