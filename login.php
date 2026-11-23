<?php
declare(strict_types=1);

/* ============================================================================
   Masuk akun pelanggan. Arah desain: meja loket - papan slot jemput di kiri,
   lembar isian datar di kanan.
   ========================================================================= */

date_default_timezone_set('Asia/Jakarta');
$siteConfig = require __DIR__ . '/config/site.php';

require_once __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/customer.php';
require_once __DIR__ . '/includes/account-ui.php';

lk_session_boot();

$customer = customer_current();
if ($customer !== null) {
    header('Location: akun.php');
    exit;
}

$errors = [];
$values = ['email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'masuk') {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null;
    $values['email'] = strtolower(clean_input($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_valid($token)) {
        $errors['form'] = 'Sesi halaman sudah kedaluwarsa. Coba kirim ulang formulir ini.';
    } else {
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Masukkan email yang Anda daftarkan.';
        }
        if ($password === '') {
            $errors['password'] = 'Kata sandi belum diisi.';
        }
        if ($errors === [] && customer_login($values['email'], $password)) {
            header('Location: akun.php');
            exit;
        }
    }
}

$flashes = customer_flashes();
$firstInvalid = array_key_first($errors);
$csrf = csrf_token();

acct_auth_page_start(
    $siteConfig,
    'Masuk akun',
    'Masuk ke akun GO Laundry untuk memantau status cucian dan riwayat pesanan.',
    'masuk'
);
?>
<div class="auth-wrap auth-shell">
    <section class="auth-pane" aria-labelledby="auth-title">
        <h1 class="auth-pane__title" id="auth-title">Masuk ke akun</h1>
        <?php acct_auth_underline(); ?>
        <p class="auth-pane__lead">Pantau status cucian, jadwal jemput, dan riwayat pesanan dari satu halaman.</p>

        <div class="auth-card">
            <?php if (isset($errors['form'])): ?>
            <?= acct_alert('error', $errors['form']) ?>
            <?php endif; ?>
            <?php foreach ($flashes as $flash): ?>
            <?= acct_alert((string) $flash['type'], (string) $flash['message']) ?>
            <?php endforeach; ?>

            <form class="auth-form acct-form" method="post" action="login.php" novalidate data-auth-form>
                <input type="hidden" name="action" value="masuk">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <?php acct_field('email', 'Email', [
                    'type'  => 'email',
                    'value' => $values['email'],
                    'error' => $errors['email'] ?? '',
                    'attrs' => [
                        'autocomplete'   => 'email',
                        'inputmode'      => 'email',
                        'maxlength'      => 190,
                        'placeholder'    => 'nama@email.com',
                        'data-autofocus' => $firstInvalid === null || $firstInvalid === 'email' ? '1' : null,
                    ],
                ]); ?>

                <?php acct_field('password', 'Kata sandi', [
                    'type'   => 'password',
                    'error'  => $errors['password'] ?? '',
                    'reveal' => true,
                    'attrs'  => [
                        'autocomplete'   => 'current-password',
                        'maxlength'      => 200,
                        'data-autofocus' => $firstInvalid === 'password' ? '1' : null,
                    ],
                ]); ?>

                <button class="acct-submit" type="submit">Masuk</button>
            </form>

            <p class="auth-switch">Belum punya akun? <a href="register.php">Buat akun</a></p>
            <p class="auth-note">Lupa kata sandi? Hubungi outlet lewat WhatsApp dengan menyertakan email akun Anda.</p>
        </div>
    </section>

    <?php acct_auth_facts($siteConfig); ?>
</div>
<?php acct_auth_page_end($siteConfig); ?>
