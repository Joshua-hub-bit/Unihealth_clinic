(() => {
    const cardSelector = [
        '.uh-card',
        '.uh-stat-card',
        '.ad-kpi-card',
        '.ad-card',
        '.ad-chart-card',
        '.pm-metric-card',
        '.pm-trend-chart-card',
        '.pm-activity-card',
        '.pm-goal-card',
        '.ph-card',
        '.ph-stat-card',
        '.an-chart-card',
    ].join(',');

    if (
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || !('IntersectionObserver' in window)
        || typeof Element.prototype.animate !== 'function'
    ) {
        return;
    }

    const observedCards = new WeakSet();
    const observer = new IntersectionObserver((entries) => {
        entries
            .filter((entry) => entry.isIntersecting && !observedCards.has(entry.target))
            .forEach((entry, index) => {
                const card = entry.target;

                observedCards.add(card);
                observer.unobserve(card);
                card.animate(
                    [
                        { opacity: 0, transform: 'translateY(8px)' },
                        { opacity: 1, transform: 'translateY(0)' },
                    ],
                    {
                        duration: 360,
                        delay: Math.min(index * 45, 180),
                        easing: 'cubic-bezier(.2,.8,.2,1)',
                    },
                );
            });
    }, { threshold: 0.08 });

    const observeCards = (root) => {
        if (root instanceof Element && root.matches(cardSelector)) {
            observer.observe(root);
        }

        if (typeof root.querySelectorAll === 'function') {
            root.querySelectorAll(cardSelector).forEach((card) => observer.observe(card));
        }
    };

    observeCards(document);

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    observeCards(node);
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
