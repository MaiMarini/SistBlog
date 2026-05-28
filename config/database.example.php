<?php
/**
 * TEMPLATE de config/database.php
 *
 * Copie este arquivo para `config/database.php` e preencha com os valores
 * reais do seu ambiente. O arquivo `config/database.php` está no .gitignore
 * justamente porque contém credenciais e chaves administrativas que não
 * devem ir para o repositório.
 *
 *   cp config/database.example.php config/database.php
 *   # depois edite config/database.php
 */

// ---------- Conexão com MySQL ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco_aqui');
define('DB_USER', 'seu_usuario_aqui');
define('DB_PASS', 'sua_senha_aqui');
define('DB_CHARSET', 'utf8mb4');

// ---------- Base URL ----------
// '/' para site na raiz; '/subpasta/' se estiver em subdiretório
define('BASE_URL', '/');

// ---------- Chaves dos endpoints administrativos ----------
// Use strings longas e aleatórias em produção (use um gerador).
// Não cometa estas chaves no Git — elas são o que protege /install.php
// (endpoint de deploy via HTTP POST) e /migrate.php (rodar migrações).
define('DEPLOY_KEY',  'TROQUE_PARA_UMA_STRING_LONGA_ALEATORIA');
define('MIGRATE_KEY', 'TROQUE_PARA_OUTRA_STRING_LONGA_ALEATORIA');

// ---------- PDO singleton ----------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
