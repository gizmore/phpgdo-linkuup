/* A small, optional arrival transition for the LinkUUp welcome journey. */
(() => {
    'use strict';
    const run = () => {
        const normalizeAccountGrid = () => {
            const nav = document.querySelector('#navbarSupportedContent .navbar-nav');
            if (!nav) return;
            const items = Array.from(nav.children).filter((item) => item.classList.contains('nav-item'));
            const isAccountArea = items.some((item) => /(?:Konto|Ausloggen|Admin|Team-Bereich)/i.test(item.textContent));
            if (!isAccountArea) return;
            items.forEach((item) => {
                const anchors = Array.from(item.querySelectorAll('a'));
                const accountLink = anchors.find((anchor) => anchor.textContent.trim() === 'Konto');
                if (accountLink) {
                    const accountItem = accountLink.closest('.gdt-link');
                    if (accountItem) accountItem.style.setProperty('display', 'none', 'important');
                }
                const link = anchors.find((anchor) => anchor !== accountLink);
                if (!link) return;
                if (link.textContent.trim().startsWith('Ausloggen')) link.textContent = 'Ausloggen';
                item.style.setProperty('position', 'relative', 'important');
                item.style.setProperty('min-height', '46px', 'important');
                item.style.setProperty('grid-column', 'auto', 'important');
                link.style.setProperty('position', 'absolute', 'important');
                link.style.setProperty('inset', '0', 'important');
                link.style.setProperty('display', 'flex', 'important');
                link.style.setProperty('align-items', 'center', 'important');
                link.style.setProperty('width', '100%', 'important');
                link.style.setProperty('max-width', 'none', 'important');
                link.style.setProperty('min-width', '0', 'important');
            });
        };
        normalizeAccountGrid();
        const accountToggle = document.querySelector('[aria-controls="navbarSupportedContent"]');
        if (accountToggle) accountToggle.addEventListener('click', () => window.setTimeout(normalizeAccountGrid, 0));

        const setupFriendsPage = () => {
            if (!window.location.pathname.toLowerCase().includes('friends;friendlist')) return;
            const content = document.querySelector('#content-wrap');
            if (!content || content.dataset.lupFriendsReady === '1') return;
            content.dataset.lupFriendsReady = '1';
            document.body.classList.add('lup-friends-page');
            const addFriendLink = Array.from(document.querySelectorAll('a')).find((link) => /Freund\(in\) hinzufügen/i.test(link.textContent));
            const friendTabs = addFriendLink && addFriendLink.closest('.gdt-container, .gdt-menu, .gdt-bar');
            if (friendTabs) {
                friendTabs.classList.add('lup-friends-tabs');
                friendTabs.querySelectorAll('a').forEach((link) => {
                    const label = link.textContent.trim();
                    if (/Freund\(in\) hinzufügen/i.test(label)) link.textContent = 'Freundschaft anfragen';
                    else if (/^Freunde/i.test(label)) link.textContent = 'Deine Freunde';
                    else if (/^Eingehende/i.test(label)) link.textContent = 'Eingehende Anfragen';
                    else if (/^Gesendete/i.test(label)) link.textContent = 'Gesendete Anfragen';
                });
            }
            const heading = content.querySelector('.gdt-list-title, h1, h2, h3');
            const hero = document.createElement('section');
            hero.className = 'lup-friends-hero';
            hero.innerHTML = '<div><span>DEIN KREIS</span><h1>Freunde, die wirklich da sind.</h1><p>Begegnungen werden zu Verbindungen – direkt, respektvoll und auf Augenhöhe.</p></div><div class="lup-friends-orbit" aria-hidden="true"><i></i><b></b><em></em></div>';
            if (heading) heading.before(hero); else content.prepend(hero);
            const list = content.querySelector('.gdt-list');
            const listTitle = content.querySelector('.gdt-list-title h3');
            if (listTitle) listTitle.textContent = 'Deine Freunde';
            if (list && !list.querySelector('.gdt-list-item')) {
                const empty = document.createElement('section');
                empty.className = 'lup-friends-empty';
                empty.innerHTML = '<div class="lup-friends-empty-mark" aria-hidden="true"><span></span><span></span><span></span></div><h2>Dein Kreis wartet.</h2><p>Wenn du jemandem vor Ort begegnest, kannst du eine Freundschaft anfragen. Hier bleiben später eure Verbindungen griffbereit.</p><a href="/linkuup;welcome.html?_lang=de">Orte in deiner Nähe entdecken</a>';
                list.append(empty);
            }
        };
        setupFriendsPage();
        const polishFriendsNavigation = () => {
            if (!window.location.pathname.toLowerCase().includes('friends;friendlist')) return;
            const addLink = Array.from(document.querySelectorAll('a')).find((link) => /Freund.*hinzuf/i.test(link.textContent));
            const tabs = addLink && addLink.closest('.gdt-container, .gdt-menu, .gdt-bar');
            if (tabs) {
                tabs.classList.add('lup-friends-tabs');
                tabs.querySelectorAll('a').forEach((link) => {
                    const label = link.textContent.trim();
                    if (/Freund.*hinzuf/i.test(label)) link.textContent = 'Freundschaft anfragen';
                    else if (/^Freunde/i.test(label)) link.textContent = 'Deine Freunde';
                    else if (/^Eingehende/i.test(label)) link.textContent = 'Eingehende Anfragen';
                    else if (/^Gesendete/i.test(label)) link.textContent = 'Gesendete Anfragen';
                });
            }
            const title = Array.from(document.querySelectorAll('#content-wrap h1,#content-wrap h2,#content-wrap h3')).find((node) => /(?:Ihre|Deine) Freunde/.test(node.textContent) && !node.closest('.lup-friends-hero'));
            if (title) title.textContent = 'Deine Freunde';
        };
        polishFriendsNavigation();
        window.setTimeout(polishFriendsNavigation, 500);

        const links = document.querySelectorAll('[data-lup-scroll]');
        if (!links.length) return;
        links.forEach((link) => link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) return;
            event.preventDefault();
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            target.classList.remove('lup-arrival-section-enter');
            void target.offsetWidth;
            target.classList.add('lup-arrival-section-enter');
            target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
            window.setTimeout(() => target.classList.remove('lup-arrival-section-enter'), reduced ? 10 : 900);
        }));

        if (!('IntersectionObserver' in window)) return;
        const sections = document.querySelectorAll('.lup-arrival-journey, .lup-arrival-categories, .lup-arrival-principles');
        const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('lup-arrival-in-view');
            observer.unobserve(entry.target);
        }), { threshold: 0.18 });
        sections.forEach((section) => observer.observe(section));
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run) : run();
})();
