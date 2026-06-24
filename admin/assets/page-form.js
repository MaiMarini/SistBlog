/**
 * KALLME ADMIN — page-form.js
 *
 * Responsabilidades:
 *  1) Alternar campos visíveis conforme page_type (article vs static)
 *  2) Atualizar dinamicamente o prefixo do slug (/br/, /br/croche/, ...)
 *  3) Slug automático a partir do título (com flag manual)
 *  4) Tempo de leitura calculado em tempo real (display)
 *  5) Inserção de blocos editoriais no TinyMCE
 */
(function () {
    'use strict';

    // ============================================================
    // 1) ALTERNAR CAMPOS BASEADO EM page_type (article | recipe | static)
    // ============================================================
    function togglePageTypeFields() {
        const pageTypeEl = document.getElementById('page_type');
        if (!pageTypeEl) return;

        const pageType = pageTypeEl.value;

        // Article-only / shared
        const categoryGroup   = document.getElementById('category-group');
        const categorySelect  = document.getElementById('category');
        const categoryHint    = document.getElementById('category-hint');
        const heroStyleGroup  = document.getElementById('hero-style-group');
        const heroStyleSelect = document.getElementById('template');

        // Recipe-only
        const recipeFields    = document.getElementById('recipe-fields');
        const recipeStitches  = document.getElementById('recipe-stitches-block');
        const recipeSteps     = document.getElementById('recipe-steps-block');
        const recipeTips      = document.getElementById('recipe-tips-block');
        const recipePhotos    = document.getElementById('recipe-step-photos-block');
        const recipeColors    = document.getElementById('recipe-color-guide-block');
        const recipeStitchInline = document.getElementById('recipe-stitch-inline-block');
        const recipeNotes        = document.getElementById('recipe-notes-block');
        const difficultySelect = document.getElementById('difficulty');

        const clearRequired = (el) => { if (el) el.removeAttribute('required'); };

        if (pageType === 'article') {
            // Categoria obrigatória, hero visível, receita escondida
            if (categoryGroup)   categoryGroup.style.display = '';
            if (categorySelect)  categorySelect.setAttribute('required', 'required');
            if (categoryHint)    categoryHint.style.display = 'none';
            if (heroStyleGroup)  heroStyleGroup.style.display = '';

            if (recipeFields)    recipeFields.style.display = 'none';
            if (recipeStitches)  recipeStitches.style.display = 'none';
            if (recipeSteps)     recipeSteps.style.display = 'none';
            if (recipeTips)      recipeTips.style.display = 'none';
            if (recipePhotos)    recipePhotos.style.display = 'none';
            if (recipeColors)    recipeColors.style.display = 'none';
            if (recipeStitchInline) recipeStitchInline.style.display = 'none';
            if (recipeNotes)     recipeNotes.style.display = 'none';
            clearRequired(difficultySelect);

        } else if (pageType === 'recipe') {
            // Categoria obrigatória, hero NÃO se aplica, receita aparece
            if (categoryGroup)   categoryGroup.style.display = '';
            if (categorySelect)  categorySelect.setAttribute('required', 'required');
            if (categoryHint)    categoryHint.style.display = 'none';
            if (heroStyleGroup)  heroStyleGroup.style.display = 'none';
            if (heroStyleSelect) heroStyleSelect.value = '';

            if (recipeFields)    recipeFields.style.display = '';
            if (recipeStitches)  recipeStitches.style.display = '';
            if (recipeSteps)     recipeSteps.style.display = '';
            if (recipeTips)      recipeTips.style.display = '';
            if (recipePhotos)    recipePhotos.style.display = '';
            if (recipeColors)    recipeColors.style.display = '';
            if (recipeStitchInline) recipeStitchInline.style.display = '';
            if (recipeNotes)     recipeNotes.style.display = '';
            if (difficultySelect) difficultySelect.setAttribute('required', 'required');

        } else {
            // Static: categoria zerada, hero escondido, receita escondida
            if (categorySelect) {
                categorySelect.value = '';
                clearRequired(categorySelect);
            }
            if (categoryHint)    categoryHint.style.display = 'block';
            if (heroStyleGroup)  heroStyleGroup.style.display = 'none';
            if (heroStyleSelect) heroStyleSelect.value = '';

            if (recipeFields)    recipeFields.style.display = 'none';
            if (recipeStitches)  recipeStitches.style.display = 'none';
            if (recipeSteps)     recipeSteps.style.display = 'none';
            if (recipeTips)      recipeTips.style.display = 'none';
            if (recipePhotos)    recipePhotos.style.display = 'none';
            if (recipeColors)    recipeColors.style.display = 'none';
            if (recipeStitchInline) recipeStitchInline.style.display = 'none';
            if (recipeNotes)     recipeNotes.style.display = 'none';
            clearRequired(difficultySelect);
        }

        updateSlugPrefix();
    }

    // ============================================================
    // 2) ATUALIZAR PREFIXO DO SLUG DINAMICAMENTE
    // ============================================================
    function updateSlugPrefix() {
        const slugPrefix = document.querySelector('.input-prefix');
        if (!slugPrefix) return;

        const pageType = document.getElementById('page_type')?.value || 'article';
        const lang = document.getElementById('language')?.value || 'br';
        const category = document.getElementById('category')?.value || '';

        if (pageType === 'article' && category) {
            slugPrefix.textContent = '/' + lang + '/' + category + '/';
        } else {
            slugPrefix.textContent = '/' + lang + '/';
        }
    }

    // ============================================================
    // 3) SLUG AUTOMÁTICO A PARTIR DO TÍTULO
    // ============================================================
    function slugify(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput) {
        let slugManuallyEdited = !!slugInput.value;

        slugInput.addEventListener('input', function () {
            slugManuallyEdited = true;
        });

        titleInput.addEventListener('input', function (e) {
            if (!slugManuallyEdited) {
                slugInput.value = slugify(e.target.value);
            }
        });
    }

    // ============================================================
    // 4) TEMPO DE LEITURA (display em tempo real)
    // ============================================================
    window.updateReadingTime = function (text) {
        const words = text.trim().split(/\s+/).filter(function (w) { return w.length > 0; }).length;
        const minutes = Math.max(1, Math.ceil(words / 200));

        const display = document.getElementById('reading-time-display');
        if (display) {
            display.textContent = 'Tempo estimado: ' + minutes + ' min (' + words + ' palavras)';
        }
        // Não preenche o input automaticamente — o backend recalcula no submit
        // se o campo vier vazio (ver buildPageDataFromPost).
    };

    // ============================================================
    // 5) BOTÕES DE BLOCOS EDITORIAIS
    // ============================================================
    const blockTemplates = {
        image: `<figure class="editorial-image">
  <img src="/assets/img/articles/exemplo.jpg" alt="Descrição">
  <figcaption>Legenda da imagem</figcaption>
</figure>`,

        quote: `<blockquote class="editorial-quote">
  <p>Texto da citação aqui.</p>
  <cite>— Fonte da citação</cite>
</blockquote>`,

        tip: `<aside class="editorial-tip">
  <strong>💡 Dica</strong>
  <p>Texto da dica aqui.</p>
</aside>`,

        warning: `<aside class="editorial-warning">
  <strong>⚠ Atenção</strong>
  <p>Texto do aviso aqui.</p>
</aside>`,

        list: `<ul class="editorial-list">
  <li>Primeiro item</li>
  <li>Segundo item</li>
  <li>Terceiro item</li>
</ul>`,

        gallery: `<div class="editorial-gallery">
  <figure><img src="/assets/img/articles/img1.jpg" alt=""><figcaption>Legenda 1</figcaption></figure>
  <figure><img src="/assets/img/articles/img2.jpg" alt=""><figcaption>Legenda 2</figcaption></figure>
  <figure><img src="/assets/img/articles/img3.jpg" alt=""><figcaption>Legenda 3</figcaption></figure>
</div>`,

        product: `<div class="editorial-product">
  <div class="editorial-product__image"><img src="/assets/img/products/exemplo.jpg" alt=""></div>
  <div class="editorial-product__content">
    <h4>Nome do produto</h4>
    <p>Por que esse produto vale a pena.</p>
    <p class="editorial-product__price">R$ XX,XX</p>
    <a href="LINK_AFILIADO" class="editorial-product__cta" target="_blank" rel="sponsored noopener">Ver na loja →</a>
  </div>
</div>`,

        highlight: `<p class="editorial-highlight">Frase de destaque aqui.</p>`,

        divider: `<hr class="editorial-divider">`,

        table: `<table class="editorial-table">
  <thead>
    <tr><th>Coluna 1</th><th>Coluna 2</th></tr>
  </thead>
  <tbody>
    <tr><td>Linha 1, Col 1</td><td>Linha 1, Col 2</td></tr>
    <tr><td>Linha 2, Col 1</td><td>Linha 2, Col 2</td></tr>
  </tbody>
</table>`
    };

    document.querySelectorAll('.editorial-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const blockType = btn.dataset.block;
            const html = blockTemplates[blockType];
            if (html && typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                tinymce.activeEditor.insertContent(html);
                tinymce.activeEditor.insertContent('<p></p>');
            }
        });
    });

    // ============================================================
    // BINDS
    // ============================================================
    const pageTypeSelect = document.getElementById('page_type');
    const languageSelect = document.getElementById('language');
    const categorySelect = document.getElementById('category');

    if (pageTypeSelect) pageTypeSelect.addEventListener('change', togglePageTypeFields);
    if (languageSelect) languageSelect.addEventListener('change', updateSlugPrefix);
    if (categorySelect) categorySelect.addEventListener('change', updateSlugPrefix);

    // ============================================================
    // 6) ORDENAÇÃO DE PONTOS NA RECEITA (setas ↑ ↓ + feedback visual)
    // ============================================================
    const stitchesList = document.getElementById('stitches-list');
    if (stitchesList) {
        stitchesList.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;

            const item = btn.closest('.stitch-item');
            if (!item) return;

            if (btn.classList.contains('btn-move-up')) {
                const prev = item.previousElementSibling;
                if (prev) stitchesList.insertBefore(item, prev);
            } else if (btn.classList.contains('btn-move-down')) {
                const next = item.nextElementSibling;
                if (next) stitchesList.insertBefore(next, item);
            }
        });

        // Visual feedback ao (des)marcar checkbox
        stitchesList.addEventListener('change', function (e) {
            const cb = e.target.closest('input[type="checkbox"]');
            if (!cb) return;
            const item = cb.closest('.stitch-item');
            if (item) item.classList.toggle('is-checked', cb.checked);
        });
    }

    // ============================================================
    // 7) BLOCOS EDITORIAIS DE RECEITA — cards colapsáveis (steps + tips)
    // ============================================================
    const stepsList = document.getElementById('steps-sections-list');
    const tipsList  = document.getElementById('tips-list');

    if (stepsList || tipsList) {
        // 7a) Toggle expand/collapse no clique do header (mas não nos botões de controle)
        document.addEventListener('click', function (e) {
            const header = e.target.closest('.collapse-card__header');
            if (!header) return;
            if (e.target.closest('.collapse-card__controls')) return;
            const card = header.closest('.collapse-card');
            if (card) card.classList.toggle('open');
        });

        // 7b) Botões mover ↑↓ e remover ×
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            const card = btn.closest('.collapse-card');
            if (!card) return;
            const list = card.parentElement;

            if (btn.classList.contains('btn-move-up')) {
                const prev = card.previousElementSibling;
                if (prev) list.insertBefore(card, prev);
            } else if (btn.classList.contains('btn-move-down')) {
                const next = card.nextElementSibling;
                if (next) list.insertBefore(next, card);
            } else if (btn.classList.contains('btn-remove')) {
                if (confirm('Remover este bloco?')) card.remove();
            }
        });

        // 7c) Atualiza preview do nome da seção, contagem de linhas e prévia da dica
        document.addEventListener('input', function (e) {
            const card = e.target.closest('.collapse-card');
            if (!card) return;

            if (e.target.classList.contains('section-name-input')) {
                const preview = card.querySelector('.section-name-preview');
                if (preview) preview.textContent = e.target.value || '(sem nome)';
            }

            if (e.target.classList.contains('steps-content-input')) {
                const counter = card.querySelector('.line-count');
                if (counter) {
                    const lines = e.target.value.split('\n').filter((l) => l.trim() !== '').length;
                    counter.textContent = `— ${lines} linha(s)`;
                }
            }

            if (e.target.classList.contains('tip-text-input')) {
                const preview = card.querySelector('.line-count');
                if (preview) {
                    const txt = e.target.value || '(vazio)';
                    preview.textContent = '— ' + txt.substring(0, 50) + (txt.length > 50 ? '…' : '');
                }
            }
        });

        // 7d) Mudança no select de tipo da dica
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('tip-type-input')) return;
            const card = e.target.closest('.collapse-card');
            if (!card) return;
            const labels = { 'tip': '💡 Dica', 'alert': '⚠️ Alerta', 'important': '❗ Importante' };
            const preview = card.querySelector('.tip-type-preview');
            if (preview) preview.textContent = labels[e.target.value] || '';
        });

        // 7e) Add seção de passos
        const addStepsBtn = document.getElementById('add-steps-section');
        if (addStepsBtn) {
            addStepsBtn.addEventListener('click', function () {
                const list = document.getElementById('steps-sections-list');
                const idx = list.querySelectorAll('.collapse-card').length + 1;
                list.insertAdjacentHTML('beforeend', `
<div class="collapse-card open" data-block-type="steps">
    <div class="collapse-card__header">
        <span class="collapse-card__title">
            <span class="chevron">▼</span>
            <strong>Seção ${idx}:</strong>
            <span class="section-name-preview">(sem nome)</span>
            <span class="line-count">— 0 linha(s)</span>
        </span>
        <span class="collapse-card__controls">
            <button type="button" class="btn-move-up" title="Subir">↑</button>
            <button type="button" class="btn-move-down" title="Descer">↓</button>
            <button type="button" class="btn-remove" title="Remover seção">×</button>
        </span>
    </div>
    <div class="collapse-card__body">
        <div class="form-group">
            <label>Nome da seção</label>
            <input type="text" name="steps_section[]" placeholder="Ex: Corpo (vermelho/rosa)" class="section-name-input">
        </div>
        <div class="form-group">
            <label>Linhas da receita (1 por linha)</label>
            <textarea name="steps_content[]" rows="6" placeholder="R1: 6 pb no anel mágico (6)" class="steps-content-input"></textarea>
        </div>
        <div class="form-group">
            <label>🖼️ Foto da seção (opcional)</label>
            <div class="photo-upload-wrapper">
                <div class="photo-preview"><span class="photo-placeholder">Sem foto</span></div>
                <div class="photo-controls">
                    <input type="hidden" name="steps_photo[]" value="" class="photo-url">
                    <label class="btn btn-secondary btn-photo-pick">
                        Escolher foto
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="photo-file-input" style="display:none;">
                    </label>
                    <button type="button" class="btn btn-danger btn-photo-remove" style="display:none;">Remover</button>
                    <span class="photo-status"></span>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Legenda da foto (opcional)</label>
            <input type="text" name="steps_photo_caption[]" value="" placeholder="Ex: Após R5">
        </div>
    </div>
</div>`);
            });
        }

        // 7f) Add dica/alerta/importante
        const addTipBtn = document.getElementById('add-tip');
        if (addTipBtn) {
            addTipBtn.addEventListener('click', function () {
                const list = document.getElementById('tips-list');
                list.insertAdjacentHTML('beforeend', `
<div class="collapse-card open" data-block-type="tip">
    <div class="collapse-card__header">
        <span class="collapse-card__title">
            <span class="chevron">▼</span>
            <span class="tip-type-preview">💡 Dica</span>
            <span class="line-count">— (vazio)</span>
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
                <option value="tip">💡 Dica (sage)</option>
                <option value="alert">⚠️ Alerta (honey)</option>
                <option value="important">❗ Importante (rose)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Texto</label>
            <textarea name="tip_text[]" rows="3" placeholder="Ex: Use marcador de pontos pra não perder a conta." class="tip-text-input"></textarea>
        </div>
    </div>
</div>`);
            });
        }
    }

    // Estado inicial ao carregar
    togglePageTypeFields();
})();

