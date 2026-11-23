<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = lk_db();
$adminCount = null;
if ($pdo) {
    try {
        $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}
if ($adminCount !== null && $adminCount > 0) {
    admin_redirect('login.php');
}

$errors = [];
$values = ['name' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $pdo) {
    admin_check_csrf();
    $values['name'] = admin_post('name');
    $values['email'] = strtolower(admin_post('email'));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if (text_len($values['name']) < 2 || text_len($values['name']) > 60) {
        $errors['name'] = 'Tulis nama pemilik, 2 sampai 60 karakter.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Masukkan email yang valid.';
    }
    if (strlen($password) < 10) {
        $errors['password'] = 'Kata sandi minimal 10 karakter.';
    } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Campur huruf dan angka agar lebih aman.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Konfirmasi kata sandi belum sama.';
    }

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $statement->execute([
                $values['name'],
                $values['email'],
                password_hash($password, PASSWORD_DEFAULT),
                'owner',
            ]);
            session_regenerate_id(true);
            $_SESSION['lk_admin_id'] = (int) $pdo->lastInsertId();
            admin_audit('setup', 'Akun pemilik pertama dibuat');
            admin_flash('success', 'Dashboard siap. Selamat datang, ' . $values['name'] . '.');
            admin_redirect('index.php');
        } catch (Throwable $error) {
            $errors['form'] = 'Akun gagal dibuat: ' . $error->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Buat akun pemilik · GO Laundry CMS</title>
<meta name="theme-color" content="#2E2149">
<link rel="icon" type="image/png" href="../go-laundry.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/tokens.css?v=<?= @filemtime(__DIR__ . '/assets/tokens.css') ?: 1 ?>">
<link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(__DIR__ . '/assets/admin.css') ?: 1 ?>">
</head>
<body>
<main class="auth auth--single">
    <section class="auth__panel">
        <div class="auth__card setup__box">
            <a class="auth__brand" href="../index.php">
                <span class="brand__plate">
                    <img class="brand__logo" src="../go-laundry.png" alt="GO Laundry" width="1024" height="1024">
                </span>
                <span class="brand__text"><span>Admin CMS</span></span>
            </a>

            <header class="auth__head">
                <p class="auth__eyebrow"><?= admin_icon('sparkle', 13) ?> Instalasi · sekali jalan</p>
                <h1><?= $pdo ? 'Buat akun pemilik' : 'Database belum tersambung' ?></h1>
                <p class="auth__lead">
                    <?php if ($pdo): ?>
                        Halaman ini hanya muncul saat belum ada admin. Setelah akun dibuat, instalasi tertutup otomatis.
                    <?php else: ?>
                        Instalasi butuh MySQL. Nyalakan MySQL di XAMPP, lalu muat ulang halaman ini.
                    <?php endif; ?>
                </p>
            </header>

            <div class="auth__body">
                <?php if (!$pdo): ?>
                    <div class="hintbox">
                        <?= admin_icon('alert', 17) ?>
                        <span>
                            Kredensial ada di <code class="code">config/database.php</code> — bawaan XAMPP:
                            pengguna <strong>root</strong> tanpa sandi.
                        </span>
                    </div>
                    <?php if (lk_db_error() !== ''): ?>
                        <div class="auth__alert" role="alert">
                            <?= admin_icon('alert', 17) ?>
                            <p><?= e(lk_db_error()) ?></p>
                        </div>
                    <?php endif; ?>
                    <a class="abtn abtn--ghost abtn--block" href="../index.php">← Kembali ke situs</a>
                <?php else: ?>
                    <?php if (isset($errors['form'])): ?>
                        <div class="auth__alert" role="alert">
                            <?= admin_icon('alert', 17) ?>
                            <p><?= e($errors['form']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form class="auth__form" method="post" action="setup.php" novalidate>
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">

                        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
                            <label for="name">Nama pemilik</label>
                            <div class="field__wrap">
                                <input id="name" name="name" value="<?= e($values['name']) ?>" autocomplete="name"
                                       placeholder="Nama Anda" <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?> required autofocus>
                            </div>
                            <?= admin_field_error($errors, 'name') ?>
                        </div>

                        <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                            <label for="email">Email admin</label>
                            <div class="field__wrap">
                                <input id="email" name="email" type="email" inputmode="email" autocomplete="username"
                                       value="<?= e($values['email']) ?>" placeholder="nama@laundrykita.id"
                                       <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?> required>
                            </div>
                            <?= admin_field_error($errors, 'email') ?>
                        </div>

                        <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
                            <label for="password">Kata sandi <span>dipakai untuk masuk dashboard</span></label>
                            <div class="field__wrap">
                                <input id="password" name="password" type="password" autocomplete="new-password"
                                       placeholder="Buat kata sandi"
                                       data-pw-source <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?> required>
                                <button class="field__reveal" type="button" data-reveal="password"
                                        aria-label="Tampilkan kata sandi" aria-pressed="false"
                                        aria-controls="password"><?= admin_icon('eye', 17) ?></button>
                            </div>
                            <?= admin_field_error($errors, 'password') ?>
                            <ul class="pwreq" data-pw-req aria-live="polite">
                                <li data-req="length">Minimal 10 karakter</li>
                                <li data-req="letter">Ada huruf</li>
                                <li data-req="digit">Ada angka</li>
                            </ul>
                        </div>

                        <div class="field<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
                            <label for="password_confirm">Ulangi kata sandi</label>
                            <div class="field__wrap">
                                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password"
                                       placeholder="Ketik ulang kata sandi" data-pw-confirm
                                       <?= isset($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?> required>
                                <button class="field__reveal" type="button" data-reveal="password_confirm"
                                        aria-label="Tampilkan kata sandi" aria-pressed="false"
                                        aria-controls="password_confirm"><?= admin_icon('eye', 17) ?></button>
                            </div>
                            <?= admin_field_error($errors, 'password_confirm') ?>
                            <p class="field__hint" data-pw-match hidden>Kedua kata sandi sudah sama.</p>
                        </div>

                        <button class="abtn abtn--primary abtn--block abtn--lg" type="submit" data-busy="Menyiapkan...">
                            Buat akun &amp; buka dashboard
                        </button>
                    </form>

                    <p class="auth__note">
                        Simpan kata sandi di pengelola sandi. Tidak ada kata sandi bawaan yang dikirim ke browser.
                    </p>
                <?php endif; ?>
            </div>

            <footer class="auth__cardfoot">
                <a href="../index.php">← Kembali ke situs</a>
                <p>Hanya untuk pemasangan pertama.</p>
            </footer>
        </div>
    </section>
</main>
<script src="assets/admin.js" defer></script>
</body>
</html>
