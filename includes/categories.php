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
 * Retorna todas as categorias ativas com contagem de conteúdo publicado
 * por idioma e flags de disponibilidade. Resultado é um array associativo
 * keyed pelo slug. Usa cache de 1h.
 *
 * "Conteúdo" inclui:
 *   - pages.page_type IN ('article','recipe') com status='published'
 *   - guia de pontos do crochê (categoria 'croche') se houver ao menos
 *     1 ponto ativo em crochet_stitches — só conta no idioma 'br' (o guia
 *     é exclusivo da versão pt-BR por enquanto).
 *
 * Cada item:
 *   slug, name_br, name_en, description_br, description_en,
 *   quote_text_br/en, quote_author_br/en,
 *   icon_type, icon_value, display_order,
 *   article_count_br, article_count_en,
 *   recipe_count_br, recipe_count_en,
 *   has_stitch_guide_br (bool),
 *   is_available_br (bool), is_available_en (bool)
 */
function getCategoriesWithContentCount(): array {
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
               quote_text_br, quote_text_en, quote_author_br, quote_author_en,
               icon_type, icon_value, color_bg, color_text, display_order
        FROM categories
        WHERE is_active = TRUE
        ORDER BY display_order ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM pages
        WHERE category = :slug
          AND language = :lang
          AND status = 'published'
          AND page_type = :ptype
    ");

    // Guia de pontos: conta só uma vez. Só conta como conteúdo se houver
    // ao menos 1 ponto ativo. Exclusivo do crochê e do idioma 'br'.
    $stitchCount = 0;
    try {
        $stitchCount = (int) $pdo->query("
            SELECT COUNT(*) FROM crochet_stitches WHERE is_active = 1
        ")->fetchColumn();
    } catch (\PDOException $e) {
        // Tabela pode não existir em ambientes antigos — trata como 0.
        $stitchCount = 0;
    }
    $hasStitchGuide = $stitchCount > 0;

    $result = [];
    foreach ($cats as $cat) {
        // Artigos
        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'br', 'ptype' => 'article']);
        $articleBr = (int) $countStmt->fetchColumn();
        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'en', 'ptype' => 'article']);
        $articleEn = (int) $countStmt->fetchColumn();

        // Receitas
        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'br', 'ptype' => 'recipe']);
        $recipeBr = (int) $countStmt->fetchColumn();
        $countStmt->execute(['slug' => $cat['slug'], 'lang' => 'en', 'ptype' => 'recipe']);
        $recipeEn = (int) $countStmt->fetchColumn();

        // Guia de pontos só vale pra crochê (e só BR)
        $catHasGuide = ($cat['slug'] === 'croche') && $hasStitchGuide;

        $cat['article_count_br']   = $articleBr;
        $cat['article_count_en']   = $articleEn;
        $cat['recipe_count_br']    = $recipeBr;
        $cat['recipe_count_en']    = $recipeEn;
        $cat['has_stitch_guide_br'] = $catHasGuide;

        $cat['is_available_br'] = ($articleBr + $recipeBr) > 0 || $catHasGuide;
        $cat['is_available_en'] = ($articleEn + $recipeEn) > 0;

        $result[$cat['slug']] = $cat;
    }

    // 3) Grava o cache (silencioso se falhar)
    @file_put_contents(CATEGORIES_CACHE_FILE, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Alias de retrocompatibilidade — chamadores antigos seguem funcionando.
 * @deprecated use getCategoriesWithContentCount()
 */
function getCategoriesWithArticleCount(): array {
    return getCategoriesWithContentCount();
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
    $all = getCategoriesWithContentCount();
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

                // Limpa declaração XML e comentários (geralmente vêm do Illustrator/Inkscape)
                $svg = preg_replace('/<\?xml[^>]*\?>/', '', $svg);
                $svg = preg_replace('/<!--.*?-->/s', '', $svg);

                // Classe base + extras passadas pelo caller (ex: 'icon-sm')
                $classes = trim('category-icon-svg ' . $extraClass);
                $classesEsc = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');

                if (preg_match('/<svg\b[^>]*\bclass="([^"]*)"/', $svg)) {
                    // SVG já tem class= → faz merge com as classes existentes
                    $svg = preg_replace_callback(
                        '/<svg\b([^>]*)\bclass="([^"]*)"([^>]*)>/',
                        function ($m) use ($classes) {
                            $merged = trim($m[2] . ' ' . $classes);
                            return '<svg' . $m[1] . 'class="' . htmlspecialchars($merged, ENT_QUOTES, 'UTF-8') . '"' . $m[3] . '>';
                        },
                        $svg,
                        1
                    );
                } else {
                    // SVG sem class= → injeta logo após <svg
                    $svg = preg_replace('/<svg\b/', '<svg class="' . $classesEsc . '"', $svg, 1);
                }

                return trim($svg);
            }
            return '';
        }
        // SVG inline completo (não termina em .svg → o icon_value JÁ É o markup)
        return $value;
    }

    return '';
}

// ============================================================
// CRUD ADMIN — gerenciamento de categorias pelo painel
// ============================================================

/**
 * Lista todas as categorias (incluindo inativas) — uso administrativo.
 */
function getAllCategoriesForAdmin(): array {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT *
        FROM categories
        ORDER BY display_order ASC, name_br ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca categoria por ID (qualquer estado).
 */
function getCategoryById(int $id): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Salva categoria. INSERT se $id=null; UPDATE caso contrário.
 *
 * Whitelist controla quais colunas podem ser escritas — qualquer chave
 * extra em $data é ignorada silenciosamente.
 */
function saveCategory(array $data, ?int $id = null): int|false {
    $pdo = getDB();

    $fields = [
        'slug', 'name_br', 'name_en',
        'description_br', 'description_en',
        'quote_text_br', 'quote_text_en',
        'quote_author_br', 'quote_author_en',
        'icon_type', 'icon_value',
        'color_bg', 'color_text',
        'display_order', 'is_active',
    ];

    if ($id) {
        $sets = [];
        $values = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($sets)) return false;
        $values[] = $id;

        $sql = "UPDATE categories SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $id;
    }

    $insertFields = [];
    $placeholders = [];
    $values = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data)) {
            $insertFields[] = $f;
            $placeholders[] = '?';
            $values[] = $data[$f];
        }
    }
    if (empty($insertFields)) return false;

    $sql = "INSERT INTO categories (" . implode(', ', $insertFields) . ")
            VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    return (int) $pdo->lastInsertId();
}

/**
 * Deleta categoria. Recusa se houver artigos publicados vinculados.
 * Retorna ['success' => bool, 'error' => ?string].
 */
function deleteCategory(int $id): array {
    $pdo = getDB();

    $cat = getCategoryById($id);
    if (!$cat) {
        return ['success' => false, 'error' => 'Categoria não encontrada'];
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM pages
        WHERE category = ? AND page_type IN ('article', 'recipe')
    ");
    $stmt->execute([$cat['slug']]);
    $count = (int) $stmt->fetchColumn();

    if ($count > 0) {
        return [
            'success' => false,
            'error' => "Não é possível excluir: existem $count conteúdo(s) (artigos/receitas) vinculado(s) a esta categoria.",
        ];
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return ['success' => true];
}

/**
 * Verifica se um slug está disponível (não conflita com outro registro).
 */
function isCategorySlugAvailable(string $slug, ?int $excludeId = null): bool {
    $pdo = getDB();
    if ($excludeId) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
    }
    return !$stmt->fetch();
}