// ============================================================
// 8) UPLOAD AJAX DE IMAGENS (foto da seção + foto da galeria step_photos)
// ============================================================
(function () {
    'use strict';

    async function uploadImage(file, statusEl) {
        if (statusEl) statusEl.textContent = 'Enviando…';

        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const slugInput = document.getElementById('slug');

        const fd = new FormData();
        fd.append('file', file);
        fd.append('csrf_token', csrfInput ? csrfInput.value : '');
        fd.append('recipe_slug', slugInput ? slugInput.value : '');

        try {
            const res = await fetch('upload-image.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data && data.success) {
                if (statusEl) {
                    statusEl.textContent = '✅ Enviado';
                    setTimeout(() => { statusEl.textContent = ''; }, 2000);
                }
                return data.url;
            }
            if (statusEl) statusEl.textContent = '❌ ' + (data.error || 'Erro no upload');
            return null;
        } catch (err) {
            if (statusEl) statusEl.textContent = '❌ Erro de rede';
            console.error(err);
            return null;
        }
    }

    // Change em qualquer .photo-file-input → upload AJAX + update preview/hidden
    document.addEventListener('change', async function (e) {
        if (!e.target.classList || !e.target.classList.contains('photo-file-input')) return;

        const fileInput = e.target;
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;

        const wrapper  = fileInput.closest('.photo-upload-wrapper, .step-photo-item');
        if (!wrapper) return;

        const statusEl  = wrapper.querySelector('.photo-status');
        const previewEl = wrapper.querySelector('.photo-preview, .step-photo-preview');
        const urlInput  = wrapper.querySelector('.photo-url, .step-photo-url');
        const removeBtn = wrapper.querySelector('.btn-photo-remove');

        const url = await uploadImage(file, statusEl);
        if (url && previewEl && urlInput) {
            previewEl.innerHTML = '<img src="' + url + '" alt="">';
            previewEl.classList.add('has-photo');
            urlInput.value = url;
            if (removeBtn) removeBtn.style.display = '';
        }

        fileInput.value = ''; // permite re-selecionar o mesmo arquivo
    });

    // Botão "Remover" da foto da seção (apenas .photo-upload-wrapper, NÃO step-photo-item — esse é tratado em outro lugar)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.photo-upload-wrapper .btn-photo-remove');
        if (!btn) return;
        if (!confirm('Remover esta foto?')) return;

        const wrapper   = btn.closest('.photo-upload-wrapper');
        const previewEl = wrapper.querySelector('.photo-preview');
        const urlInput  = wrapper.querySelector('.photo-url');

        if (previewEl) {
            previewEl.innerHTML = '<span class="photo-placeholder">Sem foto</span>';
            previewEl.classList.remove('has-photo');
        }
        if (urlInput) urlInput.value = '';
        btn.style.display = 'none';
    });
})();

