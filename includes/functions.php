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
 *   - page_type   string   ex: 'article', 'presell'
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

    $fields = [
        'title', 'subtitle', 'slug', 'main_image', 'content',
        'affiliate_link', 'cta_text', 'cta_color', 'author_name',
        'author_avatar', 'publish_date', 'template', 'comments_json',
        'meta_title', 'meta_description', 'status',
        // i18n / categorização / metadados editoriais
        'language', 'page_type', 'category', 'excerpt', 'featured_image', 'reading_time',
        // Cabeçalho (template structured)
        'header_bg_type', 'header_bg_direction', 'header_bg_color1',
        'header_bg_color2', 'header_bg_color3', 'header_text', 'header_image', 'header_text_below',
        // Conteúdo 1
        'content1_text', 'content1_image', 'content1_bg_image', 'content1_bg_color',
        // Conteúdo 2
        'content2_images_json', 'content2_text', 'content2_cta_text', 'content2_cta_color', 'content2_cta_text_color',
        'content2_bg_image', 'content2_bg_color',
        // Rodapé
        'footer_bg_color', 'footer_alerts', 'footer_buttons_json',
        'footer_btn_color', 'footer_btn_size',
        'tracking_code',
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
 */
function buildPageDataFromPost(array $post, array $files, array &$errors = []): array {
    $data = [
        'title' => trim($post['title'] ?? ''),
        'subtitle' => trim($post['subtitle'] ?? ''),
        'slug' => trim($post['slug'] ?? ''),
        'content' => $post['content'] ?? '',
        // i18n / tipo / categoria / metadados editoriais
        'language' => in_array($post['language'] ?? 'br', ['br', 'en'], true) ? $post['language'] : 'br',
        'page_type' => in_array($post['page_type'] ?? 'presell', ['article', 'presell', 'static', 'home'], true) ? $post['page_type'] : 'presell',
        'category' => !empty($post['category']) ? trim($post['category']) : null,
        'excerpt' => trim($post['excerpt'] ?? ''),
        'affiliate_link' => trim($post['affiliate_link'] ?? ''),
        'cta_text' => trim($post['cta_text'] ?? 'Saiba Mais'),
        'cta_color' => trim($post['cta_color'] ?? '#e85d04'),
        'author_name' => trim($post['author_name'] ?? 'Redação'),
        'publish_date' => $post['publish_date'] ?? date('Y-m-d'),
        'template' => $post['template'] ?? 'structured',
        'meta_title' => trim($post['meta_title'] ?? ''),
        'meta_description' => trim($post['meta_description'] ?? ''),
        'status' => $post['status'] ?? 'draft',
        // Estruturado - cabeçalho
        'header_bg_type' => $post['header_bg_type'] ?? 'solid',
        'header_bg_direction' => $post['header_bg_direction'] ?? 'to bottom',
        'header_bg_color1' => $post['header_bg_color1'] ?? '#ffffff',
        'header_bg_color2' => $post['header_bg_color2'] ?? '',
        'header_bg_color3' => $post['header_bg_color3'] ?? '',
        'header_text' => $post['header_text'] ?? '',
        'header_text_below' => $post['header_text_below'] ?? '',
        // Conteúdo 1
        'content1_text' => $post['content1_text'] ?? '',
        'content1_bg_color' => $post['content1_bg_color'] ?? '#ffffff',
        // Conteúdo 2
        'content2_text' => $post['content2_text'] ?? '',
        'content2_cta_text' => trim($post['content2_cta_text'] ?? 'Saiba Mais'),
        'content2_cta_color' => $post['content2_cta_color'] ?? '#e85d04',
        'content2_cta_text_color' => $post['content2_cta_text_color'] ?? '#ffffff',
        'content2_bg_color' => $post['content2_bg_color'] ?? '#f8f9fa',
        // Rodapé
        'footer_bg_color' => $post['footer_bg_color'] ?? '#1a1a2e',
        'footer_alerts' => $post['footer_alerts'] ?? '',
        'footer_btn_color' => $post['footer_btn_color'] ?? '#ffffff',
        'footer_btn_size' => $post['footer_btn_size'] ?? '13px',
        'tracking_code' => $post['tracking_code'] ?? '',
    ];

    // Slug
    if (empty($data['slug'])) {
        $data['slug'] = slugify($data['title']);
    } else {
        $data['slug'] = slugify($data['slug']);
    }

    if (empty($data['title'])) {
        $errors[] = 'O título é obrigatório.';
    }
    if (empty($data['slug'])) {
        $errors[] = 'O slug é obrigatório.';
    }

    // Upload de imagem principal (templates clássicos)
    if (!empty($files['main_image']['name']) && $files['main_image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($files['main_image']);
        if ($imagePath) {
            $data['main_image'] = $imagePath;
        } else {
            $errors[] = 'Erro no upload da imagem principal.';
        }
    }

    // Avatar do autor
    if (!empty($files['author_avatar']['name']) && $files['author_avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarPath = uploadImage($files['author_avatar'], 'avatars');
        if ($avatarPath) {
            $data['author_avatar'] = $avatarPath;
        }
    }

    // Imagem destacada (featured_image) — usada em cards de blog e SEO
    if (!empty($files['featured_image']['name']) && $files['featured_image']['error'] === UPLOAD_ERR_OK) {
        $featuredPath = uploadImage($files['featured_image']);
        if ($featuredPath) {
            $data['featured_image'] = $featuredPath;
        } else {
            $errors[] = 'Erro no upload da featured_image.';
        }
    } elseif (!empty($post['featured_image_existing'])) {
        $data['featured_image'] = $post['featured_image_existing'];
    }

    // Imagens estruturadas (header, content1, content1_bg)
    foreach (['header_image', 'content1_image', 'content1_bg_image', 'content2_bg_image'] as $field) {
        if (!empty($files[$field]['name']) && $files[$field]['error'] === UPLOAD_ERR_OK) {
            $path = uploadImage($files[$field]);
            if ($path) {
                $data[$field] = $path;
            } else {
                $errors[] = "Erro no upload de $field.";
            }
        } elseif (!empty($post[$field . '_existing'])) {
            $data[$field] = $post[$field . '_existing'];
        }
    }

    // Conteúdo 2 - múltiplas imagens (até 5)
    $content2Images = [];
    if (!empty($files['content2_images']['name']) && is_array($files['content2_images']['name'])) {
        foreach ($files['content2_images']['name'] as $i => $name) {
            if (empty($name) || $files['content2_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $singleFile = [
                'name' => $name,
                'type' => $files['content2_images']['type'][$i],
                'tmp_name' => $files['content2_images']['tmp_name'][$i],
                'error' => $files['content2_images']['error'][$i],
                'size' => $files['content2_images']['size'][$i],
            ];
            $imgPath = uploadImage($singleFile);
            if ($imgPath) {
                $content2Images[] = $imgPath;
            }
            if (count($content2Images) >= 5) break;
        }
    }
    // Se não enviou novas, manter as existentes
    if (empty($content2Images) && !empty($post['content2_images_existing']) && is_array($post['content2_images_existing'])) {
        $content2Images = array_slice(array_filter($post['content2_images_existing']), 0, 5);
    }
    $data['content2_images_json'] = json_encode($content2Images, JSON_UNESCAPED_UNICODE);

    // Comentários (templates clássicos)
    $comments = [];
    if (!empty($post['comment_name']) && is_array($post['comment_name'])) {
        foreach ($post['comment_name'] as $i => $name) {
            $name = trim($name);
            $text = trim($post['comment_text'][$i] ?? '');
            $date = trim($post['comment_date'][$i] ?? '');
            if (!empty($name) && !empty($text)) {
                $comments[] = [
                    'name' => $name,
                    'text' => $text,
                    'date' => $date ?: date('d/m/Y'),
                ];
            }
        }
    }
    $data['comments_json'] = json_encode($comments, JSON_UNESCAPED_UNICODE);

    // Rodapé - 3 botões
    $footerBtns = [];
    if (!empty($post['footer_btn_text']) && is_array($post['footer_btn_text'])) {
        foreach ($post['footer_btn_text'] as $i => $text) {
            $text = trim($text);
            $link = trim($post['footer_btn_link'][$i] ?? '');
            if (!empty($text)) {
                $footerBtns[] = ['text' => $text, 'link' => $link];
            }
        }
    }
    $data['footer_buttons_json'] = json_encode($footerBtns, JSON_UNESCAPED_UNICODE);

    // reading_time: aceita override do form ($post), senão calcula a partir do content
    // (só faz sentido para page_type='article'; para presell pode ficar NULL).
    if (isset($post['reading_time']) && $post['reading_time'] !== '') {
        $data['reading_time'] = (int) $post['reading_time'];
    } elseif ($data['page_type'] === 'article' && !empty($data['content'])) {
        // Usa o helper se disponível; senão faz cálculo inline.
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

    return $data;
}

function getTemplates(): array {
    return [
        'structured' => 'Estruturado (4 Seções)',
        'advertorial' => 'Advertorial / Notícia',
        'blog-personal' => 'Blog Pessoal',
        'landing' => 'Landing Page',
    ];
}
