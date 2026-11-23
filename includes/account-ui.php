<?php
declare(strict_types=1);

/* ============================================================================
   Tampilan bersama halaman akun publik.
   Arah desain: "meja loket" - papan slot jemput yang tenang di kiri, lembar
   isian datar di kanan. Tanpa gradasi, tanpa label uppercase ber-tracking.
   ========================================================================= */

function acct_asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return e($path . '?v=' . $version);
}

function acct_logo(array $biz, string $class = 'brand__logo'): string
{
    return '<span class="brand__plate">'
        . '<img class="' . e($class) . '" src="go-laundry.png" alt="' . e($biz['name']) . '" width="1024" height="1024">'
        . '</span>';
}

function acct_page_start(array $siteConfig, string $title, string $description, string $page = ''): void
{
    $biz = $siteConfig['business'];
    $customer = function_exists('customer_current') ? customer_current() : null;
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title . ' | ' . $biz['name']) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2B2043">
<link rel="icon" href="go-laundry.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&family=Poppins:wght@500;600&display=swap">
<link rel="stylesheet" href="<?= acct_asset('assets/css/main.css') ?>">
<link rel="stylesheet" href="<?= acct_asset('assets/css/account.css') ?>">
</head>
<body class="acct-body">
<a class="acct-skip" href="#acct-main">Lewati ke konten</a>

<header class="acct-bar">
    <div class="wrap acct-bar__inner">
        <a class="acct-bar__brand" href="index.php" aria-label="<?= e($biz['name']) ?>, ke beranda">
            <?= acct_logo($biz) ?>
        </a>
        <nav class="acct-bar__nav" aria-label="Navigasi akun">
            <a class="acct-bar__link" href="index.php">Beranda</a>
            <?php if ($customer !== null): ?>
            <a class="acct-bar__link" href="akun.php"<?= $page === 'akun' ? ' aria-current="page"' : '' ?>>Pesanan saya</a>
            <form class="acct-bar__logout" method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="acct-bar__link acct-bar__link--btn" type="submit">Keluar</button>
            </form>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="acct-main">
<?php
}

function acct_page_end(array $siteConfig, bool $withFooter = true): void
{
    $biz = $siteConfig['business'];
    $waNumber = preg_replace('/[^0-9]/', '', (string) $biz['whatsapp']) ?? '';
    ?>
</main>

<?php if ($withFooter): ?>
<footer class="acct-foot">
    <div class="wrap acct-foot__inner">
        <p class="acct-foot__brand"><?= e($biz['name']) ?> &middot; <?= e($biz['region'] ?? 'Delanggu, Klaten') ?></p>
        <p class="acct-foot__meta">
            <?php if (!is_placeholder($biz['whatsapp']) && strlen($waNumber) >= 9): ?>
            <a href="https://wa.me/<?= e($waNumber) ?>" target="_blank" rel="noopener">WhatsApp <?= e($biz['whatsapp']) ?></a>
            <span aria-hidden="true">&middot;</span>
            <?php endif; ?>
            <a href="index.php">Beranda</a>
            <span aria-hidden="true">&middot;</span>
            <a href="index.php#faq">Pertanyaan umum</a>
        </p>
    </div>
</footer>
<?php endif; ?>

<script src="<?= acct_asset('assets/js/account.js') ?>" defer></script>
</body>
</html>
<?php
}

/* ----------------------------------------------------------------------------
   Halaman masuk dan daftar memakai kerangka sendiri (assets/css/auth.css),
   terpisah dari shell halaman akun supaya tidak saling menimpa.
   ------------------------------------------------------------------------- */

function acct_auth_page_start(array $siteConfig, string $title, string $description, string $page = 'masuk'): void
{
    $biz = $siteConfig['business'];
    $altHref = $page === 'masuk' ? 'register.php' : 'login.php';
    $altLabel = $page === 'masuk' ? 'Buat akun' : 'Masuk';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title . ' | ' . $biz['name']) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2B2043">