// ============================================================
// 9) STEP PHOTOS — galeria final (add / remove / reorder + renumera)
// ============================================================
(function () {
    'use strict';

    const list = document.getElementById('step-photos-list');
    const addBtn = document.getElementById('add-step-photo');
    if (!list && !addBtn) return;

    function renumber() {
        if (!list) return;
        list.querySelectorAll('.step-photo-item').forEach(function (item, idx) {
            const num = item.querySelector('.step-photo-number');
            if (num) num.textContent = idx + 1;
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const idx = list.querySelectorAll('.step-photo-item').length;
            list.insertAdjacentHTML('beforeend', `
<div class="step-photo-item" data-index="${idx}">
    <div class="step-photo-number">${idx + 1}</div>
    <div class="step-photo-preview"><span class="photo-placeholder">Sem foto</span></div>
    <input type="hidden" name="step_photos_url[]" value="" class="step-photo-url">
    <input type="text" name="step_photos_caption[]" value="" placeholder="Legenda (ex: Anel mágico)" class="step-photo-caption">
    <div class="step-photo-controls">
        <label class="btn-icon btn-photo-pick" title="Trocar foto">
            📷
            <input type="file" accept="image/jpeg,image/png,image/webp" class="photo-file-input" style="display:none;">
        </label>
        <button type="button" class="btn-icon btn-move-up" title="Subir">↑</button>
        <button type="button" class="btn-icon btn-move-down" title="Descer">↓</button>
        <button type="button" class="btn-icon btn-photo-remove" title="Remover">×</button>
    </div>
</div>`);
            renumber();
        });
    }

    if (list) {
        list.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            const item = btn.closest('.step-photo-item');
            if (!item) return;

            if (btn.classList.contains('btn-move-up')) {
                const prev = item.previousElementSibling;
                if (prev) list.insertBefore(item, prev);
                renumber();
            } else if (btn.classList.contains('btn-move-down')) {
                const next = item.nextElementSibling;
                if (next) list.insertBefore(next, item);
                renumber();
            } else if (btn.classList.contains('btn-photo-remove')) {
                // No contexto de step-photo-item, "Remover" remove o item INTEIRO
                // (não só a foto). Bloqueia o handler genérico de remoção parando a propagação.
                e.stopImmediatePropagation();
                if (confirm('Remover esta foto da galeria?')) {
                    item.remove();
                    renumber();
                }
            }
        }, true); // capture: roda antes do handler genérico de .btn-photo-remove
    }
})();

