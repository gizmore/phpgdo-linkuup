/* Keep the legacy backend drawer predictable. Its visual trigger remains in
 * the theme; this only synchronises the state and makes the page surface a
 * reliable close target. */
(function () {
    'use strict';

    function initialise() {
        var checkbox = document.getElementById('gdo-left-nav');
        var page = document.getElementById('gdo-pagewrap');
        if (!checkbox || !page || checkbox.dataset.lupDrawerBound === '1') {
            return;
        }
        checkbox.dataset.lupDrawerBound = '1';

        function setOpen(open) {
            checkbox.checked = open;
            document.body.classList.toggle('lup-admin-drawer-open', open);
        }

        checkbox.addEventListener('change', function () { setOpen(checkbox.checked); });
        /* The remaining welcome surface is the natural close target. Using a
         * document capture handler also works when an inner page component
         * stops bubbling its own click event. */
        document.addEventListener('pointerdown', function (event) {
            if (!checkbox.checked) {
                return;
            }
            var drawer = document.getElementById('gdo-left-bar');
            var trigger = document.querySelector('label[for="gdo-left-nav"]');
            if ((drawer && drawer.contains(event.target)) ||
                (trigger && trigger.contains(event.target)) ||
                event.target === checkbox) {
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise);
    } else {
        initialise();
    }
}());
