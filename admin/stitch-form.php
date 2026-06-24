<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stitches.php';
requireLogin();

$activePage = 'stitches';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$stitch = null;

if ($id) {
    $stitch = getStitchById($id);
    if (!$stitch) {
        header('Location: ' . BASE_URL . 'admin/stitches.php');
        exit;
    }
}

// Defaults pra criação
if (!$id) {
    $pdo = getDB();
    $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM crochet_stitches")->fetchColumn();
    $stitch = ['display_order' => $maxOrder + 1, 'is_active' => 1];
}

$pageTitle = $id ? 'Editar Ponto' : 'Novo Ponto';
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
            <a href="<?= BASE_URL ?>admin/stitches.php" class="btn btn-secondary">← Voltar</a>
        </header>

        <form method="POST" action="<?= BASE_URL ?>admin/stitch-save.php" class="stitch-form">
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
                                       value="<?= e($stitch['name_br'] ?? '') ?>"
                                       placeholder="Ex: Ponto Baixo">
                            </div>
                            <div class="form-group">
                                <label for="name_en">Nome (EN)</label>
                                <input type="text" id="name_en" name="name_en"
                                       value="<?= e($stitch['name_en'] ?? '') ?>"
                                       placeholder="Ex: Single Crochet">
                            </div>
                        </div>

                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label for="abbrev_br">Abreviação (PT) *</label>
                                <input type="text" id="abbrev_br" name="abbrev_br" required
                                       maxlength="20"
                                       value="<?= e($stitch['abbrev_br'] ?? '') ?>"
                                       placeholder="Ex: pb">
                            </div>
                            <div class="form-group">
                                <label for="abbrev_en">Abreviação (EN)</label>
                                <input type="text" id="abbrev_en" name="abbrev_en"
                                       maxlength="20"
                                       value="<?= e($stitch['abbrev_en'] ?? '') ?>"
                                       placeholder="Ex: sc">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug *</label>
                            <input type="text" id="slug" name="slug" required
                                   pattern="[a-z0-9\-]+"
                                   value="<?= e($stitch['slug'] ?? '') ?>"
                                   placeholder="ponto-baixo">
                            <small style="color:#888;">Apenas letras minúsculas, números e hífens.</small>
                        </div>
                    </div>

                    <!-- DESCRIÇÃO -->
                    <div class="card">
                        <div class="card-header"><h2>Descrição curta</h2></div>

                        <div class="form-group">
                            <label for="description_br">Descrição (PT)</label>
                            <textarea id="description_br" name="description_br" rows="3"
                                      placeholder="Ex: Ponto mais usado em amigurumi. Cria textura densa e firme."><?= e($stitch['description_br'] ?? '') ?></textarea>
                            <small style="color:#888;">1–2 linhas curtas. Aparece como tooltip ou em listagens.</small>
                        </div>

                        <div class="form-group">
                            <label for="description_en">Descrição (EN)</label>
                            <textarea id="description_en" name="description_en" rows="3"
                                      placeholder="Ex: Most used stitch in amigurumi..."><?= e($stitch['description_en'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- LINK PRO TUTORIAL -->
                    <div class="card">
                        <div class="card-header"><h2>Link pro Tutorial</h2></div>

                        <div class="form-group">
                            <label for="tutorial_anchor">Âncora no Guia de Pontos</label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">/br/croche/guia-de-pontos#</span>
                                <input type="text" id="tutorial_anchor" name="tutorial_anchor"
                                       pattern="[a-z0-9\-]+"
                                       value="<?= e($stitch['tutorial_anchor'] ?? '') ?>"
                                       placeholder="ponto-baixo">
                            </div>
                            <small style="color:#888;">
                                Quando alguém clicar no ponto numa receita, vai pra essa seção do guia de pontos.
                                Recomendado usar o mesmo valor do slug.
                            </small>
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
                                <option value="1" <?= ($stitch['is_active'] ?? 1) ? 'selected' : '' ?>>✅ Ativo</option>
                                <option value="0" <?= !($stitch['is_active'] ?? 1) ? 'selected' : '' ?>>👁️ Oculto</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Ordem</label>
                            <input type="number" id="display_order" name="display_order"
                                   min="1" max="99"
                                   value="<?= (int) ($stitch['display_order'] ?? 1) ?>">
                            <small style="color:#888;">Menor = aparece primeiro.</small>
                        </div>
                    </div>

                    <!-- ÍCONE -->
                    <div class="card">
                        <div class="card-header"><h2>Ícone</h2></div>

                        <div class="form-group">
                            <label for="image_url">Caminho do SVG/imagem</label>
                            <input type="text" id="image_url" name="image_url"
                                   value="<?= e($stitch['image_url'] ?? '') ?>"
                                   placeholder="/assets/img/icons/ponto-baixo.svg">
                            <small style="color:#888;">
                                Suba o arquivo SVG via cPanel em <code>/assets/img/icons/</code> e cole o caminho aqui.
                            </small>
                        </div>

                        <?php if (!empty($stitch['image_url'])): ?>
                            <div style="margin:0 20px 18px;padding:20px;background:#0f0f23;border:1px solid #2a2a4a;border-radius:8px;text-align:center;">
                                <small style="display:block;color:#888;margin-bottom:10px;">Preview:</small>
                                <div style="width:80px;height:80px;margin:0 auto;color:#e0e0e0;">
                                    <?= renderStitchIcon($stitch) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ★ FOTO DO PONTO (foto real, separada do ícone SVG) ★ -->
                    <div class="card">
                        <div class="card-header"><h2>Foto do ponto</h2></div>
                        <p style="padding: 0 20px 12px; font-size: 12px; color: #888;">
                            Imagem real do ponto feito em fio. Aparece no guia público
                            <a href="<?= BASE_URL ?>br/croche/guia-de-pontos" target="_blank" style="color:#e94560;">(/br/croche/guia-de-pontos)</a>.
                        </p>

                        <div class="form-group">
                            <!-- Hidden que vai pro DB -->
                            <input type="hidden" id="photo_url" name="photo_url"
                                   value="<?= e($stitch['photo_url'] ?? '') ?>">

                            <!-- Container que muda conforme estado (vazio ou com foto) -->
                            <div id="photo-upload-container">
                                <?php if (!empty($stitch['photo_url'])): ?>
                                    <!-- ESTADO: COM FOTO -->
                                    <div id="photo-preview-wrap">
                                        <div class="photo-preview-frame">
                                            <img id="photo-preview-img"
                                                 src="<?= e($stitch['photo_url']) ?>" alt="">
                                        </div>
                                        <div class="photo-preview-filename">
                                            <code id="photo-filename"><?= e(basename($stitch['photo_url'])) ?></code>
                                        </div>
                                        <div class="photo-preview-actions">
                                            <button type="button" id="btn-photo-replace" class="btn btn-secondary btn-sm">↻ Trocar</button>
                                            <button type="button" id="btn-photo-remove"  class="btn btn-delete btn-sm">🗑 Remover</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- ESTADO: VAZIO -->
                                    <div id="photo-upload-zone">
                                        <div class="photo-dropzone" onclick="document.getElementById('photo-input-file').click();">
                                            <div class="photo-dropzone__icon">⤴</div>
                                            <div class="photo-dropzone__title">Clique pra escolher imagem</div>
                                            <div class="photo-dropzone__hint">JPG ou PNG · max 10MB</div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- File input escondido (compartilhado) -->
                                <input type="file" id="photo-input-file"
                                       accept="image/jpeg,image/png"
                                       style="display:none;">

                                <div id="photo-loading" class="photo-loading" style="display:none;">⏳ Fazendo upload...</div>
                                <div id="photo-error"   class="photo-error"   style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <?= $id ? 'Atualizar' : 'Criar' ?> Ponto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<?php if (!$id): ?>
<script>
// Slug e anchor automáticos a partir do name_br (só pra novo)
(function () {
    const nameInput = document.getElementById('name_br');
    const slugInput = document.getElementById('slug');
    const anchorInput = document.getElementById('tutorial_anchor');
    let slugManual = !!slugInput.value;
    let anchorManual = !!anchorInput.value;

    slugInput.addEventListener('input', () => { slugManual = true; });
    anchorInput.addEventListener('input', () => { anchorManual = true; });

    nameInput.addEventListener('input', (e) => {
        const slugified = e.target.value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        if (!slugManual)   slugInput.value = slugified;
        if (!anchorManual) anchorInput.value = slugified;
    });
})();
</script>
<?php endif; ?>

<!-- Upload de foto do ponto (R-A3) — funciona em criação E edição -->
<script>
(function () {
    'use strict';

    const photoUrlInput = document.getElementById('photo_url');
    const slugInput     = document.getElementById('slug');
    const container     = document.getElementById('photo-upload-container');
    const csrfInput     = document.querySelector('input[name="csrf_token"]');

    if (!photoUrlInput || !container) return;

    function showError(msg) {
        const el = document.getElementById('photo-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }
    function hideError() {
        const el = document.getElementById('photo-error');
        if (el) el.style.display = 'none';
    }
    function setLoading(on) {
        const el = document.getElementById('photo-loading');
        if (el) el.style.display = on ? 'block' : 'none';
    }

    async function uploadPhoto(file, slug) {
        hideError();
        setLoading(true);

        const fd = new FormData();
        fd.append('image', file);
        fd.append('folder', 'stitches/photos');
        fd.append('filename_base', slug);
        fd.append('delete_old', photoUrlInput.value || '');
        fd.append('csrf_token', csrfInput ? csrfInput.value : '');

        try {
            const res = await fetch('<?= BASE_URL ?>admin/upload-image.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Falha no upload');
            photoUrlInput.value = data.url;
            renderWithPhoto(data.url);
        } catch (err) {
            showError('Erro no upload: ' + err.message);
        } finally {
            setLoading(false);
        }
    }

    async function removePhoto() {
        if (!confirm('Remover a foto deste ponto?')) return;
        hideError();
        setLoading(true);

        const oldUrl = photoUrlInput.value;
        try {
            if (oldUrl) {
                const fd = new FormData();
                fd.append('delete_url', oldUrl);
                fd.append('csrf_token', csrfInput ? csrfInput.value : '');
                await fetch('<?= BASE_URL ?>admin/delete-image.php', { method: 'POST', body: fd });
            }
            photoUrlInput.value = '';
            renderEmpty();
        } catch (err) {
            showError('Erro ao remover: ' + err.message);
        } finally {
            setLoading(false);
        }
    }

    function handleFileChange(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) return;

        const slug = (slugInput && slugInput.value || '').trim();
        if (!slug) {
            showError('Preencha o nome do ponto antes de subir a foto (precisa do slug).');
            e.target.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showError('Arquivo muito grande. Máximo 10 MB.');
            e.target.value = '';
            return;
        }
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            showError('Tipo inválido. Use JPG ou PNG.');
            e.target.value = '';
            return;
        }

        uploadPhoto(file, slug).finally(() => { e.target.value = ''; });
    }

    function bindFileInput() {
        const inp = document.getElementById('photo-input-file');
        if (inp) inp.addEventListener('change', handleFileChange);
    }

    function renderWithPhoto(url) {
        const filename = url.split('/').pop();
        container.innerHTML = `
            <div id="photo-preview-wrap">
                <div class="photo-preview-frame">
                    <img src="${url}" alt="">
                </div>
                <div class="photo-preview-filename"><code>${filename}</code></div>
                <div class="photo-preview-actions">
                    <button type="button" id="btn-photo-replace" class="btn btn-secondary btn-sm">↻ Trocar</button>
                    <button type="button" id="btn-photo-remove"  class="btn btn-delete btn-sm">🗑 Remover</button>
                </div>
            </div>
            <input type="file" id="photo-input-file" accept="image/jpeg,image/png" style="display:none;">
            <div id="photo-loading" class="photo-loading" style="display:none;">⏳ Fazendo upload...</div>
            <div id="photo-error"   class="photo-error"   style="display:none;"></div>
        `;
        bindFileInput();
    }

    function renderEmpty() {
        container.innerHTML = `
            <div id="photo-upload-zone">
                <div class="photo-dropzone" onclick="document.getElementById('photo-input-file').click();">
                    <div class="photo-dropzone__icon">⤴</div>
                    <div class="photo-dropzone__title">Clique pra escolher imagem</div>
                    <div class="photo-dropzone__hint">JPG ou PNG · max 10MB</div>
                </div>
            </div>
            <input type="file" id="photo-input-file" accept="image/jpeg,image/png" style="display:none;">
            <div id="photo-loading" class="photo-loading" style="display:none;">⏳ Fazendo upload...</div>
            <div id="photo-error"   class="photo-error"   style="display:none;"></div>
        `;
        bindFileInput();
    }

    // Inicialização: bind do file input + delegação dos botões trocar/remover
    bindFileInput();
    container.addEventListener('click', function (e) {
        if (e.target.closest('#btn-photo-replace')) {
            const inp = document.getElementById('photo-input-file');
            if (inp) inp.click();
        } else if (e.target.closest('#btn-photo-remove')) {
            removePhoto();
        }
    });
})();
</script>

</body>
</html>
