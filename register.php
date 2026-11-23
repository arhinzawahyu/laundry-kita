<?php
declare(strict_types=1);

/* ============================================================================
   Pendaftaran akun pelanggan. Validasi di server (includes/customer.php),
   pemeriksaan langsung di peramban hanya sebagai bantuan.
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
$values = ['name' => '', 'email' => '', 'whatsapp' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($_GET['from'] ?? '') === 'jadwal') {
    $draft = $_SESSION['lkj_pickup_draft']['fields'] ?? null;
    if (is_array($draft)) {
        $values['name'] = clean_input($draft['nama'] ?? '');
        $values['whatsapp'] = clean_input($draft['whatsapp'] ?? '');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'daftar') {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null;
    if (!csrf_valid($token)) {
        $errors['form'] = 'Sesi halaman sudah kedaluwarsa. Coba kirim ulang formulir ini.';
    } else {
        $values['name'] = clean_input($_POST['name'] ?? '');
        $values['email'] = strtolower(clean_input($_POST['email'] ?? ''));
        $values['whatsapp'] = clean_input($_POST['whatsapp'] ?? '');
    }

    $input = [
        'name'             => $values['name'],
        'email'            => $values['email'],
        'whatsapp'         => $values['whatsapp'],
        'password'         => (string) ($_POST['password'] ?? ''),
        'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
    ];

    if ($errors === [] && customer_register($input, $errors) !== null) {
        header('Location: akun.php');
        exit;
    }
}

$flashes = customer_flashes();
$firstInvalid = array_key_first($errors);
$csrf = csrf_token();

acct_auth_page_start(
    $siteConfig,
    'Buat akun',
    'Daftar akun GO Laundry untuk memantau status cucian dan menyimpan riwayat pesanan.',
    'daftar'
);
?>
<div class="auth-wrap auth-shell">
    <section class="auth-pane" aria-labelledby="auth-title">
        <h1 class="auth-pane__title" id="auth-title">Buat akun</h1>
        <?php acct_auth_underline(); ?>
        <p class="auth-pane__lead">Pesanan dengan nomor WhatsApp ini masuk ke akun Anda, tanpa perlu mengetik kode pesanan.</p>

        <div class="auth-card">
            <?php if (isset($errors['form'])): ?>
            <?= acct_alert('error', $errors['form']) ?>
            <?php endif; ?>
            <?php foreach ($flashes as $flash): ?>
            <?= acct_alert((string) $flash['type'], (string) $flash['message']) ?>
            <?php endforeach; ?>

            <form class="auth-form acct-form" method="post" action="register.php" novalidate data-auth-form>
                <input type="hidden" name="action" value="daftar">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <?php acct_field('name', 'Nama lengkap', [
                    'value' => $values['name'],
                    'error' => $errors['name'] ?? '',
                    'attrs' => [
                        'autocomplete'   => 'name',
                        'maxlength'      => 60,
                        'placeholder'    => 'Contoh: Siti Rahayu',
                        'data-autofocus' => $firstInvalid === 'name' || $firstInvalid === null ? '1' : null,
                    ],
                ]); ?>

                <?php acct_field('whatsapp', 'Nomor WhatsApp', [
                    'type'  => 'tel',
                    'value' => $values['whatsapp'],
                    'error' => $errors['whatsapp'] ?? '',
                    'hint'  => 'Dipakai kurir untuk mengabari jadwal jemput.',
                    'attrs' => [
                        'autocomplete'   => 'tel',
                        'inputmode'      => 'tel',
                        'maxlength'      => 20,
                        'placeholder'    => '0812 3456 7890',
                        'data-autofocus' => $firstInvalid === 'whatsapp' ? '1' : null,
                    ],
                ]); ?>

                <?php acct_field('email', 'Email', [
                    'type'  => 'email',
                    'value' => $values['email'],
                    'error' => $errors['email'] ?? '',
                    'attrs' => [
                        'autocomplete'   => 'email',
                        'inputmode'      => 'email',
                        'maxlength'      => 190,
                        'placeholder'    => 'nama@email.com',
                        'data-autofocus' => $firstInvalid === 'email' ? '1' : null,
                    ],
                ]); ?>

                <?php acct_field('password', 'Kata sandi', [
                    'type'   => 'password',
                    'error'  => $errors['password'] ?? '',
                    'reveal' => true,
                    'attrs'  => [
                        'autocomplete'   => 'new-password',
                        'maxlength'      => 200,
                        'data-pw-checks' => '1',
                        'data-autofocus' => $firstInvalid === 'password' ? '1' : null,
                    ],
                ]); ?>

                <ul class="acct-checks" data-pwreq aria-label="Syarat kata sandi">
                    <li class="acct-checks__item" data-pw-check="length"><span class="acct-checks__box" aria-hidden="true"></span>Minimal 10 karakter</li>
                    <li class="acct-checks__item" data-pw-check="letter"><span class="acct-checks__box" aria-hidden="true"></span>Ada huruf</li>
                    <li class="acct-checks__item" data-pw-check="digit"><span class="acct-checks__box" aria-hidden="true"></span>Ada angka</li>
                </ul>

                <?php acct_field('password_confirm', 'Ulangi kata sandi', [
                    'type'   => 'password',
                    'error'  => $errors['password_confirm'] ?? '',
                    'reveal' => true,
                    'attrs'  => [
                        'autocomplete'   => 'new-password',
                        'maxlength'      => 200,
                        'data-pw-match'  => 'password',
                        'data-autofocus' => $firstInvalid === 'password_confirm' ? '1' : null,
                    ],
                ]); ?>

                <p class="acct-match" data-pw-hint aria-live="polite" hidden></p>

                <button class="acct-submit" type="submit">Buat akun</button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk ke akun</a></p>
            <p class="auth-note">Kami menyimpan nama, email, dan nomor WhatsApp untuk keperluan pesanan saja.</p>
        </div>
    </section>

    <?php acct_auth_facts($siteConfig); ?>
</div>
<?php acct_auth_page_end($siteConfig); ?>
