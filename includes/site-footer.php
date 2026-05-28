<?php
/**
 * SITE FOOTER — Kallme (Sereno Romântico)
 *
 * Footer compartilhado de páginas estáticas e artigos. Pre-sells NÃO usam
 * este arquivo.
 *
 * Estrutura (3 colunas):
 *   1. Marca (logo "Maíra Marini Ateliê" + tagline + Pinterest)
 *   2. Site (Início, Sobre, Contato)
 *   3. Legal (Privacidade, Termos, Divulgação de Afiliados)
 *
 * Linha inferior: copyright à esquerda, email de contato à direita.
 *
 * Reescrito na Fase 3-bis: removeu categorias (voltam na Fase 3 quando
 * houver páginas de categoria), removeu email da coluna Legal,
 * adicionou logo "Maíra Marini Ateliê" e ícone Pinterest.
 *
 * Variáveis esperadas (opcionais):
 *   $lang  string  'br' | 'en'. Default: getCurrentLanguage()
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/site-helpers.php';

$lang = $lang ?? getCurrentLanguage();
$siteName = getSetting('site_name', 'Kallme');
$tagline = $lang === 'en' ? getSetting('site_tagline_en', '') : getSetting('site_tagline_br', '');
$contactEmail = getSetting('contact_email', 'support@kallme.online');
$year = date('Y');

// Pinterest é o único social ativo por enquanto. Outras redes podem ser
// adicionadas depois (basta repetir o <a> dentro de .footer-social).
$pinterestUrl = getSetting('social_pinterest', '');

// Labels por idioma
$labels = $lang === 'en' ? [
    'site'       => 'Site',
    'legal'      => 'Legal',
    'home'       => 'Home',
    'about'      => 'About',
    'contact'    => 'Contact',
    'privacy'    => 'Privacy Policy',
    'terms'      => 'Terms of Use',
    'disclosure' => 'Affiliate Disclosure',
    'contact_label' => 'Contact',
    'pin_aria'   => 'Kallme on Pinterest',
] : [
    'site'       => 'Site',
    'legal'      => 'Legal',
    'home'       => 'Início',
    'about'      => 'Sobre',
    'contact'    => 'Contato',
    'privacy'    => 'Política de privacidade',
    'terms'      => 'Termos de uso',
    'disclosure' => 'Divulgação de afiliados',
    'contact_label' => 'Contato',
    'pin_aria'   => 'Pinterest do Kallme',
];

$slugs = $lang === 'en'
    ? ['about' => 'about', 'contact' => 'contact', 'privacy' => 'privacy-policy', 'terms' => 'terms', 'disclosure' => 'affiliate-disclosure']
    : ['about' => 'sobre', 'contact' => 'contato', 'privacy' => 'politica-de-privacidade', 'terms' => 'termos', 'disclosure' => 'divulgacao-afiliados'];
?>

<footer class="site-footer">
    <div class="container container-wide">

        <div class="site-footer__grid">

            <!-- COLUNA 1: MARCA (só logo, alinhada ao topo) -->
            <div class="footer-col footer-col--brand">
                <a href="<?= e(url('', $lang)) ?>" class="footer-logo" aria-label="<?= e($siteName) ?>">
                    <img src="<?= BASE_URL ?>assets/img/logo-full.png"
                         alt="Maíra Marini Ateliê"
                         class="footer-logo__img">
                    <!-- Fallback de texto: escondido por CSS sempre que <img> existir
                         (.footer-logo:has(img) .footer-logo__text { display: none }) -->
                    <span class="footer-logo__text">Maíra Marini Ateliê</span>
                </a>
            </div>

            <!-- COLUNA 2: SITE -->
            <div class="footer-col">
                <h4 class="footer-col__title"><?= e($labels['site']) ?></h4>
                <nav class="footer-nav" aria-label="Navegação institucional">
                    <a href="<?= e(url('', $lang)) ?>"><?= e($labels['home']) ?></a>
                    <a href="<?= e(url($slugs['about'], $lang)) ?>"><?= e($labels['about']) ?></a>
                    <a href="<?= e(url($slugs['contact'], $lang)) ?>"><?= e($labels['contact']) ?></a>
                </nav>
            </div>

            <!-- COLUNA 3: LEGAL -->
            <div class="footer-col">
                <h4 class="footer-col__title"><?= e($labels['legal']) ?></h4>
                <nav class="footer-nav" aria-label="Páginas legais">
                    <a href="<?= e(url($slugs['privacy'], $lang)) ?>"><?= e($labels['privacy']) ?></a>
                    <a href="<?= e(url($slugs['terms'], $lang)) ?>"><?= e($labels['terms']) ?></a>
                    <a href="<?= e(url($slugs['disclosure'], $lang)) ?>"><?= e($labels['disclosure']) ?></a>
                </nav>
            </div>

            <!-- COLUNA 4: REDES -->
            <div class="footer-col">
                <h4 class="footer-col__title"><?= $lang === 'en' ? 'Social' : 'Redes' ?></h4>
                <nav class="footer-nav footer-nav--social" aria-label="Redes sociais">
                    <!-- Pinterest sempre renderizado; href cai em '#' se setting vazio -->
                    <a href="<?= e($pinterestUrl ?: '#') ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="<?= e($labels['pin_aria']) ?>">
                        <i class="ph-light ph-pinterest-logo icon-sm"></i>
                        <span>Pinterest</span>
                    </a>
                </nav>
            </div>

        </div>

        <!-- LINHA INFERIOR: copyright + email -->
        <div class="site-footer__bottom">
            <p class="footer-copyright">
                © <?= e($year) ?> Maíra Marini ·
                <a href="https://kallme.online">kallme.online</a>
            </p>
            <?php if (!empty($contactEmail)): ?>
                <p class="footer-contact">
                    <?= e($labels['contact_label']) ?>:
                    <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
                </p>
            <?php endif; ?>
        </div>

    </div>
</footer>

<!-- Phosphor Icons são font-based (CSS puro) — não precisam de inicialização JS -->

<!-- Header: drawer lateral + busca -->
<script src="<?= BASE_URL ?>assets/js/header.js" defer></script>
</body>
</html>
