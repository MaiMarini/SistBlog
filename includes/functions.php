<?php
require_once __DIR__ . '/../config/database.php';

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[áàãâä]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòõôö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = preg_replace('/[ñ]/u', 'n', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uploadImage(array $file, string $subdir = ''): string|false {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    if ($file['size'] > $maxSize) {
        return false;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/' . $subdir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . ($subdir ? $subdir . '/' : '') . $filename;
    }
    return false;
}

/**
 * Busca uma página publicada por slug.
 *
 * Após a migração bilíngue, slug não é mais único por si só — pode repetir
 * em idiomas diferentes. Por isso adicionamos $language como filtro opcional.
 *
 * @param string      $slug      Slug da página.
 * @param string|null $language  Filtra por idioma ('br', 'en', ...). null = qualquer.
 * @param string|null $category  Filtra por categoria (slug). null = qualquer.
 */
function getPage(string $slug, ?string $language = null, ?string $category = null): array|false {
    $pdo = getDB();
    $sql = "SELECT * FROM pages WHERE slug = ? AND status = 'published'";
    $params = [$slug];

    if ($language !== null) {
        $sql .= " AND language = ?";
        $params[] = $language;
    }
    if ($category !== null) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function getPageById(int $id): array|false {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Lista todas as páginas (todos os status) — uso administrativo.
 *
 * Aceita filtros opcionais:
 *   - language    string   ex: 'br'
 *   - page_type   string   'article' | 'static'
 *   - category    string   slug da categoria
 *   - status      string   'draft' ou 'published'
 */
function getAllPages(array $filters = []): array {
    $pdo = getDB();

    $where = [];
    $params = [];
    foreach (['language', 'page_type', 'category', 'status'] as $key) {
        if (!empty($filters[$key])) {
            $where[] = "$key = ?";
            $params[] = $filters[$key];
        }
    }

    $sql = "SELECT id, title, slug, template, status, language, page_type, category,
                   publish_date, created_at
            FROM pages";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function savePage(array $data, ?int $id = null): int|false {
    $pdo = getDB();

    // Whitelist de colunas editoriais + campos de receita (Fase B1).
    // Reflete o schema atual da tabela `pages`.
    $fields = [
        // Editorial / SEO
        'title', 'slug', 'content', 'status',
        'language', 'page_type', 'category',
        'excerpt', 'featured_image', 'reading_time',
        'author_name', 'publish_date', 'template',
        'meta_title', 'meta_description', 'tracking_code',
        // Campos específicos de receita (page_type='recipe')
        'difficulty', 'estimated_time', 'piece_type',
        'final_size', 'yarn_recommended', 'hook_size',
        'is_free',
    ];

    if ($id) {
        $sets = [];
        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        $values[] = $id;
        $sql = "UPDATE pages SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return $id;
    } else {
        $insertFields = [];
        $placeholders = [];
        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $insertFields[] = $field;
                $placeholders[] = '?';
                $values[] = $data[$field];
            }
        }
        $sql = "INSERT INTO pages (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return (int)$pdo->lastInsertId();
    }
}

function deletePage(int $id): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    return $stmt->execute([$id]);
}

