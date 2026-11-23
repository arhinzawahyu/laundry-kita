(function () {
    'use strict';

    function boot() {
    var doc = document.documentElement;
    var DATA = window.LK_DATA || {};
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasGsap = !!(window.gsap && window.ScrollTrigger);
    var $ = function (selector, scope) { return (scope || document).querySelector(selector); };
    var $$ = function (selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); };
    var replay = function (node, className) {
        if (!node) { return; }
        node.classList.remove(className);
        void node.offsetWidth;
        node.classList.add(className);
    };
    var lenis = null;
    var tickerFn = null;
    var modalOpen = false;
    var drawerOpen = false;
    var unlockTimer = null;

    if (!hasGsap || reduced) {
        doc.classList.remove('js');
    }
    if (hasGsap) {
        window.gsap.registerPlugin(window.ScrollTrigger);
    }

    /* ---------------------------------------------------------- scroll lock */
    function syncScrollLock() {
        var locked = modalOpen || drawerOpen;
        document.body.classList.toggle('is-locked', locked);
        if (lenis) {
            if (locked) { lenis.stop(); } else { lenis.start(); }
        }
    }

    /* --------------------------------------------------------------- lenis */
    if (hasGsap && !reduced && window.Lenis) {
        lenis = new window.Lenis({ duration: 1.05, smoothWheel: true, wheelMultiplier: 1, touchMultiplier: 1.3 });
        lenis.on('scroll', window.ScrollTrigger.update);
        tickerFn = function (time) { lenis.raf(time * 1000); };
        window.gsap.ticker.add(tickerFn);
        window.gsap.ticker.lagSmoothing(0);
    }

    /* ------------------------------------------------------ header + drawer */
    var header = $('#site-header');
    var menuToggle = $('#menu-toggle');

    function setScrolled(active) {
        if (header) { header.classList.toggle('is-solid', active); }
    }
    if (lenis) {
        lenis.on('scroll', function (event) { setScrolled(event.scroll > 24); });
    } else {
        var scrollTick = false;
        window.addEventListener('scroll', function () {
            if (scrollTick) { return; }
            scrollTick = true;
            window.requestAnimationFrame(function () {
                setScrolled(window.scrollY > 24);
                scrollTick = false;
            });
        }, { passive: true });
    }

    function setDrawer(open) {
        if (!header || !menuToggle) { return; }
        drawerOpen = open;
        header.classList.toggle('is-open', open);
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        syncScrollLock();
        if (open) {
            window.setTimeout(function () {
                var firstLink = $('[data-drawer-link]');
                if (firstLink && drawerOpen) { firstLink.focus({ preventScroll: true }); }
            }, 90);
        }
    }
    if (menuToggle) {
        menuToggle.addEventListener('click', function () { setDrawer(!drawerOpen); });
    }
    $$('[data-drawer-link]').forEach(function (link) {
        link.addEventListener('click', function () { setDrawer(false); });
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && drawerOpen) { setDrawer(false); }
    });

    /* ------------------------------------------------------- active section */
    var navLinks = $$('[data-nav-link]');
    var sectionIds = navLinks.map(function (link) { return link.getAttribute('data-nav-link'); });
    var observed = $$('main section[id]').filter(function (section) { return sectionIds.indexOf(section.id) !== -1; });

    if ('IntersectionObserver' in window) {
        var sectionObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                navLinks.forEach(function (link) {
                    var active = link.getAttribute('data-nav-link') === entry.target.id;
                    link.classList.toggle('is-active', active);
                    if (active) { link.setAttribute('aria-current', 'true'); }
                    else { link.removeAttribute('aria-current'); }
                });
            });
        }, { rootMargin: '-42% 0px -52% 0px', threshold: 0 });
        observed.forEach(function (section) { sectionObserver.observe(section); });
    }

    /* ------------------------------------------------------- anchor scrolling */
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="#"]');
        if (!link) { return; }
        var hash = link.getAttribute('href');
        if (!hash || hash === '#') { return; }
        var target = document.getElementById(hash.slice(1));
        if (!target) { return; }
        event.preventDefault();
        if (drawerOpen) { setDrawer(false); }
        if (lenis) {
            lenis.scrollTo(target, { offset: -((header ? header.offsetHeight : 72) + 8), duration: 1.1 });
        } else {
            target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
        }
        if (window.history && window.history.replaceState) { window.history.replaceState(null, '', hash); }
    });

    /* --------------------------------------------------------- auth modal */
    var modal = $('#auth-modal');
    var modalPanel = modal ? modal.querySelector('.modal__panel') : null;
    var modalClose = modal ? modal.querySelector('[data-modal-close]') : null;
    var lastFocus = null;
    var contextCopy = {
        calc: 'Estimasi Anda siap dikunci. Masuk atau buat akun supaya tarif dan jadwal jemput tersimpan di pesanan.',
        form: 'Jadwal jemput Anda sudah dicatat. Masuk atau buat akun untuk mengonfirmasi pesanan.',
        promo: 'Voucher hanya bisa diklaim dari akun terverifikasi. Masuk atau daftar untuk membuka penawaran.',
        tracking: 'Pelacakan cucian terhubung ke pesanan di akun Anda. Masuk untuk melihat status terbaru.',
        membership: 'Poin menempel pada akun. Masuk atau daftar untuk mulai mengumpulkan poin transaksi.',
        header: 'Masuk untuk memesan jemputan, mengunci tarif, dan melihat riwayat cucian Anda.',
        register: 'Buat akun untuk memesan jemputan dan menyimpan alamat secara otomatis.'
    };

    function renderDraft(draft) {
        if (!modal) { return; }
        var box = $('#modal-draft');
        var list = box ? box.querySelector('[data-modal-draft-list]') : null;
        if (!box || !list) { return; }
        while (list.firstChild) { list.removeChild(list.firstChild); }
        var keys = draft ? Object.keys(draft) : [];
        if (!keys.length) {
            box.hidden = true;
            return;
        }
        keys.forEach(function (key) {
            var row = document.createElement('div');
            var dt = document.createElement('dt');
            var dd = document.createElement('dd');
            dt.textContent = key;
            dd.textContent = draft[key];
            row.appendChild(dt);
            row.appendChild(dd);
            list.appendChild(row);
        });
        box.hidden = false;
    }

    function openAuth(context, draft) {
        if (!modal) { return; }
        lastFocus = document.activeElement;
        var desc = $('[data-modal-desc]');
        if (desc) { desc.textContent = contextCopy[context] || contextCopy.header; }
        renderDraft(draft || null);
        if (unlockTimer) { window.clearTimeout(unlockTimer); }
        modal.hidden = false;
        modalOpen = true;
        syncScrollLock();
        window.requestAnimationFrame(function () { modal.classList.add('is-open'); });
        if (modalPanel) { modalPanel.focus({ preventScroll: true }); }
        document.addEventListener('keydown', onModalKeydown);
    }

    function closeAuth() {
        if (!modal || !modalOpen) { return; }
        modalOpen = false;
        modal.classList.remove('is-open');
        document.removeEventListener('keydown', onModalKeydown);
        unlockTimer = window.setTimeout(function () { modal.hidden = true; }, 280);
        syncScrollLock();
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus({ preventScroll: true });
        }
    }

    function onModalKeydown(event) {
        if (!modal) { return; }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeAuth();
            return;
        }
        if (event.key !== 'Tab') { return; }
        var focusables = $$('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])', modal)
            .filter(function (node) { return node.offsetParent !== null; });
        if (!focusables.length) { return; }
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-modal-close]')) { closeAuth(); }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && drawerOpen) { setDrawer(false); }
    });

    /* ---------------------------------------------------------- draft memory */
    var DRAFT_KEY = 'lkj_pickup_draft_v1';
    var form = $('#pickup-form');

    function readDraft() {
        try {
            var raw = window.sessionStorage.getItem(DRAFT_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }
    function writeDraft(value) {
        try { window.sessionStorage.setItem(DRAFT_KEY, JSON.stringify(value)); } catch (error) { /* storage disabled */ }
    }
    function collectDraft() {
        if (!form) { return null; }
        var names = ['nama', 'whatsapp', 'desa', 'alamat', 'tanggal', 'slot', 'layanan', 'berat', 'catatan'];
        var draft = {};
        names.forEach(function (name) {
            var field = form.elements.namedItem(name);
            if (field && String(field.value).trim() !== '') { draft[name] = String(field.value).trim(); }
        });
        return Object.keys(draft).length ? draft : null;
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-auth]');
        if (!trigger) { return; }
        event.preventDefault();
        var context = trigger.getAttribute('data-auth');
        if (context === 'header-register' || context === 'drawer-register') { context = 'register'; }
        var draft = collectDraft();
        if (context === 'form' && draft) { writeDraft(draft); }
        openAuth(context, draft);
    });

    /* --------------------------------------------------------- hero sequence */
    function initSequence() {
        var canvas = $('#hero-canvas');
        var stage = $('[data-hero-stage]');
        var track = $('[data-hero-track]');
        var frames = Array.isArray(DATA.frames) ? DATA.frames : [];
        if (!canvas || !stage || !track || !frames.length) { return; }

        var ctx = canvas.getContext('2d');
        if (!ctx) { return; }

        var images = new Array(frames.length);
        var current = 0;
        var drawnIndex = -1;
        var ready = false;

        function canvasRatio() { return Math.min(window.devicePixelRatio || 1, 2); }

        function sizeCanvas() {
            var rect = canvas.getBoundingClientRect();
            var ratio = canvasRatio();
            var width = Math.max(1, Math.round(rect.width * ratio));
            var height = Math.max(1, Math.round(rect.height * ratio));
            if (canvas.width !== width || canvas.height !== height) {
                canvas.width = width;
                canvas.height = height;
                if (ready) { paint(current); }
            }
        }

        function paint(index) {
            current = index;
            var image = images[index];
            if (!image || !image.complete || !image.naturalWidth) { return; }
            var cw = canvas.width;
            var ch = canvas.height;
            var scale = Math.max(cw / image.naturalWidth, ch / image.naturalHeight);
            var dw = image.naturalWidth * scale;
            var dh = image.naturalHeight * scale;
            ctx.clearRect(0, 0, cw, ch);
            ctx.drawImage(image, (cw - dw) / 2, (ch - dh) / 2, dw, dh);
            drawnIndex = index;
            if (!ready) {
                ready = true;
                stage.classList.add('is-ready');
            }
        }

        sizeCanvas();

        var cursor = 1;
        var concurrency = window.matchMedia('(max-width: 767px)').matches ? 3 : 6;

        function load(index, done) {
            if (images[index] || index >= frames.length) {
                if (done) { done(); }
                return;
            }
            var image = new Image();
            images[index] = image;
            image.decoding = 'async';
            image.onload = function () {
                if (index === 0 || index === current) { paint(current); }
                if (done) { done(); }
            };
            image.onerror = function () {
                images[index] = null;
                if (done) { done(); }
            };
            image.src = frames[index];
        }

        function worker() {
            if (cursor >= frames.length) { return; }
            var next = cursor;
            cursor += 1;
            load(next, worker);
        }

        load(0, function () {
            sizeCanvas();
            if (hasGsap) { window.ScrollTrigger.refresh(); }
        });
        for (var i = 0; i < concurrency; i += 1) { worker(); }

        if (hasGsap && !reduced) {
            var scrubState = { frame: 0 };
            window.gsap.to(scrubState, {
                frame: frames.length - 1,
                ease: 'none',
                onUpdate: function () {
                    var index = Math.round(scrubState.frame);
                    if (index !== drawnIndex) { paint(index); }
                },
                scrollTrigger: {
                    trigger: track,
                    start: 'top top',
                    end: 'bottom bottom',
                    scrub: 0.5,
                    invalidateOnRefresh: true
                }
            });
        } else {
            paint(0);
        }

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) { window.clearTimeout(resizeTimer); }
            resizeTimer = window.setTimeout(sizeCanvas, 120);
        });
    }

    /* ------------------------------------------------------ motion + reveals */
    function initMotion() {
        var manifesto = $('[data-manifesto]');
        if (manifesto) {
            var words = manifesto.textContent.trim().split(/\s+/);
            manifesto.textContent = '';
            words.forEach(function (word, index) {
                var span = document.createElement('span');
                span.className = 'w';
                span.textContent = word;
                manifesto.appendChild(span);
                if (index < words.length - 1) { manifesto.appendChild(document.createTextNode(' ')); }
            });
        }

        if (!hasGsap || reduced) { return; }

        window.gsap.fromTo('[data-hero-copy] > *', { y: 28, opacity: 0 }, {
            y: 0,
            opacity: 1,
            duration: 0.9,
            ease: 'power3.out',
            stagger: 0.09,
            delay: 0.1
        });
        window.gsap.fromTo('[data-hero-meta]', { y: 16, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: 'power3.out', delay: 0.55 });
        window.gsap.to('[data-hero-copy]', {
            y: -64,
            opacity: 0.15,
            ease: 'none',
            scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom 70%', scrub: true }
        });

        $$('[data-reveal-group]').forEach(function (group) {
            var items = $$('[data-reveal]', group);
            if (!items.length) { return; }
            doc.classList.add('js');
            window.gsap.fromTo(items, { y: 32, opacity: 0 }, {
                y: 0,
                opacity: 1,
                duration: 0.75,
                ease: 'power3.out',
                stagger: 0.08,
                scrollTrigger: { trigger: group, start: 'top 84%', once: true }
            });
        });

        if (manifesto) {
            window.gsap.to($$('.w', manifesto), {
                opacity: 1,
                ease: 'none',
                stagger: 0.06,
                scrollTrigger: { trigger: '#manifesto', start: 'top 74%', end: 'bottom 58%', scrub: true }
            });
        }

        $$('main section[id]').forEach(function (section) {
            var eyebrow = section.querySelector('.eyebrow');
            if (!eyebrow) { return; }
            window.gsap.fromTo(eyebrow, { opacity: 0, x: -14 }, {
                opacity: 1,
                x: 0,
                duration: 0.6,
                ease: 'power2.out',
                scrollTrigger: { trigger: section, start: 'top 86%', once: true }
            });
        });

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { window.ScrollTrigger.refresh(); });
        }
        window.addEventListener('load', function () { window.ScrollTrigger.refresh(); });
    }

    /* ------------------------------------------------------------- carousel */
    function initReviews() {
        var root = $('[data-reviews]');
        if (!root) { return; }
        var track = $('[data-review-track]', root);
        var slides = $$('[data-review-slide]', root);
        var dots = $$('[data-review-dot]', root);
        var prev = $('[data-review-prev]', root);
        var next = $('[data-review-next]', root);
        var viewport = $('[data-review-viewport]', root);
        if (!track || !slides.length) { return; }

        if (slides.length < 2) {
            root.classList.add('is-single');
            return;
        }

        var index = 0;

        function render() {
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
            slides.forEach(function (slide, i) {
                slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
                if (i === index) { dot.setAttribute('aria-current', 'true'); }
                else { dot.removeAttribute('aria-current'); }
            });
        }

        function go(target) {
            index = (target + slides.length) % slides.length;
            render();
        }

        if (prev) { prev.addEventListener('click', function () { go(index - 1); }); }
        if (next) { next.addEventListener('click', function () { go(index + 1); }); }
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { go(i); });
        });

        if (viewport) {
            viewport.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') { event.preventDefault(); go(index - 1); }
                if (event.key === 'ArrowRight') { event.preventDefault(); go(index + 1); }
            });

            var startX = null;
            viewport.addEventListener('pointerdown', function (event) {
                if (event.pointerType === 'mouse') { return; }
                startX = event.clientX;
            });
            viewport.addEventListener('pointerup', function (event) {
                if (startX === null) { return; }
                var delta = event.clientX - startX;
                startX = null;
                if (Math.abs(delta) < 42) { return; }
                go(delta < 0 ? index + 1 : index - 1);
            });
            viewport.addEventListener('pointercancel', function () { startX = null; });
        }

        render();
    }

    /* ----------------------------------------------------------- calculator */
    function initCalculator() {
        var services = DATA.services || {};
        var range = $('#calc-range');
        var number = $('#calc-number');
        var lock = $('#calc-lock');
        if (!range || !number || !services.kiloan) { return; }

        var amount = $('#calc-amount');
        var duration = $('#calc-duration');
        var serviceName = $('#calc-service-name');
        var qtyLabel = $('#calc-qty-label');
        var rateLabel = $('#calc-rate-label');
        var minLabel = $('#calc-min-label');
        var unitLabel = $('#calc-unit-label');
        var hint = $('#calc-hint');
        var live = $('#calc-live');
        var qtyControlLabel = $('#calc-qty-label-control');
        var radios = $$('[data-calc-service]');
        var prefill = $('#form-prefill');
        var prefillText = $('#form-prefill-text');
        var format = new Intl.NumberFormat('id-ID');
        var state = { key: 'kiloan', qty: 4 };
        var lastTotal = null;
        var lastService = null;

        function trimNumber(value) {
            return String(Number(value.toFixed(2))).replace('.', ',');
        }

        function unitText(service, qty) {
            if (service.unit === 'kg') { return trimNumber(qty) + ' kg'; }
            return Math.round(qty) + ' ' + service.unit;
        }

        function paintSlider() {
            var min = parseFloat(range.min) || 0;
            var max = parseFloat(range.max) || 1;
            var percent = ((state.qty - min) / (max - min)) * 100;
            range.style.setProperty('--fill', Math.max(0, Math.min(100, percent)) + '%');
        }

        function render(announce) {
            var service = services[state.key];
            if (!service) { return; }
            var total = state.qty * service.price;
            if (lastTotal !== null && total !== lastTotal) { replay(amount, 'is-ticking'); }
            lastTotal = total;
            amount.textContent = 'Rp ' + format.format(total);
            if (lastService !== null && state.key !== lastService) {
                replay(duration, 'is-swapping');
                replay(serviceName, 'is-swapping');
                replay(rateLabel, 'is-swapping');
            }
            lastService = state.key;
            duration.textContent = service.duration;
            serviceName.textContent = service.label;
            qtyLabel.textContent = unitText(service, state.qty);
            rateLabel.textContent = 'Rp ' + format.format(service.price);
            minLabel.textContent = unitText(service, service.min);
            unitLabel.textContent = service.unit === 'kg' ? 'kg' : 'potong';
            if (qtyControlLabel) {
                qtyControlLabel.textContent = service.unit === 'kg' ? 'Berat cucian (kg)' : 'Jumlah potong';
            }
            if (service.unit === 'kg') {
                hint.textContent = 'Minimal ' + trimNumber(service.min) + ' kg. Berat final ditimbang saat penjemputan.';
            } else {
                hint.textContent = 'Minimal ' + Math.round(service.min) + ' potong. Tarif per jenis barang berbeda.';
            }
            hint.classList.remove('is-warn');
            paintSlider();
            if (announce && live) {
                live.textContent = 'Estimasi Rp ' + format.format(total) + ' untuk ' + unitText(service, state.qty) + ' ' + service.label + '.';
            }
        }

        function applyService(key) {
            var service = services[key];
            if (!service) { return; }
            state.key = key;
            state.qty = service.min;
            range.min = String(service.min);
            range.max = service.unit === 'kg' ? '30' : '20';
            range.step = String(service.step);
            range.value = String(state.qty);
            number.min = String(service.min);
            number.max = range.max;
            number.step = String(service.step);
            number.value = String(state.qty);
            render(true);
        }

        function setQty(value, announce) {
            var service = services[state.key];
            var min = service.min;
            var max = parseFloat(range.max);
            var step = service.step;
            var parsed = parseFloat(String(value).replace(',', '.'));
            if (isNaN(parsed)) { parsed = min; }
            parsed = Math.min(Math.max(parsed, min), max);
            parsed = Math.round(parsed / step) * step;
            state.qty = parsed;
            range.value = String(parsed);
            number.value = String(Number(parsed.toFixed(2)));
            render(announce);
        }

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) { applyService(radio.value); }
            });
        });
        range.addEventListener('input', function () { setQty(range.value, false); });
        range.addEventListener('change', function () { setQty(range.value, true); });
        number.addEventListener('change', function () { setQty(number.value, true); });
        number.addEventListener('input', function () {
            if (number.value === '') { return; }
            setQty(number.value, false);
        });

        if (lock) {
            lock.addEventListener('click', function () {
                var service = services[state.key];
                var summary = {
                    Layanan: service.label,
                    Jumlah: unitText(service, state.qty),
                    Estimasi: 'Rp ' + format.format(state.qty * service.price),
                    'Waktu selesai': service.duration
                };
                var formService = $('#order-layanan');
                if (formService) { formService.value = state.key; }
                if (service.unit === 'kg') {
                    var berat = $('#berat');
                    if (berat) { berat.value = String(state.qty); }
                }
                if (prefill && prefillText) {
                    prefillText.textContent = 'Dari kalkulator: ' + service.label + ', ' + unitText(service, state.qty) + '.';
                    prefill.classList.add('is-visible');
                }
                writeDraft(collectDraft());
                openAuth('calc', summary);
            });
        }

        var clearPrefill = $('#form-prefill-clear');
        if (clearPrefill && prefill) {
            clearPrefill.addEventListener('click', function () {
                prefill.classList.remove('is-visible');
                var formService = $('#order-layanan');
                var berat = $('#berat');
                if (formService) { formService.value = 'kiloan'; }
                if (berat) { berat.value = ''; }
                writeDraft(collectDraft());
            });
        }

        render(false);
    }

    /* ------------------------------------------------------- tracking preview */
    function initTracking() {
        var items = $$('[data-track-step]');
        var fill = $('#track-fill');
        var title = $('#track-status-title');
        var text = $('#track-status-text');
        var live = $('#track-live');
        var next = $('#track-next');
        var reset = $('#track-reset');
        if (!items.length || !title || !text) { return; }
        var stageIndex = 1;
        var stages = [
            { title: 'Menunggu antrean', text: 'Pesanan sudah tercatat dan menunggu slot penjemputan berikutnya.' },
            { title: 'Sedang dicuci', text: 'Cucian sedang diproses di mesin. Tahap berikutnya adalah pengeringan.' },
            { title: 'Sedang dikeringkan', text: 'Cucian dipindahkan ke pengering. Setelah kering, pakaian disetrika dan dilipat.' },
            { title: 'Disetrika dan dilipat', text: 'Pakaian disetrika, dilipat, lalu dikemas per pesanan dengan kode tiket.' },
            { title: 'Siap diantar', text: 'Pesanan siap. Kurir mengantar sesuai slot yang Anda pilih.' }
        ];

        var shownStage = null;

        function render(announce) {
            var changed = stageIndex !== shownStage;
            items.forEach(function (item, index) {
                item.classList.toggle('is-done', index < stageIndex);
                if (index === stageIndex) {
                    item.setAttribute('aria-current', 'step');
                } else {
                    item.removeAttribute('aria-current');
                }
            });
            if (fill) { fill.style.width = ((stageIndex / (stages.length - 1)) * 100) + '%'; }
            title.textContent = stages[stageIndex].title;
            text.textContent = stages[stageIndex].text;
            if (announce && changed) {
                var activeItem = items[stageIndex];
                var status = title.parentNode;
                if (activeItem) { replay(activeItem, 'is-pop'); }
                if (status) { replay(status, 'is-changing'); }
            }
            shownStage = stageIndex;
            if (next) { next.textContent = stageIndex === stages.length - 1 ? 'Ulangi dari antrean' : 'Tahap berikutnya'; }
            if (announce && live) {
                live.textContent = 'Tahap ' + (stageIndex + 1) + ' dari ' + stages.length + ', ' + stages[stageIndex].title + '.';
            }
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                stageIndex = parseInt(item.getAttribute('data-track-step'), 10) || 0;
                render(true);
            });
        });
        if (next) {
            next.addEventListener('click', function () {
                stageIndex = (stageIndex + 1) % stages.length;
                render(true);
            });
        }
        if (reset) {
            reset.addEventListener('click', function () {
                stageIndex = 0;
                render(true);
            });
        }
        render(false);
    }

    /* ---------------------------------------------------------------- form */
    function initForm() {
        var formEl = $('#pickup-form');
        if (!formEl) { return; }
        var alertBox = $('#form-alert');
        var successBox = $('#form-success');
        var submit = $('#form-submit');
        var submitLabelEl = submit ? submit.querySelector('.btn__label') : null;
        var submitLabel = submitLabelEl ? submitLabelEl.textContent : 'Simpan jadwal jemput';
        var today = $('#tanggal') ? $('#tanggal').min : '';
        var maxDate = $('#tanggal') ? $('#tanggal').max : '';

        function fieldWrap(name) { return formEl.querySelector('[data-field="' + name + '"]'); }
        function fieldError(name) {
            var wrap = fieldWrap(name);
            return wrap ? wrap.querySelector('.field__err') : null;
        }
        function setError(name, message) {
            var wrap = fieldWrap(name);
            var err = fieldError(name);
            var input = formEl.elements.namedItem(name);
            if (!wrap || !err) { return; }
            if (message) {
                wrap.classList.add('has-error');
                err.textContent = message;
                if (input) { input.setAttribute('aria-invalid', 'true'); }
            } else {
                wrap.classList.remove('has-error');
                if (input) { input.removeAttribute('aria-invalid'); }
            }
        }
        function setFormAlert(message) {
            if (!alertBox) { return; }
            alertBox.textContent = message || '';
            alertBox.classList.toggle('is-visible', !!message);
        }
        function setSuccess(message) {
            if (!successBox) { return; }
            successBox.textContent = message || '';
            successBox.classList.toggle('is-visible', !!message);
        }

        function validate() {
            var values = {
                nama: formEl.elements.namedItem('nama').value.trim(),
                whatsapp: formEl.elements.namedItem('whatsapp').value.trim(),
                desa: formEl.elements.namedItem('desa').value,
                alamat: formEl.elements.namedItem('alamat').value.trim(),
                tanggal: formEl.elements.namedItem('tanggal').value,
                slot: formEl.elements.namedItem('slot').value,
                layanan: formEl.elements.namedItem('layanan').value,
                berat: formEl.elements.namedItem('berat').value.trim()
            };
            var errors = {};

            ['nama', 'whatsapp', 'desa', 'alamat', 'tanggal', 'slot', 'layanan', 'berat'].forEach(function (name) { setError(name, null); });

            if (values.nama.length < 2 || values.nama.length > 60) {
                errors.nama = 'Tulis nama penerima, 2 sampai 60 karakter.';
            }
            var wa = values.whatsapp.replace(/[^0-9+]/g, '');
            if (wa.indexOf('+62') === 0) { wa = '62' + wa.slice(3); }
            else if (wa.indexOf('0') === 0) { wa = '62' + wa.slice(1); }
            if (!/^628[1-9][0-9]{6,11}$/.test(wa)) {
                errors.whatsapp = 'Nomor WhatsApp belum valid. Contoh: 0812 3456 7890.';
            }
            if (!values.desa) { errors.desa = 'Pilih desa penjemputan dari daftar.'; }
            if (values.alamat.length < 5 || values.alamat.length > 160) {
                errors.alamat = 'Tulis alamat lengkap, 5 sampai 160 karakter.';
            }
            if (!values.tanggal) {
                errors.tanggal = 'Pilih tanggal penjemputan.';
            } else if (today && values.tanggal < today) {
                errors.tanggal = 'Tanggal penjemputan tidak boleh di masa lalu.';
            } else if (maxDate && values.tanggal > maxDate) {
                errors.tanggal = 'Jadwal dibuka maksimal 30 hari ke depan.';
            }
            if (!values.slot) { errors.slot = 'Pilih slot jam penjemputan.'; }
            if (!values.layanan) { errors.layanan = 'Pilih jenis layanan.'; }
            if (values.berat !== '') {
                var berat = parseFloat(values.berat);
                if (isNaN(berat) || berat <= 0 || berat > 100) {
                    errors.berat = 'Berat perkiraan harus antara 0,5 dan 100 kg.';
                }
            }

            Object.keys(errors).forEach(function (name) { setError(name, errors[name]); });
            return errors;
        }

        formEl.addEventListener('input', function () {
            if (formEl.dataset.dirty) { writeDraft(collectDraft()); }
        });
        formEl.addEventListener('change', function () {
            formEl.dataset.dirty = '1';
            writeDraft(collectDraft());
        });

        formEl.addEventListener('submit', function (event) {
            event.preventDefault();
            formEl.dataset.dirty = '1';
            setFormAlert('');
            setSuccess('');
            var errors = validate();
            var keys = Object.keys(errors);
            if (keys.length) {
                setFormAlert('Periksa kembali kolom yang ditandai.');
                var firstWrap = fieldWrap(keys[0]);
                var firstInput = firstWrap ? firstWrap.querySelector('input, select, textarea') : null;
                if (firstInput) { firstInput.focus(); }
                return;
            }

            var payload = new FormData(formEl);
            payload.set('website', '');
            if (submit) {
                submit.disabled = true;
                submit.classList.add('is-busy');
                if (submitLabelEl) { submitLabelEl.textContent = 'Menyimpan jadwal...'; }
            }
            formEl.setAttribute('aria-busy', 'true');

            var endpoint = formEl.getAttribute('action') || window.location.pathname + window.location.search;
            fetch(endpoint, {
                method: 'POST',
                body: payload,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            }).then(function (result) {
                var tokenField = $('[data-csrf]');
                if (result.csrf && tokenField) { tokenField.value = result.csrf; }

                if (result.ok && result.needs_auth) {
                    setSuccess(result.message || 'Jadwal disimpan. Masuk untuk mengonfirmasi.');
                    var draft = result.draft || collectDraft();
                    writeDraft(draft);
                    openAuth('form', draft);
                } else if (result.errors) {
                    Object.keys(result.errors).forEach(function (name) {
                        if (name === 'form') {
                            setFormAlert(result.errors[name]);
                        } else {
                            setError(name, result.errors[name]);
                        }
                    });
                    if (result.message && !result.errors.form) { setFormAlert(result.message); }
                } else {
                    setFormAlert(result.message || 'Jadwal belum bisa disimpan. Coba lagi.');
                }
            }).catch(function () {
                setFormAlert('Koneksi gagal. Periksa jaringan lalu kirim ulang, data Anda masih tersimpan di formulir.');
            }).then(function () {
                if (submit) {
                    submit.disabled = false;
                    submit.classList.remove('is-busy');
                    if (submitLabelEl) { submitLabelEl.textContent = submitLabel; }
                }
                formEl.removeAttribute('aria-busy');
            });
        });

        var saved = readDraft();
        if (saved) {
            Object.keys(saved).forEach(function (name) {
                var field = formEl.elements.namedItem(name);
                if (!field) { return; }
                if (field.value === '' && name !== 'website') {
                    field.value = saved[name];
                }
            });
        }
    }

    /* -------------------------------------------------------- micro interactions */
    function initSegThumb() {
        var group = $('.segmented');
        var thumb = group ? $('.seg__thumb', group) : null;
        if (!group || !thumb) { return; }
        var resizeTimer = null;

        function place(smooth) {
            var checked = $('input:checked', group);
            if (!checked) { return; }
            var label = checked.parentNode;
            var groupRect = group.getBoundingClientRect();
            var labelRect = label.getBoundingClientRect();
            if (labelRect.width < 1) { return; }
            if (!smooth) { thumb.style.transition = 'none'; }
            thumb.style.width = labelRect.width + 'px';
            thumb.style.transform = 'translateX(' + (labelRect.left - groupRect.left) + 'px)';
            if (!smooth) {
                void thumb.offsetWidth;
                thumb.style.transition = '';
            }
        }

        place(false);
        $$('input', group).forEach(function (radio) {
            radio.addEventListener('change', function () { place(true); });
        });
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () { place(false); }, 120);
        });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { place(false); });
        }
    }

    function initRangeGrip() {
        var range = $('#calc-range');
        if (!range) { return; }
        function release() { range.classList.remove('is-dragging'); }
        range.addEventListener('pointerdown', function () { range.classList.add('is-dragging'); });
        range.addEventListener('keydown', function () {
            range.classList.add('is-dragging');
            window.setTimeout(release, 320);
        });
        window.addEventListener('pointerup', release);
        window.addEventListener('pointercancel', release);
        range.addEventListener('blur', release);
    }

    function initMicro() {
        initSegThumb();
        initRangeGrip();

        var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (reduced || !finePointer) { return; }

        $$('.btn-3d').forEach(function (btn) {
            var frame = null;
            var tiltX = 0;
            var tiltY = 0;

            btn.addEventListener('pointermove', function (event) {
                if (event.pointerType !== 'mouse') { return; }
                var rect = btn.getBoundingClientRect();
                if (!rect.width || !rect.height) { return; }
                var px = (event.clientX - rect.left) / rect.width;
                var py = (event.clientY - rect.top) / rect.height;
                tiltX = (0.5 - py) * 4.5;
                tiltY = (px - 0.5) * 5.5;
                btn.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
                btn.style.setProperty('--my', (py * 100).toFixed(1) + '%');
                if (frame === null) {
                    frame = window.requestAnimationFrame(function () {
                        frame = null;
                        btn.style.setProperty('--tilt-x', tiltX.toFixed(2) + 'deg');
                        btn.style.setProperty('--tilt-y', tiltY.toFixed(2) + 'deg');
                    });
                }
            });

            btn.addEventListener('pointerleave', function () {
                if (frame !== null) { window.cancelAnimationFrame(frame); frame = null; }
                btn.style.setProperty('--tilt-x', '0deg');
                btn.style.setProperty('--tilt-y', '0deg');
                btn.style.setProperty('--mx', '50%');
                btn.style.setProperty('--my', '50%');
            });
        });
    }

    /* ------------------------------------------------------------ bootstrap */
    function safeInit(name, fn) {
        try {
            fn();
        } catch (error) {
            doc.classList.remove('js');
            if (window.console && window.console.warn) {
                window.console.warn('[GO Laundry] ' + name + ' gagal dijalankan:', error);
            }
        }
    }

    safeInit('sequence', initSequence);
    safeInit('motion', initMotion);
    safeInit('reviews', initReviews);
    safeInit('calculator', initCalculator);
    safeInit('tracking', initTracking);
    safeInit('form', initForm);
    safeInit('micro interactions', initMicro);

    window.addEventListener('pagehide', function (event) {
        if (event.persisted) { return; }
        document.removeEventListener('keydown', onModalKeydown);
        if (lenis) { lenis.destroy(); }
        if (tickerFn && window.gsap) { window.gsap.ticker.remove(tickerFn); }
        if (hasGsap) { window.ScrollTrigger.getAll().forEach(function (trigger) { trigger.kill(); }); }
    });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
