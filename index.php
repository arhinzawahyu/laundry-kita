<?php
declare(strict_types=1);

/* ============================================================================
   [SECTION A: SESSION BOOTSTRAP & FILE INCLUDES]
   Config in config/site.php, helpers in includes/, view data below.
   ========================================================================= */

date_default_timezone_set('Asia/Jakarta');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

/* ----------------------------------------------------------------------------
   Bootstrap in four files: config, helpers, runtime (auth + sequence), handler.
   ------------------------------------------------------------------------- */
$siteConfig = require __DIR__ . '/config/site.php';
require_once __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/pickup.php';

$csrf = csrf_token();

/* ----------------------------------------------------------------------------
   6. Values consumed by the markup below.
   ------------------------------------------------------------------------- */
$biz        = $siteConfig['business'];
$services   = $siteConfig['services'];
$navItems   = $siteConfig['navigation'];
$todayValue = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$maxValue   = (new DateTimeImmutable('+30 days'))->format('Y-m-d');
$waNumber   = preg_replace('/[^0-9]/', '', $biz['whatsapp']) ?? '';
$waReady    = !is_placeholder($biz['whatsapp']) && strlen($waNumber) >= 9;
$schemaType = 'LocalBusiness';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($siteConfig['meta']['title']) ?></title>
<meta name="description" content="<?= e($siteConfig['meta']['description']) ?>">
<meta name="theme-color" content="#53629E">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($siteConfig['meta']['title']) ?>">
<meta property="og:description" content="<?= e($siteConfig['meta']['description']) ?>">
<meta property="og:locale" content="id_ID">
<link rel="icon" type="image/png" href="go-laundry.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<?php if ($firstFrame !== null): ?>
<link rel="preload" as="image" href="<?= e($firstFrame) ?>">
<?php endif; ?>
<script>document.documentElement.classList.add('js');</script>
<link rel="stylesheet" href="assets/css/main.css?v=<?= @filemtime(__DIR__ . '/assets/css/main.css') ?: 1 ?>">
</head>
<body>
<a class="visually-hidden-focusable" href="#main" style="position:absolute;left:16px;top:8px;z-index:200;background:#fff;color:#241B38;padding:10px 14px;border-radius:8px;">Lewati ke konten utama</a>

<?php
/* ============================================================================
   [SECTION B: HTML5 SEMANTIC STRUCTURE & 10 SECTIONS]
   ========================================================================= */
$areaCount = count($siteConfig['areas']);
$slotCount = count($siteConfig['pickup_slots']);
$waLink    = $waReady ? 'https://wa.me/' . $waNumber : null;
$waText    = 'Halo ' . $biz['name'] . ', saya ingin bertanya soal layanan laundry.';
$waHref    = $waLink !== null ? $waLink . '?text=' . rawurlencode($waText) : null;
$customerSession = $authReady ? customer_current() : null;
?>

