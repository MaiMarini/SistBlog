<?php
/**
 * STITCHES — Helpers de pontos de crochê (Fase Receita A1).
 *
 * Segue o padrão de includes/categories.php — CRUD + render de ícone
 * inline (preservando currentColor).
 */

require_once __DIR__ . '/functions.php';

/**
 * Lista todos os pontos. Por padrão só os ativos; passe false pra incluir ocultos.
 */
function getAllStitches(bool $onlyActive = true): array {
    $pdo = getDB();
    $sql = "SELECT * FROM crochet_stitches";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY display_order ASC, name_br ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca ponto por ID.
 */
function getStitchById(int $id): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM crochet_stitches WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Busca ponto por slug.
 */
function getStitchBySlug(string $slug): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM crochet_stitches WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Conta quantos pontos estão cadastrados (e ativos, por padrão).
 * Útil pro badge "X pontos cadastrados" em listings públicos.
 */
function getStitchCount(bool $onlyActive = true): int {
    $pdo = getDB();
    $sql = "SELECT COUNT(*) FROM crochet_stitches";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    return (int) $pdo->query($sql)->fetchColumn();
}

/**
 * Salva (INSERT se $id=null, UPDATE caso contrário). Whitelist controla colunas.
 */
function saveStitch(array $data, ?int $id = null): int|false {
    $pdo = getDB();

    $fields = [
        'slug', 'name_br', 'name_en', 'abbrev_br', 'abbrev_en',
        'image_url', 'photo_url',
        'description_br', 'description_en',
        'tutorial_anchor', 'display_order', 'is_active',
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

        $sql = "UPDATE crochet_stitches SET " . implode(', ', $sets) . " WHERE id = ?";
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

    $sql = "INSERT INTO crochet_stitches (" . implode(', ', $insertFields) . ")
            VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    return (int) $pdo->lastInsertId();
}

/**
 * Deleta ponto. Sem proteção por uso (na Fase A não há vinculação ainda).
 */
function deleteStitch(int $id): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM crochet_stitches WHERE id = ?");
    $stmt->execute([$id]);
    return ['success' => true];
}

/**
 * Verifica se slug está disponível.
 */
function isStitchSlugAvailable(string $slug, ?int $excludeId = null): bool {
    $pdo = getDB();
    if ($excludeId) {
        $stmt = $pdo->prepare("SELECT id FROM crochet_stitches WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM crochet_stitches WHERE slug = ?");
        $stmt->execute([$slug]);
    }
    return !$stmt->fetch();
}

/**
 * Renderiza o ícone do ponto.
 *
 * - Se image_url termina em .svg E o arquivo existe → injeta inline (currentColor)
 * - Senão se image_url está preenchido → <img> tag (PNG/JPG ou fallback de SVG ausente)
 * - Senão → placeholder "—"
 *
 * Usa __DIR__ . '/..' pra resolver o caminho do arquivo no projeto (mais
 * robusto que $_SERVER['DOCUMENT_ROOT'] em shared hosting).
 */
function renderStitchIcon(array $stitch, string $cssClass = 'stitch-icon-svg'): string {
    $url = $stitch['image_url'] ?? '';
    if (empty($url)) {
        return '<span class="' . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8') . ' stitch-icon-missing">—</span>';
    }

    $classEsc = htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8');

    // SVG inline (preserva currentColor)
    if (str_ends_with($url, '.svg')) {
        $path = __DIR__ . '/..' . $url;
        if (is_file($path)) {
            $svg = file_get_contents($path);
            $svg = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg);
            $svg = preg_replace('/<!--.*?-->/s', '', $svg);

            if (preg_match('/<svg\b[^>]*\bclass="/', $svg)) {
                // já tem class — faz merge
                $svg = preg_replace_callback(
                    '/<svg\b([^>]*)\bclass="([^"]*)"([^>]*)>/',
                    function ($m) use ($cssClass) {
                        $merged = trim($m[2] . ' ' . $cssClass);
                        return '<svg' . $m[1] . 'class="' . htmlspecialchars($merged, ENT_QUOTES, 'UTF-8') . '"' . $m[3] . '>';
                    },
                    $svg,
                    1
                );
            } else {
                $svg = preg_replace('/<svg\b/', '<svg class="' . $classEsc . '"', $svg, 1);
            }
            return trim($svg);
        }
    }

    // Fallback: <img> tag (PNG/JPG, ou SVG que ainda não foi subido ao servidor)
    return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
         . 'class="' . $classEsc . '" '
         . 'alt="' . htmlspecialchars($stitch['name_br'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}
