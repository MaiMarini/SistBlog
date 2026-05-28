<?php
require_once __DIR__ . '/config/database.php';

$messages = [];
$error = false;

try {
    $pdo = getDB();

    // Criar tabela users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Tabela 'users' criada com sucesso.";

    // Criar tabela pages
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255) DEFAULT '',
            slug VARCHAR(255) NOT NULL UNIQUE,
            main_image VARCHAR(500) DEFAULT '',
            content LONGTEXT,
            affiliate_link VARCHAR(500) DEFAULT '',
            cta_text VARCHAR(100) DEFAULT 'Saiba Mais',
            cta_color VARCHAR(7) DEFAULT '#e85d04',
            author_name VARCHAR(100) DEFAULT 'Redação',
            author_avatar VARCHAR(500) DEFAULT '',
            publish_date DATE DEFAULT NULL,
            template VARCHAR(50) DEFAULT 'advertorial',
            comments_json LONGTEXT DEFAULT NULL,
            meta_title VARCHAR(255) DEFAULT '',
            meta_description TEXT DEFAULT NULL,
            status ENUM('draft','published') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "Tabela 'pages' criada com sucesso.";

    // Criar usuário admin padrão (admin / admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        $messages[] = "Usuário admin criado. Login: admin / Senha: admin123";
        $messages[] = "⚠ IMPORTANTE: Troque a senha padrão após o primeiro login!";
    } else {
        $messages[] = "Usuário admin já existe.";
    }

    $messages[] = "";
    $messages[] = "Instalação concluída com sucesso!";
    $messages[] = "⚠ REMOVA OU RENOMEIE ESTE ARQUIVO (install.php) APÓS A INSTALAÇÃO!";

} catch (PDOException $e) {
    $error = true;
    $messages[] = "Erro na instalação: " . $e->getMessage();
    $messages[] = "Verifique as configurações em config/database.php";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Sistema</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: #16213e; padding: 40px; border-radius: 12px; max-width: 600px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { color: #e94560; margin-bottom: 20px; font-size: 24px; }
        .msg { padding: 10px 15px; margin: 8px 0; border-radius: 6px; font-size: 14px; }
        .msg.success { background: rgba(76, 175, 80, 0.15); border-left: 4px solid #4caf50; color: #a5d6a7; }
        .msg.error { background: rgba(244, 67, 54, 0.15); border-left: 4px solid #f44336; color: #ef9a9a; }
        .msg.warning { background: rgba(255, 152, 0, 0.15); border-left: 4px solid #ff9800; color: #ffcc80; }
        a { color: #e94560; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .link { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Instalação do Kallme</h1>
        <?php foreach ($messages as $msg): ?>
            <?php if (empty($msg)) continue; ?>
            <?php
                $class = 'success';
                if ($error) $class = 'error';
                elseif (str_contains($msg, '⚠')) $class = 'warning';
            ?>
            <div class="msg <?= $class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
        <?php if (!$error): ?>
            <div class="link">
                <a href="<?= BASE_URL ?>admin/">→ Acessar Painel Admin</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