<link rel="icon" href="go-laundry.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<link rel="stylesheet" href="<?= acct_asset('assets/css/main.css') ?>">
<link rel="stylesheet" href="<?= acct_asset('assets/css/auth.css') ?>">
</head>
<body class="auth-body">
<a class="auth-skip" href="#auth-main">Lewati ke konten</a>

<header class="auth-top">
    <div class="auth-wrap auth-top__inner">
        <a class="auth-top__brand" href="index.php" aria-label="<?= e($biz['name']) ?>, ke beranda">
            <span class="auth-top__mark">
                <img src="go-laundry.png" alt="" width="1024" height="1024">
            </span>
        </a>
        <nav class="auth-top__nav" aria-label="Navigasi akun">
            <a class="auth-top__link" href="index.php">Beranda</a>
            <span class="auth-top__sep" aria-hidden="true"></span>
            <a class="auth-top__link auth-top__link--strong" href="<?= e($altHref) ?>"><?= e($altLabel) ?></a>
        </nav>
    </div>
</header>

<main class="auth-main" id="auth-main">
<?php
}

function acct_auth_page_end(array $siteConfig): void
{
    $biz = $siteConfig['business'];
    ?>
</main>

<footer class="auth-foot">
    <div class="auth-wrap auth-foot__inner">
        <p><?= e($biz['name']) ?> &middot; <?= e($biz['region']) ?></p>
        <p class="auth-foot__links">
            <a href="index.php">Beranda</a>
            <a href="index.php#faq">Pertanyaan umum</a>
        </p>
    </div>
</footer>

<script src="<?= acct_asset('assets/js/account.js') ?>" defer></script>
</body>
</html>
<?php
}

/**
 * Adegan loket dalam satu sketsa: tali jemuran berisi kaos, handuk, dan kaus
 * kaki, keranjang cucian, mesin cuci, dan gelembung sabun. Garisnya ditarik
 * sekali saat halaman dibuka. Keranjang dipakai sebagai penanda isian: tiap
 * lipatan cucian muncul saat satu kolom formulir terisi (lihat account.js).
 */