// ============================================================
// 10) COLOR GUIDE — add / remove
// ============================================================
(function () {
    'use strict';

    const list = document.getElementById('color-guide-list');
    const addBtn = document.getElementById('add-color');
    if (!list && !addBtn) return;

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            list.insertAdjacentHTML('beforeend', `
<div class="color-item">
    <input type="color" name="color_hex[]" value="#F5C0C0" class="color-picker">
    <input type="text" name="color_name_br[]" value="" placeholder="Nome (ex: Rosa)" class="color-name">
    <input type="text" name="color_usage_br[]" value="" placeholder="Uso (ex: Corpo)" class="color-usage">
    <button type="button" class="btn-icon btn-remove" title="Remover">×</button>
</div>`);
        });
    }

    if (list) {
        list.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-remove');
            if (!btn) return;
            const item = btn.closest('.color-item');
            if (item && confirm('Remover esta cor?')) item.remove();
        });
    }
})();

// ============================================================
// 11) STITCH GUIDE INLINE — add + live preview do nome do ponto
// ============================================================
(function () {
    'use strict';

    const list   = document.getElementById('stitch-inline-list');
    const addBtn = document.getElementById('add-stitch-inline');
    if (!list && !addBtn) return;

    // Atualiza o preview do nome no header quando muda o select
    document.addEventListener('change', function (e) {
        if (!e.target.classList || !e.target.classList.contains('stitch-inline-select')) return;
        const card = e.target.closest('.collapse-card');
        if (!card) return;
        const preview = card.querySelector('.stitch-name-preview');
        if (preview) {
            const selected = e.target.options[e.target.selectedIndex];
            const txt = (selected && selected.value) ? selected.text.trim() : '(escolher ponto)';
            preview.textContent = txt;
        }
    });

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            // Reaproveita as <option>s do primeiro select existente
            // (assim não precisa replicar a lista de pontos em JS)
            const firstSelect = list.querySelector('.stitch-inline-select');
            const optionsHtml = firstSelect
                ? firstSelect.innerHTML.replace(/\sselected(="selected")?/g, '')
                : '<option value="">— Selecionar ponto —</option>';

            list.insertAdjacentHTML('beforeend', `
<div class="collapse-card open" data-block-type="stitch_inline">
    <div class="collapse-card__header">
        <span class="collapse-card__title">
            <span class="chevron">▼</span>
            <strong>Ponto:</strong>
            <span class="stitch-name-preview">(escolher ponto)</span>
        </span>
        <span class="collapse-card__controls">
            <button type="button" class="btn-move-up"   title="Subir">↑</button>
            <button type="button" class="btn-move-down" title="Descer">↓</button>
            <button type="button" class="btn-remove"    title="Remover">×</button>
        </span>
    </div>
    <div class="collapse-card__body">
        <div class="form-group">
            <label>Ponto cadastrado</label>
            <select name="stitch_inline_id[]" class="stitch-inline-select">${optionsHtml}</select>
        </div>
        <div class="form-group">
            <label>Observação extra (opcional)</label>
            <textarea name="stitch_inline_observation[]" rows="2" placeholder="Ex: Lembre de fechar com o último laço pra não soltar."></textarea>
        </div>
    </div>
</div>`);
        });
    }
})();
