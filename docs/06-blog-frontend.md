# Blog Frontend — Kallme

Estrutura do site público: header horizontal, drawer lateral, footer, hero banner da homepage e as 7 páginas estáticas em `pages/br/`.

---

## 🗂 Componentes compartilhados

Toda página estática (e futuro artigo) carrega:

```php
include __DIR__ . '/../../includes/site-header.php';
// ... conteúdo da página ...
include __DIR__ . '/../../includes/site-footer.php';
```

`site-header.php` cuida do `<head>` (meta, fontes, favicons, tracking), abre `<body>`, renderiza o **header horizontal sticky** e inclui o **drawer**. `site-footer.php` renderiza o footer 4 colunas e fecha `</body></html>`.

---

## 🧭 `includes/site-header.php`

### O que faz

1. PHP setup: define `$lang`, `$siteName`, `$pageTitle`, `$pageDescription`, `$pageSlug`, `$canonicalUrl`, `$activeNav`
2. Renderiza o `<head>` completo:
   - Meta charset + viewport
   - `<title>` e `meta description`
   - `hreflang` BR/EN/x-default + canonical
   - Google Fonts (Playfair Display + Inter)
   - **Phosphor Icons** (regular + light) via CDN
   - `<link rel="stylesheet">` para `assets/css/site.css`
   - **Favicons** (icon, apple-touch, manifest, theme-color)
   - **Trackings globais** (6 settings injetados crus, exatamente como o admin colou)
   - Opcional: `$extraHead` (HTML extra por página)
3. Abre `<body class="site-body lang-<X>">`
4. Renderiza `<header class="site-header">` com 3 zonas
5. Inclui `site-drawer.php`

### Variáveis aceitas (todas opcionais)

| Variável | Default | Uso |
|----------|---------|-----|
| `$lang` | `getCurrentLanguage()` | Idioma da página |
| `$pageTitle` | `getSetting('site_name')` | `<title>` |
| `$pageDescription` | meta description padrão do idioma | Meta description |
| `$pageSlug` | `trim($_GET['slug'])` | Slug atual, usado pra hreflang |
| `$canonicalUrl` | constrói automaticamente | Canonical |
| `$extraHead` | '' | HTML extra dentro do `<head>` |
| `$activeNav` | '' | `'home'` \| `'sobre'` \| `'contato'` — destaca link ativo |

### Header horizontal — 3 zonas

```
┌─────────────────────────────────────────────┐
│  ☰     HOME · Kallme · SOBRE · CONTATO   📌 🔍 │  (página não-home)
│  ☰     HOME · SOBRE · CONTATO            📌 🔍 │  (homepage)
└─────────────────────────────────────────────┘
   esquerda          centro              direita
```

- **Esquerda**: botão hambúrguer (Phosphor `ph-list`, peso regular). Abre drawer.
- **Centro**: nav inline (Home, Sobre, Contato). Em páginas que **não são** a home, o "Kallme" pequeno (Playfair 22px) aparece entre os links.
- **Direita**: ícone Pinterest (`ph-pinterest-logo`) que abre o perfil em nova aba + busca (`ph-magnifying-glass`) que dispara alert "Em breve" via `header.js`.

CSS: fundo **rosa pétala claro** (`#F5E1DC`), sombra sutil embaixo, sticky com `z-index: 100`. Hambúrguer e ícones encostam nas bordas via `justify-self: start/end`. Em mobile (≤768px) os links do centro somem; só sobra hambúrguer + brand + ícones.

---

## 📂 `includes/site-drawer.php`

Menu lateral que desliza da esquerda ao clicar no hambúrguer.

### Estrutura

```
┌────────────────────┐░░░░░░░░░░░░░
│ [logo]        [X]  │░  overlay  ░
│                    │░░░░░░░░░░░░░
│ NAVEGAÇÃO          │
│ Home               │
│ Sobre              │
│ Contato            │
│                    │
│ CATEGORIAS         │
│ 💗 Crochê    em breve│
│ 🌷 Jardinagem em breve│
│ 🎨 DIY Geral  em breve│
│ 📚 Minha estante em breve│
└────────────────────┘
```

### Lógica de "disponível vs em breve"

Cada categoria é renderizada via `getCategoriesWithArticleCount()`. Para cada uma:
- `is_available_br`: existe artigo publicado em BR nessa categoria?
- Se sim → vira `<a href="/br/<slug>">` (link ativo)
- Se não → vira `<span class="drawer__category-placeholder">` com tag "em breve"

Por ora todas estão "em breve" (não há artigos publicados ainda).

### Largura

- Desktop: `width: 320px` / `max-width: 85vw`
- Slide-in via `transform: translateX(-100%) → translateX(0)`
- `z-index: 200` (acima do header e do overlay)

---

## 🦶 `includes/site-footer.php`

Footer 4 colunas:

```
┌──────────────┬──────────┬──────────────────┬────────────┐
│  [LOGO]      │  Site    │  Legal           │  Redes      │
│              │  Início  │  Privacidade     │  📌 Pinterest│
│              │  Sobre   │  Termos          │             │
│              │  Contato │  Divulgação      │             │
└──────────────┴──────────┴──────────────────┴────────────┘
  1.5fr          1fr        1fr                1fr
────────────────────────────────────────────────────────────
© 2026 Maíra Marini · kallme.online        Contato: support@...
```

### Características

