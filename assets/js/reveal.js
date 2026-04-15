/* ── Reveal on Scroll ──
   Añade clase .visible a elementos con .reveal cuando entran en viewport.
   También añade .reveal automáticamente a secciones principales.
*/
(function () {
    // Auto-tag secciones principales con .reveal si no lo tienen ya
    const selectors = [
        '.category-section',
        '.section-head',
        '.featured-section',
        '.featured-grid',
        '.cod-section',
        '.trending-section',
        '.stats-banner',
        '.arena-hero',
        '.arena-content',
        '.auctions-header',
        '.auctions-grid',
        '.cart-container',
        '.mercado-content .section-head',
        '.site-footer .footer-inner'
    ];

    selectors.forEach(function (sel) {
        document.querySelectorAll(sel).forEach(function (el) {
            if (!el.classList.contains('reveal')) {
                el.classList.add('reveal');
            }
        });
    });

    // Stagger: featured cards y auction cards
    document.querySelectorAll('.featured-card, .auction-card, .bet-item').forEach(function (el, i) {
        el.classList.add('reveal');
        el.style.transitionDelay = (i % 4) * 0.08 + 's';
    });

    // IntersectionObserver para activar .visible
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    document.querySelectorAll('.reveal').forEach(function (el) {
        observer.observe(el);
    });

    // Forzar visible en elementos ya en viewport al cargar
    requestAnimationFrame(function () {
        document.querySelectorAll('.reveal').forEach(function (el) {
            var rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                el.classList.add('visible');
            }
        });
    });
})();
