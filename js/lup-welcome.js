/* A small, optional arrival transition for the LinkUUp welcome journey. */
(() => {
    'use strict';
    const run = () => {
        const normalizeAccountGrid = () => {
            /* The arrival page uses Bootstrap's native overlay unchanged.
             * Repositioning its legacy links after a tap made the panel flash
             * and immediately collapse on some phone browsers. */
            if (document.body.classList.contains('lup-arrival-active')) return;
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
        if (document.body.classList.contains('lup-arrival-active')) {
            document.querySelectorAll('#navbarSupportedContent a').forEach((link) => {
                if (/^Ausloggen\b/i.test(link.textContent.trim())) {
                    link.textContent = 'Ausloggen';
                }
            });
        }
        const animateArrivalPin = () => {
            const pin = document.querySelector('.lup-arrival-world-pin > i');
            if (!pin || pin.dataset.lupPinAnimated === '1' ||
                window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
                typeof pin.animate !== 'function') return;
            pin.dataset.lupPinAnimated = '1';
            /* Each short sequence is generated anew. That retains a calm,
             * physical rhythm but avoids the obvious identical CSS loop. */
            pin.style.setProperty('animation', 'none', 'important');
            pin.style.setProperty('will-change', 'transform', 'important');
            const random = (min, max) => min + Math.random() * (max - min);
            const nextMotion = () => {
                const leftA = random(-3.2, -1.1);
                const rightA = random(1.2, 3.6);
                const leftB = random(-2.6, -0.8);
                const liftA = random(18, 24);
                const liftB = random(10, 16);
                const settle = random(3, 6);
                const animation = pin.animate([
                    { transform: 'translate3d(-50%,-10px,0) rotate(-1.2deg)', offset: 0 },
                    { transform: `translate3d(calc(-50% + ${leftA}px),-${liftA}px,0) rotate(${random(-4.2, -2.3)}deg)`, offset: .17 },
                    { transform: `translate3d(calc(-50% + ${rightA}px),-${liftB}px,0) rotate(${random(1.6, 3.5)}deg)`, offset: .39 },
                    { transform: `translate3d(calc(-50% + ${leftB}px),-${settle}px,0) rotate(${random(-2.4, -.6)}deg)`, offset: .58 },
                    { transform: 'translate3d(-50%,0,0) rotate(0deg)', offset: .67 },
                    { transform: `translate3d(calc(-50% + ${random(-1.2, 1.8)}px),-${random(4, 8)}px,0) rotate(${random(-1.3, 1.5)}deg)`, offset: .82 },
                    { transform: 'translate3d(-50%,-10px,0) rotate(-1.2deg)', offset: 1 },
                ], {
                    duration: random(4800, 6200),
                    easing: 'cubic-bezier(.33,.01,.32,1)',
                    fill: 'forwards',
                });
                animation.onfinish = () => window.setTimeout(nextMotion, random(80, 420));
            };
            nextMotion();
        };
        animateArrivalPin();
        const accountToggle = document.querySelector('[aria-controls="navbarSupportedContent"]');
        if (!document.body.classList.contains('lup-arrival-active') && accountToggle && accountToggle.dataset.lupAccountBound !== '1') {
            accountToggle.dataset.lupAccountBound = '1';
            /* Bootstrap owns the actual collapse state. We only normalize
             * the legacy account links after its state change has completed. */
            accountToggle.addEventListener('click', () => window.requestAnimationFrame(normalizeAccountGrid));
        }

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
                    if (/Freund\(in\) hinzufügen/i.test(label)) link.textContent = 'Freund hinzufügen';
                    else if (/^Freunde/i.test(label)) link.textContent = 'Meine Freunde';
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
            if (listTitle) listTitle.textContent = 'Dein Freundeskreis';
            if (list && !list.querySelector('.gdt-list-item')) {
                const empty = document.createElement('section');
                empty.className = 'lup-friends-empty';
                empty.innerHTML = '<div class="lup-friends-empty-art" aria-hidden="true"><i></i><b></b><em></em><span></span></div><div class="lup-friends-empty-copy"><span>DEIN FREUNDESKREIS</span><h2>Begegnungen bleiben verbunden.</h2><p>Hier siehst du deine Freunde, eingehende Anfragen und Menschen, mit denen du vor Ort in Kontakt bleiben möchtest.</p><a href="/linkuup;welcome.html?_lang=de">Orte entdecken</a></div>';
                list.append(empty);
            }
        };
        const setupFriendsModule = () => {
            const path = window.location.pathname.toLowerCase();
            if (!path.includes('friends;')) return;
            document.body.classList.add('lup-friends-module');
            const labels = [
                [/Freund.*hinzuf/i, 'Freund hinzufügen'],
                [/^(?:Ihre |Deine )?Freunde/i, 'Meine Freunde'],
                [/^Eingehende/i, 'Eingehende Anfragen'],
                [/^Gesendete/i, 'Gesendete Anfragen'],
            ];
            const links = Array.from(document.querySelectorAll('#top .gdt-bar.horizontal a, .lup-friends-tabs a'));
            links.forEach((link) => {
                const original = link.textContent.trim();
                const match = labels.find(([pattern]) => pattern.test(original));
                if (!match) return;
                link.textContent = match[1];
                link.closest('.gdt-link')?.classList.toggle('lup-friends-tab-active', link.href.toLowerCase().includes(path.split('/').pop()));
            });
        };
        setupFriendsModule();
        setupFriendsPage();
        const polishFriendsNavigation = () => {
            if (!window.location.pathname.toLowerCase().includes('friends;friendlist')) return;
            const addLink = Array.from(document.querySelectorAll('a')).find((link) => /Freund.*hinzuf/i.test(link.textContent));
            const tabs = addLink && addLink.closest('.gdt-container, .gdt-menu, .gdt-bar');
            if (tabs) {
                tabs.classList.add('lup-friends-tabs');
                tabs.querySelectorAll('a').forEach((link) => {
                    const label = link.textContent.trim();
                    if (/Freund.*hinzuf/i.test(label)) link.textContent = 'Freund hinzufügen';
                    else if (/^Freunde/i.test(label)) link.textContent = 'Meine Freunde';
                    else if (/^Eingehende/i.test(label)) link.textContent = 'Eingehende Anfragen';
                    else if (/^Gesendete/i.test(label)) link.textContent = 'Gesendete Anfragen';
                });
            }
            const title = Array.from(document.querySelectorAll('#content-wrap .gdt-list-title, #content-wrap h1, #content-wrap h2, #content-wrap h3, #content-wrap h4')).find((node) => /(?:Ihre|Deine) Freunde/.test(node.textContent) && !node.closest('.lup-friends-hero'));
            if (title) {
                const visibleTitle = title.matches('.gdt-list-title') ? title.querySelector('h1,h2,h3,h4') || title : title;
                visibleTitle.textContent = 'Dein Freundeskreis';
            }
        };
        polishFriendsNavigation();
        window.setTimeout(() => { setupFriendsModule(); polishFriendsNavigation(); }, 500);

        const links = document.querySelectorAll('[data-lup-scroll]');
        if (!links.length) return;
        links.forEach((link) => link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) return;
            event.preventDefault();
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            /* An expanded explanation belongs to the step the visitor just
             * read. Before moving on, compact earlier steps again so their
             * extra height can never make the next destination appear offset
             * or leave its continuation button below the visible area. */
            document.querySelectorAll('.lup-arrival-more[open]').forEach((details) => {
                if (!target.contains(details)) details.open = false;
            });
            // The section itself has generous visual breathing room.  On a
            // phone that put the actual heading below the fold, so navigate
            // to the first visible content node instead.
            const presentationTarget = target.querySelector('header') || target;
            target.classList.remove('lup-arrival-section-enter');
            void target.offsetWidth;
            target.classList.add('lup-arrival-section-enter');
            /* The backend owns its own vertical scrolling area.  Native
             * scrollIntoView() occasionally selected the document instead,
             * especially on mobile, leaving the second journey button still
             * hidden below the fold. */
            const scroller = document.querySelector('#page-content-wrapper');
            const header = document.querySelector('#page-content-wrapper > .navbar');
            if (scroller && scroller.scrollHeight > scroller.clientHeight + 2) {
                const destination = presentationTarget.getBoundingClientRect().top
                    - scroller.getBoundingClientRect().top
                    + scroller.scrollTop
                    - (header ? header.getBoundingClientRect().height + 12 : 12);
                scroller.scrollTo({ top: Math.max(0, destination), behavior: reduced ? 'auto' : 'smooth' });
            } else {
                const offset = header ? header.getBoundingClientRect().height + 12 : 12;
                const destination = presentationTarget.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top: Math.max(0, destination), behavior: reduced ? 'auto' : 'smooth' });
            }
            window.setTimeout(() => target.classList.remove('lup-arrival-section-enter'), reduced ? 10 : 900);
        }));

        /* Reading an explanation must not trigger a jump. Let the browser
         * finish its native layout first, then preserve the reader's current
         * position in the section. */
        document.querySelectorAll('.lup-arrival-more').forEach((details) => {
            details.addEventListener('toggle', () => {
                const section = details.closest('.lup-arrival-hero, .lup-arrival-journey, .lup-arrival-categories, .lup-arrival-principles');
                if (!section || !details.open) return;
                window.requestAnimationFrame(() => section.classList.add('lup-arrival-reading'));
                window.setTimeout(() => section.classList.remove('lup-arrival-reading'), 260);
            });
        });

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
