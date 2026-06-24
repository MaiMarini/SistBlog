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

    // ---------- 4b. Colunas editoriais de citação em `categories` ----------
    echo "\n--- Colunas de citação em `categories` ---\n";
    $quoteCols = [
        "quote_text_br TEXT NULL",
        "quote_text_en TEXT NULL",
        "quote_author_br VARCHAR(200) NULL",
        "quote_author_en VARCHAR(200) NULL",
    ];
    $existingCatCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM categories") as $row) {
        $existingCatCols[$row['Field']] = true;
    }
    foreach ($quoteCols as $colDef) {
        $colName = explode(' ', $colDef)[0];
        if (isset($existingCatCols[$colName])) {
            echo "[ SKIP ] $colName (já existe)\n";
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE categories ADD COLUMN $colDef");
            echo "[  OK  ] $colName adicionado\n";
        } catch (PDOException $e) {
            echo "[ERROR] $colName: " . $e->getMessage() . "\n";
        }
    }

    // ---------- 4c. Colunas de cor por categoria ----------
    // color_bg (pastel) + color_text (escuro) — usadas nas pills dos cards
    // públicos. Fallback no template quando NULL.
    echo "\n--- Colunas de cor em `categories` ---\n";
    $colorCols = [
        "color_bg VARCHAR(7) DEFAULT NULL",
        "color_text VARCHAR(7) DEFAULT NULL",
    ];
    $existingCatCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM categories") as $row) {
        $existingCatCols[$row['Field']] = true;
    }
    foreach ($colorCols as $colDef) {
        $colName = explode(' ', $colDef)[0];
        if (isset($existingCatCols[$colName])) {
            echo "[ SKIP ] $colName (já existe)\n";
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE categories ADD COLUMN $colDef");
            echo "[  OK  ] $colName adicionado\n";
        } catch (PDOException $e) {
            echo "[ERROR] $colName: " . $e->getMessage() . "\n";
        }
    }

    // Seed da paleta inicial — só preenche onde ainda está NULL, pra
    // não sobrescrever escolhas que o admin já fez.
    echo "\n--- Seed de cores das categorias canônicas ---\n";
    $palette = [
        // slug          => [bg pastel, text escuro]
        'croche'         => ['#E0EDD8', '#4A6B3A'], // sage verde
        'jardinagem'     => ['#FCEAC4', '#8B6028'], // honey amarelo
        'cozinha'        => ['#F5D5D5', '#A04848'], // rosa accent
        'costura'        => ['#E5DEEC', '#4A4259'], // lavanda
        'bem-estar'      => ['#D5E5EC', '#2C5663'], // azul sereno
        'diy'            => ['#F0E6D8', '#705A3A'], // areia neutra
        'minha-estante'  => ['#E8DCD3', '#5C4533'], // papel envelhecido
    ];
    $updColors = $pdo->prepare("
        UPDATE categories
        SET color_bg = ?, color_text = ?
        WHERE slug = ?
          AND (color_bg IS NULL OR color_bg = '')
          AND (color_text IS NULL OR color_text = '')
    ");
    foreach ($palette as $slug => [$bg, $text]) {
        $updColors->execute([$bg, $text, $slug]);
        $n = $updColors->rowCount();
        if ($n > 0) {
            echo "[  OK  ] $slug ← bg=$bg / text=$text\n";
        } else {
            echo "[ SKIP ] $slug (já tem cor definida ou slug não existe)\n";
        }
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

    // ---------- 7. Tabela `crochet_stitches` (Fase Receita) ----------
    echo "\n--- Tabela `crochet_stitches` ---\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crochet_stitches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            name_br VARCHAR(100) NOT NULL,
            name_en VARCHAR(100) NULL,
            abbrev_br VARCHAR(20) NOT NULL,
            abbrev_en VARCHAR(20) NULL,
            image_url VARCHAR(500) NULL,
            description_br TEXT NULL,
            description_en TEXT NULL,
            tutorial_anchor VARCHAR(100) NULL,
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_order (is_active, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[  OK  ] Tabela `crochet_stitches` garantida\n";

    // ---------- 8. Seed dos 7 pontos básicos ----------
    echo "\n--- Seed de pontos básicos ---\n";
    $defaultStitches = [
        // [slug, name_br, name_en, abbrev_br, abbrev_en, image_url, tutorial_anchor, display_order]
        ['anel-magico',      'Anel Mágico',       'Magic Ring',          'AM',  'MR',    '/assets/img/icons/anel-magico.svg',      'anel-magico',      1],
        ['correntinha',      'Correntinha',       'Chain',               'c',   'ch',    '/assets/img/icons/correntinha.svg',      'correntinha',      2],
        ['ponto-baixissimo', 'Ponto Baixíssimo',  'Slip Stitch',         'pbx', 'sl st', '/assets/img/icons/ponto-baixissimo.svg', 'ponto-baixissimo', 3],
        ['ponto-baixo',      'Ponto Baixo',       'Single Crochet',      'pb',  'sc',    '/assets/img/icons/ponto-baixo.svg',      'ponto-baixo',      4],
        ['meio-ponto-alto',  'Meio Ponto Alto',   'Half Double Crochet', 'mpa', 'hdc',   '/assets/img/icons/meio-ponto-alto.svg',  'meio-ponto-alto',  5],
        ['ponto-alto',       'Ponto Alto',        'Double Crochet',      'pa',  'dc',    '/assets/img/icons/ponto-alto.svg',       'ponto-alto',       6],
        ['ponto-alto-duplo', 'Ponto Alto Duplo',  'Treble Crochet',      'pad', 'tr',    '/assets/img/icons/ponto-alto-duplo.svg', 'ponto-alto-duplo', 7],
    ];

    $checkSt = $pdo->prepare("SELECT id FROM crochet_stitches WHERE slug = ?");
    $insertSt = $pdo->prepare("
        INSERT INTO crochet_stitches
            (slug, name_br, name_en, abbrev_br, abbrev_en, image_url, tutorial_anchor, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    foreach ($defaultStitches as $st) {
        [$slug, $nameBr, $nameEn, $abBr, $abEn, $img, $anchor, $order] = $st;
        $checkSt->execute([$slug]);
        if ($checkSt->fetch()) {
            echo "[ SKIP ] $slug (já existe)\n";
            continue;
        }
        try {
            $insertSt->execute([$slug, $nameBr, $nameEn, $abBr, $abEn, $img, $anchor, $order]);
            echo "[  OK  ] $slug ($nameBr / $nameEn)\n";
        } catch (PDOException $e) {
            echo "[ERROR] $slug: " . $e->getMessage() . "\n";
        }
    }

    // ---------- 9. Schema de RECEITAS (Fase Receita B1) ----------
    echo "\n--- page_type ENUM (adicionar 'recipe') ---\n";
    $currentType = (string) $pdo->query("
        SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pages'
          AND COLUMN_NAME = 'page_type'
    ")->fetchColumn();

    if (strpos($currentType, "'recipe'") !== false) {
        echo "[ SKIP ] page_type já aceita 'recipe'\n";
    } else {
        try {
            $pdo->exec("
                ALTER TABLE pages
                MODIFY COLUMN page_type ENUM('article','static','recipe')
                NOT NULL DEFAULT 'article'
            ");
            echo "[  OK  ] page_type expandido pra ENUM('article','static','recipe')\n";
        } catch (PDOException $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
        }
    }

    // ---------- 10. Colunas específicas de receita em `pages` ----------
    echo "\n--- Colunas de receita em `pages` ---\n";
    $recipeCols = [
        "difficulty ENUM('beginner','intermediate','advanced') NULL",
        "estimated_time VARCHAR(100) NULL",
        "piece_type ENUM('amigurumi','wearable','decor','accessory','other') NULL",
        "final_size VARCHAR(200) NULL",
        "yarn_recommended TEXT NULL",
        "hook_size VARCHAR(20) NULL",
        "is_free TINYINT(1) DEFAULT NULL",
    ];
    $existingPagesCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM pages") as $row) {
        $existingPagesCols[$row['Field']] = true;
    }
    foreach ($recipeCols as $colDef) {
        $colName = explode(' ', $colDef)[0];
        if (isset($existingPagesCols[$colName])) {
            echo "[ SKIP ] $colName (já existe)\n";
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE pages ADD COLUMN $colDef");
            echo "[  OK  ] $colName adicionado\n";
        } catch (PDOException $e) {
            echo "[ERROR] $colName: " . $e->getMessage() . "\n";
        }
    }

    // ---------- 11. Tabela `recipe_stitches` (relação N:N) ----------
    echo "\n--- Tabela `recipe_stitches` ---\n";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS recipe_stitches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                stitch_id INT NOT NULL,
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_recipe_stitch (page_id, stitch_id),
                INDEX idx_page (page_id),
                INDEX idx_stitch (stitch_id),
                CONSTRAINT fk_recipe_stitches_page
                    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
                CONSTRAINT fk_recipe_stitches_stitch
                    FOREIGN KEY (stitch_id) REFERENCES crochet_stitches(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "[  OK  ] Tabela `recipe_stitches` garantida\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }

    // ---------- 10b. Corrigir `is_free` em `pages`: aceitar NULL ----------
    // Em instalações antigas a coluna foi criada com NOT NULL DEFAULT 1, o que
    // quebra publicação de artigos/estáticas (Column 'is_free' cannot be null).
    // is_free só faz sentido pra receitas → NULL pra artigo/estática.
    echo "\n--- Coluna `is_free` em `pages` (corrigir NULL) ---\n";
    $isFreeCol = $pdo->query("
        SELECT IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pages'
          AND COLUMN_NAME = 'is_free'
    ")->fetch();

    if (!$isFreeCol) {
        echo "[ SKIP ] coluna is_free não existe (instalação muito antiga; rode o migrate da Fase B1 primeiro)\n";
    } elseif ($isFreeCol['IS_NULLABLE'] === 'YES') {
        echo "[ SKIP ] is_free já aceita NULL\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE pages MODIFY COLUMN is_free TINYINT(1) DEFAULT NULL");
            echo "[  OK  ] is_free agora aceita NULL\n";

            // Limpa is_free de artigos/estáticas (não-receitas)
            $cleared = $pdo->exec("UPDATE pages SET is_free = NULL WHERE page_type != 'recipe'");
            echo "[  OK  ] $cleared linha(s) não-receita normalizadas pra NULL\n";

            // Garante que receitas sem is_free definido fiquem em 0 (paga por default)
            $defaulted = $pdo->exec("UPDATE pages SET is_free = 0 WHERE page_type = 'recipe' AND is_free IS NULL");
            echo "[  OK  ] $defaulted receita(s) sem is_free normalizadas pra 0 (paga)\n";
        } catch (PDOException $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
        }
    }

    // ---------- 11b. Coluna `photo_url` em crochet_stitches (Fase R-A2 / foto real) ----------
    echo "\n--- Coluna `photo_url` em `crochet_stitches` ---\n";
    $hasPhotoUrl = false;
    foreach ($pdo->query("SHOW COLUMNS FROM crochet_stitches") as $col) {
        if ($col['Field'] === 'photo_url') { $hasPhotoUrl = true; break; }
    }
    if ($hasPhotoUrl) {
        echo "[ SKIP ] photo_url (já existe)\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE crochet_stitches ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL AFTER image_url");
            echo "[  OK  ] photo_url adicionado\n";
        } catch (PDOException $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
        }
    }

    // ---------- 12. Tabela `recipe_blocks` (Fase Receita C1) ----------
    echo "\n--- Tabela `recipe_blocks` ---\n";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS recipe_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                block_type ENUM(
                    'steps',
                    'tip',
                    'step_photos',
                    'stitch_guide_inline',
                    'notes',
                    'color_guide'
                ) NOT NULL,
                block_data JSON NOT NULL,
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_page (page_id),
                INDEX idx_page_order (page_id, display_order),
                CONSTRAINT fk_recipe_blocks_page
                    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "[  OK  ] Tabela `recipe_blocks` garantida (ENUM com os 6 tipos previstos)\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }

    echo "\n✅ Migração concluída!\n";

} catch (PDOException $e) {
    echo "Erro de banco: " . $e->getMessage();
}
