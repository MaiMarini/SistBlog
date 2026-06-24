<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stitches.php';
requireLogin();

$stitches = getAllStitches(false); // inclui ocultos
$msg = $_GET['msg'] ?? '';
$activePage = 'stitches';
$pageTitle = 'Glossário de Pontos';
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
            <h1>Glossário de Pontos</h1>
            <a href="<?= BASE_URL ?>admin/stitch-form.php" class="btn btn-primary">+ Novo Ponto</a>
        </header>

        <?php if ($msg === 'saved'): ?>
            <div class="alert alert-success">Ponto salvo com sucesso.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-success">Ponto excluído com sucesso.</div>
        <?php elseif ($msg && str_starts_with($msg, 'error:')): ?>
            <div class="alert alert-error"><?= e(substr($msg, 6)) ?></div>
        <?php endif; ?>

        <?php if (empty($stitches)): ?>
            <div class="card">
                <div class="empty-state">
                    <p>Nenhum ponto cadastrado ainda.</p>
                    <a href="<?= BASE_URL ?>admin/stitch-form.php" class="btn btn-primary">Cadastrar primeiro ponto</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:60px;">Ordem</th>
                            <th style="width:80px;">Ícone</th>
                            <th>Nome (PT)</th>
                            <th>Nome (EN)</th>
                            <th>Abreviação</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stitches as $st): ?>
                            <tr>
                                <td><strong><?= (int) $st['display_order'] ?></strong></td>
                                <td>
                                    <div style="width:40px;height:40px;color:#e0e0e0;">
                                        <?= renderStitchIcon($st) ?>
                                    </div>
                                </td>
                                <td><strong><?= e($st['name_br']) ?></strong></td>
                                <td><?= e($st['name_en'] ?? '') ?: '<span style="color:#666;">—</span>' ?></td>
                                <td>
                                    <code><?= e($st['abbrev_br']) ?></code>
                                    <?php if (!empty($st['abbrev_en'])): ?>
                                        / <code><?= e($st['abbrev_en']) ?></code>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $st['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $st['is_active'] ? '✅ Ativo' : '👁️ Oculto' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>admin/stitch-form.php?id=<?= (int) $st['id'] ?>"
                                       class="btn btn-sm btn-edit" title="Editar">✏️</a>
                                    <a href="<?= BASE_URL ?>admin/stitch-delete.php?id=<?= (int) $st['id'] ?>"
                                       class="btn btn-sm btn-delete" title="Excluir"
                                       onclick="return confirm('Tem certeza? Esta ação não pode ser desfeita.')">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:20px;">
                <div style="padding:20px;">
                    <h3 style="font-family:'Playfair Display',serif;color:#fff;font-size:18px;margin-bottom:10px;">
                        📖 Como usar
                    </h3>
                    <p style="color:#aaa;line-height:1.7;font-size:14px;">
                        Cada ponto cadastrado aqui poderá ser usado nas receitas de crochê (Fase Receita B).
                        O ícone aparece junto com o nome e abreviação, e clicar leva a leitora pro tutorial
                        único do guia de pontos (será criado depois).
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
