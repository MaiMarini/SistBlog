<?php
/**
 * ADMIN — FORMULÁRIO DE PÁGINA (versão transitória editorial)
 *
 * Esta é uma versão simplificada após a remoção do sistema presell.
 * Apenas os campos básicos da tabela `pages` (schema editorial).
 *
 * Será substituída por um sistema de blocos modulares na próxima fase.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/site-helpers.php';
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
                if (function_exists('clearCategoriesCache')) clearCategoriesCache();
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

if (!empty($_GET['saved'])) $success = true;

$categories = getAllCategories();
$pageTitle = $id ? 'Editar Página' : 'Nova Página';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <style>
        .ql-editor { min-height: 320px; font-family: Georgia, serif; font-size: 16px; line-height: 1.7; background: #fff; color: #333; }
        .ql-toolbar.ql-snow { background: #fff; border-color: #2a2a4a; border-radius: 8px 8px 0 0; }
        .ql-container.ql-snow { border-color: #2a2a4a; border-radius: 0 0 8px 8px; }
        .notice {
            background: rgba(255, 200, 100, 0.15);
            border: 1px solid rgba(255, 200, 100, 0.3);
            color: #f0c674;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1><?= e($pageTitle) ?></h1>
                <a href="<?= BASE_URL ?>admin/pages.php" class="btn btn-secondary">← Voltar</a>
            </header>

            <div class="notice">
                ⚠ <strong>Versão transitória.</strong> O sistema editorial completo (com blocos modulares) será adicionado em breve. Por enquanto, apenas os campos básicos estão disponíveis.
            </div>

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
                            <div class="card-header"><h2>Conteúdo</h2></div>

                            <div class="form-group">
                                <label for="title">Título *</label>
                                <input type="text" id="title" name="title" required
                                       value="<?= e($page['title'] ?? '') ?>"
                                       placeholder="Título do artigo ou página">
                            </div>

                            <div class="form-group">
                                <label for="slug">Slug (URL) *</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">/<?= e($page['language'] ?? 'br') ?>/</span>
                                    <input type="text" id="slug" name="slug"
                                           value="<?= e($page['slug'] ?? '') ?>"
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
                                <div id="editor-container"></div>
                                <textarea id="content" name="content" style="display:none"><?= e($page['content'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- IMAGEM DESTACADA -->
                        <div class="card">
                            <div class="card-header"><h2>Imagem Destacada</h2></div>
                            <div class="form-group">
                                <label for="featured_image">Upload</label>
                                <input type="file" id="featured_image" name="featured_image" accept="image/*">
                                <?php if (!empty($page['featured_image'])): ?>
                                    <div style="margin-top:10px;">
                                        <img src="<?= BASE_URL . e($page['featured_image']) ?>" style="max-width:200px;border-radius:8px;">
                                    </div>
                                    <input type="hidden" name="featured_image_existing" value="<?= e($page['featured_image']) ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-sidebar">

                        <!-- PUBLICAÇÃO -->
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
                                <label for="page_type">Tipo</label>
                                <select id="page_type" name="page_type">
                                    <option value="static" <?= ($page['page_type'] ?? 'static') === 'static' ? 'selected' : '' ?>>Estática</option>
                                    <option value="article" <?= ($page['page_type'] ?? '') === 'article' ? 'selected' : '' ?>>Artigo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="language">Idioma</label>
                                <select id="language" name="language">
                                    <option value="br" <?= ($page['language'] ?? 'br') === 'br' ? 'selected' : '' ?>>Português (br)</option>
                                    <option value="en" <?= ($page['language'] ?? '') === 'en' ? 'selected' : '' ?>>English (en)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="category">Categoria</label>
                                <select id="category" name="category">
                                    <option value="">— Sem categoria —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat['slug']) ?>" <?= ($page['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= e($cat['name_br']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="publish_date">Data de Publicação</label>
                                <input type="date" id="publish_date" name="publish_date"
                                       value="<?= e($page['publish_date'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="author_name">Autor</label>
                                <input type="text" id="author_name" name="author_name"
                                       value="<?= e($page['author_name'] ?? '') ?>"
                                       placeholder="Mai Marini">
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

                        <!-- SEO -->
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

                        <!-- TRACKING -->
                        <div class="card">
                            <div class="card-header"><h2>📊 Tracking</h2></div>
                            <div class="form-group">
                                <label for="tracking_code">Código adicional (só desta página)</label>
                                <textarea id="tracking_code" name="tracking_code" rows="6" style="font-family:monospace;font-size:12px;"
                                          placeholder="<script>...</script>"><?= e($page['tracking_code'] ?? '') ?></textarea>
                                <small style="color:#888;display:block;margin-top:4px;">Trackings globais ficam em <a href="<?= BASE_URL ?>admin/settings.php#tracking" style="color:#e94560;">Configurações</a>.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Conteúdo da página...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['blockquote', 'link', 'image'],
                    ['clean']
                ]
            }
        });

        const contentTextarea = document.getElementById('content');
        if (contentTextarea.value) {
            quill.clipboard.dangerouslyPasteHTML(contentTextarea.value);
        }

        document.getElementById('page-form').addEventListener('submit', function() {
            contentTextarea.value = quill.root.innerHTML;
        });

        // Slug a partir do título
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        let slugManual = !!slugInput.value;
        slugInput.addEventListener('input', () => { slugManual = true; });
        titleInput.addEventListener('input', () => {
            if (slugManual) return;
            slugInput.value = titleInput.value
                .toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        });
    </script>
</body>
</html>