<!-- --------------------------------------------------------------- 0. HEADER -->
<header class="site-header on-dark" id="site-header">
    <div class="site-header__bar">
        <div class="wrap site-header__inner">
            <a class="brand" href="#hero" aria-label="<?= e($biz['name']) ?>, kembali ke atas">
                <span class="brand__plate">
                    <img class="brand__logo" src="go-laundry.png" alt="<?= e($biz['name']) ?>" width="1024" height="1024">
                </span>
            </a>

            <nav class="nav" aria-label="Navigasi utama">
                <?php foreach ($navItems as $item): ?>
                <a class="nav__link" href="#<?= e($item['id']) ?>" data-nav-link="<?= e($item['id']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="header__actions">
                <?php if ($authReady && $customerSession !== null): ?>
                <a class="header__login" href="akun.php">Akun saya</a>
                <a class="btn-3d btn-3d--sm header__cta" href="index.php#jadwal">Pesan jemput</a>
                <?php elseif ($authReady): ?>
                <a class="header__login" href="<?= e($siteConfig['auth']['login_url']) ?>">Masuk</a>
                <a class="btn-3d btn-3d--sm header__cta" href="<?= e($siteConfig['auth']['register_url']) ?>">Buat akun</a>
                <?php else: ?>
                <button class="header__login" type="button" data-auth="header-login">Masuk</button>
                <button class="btn-3d btn-3d--sm header__cta" type="button" data-auth="header-register">Buat akun</button>
                <?php endif; ?>
                <button class="menu-toggle" type="button" id="menu-toggle" aria-expanded="false" aria-controls="mobile-drawer" aria-label="Buka menu navigasi">
                    <span class="menu-toggle__box" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
            </div>
        </div>
    </div>

    <div class="drawer" id="mobile-drawer">
        <nav aria-label="Navigasi seluler">
            <ul class="drawer__list">
                <?php foreach ($navItems as $index => $item): ?>
                <li>
                    <a class="drawer__link" href="#<?= e($item['id']) ?>" data-drawer-link>
                        <span aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <?= e($item['label']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="drawer__actions">
            <?php if ($authReady && $customerSession !== null): ?>
            <a class="btn-3d" href="akun.php">Akun saya</a>
            <a class="btn-line" href="index.php#jadwal">Atur jadwal jemput</a>
            <?php elseif ($authReady): ?>
            <a class="btn-3d" href="<?= e($siteConfig['auth']['login_url']) ?>">Masuk ke akun</a>
            <a class="btn-line" href="<?= e($siteConfig['auth']['register_url']) ?>">Buat akun baru</a>
            <?php else: ?>
            <button class="btn-3d" type="button" data-auth="drawer-login">Masuk ke akun</button>
            <button class="btn-line" type="button" data-auth="drawer-register">Buat akun baru</button>
            <?php endif; ?>
            <p class="drawer__meta">Jemput dan antar: <?= e($siteConfig['areas'][0]) ?> dan <?= $areaCount - 1 ?> desa sekitar.</p>
        </div>
    </div>
</header>

<main id="main">

<!-- ---------------------------------------------------------------- 1. HERO -->
<section class="hero on-dark" id="hero" aria-labelledby="hero-title">
    <div class="hero__track" data-hero-track>
        <div class="hero__stage" id="hero-stage" data-hero-stage>
            <canvas class="hero__canvas" id="hero-canvas" aria-hidden="true"></canvas>
            <div class="hero__scrim" data-hero-scrim aria-hidden="true"></div>

            <div class="hero__inner wrap">
                <div class="hero__copy" data-hero-copy>
                    <p class="hero__kicker">Jemput antar di Delanggu dan Klaten</p>
                    <h1 class="display hero__title" id="hero-title">Cucian bersih, <em>dijemput</em> dari rumah.</h1>
                    <p class="hero__lede">Kami menjemput cucian Anda di Kecamatan Delanggu, Klaten, dan desa sekitar. Hitung estimasi harga lebih dulu, lalu pilih jam jemput yang pas.</p>
                    <div class="hero__actions">
                        <a class="btn-3d" href="#pesan" data-hero-cta>Pesan jemput sekarang</a>
                        <a class="btn-line" href="#kalkulator">Cek harga cucian</a>
                    </div>
                </div>

                <dl class="hero__meta" data-hero-meta>
                    <div class="hero__fact">
                        <dt>Area jemput</dt>
                        <dd><?= e($siteConfig['areas'][0]) ?> dan <?= $areaCount - 1 ?> desa</dd>
                    </div>
                    <div class="hero__fact">
                        <dt>Layanan</dt>
                        <dd>Kiloan, satuan, ekspres</dd>
                    </div>
                    <div class="hero__fact">
                        <dt>Slot jemput</dt>
                        <dd><?= $slotCount ?> pilihan jam per hari</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------- 1B. TAGLINE BAND -->
<section class="manifesto on-deep" id="manifesto" aria-label="Prinsip layanan">
    <div class="wrap">
        <p class="manifesto__text" data-manifesto>Bersih itu soal detail. Kami menimbang, mencuci, dan melipat dengan hitungan yang bisa Anda cek.</p>
        <p class="manifesto__sign">Ditimbang di depan Anda, dicatat di tiket, diantar sesuai slot.</p>
    </div>
</section>

<!-- --------------------------------------------------------- 2. CALCULATOR -->
<section class="section section--paper calc" id="kalkulator" aria-labelledby="kalkulator-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Kalkulator harga</p>
                <h2 class="h2" id="kalkulator-title" style="margin-top:14px;">Hitung dulu, baru kirim cucian.</h2>
            </div>
            <p class="lede">Geser berat atau jumlah potong, pilih layanan, dan lihat estimasi yang harus dibayar. Tarif final dikonfirmasi kurir saat cucian ditimbang.</p>
        </div>

        <div class="calc__grid">
            <div class="calc__sticky">
                <div class="calc__panel" aria-label="Ringkasan estimasi">
                    <p class="calc__panel-label">Estimasi biaya</p>
                    <p class="calc__amount" id="calc-amount" data-calc-amount>Rp 28.000</p>
                    <p class="calc__duration">Selesai dalam <strong id="calc-duration" data-calc-duration>2 hari</strong></p>
                    <div class="calc__rows">
                        <div class="calc__row"><span>Layanan</span><b id="calc-service-name">Kiloan</b></div>
                        <div class="calc__row"><span>Jumlah</span><b id="calc-qty-label">4 kg</b></div>
                        <div class="calc__row"><span>Tarif per <?= e($services['kiloan']['unit']) ?></span><b id="calc-rate-label">Rp 7.000</b></div>
                        <div class="calc__row"><span>Minimal order</span><b id="calc-min-label">3 kg</b></div>
                    </div>
                    <p class="calc__note" id="calc-note">Estimasi contoh, belum termasuk item tambahan yang dicatat kurir.</p>
                    <p class="visually-hidden" aria-live="polite" id="calc-live">Estimasi Rp 28.000 untuk 4 kg Kiloan.</p>
                </div>
            </div>

            <div class="calc__controls">
                <div class="field-group">
                    <span class="field-group__label" id="calc-service-label">Jenis layanan</span>
                    <div class="segmented" role="radiogroup" aria-labelledby="calc-service-label">
                        <span class="seg__thumb" aria-hidden="true"></span>
                        <?php foreach ($services as $key => $service): ?>
                        <label class="seg">
                            <input type="radio" name="calc-service" value="<?= e($key) ?>" data-calc-service <?= $key === 'kiloan' ? 'checked' : '' ?>>
                            <?= e($service['label']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-group__label" for="calc-number" id="calc-qty-label-control">Berat cucian (kg)</label>
                    <div class="qty">
                        <input class="range" type="range" id="calc-range" min="0.5" max="30" step="0.5" value="4" aria-describedby="calc-hint">
                        <div class="qty__input">
                            <input type="number" id="calc-number" min="0.5" max="30" step="0.5" value="4" inputmode="decimal" aria-label="Berat atau jumlah cucian">
                            <span class="qty__unit" id="calc-unit-label">kg</span>
                        </div>
                    </div>
                </div>

                <p class="calc__hint" id="calc-hint">Minimal 3 kg untuk layanan kiloan.</p>

                <div class="calc__actions">
                    <button class="btn-3d" type="button" id="calc-lock">Kunci harga dan pesan</button>
                    <a class="btn-line" href="#layanan">Baca rincian tarif</a>
                </div>
                <p class="small">Harga dikunci setelah Anda masuk ke akun, lalu jadwal jemput dikonfirmasi kurir.</p>
            </div>
        </div>
    </div>
</section>

<!-- ---------------------------------------------------------- 3. PROMO -->
<section class="section section--tint" id="promo" aria-labelledby="promo-title">
    <div class="wrap">
        <div class="promo__grid">
            <div>
                <p class="eyebrow">Pengguna baru</p>
                <h2 class="h2" id="promo-title" style="margin-top:14px;">Tiga hal untuk pesanan pertama Anda.</h2>
                <p class="lede" style="margin-top:18px;">Voucher terbuka setelah akun aktif. Sebelum itu, kartu promo hanya menampilkan isi penawaran.</p>
                <p class="small" style="margin-top:16px;max-width:40ch;"><?= e($siteConfig['promo']['note']) ?></p>
                <div class="stitch" style="margin-top:24px;max-width:280px;"></div>
                <p class="small" style="margin-top:16px;">Gratis ongkir jemput dan antar untuk area <?= e($siteConfig['promo']['free_pickup_area']) ?>.</p>
            </div>

            <div class="promo__list">
                <article class="ticket">
                    <div class="ticket__body">
                        <p class="ticket__label">Order pertama</p>
                        <p class="ticket__value">Diskon <?= (int) $siteConfig['promo']['first_order_percent'] ?>%</p>
                        <p class="ticket__desc">Potongan langsung pada transaksi pertama setelah akun terverifikasi.</p>
                    </div>
                    <div class="ticket__stub">
                        <span class="ticket__lock" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <button class="ticket__claim" type="button" data-auth="promo">Klaim setelah masuk</button>
                    </div>
                </article>

                <article class="ticket">
                    <div class="ticket__body">
                        <p class="ticket__label">Ongkir jemput antar</p>
                        <p class="ticket__value">Gratis</p>
                        <p class="ticket__desc">Jemput dan antar tanpa biaya untuk area <?= e($siteConfig['promo']['free_pickup_area']) ?> pada pesanan pertama.</p>
                    </div>
                    <div class="ticket__stub">
                        <span class="ticket__lock" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <button class="ticket__claim" type="button" data-auth="promo">Klaim setelah masuk</button>
                    </div>
                </article>

                <article class="ticket">
                    <div class="ticket__body">
                        <p class="ticket__label">Member baru</p>
                        <p class="ticket__value">Voucher</p>
                        <p class="ticket__desc">Voucher pembuka dengan nilai <?= e($siteConfig['promo']['member_voucher']) ?>, berlaku untuk satu transaksi.</p>
                    </div>
                    <div class="ticket__stub">
                        <span class="ticket__lock" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <button class="ticket__claim" type="button" data-auth="promo">Klaim setelah masuk</button>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
<!-- -------------------------------------------------------- 4. LAYANAN -->
<section class="section section--paper section--edge" id="layanan" aria-labelledby="layanan-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Layanan dan tarif</p>
                <h2 class="h2" id="layanan-title" style="margin-top:14px;">Harga yang bisa dibaca sebelum kirim.</h2>
            </div>
            <p class="lede">Tiga pilihan layanan dengan hitungan berbeda. Semua pesanan ditimbang di depan Anda, dan selisih berat dicatat di tiket.</p>
        </div>

        <div class="price-wrap">
            <table class="price-table">
                <caption>Daftar harga per layanan</caption>
                <thead>
                    <tr>
                        <th scope="col">Layanan</th>
                        <th scope="col">Tarif</th>
                        <th scope="col">Minimal</th>
                        <th scope="col">Estimasi selesai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $key => $service): ?>
                    <tr>
                        <td data-label="Layanan">
                            <span class="service-name"><?= e($service['label']) ?></span>
                            <span class="service-desc" style="display:block;"><?= e($service['blurb']) ?></span>
                        </td>
                        <td data-label="Tarif">
                            <span class="price-cell">
                                <span class="price-main tabnums"><?= e(rupiah((float) $service['price'])) ?></span>
                                <span class="price-unit"> / <?= e($service['unit']) ?></span>
                            </span>
                        </td>
                        <td data-label="Minimal">
                            <span class="price-cell-block">
                                <span class="tabnums"><?= e(rtrim(rtrim(number_format((float) $service['min_qty'], 1, ',', '.'), '0'), ',')) ?> <?= e($service['unit']) ?></span>
                                <span class="service-desc" style="display:block;"><?= e($service['detail']) ?></span>
                            </span>
                        </td>
                        <td data-label="Estimasi selesai">
                            <span class="tabnums"><?= e($service['duration_label']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="price-note">
                <span>Tarif pada tabel ini masih contoh dan menunggu data resmi outlet. Minimal order dapat berubah.</span>
                <a href="#kalkulator">Hitung estimasi Anda</a>
            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------ 5. CARA KERJA -->
<section class="section section--deep on-deep" id="cara-kerja" aria-labelledby="cara-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Cara kerja</p>
                <h2 class="h2" id="cara-title" style="margin-top:14px;">Empat langkah, tanpa antar sendiri.</h2>
            </div>
            <p class="lede">Alurnya sama untuk semua layanan. Yang berbeda hanya waktu pengerjaan dan hitungan tarifnya.</p>
        </div>

        <ol class="steps" data-reveal-group>
            <li class="step" data-reveal>
                <span class="step__num" aria-hidden="true">1</span>
                <h3 class="step__title">Pesan online</h3>
                <p class="step__text">Isi alamat, tanggal, dan slot jemput. Estimasi harga muncul saat itu juga.</p>
            </li>
            <li class="step" data-reveal>
                <span class="step__num" aria-hidden="true">2</span>
                <h3 class="step__title">Kurir jemput</h3>
                <p class="step__text">Cucian ditimbang di depan Anda. Jumlah dan jenisnya dicatat pada tiket pesanan.</p>
            </li>
            <li class="step" data-reveal>
                <span class="step__num" aria-hidden="true">3</span>
                <h3 class="step__title">Pantau status</h3>
                <p class="step__text">Lima tahap dari antrean sampai siap antar terlihat di halaman pelacakan akun.</p>
            </li>
            <li class="step" data-reveal>
                <span class="step__num" aria-hidden="true">4</span>
                <h3 class="step__title">Antar bersih</h3>
                <p class="step__text">Cucian dilipat dan diantar kembali sesuai slot yang Anda pilih saat memesan.</p>
            </li>
        </ol>
    </div>
</section>

<!-- ------------------------------------------------ 6. TRACKING PREVIEW -->
<section class="section section--tint" id="tracking-preview" aria-labelledby="tracking-title">
    <div class="wrap">
        <div class="track__grid">
            <div>
                <p class="eyebrow">Pelacakan status</p>
                <h2 class="h2" id="tracking-title" style="margin-top:14px;">Lihat di mana cucian Anda.</h2>
                <p class="lede" style="margin-top:18px;">Setiap pesanan punya lima tahap yang bisa dicek kapan saja setelah masuk ke akun.</p>

                <div class="track__details" style="border-top:0;padding-top:0;margin-top:24px;">
                    <div class="track__detail">
                        <span>Tahap</span>
                        <b>5 langkah</b>
                    </div>
                    <div class="track__detail">
                        <span>Pembaruan</span>
                        <b>Setiap perpindahan tahap</b>
                    </div>
                    <div class="track__detail">
                        <span>Akses</span>
                        <b>Perlu akun</b>
                    </div>
                </div>

                <div class="track__actions">
                    <?php if ($customerSession !== null): ?>
                    <a class="btn-3d" href="akun.php">Buka status pesanan saya</a>
                    <?php else: ?>
                    <button class="btn-3d" type="button" data-auth="tracking">Akses pelacakan akun saya</button>
                    <?php endif; ?>
                    <?php if ($waHref !== null): ?>
                    <a class="btn-line" href="<?= e($waHref) ?>" target="_blank" rel="noopener">Tanya status lewat WhatsApp</a>
                    <?php endif; ?>
                </div>
                <p class="small" style="margin-top:16px;">Contoh di samping adalah simulasi tampilan, bukan data pesanan nyata.</p>
            </div>

            <div>
                <div class="ticket-panel" aria-labelledby="track-panel-title">
                    <div class="ticket-panel__head">
                        <p class="ticket-panel__code" id="track-panel-title">Contoh pesanan PRV-0001</p>
                        <span class="sim-chip">Simulasi preview</span>
                    </div>
                    <div class="ticket-panel__body">
                        <div class="rail" role="group" aria-label="Tahap contoh pesanan">
                            <span class="rail__bar" aria-hidden="true"><span class="rail__fill" id="track-fill" style="width:25%"></span></span>
                            <button class="rail__item is-done" type="button" data-track-step="0" aria-label="Tahap 1, antrean">
                                <span class="rail__dot" aria-hidden="true">1</span>
                                <span class="rail__label">Antrean</span>
                            </button>
                            <button class="rail__item" type="button" data-track-step="1" aria-current="step" aria-label="Tahap 2, dicuci">
                                <span class="rail__dot" aria-hidden="true">2</span>
                                <span class="rail__label">Dicuci</span>
                            </button>
                            <button class="rail__item" type="button" data-track-step="2" aria-label="Tahap 3, dikeringkan">
                                <span class="rail__dot" aria-hidden="true">3</span>
                                <span class="rail__label">Dikeringkan</span>
                            </button>
                            <button class="rail__item" type="button" data-track-step="3" aria-label="Tahap 4, disetrika">
                                <span class="rail__dot" aria-hidden="true">4</span>
                                <span class="rail__label">Setrika</span>
                            </button>
                            <button class="rail__item" type="button" data-track-step="4" aria-label="Tahap 5, siap antar">
                                <span class="rail__dot" aria-hidden="true">5</span>
                                <span class="rail__label">Siap antar</span>
                            </button>
                        </div>

                        <div class="track__status">
                            <h3 class="track__status-title" id="track-status-title">Sedang dicuci</h3>
                            <p class="track__status-text" id="track-status-text">Cucian sedang diproses di mesin. Tahap berikutnya adalah pengeringan.</p>
                            <p class="visually-hidden" aria-live="polite" id="track-live">Tahap 2 dari 5, sedang dicuci.</p>
                        </div>

                        <div class="track__details">
                            <div class="track__detail"><span>Layanan contoh</span><b>Kiloan</b></div>
                            <div class="track__detail"><span>Berat contoh</span><b>4 kg</b></div>
                            <div class="track__detail"><span>Estimasi selesai</span><b>2 hari</b></div>
                            <div class="track__detail"><span>Data</span><b>Fiktif untuk preview</b></div>
                        </div>

                        <div class="track__actions">
                            <button class="btn-3d btn-3d--sm" type="button" id="track-next">Tahap berikutnya</button>
                            <button class="btn-line" type="button" id="track-reset">Ulangi dari antrean</button>
                        </div>
                    </div>
                    <div class="track__foot">
                        <p class="tiny">Simulasi ini hanya memperlihatkan bentuk antarmuka. Status asli hanya muncul di akun setelah pesanan dibuat.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------ 7. MEMBERSHIP -->
<section class="section section--paper section--edge" id="membership" aria-labelledby="membership-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Poin dan keanggotaan</p>
                <h2 class="h2" id="membership-title" style="margin-top:14px;">Poin menumpuk dari kebiasaan kecil.</h2>
            </div>
            <p class="lede">Setiap transaksi menambah poin. Poin bisa ditukar menjadi potongan harga atau layanan tambahan.</p>
        </div>

        <div class="member__grid">
            <div class="earn-box">
                <p class="earn-box__formula">
                    1 poin <span style="font-size:0.5em;vertical-align:middle;">setiap</span> Rp1.000
                    <small>Dihitung dari nilai transaksi yang sudah dibayar, dibulatkan ke bawah.</small>
                </p>
                <div class="stitch" style="margin:20px 0;"></div>
                <p class="field-group__label">Penukaran poin</p>
                <ul class="redeem">
                    <?php foreach ($siteConfig['membership']['redeem'] as $redeem): ?>
                    <li class="redeem__item">
                        <span><?= e($redeem['value']) ?></span>
                        <span class="redeem__cost"><?= (int) $redeem['cost'] ?> poin</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <p class="tiny" style="margin-top:16px;"><?= e($siteConfig['membership']['note']) ?></p>
                <div style="margin-top:20px;">
                    <button class="btn-3d btn-3d--sm" type="button" data-auth="membership">Aktifkan poin saya</button>
                </div>
            </div>

            <div>
                <div class="tiers">
                    <?php foreach ($siteConfig['membership']['tiers'] as $index => $tier): ?>
                    <div class="tier<?= $index === 1 ? ' tier--mid' : ($index === 2 ? ' tier--top' : '') ?>">
                        <div class="tier__head">
                            <span class="tier__name"><?= e($tier['name']) ?></span>
                            <span class="tier__min">
                                <?php if ($tier['min_points'] > 0): ?>
                                Mulai <?= (int) $tier['min_points'] ?> poin
                                <?php else: ?>
                                Tanpa syarat poin
                                <?php endif; ?>
                            </span>
                        </div>
                        <ul class="tier__benefits">
                            <?php foreach ($tier['benefits'] as $benefit): ?>
                            <li><?= e($benefit) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="small" style="margin-top:16px;max-width:56ch;">Poin tidak hangus selama akun aktif. Syarat penukaran mengikuti ketentuan yang berlaku di outlet.</p>
            </div>
        </div>
    </div>
</section>
<!-- ------------------------------------------------------ 8. TESTIMONIAL -->
<section class="section section--base on-base" id="testimoni" aria-labelledby="testimoni-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Ulasan dan jaminan</p>
                <h2 class="h2" id="testimoni-title" style="margin-top:14px;">Kepercayaan tidak dibuat sendiri.</h2>
            </div>
            <p class="lede">Wajah dan nama disamarkan. Ulasan contoh ditandai sampai pemilik memverifikasinya.</p>
        </div>

        <div class="stats" data-reveal-group>
            <?php foreach ($siteConfig['stats'] as $stat): ?>
            <div class="stat" data-reveal>
                <p class="stat__label"><?= e($stat['label']) ?></p>
                <p class="stat__value"><?= e($stat['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="reviews" data-reviews aria-roledescription="carousel" aria-label="Ulasan pelanggan anonim">
            <div class="reviews__viewport" data-review-viewport tabindex="0" role="group" aria-roledescription="slide container">
                <div class="reviews__track" data-review-track>
                    <?php
                    $reviewPhotos = [
                        'assets/img/reviews/anon-1.jpg',
                        'assets/img/reviews/anon-2.jpg',
                        'assets/img/reviews/anon-3.jpg',
                    ];
                    $reviewTotal = count($siteConfig['testimonials']);
                    foreach ($siteConfig['testimonials'] as $reviewIndex => $review):
                        $photo = $reviewPhotos[$reviewIndex % count($reviewPhotos)];
                        $isSample = is_placeholder((string) $review['quote']);
                    ?>
                    <article class="review-slide" data-review-slide role="group" aria-roledescription="slide" aria-label="Ulasan <?= (int) ($reviewIndex + 1) ?> dari <?= (int) $reviewTotal ?>"<?= $reviewIndex === 0 ? '' : ' aria-hidden="true"' ?>>
                        <div class="review-slide__inner">
                            <div class="review-slide__photo">
                                <img src="<?= e($photo) ?>" alt="Potret pelanggan dengan wajah disamarkan" width="720" height="720" loading="lazy" decoding="async">
                                <span class="review-slide__anon">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                    Anonim
                                </span>
                            </div>
                            <div class="review-slide__body">
                                <?php if ($isSample): ?>
                                    <p class="review-slide__quote review-slide__quote--muted">Ulasan layanan <?= e(strtolower((string) $review['service'])) ?> segera hadir.</p>
                                <?php else: ?>
                                    <blockquote class="review-slide__quote">&ldquo;<?= e($review['quote']) ?>&rdquo;</blockquote>
                                <?php endif; ?>
                                <p class="review-slide__by"><span class="review-slide__pip" aria-hidden="true"></span>Anonim &middot; <?= e($review['service']) ?><?php if ($isSample): ?><span class="review-slide__flag">&nbsp;Contoh</span><?php endif; ?></p>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reviews__controls">
                <button class="review-nav" type="button" data-review-prev aria-label="Ulasan sebelumnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                </button>
                <div class="reviews__dots" aria-label="Pilih ulasan">
                    <?php for ($dot = 0; $dot < $reviewTotal; $dot++): ?>
                    <button class="reviews__dot<?= $dot === 0 ? ' is-active' : '' ?>" type="button" data-review-dot aria-label="Ulasan <?= (int) ($dot + 1) ?>"<?= $dot === 0 ? ' aria-current="true"' : '' ?>></button>
                    <?php endfor; ?>
                </div>
                <button class="review-nav" type="button" data-review-next aria-label="Ulasan berikutnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </button>
            </div>
        </div>

        <div class="guarantees" data-reveal-group>
            <?php foreach ($siteConfig['guarantees'] as $guarantee): ?>
            <div class="guarantee" data-reveal>
                <h4><?= e($guarantee['title']) ?></h4>
                <p><?= e($guarantee['text']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="small" style="margin-top:22px;color:#E9EEF8;max-width:70ch;">Teks jaminan di atas adalah rancangan kebijakan, bukan hasil audit. Placeholder diganti setelah pemilik menetapkan syarat resmi.</p>
    </div>
</section>

<!-- ------------------------------------------------------ 9. JANGKAUAN -->
<section class="section section--tint" id="jangkauan" aria-labelledby="jangkauan-title">
    <div class="wrap">
        <div class="section__top section__top--split">
            <div>
                <p class="eyebrow">Jangkauan dan jam</p>
                <h2 class="h2" id="jangkauan-title" style="margin-top:14px;">Sembilan desa, satu jalur jemput.</h2>
            </div>
            <p class="lede">Kurir menjemput sesuai slot yang Anda pilih. Untuk lokasi di luar daftar, konfirmasi dulu lewat WhatsApp sebelum memesan.</p>
        </div>

        <div class="area__grid">
            <div class="map">
                <svg viewBox="0 0 520 380" role="img" aria-labelledby="map-title map-desc" focusable="false">
                    <title id="map-title">Skema wilayah jemput GO Laundry</title>
                    <desc id="map-desc">Sembilan desa tersebar mengelilingi Delanggu sebagai titik pusat.</desc>
                    <rect x="0" y="0" width="520" height="380" rx="14" fill="#F7FAF8"/>
                    <path d="M40 300 C 120 260, 150 200, 250 180 S 400 120, 480 90" fill="none" stroke="rgba(135,186,195,0.55)" stroke-width="10" stroke-linecap="round"/>
                    <path d="M70 90 C 150 120, 190 150, 250 180 S 380 250, 460 320" fill="none" stroke="rgba(71,52,114,0.12)" stroke-width="10" stroke-linecap="round"/>
                    <g stroke="rgba(71,52,114,0.35)" stroke-width="1.5" stroke-dasharray="5 6">
                        <line x1="250" y1="180" x2="150" y2="120"/>
                        <line x1="250" y1="180" x2="330" y2="110"/>
                        <line x1="250" y1="180" x2="350" y2="220"/>
                        <line x1="250" y1="180" x2="240" y2="285"/>
                        <line x1="250" y1="180" x2="140" y2="260"/>
                        <line x1="250" y1="180" x2="90" y2="190"/>
                        <line x1="250" y1="180" x2="430" y2="150"/>
                        <line x1="250" y1="180" x2="420" y2="300"/>
                    </g>
                    <g fill="#473472">
                        <circle cx="150" cy="120" r="6"/>
                        <circle cx="330" cy="110" r="6"/>
                        <circle cx="350" cy="220" r="6"/>
                        <circle cx="240" cy="285" r="6"/>
                        <circle cx="140" cy="260" r="6"/>
                        <circle cx="90" cy="190" r="6"/>
                        <circle cx="430" cy="150" r="6"/>
                        <circle cx="420" cy="300" r="6"/>
                    </g>
                    <circle cx="250" cy="180" r="16" fill="rgba(135,186,195,0.4)"/>
                    <circle cx="250" cy="180" r="9" fill="#473472" stroke="#D6F4ED" stroke-width="3"/>
                    <g font-family="Poppins, Segoe UI, sans-serif" font-size="12" font-weight="500" fill="#473472" text-anchor="middle">
                        <text x="150" y="104">Sabrang</text>
                        <text x="330" y="94">Gatak</text>
                        <text x="350" y="244">Kepanjen</text>
                        <text x="240" y="309">Bowan</text>
                        <text x="140" y="284">Sribit</text>
                        <text x="90" y="174">Wonosari</text>
                        <text x="430" y="134">Ceper</text>
                        <text x="420" y="324">Juwiring</text>
                        <text x="250" y="212" font-weight="600" fill="#2B2043">Delanggu</text>
                    </g>
                </svg>
                <div class="map__legend">
                    <span><i class="hub" aria-hidden="true"></i>Titik pusat layanan</span>
                    <span><i aria-hidden="true"></i>Desa dalam jangkauan</span>
                    <span>Skema wilayah, bukan peta skala</span>
                </div>
            </div>

            <div>
                <ul class="area-list" aria-label="Daftar desa yang dilayani">
                    <?php foreach ($siteConfig['areas'] as $area): ?>
                    <li><?= e($area) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="hours">
                    <div class="hours__group">
                        <h4>Jam outlet</h4>
                        <?php foreach ($siteConfig['hours']['outlet'] as $row): ?>
                        <div class="hours__row">
                            <span><?= e($row['day']) ?></span>
                            <b><?= e($row['time']) ?></b>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="hours__group">
                        <h4>Jam kurir jemput</h4>
                        <?php foreach ($siteConfig['hours']['courier'] as $row): ?>
                        <div class="hours__row">
                            <span><?= e($row['day']) ?></span>
                            <b><?= e($row['time']) ?></b>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="price-note" style="margin-top:22px;border-radius:10px;border:1px dashed var(--line-strong);">
                    <span>Jam di atas masih placeholder dan dikonfirmasi ulang saat pemesanan.</span>
                    <a href="#pesan">Atur jadwal jemput</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------- 10. ORDER FORM + CTA -->
<section class="section section--deep on-deep" id="pesan" aria-labelledby="pesan-title">
    <div class="wrap">
        <div class="order__grid">
            <div class="order__side">
                <p class="eyebrow">Jadwal penjemputan</p>
                <h2 class="h2" id="pesan-title" style="margin-top:14px;color:#FFFFFF;">Atur jemput, kami catat di tiket.</h2>
                <p class="lede" style="margin-top:18px;">Isi data penjemputan. Setelah jadwal tersimpan, Anda masuk atau membuat akun untuk mengonfirmasi pesanan.</p>

                <ul class="order__steps">
                    <li><span aria-hidden="true">1</span><div><b>Isi jadwal.</b> Nama, nomor WhatsApp, alamat, tanggal, dan slot jemput.</div></li>
                    <li><span aria-hidden="true">2</span><div><b>Masuk atau daftar.</b> Data jadwal tetap tersimpan selama proses ini.</div></li>
                    <li><span aria-hidden="true">3</span><div><b>Kurir mengonfirmasi.</b> Pesanan dan berat final dikunci saat penjemputan.</div></li>
                </ul>

                <?php if ($waHref !== null): ?>
                <div style="margin-top:26px;">
                    <a class="btn-line" href="<?= e($waHref) ?>" target="_blank" rel="noopener">Tanya dulu lewat WhatsApp</a>
                </div>
                <?php endif; ?>

                <p class="small" style="margin-top:24px;max-width:44ch;">Belum punya akun? Daftar tetap diperlukan untuk mengunci tarif, memantau status, dan mengumpulkan poin.</p>
            </div>

            <div class="form-card">
                <div class="form-card__head">
                    <h3 class="h3" id="form-title">Formulir jadwal jemput</h3>
                    <p class="small" style="margin-top:6px;">Kolom bertanda wajib harus diisi sebelum jadwal disimpan.</p>
                </div>

                <div class="form-alert<?= isset($formState['errors']['form']) ? ' is-visible' : '' ?>" id="form-alert" role="alert">
                    <?= e($formState['errors']['form'] ?? '') ?>
                </div>
                <div class="form-success<?= $formState['success'] ? ' is-visible' : '' ?>" id="form-success" role="status">
                    <?= e($formState['notice']) ?>
                </div>

                <div class="form-prefill" id="form-prefill">
                    <span id="form-prefill-text">Data dari kalkulator ditambahkan.</span>
                    <button type="button" id="form-prefill-clear">Hapus</button>
                </div>

                <form id="pickup-form" method="post" action="index.php#pesan" novalidate>
                    <input type="hidden" name="action" value="jadwal">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>" data-csrf>
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-grid">
                        <div class="field<?= isset($formState['errors']['nama']) ? ' has-error' : '' ?>" data-field="nama">
                            <label class="field__label" for="nama">Nama penerima</label>
                            <input type="text" id="nama" name="nama" value="<?= e($formState['values']['nama']) ?>" autocomplete="name" minlength="2" maxlength="60" required aria-describedby="err-nama">
                            <p class="field__err" id="err-nama"><?= e($formState['errors']['nama'] ?? 'Tulis nama penerima, 2 sampai 60 karakter.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['whatsapp']) ? ' has-error' : '' ?>" data-field="whatsapp">
                            <label class="field__label" for="whatsapp">Nomor WhatsApp</label>
                            <input type="tel" id="whatsapp" name="whatsapp" value="<?= e($formState['values']['whatsapp']) ?>" placeholder="0812 3456 7890" autocomplete="tel" inputmode="tel" maxlength="20" required aria-describedby="err-whatsapp">
                            <p class="field__err" id="err-whatsapp"><?= e($formState['errors']['whatsapp'] ?? 'Nomor WhatsApp belum valid. Contoh: 0812 3456 7890.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['desa']) ? ' has-error' : '' ?>" data-field="desa">
                            <label class="field__label" for="desa">Desa penjemputan</label>
                            <select id="desa" name="desa" required aria-describedby="err-desa">
                                <option value="">Pilih desa</option>
                                <?php foreach ($siteConfig['areas'] as $area): ?>
                                <option value="<?= e($area) ?>"<?= $formState['values']['desa'] === $area ? ' selected' : '' ?>><?= e($area) ?></option>
                                <?php endforeach; ?>
                                <option value="Luar area"<?= $formState['values']['desa'] === 'Luar area' ? ' selected' : '' ?>>Luar area (konfirmasi dulu)</option>
                            </select>
                            <p class="field__err" id="err-desa"><?= e($formState['errors']['desa'] ?? 'Pilih desa penjemputan dari daftar.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['tanggal']) ? ' has-error' : '' ?>" data-field="tanggal">
                            <label class="field__label" for="tanggal">Tanggal jemput</label>
                            <input type="date" id="tanggal" name="tanggal" value="<?= e($formState['values']['tanggal']) ?>" min="<?= e($todayValue) ?>" max="<?= e($maxValue) ?>" required aria-describedby="err-tanggal">
                            <p class="field__err" id="err-tanggal"><?= e($formState['errors']['tanggal'] ?? 'Pilih tanggal penjemputan, maksimal 30 hari ke depan.') ?></p>
                        </div>

                        <div class="field field--wide<?= isset($formState['errors']['alamat']) ? ' has-error' : '' ?>" data-field="alamat">
                            <label class="field__label" for="alamat">Alamat lengkap <span>(nama jalan, nomor rumah, patokan)</span></label>
                            <input type="text" id="alamat" name="alamat" value="<?= e($formState['values']['alamat']) ?>" minlength="5" maxlength="160" autocomplete="street-address" required aria-describedby="err-alamat">
                            <p class="field__err" id="err-alamat"><?= e($formState['errors']['alamat'] ?? 'Tulis alamat lengkap, 5 sampai 160 karakter.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['slot']) ? ' has-error' : '' ?>" data-field="slot">
                            <label class="field__label" for="slot">Slot jam jemput</label>
                            <select id="slot" name="slot" required aria-describedby="err-slot">
                                <option value="">Pilih slot</option>
                                <?php foreach ($siteConfig['pickup_slots'] as $slot): ?>
                                <option value="<?= e($slot) ?>"<?= $formState['values']['slot'] === $slot ? ' selected' : '' ?>><?= e($slot) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field__err" id="err-slot"><?= e($formState['errors']['slot'] ?? 'Pilih slot jam penjemputan.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['layanan']) ? ' has-error' : '' ?>" data-field="layanan">
                            <label class="field__label" for="order-layanan">Jenis layanan</label>
                            <select id="order-layanan" name="layanan" required aria-describedby="err-layanan">
                                <?php foreach ($services as $key => $service): ?>
                                <option value="<?= e($key) ?>"<?= $formState['values']['layanan'] === $key ? ' selected' : '' ?>><?= e($service['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field__err" id="err-layanan"><?= e($formState['errors']['layanan'] ?? 'Pilih jenis layanan.') ?></p>
                        </div>

                        <div class="field<?= isset($formState['errors']['berat']) ? ' has-error' : '' ?>" data-field="berat">
                            <label class="field__label" for="berat">Perkiraan berat <span>(opsional)</span></label>
                            <input type="number" id="berat" name="berat" value="<?= e($formState['values']['berat']) ?>" min="0.5" max="100" step="0.5" inputmode="decimal" aria-describedby="err-berat">
                            <p class="field__err" id="err-berat"><?= e($formState['errors']['berat'] ?? 'Berat perkiraan harus antara 0,5 dan 100 kg.') ?></p>
                        </div>

                        <div class="field field--wide" data-field="catatan">
                            <label class="field__label" for="catatan">Catatan untuk kurir <span>(opsional)</span></label>
                            <textarea id="catatan" name="catatan" maxlength="200" placeholder="Contoh: pagar hijau, titip ke penjaga kos."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn-3d" type="submit" id="form-submit"><span class="btn__label">Simpan jadwal jemput</span><span class="btn-spinner" aria-hidden="true"></span></button>
                        <span class="small">Data disimpan di sesi ini sampai Anda masuk.</span>
                    </div>
                    <p class="form-fine">Slot jam pada formulir masih contoh. Kurir mengonfirmasi ulang sebelum menjemput.</p>
                </form>
            </div>
        </div>
    </div>
</section>

</main>

<!-- ---------------------------------------------------------------- FOOTER -->
<footer class="site-footer">
    <div class="wrap">
        <div class="footer__grid">
            <div class="footer__brand">
                <a class="brand" href="#hero" aria-label="<?= e($biz['name']) ?>, kembali ke atas">
                    <span class="brand__plate">
                        <img class="brand__logo" src="go-laundry.png" alt="<?= e($biz['name']) ?>" width="1024" height="1024">
                    </span>
                </a>
                <p class="footer__desc">Layanan laundry jemput antar untuk warga <?= e($biz['region']) ?>.</p>
                <div class="footer__contact">
                    <?php if ($waHref !== null): ?>
                    <a href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp: <?= e($biz['whatsapp']) ?></a>
                    <?php else: ?>
                    <span>WhatsApp: <?= e($biz['whatsapp']) ?></span>
                    <?php endif; ?>
                    <span>Email: <?= e($biz['email']) ?></span>
                    <span>Alamat: <?= e($biz['address']) ?></span>
                </div>
            </div>

            <div>
                <p class="footer__h">Navigasi</p>
                <nav class="footer__links" aria-label="Navigasi footer">
                    <?php foreach ($navItems as $item): ?>
                    <a href="#<?= e($item['id']) ?>"><?= e($item['label']) ?></a>
                    <?php endforeach; ?>
                    <a href="#promo">Promo pengguna baru</a>
                    <a href="#pesan">Jadwal penjemputan</a>
                </nav>
            </div>

            <div>
                <p class="footer__h">Jam operasional</p>
                <div class="footer__hours">
                    <?php foreach ($siteConfig['hours']['outlet'] as $row): ?>
                    <div><span><?= e($row['day']) ?></span><b><?= e($row['time']) ?></b></div>
                    <?php endforeach; ?>
                </div>
                <p class="footer__h" style="margin-top:22px;">Kontak outlet</p>
                <div class="footer__contact" style="margin-top:0;">
                    <span>Instagram: <?= e($biz['instagram']) ?></span>
                    <span>Peta: <?= e($biz['maps_url'] ?? '[URL_PETA_LOKASI]') ?></span>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <span>&copy; <?= date('Y') ?> <?= e($biz['name']) ?>. Semua harga dan jam pada halaman ini masih placeholder sampai data resmi dimasukkan.</span>
            <span>Kebijakan privasi: <?= e($siteConfig['legal']['privacy']) ?> | Syarat layanan: <?= e($siteConfig['legal']['terms']) ?></span>
        </div>
    </div>
</footer>

<!-- ------------------------------------------------------------ AUTH MODAL -->
<div class="modal" id="auth-modal" hidden>
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" aria-describedby="auth-modal-desc" tabindex="-1">
        <button class="modal__close" type="button" data-modal-close aria-label="Tutup dialog">&times;</button>
        <p class="modal__kicker">Akun <?= e($biz['name']) ?></p>
        <h2 class="modal__title" id="auth-modal-title">Masuk untuk melanjutkan</h2>
        <p class="modal__desc" id="auth-modal-desc" data-modal-desc>Untuk mengunci harga, memantau status, dan mengumpulkan poin, akun diperlukan.</p>

        <div class="modal__draft" id="modal-draft" hidden>
            <p class="field-group__label" style="font-size:11px;">Ringkasan data</p>
            <dl data-modal-draft-list></dl>
        </div>

        <?php if ($authReady): ?>
        <div class="modal__actions">
            <a class="btn-3d" href="<?= e($siteConfig['auth']['login_url']) ?>">Masuk ke akun</a>
            <a class="btn-line" href="<?= e($siteConfig['auth']['register_url']) ?>">Buat akun baru</a>
        </div>
        <?php else: ?>
        <div class="modal__state">
            <b>Halaman masuk dan daftar belum tersambung di server ini.</b><br>
            Titik integrasi ada di <code><?= e($siteConfig['auth']['login_url']) ?></code> dan <code><?= e($siteConfig['auth']['register_url']) ?></code>. Setelah kedua file tersedia, tombol di sini aktif otomatis.
        </div>
        <div class="modal__actions">
            <button class="btn-3d" type="button" disabled>Masuk (belum tersedia)</button>
            <button class="btn-line" type="button" disabled>Daftar (belum tersedia)</button>
        </div>
        <?php endif; ?>

        <?php if ($waHref !== null): ?>
        <a class="btn-line modal__wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener">Lanjut lewat WhatsApp</a>
        <?php endif; ?>

        <p class="modal__fine">Data jadwal Anda disimpan di peramban dan sesi ini, lalu terisi otomatis saat halaman akun dibuka kembali.</p>
    </div>
</div>
<?php
/* ============================================================================
   [SECTION D: JAVASCRIPT, HD SEQUENCE ENGINE, LENIS, GSAP & INTERACTIVE LOGIC]
   ========================================================================= */
$jsData = [
    'frames' => $frames,
    'authReady' => (bool) $authReady,
    'auth' => [
        'login' => $siteConfig['auth']['login_url'],
        'register' => $siteConfig['auth']['register_url'],
    ],
    'services' => array_map(static function (array $service): array {
        return [
            'label' => $service['label'],
            'price' => (float) $service['price'],
            'unit' => $service['unit'],
            'min' => (float) $service['min_qty'],
            'step' => (float) $service['step'],
            'duration' => $service['duration_label'],
            'blurb' => $service['blurb'],
        ];
    }, $services),
    'draft' => $_SESSION['lkj_pickup_draft']['fields'] ?? null,
];
?>
<script>
window.LK_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js" defer></script>
<script src="assets/js/main.js" defer></script>
</body>
</html>
