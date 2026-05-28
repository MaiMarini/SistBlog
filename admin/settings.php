<?php
/**
 * ADMIN — CONFIGURAÇÕES GLOBAIS
 *
 * Edição em massa de todas as settings (tabela `settings`) agrupadas em abas:
 *   - general:   nome, taglines, e-mail, idioma padrão
 *   - tracking:  códigos de tracking globais
 *   - social:    URLs de redes sociais
 *   - seo:       meta descriptions padrão
 *
 * O form envia todas as settings de uma vez. CSRF obrigatório.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings.php';
requireLogin();

$activePage = 'settings';

// ---------- Definição das abas e campos ----------
// A ordem aqui define a ordem na UI. O tipo controla o widget:
//   'text'      → <input type="text">
//   'email'     → <input type="email">
//   'url'       → <input type="url">
//   'select'    → <select> (precisa de 'options')
//   'textarea'  → <textarea> (default 4 linhas)
//   'code'      → <textarea> monoespaçado para HTML/JS (8+ linhas)
$tabs = [
    'general' => [
        'label' => '⚙️ Geral',
        'fields' => [
            ['key' => 'site_name',        'label' => 'Nome do site',         'type' => 'text'],
            ['key' => 'site_tagline_br',  'label' => 'Tagline (português)',  'type' => 'text'],
            ['key' => 'site_tagline_en',  'label' => 'Tagline (inglês)',     'type' => 'text'],
            ['key' => 'contact_email',    'label' => 'E-mail de contato',    'type' => 'email'],
            ['key' => 'default_language', 'label' => 'Idioma padrão',        'type' => 'select',
                'options' => ['br' => 'Português (br)', 'en' => 'English (en)']],
        ],
    ],
    'tracking' => [
        'label' => '📊 Tracking Global',
        'fields' => [
            ['key' => 'tracking_global_ga4',       'label' => 'Google Analytics 4 (gtag)', 'type' => 'code'],
            ['key' => 'tracking_global_googleads', 'label' => 'Google Ads (gtag)',          'type' => 'code'],
            ['key' => 'tracking_global_pinterest', 'label' => 'Pinterest Tag',              'type' => 'code'],
            ['key' => 'tracking_global_facebook',  'label' => 'Facebook Pixel',             'type' => 'code'],
            ['key' => 'tracking_global_tiktok',    'label' => 'TikTok Pixel',               'type' => 'code'],
            ['key' => 'tracking_global_custom',    'label' => 'Outros (Clarity, Hotjar, etc.)', 'type' => 'code'],
        ],
    ],
    'social' => [
        'label' => '🔗 Redes Sociais',
        'fields' => [
            ['key' => 'social_pinterest', 'label' => 'Pinterest',  'type' => 'url'],
            ['key' => 'social_instagram', 'label' => 'Instagram',  'type' => 'url'],
            ['key' => 'social_facebook',  'label' => 'Facebook',   'type' => 'url'],
            ['key' => 'social_youtube',   'label' => 'YouTube',    'type' => 'url'],
            ['key' => 'social_twitter',   'label' => 'X / Twitter','type' => 'url'],
        ],
    ],
    'seo' => [
        'label' => '🔍 SEO',
        'fields' => [
            ['key' => 'default_meta_description_br', 'label' => 'Meta description padrão (BR)', 'type' => 'textarea'],
            ['key' => 'default_meta_description_en', 'label' => 'Meta description padrão (EN)', 'type' => 'textarea'],
        ],
    ],
];

// ---------- POST: salvar settings ----------
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Tente novamente.';
    } else {
        $countSaved = 0;
        foreach ($tabs as $groupKey => $tab) {
            foreach ($tab['fields'] as $field) {
                $key = $field['key'];
                if (!array_key_exists($key, $_POST)) continue;
                // textarea / code mantém quebras de linha; demais usamos trim
                $value = in_array($field['type'], ['textarea', 'code'], true)
                    ? (string) $_POST[$key]
                    : trim((string) $_POST[$key]);
                if (setSetting($key, $value, $groupKey)) {
                    $countSaved++;
                }
            }
        }
        $success = $countSaved > 0;
    }
}

// Carrega valores atuais (sempre, mesmo após save, para refletir o estado)
loadAllSettings();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações — Presell Manager</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
    <style>
        /* Tabs */
        .settings-tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid #2a2a4a;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .settings-tab-btn {
            padding: 12px 20px;
            background: transparent;
            color: #888;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .settings-tab-btn:hover { color: #fff; }
        .settings-tab-btn.active {
            color: #e94560;
            border-bottom-color: #e94560;
        }
        .settings-tab-panel { display: none; }
        .settings-tab-panel.active { display: block; }
        textarea.code-input {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            line-height: 1.6;
            min-height: 180px;
        }
        textarea.text-input { min-height: 80px; }
        .field-help {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1>Configurações</h1>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success">Configurações salvas com sucesso.</div>
            <?php endif; ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST" class="page-form">
                <?= csrfField() ?>

                <!-- Botões de aba -->
                <div class="settings-tabs">
                    <?php $i = 0; foreach ($tabs as $tabKey => $tab): ?>
                        <button type="button"
                                class="settings-tab-btn <?= $i === 0 ? 'active' : '' ?>"
                                data-tab="<?= e($tabKey) ?>">
                            <?= e($tab['label']) ?>
                        </button>
                    <?php $i++; endforeach; ?>
                </div>

                <!-- Painéis -->
                <?php $i = 0; foreach ($tabs as $tabKey => $tab): ?>
                    <div class="settings-tab-panel <?= $i === 0 ? 'active' : '' ?>" id="panel-<?= e($tabKey) ?>">
                        <div class="card">
                            <div class="card-header"><h2><?= e($tab['label']) ?></h2></div>

                            <?php foreach ($tab['fields'] as $field):
                                $key = $field['key'];
                                $val = getSetting($key, '');
                                $type = $field['type'];
                                $description = $GLOBALS['_settings'][$key]['description'] ?? '';
                            ?>
                            <div class="form-group">
                                <label for="<?= e($key) ?>"><?= e($field['label']) ?></label>

                                <?php if ($type === 'select'): ?>
                                    <select id="<?= e($key) ?>" name="<?= e($key) ?>">
                                        <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                                            <option value="<?= e($optVal) ?>" <?= $val === $optVal ? 'selected' : '' ?>>
                                                <?= e($optLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($type === 'textarea'): ?>
                                    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" class="text-input" rows="4"><?= e($val) ?></textarea>

                                <?php elseif ($type === 'code'): ?>
                                    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>"
                                              class="code-input" rows="8"
                                              placeholder="Cole o código completo (incluindo &lt;script&gt;...&lt;/script&gt;)"><?= e($val) ?></textarea>

                                <?php else: /* text, email, url */ ?>
                                    <input type="<?= e($type) ?>"
                                           id="<?= e($key) ?>"
                                           name="<?= e($key) ?>"
                                           value="<?= e($val) ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $i++; endforeach; ?>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">💾 Salvar todas as configurações</button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Tabs simples por JS
        const tabBtns = document.querySelectorAll('.settings-tab-btn');
        const panels = document.querySelectorAll('.settings-tab-panel');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabKey = btn.dataset.tab;

                tabBtns.forEach(b => b.classList.toggle('active', b === btn));
                panels.forEach(p => {
                    p.classList.toggle('active', p.id === 'panel-' + tabKey);
                });

                // Atualiza URL hash para deep-link
                history.replaceState(null, '', '#' + tabKey);
            });
        });

        // Ao carregar, ativa aba do hash (se houver)
        if (window.location.hash) {
            const target = document.querySelector('[data-tab="' + window.location.hash.slice(1) + '"]');
            if (target) target.click();
        }
    </script>
</body>
</html>
