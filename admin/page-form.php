<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$templates = getTemplates();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$page = null;
$errors = [];

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

        // Verificar slug único
        if (empty($errors)) {
            $pdo = getDB();
            $slugCheck = $pdo->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
            $slugCheck->execute([$data['slug'], $id ?? 0]);
            if ($slugCheck->fetch()) {
                $errors[] = 'Este slug já está em uso. Escolha outro.';
            }
        }

        if (empty($errors)) {
            $savedId = savePage($data, $id);
            if ($savedId) {
                // Invalida o cache de categorias (contagem/disponibilidade no drawer)
                if (function_exists('clearCategoriesCache')) {
                    clearCategoriesCache();
                }
                header('Location: ' . BASE_URL . 'admin/pages.php?msg=saved');
                exit;
            } else {
                $errors[] = 'Erro ao salvar a página.';
            }
        }

        // Preservar dados do form em caso de erro
        $page = array_merge($page ?? [], $data);
        $page['id'] = $id;
    }
}

// Preparar dados auxiliares
$comments = [];
if ($page && !empty($page['comments_json'])) {
    $comments = json_decode($page['comments_json'], true) ?: [];
}

$content2Images = [];
if ($page && !empty($page['content2_images_json'])) {
    $content2Images = json_decode($page['content2_images_json'], true) ?: [];
}

$footerButtons = [];
if ($page && !empty($page['footer_buttons_json'])) {
    $footerButtons = json_decode($page['footer_buttons_json'], true) ?: [];
}
while (count($footerButtons) < 3) {
    $footerButtons[] = ['text' => '', 'link' => ''];
}

