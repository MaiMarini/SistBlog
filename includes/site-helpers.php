<?php
/**
 * SITE HELPERS — Kallme
 *
 * Funções utilitárias do site público (blog) e do roteamento bilíngue.
 * Carregue depois de includes/functions.php (compartilha getDB).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/categories.php';

// Idiomas suportados pelo sistema (whitelist)
const KALLME_LANGS = ['br', 'en'];
const KALLME_DEFAULT_LANG = 'br';

/**
 * Converte um nome de ícone (legado Lucide, ainda armazenado no banco em
 * categories.icon) para o nome equivalente no Phosphor Icons (peso light).
 *
 * Retorna apenas o SUFIXO do ícone (sem o prefixo "ph-light ph-").
 * Uso no template:
 *   <i class="ph-light ph-<?= e(phosphorIcon($cat['icon'])) ?> icon-sm"></i>
 *
 * Mapeamento das categorias segue a decisão de design:
 *   Crochê (scissors) → hand-heart  ·  Jardinagem (flower-2) → flower-tulip
 *   Costura (shirt) → t-shirt        ·  DIY (palette) → palette
 *   Minha estante (books) → books
 *
 * Ícones desconhecidos caem em 'tag'.
 */
function phosphorIcon(string $name): string {
    static $map = [
        // Interface
        'menu' => 'list', 'x' => 'x', 'search' => 'magnifying-glass',
        'search-x' => 'magnifying-glass-minus',
        'arrow-right' => 'arrow-right', 'arrow-left' => 'arrow-left',
        'chevron-right' => 'caret-right', 'chevron-down' => 'caret-down',
        'external-link' => 'arrow-square-out', 'mail' => 'envelope',
        'home' => 'house', 'user' => 'user', 'clock' => 'clock',
        'calendar' => 'calendar', 'alert-circle' => 'warning-circle',
        'info' => 'info', 'check' => 'check', 'check-circle' => 'check-circle',
        'heart' => 'heart', 'star' => 'star', 'quote' => 'quotes',
        // Redes sociais
        'pinterest' => 'pinterest-logo', 'pin' => 'pinterest-logo',
        'instagram' => 'instagram-logo',
        // Categorias do blog
        'scissors' => 'hand-heart',     // Crochê (provisório, vira SVG depois)
        'flower-2' => 'flower-tulip',   // Jardinagem
        'shirt' => 't-shirt',           // Costura
        'palette' => 'palette',         // DIY Geral
        'books' => 'books',             // Minha estante
        'tag' => 'tag',
    ];
    return $map[$name] ?? 'tag';
}

/**
 * Idioma "atual" da requisição.
 *
 * Ordem de resolução:
 *   1. $_GET['lang'] (vem do roteador)
 *   2. cookie 'kallme_lang' (preferência salva)
 *   3. fallback: KALLME_DEFAULT_LANG ('br')
 */
function getCurrentLanguage(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'], KALLME_LANGS, true)) {
        return $_GET['lang'];
    }
    if (isset($_COOKIE['kallme_lang']) && in_array($_COOKIE['kallme_lang'], KALLME_LANGS, true)) {
        return $_COOKIE['kallme_lang'];
    }
    return KALLME_DEFAULT_LANG;
}

/**
 * Prefixo de URL para um idioma (ex: '/br').
 *
 * @param string|null $lang  Se omitido, usa o idioma atual.
 */
function getLanguagePrefix(?string $lang = null): string {
    return '/' . ($lang ?? getCurrentLanguage());
}

/**
 * Constrói uma URL relativa com prefixo de idioma.
 *
 *   url('sobre')                  → '/br/sobre'
 *   url('croche/como-comecar')    → '/br/croche/como-comecar'
 *   url('/produto/x-y', 'en')     → '/en/produto/x-y'
 *
 * @param string      $path  Caminho relativo (com ou sem barra inicial).
 * @param string|null $lang  Idioma (default: atual).
 */
function url(string $path, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    $path = ltrim($path, '/');
    return '/' . $lang . ($path === '' ? '/' : '/' . $path);
}

/**
 * Busca uma categoria ativa pelo slug (schema bilíngue).
 *
 * Acrescenta uma chave de conveniência `name` (e `description`) já
 * localizada conforme $lang, pra retrocompat com chamadas que usam
 * $cat['name'].
 *
 * @return array|null  Row da tabela (+ name/description localizados) ou null.
 */
function getCategory(string $slug, string $lang = KALLME_DEFAULT_LANG): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = TRUE LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $useEn = ($lang === 'en' && !empty($row['name_en']));
    $row['name'] = $useEn ? $row['name_en'] : $row['name_br'];
    $row['description'] = $useEn ? ($row['description_en'] ?? '') : ($row['description_br'] ?? '');
    return $row;
}