function countPages(): array {
    $pdo = getDB();
    $total = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
    $published = $pdo->query("SELECT COUNT(*) FROM pages WHERE status = 'published'")->fetchColumn();
    $draft = $pdo->query("SELECT COUNT(*) FROM pages WHERE status = 'draft'")->fetchColumn();
    return ['total' => $total, 'published' => $published, 'draft' => $draft];
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Processa $_POST e $_FILES e retorna o array $data pronto para savePage().
 * Adiciona erros em $errors quando aplicável (uploads inválidos).
 *
 * Versão editorial enxuta — apenas os campos que existem na tabela `pages`
 * depois da remoção do sistema presell legado.
 */
function buildPageDataFromPost(array $post, array $files, array &$errors = []): array {
    $data = [
        'title'            => trim($post['title'] ?? ''),
        'slug'             => trim($post['slug'] ?? ''),
        'content'          => $post['content'] ?? '',
        'status'           => in_array($post['status'] ?? 'draft', ['draft', 'published'], true) ? $post['status'] : 'draft',
        'language'         => in_array($post['language'] ?? 'br', ['br', 'en'], true) ? $post['language'] : 'br',
        'page_type'        => in_array($post['page_type'] ?? 'article', ['article', 'recipe', 'static'], true) ? $post['page_type'] : 'article',
        'category'         => !empty($post['category']) ? trim($post['category']) : null,
        'excerpt'          => trim($post['excerpt'] ?? ''),
        'author_name'      => trim($post['author_name'] ?? '') ?: null,
        // Auto-fill: se publicado e sem data → hoje. Rascunho sem data → NULL.
        // Em edição, o form já vem pré-preenchido com o valor existente,
        // então o rule preserva publish_date original quando já existe.
        'publish_date'     => (function () use ($post) {
            $given = trim((string) ($post['publish_date'] ?? ''));
            if ($given !== '') return $given;
            $status = $post['status'] ?? 'draft';
            return $status === 'published' ? date('Y-m-d') : null;
        })(),
        'template'         => !empty($post['template']) ? trim($post['template']) : null, // hero variant
        'meta_title'       => trim($post['meta_title'] ?? ''),
        'meta_description' => trim($post['meta_description'] ?? ''),
        'tracking_code'    => $post['tracking_code'] ?? '',
        // Campos específicos de receita (coletados sempre; normalizados na seção de coerência abaixo)
        'difficulty'       => !empty($post['difficulty']) ? trim($post['difficulty']) : null,
        'estimated_time'   => !empty($post['estimated_time']) ? trim($post['estimated_time']) : null,
        'piece_type'       => !empty($post['piece_type']) ? trim($post['piece_type']) : null,
        'final_size'       => !empty($post['final_size']) ? trim($post['final_size']) : null,
        'yarn_recommended' => !empty($post['yarn_recommended']) ? trim($post['yarn_recommended']) : null,
        'hook_size'        => !empty($post['hook_size']) ? trim($post['hook_size']) : null,
        'is_free'          => !empty($post['is_free']) ? 1 : 0,
    ];

    // Slug obrigatório (auto-gera se vazio)
    $data['slug'] = empty($data['slug']) ? slugify($data['title']) : slugify($data['slug']);
    if (empty($data['title'])) $errors[] = 'O título é obrigatório.';
    if (empty($data['slug']))  $errors[] = 'O slug é obrigatório.';

    // Upload da featured_image (única imagem ainda persistida no schema editorial)
    if (!empty($files['featured_image']['name']) && $files['featured_image']['error'] === UPLOAD_ERR_OK) {
        $featuredPath = uploadImage($files['featured_image']);
        if ($featuredPath) {
            $data['featured_image'] = $featuredPath;
        } else {
            $errors[] = 'Erro no upload da imagem destacada.';
        }
    } elseif (!empty($post['featured_image_existing'])) {
        $data['featured_image'] = $post['featured_image_existing'];
    }

    // reading_time: aceita override do form, senão calcula automaticamente para artigos
    if (isset($post['reading_time']) && $post['reading_time'] !== '') {
        $data['reading_time'] = (int) $post['reading_time'];
    } elseif ($data['page_type'] === 'article' && !empty($data['content'])) {
        if (function_exists('calculateReadingTime')) {
            $data['reading_time'] = calculateReadingTime($data['content']);
        } else {
            $text = trim(strip_tags($data['content']));
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            $data['reading_time'] = max(1, (int) ceil(count($words) / 250));
        }
    } else {
        $data['reading_time'] = null;
    }

    // ============================================================
    // Coerência por page_type — zera/exige campos conforme o tipo
    // ============================================================
    $pageType = $data['page_type'];

    // Limpa campos de receita por padrão; só preserva no branch de receita
    $clearRecipeFields = function (array &$data) {
        $data['difficulty']       = null;
        $data['estimated_time']   = null;
        $data['piece_type']       = null;
        $data['final_size']       = null;
        $data['yarn_recommended'] = null;
        $data['hook_size']        = null;
        $data['is_free']          = null;
    };

    if ($pageType === 'static') {
        // Estática: zera categoria, template e tudo de receita
        $data['category'] = null;
        $data['template'] = null;
        $clearRecipeFields($data);

    } elseif ($pageType === 'article') {
        // Artigo: precisa categoria + valida hero (allowlist com fallback)
        if (empty($data['category'])) {
            $errors[] = 'Artigos precisam de categoria. Selecione uma categoria ou marque como Estática.';
        }
        $allowedHeros = ['hero-classic', 'hero-side', 'hero-minimal'];
        $template = $data['template'] ?? 'hero-classic';
        $data['template'] = in_array($template, $allowedHeros, true) ? $template : 'hero-classic';
        $clearRecipeFields($data);

    } elseif ($pageType === 'recipe') {
        // Receita: precisa categoria, NÃO usa hero (template=null), valida ENUMs
        if (empty($data['category'])) {
            $errors[] = 'Receitas precisam de categoria.';
        }
        $data['template'] = null;

        $allowedDifficulties = ['beginner', 'intermediate', 'advanced'];
        if (empty($data['difficulty']) || !in_array($data['difficulty'], $allowedDifficulties, true)) {
            $errors[] = 'Receitas precisam de dificuldade (Iniciante / Intermediário / Avançado).';
            $data['difficulty'] = null;
        }

        $allowedPieceTypes = ['amigurumi', 'wearable', 'decor', 'accessory', 'other'];
        if (!empty($data['piece_type']) && !in_array($data['piece_type'], $allowedPieceTypes, true)) {
            $data['piece_type'] = null;
        }
        // is_free já foi normalizado pra 0/1 acima; preserva
    }

    return $data;
}

/**
 * Variantes de hero para artigos. A coluna `pages.template` armazena uma dessas chaves.
 * Sistema de blocos modulares completo será adicionado na próxima fase.
 */
function getTemplates(): array {
    return [
        'hero-classic' => 'Hero clássico',
        'hero-side'    => 'Hero lateral',
        'hero-minimal' => 'Hero minimalista',
    ];
}

/**
 * Busca um artigo publicado por slug + idioma + categoria.
 * Diferente de getPage() porque exige page_type = 'article' explicitamente.
 */
function findArticle(string $slug, string $language, string $category): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT *
        FROM pages
        WHERE slug = ?
          AND language = ?
          AND category = ?
          AND page_type = 'article'
          AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug, $language, $category]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Lista conteúdo publicado de uma categoria (artigos + receitas),
 * do mais novo pro mais antigo. Ordena por publish_date (cai pra created_at se NULL).
 *
 * Inclui campos extras de receita (page_type, difficulty, estimated_time, is_free)
 * pra o template renderizar selos diferenciados sem precisar de query extra.
 *
 * @param string $categorySlug Slug da categoria.
 * @param string $language     Idioma ('br'|'en').
 * @param int    $limit        0 = sem limite.
 * @param int    $offset       Offset pra paginação (default 0).
 */
