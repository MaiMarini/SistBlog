<?php
/**
 * INDEX — Kallme
 *
 * Fallback caso o .htaccess não capture a raiz "/" (alguns ambientes não
 * disparam RewriteRule ^$ quando o request é diretório).
 *
 * Comportamento: 301 → /br/ (homepage do idioma padrão).
 */
header('Location: /br/', true, 301);
exit;
