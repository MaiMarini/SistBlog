<?php
/**
 * ADMIN — FORMULÁRIO DE PÁGINA
 *
 * Editor TinyMCE 6 + toolbar de blocos editoriais modulares
 * (citação, dica, atenção, galeria, produto afiliado, etc.).
 * Os blocos são injetados como HTML estruturado e renderizados
 * pelo site público via assets/css/site.css.
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
    <script src="https://cdn.tiny.cloud/1/dkiaecki9zwrxev9ntxmp9vz6e5yj6yc1mzdb21kxoxrazl5/tinymce/6/tinymce.min.js"
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

                                <!-- Toolbar de blocos editoriais -->
                                <div class="editorial-blocks-toolbar" id="editorial-blocks-toolbar">
                                    <span class="toolbar-label">Inserir bloco:</span>
                                    <button type="button" class="editorial-btn" data-block="image"     title="Imagem com legenda">🖼 Imagem</button>
                                    <button type="button" class="editorial-btn" data-block="quote"     title="Citação destacada">❝ Citação</button>
                                    <button type="button" class="editorial-btn" data-block="tip"       title="Caixa de dica (rosa)">💡 Dica</button>
                                    <button type="button" class="editorial-btn" data-block="warning"   title="Caixa de atenção (amarela)">⚠ Atenção</button>
                                    <button type="button" class="editorial-btn" data-block="list"      title="Lista com bullets">≡ Lista</button>
                                    <button type="button" class="editorial-btn" data-block="gallery"   title="Galeria de 3 imagens">🖼🖼 Galeria</button>
                                    <button type="button" class="editorial-btn" data-block="product"   title="Produto afiliado">🛒 Produto</button>
                                    <button type="button" class="editorial-btn" data-block="highlight" title="Frase em destaque grande">★ Destaque</button>
                                    <button type="button" class="editorial-btn" data-block="divider"   title="Divisor decorativo">— Divisor</button>
                                    <button type="button" class="editorial-btn" data-block="table"     title="Tabela editorial">⊞ Tabela</button>
                                </div>

                                <textarea id="content" name="content"><?= e($page['content'] ?? '') ?></textarea>
                                <small id="reading-time-display" style="color:#888;display:block;margin-top:6px;"></small>
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
                                    <option value="article" <?= ($page['page_type'] ?? 'article') === 'article' ? 'selected' : '' ?>>📝 Artigo</option>
                                    <option value="static" <?= ($page['page_type'] ?? '') === 'static' ? 'selected' : '' ?>>📄 Estática</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="language">Idioma</label>
                                <select id="language" name="language">
                                    <option value="br" <?= ($page['language'] ?? 'br') === 'br' ? 'selected' : '' ?>>Português (br)</option>
                                    <option value="en" <?= ($page['language'] ?? '') === 'en' ? 'selected' : '' ?>>English (en)</option>
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

    <script src="<?= BASE_URL ?>admin/assets/page-form.js"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 600,
            menubar: false,
            language: 'pt_BR',
            language_url: 'https://cdn.tiny.cloud/1/dkiaecki9zwrxev9ntxmp9vz6e5yj6yc1mzdb21kxoxrazl5/tinymce/6/langs/pt_BR.js',

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
            setup: function(editor) {
                editor.on('input change', debounce(function() {
                    if (typeof window.updateReadingTime === 'function') {
                        window.updateReadingTime(editor.getContent({ format: 'text' }));
                    }
                }, 500));
            },

            // Carregar conteúdo existente ao iniciar (caso seja preciso via window.initialContent)
            init_instance_callback: function(editor) {
                if (typeof window.initialContent !== 'undefined') {
                    editor.setContent(window.initialContent);
                }
            }
        });

        // Debounce utility (necessário pelo setup acima)
        function debounce(fn, delay) {
            let timer;
            return function() {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, arguments), delay);
            };
        }
    </script>
</body>
</html>
