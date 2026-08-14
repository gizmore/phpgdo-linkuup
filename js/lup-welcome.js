/* A small, optional arrival transition for the LinkUUp welcome journey. */
(() => {
    'use strict';
    const run = () => {
        const link = document.querySelector('[data-lup-journey-link]');
        const journey = document.getElementById('lup-arrival-journey');
        if (!link || !journey) return;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            journey.classList.remove('lup-journey-enter');
            void journey.offsetWidth;
            journey.classList.add('lup-journey-enter');
            journey.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
            window.setTimeout(() => journey.classList.remove('lup-journey-enter'), reduced ? 10 : 1650);
        });
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run) : run();
})();
