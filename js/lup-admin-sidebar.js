/* LinkUUp backend drawer: the current Bootstrap sidebar and the older GDO
 * drawer use different markup.  Keep both usable and let the visible page
 * surface close an open mobile drawer, just like the app. */
(function () {
    'use strict';

    function initialiseLegacyDrawer() {
        var checkbox = document.getElementById('gdo-left-nav');
        if (!checkbox || checkbox.dataset.lupDrawerBound === '1') {
            return;
        }
        checkbox.dataset.lupDrawerBound = '1';

        function setOpen(open) {
            checkbox.checked = open;
            document.body.classList.toggle('lup-admin-drawer-open', open);
        }

        checkbox.addEventListener('change', function () { setOpen(checkbox.checked); });
        document.addEventListener('pointerdown', function (event) {
            if (!checkbox.checked) {
                return;
            }
            var drawer = document.getElementById('gdo-left-bar');
            var trigger = document.querySelector('label[for="gdo-left-nav"]');
            if ((drawer && drawer.contains(event.target)) ||
                (trigger && trigger.contains(event.target)) || event.target === checkbox) {
                return;
            }
            setOpen(false);
        }, true);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && checkbox.checked) {
                setOpen(false);
            }
        });
        setOpen(checkbox.checked);
    }

    function initialiseBootstrapDrawer() {
        var trigger = document.getElementById('sidebarToggle');
        var drawer = document.getElementById('sidebar-wrapper');
        var page = document.getElementById('page-content-wrapper');
        if (!trigger || !drawer || trigger.dataset.lupDrawerBound === '1') {
            return;
        }
        trigger.dataset.lupDrawerBound = '1';

        function isMobile() {
            return window.matchMedia('(max-width: 767.98px)').matches;
        }

        function isOpen() {
            return isMobile() && document.body.classList.contains('sb-sidenav-toggled');
        }

        function sync() {
            var open = isOpen();
            document.body.classList.toggle('lup-sidebar-drawer-open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function close() {
            if (!isOpen()) {
                return;
            }
            document.body.classList.remove('sb-sidenav-toggled');
            sync();
        }

        trigger.addEventListener('click', function () {
            window.setTimeout(sync, 0);
        });
        /* The visible page surface is the only outside-close control. Catch
         * the whole pointer sequence there before any legacy page action sees
         * it; this avoids a full-screen overlay and keeps the top bar intact. */
        if (page) {
            var swallowUntil = 0;
            function isOutside(event) {
                return !drawer.contains(event.target) && !trigger.contains(event.target);
            }
            page.addEventListener('pointerdown', function (event) {
                if (!isOpen() || !isOutside(event)) {
                    return;
                }
                swallowUntil = Date.now() + 800;
                event.preventDefault();
                event.stopImmediatePropagation();
                close();
            }, true);
            page.addEventListener('pointerup', function (event) {
                if (Date.now() > swallowUntil || !isOutside(event)) {
                    return;
                }
                event.preventDefault();
                event.stopImmediatePropagation();
            }, true);
            page.addEventListener('click', function (event) {
                if (Date.now() > swallowUntil || !isOutside(event)) {
                    return;
                }
                swallowUntil = 0;
                event.preventDefault();
                event.stopImmediatePropagation();
            }, true);
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
        window.addEventListener('resize', sync, {passive: true});
        sync();
    }

    function initialise() {
        initialiseLegacyDrawer();
        initialiseBootstrapDrawer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise);
    } else {
        initialise();
    }
}());
