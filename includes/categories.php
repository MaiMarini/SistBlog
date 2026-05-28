<?php
/**
 * CATEGORIES — Kallme
 *
 * Funções auxiliares de categorias do blog, com cache em arquivo (1h)
 * pra evitar query repetida a cada request.
 *
 * Adaptado da spec para a arquitetura do projeto: usamos getDB() (PDO
 * singleton) em vez de `global $pdo`.
 *
 * Schema esperado da tabela `categories`:
 *   slug, name_br, name_en, description_br, description_en,
 *   icon_type ('phosphor'|'svg'), icon_value, display_order, is_active
 */

require_once __DIR__ . '/../config/database.php';

if (!defined('CATEGORIES_CACHE_TTL')) {
    define('CATEGORIES_CACHE_TTL', 3600); // 1 hora
}
if (!defined('CATEGORIES_CACHE_FILE')) {
    define('CATEGORIES_CACHE_FILE', sys_get_temp_dir() . '/kallme_categories_cache.json');
}

/**
 * Retorna todas as categorias ativas com contagem de artigos publicados
 * por idioma e flags de disponibilidade. Resultado é um array associativo
 * keyed pelo slug. Usa cache de 1h.
 *
 * Cada item:
 *   slug, name_br, name_en, description_br, description_en,
 *   icon_type, icon_value, display_order,
 *   article_count_br, article_count_en,
 *   is_available_br (bool), is_available_en (bool)
 */
function getCategoriesWithArticleCount(): array {
    // 1) Tenta o cache
    if (is_file(CATEGORIES_CACHE_FILE)) {
        $age = time() - filemtime(CATEGORIES_CACHE_FILE);
        if ($age < CATEGORIES_CACHE_TTL) {
            $cached = json_decode(file_get_contents(CATEGORIES_CACHE_FILE), true);
            if (is_array($cached)) {
                return $cached;
            }
        }
    }

    // 2) Consulta o banco
    $pdo = getDB();
    $cats = $pdo->query("
        SELECT slug, name_br, name_en, description_br, description_en,
               icon_type, icon_value, display_order
        FROM categories
        WHERE is_active = TRUE
        ORDER BY display_order ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM pages
        WHERE category = :slug
          AND language = :lang
          AND status = 'published'
          AND page_type = 'article'
    ");

    $result = [];
    foreach ($cats as $cat) {
        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'br']);
        $br = (int) $countStmt->fetchColumn();

        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'en']);
        $en = (int) $countStmt->fetchColumn();

        $cat['article_count_br'] = $br;
        $cat['article_count_en'] = $en;
        $cat['is_available_br'] = $br > 0;
        $cat['is_available_en'] = $en > 0;

        $result[$cat['slug']] = $cat;
    }

    // 3) Grava o cache (silencioso se falhar)
    @file_put_contents(CATEGORIES_CACHE_FILE, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Limpa o cache de categorias. Chamar sempre que uma página é
 * criada/editada/excluída no admin.
 */
function clearCategoriesCache(): void {
    if (is_file(CATEGORIES_CACHE_FILE)) {
        @unlink(CATEGORIES_CACHE_FILE);
    }
}

/**
 * Retorna uma categoria pelo slug (a partir do conjunto cacheado).
 */
function getCategoryBySlug(string $slug): ?array {
    $all = getCategoriesWithArticleCount();
    return $all[$slug] ?? null;
}

/**
 * Renderiza o HTML do ícone de uma categoria (phosphor ou svg).
 *
 * @param array|null $category   Item de categoria (com icon_type/icon_value)
 * @param string     $extraClass Classes extras (ex: 'icon-sm')
 */
function renderCategoryIcon(?array $category, string $extraClass = ''): string {
    if (!$category || empty($category['icon_value'])) {
        return '';
    }

    $type = $category['icon_type'] ?? 'phosphor';

    if ($type === 'phosphor') {
        // icon_value já é a classe completa, ex: "ph-light ph-hand-heart"
        return sprintf(
            '<i class="%s %s"></i>',
            htmlspecialchars($category['icon_value'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8')
        );
    }

    if ($type === 'svg') {
        $value = $category['icon_value'];
        // Caminho de arquivo .svg → injeta inline
        if (str_ends_with($value, '.svg')) {
            $path = __DIR__ . '/..' . $value; // relativo à raiz do projeto
            if (is_file($path)) {
                $svg = file_get_contents($path);
                if ($extraClass !== '') {
                    $svg = preg_replace('/<svg /', '<svg class="' . htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8') . '" ', $svg, 1);
                }
                return $svg;
            }
            return '';
        }
        // SVG inline completo
        return $value;
    }

    return '';
}