$currentTemplate = $page['template'] ?? 'structured';
$pageTitle = $id ? 'Editar Página' : 'Nova Página';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Presell Manager</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Open+Sans:wght@400;700&family=Lato:wght@400;700&family=Montserrat:wght@400;700&family=Poppins:wght@400;700&family=Merriweather:wght@400;700&family=Playfair+Display:wght@400;700&family=Oswald:wght@400;700&family=Raleway:wght@400;700&family=Source+Sans+3:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .ql-editor { min-height: 200px; font-family: Georgia, serif; font-size: 16px; line-height: 1.7; background: #fff; color: #333; }
        .ql-toolbar.ql-snow { background: #fff; border-color: #2a2a4a; border-radius: 8px 8px 0 0; }
        .ql-container.ql-snow { border-color: #2a2a4a; border-radius: 0 0 8px 8px; }
        .quill-editor { border-radius: 8px; margin-bottom: 4px; }
        .gradient-colors { display: flex; gap: 8px; align-items: center; }
        .gradient-colors input[type="color"] { width: 50px; height: 38px; padding: 2px; }
        .image-thumb { max-width: 80px; max-height: 80px; margin: 4px; border-radius: 4px; vertical-align: middle; border: 1px solid #2a2a4a; }
        .images-list { display: flex; flex-wrap: wrap; gap: 8px; padding: 0 20px 10px; }
        .image-slot { position: relative; }
        .image-slot .remove-btn { position: absolute; top: -6px; right: -6px; background: #e94560; color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; border: none; }
        .footer-button-row { display: grid; grid-template-columns: 1fr 1.5fr; gap: 8px; margin-bottom: 8px; }

        /* Quill: mostrar tamanho em px e nome da fonte no dropdown */
        .ql-snow .ql-picker.ql-size .ql-picker-label::before,
        .ql-snow .ql-picker.ql-size .ql-picker-item::before { content: attr(data-value) !important; }
        .ql-snow .ql-picker.ql-size .ql-picker-label[data-value]:not([data-value=""])::before,
        .ql-snow .ql-picker.ql-size .ql-picker-item[data-value]:not([data-value=""])::before { content: attr(data-value) !important; }
        .ql-snow .ql-picker.ql-font .ql-picker-label::before,
        .ql-snow .ql-picker.ql-font .ql-picker-item::before { content: attr(data-value) !important; }
        .ql-snow .ql-picker.ql-font { width: 140px; }
        .ql-snow .ql-picker.ql-size { width: 70px; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Arial"]::before { font-family: Arial; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Georgia"]::before { font-family: Georgia; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Times New Roman"]::before { font-family: 'Times New Roman'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Courier New"]::before { font-family: 'Courier New'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Roboto"]::before { font-family: 'Roboto'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Open Sans"]::before { font-family: 'Open Sans'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Lato"]::before { font-family: 'Lato'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Montserrat"]::before { font-family: 'Montserrat'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Poppins"]::before { font-family: 'Poppins'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Merriweather"]::before { font-family: 'Merriweather'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Playfair Display"]::before { font-family: 'Playfair Display'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Oswald"]::before { font-family: 'Oswald'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Raleway"]::before { font-family: 'Raleway'; }
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Source Sans 3"]::before { font-family: 'Source Sans 3'; }

        /* Cor + hex input */
        .color-with-hex { display: flex; gap: 8px; align-items: center; }
        .color-with-hex input[type="color"] { width: 50px; height: 38px; padding: 2px; flex-shrink: 0; }
        .color-with-hex input[type="text"] { width: 100px; padding: 8px 10px; font-family: monospace; text-transform: uppercase; }

        /* Botões de cor HEX customizados na toolbar do Quill */
        .ql-toolbar.ql-snow .ql-hex-btn { width: auto !important; padding: 0 6px !important; height: 24px; font-size: 13px; cursor: pointer; background: transparent; border: 1px solid transparent; border-radius: 3px; margin: 0 2px; }
        .ql-toolbar.ql-snow .ql-hex-btn:hover { background: #f0f0f0; border-color: #ccc; }

        /* Permitir que dropdowns do Quill (font, size, cores) escapem do card */
        .form-grid .card { overflow: visible; }
        .ql-snow .ql-picker-options { z-index: 100; max-height: 400px; overflow-y: auto; }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php $activePage = 'new-page'; include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1><?= e($pageTitle) ?></h1>
                <a href="<?= BASE_URL ?>admin/pages.php" class="btn btn-secondary">← Voltar</a>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="page-form" id="page-form">
                <?= csrfField() ?>
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-main">
                        <!-- Informações Gerais -->
                        <div class="card">
                            <div class="card-header"><h2>Informações Gerais</h2></div>

                            <div class="form-group">
                                <label for="title">Título *</label>
                                <input type="text" id="title" name="title" required
                                    value="<?= e($page['title'] ?? '') ?>"
                                    placeholder="Ex: Novo método revolucionário..."
                                    oninput="generateSlug(this.value)">
                            </div>

                            <div class="form-group">
                                <label for="subtitle">Subtítulo</label>
                                <input type="text" id="subtitle" name="subtitle"
                                    value="<?= e($page['subtitle'] ?? '') ?>"
                                    placeholder="Ex: Descubra como milhares de pessoas...">
                            </div>

                            <div class="form-group">
                                <label for="slug">Slug (URL) *</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">seusite.com/</span>
                                    <input type="text" id="slug" name="slug" value="<?= e($page['slug'] ?? '') ?>"
                                        placeholder="meu-artigo-incrivel">
                                </div>
                            </div>
                        </div>

                        <!-- =========== TEMPLATES CLÁSSICOS =========== -->
                        <div class="template-classic-fields" style="display:none;">
                            <div class="card">
                                <div class="card-header"><h2>Conteúdo do Artigo</h2></div>
                                <div class="form-group">
                                    <div id="editor-container" class="quill-editor"></div>
                                    <textarea id="content" name="content" style="display:none"><?= e($page['content'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header"><h2>Imagem Principal</h2></div>
                                <div class="form-group">
                                    <label for="main_image">Upload da Imagem</label>
                                    <input type="file" id="main_image" name="main_image" accept="image/*">
                                    <?php if (!empty($page['main_image'])): ?>
                                        <div><img src="<?= BASE_URL . e($page['main_image']) ?>" class="image-thumb" style="max-width:200px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h2>Comentários Fictícios</h2>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addComment()">+ Adicionar</button>
                                </div>
                                <div id="comments-container">
                                    <?php foreach ($comments as $i => $c): ?>
                                        <div class="comment-row">
                                            <div class="comment-fields">
                                                <input type="text" name="comment_name[]" placeholder="Nome" value="<?= e($c['name']) ?>">
                                                <input type="text" name="comment_date[]" placeholder="Data (dd/mm/aaaa)" value="<?= e($c['date']) ?>">
                                                <textarea name="comment_text[]" placeholder="Texto do comentário" rows="2"><?= e($c['text']) ?></textarea>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-delete" onclick="this.closest('.comment-row').remove()">🗑️</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- =========== TEMPLATE ESTRUTURADO (4 SEÇÕES) =========== -->
                        <div class="template-structured-fields" style="display:none;">

                            <!-- 1. CABEÇALHO -->
                            <div class="card">
                                <div class="card-header"><h2>🎨 1. Cabeçalho</h2></div>

                                <div class="form-group">
                                    <label>Tipo de Fundo</label>
                                    <select name="header_bg_type" id="header_bg_type" onchange="toggleGradientFields()">
                                        <option value="solid" <?= ($page['header_bg_type'] ?? 'solid') === 'solid' ? 'selected' : '' ?>>Cor Sólida</option>
                                        <option value="linear" <?= ($page['header_bg_type'] ?? '') === 'linear' ? 'selected' : '' ?>>Gradient Linear</option>
                                        <option value="radial" <?= ($page['header_bg_type'] ?? '') === 'radial' ? 'selected' : '' ?>>Gradient Radial</option>
                                    </select>
                                </div>

                                <div class="form-group" id="gradient-direction-group">
                                    <label>Direção (apenas linear)</label>
                                    <select name="header_bg_direction">
                                        <?php $dir = $page['header_bg_direction'] ?? 'to bottom'; ?>
                                        <option value="to bottom" <?= $dir === 'to bottom' ? 'selected' : '' ?>>↓ Topo para baixo</option>
                                        <option value="to top" <?= $dir === 'to top' ? 'selected' : '' ?>>↑ Baixo para topo</option>
                                        <option value="to right" <?= $dir === 'to right' ? 'selected' : '' ?>>→ Esquerda para direita</option>
                                        <option value="to left" <?= $dir === 'to left' ? 'selected' : '' ?>>← Direita para esquerda</option>
                                        <option value="135deg" <?= $dir === '135deg' ? 'selected' : '' ?>>↘ Diagonal</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Cores do Fundo</label>
                                    <div class="gradient-colors">
                                        <input type="color" name="header_bg_color1" value="<?= e($page['header_bg_color1'] ?? '#ffffff') ?>" title="Cor 1">
                                        <input type="color" name="header_bg_color2" value="<?= e($page['header_bg_color2'] ?? '#ffffff') ?>" title="Cor 2 (gradient)">
                                        <input type="color" name="header_bg_color3" value="<?= e($page['header_bg_color3'] ?? '#ffffff') ?>" title="Cor 3 (opcional)">
                                    </div>
                                    <small style="color:#888;display:block;margin-top:4px;">Use cor 2 e 3 apenas para gradient. Deixe igual à cor 1 para sólida.</small>
                                </div>

                                <div class="form-group">
                                    <label>Texto Acima da Imagem</label>
                                    <div id="header-text-editor" class="quill-editor"></div>
                                    <textarea name="header_text" id="header_text" style="display:none"><?= e($page['header_text'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="header_image">Imagem do Cabeçalho</label>
                                    <input type="file" name="header_image" accept="image/*">
                                    <?php if (!empty($page['header_image'])): ?>
                                        <div><img src="<?= BASE_URL . e($page['header_image']) ?>" class="image-thumb" style="max-width:160px;"></div>
                                    <?php endif; ?>
                                    <input type="hidden" name="header_image_existing" value="<?= e($page['header_image'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Texto Abaixo da Imagem</label>
                                    <div id="header-text-below-editor" class="quill-editor"></div>
                                    <textarea name="header_text_below" id="header_text_below" style="display:none"><?= e($page['header_text_below'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- 2. CONTEÚDO 1 -->
                            <div class="card">
                                <div class="card-header"><h2>📝 2. Conteúdo 1 (Texto + Imagem)</h2></div>

                                <div class="form-group">
                                    <label>Texto (esquerda)</label>
                                    <div id="content1-text-editor" class="quill-editor"></div>
                                    <textarea name="content1_text" id="content1_text" style="display:none"><?= e($page['content1_text'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Imagem (direita)</label>
                                    <input type="file" name="content1_image" accept="image/*">
                                    <?php if (!empty($page['content1_image'])): ?>
                                        <div><img src="<?= BASE_URL . e($page['content1_image']) ?>" class="image-thumb" style="max-width:160px;"></div>
                                    <?php endif; ?>
                                    <input type="hidden" name="content1_image_existing" value="<?= e($page['content1_image'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Imagem de Fundo (atrás de tudo)</label>
                                    <input type="file" name="content1_bg_image" accept="image/*">
                                    <?php if (!empty($page['content1_bg_image'])): ?>
                                        <div><img src="<?= BASE_URL . e($page['content1_bg_image']) ?>" class="image-thumb" style="max-width:160px;"></div>
                                    <?php endif; ?>
                                    <input type="hidden" name="content1_bg_image_existing" value="<?= e($page['content1_bg_image'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Cor de Fundo (sem imagem)</label>
                                    <input type="color" name="content1_bg_color" value="<?= e($page['content1_bg_color'] ?? '#ffffff') ?>">
                                </div>
                            </div>

                            <!-- 3. CONTEÚDO 2 -->
                            <div class="card">
                                <div class="card-header"><h2>🖼️ 3. Conteúdo 2 (Imagens + CTA)</h2></div>

                                <div class="form-group">
                                    <label>Imagens (até 5, centralizadas em grid)</label>
                                    <input type="file" name="content2_images[]" accept="image/*" multiple>
                                    <small style="color:#888;display:block;margin-top:4px;">Selecione novas imagens (substitui as atuais)</small>
                                </div>

                                <?php if (!empty($content2Images)): ?>
                                    <div class="images-list">
                                        <?php foreach ($content2Images as $img): ?>
                                            <div class="image-slot">
                                                <img src="<?= BASE_URL . e($img) ?>" class="image-thumb">
                                                <input type="hidden" name="content2_images_existing[]" value="<?= e($img) ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label>Texto</label>
                                    <div id="content2-text-editor" class="quill-editor"></div>
                                    <textarea name="content2_text" id="content2_text" style="display:none"><?= e($page['content2_text'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Texto do Botão CTA</label>
                                    <input type="text" name="content2_cta_text"
                                        value="<?= e($page['content2_cta_text'] ?? 'Saiba Mais') ?>"
                                        placeholder="Ex: Quero Garantir o Meu">
                                </div>

                                <div class="form-group">
                                    <label>Cor de Fundo do Botão CTA</label>
                                    <input type="color" name="content2_cta_color" value="<?= e($page['content2_cta_color'] ?? '#e85d04') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Cor do Texto do Botão CTA</label>
                                    <input type="color" name="content2_cta_text_color" value="<?= e($page['content2_cta_text_color'] ?? '#ffffff') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Imagem de Fundo (atrás de tudo)</label>
                                    <input type="file" name="content2_bg_image" accept="image/*">
                                    <?php if (!empty($page['content2_bg_image'])): ?>
                                        <div><img src="<?= BASE_URL . e($page['content2_bg_image']) ?>" class="image-thumb" style="max-width:160px;"></div>
                                    <?php endif; ?>
                                    <input type="hidden" name="content2_bg_image_existing" value="<?= e($page['content2_bg_image'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Cor de Fundo (sem imagem)</label>
                                    <input type="color" name="content2_bg_color" value="<?= e($page['content2_bg_color'] ?? '#f8f9fa') ?>">
                                </div>
                            </div>

                            <!-- 4. RODAPÉ -->
                            <div class="card">
                                <div class="card-header"><h2>📜 4. Rodapé</h2></div>

                                <div class="form-group">
                                    <label>Cor de Fundo</label>
                                    <input type="color" name="footer_bg_color" value="<?= e($page['footer_bg_color'] ?? '#1a1a2e') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Texto de Alertas / Disclaimer</label>
                                    <div id="footer-alerts-editor" class="quill-editor"></div>
                                    <textarea name="footer_alerts" id="footer_alerts" style="display:none"><?= e($page['footer_alerts'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Botões do Rodapé (3)</label>
                                    <?php foreach ($footerButtons as $i => $btn): ?>
                                        <div class="footer-button-row">
                                            <input type="text" name="footer_btn_text[]" placeholder="Texto do botão <?= $i + 1 ?>"
                                                value="<?= e($btn['text'] ?? '') ?>">
                                            <input type="url" name="footer_btn_link[]" placeholder="https://..."
                                                value="<?= e($btn['link'] ?? '') ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="form-group">
                                    <label>Cor do Texto dos Botões</label>
                                    <input type="color" name="footer_btn_color" value="<?= e($page['footer_btn_color'] ?? '#ffffff') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Tamanho da Fonte dos Botões</label>
                                    <select name="footer_btn_size">
                                        <?php
                                        $sizes = ['10px', '11px', '12px', '13px', '14px', '15px', '16px', '18px', '20px', '24px'];
                                        $current = $page['footer_btn_size'] ?? '13px';
                                        foreach ($sizes as $s): ?>
                                            <option value="<?= $s ?>" <?= $current === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar do Form -->
                    <div class="form-sidebar">
                        <div class="card">
                            <div class="card-header"><h2>Publicação</h2></div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="publish_date">Data de Publicação</label>
                                <input type="date" id="publish_date" name="publish_date"
                                    value="<?= e($page['publish_date'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="form-actions-sticky" style="display:flex;flex-direction:column;gap:8px;">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <?= $id ? 'Atualizar Página' : 'Criar Página' ?>
                                </button>
                                <button type="submit" class="btn btn-secondary btn-block"
                                    formaction="<?= BASE_URL ?>admin/preview.php" formtarget="_blank">
                                    👁️ Visualizar
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h2>Template</h2></div>
                            <div class="form-group">
                                <label for="template">Modelo da Página</label>
                                <select id="template" name="template" onchange="toggleTemplateFields()">
                                    <?php foreach ($templates as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= $currentTemplate === $key ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h2>Link de Afiliado</h2></div>
                            <div class="form-group">
                                <label for="affiliate_link">URL do Afiliado</label>
                                <input type="url" id="affiliate_link" name="affiliate_link"
                                    value="<?= e($page['affiliate_link'] ?? '') ?>" placeholder="https://...">
                            </div>
                            <div class="template-classic-fields" style="display:none;">
                                <div class="form-group">
                                    <label for="cta_text">Texto do Botão CTA</label>
                                    <input type="text" id="cta_text" name="cta_text"
                                        value="<?= e($page['cta_text'] ?? 'Saiba Mais') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="cta_color">Cor do Botão CTA</label>
                                    <input type="color" id="cta_color" name="cta_color"
                                        value="<?= e($page['cta_color'] ?? '#e85d04') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card template-classic-fields" style="display:none;">
                            <div class="card-header"><h2>Autor</h2></div>
                            <div class="form-group">
                                <label for="author_name">Nome do Autor</label>
                                <input type="text" id="author_name" name="author_name"
                                    value="<?= e($page['author_name'] ?? 'Redação') ?>">
                            </div>
                            <div class="form-group">
                                <label for="author_avatar">Avatar do Autor</label>
                                <input type="file" id="author_avatar" name="author_avatar" accept="image/*">
                                <?php if (!empty($page['author_avatar'])): ?>
                                    <img src="<?= BASE_URL . e($page['author_avatar']) ?>" style="max-width:60px;margin-top:8px;border-radius:50%;">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h2>SEO</h2></div>
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title"
                                    value="<?= e($page['meta_title'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" rows="3"><?= e($page['meta_description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h2>📊 Código de Rastreamento</h2></div>
                            <div class="form-group">
                                <label for="tracking_code">Tag (Google Ads, Analytics, Pixel, etc)</label>
                                <textarea id="tracking_code" name="tracking_code" rows="10" style="font-family:monospace;font-size:12px;"
                                    placeholder="Cole aqui o código completo da tag (incluindo &lt;script&gt;...&lt;/script&gt;)"><?= e($page['tracking_code'] ?? '') ?></textarea>
                                <small style="color:#888;display:block;margin-top:4px;">O código será inserido no &lt;head&gt; desta página.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script src="<?= BASE_URL ?>admin/assets/admin.js"></script>
    <script>
        // ===== Configuração de FONTES customizadas no Quill =====
        const FONTS = [
            'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana',
            'Courier New', 'Tahoma', 'Trebuchet MS', 'Impact',
            'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
            'Merriweather', 'Playfair Display', 'Oswald', 'Raleway', 'Source Sans 3'
        ];
        const FontStyle = Quill.import('attributors/style/font');
        FontStyle.whitelist = FONTS;
        Quill.register(FontStyle, true);

        // ===== COR e BACKGROUND como inline-style (em vez de classes) =====
        const ColorStyle = Quill.import('attributors/style/color');
        Quill.register(ColorStyle, true);
        const BackgroundStyle = Quill.import('attributors/style/background');
        Quill.register(BackgroundStyle, true);

        // ===== Configuração de TAMANHOS por px =====
        const SIZES = [
            '8px','9px','10px','11px','12px','13px','14px','15px','16px','17px','18px',
            '20px','22px','24px','26px','28px','32px','36px','40px','48px','56px','64px','72px','80px','96px'
        ];
        const SizeStyle = Quill.import('attributors/style/size');
        SizeStyle.whitelist = SIZES;
        Quill.register(SizeStyle, true);

        // Toolbar padrão (formatação rica)
        const richToolbar = [
            [{ 'font': FONTS }, { 'size': SIZES }],
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
        ];

        // Helper para criar Quill + sincronizar com textarea
        const editors = [];
        function initQuill(containerId, textareaId) {
            const editor = new Quill('#' + containerId, {
                theme: 'snow',
                modules: { toolbar: richToolbar }
            });

            // Limpar background-color de textos colados (vem branco de Word/Google Docs/sites)
            editor.clipboard.addMatcher(Node.ELEMENT_NODE, function(node, delta) {
                delta.ops.forEach(op => {
                    if (op.attributes && op.attributes.background) {
                        delete op.attributes.background;
                    }
                });
                return delta;
            });

            // Sobrescrever os botões de cor para aceitar HEX customizado via prompt
            const toolbar = editor.getModule('toolbar');
            toolbar.addHandler('color', function(value) {
                if (!value || value === 'custom') {
                    const hex = prompt('Digite a cor do texto (HEX, ex: #FF5733):', '#000000');
                    if (!hex) return;
                    if (!/^#?[0-9a-fA-F]{6}$/.test(hex.trim())) {
                        alert('Cor inválida. Use formato HEX de 6 caracteres (ex: #FF5733).');
                        return;
                    }
                    value = hex.trim().startsWith('#') ? hex.trim() : '#' + hex.trim();
                }
                editor.format('color', value);
            });
            toolbar.addHandler('background', function(value) {
                if (!value || value === 'custom') {
                    const hex = prompt('Digite a cor de fundo do texto (HEX, ex: #FFFF00):', '#FFFF00');
                    if (!hex) return;
                    if (!/^#?[0-9a-fA-F]{6}$/.test(hex.trim())) {
                        alert('Cor inválida. Use formato HEX de 6 caracteres (ex: #FFFF00).');
                        return;
                    }
                    value = hex.trim().startsWith('#') ? hex.trim() : '#' + hex.trim();
                }
                editor.format('background', value);
            });

            // Adicionar botões "Cor HEX" na toolbar
            addHexButtonsToToolbar(editor);

            const textarea = document.getElementById(textareaId);
            if (textarea && textarea.value) {
                editor.clipboard.dangerouslyPasteHTML(textarea.value);
            }
            editors.push({ editor, textarea });
            return editor;
        }

        function addHexButtonsToToolbar(editor) {
            const toolbarEl = editor.container.previousElementSibling;
            if (!toolbarEl) return;

            const makeBtn = (label, title, fmtKey, defaultColor) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ql-hex-btn';
                btn.innerHTML = label;
                btn.title = title;
                btn.addEventListener('click', () => {
                    const hex = prompt(title + ' (ex: ' + defaultColor + '):', defaultColor);
                    if (!hex) return;
                    const v = hex.trim();
                    if (!/^#?[0-9a-fA-F]{6}$/.test(v)) {
                        alert('Cor inválida. Use HEX de 6 caracteres (ex: ' + defaultColor + ').');
                        return;
                    }
                    const colorVal = v.startsWith('#') ? v : '#' + v;
                    editor.focus();
                    editor.format(fmtKey, colorVal);
                });
                return btn;
            };

            toolbarEl.appendChild(makeBtn('A<span style="color:#e94560">#</span>', 'Cor do texto (HEX)', 'color', '#000000'));
            toolbarEl.appendChild(makeBtn('🖌<span style="color:#e94560">#</span>', 'Cor de fundo do texto (HEX)', 'background', '#FFFF00'));
        }

        // Inicializar editores
        const quillContent = initQuill('editor-container', 'content');
        const quillHeader = initQuill('header-text-editor', 'header_text');
        const quillHeaderBelow = initQuill('header-text-below-editor', 'header_text_below');
        const quillContent1 = initQuill('content1-text-editor', 'content1_text');
        const quillContent2 = initQuill('content2-text-editor', 'content2_text');
        const quillFooter = initQuill('footer-alerts-editor', 'footer_alerts');

        // Sincronizar antes de enviar
        document.getElementById('page-form').addEventListener('submit', function() {
            editors.forEach(({ editor, textarea }) => {
                if (textarea) textarea.value = editor.root.innerHTML;
            });
        });

        // Alternar campos clássicos vs estruturado
        function toggleTemplateFields() {
            const tpl = document.getElementById('template').value;
            const isStruct = tpl === 'structured';
            document.querySelectorAll('.template-structured-fields').forEach(el => {
                el.style.display = isStruct ? '' : 'none';
            });
            document.querySelectorAll('.template-classic-fields').forEach(el => {
                el.style.display = isStruct ? 'none' : '';
            });
        }

        // Mostrar/ocultar direção do gradient
        function toggleGradientFields() {
            const type = document.getElementById('header_bg_type').value;
            document.getElementById('gradient-direction-group').style.display = type === 'linear' ? '' : 'none';
        }

        // ===== Hex input ao lado de cada color picker =====
        function isValidHex(v) {
            return /^#?[0-9a-fA-F]{6}$/.test(v);
        }
        function normalizeHex(v) {
            v = v.trim();
            if (!v.startsWith('#')) v = '#' + v;
            return v.toUpperCase();
        }
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            // Não duplicar se já foi processado
            if (colorInput.dataset.hexified === '1') return;
            colorInput.dataset.hexified = '1';

            // Cria wrapper e input de texto
            const wrapper = document.createElement('div');
            wrapper.className = 'color-with-hex';
            const hexInput = document.createElement('input');
            hexInput.type = 'text';
            hexInput.placeholder = '#FFFFFF';
            hexInput.value = (colorInput.value || '#000000').toUpperCase();
            hexInput.maxLength = 7;

            // Insere wrapper e move inputs
            colorInput.parentNode.insertBefore(wrapper, colorInput);
            wrapper.appendChild(colorInput);
            wrapper.appendChild(hexInput);

            // Sincronização
            colorInput.addEventListener('input', () => {
                hexInput.value = colorInput.value.toUpperCase();
            });
            hexInput.addEventListener('input', () => {
                if (isValidHex(hexInput.value)) {
                    colorInput.value = normalizeHex(hexInput.value);
                }
            });
            hexInput.addEventListener('blur', () => {
                if (isValidHex(hexInput.value)) {
                    hexInput.value = normalizeHex(hexInput.value);
                } else {
                    hexInput.value = colorInput.value.toUpperCase();
                }
            });
        });

        // Inicialização
        toggleTemplateFields();
        toggleGradientFields();
    </script>
</body>

</html>
