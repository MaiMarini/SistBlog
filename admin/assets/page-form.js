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
    // 1) ALTERNAR CAMPOS BASEADO EM page_type
    // ============================================================
    function togglePageTypeFields() {
        const pageTypeEl = document.getElementById('page_type');
        if (!pageTypeEl) return;

        const pageType = pageTypeEl.value;
        const categoryGroup = document.getElementById('category-group');
        const categorySelect = document.getElementById('category');
        const categoryHint = document.getElementById('category-hint');

        if (pageType === 'article') {
            // Mostra grupo, marca required, esconde hint
            if (categoryGroup) categoryGroup.style.display = '';
            if (categorySelect) categorySelect.setAttribute('required', 'required');
            if (categoryHint) categoryHint.style.display = 'none';
        } else {
            // Static: limpa categoria, remove required, mostra hint
            if (categorySelect) {
                categorySelect.value = '';
                categorySelect.removeAttribute('required');
            }
            if (categoryHint) categoryHint.style.display = 'block';
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

    // Estado inicial ao carregar
    togglePageTypeFields();
})();