/**
 * Lista categorias ativas, em ordem de exibição (schema bilíngue).
 *
 * Cada item recebe `name`/`description` localizados conforme $lang
 * (retrocompat). Use getCategoriesWithArticleCount() em includes/categories.php
 * quando precisar de contagem de artigos e disponibilidade.
 */
function getAllCategories(string $lang = KALLME_DEFAULT_LANG): array {
    $pdo = getDB();
    $rows = $pdo->query("
        SELECT * FROM categories
        WHERE is_active = TRUE
        ORDER BY display_order ASC
    ")->fetchAll();

    foreach ($rows as &$row) {
        $useEn = ($lang === 'en' && !empty($row['name_en']));
        $row['name'] = $useEn ? $row['name_en'] : $row['name_br'];
        $row['description'] = $useEn ? ($row['description_en'] ?? '') : ($row['description_br'] ?? '');
    }
    unset($row);
    return $rows;
}

/**
 * Busca artigos/páginas publicadas com filtros opcionais.
 *
 * Filtros aceitos (todos opcionais):
 *   - language    string  (ex: 'br')
 *   - category    string  (slug da categoria)
 *   - page_type   string  ('article', 'presell', 'static', 'home')
 *   - limit       int     (LIMIT N)
 *
 * Ordena por data de publicação (publish_date OU created_at) desc.
 *
 * @return array  Lista de rows.
 */
function getArticles(array $filters = []): array {
    $pdo = getDB();
    $where = ["status = 'published'"];
    $params = [];

    if (!empty($filters['language'])) {
        $where[] = 'language = ?';
        $params[] = $filters['language'];
    }
    if (!empty($filters['category'])) {
        $where[] = 'category = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['page_type'])) {
        $where[] = 'page_type = ?';
        $params[] = $filters['page_type'];
    }

    $sql = "SELECT * FROM pages WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY COALESCE(publish_date, DATE(created_at)) DESC, id DESC";
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . (int) $filters['limit'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Resumo curto da página para cards/listagens.
 *
 * Ordem de prioridade:
 *   1. campo `excerpt`
 *   2. campo `subtitle`
 *   3. campo `meta_description`
 *   4. primeiros N caracteres de `content` (strip tags)
 *
 * @param int $maxLen  Comprimento máximo do retorno.
 */
function getArticleExcerpt(array $page, int $maxLen = 160): string {
    if (!empty($page['excerpt'])) {
        return mb_substr($page['excerpt'], 0, $maxLen);
    }
    if (!empty($page['subtitle'])) {
        return mb_substr($page['subtitle'], 0, $maxLen);
    }
    if (!empty($page['meta_description'])) {
        return mb_substr($page['meta_description'], 0, $maxLen);
    }
    if (!empty($page['content'])) {
        $plain = trim(strip_tags($page['content']));
        $plain = preg_replace('/\s+/', ' ', $plain);
        return mb_substr($plain, 0, $maxLen);
    }
    return '';
}

/**
 * Imagem de capa de uma página.
 *
 * Procura em ordem:
 *   featured_image → main_image → header_image → content1_image →
 *   primeira de content2_images_json → placeholder
 *
 * Retorna caminho RELATIVO (sem barra inicial) — combine com BASE_URL no template.
 */
function getCoverImage(array $page): string {
    foreach (['featured_image', 'main_image', 'header_image', 'content1_image'] as $field) {
        if (!empty($page[$field])) {
            return $page[$field];
        }
    }
    if (!empty($page['content2_images_json'])) {
        $imgs = json_decode($page['content2_images_json'], true);
        if (is_array($imgs) && !empty($imgs[0])) {
            return $imgs[0];
        }
    }
    return 'assets/img/placeholder.png';
}

/**
 * Formata uma data ISO (YYYY-MM-DD) em formato local.
 *
 *   formatDate('2026-05-25', 'br')  → '25 de maio de 2026'
 *   formatDate('2026-05-25', 'en')  → 'May 25, 2026'
 */
function formatDate(?string $date, string $lang = KALLME_DEFAULT_LANG): string {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }

    if ($lang === 'br') {
        $months = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março',
            4 => 'abril', 5 => 'maio', 6 => 'junho',
            7 => 'julho', 8 => 'agosto', 9 => 'setembro',
            10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];
        $day = (int) date('j', $ts);
        $month = $months[(int) date('n', $ts)];
        $year = date('Y', $ts);
        return "$day de $month de $year";
    }

    // EN (e qualquer outro idioma cai aqui por enquanto)
    return date('F j, Y', $ts);
}

/**
 * Tempo estimado de leitura em minutos.
 *
 * Base: 250 palavras por minuto (média).
 * Strip tags do HTML antes de contar palavras.
 *
 * @return int  Sempre >= 1.
 */
function calculateReadingTime(string $content): int {
    $text = trim(strip_tags($content));
    if ($text === '') {
        return 1;
    }
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $count = count($words);
    return max(1, (int) ceil($count / 250));
}
