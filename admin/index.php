<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$counts = countPages();
$pages = getAllPages();
$recentPages = array_slice($pages, 0, 5);
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Dashboard</h1>
                <a href="<?= BASE_URL ?>admin/page-form.php" class="btn btn-primary">+ Nova Página</a>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $counts['total'] ?></div>
                    <div class="stat-label">Total de Páginas</div>
                </div>
                <div class="stat-card stat-published">
                    <div class="stat-number"><?= $counts['published'] ?></div>
                    <div class="stat-label">Publicadas</div>
                </div>
                <div class="stat-card stat-draft">
                    <div class="stat-number"><?= $counts['draft'] ?></div>
                    <div class="stat-label">Rascunhos</div>
                </div>
            </div>

            <!-- Recent Pages -->
            <div class="card">
                <div class="card-header">
                    <h2>Páginas Recentes</h2>
                    <a href="<?= BASE_URL ?>admin/pages.php" class="link-subtle">Ver todas →</a>
                </div>
                <?php if (empty($recentPages)): ?>
                    <div class="empty-state">
                        <p>Nenhuma página criada ainda.</p>
                        <a href="<?= BASE_URL ?>admin/page-form.php" class="btn btn-primary">Criar primeira página</a>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>URL</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPages as $page): ?>
                                <?php
                                    // Monta URL pública bilíngue (mesma lógica de admin/pages.php)
                                    $urlPath = '/' . $page['language'] . '/';
                                    if ($page['page_type'] === 'article' && !empty($page['category'])) {
                                        $urlPath .= $page['category'] . '/';
                                    }
                                    $urlPath .= $page['slug'];

                                    $publicUrl = BASE_URL . ltrim($urlPath, '/');

                                    // Label do tipo (com categoria pra artigos)
                                    $typeLabel = $page['page_type'] === 'article' ? '📝 Artigo' : '📄 Estática';
                                    if ($page['page_type'] === 'article' && !empty($page['category'])) {
                                        $catData = getCategoryBySlug($page['category']);
                                        if ($catData) {
                                            $typeLabel .= ' · ' . e($catData['name_br'] ?? $page['category']);
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= e($page['title']) ?></strong></td>
                                    <td><code><?= e($urlPath) ?></code></td>
                                    <td><span class="badge badge-type"><?= $typeLabel ?></span></td>
                                    <td>
                                        <span class="badge <?= $page['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $page['status'] === 'published' ? '✅ Publicada' : '📝 Rascunho' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($page['created_at'])) ?></td>
                                    <td class="actions">
                                        <a href="<?= BASE_URL ?>admin/page-form.php?id=<?= $page['id'] ?>"
                                           class="btn btn-sm btn-edit" title="Editar">✏️</a>
                                        <?php if ($page['status'] === 'published'): ?>
                                            <a href="<?= e($publicUrl) ?>" target="_blank"
                                               class="btn btn-sm btn-view" title="Ver pública">👁️</a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>admin/page-delete.php?id=<?= $page['id'] ?>"
                                           class="btn btn-sm btn-delete" title="Excluir"
                                           onclick="return confirm('Tem certeza?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>