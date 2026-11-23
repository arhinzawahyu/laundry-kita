/* Halaman akun publik: tampilkan kata sandi, syarat sandi, dan fokus isian. */
(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------------------------------------------------- kata sandi */
    document.querySelectorAll('[data-pw-toggle]').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('data-pw-toggle'));
        if (!input) {
            return;
        }
        var label = button.querySelector('[data-pw-label]');
        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            if (label) {
                label.textContent = show ? 'Sembunyikan' : 'Tampilkan';
            }
            input.focus({ preventScroll: true });
        });
    });

    var password = document.querySelector('[data-pw-checks]');
    var checklist = document.querySelector('[data-pwreq]');

    function markCheck(key, ok) {
        if (!checklist) {
            return;
        }
        var item = checklist.querySelector('[data-pw-check="' + key + '"]');
        if (item) {
            item.classList.toggle('is-ok', ok);
        }
    }

    function checkPassword() {
        var value = password ? password.value : '';
        markCheck('length', value.length >= 10);
        markCheck('letter', /[a-zA-Z]/.test(value));
        markCheck('digit', /[0-9]/.test(value));
    }

    if (password) {
        password.addEventListener('input', function () {
            checkPassword();
            checkMatch();
        });
        checkPassword();
    }

    var confirmInput = document.querySelector('[data-pw-match]');
    var matchHint = document.querySelector('[data-pw-hint]');

    function checkMatch() {
        if (!confirmInput || !matchHint || !password) {
            return;
        }
        var value = confirmInput.value;
        if (value === '') {
            matchHint.hidden = true;
            matchHint.classList.remove('is-ok');
            return;
        }
        var ok = value === password.value;
        matchHint.hidden = false;
        matchHint.textContent = ok ? 'Kata sandi sudah sama.' : 'Kata sandi belum sama.';
        matchHint.classList.toggle('is-ok', ok);
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
        checkMatch();
    }

    /* -------------------------------------------------- fokus isian pertama */
    var autofocus = document.querySelector('[data-autofocus]');
    if (autofocus && window.innerWidth > 720) {
        autofocus.focus({ preventScroll: false });
    }

    /* --------------------------------------------- bersihkan galat saat isi */
    document.querySelectorAll('[data-field]').forEach(function (field) {
        var input = field.querySelector('input, select, textarea');
        var error = field.querySelector('.field__err');
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            if (!field.classList.contains('has-error')) {
                return;
            }
            field.classList.remove('has-error');
            input.removeAttribute('aria-invalid');
            if (error) {
                error.textContent = '';
            }
        });
    });

    /* ---------------------------------------------- isi keranjang cucian */
    /* Satu lipatan cucian muncul tiap seperempat isian wajib terisi. Penanda
       kemajuan ini murni hiasan (aria-hidden), validasi tetap lewat pesan
       galat di tiap kolom. */
    document.querySelectorAll('[data-auth-form]').forEach(function (form) {
        var items = document.querySelectorAll('.auth-item');
        if (!items.length) {
            return;
        }
        var required = Array.prototype.slice.call(form.querySelectorAll('input[required]'));
        if (!required.length) {
            return;
        }

        function updateLoad() {
            var done = required.filter(function (input) {
                return input.value.trim() !== '';
            }).length;
            var steps = done === 0 ? 0 : Math.ceil((done / required.length) * items.length);
            items.forEach(function (item) {
                item.classList.toggle('is-filled', Number(item.dataset.step) <= steps);
            });
        }

        required.forEach(function (input) {
            input.addEventListener('input', updateLoad);
        });
        updateLoad();
    });

    /* ------------------------------------------------- cegah kirim kosong */
    document.querySelectorAll('[data-auth-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            var invalid = null;
            form.querySelectorAll('input[required]').forEach(function (input) {
                if (input.value.trim() !== '') {
                    return;
                }
                var field = input.closest('[data-field]');
                if (field) {
                    field.classList.add('has-error');
                    var error = field.querySelector('.field__err');
                    if (error && error.textContent.trim() === '') {
                        error.textContent = 'Isian ini belum boleh kosong.';
                    }
                }
                input.setAttribute('aria-invalid', 'true');
                if (!invalid) {
                    invalid = input;
                }
            });
            if (invalid) {
                event.preventDefault();
                invalid.focus({ preventScroll: reduce ? true : false });
                return;
            }

            /* Semua isian terisi: kunci tombol agar tidak terkirim dua kali. */
            form.dataset.submitting = '1';
            document.body.classList.add('is-washing');
            var submit = form.querySelector('.acct-submit');
            if (submit && !submit.classList.contains('is-busy')) {
                submit.classList.add('is-busy');
                submit.dataset.label = submit.textContent;
                submit.textContent = 'Memproses…';
                submit.setAttribute('aria-disabled', 'true');
            }
        });
    });

    /* ------------------------------------------------------ hitung naik ringkas */
    if (!reduce) {
        document.querySelectorAll('.acct-stat__value').forEach(function (value) {
            var target = parseInt(value.textContent.replace(/[^0-9]/g, ''), 10);
            if (!isFinite(target) || target < 2) {
                return;
            }
            var start = null;
            value.classList.add('is-counting');
            value.textContent = '0';

            function step(now) {
                if (start === null) {
                    start = now;
                }
                var progress = Math.min(1, (now - start) / 520);
                var eased = 1 - Math.pow(1 - progress, 3);
                value.textContent = String(Math.round(target * eased));
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    value.textContent = String(target);
                    value.classList.remove('is-counting');
                }
            }

            window.requestAnimationFrame(step);
        });
    }
})();
