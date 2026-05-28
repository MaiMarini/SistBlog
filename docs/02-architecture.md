# Arquitetura — Kallme

Visão técnica do estado atual: roteamento bilíngue, fluxo de requisições, schema do banco e funções principais.

---

## 🌐 Roteamento bilíngue

### Mapa de URLs

```
URL                                  →  O que acontece
──────────────────────────────────────────────────────────────────────────
/                                    →  index.php → 301 → /br/
/admin/                              →  admin/* (diretório real, ignora .htaccess)
/install.php                         →  Endpoint de DEPLOY HTTP POST
/migrate.php?key=<MIGRATE_KEY>       →  Executa migrações idempotentes
/site.webmanifest                    →  PWA manifest (arquivo estático)
/favicon.*, /apple-touch-icon.png    →  Favicons (arquivos estáticos)
/assets/...                          →  CSS, JS, imagens (estáticos)
/uploads/...                         →  Imagens enviadas (estáticos)

/br/                                 →  page-router.php?lang=br&slug=    → pages/br/home.php
/br/sobre                            →  page-router.php?lang=br&slug=sobre → pages/br/sobre.php
/br/contato                          →  pages/br/contato.php
/br/politica-de-privacidade          →  pages/br/politica-de-privacidade.php
/br/termos                           →  pages/br/termos.php
/br/divulgacao-afiliados             →  pages/br/divulgacao-afiliados.php
/br/<categoria>                      →  Listagem de categoria (se a cat. existe e tem artigos)
/br/<categoria>/<artigo>             →  Artigo no banco WHERE category=cat AND slug=art
/br/<slug-pagina>                    →  Página estática ou artigo no banco (sem categoria)
/br/<qualquer-outro>                 →  pages/br/404.php (404)

/en/*                                →  Mesmo fluxo com pages/en/ (vazio por enquanto)
/<algum-slug>                        →  301 → /br/<algum-slug> (fallback)
```

### `.htaccess` (mod_rewrite)

```apache
RewriteEngine On

# 1. Arquivos e diretórios reais são servidos diretamente
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# 2. Paths de sistema saem do rewrite
RewriteRule ^(admin|assets|uploads|config|includes|templates|pages)(/.*)?$ - [L,NC]
RewriteRule ^(install|migrate)\.php$ - [L,NC]

# 3. Raiz → /br/
RewriteRule ^$ /br/ [R=301,L]

# 4. Roteamento por idioma
RewriteRule ^(br|en)/?(.*)$ page-router.php?lang=$1&slug=$2 [QSA,L]

# 5. Fallback: URLs sem prefixo → /br/<path>
RewriteRule ^(.+)$ /br/$1 [R=301,L]
```

### `page-router.php` — lógica de despacho

```
1. Captura lang ('br'|'en') e slug
2. Sanitiza slug (regex aceita [a-z0-9-]+ com no máx. 1 '/')
3. Se slug vazio → require pages/<lang>/home.php
4. Se slug contém '/' (ex: "croche/como-comecar"):
   a. Busca categoria. Se não existe → 404
   b. Busca página WHERE slug=art AND category=cat AND language=lang AND status=published
   c. Renderiza com renderDbPage()
5. Se slug simples:
   a. Existe arquivo /pages/<lang>/<slug>.php? → include
   b. É uma categoria? → /pages/<lang>/_category.php (ou placeholder)
   c. Página no banco WHERE slug=X AND language=lang AND category IS NULL? → renderiza
   d. Senão → renderNotFound() (carrega /pages/<lang>/404.php)
```

`renderDbPage($page, $lang, $category)` escolhe o template:
- `page_type='article'` → `templates/article.php` (a criar — sistema editorial de blocos modulares)
- `page_type='static'` → `templates/static.php` (a criar)

Se o arquivo de template ainda não existir (estado atual, pós-cleanup), o router cai num placeholder neutro ("sistema editorial em construção").

---

## 🗄 Banco de Dados

### Conexão

`config/database.php` expõe `getDB()` (PDO singleton) e `BASE_URL`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', '<seu_banco>');
define('DB_USER', '<seu_usuario>');
define('DB_PASS', '<sua_senha>');
define('BASE_URL', '/');

