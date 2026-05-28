<?php
/**
 * POLÍTICA DE PRIVACIDADE — BR
 *
 * URL: kallme.online/br/politica-de-privacidade
 *
 * NOTA: o conteúdo legal será fornecido pela usuária em mensagem separada.
 * Por enquanto, deixe a div #content-politica-privacidade vazia ou com
 * um placeholder mínimo. Substitua quando tiver o texto final.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/site-helpers.php';

$lang = 'br';
$pageTitle = 'Política de Privacidade · Kallme';
$pageDescription = 'Como o Kallme coleta, usa e protege seus dados pessoais.';
$pageSlug = 'politica-de-privacidade';
$activeNav = ''; // nenhum item do menu principal fica destacado

include __DIR__ . '/../../includes/site-header.php';
?>

<main>
    <article class="container container-narrow prose">
        <header class="prose-header">
            <h1>Política de Privacidade</h1>
            <p class="prose-meta">Última atualização: <?= date('d/m/Y') ?></p>
        </header>
        <p class="prose-intro">
            Sua privacidade importa pra mim. Esta política explica de forma honesta
            quais dados eu coleto quando você visita o Kallme, como uso essas
            informações, e quais são os seus direitos sobre elas.
        </p>

        <p>
            Esta política está em conformidade com a <strong>Lei Geral de Proteção
                de Dados (LGPD — Lei nº 13.709/2018)</strong> no Brasil, com o
            <strong>Regulamento Geral de Proteção de Dados (GDPR)</strong> na União
            Europeia, e com o <strong>California Consumer Privacy Act (CCPA)</strong>
            nos Estados Unidos.
        </p>

        <h2>Quem é responsável por seus dados</h2>

        <p>
            O site Kallme (kallme.online) é administrado por Maíra Marini, no
            Brasil. Você pode entrar em contato comigo a qualquer momento sobre
            questões de privacidade pelo email
            <a href="mailto:support@kallme.online">support@kallme.online</a>.
        </p>

        <h2>Quais dados eu coleto</h2>

        <h3>1. Dados de navegação (automáticos)</h3>

        <p>
            Quando você acessa o Kallme, alguns dados são coletados automaticamente
            pelos serviços que eu uso pra fazer o site funcionar:
        </p>

        <ul>
            <li>Endereço IP (parcialmente anonimizado)</li>
            <li>Tipo de dispositivo, navegador e sistema operacional</li>
            <li>Páginas visitadas, tempo de permanência e cliques</li>
            <li>Origem do tráfego (de onde você veio até o site)</li>
            <li>Idioma do navegador e fuso horário</li>
        </ul>

        <p>
            Esses dados são <strong>agregados e anônimos</strong>, e me ajudam a
            entender quais conteúdos funcionam melhor e como melhorar sua
            experiência aqui.
        </p>

        <h3>2. Dados que você fornece voluntariamente</h3>

        <p>
            Quando você:
        </p>

        <ul>
            <li>
                <strong>Envia um email pra mim:</strong> recebo seu endereço de
                email e o conteúdo da mensagem
            </li>
            <li>
                <strong>Deixa um comentário no site (no futuro):</strong> nome,
                email e o texto do comentário ficam armazenados
            </li>
            <li>
                <strong>Inscreve-se na newsletter (no futuro):</strong> seu nome e
                email ficam armazenados pra que eu possa te enviar conteúdo
            </li>
        </ul>

        <h2>Como eu uso seus dados</h2>

        <p>
            Uso seus dados exclusivamente pra:
        </p>

        <ul>
            <li>Entender o desempenho do site e melhorar o conteúdo</li>
            <li>Responder mensagens enviadas por email</li>
            <li>Enviar a newsletter (apenas se você se inscrever)</li>
            <li>Cumprir obrigações legais</li>
        </ul>

        <p>
            <strong>Nunca vendo, troco ou compartilho seus dados pessoais com
                terceiros pra fins comerciais.</strong>
        </p>

        <h2>Cookies e tecnologias similares</h2>

        <p>
            O Kallme usa cookies pra funcionar corretamente. Cookies são pequenos
            arquivos de texto armazenados no seu navegador.
        </p>

        <h3>Tipos de cookies usados</h3>

        <ul>
            <li>
                <strong>Cookies essenciais:</strong> necessários pro site funcionar
                (preferência de idioma, sessão de navegação). Não podem ser
                desativados.
            </li>
            <li>
                <strong>Cookies de análise:</strong> usados pelo Google Analytics
                pra entender o comportamento dos visitantes de forma agregada.
                Você pode recusar.
            </li>
            <li>
                <strong>Cookies de publicidade:</strong> usados por plataformas como
                Google Ads e Pinterest pra mostrar anúncios relevantes em outros
                sites. Você pode recusar.
            </li>
        </ul>

        <p>
            Você pode configurar seu navegador pra recusar cookies a qualquer
            momento. Mais informações em
            <a href="https://www.allaboutcookies.org" target="_blank" rel="noopener">allaboutcookies.org</a>.
        </p>

        <h2>Parceiros e serviços terceirizados</h2>

        <p>
            O Kallme usa serviços externos pra funcionar e crescer. Cada um tem
            sua própria política de privacidade:
        </p>

        <ul>
            <li>
                <strong>Google Analytics</strong> — análise de tráfego do site.
                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Política de privacidade do
                    Google</a>
            </li>
            <li>
                <strong>Google Ads</strong> — anúncios pra divulgar o blog.
                <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener">Política de
                    anúncios do Google</a>
            </li>
            <li>
                <strong>Pinterest</strong> — divulgação de conteúdo.
                <a href="https://policy.pinterest.com/pt/privacy-policy" target="_blank" rel="noopener">Política de
                    privacidade do Pinterest</a>
            </li>
            <li>
                <strong>Amazon Associates</strong> — programa de afiliados.
                <a href="https://www.amazon.com.br/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ"
                    target="_blank" rel="noopener">Política da Amazon</a>
            </li>
            <li>
                <strong>HostGator</strong> — hospedagem do site.
                <a href="https://www.hostgator.com.br/politica-de-privacidade" target="_blank" rel="noopener">Política
                    da HostGator</a>
            </li>
        </ul>

        <h2>Seus direitos sobre seus dados</h2>

        <p>
            Você tem o direito de:
        </p>

        <ul>
            <li><strong>Acessar:</strong> saber quais dados eu tenho sobre você</li>
            <li><strong>Corrigir:</strong> pedir correção de informações erradas</li>
            <li><strong>Excluir:</strong> solicitar a exclusão dos seus dados</li>
            <li><strong>Portabilidade:</strong> receber seus dados em formato estruturado</li>
            <li><strong>Oposição:</strong> recusar o processamento de dados pra certas finalidades</li>
            <li><strong>Revogação de consentimento:</strong> cancelar autorizações dadas anteriormente</li>
        </ul>

        <p>
            Pra exercer qualquer um desses direitos, mande um email pra
            <a href="mailto:support@kallme.online">support@kallme.online</a>.
            Respondo em até 15 dias úteis.
        </p>

        <h2>Armazenamento e segurança</h2>

        <p>
            Seus dados são armazenados em servidores seguros da HostGator (Brasil)
            e dos serviços parceiros (Google, Pinterest). Aplico medidas técnicas
            e organizacionais razoáveis pra protegê-los contra acesso não
            autorizado, perda ou uso indevido.
        </p>

        <p>
            Mantenho seus dados apenas pelo tempo necessário pra cumprir as
            finalidades descritas nesta política, ou conforme exigido por lei.
        </p>

        <h2>Crianças e adolescentes</h2>

        <p>
            O Kallme não é direcionado a menores de 18 anos. Não coleto
            conscientemente dados de crianças. Se você é responsável legal de
            um menor e descobriu que ele forneceu dados aqui, entre em contato
            pra que eu possa excluí-los.
        </p>

        <h2>Atualizações desta política</h2>

        <p>
            Posso atualizar esta política eventualmente pra refletir mudanças
            legais, técnicas ou operacionais. Sempre que isso acontecer, vou
            atualizar a data abaixo. Mudanças significativas serão comunicadas
            com destaque no site.
        </p>

        <p class="prose-meta-footer">
            <strong>Versão:</strong> 1.0<br>
            <strong>Vigência:</strong> a partir de <?= date('d/m/Y') ?>
        </p>
    </article>
</main>

<?php include __DIR__ . '/../../includes/site-footer.php'; ?>