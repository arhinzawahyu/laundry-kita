<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$next = 'index.php';
$requested = $_GET['next'] ?? ($_POST['next'] ?? '');
if (is_string($requested) && preg_match('/^[a-z0-9_-]+\.php(\?[a-z0-9_=&%.-]*)?$/i', $requested) === 1) {
    $next = $requested;
}

if (admin_current() !== null) {
    admin_redirect($next);
}

$pdo = lk_db();
$adminCount = null;
if ($pdo) {
    try {
        $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}
if ($adminCount === 0) {
    admin_redirect('setup.php');
}

$email = '';
$flashes = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $email = strtolower(admin_post('email'));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        admin_flash('error', 'Masukkan email admin yang valid.');
        admin_redirect('login.php');
    }
    if ($password === '') {
        admin_flash('error', 'Kata sandi belum diisi.');
        admin_redirect('login.php');
    }
    if (admin_login_attempt($email, $password)) {
        admin_redirect($next);
    }
    admin_redirect('login.php');
}

$flashes = admin_flashes();
$hasError = false;
foreach ($flashes as $flash) {
    if (($flash['type'] ?? '') === 'error') {
        $hasError = true;
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Masuk · GO Laundry CMS</title>
<meta name="theme-color" content="#2E2149">
<link rel="icon" type="image/png" href="../go-laundry.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/tokens.css?v=<?= @filemtime(__DIR__ . '/assets/tokens.css') ?: 1 ?>">
<link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(__DIR__ . '/assets/admin.css') ?: 1 ?>">
</head>
<body>
<main class="auth">
    <section class="auth__art" aria-hidden="true">
        <a class="brand" href="../index.php" tabindex="-1">
            <span class="brand__plate">
                <img class="brand__logo" src="../go-laundry.png" alt="" width="1024" height="1024">
            </span>
            <span class="brand__text"><span>Admin CMS</span></span>
        </a>

        <div class="auth__copy">
            <h1>Satu tempat untuk cucian, jadwal, dan konten situs.</h1>
            <p>Kelola pesanan jemput, ubah harga, atur area dan slot, lalu perbarui halaman publik tanpa menyentuh kode.</p>
            <ul class="auth__list">
                <li><?= admin_icon('inbox', 16) ?>Pesanan masuk langsung dari formulir jadwal</li>
                <li><?= admin_icon('wallet', 16) ?>Harga dan layanan tersinkron dengan kalkulator</li>
                <li><?= admin_icon('trend', 16) ?>Riwayat status tercatat per pesanan</li>
            </ul>
        </div>

        <p class="auth__foot">© <?= date('Y') ?> GO Laundry · Delanggu, Klaten</p>

        <div class="auth__ticket">
            <span>GO Laundry</span>
            <strong>GO—0271</strong>
            Cuci · Kering · Lipat
        </div>
    </section>

    <section class="auth__panel">
        <div class="auth__card">
            <a class="auth__brand" href="../index.php">
                <span class="brand__plate">
                    <img class="brand__logo" src="../go-laundry.png" alt="GO Laundry" width="1024" height="1024">
                </span>
                <span class="brand__text"><span>Admin CMS</span></span>
            </a>

            <header class="auth__head">
                <p class="auth__eyebrow"><?= admin_icon('shield', 13) ?> Area terbatas</p>
                <h1>Masuk ke dashboard</h1>
                <p class="auth__lead">Pakai email admin yang terdaftar. Sesi berakhir saat Anda menekan Keluar.</p>
            </header>

            <div class="auth__body">
                <?php if ($flashes !== []): ?>
                    <?php foreach ($flashes as $flash): ?>
                        <div class="auth__alert" role="alert">
                            <?= admin_icon(($flash['type'] ?? '') === 'success' ? 'check' : 'alert', 17) ?>
                            <p><?= e($flash['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$pdo): ?>
                    <div class="hintbox">
                        <?= admin_icon('alert', 17) ?>
                        <span>Database belum tersambung. Jalankan MySQL di XAMPP, lalu muat ulang halaman ini.</span>
                    </div>
                <?php endif; ?>

                <form class="auth__form" method="post" action="login.php" novalidate>
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="next" value="<?= e($next) ?>">

                    <div class="field<?= $hasError ? ' has-error' : '' ?>">
                        <label for="email">Email admin</label>
                        <div class="field__wrap">
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="username"
                                   value="<?= e($email) ?>" placeholder="nama@golaundry.id"
                                   <?= $hasError ? 'aria-invalid="true"' : '' ?> required autofocus>
                        </div>
                    </div>

                    <div class="field<?= $hasError ? ' has-error' : '' ?>">
                        <label for="password">Kata sandi</label>
                        <div class="field__wrap">
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                   placeholder="Kata sandi akun Anda" <?= $hasError ? 'aria-invalid="true"' : '' ?> required>
                            <button class="field__reveal" type="button" data-reveal="password"
                                    aria-label="Tampilkan kata sandi" aria-pressed="false"
                                    aria-controls="password"><?= admin_icon('eye', 17) ?></button>
                        </div>
                    </div>

                    <button class="abtn abtn--primary abtn--block abtn--lg" type="submit" data-busy="Memeriksa...">
                        Masuk dashboard
                    </button>
                </form>

                <details class="auth__demo" data-demo-box>
                    <summary><?= admin_icon('sparkle', 14) ?> Akun demo untuk uji lokal</summary>
                    <div class="auth__demo-body">
                        <div class="auth__demo-row"><span>Email</span><code>admin@golaundry.id</code></div>
                        <div class="auth__demo-row"><span>Sandi</span><code>GoLaundry2026!</code></div>
                        <button class="abtn abtn--ghost abtn--sm abtn--block" type="button"
                                data-demo-fill data-email="admin@golaundry.id" data-password="GoLaundry2026!">
                            Isi otomatis
                        </button>
                    </div>
                </details>
            </div>

            <footer class="auth__cardfoot">
                <a href="../index.php">← Kembali ke situs</a>
                <p>Tidak diindeks mesin pencari.</p>
            </footer>
        </div>
    </section>
</main>
<script src="assets/admin.js" defer></script>
</body>
</html>
