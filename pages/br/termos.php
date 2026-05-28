<?php
/**
 * TERMOS DE USO — BR
 *
 * URL: kallme.online/br/termos
 *
 * NOTA: o conteúdo legal será fornecido pela usuária em mensagem separada.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Termos de Uso · Kallme';
$pageDescription = 'Termos e condições para uso do site Kallme.';
$pageSlug = 'termos';
$activeNav = '';

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <article class="container container-narrow prose">
        <header class="prose-header">
            <h1>Termos de Uso</h1>
            <p class="prose-meta">Última atualização: <?= date('d/m/Y') ?></p>
        </header>

        <p class="prose-intro">
            Ao acessar e usar o site Kallme (kallme.online), você concorda com
            estes Termos de Uso. Leia com atenção. Se não concordar com qualquer
            ponto, por favor, não use o site.
        </p>

        <h2>Sobre o Kallme</h2>

        <p>
            O Kallme é um blog editorial sobre trabalhos manuais, crochê,
            jardinagem e crafts caseiros, administrado por Maíra Marini. O
            conteúdo aqui publicado tem caráter <strong>informativo e
                inspiracional</strong>.
        </p>

        <h2>Uso do conteúdo</h2>

        <h3>O que você pode fazer</h3>

        <ul>
            <li>Ler e compartilhar links pros artigos</li>
            <li>Citar trechos curtos com atribuição (autor + link)</li>
            <li>Imprimir ou salvar conteúdo pra uso pessoal e não comercial</li>
            <li>Compartilhar artigos em redes sociais</li>
        </ul>

        <h3>O que você NÃO pode fazer</h3>

        <ul>
            <li>Copiar e republicar artigos inteiros em outros sites ou plataformas</li>
            <li>Usar imagens, textos ou design pra fins comerciais sem autorização</li>
            <li>Remover créditos de autoria</li>
            <li>Modificar o conteúdo e republicá-lo como seu</li>
            <li>Usar técnicas automatizadas (scraping, bots) pra extrair conteúdo em massa</li>
        </ul>

        <p>
            Pra solicitar autorização pra usos não previstos, entre em contato:
            <a href="mailto:support@kallme.online">support@kallme.online</a>.
        </p>

        <h2>Propriedade intelectual</h2>

        <p>
            Todo o conteúdo do Kallme — textos, imagens, design, layout, marca
            e logotipo — é protegido por leis de direito autoral brasileiras
            (Lei nº 9.610/1998) e internacionais. Os direitos pertencem a Maíra
            Marini, exceto quando indicado de outra forma (por exemplo, imagens
            de bancos com licença creative commons, devidamente creditadas).
        </p>

        <h2>Conteúdo de terceiros</h2>

        <p>
            Eventualmente o Kallme pode incluir conteúdo de terceiros, como
            imagens de bancos gratuitos, citações de outras fontes, ou links
            pra sites externos. Esses conteúdos pertencem aos seus respectivos
            donos e estão sempre devidamente creditados.
        </p>

        <h2>Links externos</h2>

        <p>
            O Kallme pode conter links pra sites externos. Não tenho controle
            sobre o conteúdo desses sites e <strong>não me responsabilizo</strong>
            por suas práticas, políticas ou conteúdo. Recomendo sempre ler os
            termos de uso e políticas de privacidade de cada site que você
            visita.
        </p>

        <h2>Links de afiliados</h2>

        <p>
            Alguns links no Kallme são <strong>links de afiliado</strong> — isso
            significa que, se você comprar algo através deles, eu recebo uma
            pequena comissão sem nenhum custo adicional pra você.
        </p>

        <p>
            Pra entender melhor como isso funciona, leia a página completa de
            <a href="/br/divulgacao-afiliados">Divulgação de Afiliados</a>.
        </p>

        <h2>Limitação de responsabilidade</h2>

        <p>
            O conteúdo do Kallme tem caráter informativo e baseia-se em pesquisa
            e na minha experiência pessoal como hobbyista. <strong>Não substitui
                consultoria profissional</strong>.
        </p>

        <p>
            Especialmente em assuntos que envolvem:
        </p>

        <ul>
            <li>Saúde ou segurança (uso de ferramentas, produtos químicos, alergias)</li>
            <li>Decisões financeiras ou de investimento</li>
            <li>Jardinagem com plantas tóxicas, espinhos ou alergênicas</li>
            <li>Uso de equipamentos que exigem treinamento</li>
        </ul>

        <p>
            Sempre consulte profissionais qualificados antes de tomar decisões
            importantes. Você é responsável por avaliar a aplicabilidade do
            conteúdo à sua situação específica.
        </p>

        <p>
            O Kallme se exime de qualquer responsabilidade por danos diretos
            ou indiretos resultantes do uso das informações aqui publicadas.
        </p>

        <h2>Disponibilidade do site</h2>

        <p>
            Faço o possível pra manter o site sempre no ar, mas não posso
            garantir disponibilidade 100% do tempo. Eventuais interrupções
            podem ocorrer por manutenção, atualizações ou problemas técnicos
            com o servidor.
        </p>

        <h2>Comentários e contribuições (futuro)</h2>

        <p>
            Quando o Kallme habilitar comentários, você concorda em:
        </p>

        <ul>
            <li>Não publicar conteúdo ofensivo, discriminatório ou ilegal</li>
            <li>Não fazer spam ou publicidade não autorizada</li>
            <li>Respeitar outros leitores e a autora</li>
            <li>Permitir que comentários inadequados sejam removidos sem aviso</li>
        </ul>

        <h2>Alterações nestes termos</h2>

        <p>
            Posso atualizar estes Termos de Uso eventualmente. A data de
            vigência sempre constará no final desta página. O uso continuado
            do site após mudanças significativas implica concordância com a
            nova versão.
        </p>

        <h2>Lei aplicável e foro</h2>

        <p>
            Estes Termos são regidos pelas leis brasileiras. Eventuais
            controvérsias serão resolvidas no foro da comarca onde a autora
            reside, exceto se a legislação de proteção ao consumidor (CDC)
            estabelecer foro diferente.
        </p>

        <h2>Contato</h2>

        <p>
            Dúvidas sobre estes Termos? Mande um email pra
            <a href="mailto:support@kallme.online">support@kallme.online</a>.
        </p>

        <p class="prose-meta-footer">
            <strong>Versão:</strong> 1.0<br>
            <strong>Vigência:</strong> a partir de <?= date('d/m/Y') ?>
        </p>
    </article>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>