<?php
/**
 * MIGRATIONS — Kallme (Blog Editorial)
 *
 * Script idempotente que evolui o schema do banco.
 * Roda via HTTP: kallme.online/migrate.php?key=<MIGRATE_KEY de config/database.php>
 *
 * O que faz (idempotente):
 *  1. Garante colunas editoriais em `pages` (i18n, page_type, category, etc.)
 *  2. Cria índices de performance
 *  3. Cria tabela `categories` (schema bilíngue) + seed das 4 canônicas
 *  4. Cria tabela `settings` + seed das 18 chaves padrão
 *
 * NOTA: o sistema presell foi REMOVIDO no Cleanup. Este migrate.php não
 * cria nem garante colunas presell. Se uma instalação antiga ainda tem
 * essas colunas, elas continuam existindo (e devem ser removidas com
 * um script de cleanup específico).
 */

require_once __DIR__ . '/config/database.php';

if (!defined('MIGRATE_KEY')) {
    die('MIGRATE_KEY não configurada em config/database.php');
}
if (($_GET['key'] ?? '') !== MIGRATE_KEY) {
    die('Acesso negado');
}

header('Content-Type: text/plain; charset=utf-8');

// ============================================================
// 1. COLUNAS EDITORIAIS DA TABELA `pages`
// ============================================================
$columns = [
    "language VARCHAR(5) DEFAULT 'br'",
    "page_type ENUM('article','static') NOT NULL DEFAULT 'static'",
    "category VARCHAR(100) DEFAULT NULL",
    "excerpt TEXT DEFAULT NULL",
    "featured_image VARCHAR(500) DEFAULT ''",
    "reading_time INT DEFAULT NULL",
    "author_name VARCHAR(100) DEFAULT NULL",
    "publish_date DATE DEFAULT NULL",
    "meta_title VARCHAR(255) DEFAULT ''",
    "meta_description TEXT DEFAULT NULL",
    "tracking_code LONGTEXT",
    "template VARCHAR(30) DEFAULT NULL", // hero variant (article)
];

// ============================================================
// 2. ÍNDICES
// ============================================================
$indexes = [
    'idx_lang_type_status' => '(language, page_type, status)',
    'idx_lang_category'    => '(language, category)',
    'idx_slug_lang'        => '(slug, language)',
];

// ============================================================
// 3. CATEGORIAS PADRÃO (schema bilíngue)
// ============================================================
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
$defaultSettings = [
    // Group: general
    ['site_name',          'general', 'Kallme',                                              'Nome do site'],
    ['site_tagline_br',    'general', 'Crochê, jardinagem e trabalhos manuais com você',    'Tagline (BR)'],
    ['site_tagline_en',    'general', 'Crochet, gardening and crafts',                       'Tagline (EN)'],
    ['contact_email',      'general', 'contato@kallme.online',                               'E-mail de contato'],
    ['default_language',   'general', 'br',                                                  'Idioma padrão'],

    // Group: tracking (globais)
    ['tracking_global_ga4',        'tracking', '', 'Google Analytics 4 (gtag)'],
    ['tracking_global_googleads',  'tracking', '', 'Google Ads (gtag)'],
    ['tracking_global_pinterest',  'tracking', '', 'Pinterest Tag'],
    ['tracking_global_facebook',   'tracking', '', 'Facebook Pixel'],
    ['tracking_global_tiktok',     'tracking', '', 'TikTok Pixel'],
    ['tracking_global_custom',     'tracking', '', 'Outros (Clarity, Hotjar, etc.)'],

    // Group: social
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
    echo "=== MIGRAÇÃO KALLME (BLOG EDITORIAL) ===\n\n";

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
    echo "\n--- Índice legado UNIQUE(slug) ---\n";
    $legacy = $pdo->query("SHOW INDEX FROM pages WHERE Key_name = 'slug' AND Non_unique = 0")->fetchAll();
    if (!empty($legacy)) {
        try {
            $pdo->exec("ALTER TABLE pages DROP INDEX slug");
            echo "[  OK  ] UNIQUE(slug) removido\n";
        } catch (PDOException $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
        }
    } else {
        echo "[ SKIP ] UNIQUE(slug) já não existe\n";
    }

    // ---------- 3. Índices ----------
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

    // ---------- 4. Tabela `categories` (schema bilíngue) ----------
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
        echo "[  OK  ] Tabela `categories` recriada (schema bilíngue)\n";

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

    // ---------- 5. Tabela `settings` ----------
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

    // ---------- 6. Seed das settings ----------
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
            echo "[ SKIP ] $key\n";
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

} catch (PDOException $e) {
    echo "Erro de banco: " . $e->getMessage();
}
