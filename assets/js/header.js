/**
 * HEADER — Kallme
 *
 * Controla o drawer lateral (abre da esquerda ao clicar no hambúrguer)
 * e o botão de busca (placeholder por enquanto).
 *
 * Elementos esperados no DOM:
 *   #drawer          <aside>  — o menu lateral
 *   #drawerOverlay   <div>    — overlay escuro
 *   #drawerOpen      <button> — hambúrguer no header
 *   .drawer__close   <button> — botão X dentro do drawer
 *   #searchBtn       <button> — botão de busca no header
 *
 * Comportamento:
 *   - Click no hambúrguer    → abre drawer
 *   - Click no X / overlay   → fecha drawer
 *   - ESC (com drawer aberto)→ fecha drawer
 *   - Click em link do drawer→ fecha drawer (navegação)
 *   - Click na busca         → alert "Em breve" (funcionalidade futura)
 */
(function () {
    'use strict';

    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('drawerOverlay');
    const openBtn = document.getElementById('drawerOpen');
    const closeBtn = drawer ? drawer.querySelector('.drawer__close') : null;
    const searchBtn = document.getElementById('searchBtn');
    const body = document.body;

    function openDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.add('is-open');
        overlay.classList.add('is-visible');
        body.classList.add('menu-open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        body.classList.remove('menu-open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');
    }

    if (openBtn) openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    // Fechar com ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    // Fechar ao clicar em link de navegação dentro do drawer
    if (drawer) {
        drawer.querySelectorAll('.drawer__nav a').forEach(function (link) {
            link.addEventListener('click', closeDrawer);
        });
    }

    // Botão de busca — placeholder até implementar a busca real
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            alert('Busca em breve. 🌷');
        });
    }
})();
