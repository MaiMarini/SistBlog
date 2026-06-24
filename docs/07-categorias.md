# Sistema de Categorias — Kallme

Como funciona o sistema de categorias do blog: schema bilíngue, cache, render de ícones e drawer dinâmico.

---

## 🗄 Schema da tabela `categories`

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

### Por que bilíngue de cara

Cada categoria tem **uma linha só** com colunas `name_br`/`name_en` (e descriptions). Schema anterior usava 1 linha por idioma — esse foi descartado porque duplicava metadados (icon, sort_order) e complicava a lógica de "categoria existe para esse idioma?".

### `icon_type` / `icon_value`

| icon_type | icon_value | Como renderiza |
|-----------|-----------|----------------|
| `phosphor` | classe completa, ex: `ph-light ph-hand-heart` | `<i class="ph-light ph-hand-heart icon-sm"></i>` |
| `svg` | caminho de arquivo `.svg` (ex: `/assets/img/icons/croche.svg`) **ou** SVG inline completo | injeta o SVG; se for path, lê o arquivo e adiciona a classe extra |

A intenção: hoje usamos Phosphor para todas, mas o suporte a SVG já está pronto pra quando trocarmos a Crochê pelo SVG customizado.

### Seed atual

| slug | name_br | name_en | icon_value | display_order |
|------|---------|---------|------------|---------------|
| `croche` | Crochê | Crochet | `ph-light ph-hand-heart` | 1 |
| `jardinagem` | Jardinagem | Gardening | `ph-light ph-flower-tulip` | 2 |
| `diy` | DIY Geral | DIY | `ph-light ph-palette` | 3 |
| `minha-estante` | Minha estante | My Shelf | `ph-light ph-books` | 4 |

> Costura foi **removida** intencionalmente. Se voltar no futuro, basta `INSERT` ou reativar (se vier a existir).

---

## 🧑‍💻 Funções principais (`includes/categories.php`)

### `getCategoriesWithContentCount(): array`

Retorna **todas as categorias ativas** com:
- Todos os campos da tabela
- `article_count_br` / `article_count_en` — artigos publicados por idioma
- `recipe_count_br` / `recipe_count_en` — receitas publicadas por idioma
- `has_stitch_guide_br` — `true` se a categoria for `croche` e houver ≥1 ponto ativo em `crochet_stitches` (o guia de pontos é exclusivo do crochê e do idioma BR)
- `is_available_br` — `true` se houver artigo OU receita OU guia de pontos disponível em BR
- `is_available_en` — `true` se houver artigo OU receita em EN

Resultado é um array **keyed pelo slug**, ordenado por `display_order`.

**Cache de 1 hora** em arquivo (`sys_get_temp_dir() . '/kallme_categories_cache.json'`).

```php
$cats = getCategoriesWithContentCount();
foreach ($cats as $slug => $cat) {
    $total = $cat['article_count_br'] + $cat['recipe_count_br'];
    echo $cat['name_br'] . ": $total conteúdo(s)\n";
}
```

> O nome antigo `getCategoriesWithArticleCount()` continua funcionando como alias deprecated — chamadores legados não quebram, mas novos lugares devem usar `getCategoriesWithContentCount()`.

### `clearCategoriesCache(): void`

Apaga o arquivo de cache. **Chamado automaticamente** quando algum item relevante muda no admin:

- `admin/page-form.php` — após `savePage()` bem-sucedido (artigos/receitas/estáticas)
- `admin/page-delete.php` — após `deletePage()`
- `admin/category-save.php` / `admin/category-delete.php` — CRUD de categorias
- `admin/stitch-save.php` / `admin/stitch-delete.php` — CRUD de pontos de crochê (afeta `has_stitch_guide_br`)

Você não precisa chamar manualmente. Para forçar limpeza: cPanel → Gerenciador de Arquivos → `/tmp/kallme_categories_cache.json` → deletar.

### `getCategoryBySlug($slug): ?array`

Atalho — pega uma categoria específica do conjunto cacheado.

### `renderCategoryIcon($cat, $extraClass): string`

Gera o HTML do ícone:

```php
echo renderCategoryIcon($cat, 'icon-sm');
// Se icon_type='phosphor':
// → <i class="ph-light ph-hand-heart icon-sm"></i>
//
// Se icon_type='svg' e icon_value termina em .svg:
// → injeta o SVG do arquivo, adicionando class="icon-sm" no <svg>
//
// Se icon_type='svg' e icon_value é SVG inline:
// → retorna o SVG cru
```

---

## 🔍 Helpers em `includes/site-helpers.php`

Existem também versões "simples" que retornam dados localizados:

### `getCategory($slug, $lang): ?array`

Pega uma categoria pelo slug. Adiciona aliases `name` e `description` já localizados conforme `$lang` (retrocompat).

Usado em:
- `page-router.php` — para validar `/br/categoria/artigo`
- `pages/br/home.php` — para o badge do card de artigo

### `getAllCategories($lang): array`

Lista todas as categorias ativas, ordenadas, com `name`/`description` localizados.

> Se você precisa **da contagem de conteúdo** (artigos + receitas + guia de pontos no crochê) e da flag `is_available`, use `getCategoriesWithContentCount()` (cacheado) de `includes/categories.php`.

