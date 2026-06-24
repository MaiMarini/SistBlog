<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
requireLogin();

$pages = getAllPages();

$msg = $_GET['msg'] ?? '';
$activePage = 'pages';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Páginas - Kallme Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/admin.css">
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="content-header">
                <h1>Páginas</h1>
                <a href="<?= BASE_URL ?>admin/page-form.php" class="btn btn-primary">+ Nova Página</a>
            </header>

            <?php if ($msg === 'deleted'): ?>
                <div class="alert alert-success">Página excluída com sucesso.</div>
            <?php elseif ($msg === 'saved'): ?>
                <div class="alert alert-success">Página salva com sucesso.</div>
            <?php endif; ?>

            <?php if (empty($pages)): ?>
                <div class="card">
                    <div class="empty-state">
                        <p>Nenhuma página criada ainda.</p>
                        <a href="<?= BASE_URL ?>admin/page-form.php" class="btn btn-primary">Criar primeira página</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>URL</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Idioma</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <?php
                                    // Monta a URL pública conforme tipo + idioma + categoria
                                    $hasCatPrefix = in_array($page['page_type'], ['article', 'recipe'], true) && !empty($page['category']);
                                    $publicUrl = BASE_URL . $page['language'] . '/';
                                    if ($hasCatPrefix) {
                                        $publicUrl .= $page['category'] . '/';
                                    }
                                    $publicUrl .= $page['slug'];

                                    // Caminho legível (igual ao publicUrl mas sem BASE_URL)
                                    $urlPath = '/' . $page['language'] . '/'
                                             . ($hasCatPrefix ? $page['category'] . '/' : '')
                                             . $page['slug'];

                                    // Label do tipo (com categoria pra artigos/receitas)
                                    $typeLabel = match ($page['page_type']) {
                                        'article' => '📝 Artigo',
                                        'recipe'  => '🧶 Receita',
                                        'static'  => '📄 Estática',
                                        default   => e($page['page_type']),
                                    };
                                    if (in_array($page['page_type'], ['article', 'recipe'], true) && !empty($page['category'])) {
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
                                    <td><?= strtoupper(e($page['language'])) ?></td>
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
                                           onclick="return confirm('Tem certeza que deseja excluir esta página?')">🗑️</a>
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
