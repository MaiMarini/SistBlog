<?php
/**
 * SETTINGS — Kallme
 *
 * Acesso às configurações globais (tabela `settings`) com cache em memória.
 * Carregue UMA vez por request com loadAllSettings(); as funções abaixo
 * leem do cache sem novas queries.
 *
 * Padrão de uso:
 *   require_once includes/settings.php;
 *   $name = getSetting('site_name', 'Kallme');
 *   $tracking = getSettings('tracking');
 */

require_once __DIR__ . '/../config/database.php';

// Cache em memória dentro do request.
// Estrutura: $GLOBALS['_settings'] = ['site_name' => ['value' => '...', 'group' => 'general'], ...]
if (!isset($GLOBALS['_settings'])) {
    $GLOBALS['_settings'] = null; // null = ainda não carregado; array = carregado
}

/**
 * Carrega todas as settings do banco para o cache em memória.
 * Idempotente: chamadas subsequentes não fazem nova query.
 */
function loadAllSettings(): void {
    if ($GLOBALS['_settings'] !== null) {
        return;
    }
    try {
        $pdo = getDB();
        $rows = $pdo->query("SELECT setting_key, setting_value, setting_group FROM settings")->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = [
                'value' => $row['setting_value'] ?? '',
                'group' => $row['setting_group'] ?? 'general',
            ];
        }
        $GLOBALS['_settings'] = $cache;
    } catch (PDOException $e) {
        // Se a tabela ainda não existe (migrate não rodou), trata como vazio em vez de quebrar
        $GLOBALS['_settings'] = [];
    }
}

/**
 * Retorna o valor de uma setting (ou o default se não existir).
 *
 *   getSetting('site_name')                  → 'Kallme'
 *   getSetting('tracking_global_ga4', '')    → '' se não definido
 *
 * @param string $key      Chave da setting (ex: 'site_name')
 * @param string $default  Valor de fallback
 */
function getSetting(string $key, string $default = ''): string {
    loadAllSettings();
    if (!isset($GLOBALS['_settings'][$key])) {
        return $default;
    }
    return (string) ($GLOBALS['_settings'][$key]['value'] ?? $default);
}

/**
 * Retorna um dicionário [key => value] de settings.
 *
 *   getSettings()             → todas
 *   getSettings('tracking')   → só do grupo 'tracking'
 *
 * @param string|null $group  Se informado, filtra pelo grupo.
 * @return array<string,string>
 */
function getSettings(?string $group = null): array {
    loadAllSettings();
    $result = [];
    foreach ($GLOBALS['_settings'] as $key => $row) {
        if ($group !== null && ($row['group'] ?? 'general') !== $group) {
            continue;
        }
        $result[$key] = (string) ($row['value'] ?? '');
    }
    return $result;
}

/**
 * Salva (insert ou update) uma setting individual.
 * Atualiza também o cache em memória.
 *
 * @return bool  true se a operação rodou sem exceção.
 */
function setSetting(string $key, string $value, string $group = 'general'): bool {
    try {
        $pdo = getDB();
        // INSERT ... ON DUPLICATE KEY UPDATE — atomic upsert no MySQL
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_group = VALUES(setting_group)
        ");
        $stmt->execute([$key, $value, $group]);

        // Atualiza cache em memória
        if ($GLOBALS['_settings'] === null) {
            loadAllSettings();
        }
        $GLOBALS['_settings'][$key] = ['value' => $value, 'group' => $group];
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Atalho para imprimir o valor já escapado (HTML-safe) em um atributo HTML.
 * NÃO use para tracking codes (que precisam ser HTML cru).
 */
function settingAttr(string $key, string $default = ''): string {
    return htmlspecialchars(getSetting($key, $default), ENT_QUOTES, 'UTF-8');
}
