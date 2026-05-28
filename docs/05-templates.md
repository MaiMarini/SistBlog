# Templates — Kallme

Documentação dos templates do sistema. Eles se dividem em **duas famílias**:

- **Templates de PRESELL** (`templates/`) — usados por páginas tipo presell salvas no banco (page_type='presell'). Renderizam HTML completo (não usam `site-header`/`site-footer`).
- **Templates de BLOG** (futuro) — para artigos editoriais (page_type='article'). Compartilharão o `site-header` + `site-footer`.

Páginas estáticas do blog (home, sobre, etc.) **não usam templates** — elas são arquivos PHP em `pages/<lang>/`.

---

## 🔄 Como o despacho funciona

`page-router.php` recebe um slug, busca a página no banco (ou abre o arquivo estático), depois decide:

```php
$pageType = $page['page_type'] ?? 'presell';
$templateName = match ($pageType) {
    'article' => 'article',                                 // → templates/article.php (futuro)
    'static'  => 'static',                                  // → templates/static.php (futuro)
    default   => ($page['template'] ?: 'advertorial'),      // presell e demais
};
require __DIR__ . '/templates/' . $templateName . '.php';
```

Para o template recebe as variáveis: `$page`, `$comments`, `$metaTitle`, `$metaDescription`, `$lang`, `$category` (quando categorizado).

---

## 1. Estruturado (`templates/structured.php`) — padrão atual

**Quando usar**: padrão de presell. 4 seções 100% customizáveis pelo admin.

### Estrutura visual

```
┌──────────────────────────────────────┐
│ CABEÇALHO                            │
│  • Background (sólido/linear/radial) │
│  • Texto acima da imagem (rich)      │
│  • Imagem                            │
│  • Texto abaixo da imagem (rich)     │
├──────────────────────────────────────┤
│ CONTEÚDO 1                           │
│  • Background (cor ou imagem)        │
│  ┌──────────┬──────────┐            │
│  │ Texto    │ Imagem   │            │
│  └──────────┴──────────┘            │
├──────────────────────────────────────┤
│ CONTEÚDO 2                           │
│  • Background (cor ou imagem)        │
│  ┌──┬──┬──┬──┬──┐ (até 5 imagens)  │
│  └──┴──┴──┴──┴──┘                  │
│  • Texto rich                        │
│  [ BOTÃO CTA ]                       │
├──────────────────────────────────────┤
│ RODAPÉ                               │
│  • Cor de fundo                      │
│  • Texto de alertas                  │
│  • [Btn1] [Btn2] [Btn3]              │
└──────────────────────────────────────┘

       [Botão flutuante CTA] ← canto inferior direito
```

### Campos do banco usados

| Campo | Uso |
|-------|-----|
| `header_bg_type` | `'solid'`, `'linear'`, `'radial'` |
| `header_bg_direction` | `'to bottom'`, `'to right'`, etc. |
| `header_bg_color1/2/3` | Cores do gradient (1 = sólido, 2-3 = stops) |
| `header_text` / `header_text_below` | HTML rich (acima/abaixo da imagem) |
| `header_image` | Imagem do cabeçalho |
| `content1_text` / `content1_image` / `content1_bg_image` / `content1_bg_color` | Conteúdo 1 |
| `content2_images_json` | Array de até 5 imagens |
| `content2_text` | HTML rich |
| `content2_cta_text` / `content2_cta_color` / `content2_cta_text_color` | CTA |
| `content2_bg_image` / `content2_bg_color` | Fundo |
| `footer_bg_color` / `footer_alerts` / `footer_buttons_json` | Rodapé |
| `footer_btn_color` / `footer_btn_size` | Estilo dos 3 botões |
| `affiliate_link` | URL dos CTAs |
| `tracking_code` | Injetado no `<head>` |

### Botão flutuante CTA

Renderizado sempre que `affiliate_link` está preenchido:
- `position: fixed` no canto inferior direito
- Mesmas cores e texto do CTA principal
- Aparece imediato ao carregar
- Esconde quando o CTA principal entra em 50% da viewport (IntersectionObserver em `presell.js`)
- Animação `ctaPulse`

### Grid responsivo de imagens (Conteúdo 2)

| Qtd | Desktop | Mobile |
|-----|---------|--------|
| 1 | 1 coluna grande | 1 coluna |
| 2 | 2 colunas | 1 coluna |
| 3 | 3 colunas | 1 coluna |
| 4 | 2 colunas | 1 coluna |
| 5 | auto-fit | 2 colunas |

---

## 2. Advertorial / Notícia (`templates/advertorial.php`)

**Quando usar**: parece artigo de portal de saúde/notícias. Disfarça a natureza promocional.

### Estrutura