function getDB(): PDO { /* singleton */ }
```

### Tabela `users`

| Campo | Tipo | Notas |
|-------|------|-------|
| id | INT AUTO_INCREMENT PK | |
| username | VARCHAR(50) UNIQUE | |
| password | VARCHAR(255) | bcrypt (`password_hash`) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### Tabela `pages` — Schema editorial (19 colunas)

Após a remoção do sistema presell, a tabela tem só campos universais (identificação, i18n, metadados editoriais e SEO).

| Campo | Tipo | Default | Uso |
|-------|------|---------|-----|
| `id` | INT AUTO_INCREMENT PK | — | |
| `title` | VARCHAR(255) | — | Título principal |
| `slug` | VARCHAR(255) | — | URL (`/lang/slug` ou `/lang/cat/slug`) |
| `content` | LONGTEXT | NULL | Corpo (HTML do TinyMCE; aceita blocos editoriais via classes `editorial-*`) |
| `status` | ENUM('draft','published') | 'draft' | |
| `language` | VARCHAR(5) | 'br' | 'br' \| 'en' |
| `page_type` | ENUM('article','static') | 'static' | Tipo da página |
| `category` | VARCHAR(100) | NULL | Slug da categoria |
| `excerpt` | TEXT | NULL | Resumo curto para cards |
| `featured_image` | VARCHAR(500) | '' | Imagem destacada |
| `reading_time` | INT | NULL | Minutos (auto-calc para article) |
| `author_name` | VARCHAR(100) | NULL | Nome do autor |
| `publish_date` | DATE | NULL | Data de publicação editorial |
| `template` | VARCHAR(30) | NULL | Hero variant (`hero-classic`, `hero-side`, `hero-minimal`) |
| `meta_title` | VARCHAR(255) | '' | SEO |
| `meta_description` | TEXT | NULL | SEO |
| `tracking_code` | LONGTEXT | NULL | Tag injetada no `<head>` desta página |
| `created_at` | TIMESTAMP | CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | ON UPDATE | |

Índices:
- `idx_lang_type_status (language, page_type, status)`
- `idx_lang_category (language, category)`
- `idx_slug_lang (slug, language)` — slug NÃO é único por si só

### Tabela `categories` (schema bilíngue novo)

```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name_br VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NULL,
    description_br TEXT NULL,
    description_en TEXT NULL,
    icon_type ENUM('phosphor','svg') NOT NULL DEFAULT 'phosphor',
    icon_value VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (display_order),
    INDEX idx_active (is_active)
);
```

**4 categorias seed**:

| slug | name_br | name_en | icon_value | order |
|------|---------|---------|-----------|-------|
| croche | Crochê | Crochet | `ph-light ph-hand-heart` | 1 |
| jardinagem | Jardinagem | Gardening | `ph-light ph-flower-tulip` | 2 |
| diy | DIY Geral | DIY | `ph-light ph-palette` | 3 |
| minha-estante | Minha estante | My Shelf | `ph-light ph-books` | 4 |

> Detalhes do sistema de categorias (cache, render de ícones, drawer) em [07-categorias.md](07-categorias.md).

### Tabela `settings`

```sql
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_group (setting_group)
);
```

**18 settings seed** em 4 grupos:
- `general` (5): site_name, site_tagline_br/en, contact_email, default_language
- `tracking` (6): ga4, googleads, pinterest, facebook, tiktok, custom
- `social` (5): pinterest, instagram, facebook, youtube, twitter
- `seo` (2): default_meta_description_br/en

---

## 🔧 Funções principais

### `includes/functions.php`

| Função | O que faz |
|--------|-----------|
| `slugify($text)` | Slug a partir de texto (remove acentos, espaços→hífens) |
| `uploadImage($file, $subdir)` | Upload validado. Retorna caminho relativo ou false |
| `getPage($slug, $lang, $category)` | Busca página publicada (todos filtros opcionais) |
| `getPageById($id)` | Busca por ID (qualquer status) — admin |
| `getAllPages($filters)` | Lista admin com filtros opcionais (language, page_type, category, status) |
| `savePage($data, $id)` | Insert/update (whitelist de campos) |
| `deletePage($id)` | Remove página |
| `countPages()` | Stats (total/published/draft) |
| `buildPageDataFromPost($post, $files, &$errors)` | Constrói `$data` para `savePage`. Inclui validação de coerência: `static` zera `category`; `article` exige `category` (acrescenta erro se vazio) |
| `e($value)` | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` |
| `getTemplates()` | Lista de variantes de hero (`hero-classic`, `hero-side`, `hero-minimal`). **Helper legado** — não consumido pelo admin atual, mantido enquanto a coluna `pages.template` existir |

### `includes/auth.php`