function acct_auth_doodle(): void
{
    ?>
<div class="auth-doodle">
    <svg viewBox="0 0 340 275" role="presentation" focusable="false" aria-hidden="true">
        <g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path data-draw pathLength="1" style="--d:0ms" d="M6 42 C 72 58, 142 60, 208 48 C 256 39, 302 38, 334 45" />

            <g class="auth-sway" stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:140ms" stroke="currentColor" stroke-opacity=".8" d="M84 36 L86 54" />
                <path data-draw pathLength="1" style="--d:170ms" stroke="currentColor" stroke-opacity=".8" d="M91 35 L89 54" />
                <path data-draw pathLength="1" style="--d:260ms" class="auth-fill" fill="#E3E7F4" fill-opacity="0" d="M66 60 L54 82 L68 88 L68 136 C 78 144, 96 144, 106 136 L106 88 L120 82 L108 60 L96 56 C 90 65, 82 65, 76 56 Z" />
                <path data-draw pathLength="1" style="--d:460ms" d="M76 56 C 82 66, 90 66, 96 56" />
                <path data-draw pathLength="1" style="--d:540ms" stroke-width="1.25" stroke-opacity=".3" d="M68 126 L106 126" />
            </g>

            <g class="auth-sway auth-sway--late" stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:160ms" stroke="currentColor" stroke-opacity=".8" d="M170 36 L171 54" />
                <path data-draw pathLength="1" style="--d:190ms" stroke="currentColor" stroke-opacity=".8" d="M177 35 L175 54" />
                <path data-draw pathLength="1" style="--d:300ms" class="auth-fill" fill="#EBEEF8" fill-opacity="0" d="M160 58 C 158 86, 158 112, 160 132 C 168 138, 184 138, 190 132 C 192 110, 192 84, 190 58 C 180 54, 170 54, 160 58 Z" />
                <path data-draw pathLength="1" style="--d:580ms" stroke-width="1.25" stroke-opacity=".3" d="M182 62 C 181 80, 182 96, 183 112" />
                <path data-draw pathLength="1" style="--d:560ms" stroke-opacity=".4" d="M160 98 C 170 101, 182 101, 190 98" />
                <path data-draw pathLength="1" style="--d:600ms" stroke-opacity=".4" d="M160 114 C 170 117, 182 117, 190 114" />
            </g>

            <g class="auth-sway" stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:180ms" stroke="currentColor" stroke-opacity=".8" d="M248 36 L249 54" />
                <path data-draw pathLength="1" style="--d:210ms" stroke="currentColor" stroke-opacity=".8" d="M255 35 L253 54" />
                <path data-draw pathLength="1" style="--d:340ms" class="auth-fill" fill="#E3E7F4" fill-opacity="0" d="M248 56 C 250 68, 247 76, 249 90 C 251 102, 248 110, 256 118 C 265 126, 276 122, 277 111 C 278 100, 268 98, 264 88 C 260 78, 262 66, 261 56 Z" />
                <path data-draw pathLength="1" style="--d:640ms" stroke-width="1.25" stroke-opacity=".3" d="M253 84 C 259 92, 259 100, 255 108" />
                <path data-draw pathLength="1" style="--d:620ms" stroke-opacity=".4" d="M250 65 L259 63" />
                <path data-draw pathLength="1" style="--d:650ms" stroke-opacity=".4" d="M250 75 L260 73" />
            </g>

            <g stroke-opacity=".8">
                <path data-draw pathLength="1" style="--d:720ms" class="auth-fill" fill="#E3E7F4" fill-opacity="0" d="M24 192 C 58 190, 96 190, 126 193 C 130 194, 131 197, 130 201 L 122 250 C 121 256, 117 258, 112 258 L 38 258 C 33 258, 29 256, 28 250 L 20 201 C 19 197, 20 192, 24 192 Z" />
                <path data-draw pathLength="1" style="--d:820ms" stroke-width="1.25" stroke-opacity=".3" d="M26 218 C 58 216, 96 216, 127 219" />
                <path data-draw pathLength="1" style="--d:850ms" stroke-width="1.25" stroke-opacity=".3" d="M29 238 C 58 236, 96 236, 124 239" />

                <g class="auth-load" stroke="#53629E">
                    <g class="auth-item" data-step="1">
                        <path d="M32 192 L32 180 C 32 177, 34 175, 38 175 L64 175 C 68 175, 70 177, 70 180 L70 192" />
                        <path style="stroke-opacity:.35" d="M36 185 L66 185" />
                    </g>
                    <g class="auth-item" data-step="2">
                        <path d="M74 192 L74 178 C 74 175, 76 173, 80 173 L104 173 C 108 173, 110 175, 110 178 L110 192" />
                        <path style="stroke-opacity:.35" d="M78 183 L106 183" />
                    </g>
                    <g class="auth-item" data-step="3">
                        <path d="M40 175 L40 163 C 40 160, 42 158, 46 158 L70 158 C 74 158, 76 160, 76 163 L76 175" />
                        <path style="stroke-opacity:.35" d="M44 167 L72 167" />
                    </g>
                    <g class="auth-item" data-step="4">
                        <path d="M80 173 L80 160 C 80 157, 82 155, 86 155 L108 155 C 112 155, 114 157, 114 160 L114 173" />
                        <path style="stroke-opacity:.35" d="M84 164 L110 164" />
                    </g>
                </g>
                <path data-draw pathLength="1" style="--d:880ms" stroke-width="2" d="M20 198 C 58 195, 96 195, 129 202" />
            </g>

            <g stroke-opacity=".8">
                <path data-draw pathLength="1" style="--d:900ms" stroke-width="2" class="auth-fill" fill="#F6F7FC" fill-opacity="0" d="M216 156 C 250 153, 296 153, 314 158 C 320 160, 322 165, 322 173 L322 246 C 322 254, 318 258, 310 258 L222 258 C 214 258, 208 254, 208 246 L208 173 C 208 165, 210 158, 216 156 Z" />
                <path data-draw pathLength="1" style="--d:1000ms" stroke-width="1.3" d="M209 172 C 250 170, 292 170, 321 172" />
                <path data-draw pathLength="1" style="--d:1020ms" stroke-width="1.2" d="M221 160 L 246 159 C 248.5 159, 250 160.5, 250 163 L 250 168 M 222 164 L 249 163" />
                <path data-draw pathLength="1" style="--d:1060ms" d="M303 178 a5 5 0 1 0 10 0 a5 5 0 1 0 -10 0" />
                <path data-draw pathLength="1" style="--d:1070ms" stroke-width="1.3" stroke-opacity=".55" d="M264 181 C 284 181, 298 196, 298 216 C 298 236, 284 251, 264 251 C 244 251, 230 236, 230 216 C 230 196, 244 181, 264 181 Z" />
                <path data-draw pathLength="1" style="--d:1100ms" stroke-width="1.3" stroke-opacity=".6" d="M221 258 L 219 263 M 226 258 L 228 263 M 304 258 L 302 263 M 309 258 L 311 263" />
                <path data-draw pathLength="1"  />
                <g class="auth-drum">
                    <path data-draw pathLength="1" style="--d:1080ms" class="auth-fill" fill="#E3E7F4" fill-opacity="0" d="M264 189 C 279 189, 291 201, 291 216 C 291 231, 279 243, 264 243 C 249 243, 237 231, 237 216 C 237 201, 249 189, 264 189 Z" />
                    <path data-draw pathLength="1" style="--d:1140ms" stroke-opacity=".38" d="M249 216 C 251 210, 258 208, 261 212 C 264 216, 261 221, 256 221 C 251 221, 247 220, 249 216 Z" />
                    <path data-draw pathLength="1" style="--d:1160ms" stroke-opacity=".38" d="M270 224 C 272 219, 278 218, 280 222 C 282 226, 278 229, 274 228 C 271 227, 269 227, 270 224 Z" />
                    
                </g>
            </g>

            <g class="auth-bubbles" stroke="#D9A227">
                <circle class="auth-bubble" style="--o:0s" cx="186" cy="152" r="6" />
                <circle class="auth-bubble" style="--o:2.2s" cx="170" cy="126" r="4" />
                <circle class="auth-bubble" style="--o:4.4s" cx="196" cy="106" r="2.8" />
                <circle class="auth-bubble" style="--o:3.1s" cx="158" cy="168" r="3.4" />
                <circle class="auth-bubble" style="--o:5.8s" cx="206" cy="134" r="3" />
            </g>
        </g>
    </svg>
</div>
<?php
}

