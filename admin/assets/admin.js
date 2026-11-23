(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    var backdrop = document.querySelector('[data-sidebar-close]');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var lastFocus = null;

    function setSidebar(open) {
        if (!sidebar || !toggle) return;
        sidebar.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        if (backdrop) backdrop.hidden = !open;
        if (open) {
            lastFocus = document.activeElement;
            var first = sidebar.querySelector('a, button');
            if (first) window.setTimeout(function () { first.focus({ preventScroll: true }); }, 120);
        } else if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus({ preventScroll: true });
            lastFocus = null;
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setSidebar(!sidebar.classList.contains('is-open'));
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { setSidebar(false); });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
        }
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 960 && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
        }
    });

    /* Toasts ------------------------------------------------------------- */
    var toasts = Array.prototype.slice.call(document.querySelectorAll('[data-toast]'));

    function dismissToast(node) {
        if (!node || node.classList.contains('is-leaving')) return;
        node.classList.add('is-leaving');
        window.setTimeout(function () { node.remove(); }, 220);
    }

    toasts.forEach(function (node) {
        var close = node.querySelector('[data-toast-close]');
        if (close) close.addEventListener('click', function () { dismissToast(node); });
    });

    /* Confirm dialog ----------------------------------------------------- */
    var dialog = document.getElementById('confirm-dialog');
    var dialogText = document.getElementById('confirm-text');
    var pending = null;

    function askConfirm(message, onConfirm) {
        if (!dialog || typeof dialog.showModal !== 'function') {
            if (window.confirm(message)) onConfirm();
            return;
        }
        pending = onConfirm;
        if (dialogText) dialogText.textContent = message;
        dialog.showModal();
    }

    if (dialog) {
        dialog.addEventListener('close', function () {
            var action = pending;
            pending = null;
            if (dialog.returnValue === 'confirm' && typeof action === 'function') {
                action();
            }
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-confirm]');
        if (!link) return;
        event.preventDefault();
        var href = link.getAttribute('href');
        askConfirm(link.getAttribute('data-confirm'), function () {
            window.location.href = href;
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.confirmDone === '1') {
            form.dataset.confirmDone = '';
            return;
        }
        var message = form.getAttribute('data-confirm');
        if (message && !form.dataset.confirmDisabled) {
            event.preventDefault();
            askConfirm(message, function () {
                form.dataset.confirmDone = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
            return;
        }

        var button = event.submitter || form.querySelector('button[type="submit"]');
        if (button && !button.classList.contains('is-busy')) {
            var label = button.getAttribute('data-busy');
            if (label) {
                button.classList.add('is-busy');
                button.setAttribute('aria-busy', 'true');
                button.insertAdjacentHTML('afterbegin', '<span class="abtn__spin" aria-hidden="true"></span>');
                var textNode = Array.prototype.slice.call(button.childNodes).filter(function (node) {
                    return node.nodeType === 3 && node.nodeValue.trim() !== '';
                })[0];
                if (textNode) textNode.nodeValue = ' ' + label;
            }
        }
    });

    /* Motion layer ------------------------------------------------------- */
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduced) {
        Array.prototype.forEach.call(document.querySelectorAll('.stat__value'), function (el) {
            var match = el.textContent.trim().match(/^([^\d]*)([\d.]+)(.*)$/);
            if (!match) return;
            var targetNumber = parseInt(match[2].replace(/\./g, ''), 10);
            if (!isFinite(targetNumber) || targetNumber === 0) return;
            var prefix = match[1];
            var suffix = match[3];
            var width = el.getBoundingClientRect().width;
            el.style.minWidth = Math.ceil(width) + 'px';
            var start = null;
            var duration = 700;
            function tick(now) {
                if (start === null) start = now;
                var progress = Math.min(1, (now - start) / duration);
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = prefix + Math.round(targetNumber * eased).toLocaleString('id-ID') + suffix;
                if (progress < 1) window.requestAnimationFrame(tick);
            }
            el.textContent = prefix + '0' + suffix;
            window.requestAnimationFrame(tick);
        });
    }

    var routebar = document.createElement('div');
    routebar.className = 'routebar';
    routebar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(routebar);
    var leaving = false;

    function startRoute() {
        if (leaving) return;
        leaving = true;
        routebar.classList.add('is-on');
        window.requestAnimationFrame(function () { routebar.style.transform = 'scaleX(0.78)'; });
        var page = document.querySelector('.page');
        if (page) page.classList.add('is-leaving');
    }

    function resetRoute() {
        leaving = false;
        routebar.classList.remove('is-on');
        routebar.style.transform = 'scaleX(0)';
        var page = document.querySelector('.page');
        if (page) page.classList.remove('is-leaving');
    }

    if (!reduced) {
        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[href]');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;
            if (link.hasAttribute('data-confirm')) return;
            var raw = link.getAttribute('href');
            if (raw === '' || raw.charAt(0) === '#' || raw.indexOf('export=csv') !== -1) return;
            var url;
            try { url = new URL(link.href, window.location.href); } catch (error) { return; }
            if (url.origin !== window.location.origin) return;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== '') return;
            startRoute();
        });

        document.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            startRoute();
        });
    }

    window.addEventListener('pageshow', resetRoute);
    window.setTimeout(function () { if (leaving) resetRoute(); }, 9000);

    /* Demo credentials --------------------------------------------------- */
    var demoFill = document.querySelector('[data-demo-fill]');
    if (demoFill) {
        demoFill.addEventListener('click', function () {
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            if (email) { email.value = demoFill.getAttribute('data-email') || ''; }
            if (password) {
                password.value = demoFill.getAttribute('data-password') || '';
                password.focus();
            }
        });
    }

    /* -------------------------------------------------- Password field helpers */
    var revealIcons = {
        on: '<svg class="ico" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
        off: '<svg class="ico" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4.5 20 20"/><path d="M9.6 6.2A9.6 9.6 0 0 1 12 5.8c6 0 9.5 6.2 9.5 6.2a17 17 0 0 1-3 3.6"/><path d="M6.4 8.1A17 17 0 0 0 2.5 12S6 18.2 12 18.2a9.4 9.4 0 0 0 3.2-.5"/><path d="M10 10a3 3 0 0 0 4 4"/></svg>'
    };

    Array.prototype.forEach.call(document.querySelectorAll('[data-reveal]'), function (button) {
        var input = document.getElementById(button.getAttribute('data-reveal'));
        if (!input) return;
        button.innerHTML = revealIcons.on;
        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            button.innerHTML = show ? revealIcons.off : revealIcons.on;
            input.focus({ preventScroll: true });
        });
    });

    var pwSource = document.querySelector('[data-pw-source]');
    var pwReq = document.querySelector('[data-pw-req]');
    var pwConfirm = document.querySelector('[data-pw-confirm]');
    var pwMatch = document.querySelector('[data-pw-match]');

    function checkPassword() {
        if (!pwSource) return '';
        var value = pwSource.value;
        var met = {
            length: value.length >= 10,
            letter: /[a-zA-Z]/.test(value),
            digit: /[0-9]/.test(value)
        };
        if (pwReq) {
            Array.prototype.forEach.call(pwReq.querySelectorAll('[data-req]'), function (item) {
                item.classList.toggle('is-met', !!met[item.getAttribute('data-req')]);
            });
        }
        if (pwConfirm && pwMatch) {
            var same = value !== '' && value === pwConfirm.value;
            pwMatch.hidden = !same;
        }
        return value;
    }

    if (pwSource) {
        pwSource.addEventListener('input', checkPassword);
        checkPassword();
    }
    if (pwConfirm) {
        pwConfirm.addEventListener('input', checkPassword);
    }

    /* Field bertanda error melepas tanda saat diperbaiki */
    Array.prototype.forEach.call(document.querySelectorAll('.field.has-error input, .field.has-error select, .field.has-error textarea'), function (input) {
        input.addEventListener('input', function () {
            input.removeAttribute('aria-invalid');
            var field = input.closest('.field');
            if (field) field.classList.remove('has-error');
        });
    });

    /* Fokuskan kolom pertama yang bermasalah */
    var firstInvalid = document.querySelector('.field.has-error input, .field.has-error select, .field.has-error textarea');
    if (firstInvalid && !firstInvalid.autofocus) {
        window.setTimeout(function () { firstInvalid.focus({ preventScroll: true }); }, 60);
    }

    /* Copy order code ---------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy]');
        if (!button) return;
        var value = button.getAttribute('data-copy');
        if (!value) return;
        var label = button.querySelector('[data-copy-label]');
        var original = label ? label.textContent : '';
        var done = function () {
            button.classList.add('is-copied');
            if (label) label.textContent = 'Tersalin';
            window.setTimeout(function () {
                button.classList.remove('is-copied');
                if (label) label.textContent = original;
            }, 1400);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(done, done);
        } else {
            done();
        }
    });
})();
