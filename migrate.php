<?php
/**
 * MIGRATIONS — Kallme
 *
 * Script idempotente que evolui o schema do banco.
 * Roda via HTTP: kallme.online/migrate.php?key=<MIGRATE_KEY de config/database.php>
 *
 * O que faz:
 *  1. Adiciona colunas novas à tabela `pages` (incluindo i18n, page_type, categoria, etc.)
 *  2. Cria a tabela `categories` com índice composto
 *  3. Cria os índices de performance na tabela `pages`
 *  4. Remove o índice UNIQUE legado em `slug` (substituído por composto slug+language)
 *  5. Faz seed das categorias padrão em BR
 *
 * Idempotente — pode rodar quantas vezes precisar; só altera o que falta.
 */

require_once __DIR__ . '/config/database.php';

// Chave validada por constante definida em config/database.php (gitignored).
// Se não estiver definida, o endpoint é desabilitado por padrão.
if (!defined('MIGRATE_KEY')) {
    die('MIGRATE_KEY não configurada em config/database.php');
}
if (($_GET['key'] ?? '') !== MIGRATE_KEY) {
    die('Acesso negado');
}

header('Content-Type: text/plain; charset=utf-8');

// ============================================================
// 1. COLUNAS DA TABELA `pages`
// ============================================================
$columns = [
    // -- i18n / tipo / categoria / metadados editoriais (NOVOS)
    "language VARCHAR(5) DEFAULT 'br'",
    "page_type VARCHAR(20) DEFAULT 'presell'",
    "category VARCHAR(100) DEFAULT NULL",
    "excerpt TEXT DEFAULT NULL",
    "featured_image VARCHAR(500) DEFAULT ''",
    "reading_time INT DEFAULT NULL",

    // -- Cabeçalho (template structured)
    "header_bg_type VARCHAR(20) DEFAULT 'solid'",
    "header_bg_direction VARCHAR(20) DEFAULT 'to bottom'",
    "header_bg_color1 VARCHAR(7) DEFAULT '#ffffff'",
    "header_bg_color2 VARCHAR(7) DEFAULT ''",
    "header_bg_color3 VARCHAR(7) DEFAULT ''",
    "header_text LONGTEXT",
    "header_image VARCHAR(500) DEFAULT ''",
    "header_text_below LONGTEXT",

    // -- Conteúdo 1
    "content1_text LONGTEXT",
    "content1_image VARCHAR(500) DEFAULT ''",
    "content1_bg_image VARCHAR(500) DEFAULT ''",
    "content1_bg_color VARCHAR(7) DEFAULT '#ffffff'",

    // -- Conteúdo 2
    "content2_images_json LONGTEXT",
    "content2_text LONGTEXT",
    "content2_cta_text VARCHAR(100) DEFAULT 'Saiba Mais'",
    "content2_cta_color VARCHAR(7) DEFAULT '#e85d04'",
    "content2_cta_text_color VARCHAR(7) DEFAULT '#ffffff'",
    "content2_bg_image VARCHAR(500) DEFAULT ''",
    "content2_bg_color VARCHAR(7) DEFAULT '#f8f9fa'",

    // -- Rodapé
    "footer_bg_color VARCHAR(7) DEFAULT '#1a1a2e'",
    "footer_alerts LONGTEXT",
    "footer_buttons_json LONGTEXT",
    "footer_btn_color VARCHAR(7) DEFAULT '#ffffff'",
    "footer_btn_size VARCHAR(10) DEFAULT '13px'",

    // -- Tracking
    "tracking_code LONGTEXT",
];

// ============================================================
// 2. ÍNDICES DA TABELA `pages`
// ============================================================
// Nome do índice → definição das colunas
$indexes = [
    'idx_lang_type_status' => '(language, page_type, status)',
    'idx_lang_category'    => '(language, category)',
    'idx_slug_lang'        => '(slug, language)',
];

// ============================================================
// 3. CATEGORIAS PADRÃO (schema bilíngue novo)
// ============================================================
// [slug, name_br, name_en, description_br, icon_type, icon_value, display_order]
// Costura foi REMOVIDA do escopo. "Minha estante" adicionada.
$defaultCategories = [
    ['croche',        'Crochê',        'Crochet',
        'Receitas, tutoriais e recomendações pra quem ama crochê',
        'phosphor', 'ph-light ph-hand-heart', 1],
    ['jardinagem',    'Jardinagem',    'Gardening',
        'Plantas de casa, horta, jardim e cultivo com amor',
        'phosphor', 'ph-light ph-flower-tulip', 2],
    ['diy',           'DIY Geral',     'DIY',
        'Faça-você-mesmo, projetos variados e decoração',
        'phosphor', 'ph-light ph-palette', 3],
    ['minha-estante', 'Minha estante', 'My Shelf',
        'Livros que andei lendo, recomendações e resenhas',
        'phosphor', 'ph-light ph-books', 4],
];