---

## 🎨 Drawer dinâmico (`includes/site-drawer.php`)

O drawer renderiza categorias com lógica **disponível vs em breve**:

```php
$drawerCats = getCategoriesWithContentCount();

foreach ($drawerCats as $cat):
    $isAvailable = $lang === 'en' ? $cat['is_available_en'] : $cat['is_available_br'];
    $name = ($lang === 'en' && !empty($cat['name_en'])) ? $cat['name_en'] : $cat['name_br'];

    if ($isAvailable):
        // Link clicável
        echo '<a href="' . url($cat['slug'], $lang) . '">';
        echo renderCategoryIcon($cat, 'icon-sm');
        echo '<span>' . e($name) . '</span>';
        echo '</a>';
    else:
        // Placeholder "em breve"
        echo '<span class="drawer__category-placeholder">';
        echo renderCategoryIcon($cat, 'icon-sm');
        echo '<span>' . e($name) . '</span>';
        echo '<small class="drawer__coming-soon">em breve</small>';
        echo '</span>';
    endif;
endforeach;
```

### Quando uma categoria "abre"?

Assim que **publicar o primeiro artigo** com:
- `category = '<slug>'`
- `language = 'br'` (ou 'en')
- `status = 'published'`
- `page_type = 'article'`

Após salvar no admin, o cache é invalidado e o drawer passa a mostrar o link ativo na próxima visita.

---

## 📦 Como adicionar uma nova categoria

### Via SQL direto (rápido)

```sql
INSERT INTO categories
    (slug, name_br, name_en, description_br, icon_type, icon_value, display_order, is_active)
VALUES
    ('decoracao', 'Decoração', 'Decoration',
     'Inspirações para deixar a casa mais bonita',
     'phosphor', 'ph-light ph-house-line', 5, TRUE);
```

Depois delete `/tmp/kallme_categories_cache.json` (ou salve qualquer página no admin para limpar o cache).

### Trocar Crochê por SVG customizado

Quando tiver o arquivo `/assets/img/icons/croche.svg`:

```sql
UPDATE categories
SET icon_type = 'svg',
    icon_value = '/assets/img/icons/croche.svg'
WHERE slug = 'croche';
```

`renderCategoryIcon()` automaticamente vai injetar o SVG inline (não precisa mudar código).

### Desativar uma categoria

```sql
UPDATE categories SET is_active = FALSE WHERE slug = 'diy';
```

Ela some do drawer, das listagens e do roteamento (`getCategory()` retorna null → 404 ao acessar `/br/diy`).

---

## ⚡ Performance e cache

### Cache file

- Local: `sys_get_temp_dir() . '/kallme_categories_cache.json'`
- TTL: **1 hora** (constante `CATEGORIES_CACHE_TTL`)
- Tamanho típico: < 5 KB (JSON com 4 categorias)
- Formato: array associativo serializado em JSON UTF-8

### Quando é regenerado

- Cache expirou (>1h)
- Arquivo foi deletado (manual ou via `clearCategoriesCache()`)
- 1ª visita após salvar/excluir página no admin

### Custo da regeneração

- 1 query principal (`SELECT ... FROM categories ORDER BY display_order`)
- 2 COUNT(*) por categoria (BR + EN) — 8 queries no total com 4 categorias
- Total: ~9 queries leves (índice composto `idx_lang_type_status` ajuda)
- Resultado salvo no arquivo

Para escala (muitas categorias), considerar agregar em uma query única com JOIN no futuro.

---

## 🐛 Troubleshooting

### "Costura" voltou no drawer

- Verifique se rodou `migrate.php` após a Fase 3.1 — deve ter recriado a tabela
- Confira via phpMyAdmin: `SELECT slug FROM categories` — deve ter apenas 4 slugs
- Limpe o cache: delete `/tmp/kallme_categories_cache.json`

### Ícone não aparece

- Verifique se `icon_value` é exatamente `ph-light ph-NOME` (espaço entre as classes)
- Confirme que o nome existe no Phosphor (https://phosphoricons.com) — peso light
- Se for SVG: confirme que `/assets/img/icons/<arquivo>.svg` existe no servidor

### Drawer mostra "em breve" mesmo com artigo publicado

- Invalide o cache: salve qualquer página no admin OU delete o arquivo de cache
- Confirme que o artigo tem TODOS estes campos:
  - `status = 'published'`
  - `language = 'br'` (ou 'en')
  - `page_type = 'article'`
  - `category = '<slug-da-categoria>'`

### Erro PHP "categories table doesn't exist"

- Rode `migrate.php` para criar a tabela (idempotente)

---

## 🔮 Próximas evoluções

- **CRUD de categorias no admin** — hoje só via SQL
- **Página de listagem de categoria** (`/br/<slug>`) — `pages/br/_category.php` ainda não existe; o router está preparado
- **Templates públicos** (`templates/article.php` + `templates/static.php`) — hoje, ao acessar uma página salva pelo banco, o router cai num placeholder. O conteúdo já está sendo persistido com classes `editorial-*` corretas; falta o template que envelopa esse HTML com header/footer/drawer