/** Tarikan kuas tipis di bawah judul, digambar sekali saat halaman dibuka. */
function acct_auth_underline(): void
{
    ?>
<svg class="auth-underline" viewBox="0 0 208 10" aria-hidden="true" focusable="false">
    <path data-draw pathLength="1" style="--d:380ms" d="M2 7 C 46 2, 118 2.5, 206 5.5"
        fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
</svg>
<?php
}

/** Ikon sketsa kecil untuk baris data loket. */
function acct_fact_icon(string $key): string
{
    $paths = [
        'tarif' => '<path d="M8.6 2.4 H13.2 V7 L7 13.2 L2.8 9 Z"/><circle cx="10.9" cy="4.7" r="1"/>',
        'waktu' => '<path d="M8 2.5 A5.5 5.5 0 1 1 7.9 2.5 Z"/><path d="M8 5 L8 8.5 L10.5 9.5"/>',
        'slot'  => '<path d="M3 6 H13 L11.5 13 H4.5 Z"/><path d="M5.5 6 C5.5 3.5, 10.5 3.5, 10.5 6"/>',
        'area'  => '<path d="M8 13 C5 9.5, 4 8, 4 6.4 A4 4 0 0 1 12 6.4 C12 8, 11 9.5, 8 13 Z"/><circle cx="8" cy="6.6" r="1.4"/>',
    ];
    $body = $paths[$key] ?? $paths['area'];
    return '<svg class="auth-fact__icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"'
        . ' fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">'
        . $body . '</svg>';
}

/**
 * Kolom data loket: tarif, pengerjaan, slot jemput, dan jangkauan. Semua nilai
 * dibaca dari config/site.php, dan baris yang masih placeholder dilewati.
 */