| Função | O que faz |
|--------|-----------|
| `isLoggedIn()` / `requireLogin()` | Guard de sessão |
| `login($user, $pass)` | Verifica `password_verify` + regenera session_id |
| `logout()` | Limpa sessão + cookie |
| `generateCSRFToken()` / `validateCSRFToken($t)` / `csrfField()` | CSRF helpers |

### `includes/site-helpers.php`

| Função | O que faz |
|--------|-----------|
| `getCurrentLanguage()` | Resolve via `$_GET['lang']` → cookie → default ('br') |
| `getLanguagePrefix($lang)` | Retorna `/br` ou `/en` |
| `url($path, $lang)` | Constrói URL relativa com prefixo de idioma |
| `getCategory($slug, $lang)` | Categoria + alias `name`/`description` localizado |
| `getAllCategories($lang)` | Lista ativa, ordenada por display_order |
| `getArticles($filters)` | Páginas publicadas com filtros (language, category, page_type, limit) |
| `getArticleExcerpt($page, $maxLen)` | excerpt → subtitle → meta_description → content strip |
| `getCoverImage($page)` | featured_image → main_image → header_image → content1_image → content2[0] → placeholder |
| `formatDate($date, $lang)` | BR: "25 de maio de 2026" \| EN: "May 25, 2026" |
| `calculateReadingTime($content)` | Minutos (250 palavras/min) |
| `phosphorIcon($name)` | Map legado (Lucide-ish) → nome Phosphor (uso interno) |

### `includes/categories.php`

| Função | O que faz |
|--------|-----------|
| `getCategoriesWithArticleCount()` | Categorias + contagem por idioma + flags `is_available_br/en`, com **cache 1h** |
| `clearCategoriesCache()` | Invalida o cache (chamado no admin ao salvar/excluir) |
| `getCategoryBySlug($slug)` | Atalho do cache |
| `renderCategoryIcon($cat, $extraClass)` | HTML do ícone (phosphor classe ou svg inline/arquivo) |

### `includes/settings.php`

| Função | O que faz |
|--------|-----------|
| `loadAllSettings()` | Carrega todas em memória ($GLOBALS) |
| `getSetting($key, $default)` | Valor cacheado |
| `getSettings($group)` | Dicionário key=>value de um grupo |
| `setSetting($key, $value, $group)` | Upsert (INSERT ... ON DUPLICATE KEY UPDATE) |
| `settingAttr($key, $default)` | Versão escapada para atributos HTML |

---

## 📦 Fluxo de uma requisição

### Página estática (`/br/sobre`)

```
1. Apache vê /br/sobre — não é arquivo nem diretório
2. .htaccess: passa pelos filtros → match em ^(br|en)/?(.*)$
3. Rewrite: page-router.php?lang=br&slug=sobre
4. page-router.php:
   a. require config/database.php, functions.php, site-helpers.php
   b. lang='br', slug='sobre'
   c. Não há '/' no slug → checa /pages/br/sobre.php → existe → require
5. pages/br/sobre.php:
   a. require ../../config/database.php, functions, settings, site-helpers
   b. Define $lang, $pageTitle, $pageSlug, $activeNav
   c. include ../../includes/site-header.php
      → site-header.php abre <html>, <head> com favicons/Phosphor/fonts/tracking
      → abre <body>, include site-drawer.php (categorias + nav)
   d. <main> com o conteúdo da página
   e. include ../../includes/site-footer.php
      → footer 4 colunas + scripts (header.js)
      → fecha </body></html>
```

### Artigo categorizado (`/br/croche/como-comecar`)

```
1. page-router.php: slug='croche/como-comecar' (tem '/')
2. Explode: categorySlug='croche', articleSlug='como-comecar'
3. getCategory('croche', 'br') — encontra a categoria
4. Busca DB: SELECT * FROM pages WHERE slug='como-comecar' AND category='croche' AND language='br' AND status='published'
5. renderDbPage($page, 'br', $category)
6. page_type='article' → carrega templates/article.php (a criar em fase futura)
```

---

## 👁 Preview no admin

`admin/preview.php` ainda é um placeholder. O editor de blocos editoriais (TinyMCE) já renderiza um preview interno via `content_style` — visualmente próximo do que sairá no front, mas sem o chrome do site (header/footer/drawer).

Um preview de "página final" só será implementado depois que `templates/article.php` e `templates/static.php` existirem — aí o preview vai chamar o template real com `$page` populado pelos campos do form (sem persistir no banco).