function getCategoryContent(string $categorySlug, string $language, int $limit = 0, int $offset = 0): array {
    $pdo = getDB();

    $sql = "
        SELECT id, title, slug, excerpt, featured_image,
               reading_time, publish_date, created_at, author_name,
               page_type, difficulty, estimated_time, is_free
        FROM pages
        WHERE category = ?
          AND language = ?
          AND page_type IN ('article', 'recipe')
          AND status = 'published'
        ORDER BY COALESCE(publish_date, created_at) DESC, id DESC
    ";

    if ($limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
        if ($offset > 0) {
            $sql .= " OFFSET " . (int) $offset;
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categorySlug, $language]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Alias de getCategoryContent() — mantido pra compatibilidade com chamadas
 * anteriores que esperavam apenas artigos. Hoje devolve artigos + receitas.
 *
 * @deprecated Use getCategoryContent() diretamente.
 */
function getCategoryArticles(string $categorySlug, string $language, int $limit = 0, int $offset = 0): array {
    return getCategoryContent($categorySlug, $language, $limit, $offset);
}

/**
 * Busca conteúdo relacionado da mesma categoria (excluindo o atual),
 * ordenados pela data de publicação (cai pra created_at se publish_date for NULL).
 *
 * Retorna artigos + receitas — quem está lendo um artigo de crochê deve
 * ver receitas relacionadas da mesma categoria, e vice-versa.
 */
function getRelatedArticles(int $excludeId, string $category, string $language, int $limit = 3): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, title, slug, excerpt, featured_image, reading_time, publish_date, created_at,
               page_type, difficulty, estimated_time, is_free
        FROM pages
        WHERE category = ?
          AND language = ?
          AND page_type IN ('article', 'recipe')
          AND status = 'published'
          AND id != ?
        ORDER BY COALESCE(publish_date, created_at) DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $category);
    $stmt->bindValue(2, $language);
    $stmt->bindValue(3, $excludeId, PDO::PARAM_INT);
    $stmt->bindValue(4, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