// ============================================================
// 4. SETTINGS PADRÃO
// ============================================================
// [setting_key, setting_group, setting_value, description]
$defaultSettings = [
    // Group: general
    ['site_name',          'general', 'Kallme',                                              'Nome do site (aparece em title e header)'],
    ['site_tagline_br',    'general', 'Crochê, jardinagem e trabalhos manuais com você',    'Tagline em português'],
    ['site_tagline_en',    'general', 'Crochet, gardening and crafts',                       'Tagline em inglês'],
    ['contact_email',      'general', 'contato@kallme.online',                               'E-mail de contato'],
    ['default_language',   'general', 'br',                                                  'Idioma padrão (br/en)'],

    // Group: tracking (códigos globais — injetados em todas as páginas)
    ['tracking_global_ga4',        'tracking', '', 'Google Analytics 4 (cole o script completo gtag)'],
    ['tracking_global_googleads',  'tracking', '', 'Google Ads (cole o script completo gtag)'],
    ['tracking_global_pinterest',  'tracking', '', 'Pinterest Tag (script ou meta tag)'],
    ['tracking_global_facebook',   'tracking', '', 'Facebook Pixel (script completo)'],
    ['tracking_global_tiktok',     'tracking', '', 'TikTok Pixel (script completo)'],
    ['tracking_global_custom',     'tracking', '', 'Qualquer outro código (Microsoft Clarity, Hotjar, etc.)'],

    // Group: social (URLs das redes sociais)
    ['social_pinterest',  'social', '', 'URL do perfil Pinterest'],
    ['social_instagram',  'social', '', 'URL do perfil Instagram'],
    ['social_facebook',   'social', '', 'URL do perfil Facebook'],
    ['social_youtube',    'social', '', 'URL do canal YouTube'],
    ['social_twitter',    'social', '', 'URL do perfil X / Twitter'],

    // Group: seo
    ['default_meta_description_br', 'seo', 'Trabalhos manuais, crochê e jardinagem', 'Meta description padrão (BR)'],
    ['default_meta_description_en', 'seo', 'Crafts, crochet and gardening',           'Meta description padrão (EN)'],
];