- Fundo: navy escuro (`--color-primary-dark` `#23314A`)
- Logo (`assets/img/logo-full.png`) com fallback de texto "Maíra Marini Ateliê" via `:has()` CSS
- Coluna **Marca**: só a logo (sem tagline — removida no refactor 3-bis)
- Coluna **Site**: Início / Sobre / Contato
- Coluna **Legal**: Política de privacidade / Termos / Divulgação de afiliados
- Coluna **Redes**: ícone Pinterest + texto "Pinterest" linkando para o setting `social_pinterest`
- Linha inferior: copyright (esquerda) + e-mail de contato (direita) — empilha em mobile

### Responsivo

- ≤1024px: 2 colunas (1fr 1fr)
- ≤640px: 1 coluna centralizada

---

## 🏠 Homepage (`pages/br/home.php`)

URL: **`kallme.online/br/`**

### Estrutura

```php
<main>
  <section class="hero-banner">       ← imagem + h1 Kallme + tagline
  <section class="section-articles">  ← grid de últimos artigos OU empty state
  <section class="section-about-mini"> ← mini sobre com CTA pra /br/sobre
</main>
```

### Hero banner

- `height: 60vh` (min 480, max 720px)
- `background-image: url('/assets/img/hero-banner.jpg')` — **fallback rosa pétala** se a imagem não existir
- Overlay escuro suave (gradiente navy 35%→15%) garante legibilidade
- `<h1>Kallme</h1>` Playfair 120px off-white com text-shadow
- Tagline "Trabalhos manuais com você" em itálico Playfair 22px
- Responsivo: 64px (tablet) / 48px (mobile), altura 50vh no mobile

> Para subir a imagem oficial: `/assets/img/hero-banner.jpg` ou `.webp`, proporção 16:9, ~1920×1080, < 300KB ([TinyPNG](https://tinypng.com)).

### Grid de artigos

`getArticles(['language' => 'br', 'page_type' => 'article', 'limit' => 6])` busca os 6 mais recentes. Cada card:

- Cover image via `getCoverImage($article)` (featured_image → main → header → content1 → content2[0] → placeholder)
- Badge com nome da categoria
- Título (h3 Playfair 22px)
- Meta: ícone clock + "X min de leitura · 25 de maio de 2026"
- Excerpt via `getArticleExcerpt`
- Link "Ler artigo →"

Como não há artigos publicados ainda, o **empty state** ("Os primeiros artigos chegam em breve. 🌷") aparece num card rosa pétala.

### Mini sobre

Seção com fundo rosa pétala, headline "Sobre o Kallme", parágrafo curto e botão-link "Conhecer minha história →" para `/br/sobre`.

---

## 📄 As 7 páginas estáticas BR

Todas em `pages/br/`. Padrão:

```php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = '<Título> · Kallme';
$pageDescription = '<Meta description>';
$pageSlug = '<slug>';
$activeNav = '<home | sobre | contato | ''>';

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <!-- conteúdo da página -->
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>
```

| Arquivo | URL | Status do conteúdo |
|---------|-----|--------------------|
| `home.php` | `/br/` | Hero banner + grid + mini sobre ✅ |
| `sobre.php` | `/br/sobre` | **Texto editorial real** (~800 palavras) |
| `contato.php` | `/br/contato` | Card email + FAQ "Antes de me escrever" |
| `politica-de-privacidade.php` | `/br/politica-de-privacidade` | **Texto legal real** (LGPD/GDPR/CCPA) |
| `termos.php` | `/br/termos` | **Texto legal real** |
| `divulgacao-afiliados.php` | `/br/divulgacao-afiliados` | **Texto legal real** |
| `404.php` | servida em 404 pelo router | Mensagem + 2 CTAs |

---

## 🎨 Convenções de CSS para páginas estáticas

`assets/css/site.css` tem todas as classes utilizadas:

### Containers
- `.container` / `.container-wide` (1100px) — geral
- `.container-medium` (900px) — about, contato
- `.container-narrow` (680px) — artigos, páginas legais (linha de leitura ideal)
- `.container-full` (1280px) — hero com imagem de fundo

### Prose
`.prose` é o estilo de leitura para artigos/páginas longas. Tipografia confortável (line-height 1.75, Playfair em headings, Inter no corpo).

Variantes:
- `.prose-header` — cabeçalho centralizado das páginas estáticas
- `.prose-subtitle` — subtítulo italic abaixo do h1
- `.prose-meta` — "Última atualização: dd/mm/aaaa" das páginas legais
- `.prose-intro` — primeiro parágrafo destacado

### Cards e blocos
- `.card-article`, `.card-featured`, `.card-compact` — variantes de card
- `.block-quote`, `.block-tip`, `.block-warning`, `.block-newsletter`, `.block-product`
- `.contact-card` (página de contato), `.contact-faq`
- `.error-page` (404)
- `.empty-state` (rosa pétala arredondado)

> Detalhes completos do design system em [01-design-system.md](01-design-system.md).

---

## 🇬🇧 Preparação para EN

Estrutura `pages/en/` existe (vazia). Quando começar a publicar em inglês:

1. Criar `pages/en/home.php`, `about.php`, `contact.php`, etc.
2. Os slugs em inglês já estão mapeados em `site-footer.php` e `site-header.php`:
   - `about` (vs `sobre`)
   - `contact` (vs `contato`)
   - `privacy-policy` (vs `politica-de-privacidade`)
   - `terms` (vs `termos`)
   - `affiliate-disclosure` (vs `divulgacao-afiliados`)
3. As settings já têm `default_meta_description_en`, `site_tagline_en`
4. Categories têm `name_en` e `description_en` (mas os EN ainda estão vazios — preencher no admin)
5. `formatDate($date, 'en')` produz "May 25, 2026"

O router e o `.htaccess` já roteiam `/en/...` para `page-router.php?lang=en&slug=...`.
