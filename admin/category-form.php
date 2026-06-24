<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$activePage = 'categories';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$category = null;

if ($id) {
    $category = getCategoryById($id);
    if (!$category) {
        header('Location: ' . BASE_URL . 'admin/categories.php');
        exit;
    }
}

// Defaults pra criação: próxima ordem disponível + ativa
if (!$id) {
    $pdo = getDB();
    $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();
    $category = ['display_order' => $maxOrder + 1, 'is_active' => 1, 'icon_type' => 'phosphor'];
}

$pageTitle = $id ? 'Editar Categoria' : 'Nova Categoria';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1><?= e($pageTitle) ?></h1>
            <a href="<?= BASE_URL ?>admin/categories.php" class="btn btn-secondary">← Voltar</a>
        </header>

        <form method="POST" action="<?= BASE_URL ?>admin/category-save.php" class="category-form">
            <?= csrfField() ?>
            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-main">

                    <!-- IDENTIDADE -->
                    <div class="card">
                        <div class="card-header"><h2>Identidade</h2></div>

                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label for="name_br">Nome (PT) *</label>
                                <input type="text" id="name_br" name="name_br" required
                                       value="<?= e($category['name_br'] ?? '') ?>"
                                       placeholder="Ex: Crochê">
                            </div>
                            <div class="form-group">
                                <label for="name_en">Nome (EN)</label>
                                <input type="text" id="name_en" name="name_en"
                                       value="<?= e($category['name_en'] ?? '') ?>"
                                       placeholder="Ex: Crocheting">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug (URL) *</label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">/br/</span>
                                <input type="text" id="slug" name="slug" required
                                       pattern="[a-z0-9\-]+"
                                       value="<?= e($category['slug'] ?? '') ?>"
                                       placeholder="croche">
                            </div>
                            <small style="color:#888;">Apenas letras minúsculas, números e hífens.</small>
                        </div>
                    </div>

                    <!-- DESCRIÇÃO -->
                    <div class="card">
                        <div class="card-header"><h2>Descrição</h2></div>

                        <div class="form-group">
                            <label for="description_br">Descrição (PT)</label>
                            <textarea id="description_br" name="description_br" rows="2"
                                      placeholder="Ex: Pontos, fios e projetos pra começar (e continuar)"><?= e($category['description_br'] ?? '') ?></textarea>
                            <small style="color:#888;">Aparece no topo da página da categoria.</small>
                        </div>

                        <div class="form-group">
                            <label for="description_en">Descrição (EN)</label>
                            <textarea id="description_en" name="description_en" rows="2"
                                      placeholder="Ex: Stitches, yarns and projects..."><?= e($category['description_en'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- CITAÇÃO -->
                    <div class="card">
                        <div class="card-header">
                            <h2>📖 Citação literária <span style="color:#888;font-size:13px;font-weight:400;">(opcional)</span></h2>
                        </div>

                        <p style="font-size:13px;color:#888;padding:0 20px 12px;">
                            Se preenchido, substitui a descrição como subtítulo da categoria. Ideal pra "Minha estante".
                        </p>

                        <div class="form-group">
                            <label for="quote_text_br">Trecho (PT)</label>
                            <textarea id="quote_text_br" name="quote_text_br" rows="3"
                                      placeholder='Ex: "Os livros não mudam o mundo, quem muda o mundo são as pessoas."'><?= e($category['quote_text_br'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="quote_author_br">Autor / Fonte (PT)</label>
                            <input type="text" id="quote_author_br" name="quote_author_br"
                                   value="<?= e($category['quote_author_br'] ?? '') ?>"
                                   placeholder="Ex: Mário Quintana">
                        </div>

                        <div class="form-group">
                            <label for="quote_text_en">Trecho (EN)</label>
                            <textarea id="quote_text_en" name="quote_text_en" rows="3"
                                      placeholder="Ex: Books don't change the world..."><?= e($category['quote_text_en'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="quote_author_en">Autor / Fonte (EN)</label>
                            <input type="text" id="quote_author_en" name="quote_author_en"
                                   value="<?= e($category['quote_author_en'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-sidebar">

                    <!-- CONFIGURAÇÕES -->
                    <div class="card">
                        <div class="card-header"><h2>Configurações</h2></div>

                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select id="is_active" name="is_active">
                                <option value="1" <?= ($category['is_active'] ?? 1) ? 'selected' : '' ?>>✅ Ativa</option>
                                <option value="0" <?= !($category['is_active'] ?? 1) ? 'selected' : '' ?>>👁️ Oculta</option>
                            </select>
                            <small style="color:#888;">Ocultas não aparecem no drawer público.</small>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Ordem de exibição</label>
                            <input type="number" id="display_order" name="display_order"
                                   min="1" max="99"
                                   value="<?= (int) ($category['display_order'] ?? 1) ?>">
                            <small style="color:#888;">Menor = aparece primeiro.</small>
                        </div>
                    </div>

                    <!-- ÍCONE -->
                    <div class="card">
                        <div class="card-header"><h2>Ícone</h2></div>

                        <div class="form-group">
                            <label for="icon_type">Tipo</label>
                            <select id="icon_type" name="icon_type">
                                <option value="phosphor" <?= ($category['icon_type'] ?? 'phosphor') === 'phosphor' ? 'selected' : '' ?>>Phosphor (font icon)</option>
                                <option value="svg" <?= ($category['icon_type'] ?? '') === 'svg' ? 'selected' : '' ?>>SVG customizado (arquivo)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon_value">Valor *</label>
                            <input type="text" id="icon_value" name="icon_value" required
                                   value="<?= e($category['icon_value'] ?? '') ?>"
                                   placeholder="ph-light ph-books">
                            <small id="icon_help" style="display:block;margin-top:6px;color:#888;">
                                <span class="help-phosphor">Ex: <code>ph-light ph-books</code></span>
                                <span class="help-svg" style="display:none;">
                                    Ex: <code>/assets/img/icons/croche.svg</code> — suba o SVG via cPanel.
                                </span>
                            </small>
                        </div>

                        <?php if (!empty($category['icon_value'])): ?>
                            <div style="margin:0 20px 18px;padding:16px;background:#0f0f23;border:1px solid #2a2a4a;border-radius:8px;text-align:center;">
                                <small style="display:block;color:#888;margin-bottom:8px;">Preview atual:</small>
                                <div style="font-size:40px;color:#e0e0e0;line-height:1;">
                                    <?= renderCategoryIcon($category) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- CORES DA CATEGORIA -->
                    <?php
                        $defaultBg = '#E8DCD3';
                        $defaultText = '#23314A';
                        $catBg = $category['color_bg'] ?? $defaultBg;
                        $catText = $category['color_text'] ?? $defaultText;
                    ?>
                    <div class="card">
                        <div class="card-header"><h2>Cores da categoria</h2></div>

                        <p style="padding:0 20px 12px;font-size:12px;color:#888;">
                            Cores usadas na pill que aparece nos cards do site público.
                        </p>

                        <div class="form-group">
                            <label for="color_bg">Cor de fundo (pastel)</label>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <input type="color" id="color_bg" name="color_bg"
                                       value="<?= e($catBg) ?>"
                                       style="width:60px;height:40px;border:1px solid #444;border-radius:4px;cursor:pointer;padding:2px;">
                                <input type="text" id="color_bg_text"
                                       value="<?= e($catBg) ?>"
                                       pattern="^#[0-9A-Fa-f]{6}$"
                                       maxlength="7"
                                       style="flex:1;font-family:monospace;">
                            </div>
                            <small style="color:#888;">Tom claro/pastel pra fundo da pill.</small>
                        </div>

                        <div class="form-group">
                            <label for="color_text">Cor do texto (escuro)</label>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <input type="color" id="color_text" name="color_text"
                                       value="<?= e($catText) ?>"
                                       style="width:60px;height:40px;border:1px solid #444;border-radius:4px;cursor:pointer;padding:2px;">
                                <input type="text" id="color_text_text"
                                       value="<?= e($catText) ?>"
                                       pattern="^#[0-9A-Fa-f]{6}$"
                                       maxlength="7"
                                       style="flex:1;font-family:monospace;">
                            </div>
                            <small style="color:#888;">Tom escuro pro texto sobre o fundo.</small>
                        </div>

                        <div class="form-group">
                            <label>Preview</label>
                            <div style="padding:20px;background:#fff;border-radius:6px;border:1px solid #eee;text-align:center;">
                                <span id="pill-preview" style="
                                    display:inline-block;
                                    padding:4px 12px;
                                    border-radius:5px;
                                    font-size:11px;
                                    font-weight:600;
                                    text-transform:uppercase;
                                    letter-spacing:0.8px;
                                    background:<?= e($catBg) ?>;
                                    color:<?= e($catText) ?>;
                                "><?= e($category['name_br'] ?? 'Categoria') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <?= $id ? 'Atualizar' : 'Criar' ?> Categoria
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
(function () {
    const iconTypeEl = document.getElementById('icon_type');
    const iconInput  = document.getElementById('icon_value');
    const helpPh     = document.querySelector('.help-phosphor');
    const helpSvg    = document.querySelector('.help-svg');

    function toggleIconHelp() {
        const type = iconTypeEl.value;
        helpPh.style.display  = type === 'phosphor' ? '' : 'none';
        helpSvg.style.display = type === 'svg' ? '' : 'none';
        iconInput.placeholder = type === 'phosphor'
            ? 'ph-light ph-books'
            : '/assets/img/icons/exemplo.svg';
    }
    iconTypeEl.addEventListener('change', toggleIconHelp);
    toggleIconHelp();

<?php if (!$id): ?>
    // Slug auto a partir do name_br (só pra novas categorias)
    const nameInput = document.getElementById('name_br');
    const slugInput = document.getElementById('slug');
    let slugManual = !!slugInput.value;

    slugInput.addEventListener('input', () => { slugManual = true; });

    nameInput.addEventListener('input', (e) => {
        if (slugManual) return;
        slugInput.value = e.target.value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });
<?php endif; ?>

    // Sincroniza color pickers ↔ text inputs + preview ao vivo da pill
    const colorBg       = document.getElementById('color_bg');
    const colorBgText   = document.getElementById('color_bg_text');
    const colorText     = document.getElementById('color_text');
    const colorTextText = document.getElementById('color_text_text');
    const preview       = document.getElementById('pill-preview');
    const nameBr        = document.getElementById('name_br');

    if (preview && colorBg && colorText) {
        const hexRe = /^#[0-9A-Fa-f]{6}$/;

        function updatePreview() {
            preview.style.background = colorBg.value;
            preview.style.color = colorText.value;
            if (nameBr) preview.textContent = nameBr.value || 'Categoria';
        }

        colorBg.addEventListener('input', () => {
            colorBgText.value = colorBg.value.toUpperCase();
            updatePreview();
        });
        colorText.addEventListener('input', () => {
            colorTextText.value = colorText.value.toUpperCase();
            updatePreview();
        });

        colorBgText.addEventListener('input', () => {
            if (hexRe.test(colorBgText.value)) {
                colorBg.value = colorBgText.value;
                updatePreview();
            }
        });
        colorTextText.addEventListener('input', () => {
            if (hexRe.test(colorTextText.value)) {
                colorText.value = colorTextText.value;
                updatePreview();
            }
        });

        if (nameBr) nameBr.addEventListener('input', updatePreview);
    }
})();
</script>
</body>
</html>