```
📰 Portal Saúde & Bem-Estar
Home > Saúde > Artigo
─────────────────────────────
TÍTULO (manchete)
Subtítulo
👤 Por <Autor> · dd/mm/aaaa
[f] [𝕏] [📱] Social bar

[ IMAGEM PRINCIPAL ]

Corpo do artigo (Georgia/serif)
...

[ BOTÃO CTA pulsante ]

┌─ CTA Box (2º CTA) ──┐
│ Não perca!           │
│ [ BOTÃO ]            │
└──────────────────────┘

N comentários
👤 Maria — 5 dias atrás
   "Adorei o produto!"
   👍 Curtir · Responder

Footer disclaimer
```

### Campos usados

| Campo | Uso |
|-------|-----|
| `title` / `subtitle` | Manchete |
| `main_image` | Imagem grande |
| `content` | Corpo (HTML do Quill) |
| `author_name` / `author_avatar` / `publish_date` | Crédito do "jornalista" |
| `affiliate_link` / `cta_text` / `cta_color` | CTA (2× iguais) |
| `comments_json` | Seção de comentários no fim |

---

## 3. Blog Pessoal (`templates/blog-personal.php`)

**Quando usar**: depoimento/diário pessoal. Tom informal.

Visualmente próximo do Advertorial mas com:
- Cabeçalho com só o nome do "autor"
- Sem breadcrumb
- Tipografia mais íntima
- CTA único centralizado no meio

Mesmos campos do Advertorial.

---

## 4. Landing Page (`templates/landing.php`)

**Quando usar**: comercial direto, alta intenção de compra.

### Estrutura

```
┌─────────────────────────────────────┐
│ HERO (dark navy gradient)            │
│ TÍTULO GIGANTE                       │
│ Subtítulo                            │
│ [ CTA LG pulsante ]                  │
│ [ IMAGEM HERO ]                      │
├─────────────────────────────────────┤
│ SEÇÃO DE CONTEÚDO (white)            │
├─────────────────────────────────────┤
│ ❗ CTA do meio (fundo colorido)      │
├─────────────────────────────────────┤
│ "O que as pessoas estão dizendo"     │
│ 👤 Comentário 1                      │
│ 👤 Comentário 2                      │
├─────────────────────────────────────┤
│ CTA FINAL (dark navy)                │
├─────────────────────────────────────┤
│ Footer                               │
└─────────────────────────────────────┘
```

Múltiplos CTAs pelo caminho.

---

## 🎨 CSS dos templates de presell

`assets/css/presell.css` — **legado**, não mexer. Cada template usa sua classe no `<body>`:

- `.template-advertorial`
- `.template-blog-personal`
- `.template-landing`
- `.template-structured`

E prefixos em classes específicas:
- `.adv-*` — advertorial
- `.blog-*` — blog pessoal
- `.landing-*` — landing
- `.struct-*` — estruturado

Compartilhados: `@keyframes ctaPulse`, `.comment-item`, `.comment-avatar`.

---

## 📝 Como adicionar um novo template de presell

1. Crie `templates/<seu-template>.php` (use um existente como base)
2. Adicione em `getTemplates()` em `includes/functions.php`:
   ```php
   '<seu-template>' => 'Nome Bonito',
   ```
3. Adicione estilos `.template-<seu-template>` em `assets/css/presell.css`
4. Se precisar de campos novos: adicione em `migrate.php`, em `savePage` (lista `$fields`), em `buildPageDataFromPost`, e nos inputs do `admin/page-form.php`
5. Adicione o arquivo no `deploy.sh`
6. Deploy + migração

---

## 🧪 Como funciona o HTML cru do Quill

Os campos rich text (`content`, `header_text`, `header_text_below`, `content1_text`, `content2_text`, `footer_alerts`) guardam **HTML cru** do Quill com inline-styles (font-family, font-size, color, background-color).

Nos templates são impressos sem escape (`<?= $page['campo'] ?>`) para renderizar o HTML. Por isso só o admin (logado) deve escrever nesses campos — **não há sanitização**.

Quill está configurado para usar **inline-style** (não classes) para size/font/color/background, garantindo que os estilos funcionem fora do contexto do editor.

---

## 🆕 Templates do blog (futuro)

Quando começarmos a publicar artigos editoriais reais:

### `templates/article.php` (a criar)
- Carrega `site-header.php` + `site-footer.php` (igual às páginas estáticas)
- Estrutura tipo "post de blog":
  - Header com categoria (badge) + título + meta (autor, data, tempo de leitura)
  - Featured image
  - Corpo (`.prose` do `site.css`)
  - Sidebar opcional com "artigos relacionados"
- Usa campos: title, subtitle, featured_image, content, category, reading_time, publish_date, excerpt

### `templates/static.php` (provavelmente desnecessário)
Páginas estáticas hoje vivem em `pages/<lang>/*.php`. Provavelmente não precisaremos de um template `static` no banco, mas o router está preparado caso decidamos guardar páginas estáticas no DB no futuro.

### `pages/<lang>/_category.php` (a criar)
Listagem de uma categoria — recebe `$category` e `$categoryArticles` do router. Vai renderizar grid de cards estilo home.