function acct_auth_facts(array $siteConfig): void
{
    $services = array_filter(
        (array) ($siteConfig['services'] ?? []),
        static fn ($service) => is_array($service) && (float) ($service['price'] ?? 0) > 0
    );
    $slots = array_values(array_filter(
        array_map('strval', (array) ($siteConfig['pickup_slots'] ?? [])),
        static fn ($slot) => !is_placeholder($slot)
    ));
    $areas = array_values(array_filter(
        array_map('strval', (array) ($siteConfig['areas'] ?? [])),
        static fn ($area) => !is_placeholder($area)
    ));

    $kiloan = $services['kiloan'] ?? null;
    $rows = [];

    if (is_array($kiloan) && !is_placeholder((string) ($kiloan['price'] ?? ''))) {
        $minQty = rtrim(rtrim(number_format((float) ($kiloan['min_qty'] ?? 0), 1, ',', '.'), '0'), ',');
        $rows[] = [
            'tarif',
            'Tarif kiloan',
            e(rupiah((float) $kiloan['price']) . ' per ' . (string) $kiloan['unit']),
            'minimal ' . e($minQty . ' ' . (string) $kiloan['unit']),
        ];
    }

    $duration = [];
    foreach ($services as $service) {
        if (isset($service['label'], $service['duration_label'])) {
            $duration[] = (string) $service['label'] . ' ' . (string) $service['duration_label'];
        }
    }
    if ($duration !== []) {
        $rows[] = ['waktu', 'Pengerjaan', e(implode(', ', $duration)), ''];
    }

    if ($slots !== []) {
        $rows[] = ['slot', 'Slot jemput', implode(' &middot; ', array_map(
            static fn ($slot) => e(str_replace(' - ', ' – ', $slot)),
            $slots
        )), ''];
    }

    if ($areas !== []) {
        $rows[] = ['area', 'Jangkauan', e(implode(', ', $areas)), ''];
    }

    if ($rows === []) {
        return;
    }
    ?>
<aside class="auth-facts" aria-labelledby="auth-facts-title">
    <?php acct_auth_doodle(); ?>

    <h2 class="auth-facts__title" id="auth-facts-title">Loket <?= e($siteConfig['business']['name']) ?></h2>
    <p class="auth-facts__lead">Angka yang berlaku untuk setiap pesanan jemput antar.</p>

    <dl class="auth-facts__list">
        <?php foreach ($rows as [$icon, $label, $value, $suffix]): ?>
        <div class="auth-fact">
            <dt><?= acct_fact_icon($icon) ?><?= e($label) ?></dt>
            <dd><?= $value ?><?php if ($suffix !== ''): ?> <span><?= $suffix ?></span><?php endif; ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>

    <p class="auth-facts__note">Kuota slot ekspres terbatas dan dikonfirmasi kurir saat penjemputan.</p>
</aside>
<?php
}

