<?php
/**
 * ADMIN — PREVIEW (versão transitória)
 *
 * O preview de presell foi removido junto com os templates legados.
 * Quando o sistema editorial de blocos for implementado, este preview
 * será reescrito.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Preview indisponível — Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
</head>
<body>
    <div style="max-width: 520px; margin: 80px auto; padding: 0 24px; text-align: center; color: #e0e0e0;">
        <h1 style="color:#e94560; font-size:24px; margin-bottom: 16px;">👁️ Preview em construção</h1>
        <p style="font-size:15px; line-height:1.6; color:#aaa;">
            O sistema editorial está sendo reconstruído. O preview voltará junto com o novo formulário de blocos modulares.
        </p>
        <p style="margin-top: 24px;">
            <a href="javascript:history.back()" style="color:#e94560; text-decoration:none;">← Voltar ao editor</a>
        </p>
    </div>
</body>
</html>
