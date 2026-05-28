// ============================================
// PRESELL PAGES - SCRIPTS
// ============================================

document.addEventListener('DOMContentLoaded', function() {

    // Adicionar delay na animação dos comentários (scroll reveal)
    const comments = document.querySelectorAll('.adv-comment, .comment-item');
    if (comments.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        comments.forEach(function(comment, index) {
            comment.style.opacity = '0';
            comment.style.transform = 'translateY(20px)';
            comment.style.transition = 'opacity 0.5s ease ' + (index * 0.1) + 's, transform 0.5s ease ' + (index * 0.1) + 's';
            observer.observe(comment);
        });
    }

    // Smooth scroll para links internos
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Tracking de cliques no CTA (console log para debug)
    document.querySelectorAll('.adv-cta-button, .blog-cta-button, .landing-hero-cta, .struct-cta-button, .struct-floating-cta').forEach(function(btn) {
        btn.addEventListener('click', function() {
            console.log('CTA clicked:', this.href);
        });
    });

    // Botão flutuante: visível desde o carregamento; esconde quando o CTA principal está na tela
    var floatingCta = document.getElementById('struct-floating-cta');
    var mainCta = document.querySelector('.struct-content-2-cta .struct-cta-button');
    if (floatingCta && mainCta && 'IntersectionObserver' in window) {
        var ctaObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    floatingCta.classList.add('hidden');
                } else {
                    floatingCta.classList.remove('hidden');
                }
            });
        }, { threshold: 0.5 });
        ctaObserver.observe(mainCta);
    }
});