/** Satu kolom isian dengan label, petunjuk, dan pesan galat yang terhubung. */
function acct_field(string $id, string $label, array $options = []): void
{
    $type = (string) ($options['type'] ?? 'text');
    $value = (string) ($options['value'] ?? '');
    $error = (string) ($options['error'] ?? '');
    $hint = (string) ($options['hint'] ?? '');
    $required = (bool) ($options['required'] ?? true);
    $reveal = (bool) ($options['reveal'] ?? false);
    $extra = (array) ($options['attrs'] ?? []);
    $describedBy = [];
    if ($hint !== '') {
        $describedBy[] = 'hint-' . $id;
    }
    if ($error !== '') {
        $describedBy[] = 'err-' . $id;
    }

    $attributes = '';
    foreach ($extra as $name => $attrValue) {
        if ($attrValue === null || $attrValue === '') {
            continue;
        }
        $attributes .= ' ' . e((string) $name) . '="' . e((string) $attrValue) . '"';
    }
    ?>
<div class="field acct-field<?= $error !== '' ? ' has-error' : '' ?>" data-field="<?= e($id) ?>">
    <label class="field__label" for="<?= e($id) ?>">
        <?= e($label) ?><?= $required ? '' : ' <span>(opsional)</span>' ?>
    </label>
    <?php if ($reveal): ?>
    <div class="acct-field__wrap">
        <input type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($id) ?>" value="<?= $type === 'password' ? '' : e($value) ?>"
            data-pw-input<?= $describedBy ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '' ?>
            <?= $error !== '' ? 'aria-invalid="true"' : '' ?><?= $required ? ' required' : '' ?><?= $attributes ?>>
        <button class="acct-field__reveal" type="button" data-pw-toggle="<?= e($id) ?>"
            aria-pressed="false" aria-label="Tampilkan kata sandi">
            <span data-pw-label>Tampilkan</span>
        </button>
    </div>
    <?php else: ?>
    <input type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($id) ?>" value="<?= e($value) ?>"
        <?= $describedBy ? 'aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '' ?>
        <?= $error !== '' ? 'aria-invalid="true"' : '' ?><?= $required ? ' required' : '' ?><?= $attributes ?>>
    <?php endif; ?>
    <?php if ($hint !== ''): ?>
    <p class="field__hint" id="hint-<?= e($id) ?>"><?= e($hint) ?></p>
    <?php endif; ?>
    <p class="field__err" id="err-<?= e($id) ?>" data-err-for="<?= e($id) ?>"><?= e($error) ?></p>
</div>
<?php
}

/**
 * Lembar isian: panel datar tanpa gradasi. $heading singkat, $note satu baris.
 */
function acct_sheet_start(string $heading = '', string $note = ''): void
{
    ?>
<div class="sheet">
    <?php if ($heading !== '' || $note !== ''): ?>
    <div class="sheet__head">
        <?php if ($heading !== ''): ?>
        <h2 class="sheet__title"><?= e($heading) ?></h2>
        <?php endif; ?>
        <?php if ($note !== ''): ?>
        <p class="sheet__note"><?= e($note) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php
}

function acct_sheet_end(string $footNote = ''): void
{
    ?>
    <?php if ($footNote !== ''): ?>
    <p class="sheet__fine"><?= e($footNote) ?></p>
    <?php endif; ?>
</div>
<?php
}

function acct_alert(string $type, string $message): string
{
    $role = $type === 'error' ? 'alert' : 'status';
    return '<div class="acct-alert acct-alert--' . e($type) . '" role="' . $role . '">' . e($message) . '</div>';
}

function acct_status_pill(string $status): string
{
    $map = [
        'baru'         => 'info',
        'dikonfirmasi' => 'accent',
        'dijemput'     => 'accent',
        'proses'       => 'warn',
        'siap'         => 'ok',
        'diantar'      => 'accent',
        'selesai'      => 'ok',
        'batal'        => 'danger',
    ];
    $tone = $map[$status] ?? 'info';
    return '<span class="acct-pill acct-pill--' . e($tone) . '">' . e(lk_order_status_label($status)) . '</span>';
}

function acct_date(?string $value, string $format = 'd M Y'): string
{
    if ($value === null || $value === '') {
        return '–';
    }
    $time = strtotime($value);
    if ($time === false) {
        return '–';
    }
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $out = date($format, $time);
    return strtr($out, [
        'Jan' => $months[1], 'Feb' => $months[2], 'Mar' => $months[3], 'Apr' => $months[4],
        'May' => $months[5], 'Jun' => $months[6], 'Jul' => $months[7], 'Aug' => $months[8],
        'Sep' => $months[9], 'Oct' => $months[10], 'Nov' => $months[11], 'Dec' => $months[12],
    ]);
}

/** Baris label-nilai dengan garis titik, motif yang dipakai ulang di halaman akun. */
function acct_leader_row(string $label, string $value, string $class = ''): string
{
    return '<div class="leader' . ($class !== '' ? ' ' . e($class) : '') . '">'
        . '<span class="leader__label">' . e($label) . '</span>'
        . '<span class="leader__dots" aria-hidden="true"></span>'
        . '<span class="leader__value">' . e($value) . '</span>'
        . '</div>';
}
