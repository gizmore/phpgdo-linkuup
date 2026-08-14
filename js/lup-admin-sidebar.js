/* Reliable LinkUUp backend drawer control. The old Classic theme relied only
 * on labels and a hidden checkbox, which can become unreachable after opening. */
(function () {
    'use strict';

    function initialise() {
        var checkbox = document.getElementById('gdo-left-nav');
        if (!checkbox || document.getElementById('lup-admin-drawer-toggle')) {
            return;
        }

        var toggle = document.createElement('button');
        toggle.id = 'lup-admin-drawer-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-controls', 'gdo-left-bar');

        var shade = document.createElement('button');
        shade.id = 'lup-admin-drawer-shade';
        shade.type = 'button';
        shade.setAttribute('aria-label', 'Navigation schließen');

        function setOpen(open) {
            checkbox.checked = open;
            document.body.classList.toggle('lup-admin-drawer-open', open);
            toggle.textContent = open ? '×' : '☰';
            toggle.setAttribute('aria-label', open ? 'Navigation schließen' : 'Navigation öffnen');
        }

        toggle.addEventListener('click', function () { setOpen(!checkbox.checked); });
        shade.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && checkbox.checked) {
                setOpen(false);
            }
        });

        document.body.appendChild(shade);
        document.body.appendChild(toggle);
        setOpen(checkbox.checked);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise);
    } else {
        initialise();
    }
}());
