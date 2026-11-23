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
 * Sketsa tangan: tali jemuran, kaos, kaus kaki, dan mesin cuci. Garisnya
 * digambar sekali saat halaman dibuka, kaos dan kaus kaki bergoyang pelan,
 * gelembung naik, dan drum mesin berputar saat formulir dikirim.
 */
function acct_auth_doodle(): void
{
    ?>
<div class="auth-doodle">
    <svg viewBox="0 0 320 200" role="presentation" focusable="false" aria-hidden="true">
        <g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path data-draw pathLength="1" style="--d:0ms" d="M8 43 C 78 58, 148 58, 220 45 C 258 38, 288 39, 312 44" />

            <g class="auth-sway" stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:180ms" stroke="currentColor" stroke-opacity=".8" d="M84 33 L 86.5 51" />
                <path data-draw pathLength="1" style="--d:220ms" stroke="currentColor" stroke-opacity=".8" d="M91 32 L 88 51" />
                <path data-draw pathLength="1" style="--d:300ms" d="M66 57 L 55 77 L 68 83 L 68 123 C 78 130, 95 130, 105 123 L 105 83 L 118 77 L 107 57 L 96 53 C 90 61, 82 61, 76 53 Z" />
                <path data-draw pathLength="1" style="--d:520ms" d="M76 53 C 82 62, 90 62, 96 53" />
                <path data-draw pathLength="1" style="--d:640ms" stroke-opacity=".35" d="M68 113 L 105 113" />
            </g>

            <g class="auth-sway auth-sway--late" stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:240ms" stroke="currentColor" stroke-opacity=".8" d="M213 33 L 214.5 51" />
                <path data-draw pathLength="1" style="--d:280ms" stroke="currentColor" stroke-opacity=".8" d="M219 32 L 217 51" />
                <path data-draw pathLength="1" style="--d:420ms" d="M212 53 C 214 64, 211 72, 213 84 C 215 98, 212 108, 220 116 C 229 124, 240 120, 241 109 C 242 98, 232 96, 228 86 C 224 76, 226 64, 225 53 Z" />
                <path data-draw pathLength="1" style="--d:700ms" stroke-opacity=".4" d="M213 61 L 224 58" />
                <path data-draw pathLength="1" style="--d:740ms" stroke-opacity=".4" d="M214 71 L 226 68" />
            </g>

            <g stroke-opacity=".75">
                <path data-draw pathLength="1" style="--d:120ms" d="M14 132 C 46 130, 72 132, 78 138 C 84 144, 83 186, 76 192 C 68 197, 20 197, 12 192 C 5 187, 5 141, 14 132 Z" />
                <path data-draw pathLength="1" style="--d:560ms" d="M31 148 L 55 148" />
                <g class="auth-drum">
                    <path data-draw pathLength="1" style="--d:600ms" d="M45 152 C 62 152, 70 160, 70 170 C 70 180, 59 188, 45 188 C 31 188, 21 180, 21 170 C 21 160, 29 152, 45 152 Z" />
                    <path data-draw pathLength="1" style="--d:760ms" stroke-opacity=".45" d="M45 160 L 45 180" />
                    <path data-draw pathLength="1" style="--d:780ms" stroke-opacity=".45" d="M36 165 L 54 175" />
                    <path data-draw pathLength="1" style="--d:800ms" stroke-opacity=".45" d="M54 165 L 36 175" />
                </g>
                <path data-draw pathLength="1" style="--d:700ms" d="M64 138 C 66 136, 68 138, 66 140" stroke-opacity=".5" />
            </g>

            <g class="auth-bubbles" stroke="#D9A227">
                <circle class="auth-bubble" style="--o:0s" cx="196" cy="150" r="5.5" />
                <circle class="auth-bubble" style="--o:2.4s" cx="216" cy="126" r="3.6" />
                <circle class="auth-bubble" style="--o:4.6s" cx="232" cy="158" r="2.6" />
                <circle class="auth-bubble" style="--o:6.2s" cx="248" cy="116" r="4.2" />
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
        return '—';
    }
    $time = strtotime($value);
    if ($time === false) {
        return '—';
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
