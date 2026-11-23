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
 * Adegan loket selebar halaman: ruko laundry dengan jendela mesin, jemuran di
 * halaman, dan meja timbang. Garisnya ditarik sekali saat halaman dibuka, kain
 * bergoyang dari jepitannya, drum di jendela berputar saat formulir dikirim,
 * dan keranjang dipakai sebagai penanda isian (lihat account.js).
 */
function acct_auth_doodle(): void
{
    ?>
<div class="auth-doodle">
    <svg viewBox="0 0 1080 320" role="presentation" focusable="false" aria-hidden="true" preserveAspectRatio="xMidYMax meet">
        <g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">

            <path data-draw pathLength="1" style="--d:0ms" stroke-opacity=".45" d="M8 296 C 300 300, 780 292, 1072 296" />

            <g stroke="#53629E" stroke-opacity=".95">
                <path data-draw pathLength="1" class="auth-fill" fill="#F6F7FC" fill-opacity="0" style="--d:60ms" d="M52 296 L52 152 L216 98 L380 152 L380 296" />
                <path data-draw pathLength="1" style="--d:100ms" d="M42 152 L390 152" />

                <path data-draw pathLength="1" class="auth-fill" fill="#EDEFF7" fill-opacity="0" style="--d:140ms" d="M42 152 L390 152 L390 186 Q365.15 202 340.3 186 Q315.45 202 290.6 186 Q265.75 202 240.9 186 Q216.05 202 191.2 186 Q166.35 202 141.5 186 Q116.65 202 91.8 186 Q66.95 202 42 186 Z" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:220ms" d="M91.7 152 L91.7 190" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:250ms" d="M141.4 152 L141.4 192" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:280ms" d="M191.1 152 L191.1 192" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:310ms" d="M240.8 152 L240.8 192" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:340ms" d="M290.5 152 L290.5 192" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:370ms" d="M340.2 152 L340.2 190" />

                <path data-draw pathLength="1" class="auth-fill" fill="#F6F7FC" fill-opacity="0" style="--d:260ms" d="M76 206 L146 206 L146 296 L76 296 Z" />
                <path data-draw pathLength="1" style="--d:400ms" d="M76 268 L146 268" />
                <path data-draw pathLength="1" style="--d:430ms" d="M136 254 a4 4 0 1 0 0.1 0" />

                <path data-draw pathLength="1" class="auth-fill" fill="#F6F7FC" fill-opacity="0" style="--d:340ms" d="M176 200 L352 200 L352 274 L176 274 Z" />
                <path data-draw pathLength="1" stroke-width="1.6" stroke-opacity=".5" style="--d:380ms" d="M184 208 L344 208 L344 266 L184 266 Z" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:410ms" d="M190 262 C 200 240, 206 226, 210 214" />
                <path data-draw pathLength="1" style="--d:440ms" d="M264 203 C 283 203, 298 218, 298 237 C 298 256, 283 271, 264 271 C 245 271, 230 256, 230 237 C 230 218, 245 203, 264 203 Z" />
                <g class="auth-drum">
                    <path data-draw pathLength="1" class="auth-fill" fill="#E3E7F4" fill-opacity="0" style="--d:480ms" d="M264 211 C 278 211, 290 223, 290 237 C 290 251, 278 263, 264 263 C 250 263, 238 251, 238 237 C 238 223, 250 211, 264 211 Z" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:560ms" d="M248 234 C 250 227, 258 225, 261 230 C 264 235, 260 241, 254 241 C 249 241, 246 239, 248 234 Z" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:580ms" d="M269 239 C 271 234, 278 233, 280 238 C 282 243, 277 246, 273 245 C 270 244, 268 243, 269 239 Z" />
                </g>
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:600ms" d="M246 220 C 252 214, 260 216, 260 222" />
            </g>

            <g stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" style="--d:420ms" d="M424 296 L428 150" />
                <path data-draw pathLength="1" style="--d:440ms" d="M422 148 L434 146" />
                <path data-draw pathLength="1" style="--d:450ms" d="M792 296 L796 150" />
                <path data-draw pathLength="1" style="--d:470ms" d="M790 148 L802 146" />
                <path data-draw pathLength="1" style="--d:500ms" d="M428 154 C 520 178, 700 178, 796 156" />

                <g class="auth-sway">
                    <path data-draw pathLength="1" style="--d:540ms" stroke="currentColor" stroke-opacity=".75" d="M496 148 L498 168" />
                    <path data-draw pathLength="1" style="--d:560ms" stroke="currentColor" stroke-opacity=".75" d="M505 147 L503 172" />
                    <path data-draw pathLength="1" stroke-width="1.5" stroke-opacity=".7" style="--d:580ms" d="M495 158 L506 157" />
                    <path data-draw pathLength="1" class="auth-fill" fill="#E3E7F4" fill-opacity="0" style="--d:600ms" d="M470 176 L452 206 L470 214 L470 268 C 484 280, 512 280, 526 268 L526 214 L544 206 L526 176 L510 170 C 502 182, 490 182, 482 170 Z" />
                    <path data-draw pathLength="1" style="--d:760ms" d="M482 170 C 490 182, 502 182, 510 170" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:790ms" d="M470 254 L526 254" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".28" style="--d:810ms" d="M488 214 C 484 230, 485 244, 488 256" />
                </g>

                <g class="auth-sway auth-sway--late">
                    <path data-draw pathLength="1" style="--d:600ms" stroke="currentColor" stroke-opacity=".75" d="M596 150 L597 170" />
                    <path data-draw pathLength="1" style="--d:620ms" stroke="currentColor" stroke-opacity=".75" d="M604 149 L602 174" />
                    <path data-draw pathLength="1" stroke-width="1.5" stroke-opacity=".7" style="--d:640ms" d="M595 160 L605 159" />
                    <path data-draw pathLength="1" class="auth-fill" fill="#EBEEF8" fill-opacity="0" style="--d:660ms" d="M582 172 C 580 210, 580 240, 582 262 C 592 270, 612 270, 620 262 C 622 240, 622 210, 620 172 C 608 166, 594 166, 582 172 Z" />
                    <path data-draw pathLength="1" stroke-opacity=".4" style="--d:800ms" d="M582 216 C 594 220, 610 220, 620 216" />
                    <path data-draw pathLength="1" stroke-opacity=".4" style="--d:820ms" d="M582 236 C 594 240, 610 240, 620 236" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".26" style="--d:840ms" d="M612 180 C 611 202, 612 224, 613 244" />
                    <path data-draw pathLength="1" stroke-width="1.2" stroke-opacity=".22" style="--d:860ms" d="M588 188 L596 188 M604 188 L612 188 M588 230 L596 230 M604 230 L612 230" />
                </g>

                <g class="auth-sway">
                    <path data-draw pathLength="1" style="--d:660ms" stroke="currentColor" stroke-opacity=".75" d="M688 152 L689 170" />
                    <path data-draw pathLength="1" style="--d:680ms" stroke="currentColor" stroke-opacity=".75" d="M695 151 L693 174" />
                    <path data-draw pathLength="1" stroke-width="1.5" stroke-opacity=".7" style="--d:700ms" d="M687 161 L696 160" />
                    <path data-draw pathLength="1" class="auth-fill" fill="#E3E7F4" fill-opacity="0" style="--d:700ms" d="M688 172 C 690 186, 687 196, 689 212 C 691 226, 688 236, 697 245 C 707 254, 719 250, 720 238 C 721 226, 710 224, 706 213 C 702 202, 704 187, 703 172 Z" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".45" style="--d:840ms" d="M687 178 C 693 176, 699 176, 704 178" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:850ms" d="M690 182 L704 179" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:860ms" d="M691 194 L705 191" />
                </g>

                <g class="auth-sway auth-sway--late">
                    <path data-draw pathLength="1" style="--d:720ms" stroke="currentColor" stroke-opacity=".75" d="M738 152 L739 170" />
                    <path data-draw pathLength="1" style="--d:740ms" stroke="currentColor" stroke-opacity=".75" d="M745 151 L743 174" />
                    <path data-draw pathLength="1" stroke-width="1.5" stroke-opacity=".7" style="--d:760ms" d="M737 161 L746 160" />
                    <path data-draw pathLength="1" class="auth-fill" fill="#E3E7F4" fill-opacity="0" style="--d:760ms" d="M738 172 C 740 184, 737 194, 739 208 C 741 220, 738 230, 747 238 C 757 246, 768 242, 769 231 C 770 220, 760 218, 756 208 C 752 198, 754 184, 753 172 Z" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".45" style="--d:870ms" d="M737 177 C 743 175, 749 175, 754 177" />
                    <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".35" style="--d:880ms" d="M740 181 L753 178" />
                </g>
            </g>

            <g stroke="#53629E" stroke-opacity=".95">
                <path data-draw pathLength="1" style="--d:690ms" d="M1032 100 L1032 296" />
                <path data-draw pathLength="1" style="--d:700ms" d="M1026 102 L1038 98" />
                <path data-draw pathLength="1" stroke-width="2.6" style="--d:700ms" d="M1032 106 L900 106" />
                <path data-draw pathLength="1" style="--d:750ms" d="M896 104 a4 4 0 1 0 0.1 0" />
                <path data-draw pathLength="1" style="--d:730ms" d="M900 106 L900 122" />
                <path data-draw pathLength="1" style="--d:760ms" d="M900 122 C 910 122, 918 130, 918 140 C 918 150, 910 158, 900 158 C 890 158, 882 150, 882 140 C 882 130, 890 122, 900 122 Z" />
                <path data-draw pathLength="1" stroke-width="1.6" style="--d:860ms" d="M900 140 L910 132" />
                <path data-draw pathLength="1" style="--d:880ms" d="M888 158 L900 172" />
                <path data-draw pathLength="1" style="--d:900ms" d="M912 158 L900 172" />
                <path data-draw pathLength="1" style="--d:920ms" d="M878 172 L922 172 L914 188 L886 188 Z" />
                <path data-draw pathLength="1" class="auth-fill" fill="#EBEEF8" fill-opacity="0" style="--d:940ms" d="M884 172 L884 164 C 884 160, 887 158, 891 158 L909 158 C 913 158, 916 161, 916 164 L916 172" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:960ms" d="M889 166 L912 166" />
            </g>

            <g stroke="#53629E" stroke-opacity=".9">
                <path data-draw pathLength="1" class="auth-fill" fill="#E3E7F4" fill-opacity="0" style="--d:820ms" d="M852 240 C 880 238, 918 238, 944 241 C 948 242, 950 245, 949 249 L 942 286 C 941 290, 938 292, 934 292 L 866 292 C 862 292, 859 290, 858 286 L 850 249 C 849 245, 850 240, 852 240 Z" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:940ms" d="M854 262 C 880 260, 916 260, 946 263" />
                <path data-draw pathLength="1" stroke-width="1.4" stroke-opacity=".3" style="--d:960ms" d="M857 278 C 880 276, 916 276, 943 279" />
                <path data-draw pathLength="1" stroke-width="1.3" stroke-opacity=".28" style="--d:980ms" d="M870 250 L864 258 M886 250 L880 258 M902 250 L896 258 M918 250 L912 258 M874 268 L868 274 M892 268 L886 274 M910 268 L904 274 M928 268 L922 274" />
                <path data-draw pathLength="1" stroke-width="1.8" stroke-opacity=".55" style="--d:800ms" d="M888 240 C 892 226, 908 226, 912 240" />

                <g class="auth-load">
                    <g class="auth-item" data-step="1">
                        <path d="M860 240 L860 228 C 860 225, 862 223, 866 223 L892 223 C 896 223, 898 225, 898 228 L898 240" />
                        <path style="stroke-opacity:.3" d="M864 232 L894 232" />
                    </g>
                    <g class="auth-item" data-step="2">
                        <path d="M902 240 L902 226 C 902 223, 904 221, 908 221 L930 221 C 934 221, 936 223, 936 226 L936 240" />
                        <path style="stroke-opacity:.3" d="M906 230 L932 230" />
                    </g>
                    <g class="auth-item" data-step="3">
                        <path d="M870 223 L870 211 C 870 208, 872 206, 876 206 L898 206 C 902 206, 904 208, 904 211 L904 223" />
                        <path style="stroke-opacity:.3" d="M874 214 L900 214" />
                    </g>
                    <g class="auth-item" data-step="4">
                        <path d="M908 221 L908 208 C 908 205, 910 203, 914 203 L934 203 C 938 203, 940 205, 940 208 L940 221" />
                        <path style="stroke-opacity:.3" d="M912 211 L936 211" />
                    </g>
                </g>
                <path data-draw pathLength="1" style="--d:1000ms" d="M849 246 C 880 243, 918 243, 948 249" />
            </g>

            <g class="auth-bubbles" stroke="#D9A227">
                <circle class="auth-bubble" style="--o:0s" cx="392" cy="200" r="8" />
                <circle class="auth-bubble" style="--o:2.2s" cx="406" cy="166" r="5" />
                <circle class="auth-bubble" style="--o:4.4s" cx="386" cy="234" r="4.5" />
                <circle class="auth-bubble" style="--o:3.1s" cx="966" cy="214" r="7" />
                <circle class="auth-bubble" style="--o:5.8s" cx="982" cy="182" r="5" />
                <circle class="auth-bubble" style="--o:1.6s" cx="968" cy="264" r="3.5" />
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
<div class="auth-art">
    <?php acct_auth_doodle(); ?>
</div>

<aside class="auth-facts" aria-labelledby="auth-facts-title">
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
