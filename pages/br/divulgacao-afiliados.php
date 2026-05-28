<?php
/**
 * DIVULGAÇÃO DE AFILIADOS — BR
 *
 * URL: kallme.online/br/divulgacao-afiliados
 *
 * NOTA: o conteúdo legal (FTC + LGPD) será fornecido pela usuária.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Divulgação de Afiliados · Kallme';
$pageDescription = 'Transparência sobre os links de afiliado usados no Kallme e como eles sustentam o blog.';
$pageSlug = 'divulgacao-afiliados';
$activeNav = '';

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <article class="container container-narrow prose">
        <header class="prose-header">
            <h1>Divulgação de Afiliados</h1>
            <p class="prose-meta">Última atualização: <?= date('d/m/Y') ?></p>
        </header>
        <p class="prose-intro">
            Transparência é um dos valores mais importantes do Kallme. Esta página
            explica honestamente como o blog se sustenta financeiramente — porque
            você merece saber.
        </p>

        <h2>Resumo rápido</h2>

        <p>
            O Kallme contém <strong>links de afiliado</strong>. Quando você clica
            em um desses links e faz uma compra, eu posso receber uma pequena
            comissão — sem nenhum custo adicional pra você. Essas comissões
            ajudam a manter o blog funcionando.
        </p>

        <p>
            Eu só recomendo produtos que <strong>genuinamente acredito</strong>
            serem úteis, e minha opinião nunca é influenciada por compensação
            financeira.
        </p>

        <h2>O que são links de afiliado</h2>

        <p>
            Links de afiliado são links especiais que rastreiam quando alguém
            chega a uma loja através do Kallme. Se essa pessoa comprar algo
            dentro de um certo prazo, a loja paga uma comissão pra mim como
            agradecimento por indicar o cliente.
        </p>

        <p>
            É um modelo comum na internet, usado por blogs, sites de
            comparação de preços, influenciadores e até por veículos de
            imprensa tradicionais. A diferença é que aqui no Kallme eu
            <strong>declaro abertamente</strong> quando estou usando links
            desse tipo.
        </p>

        <h2>Programas dos quais participo</h2>

        <h3>Amazon Associates</h3>

        <p>
            O Kallme é participante do Programa de Associados da Amazon Brasil,
            um programa de afiliados projetado pra dar a sites uma forma de
            ganhar comissões por publicidade e links pra Amazon.com.br. Como
            Associada Amazon, eu recebo comissões por compras qualificadas.
        </p>

        <h3>Hotmart</h3>

        <p>
            Eu também participo de programas de afiliados da Hotmart,
            recomendando cursos digitais e produtos educacionais que considero
            relevantes pro público do blog.
        </p>

        <h3>Outros parceiros</h3>

        <p>
            Eventualmente, posso participar de outros programas de afiliados
            ou parcerias específicas com marcas. Sempre que isso acontecer,
            vou deixar claro no próprio conteúdo.
        </p>

        <h2>Como identifico os links</h2>

        <p>
            Sempre que um link no Kallme for de afiliado, você vai encontrar
            uma ou mais dessas indicações:
        </p>

        <ul>
            <li>
                Uma <strong>nota visível</strong> no início ou no final do
                artigo, tipo: "Este post contém links de afiliado"
            </li>
            <li>
                Os links de produto aparecem em <strong>blocos destacados</strong>
                com a borda rosa e o texto "Recomendação"
            </li>
            <li>
                O atributo <code>rel="sponsored"</code> ou
                <code>rel="nofollow"</code> nos próprios links (visível no
                código da página, conforme exigência do Google)
            </li>
        </ul>

        <h2>Como eu escolho o que recomendar</h2>

        <p>
            Esta é uma das partes mais importantes desta página:
        </p>

        <ul>
            <li>
                Recomendo apenas produtos que eu mesma usaria, comprei ou
                pesquisei profundamente
            </li>
            <li>
                Não aceito recomendar produtos que não conheço só pra ganhar
                comissão
            </li>
            <li>
                <strong>O valor da comissão não influencia minha opinião</strong> —
                se um produto barato ou sem programa de afiliado é melhor que
                um produto caro com comissão alta, eu recomendo o melhor
            </li>
            <li>
                Quando aplicável, sinalizo limitações ou pontos negativos dos
                produtos que recomendo (porque produto perfeito não existe)
            </li>
        </ul>

        <h2>Por que isso é importante</h2>

        <p>
            Manter um blog tem custos: hospedagem, domínio, ferramentas de
            pesquisa, fotos, tempo de produção do conteúdo. As comissões de
            afiliado permitem que eu continue criando conteúdo gratuito e
            honesto pra você.
        </p>

        <p>
            Se você gostou de algo aqui e quer apoiar o trabalho, pode usar
            os links do Kallme quando for comprar algo nas lojas parceiras —
            você paga o mesmo preço, e uma pequena parte da venda volta pra
            manter o blog vivo.
        </p>

        <h2>O que NÃO acontece</h2>

        <p>
            Pra deixar bem claro o que <strong>não</strong> é praticado aqui:
        </p>

        <ul>
            <li>
                <strong>Não recebo dinheiro de marcas pra escrever artigos
                    positivos</strong> sobre seus produtos (a menos que seja
                claramente identificado como "publipost", o que ainda não
                aconteceu)
            </li>
            <li>
                <strong>Não vendo seus dados</strong> pra anunciantes
            </li>
            <li>
                <strong>Não recomendo produtos ruins</strong> só porque pagam
                comissão alta
            </li>
            <li>
                <strong>Não escondo</strong> que existem links de afiliado nos
                artigos
            </li>
        </ul>

        <h2>Conformidade legal</h2>

        <p>
            Esta divulgação está em conformidade com as normas brasileiras de
            transparência publicitária (CONAR e Código de Defesa do Consumidor)
            e com as regras da <strong>Federal Trade Commission (FTC)</strong>
            dos Estados Unidos, aplicáveis a sites que atendem público
            internacional.
        </p>

        <h2>Dúvidas?</h2>

        <p>
            Se você tem alguma dúvida sobre como o Kallme se sustenta, ou se
            encontrou algo que parece um link de afiliado não identificado,
            me avisa imediatamente:
            <a href="mailto:support@kallme.online">support@kallme.online</a>.
            Transparência é levada a sério por aqui.
        </p>

        <p class="prose-meta-footer">
            <strong>Versão:</strong> 1.0<br>
            <strong>Vigência:</strong> a partir de <?= date('d/m/Y') ?>
        </p>
    </article>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>