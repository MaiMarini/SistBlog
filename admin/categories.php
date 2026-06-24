<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/site-helpers.php';
requireLogin();

$categories = getAllCategoriesForAdmin();

// Contagem de conteúdo publicado por categoria — agregada (evita N+1).
// Conta artigos + receitas; estáticas ficam fora porque não pertencem
// a categoria editorial.
$articleCounts = [];
$pdo = getDB();
foreach ($pdo->query("
    SELECT category, COUNT(*) AS total
    FROM pages
    WHERE page_type IN ('article', 'recipe')
      AND status = 'published'
      AND category IS NOT NULL
    GROUP BY category
") as $row) {
    $articleCounts[$row['category']] = (int) $row['total'];
}

$msg = $_GET['msg'] ?? '';
$activePage = 'categories';
$pageTitle = 'Categorias';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>Categorias</h1>
            <a href="<?= BASE_URL ?>admin/category-form.php" class="btn btn-primary">+ Nova Categoria</a>
        </header>

        <?php if ($msg === 'saved'): ?>
            <div class="alert alert-success">Categoria salva com sucesso.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-success">Categoria excluída com sucesso.</div>
        <?php elseif ($msg && str_starts_with($msg, 'error:')): ?>
            <div class="alert alert-error"><?= e(substr($msg, 6)) ?></div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <div class="card">
                <div class="empty-state">
                    <p>Nenhuma categoria criada ainda.</p>
                    <a href="<?= BASE_URL ?>admin/category-form.php" class="btn btn-primary">Criar primeira categoria</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:60px;">Ordem</th>
                            <th style="width:64px;">Ícone</th>
                            <th>Nome</th>
                            <th>Slug</th>
                            <th>Artigos</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <?php $articleCount = $articleCounts[$cat['slug']] ?? 0; ?>
                            <tr>
                                <td><strong><?= (int) $cat['display_order'] ?></strong></td>
                                <td>
                                    <?php if ($cat['icon_type'] === 'phosphor'): ?>
                                        <i class="<?= e($cat['icon_value']) ?>" style="font-size:24px;color:#e0e0e0;"></i>
                                    <?php else: ?>
                                        <span style="display:inline-block;width:24px;height:24px;color:#e0e0e0;">
                                            <?= renderCategoryIcon($cat) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e($cat['name_br']) ?></strong></td>
                                <td><code><?= e($cat['slug']) ?></code></td>
                                <td><?= $articleCount ?></td>
                                <td>
                                    <span class="badge <?= $cat['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $cat['is_active'] ? '✅ Ativa' : '👁️ Oculta' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>admin/category-form.php?id=<?= (int) $cat['id'] ?>"
                                       class="btn btn-sm btn-edit" title="Editar">✏️</a>
                                    <a href="<?= e(url($cat['slug'], 'br')) ?>" target="_blank"
                                       class="btn btn-sm btn-view" title="Ver pública">👁️</a>
                                    <?php if ($articleCount === 0): ?>
                                        <a href="<?= BASE_URL ?>admin/category-delete.php?id=<?= (int) $cat['id'] ?>"
                                           class="btn btn-sm btn-delete" title="Excluir"
                                           onclick="return confirm('Tem certeza? Esta ação não pode ser desfeita.')">🗑️</a>
                                    <?php else: ?>
                                        <span class="btn btn-sm" style="opacity:0.3;cursor:not-allowed;"
                                              title="Categoria com artigos vinculados — desvincule antes de excluir">🗑️</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
