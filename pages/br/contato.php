<?php
/**
 * CONTATO — BR
 *
 * URL: kallme.online/br/contato
 *
 * NOTA: textos com [Placeholder] devem ser substituídos pelos textos
 * definitivos antes de publicar.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Contato · Kallme';
$pageDescription = 'Entre em contato com o Kallme. Respondo todas as mensagens em até 48 horas.';
$pageSlug = 'contato';
$activeNav = 'contato';

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <article class="container container-medium prose">
        <header class="prose-header">
            <h1>Vamos conversar</h1>
            <p class="prose-subtitle">
                Adoro receber mensagens. Aqui está como me achar.
            </p>
        </header>

        <section class="contact-card">
            <i class="ph-light ph-envelope icon-xl"></i>
            <h2>Email</h2>
            <p>
                <a href="mailto:support@kallme.online">support@kallme.online</a>
            </p>
            <p class="contact-meta">
                Respondo em até 48 horas em dias úteis.
            </p>
        </section>

        <h2>Antes de me escrever</h2>

        <p>
            Talvez sua dúvida já tenha resposta aqui embaixo. Se não tiver,
            é só mandar email — vou adorar saber.
        </p>

        <ul class="contact-faq">

            <li>
                <strong>Quer sugerir um tema?</strong>
                Sugestões são bem-vindas. Conta o que você gostaria de ver
                por aqui — se faz sentido pro Kallme e eu tiver experiência
                no assunto, vai pra fila editorial.
            </li>

            <li>
                <strong>Encontrou um erro no site ou no conteúdo?</strong>
                Sou grata por avisos assim. Me conta o que viu — link da
                página, o que está errado, e se possível um print. Corrijo
                o quanto antes.
            </li>

            <li>
                <strong>Quer fazer parceria ou indicar um produto?</strong>
                O Kallme ainda é um projeto novo e meu foco agora é construir
                conteúdo de qualidade. Não estou aceitando parcerias
                patrocinadas no momento, mas avalio recomendações de produtos
                que façam sentido pro público — sem qualquer compromisso de
                publicação.
            </li>

            <li>
                <strong>É algum tipo de post pago, publipost ou guest post?</strong>
                Por enquanto, não. Quando isso mudar, vou deixar claro e
                qualquer conteúdo patrocinado será identificado de forma
                visível no próprio post. Transparência é meio que a alma do
                Kallme.
            </li>

            <li>
                <strong>Quer só dizer um oi?</strong>
                Esse é meu favorito. Me conta de onde você é, o que faz com
                as mãos, qual planta você quase matou recentemente. Adoro
                saber de quem está do outro lado.
            </li>

        </ul>

        <p style="text-align:center; font-style:italic; margin-top: var(--space-xl);">
            — Mai Marini
        </p>
    </article>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>