// ============================================================
// EXECUÇÃO
// ============================================================
try {
    $pdo = getDB();

    echo "=== MIGRAÇÃO KALLME ===\n\n";

    // ---------- 1. Colunas em `pages` ----------
    echo "--- Colunas em `pages` ---\n";
    $existingColumns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM pages") as $row) {
        $existingColumns[$row['Field']] = true;
    }

    foreach ($columns as $columnDef) {
        $columnName = explode(' ', $columnDef)[0];
        if (isset($existingColumns[$columnName])) {
            echo "[ SKIP ] $columnName (já existe)\n";
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE pages ADD COLUMN $columnDef");
            echo "[  OK  ] $columnName adicionado\n";
        } catch (PDOException $e) {
            echo "[ERROR] $columnName: " . $e->getMessage() . "\n";
        }
    }

    // ---------- 2. Remover UNIQUE legado em `slug` ----------
    // O sistema antigo tinha UNIQUE(slug). Agora a unicidade é por (slug, language).
    echo "\n--- Índice legado UNIQUE(slug) ---\n";
    $legacy = $pdo->query("SHOW INDEX FROM pages WHERE Key_name = 'slug' AND Non_unique = 0")->fetchAll();
    if (!empty($legacy)) {
        try {
            $pdo->exec("ALTER TABLE pages DROP INDEX slug");
            echo "[  OK  ] UNIQUE(slug) removido\n";
        } catch (PDOException $e) {
            echo "[ERROR] Falha ao remover UNIQUE(slug): " . $e->getMessage() . "\n";
        }
    } else {
        echo "[ SKIP ] UNIQUE(slug) já não existe\n";
    }

    // ---------- 3. Índices novos em `pages` ----------
    echo "\n--- Índices em `pages` ---\n";
    $existingIndexes = [];
    foreach ($pdo->query("SHOW INDEX FROM pages") as $row) {
        $existingIndexes[$row['Key_name']] = true;
    }

    foreach ($indexes as $name => $cols) {
        if (isset($existingIndexes[$name])) {
            echo "[ SKIP ] $name (já existe)\n";
            continue;
        }
        try {
            $pdo->exec("CREATE INDEX $name ON pages $cols");
            echo "[  OK  ] $name criado em $cols\n";
        } catch (PDOException $e) {
            echo "[ERROR] $name: " . $e->getMessage() . "\n";
        }
    }

    // ---------- 4. Tabela `categories` (schema bilíngue novo) ----------
    // O schema antigo tinha 1 linha por idioma (coluna `language`, `name`, `icon`).
    // O novo tem 1 linha por categoria com colunas name_br/name_en + icon_type/icon_value.
    // Detectamos pela presença da coluna `name_br`: se faltar, recriamos a tabela.
    echo "\n--- Tabela `categories` ---\n";
    $catHasNewSchema = false;
    try {
        foreach ($pdo->query("SHOW COLUMNS FROM categories") as $col) {
            if ($col['Field'] === 'name_br') { $catHasNewSchema = true; break; }
        }
    } catch (PDOException $e) {
        // tabela não existe ainda
    }

    if (!$catHasNewSchema) {
        $pdo->exec("DROP TABLE IF EXISTS categories");
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "[  OK  ] Tabela `categories` recriada no schema bilíngue novo\n";

        // ---------- 5. Seed das 4 categorias canônicas (sem Costura) ----------
        echo "\n--- Seed de categorias ---\n";
        $insert = $pdo->prepare("
            INSERT INTO categories
                (slug, name_br, name_en, description_br, icon_type, icon_value, display_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
        ");
        foreach ($defaultCategories as $cat) {
            [$slug, $nameBr, $nameEn, $descBr, $iconType, $iconValue, $order] = $cat;
            $insert->execute([$slug, $nameBr, $nameEn, $descBr, $iconType, $iconValue, $order]);
            echo "[  OK  ] $slug criada → $nameBr / $nameEn\n";
        }
    } else {
        echo "[ SKIP ] `categories` já está no schema novo (preserva edições)\n";
    }

    // ---------- 5b. Migração de page_type das páginas existentes ----------
    // Páginas institucionais/legais → 'static'. Templates de presell → 'presell'.
    // (No estado atual a tabela pages costuma estar vazia — as páginas
    //  institucionais são arquivos PHP — então isto afeta 0 linhas.)
    echo "\n--- Migração de page_type ---\n";
    $staticSlugs = "'home','sobre','contato','politica-de-privacidade','termos','divulgacao-afiliados','404'";
    $n1 = $pdo->exec("UPDATE pages SET page_type = 'static' WHERE slug IN ($staticSlugs)");
    echo "[  OK  ] $n1 página(s) → page_type='static'\n";

    $n2 = $pdo->exec("UPDATE pages SET page_type = 'presell'
                      WHERE template IN ('advertorial','blog-personal','landing','structured','presell-A','presell-B','presell-C','presell-D')
                        AND (page_type IS NULL OR page_type = '' OR page_type = 'presell')");
    echo "[  OK  ] $n2 página(s) → page_type='presell'\n";

    // ---------- 5c. Reatribui páginas de Costura para DIY ----------
    $n3 = $pdo->exec("UPDATE pages SET category = 'diy' WHERE category = 'costura'");
    echo "[  OK  ] $n3 página(s) movida(s) de costura → diy\n";

    // ---------- 6. Tabela `settings` ----------
    echo "\n--- Tabela `settings` ---\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value LONGTEXT,
            setting_group VARCHAR(50) DEFAULT 'general',
            description VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_group (setting_group)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[  OK  ] Tabela `settings` garantida\n";

    // ---------- 7. Seed das settings padrão ----------
    echo "\n--- Seed de settings ---\n";
    $checkSet = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $insertSet = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_group, setting_value, description)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($defaultSettings as $row) {
        [$key, $group, $value, $description] = $row;
        $checkSet->execute([$key]);
        if ($checkSet->fetch()) {
            echo "[ SKIP ] $key (já existe)\n";
            continue;
        }
        try {
            $insertSet->execute([$key, $group, $value, $description]);
            echo "[  OK  ] $key ($group)\n";
        } catch (PDOException $e) {
            echo "[ERROR] $key: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Migração concluída!\n";
    echo "⚠ Considere remover ou renomear este arquivo em produção após uso.\n";

} catch (PDOException $e) {
    echo "Erro de banco: " . $e->getMessage();
}
