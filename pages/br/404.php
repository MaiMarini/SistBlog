<?php
/**
 * 404 — BR
 *
 * Página de erro 404 personalizada (servida pelo page-router.php quando
 * o slug não bate com arquivo estático, categoria ou registro no banco).
 *
 * O page-router já chamou http_response_code(404) antes de incluir este
 * arquivo, mas reforçamos aqui para o caso de acesso direto.
 */

if (!headers_sent()) {
    http_response_code(404);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Página não encontrada · Kallme';
$pageDescription = 'A página que você procura não existe ou foi movida.';
$pageSlug = '404';
$activeNav = '';

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <section class="error-page container container-narrow">
        <i class="ph-light ph-magnifying-glass-minus icon-2xl"></i>
        <h1>Hmm, não achei essa página</h1>
        <p>
            Ela pode ter sido movida ou nunca ter existido por aqui.
            Vamos voltar pro caminho:
        </p>
        <div class="error-actions">
            <a href="<?= url('', $lang) ?>" class="btn btn-primary">
                <i class="ph-light ph-house icon-sm"></i>
                Voltar pro início
            </a>
            <a href="<?= url('sobre', $lang) ?>" class="btn btn-secondary">
                Sobre o Kallme
            </a>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>
