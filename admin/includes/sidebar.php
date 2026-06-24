<?php
/**
 * SIDEBAR ADMIN — Kallme
 *
 * Sidebar reutilizável de todas as telas admin. Espera receber a variável
 * $activePage definida pelo arquivo que inclui esta sidebar.
 *
 * Valores aceitos para $activePage:
 *   'dashboard' | 'pages' | 'new-page' | 'categories' | 'stitches' | 'settings'
 *
 * Como usar em uma página admin:
 *   $activePage = 'pages';
 *   include __DIR__ . '/includes/sidebar.php';
 */

$activePage = $activePage ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>📄 Kallme Admin</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>admin/" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>admin/pages.php" class="nav-item <?= $activePage === 'pages' ? 'active' : '' ?>">
            <span class="nav-icon">📝</span> Páginas
        </a>
        <a href="<?= BASE_URL ?>admin/page-form.php" class="nav-item <?= $activePage === 'new-page' ? 'active' : '' ?>">
            <span class="nav-icon">➕</span> Nova Página
        </a>
        <a href="<?= BASE_URL ?>admin/categories.php" class="nav-item <?= $activePage === 'categories' ? 'active' : '' ?>">
            <span class="nav-icon">🏷️</span> Categorias
        </a>
        <a href="<?= BASE_URL ?>admin/stitches.php" class="nav-item <?= $activePage === 'stitches' ? 'active' : '' ?>">
            <span class="nav-icon">🧶</span> Glossário de Pontos
        </a>
        <a href="<?= BASE_URL ?>admin/settings.php" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
            <span class="nav-icon">⚙️</span> Configurações
        </a>
    </nav>
    <div class="sidebar-footer">
        <span class="user-info">👤 <?= e($_SESSION['username'] ?? 'admin') ?></span>
        <a href="<?= BASE_URL ?>admin/logout.php" class="logout-link">Sair</a>
    </div>
</aside>
