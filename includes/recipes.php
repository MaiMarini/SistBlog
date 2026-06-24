<?php
/**
 * RECIPES — Helpers de receitas de crochê (Fase Receita B1).
 *
 * Uma receita é uma linha de `pages` com page_type='recipe'. Compartilha
 * todos os campos editoriais (title, slug, content, SEO, tracking) e
 * tem 7 campos específicos:
 *   - difficulty (beginner|intermediate|advanced)
 *   - estimated_time (string livre: "2 horas")
 *   - piece_type (amigurumi|wearable|decor|accessory|other)
 *   - final_size, yarn_recommended, hook_size
 *   - is_free (selo "Free Pattern")
 *
 * Receitas referenciam múltiplos pontos do glossário via tabela N:N
 * `recipe_stitches`.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/stitches.php';

/**
 * Valores válidos da ENUM `difficulty`, com labels localizadas.
 */
function getRecipeDifficulties(): array {
    return [
        'beginner'     => ['br' => 'Iniciante',     'en' => 'Beginner'],
        'intermediate' => ['br' => 'Intermediário', 'en' => 'Intermediate'],
        'advanced'     => ['br' => 'Avançado',      'en' => 'Advanced'],
    ];
}

/**
 * Valores válidos da ENUM `piece_type`, com labels localizadas.
 */
function getRecipePieceTypes(): array {
    return [
        'amigurumi' => ['br' => 'Amigurumi', 'en' => 'Amigurumi'],
        'wearable'  => ['br' => 'Vestuário', 'en' => 'Wearable'],
        'decor'     => ['br' => 'Decoração', 'en' => 'Decor'],
        'accessory' => ['br' => 'Acessório', 'en' => 'Accessory'],
        'other'     => ['br' => 'Outro',     'en' => 'Other'],
    ];
}

/**
 * Label localizada da dificuldade. Retorna '' se NULL.
 */
function getDifficultyLabel(?string $difficulty, string $language = 'br'): string {
    if (!$difficulty) return '';
    $map = getRecipeDifficulties();
    return $map[$difficulty][$language] ?? $difficulty;
}

/**
 * Label localizada do tipo de peça. Retorna '' se NULL.
 */
function getPieceTypeLabel(?string $pieceType, string $language = 'br'): string {
    if (!$pieceType) return '';
    $map = getRecipePieceTypes();
    return $map[$pieceType][$language] ?? $pieceType;
}

/**
 * Lista todas as receitas com filtros opcionais (publicadas por padrão).
 */
function getAllRecipes(
    string $language = 'br',
    ?string $category = null,
    ?string $difficulty = null,
    bool $onlyPublished = true
): array {
    $pdo = getDB();

    $sql = "SELECT * FROM pages WHERE page_type = 'recipe' AND language = ?";
    $params = [$language];

    if ($onlyPublished) {
        $sql .= " AND status = 'published'";
    }
    if ($category !== null && $category !== '') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if ($difficulty !== null && $difficulty !== '') {
        $sql .= " AND difficulty = ?";
        $params[] = $difficulty;
    }

    $sql .= " ORDER BY COALESCE(publish_date, created_at) DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca receita publicada por slug + idioma + categoria.
 */
function findRecipe(string $slug, string $language, string $category): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM pages
        WHERE slug = ?
          AND language = ?
          AND category = ?
          AND page_type = 'recipe'
          AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug, $language, $category]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Conta receitas publicadas em uma categoria.
 */
function countRecipesInCategory(string $categorySlug, string $language = 'br'): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM pages
        WHERE category = ?
          AND language = ?
          AND page_type = 'recipe'
          AND status = 'published'
    ");
    $stmt->execute([$categorySlug, $language]);
    return (int) $stmt->fetchColumn();
}

/**
 * Lista pontos vinculados a uma receita (com dados completos do ponto +
 * a ordem no vínculo).
 */
function getRecipeStitches(int $pageId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT s.*, rs.display_order AS link_order
        FROM recipe_stitches rs
        INNER JOIN crochet_stitches s ON s.id = rs.stitch_id
        WHERE rs.page_id = ?
        ORDER BY rs.display_order ASC, s.display_order ASC, s.name_br ASC
    ");
    $stmt->execute([$pageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Dados visuais do selo de dificuldade (ícone + cores + labels bilíngues).
 * Cai pra 'beginner' se valor desconhecido.
 */
function getDifficultyVisual(?string $difficulty): array {
    $visuals = [
        'beginner' => [
            'icon'     => 'ph-light ph-plant',
            'bg'       => '#E0EDD8',  // sage
            'text'     => '#6B8552',
            'label_br' => 'Iniciante',
            'label_en' => 'Beginner',
        ],
        'intermediate' => [
            'icon'     => 'ph-light ph-flower',
            'bg'       => '#FCEAC4',  // honey
            'text'     => '#B5853A',
            'label_br' => 'Intermediário',
            'label_en' => 'Intermediate',
        ],
        'advanced' => [
            'icon'     => 'ph-light ph-tree',
            'bg'       => '#F5D5D5',  // rose
            'text'     => '#A04848',
            'label_br' => 'Avançado',
            'label_en' => 'Advanced',
        ],
    ];

    return $visuals[$difficulty] ?? $visuals['beginner'];
}

/**
 * Substitui (delete + insert) os vínculos de pontos de uma receita.
 * Transacional. A ordem dos IDs no array define `display_order`.
 */
function saveRecipeStitches(int $pageId, array $stitchIds): void {
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $pdo->prepare("DELETE FROM recipe_stitches WHERE page_id = ?")->execute([$pageId]);

        $insert = $pdo->prepare("
            INSERT INTO recipe_stitches (page_id, stitch_id, display_order)
            VALUES (?, ?, ?)
        ");

        // dedupe preservando ordem (a UNIQUE KEY na tabela já protegeria,
        // mas filtrar aqui dá mensagem de erro mais clara)
        $seen = [];
        $order = 1;
        foreach ($stitchIds as $id) {
            $id = (int) $id;
            if ($id <= 0 || isset($seen[$id])) continue;
            $seen[$id] = true;
            $insert->execute([$pageId, $id, $order++]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
