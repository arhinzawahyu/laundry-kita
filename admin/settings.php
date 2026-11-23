<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();

$business = is_array(lk_setting('business')) ? lk_setting('business') : [];
$meta = is_array(lk_setting('meta')) ? lk_setting('meta') : [];
$auth = is_array(lk_setting('auth')) ? lk_setting('auth') : [];
$legal = is_array(lk_setting('legal')) ? lk_setting('legal') : [];
$sequence = is_array(lk_setting('sequence')) ? lk_setting('sequence') : [];
$stats = is_array(lk_setting('stats')) ? lk_setting('stats') : [];
$errors = [];

if ($pdo && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();

    $business = [
        'name'      => admin_post('name'),
        'tagline'   => admin_post('tagline'),
        'region'    => admin_post('region'),
        'address'   => admin_post('address'),
        'whatsapp'  => admin_post('whatsapp'),
        'email'     => admin_post('email'),
        'instagram' => admin_post('instagram'),
        'maps_url'  => admin_post('maps_url') !== '' ? admin_post('maps_url') : null,
    ];
    $meta = [
        'title'       => admin_post('meta_title'),
        'description' => admin_post('meta_description'),
    ];
    $auth = [
        'login_url'     => admin_post('login_url', 'login.php'),
        'register_url'  => admin_post('register_url', 'register.php'),
        'backend_ready' => admin_post('backend_ready') === '1' ? true : (admin_post('backend_ready') === '0' ? false : null),
    ];
    $legal = [
        'privacy' => admin_post('privacy'),
        'terms'   => admin_post('terms'),
    ];
    $sequence = [
        'folder' => admin_post('sequence_folder', 'sequence'),
        'limit'  => max(1, min(500, (int) admin_post('sequence_limit', '240'))),
    ];
    $stats = [];
    $statLabels = is_array($_POST['stat_label'] ?? null) ? $_POST['stat_label'] : [];
    $statValues = is_array($_POST['stat_value'] ?? null) ? $_POST['stat_value'] : [];
    foreach ($statLabels as $index => $label) {
        $label = clean_input((string) $label);
        $value = clean_input((string) ($statValues[$index] ?? ''));
        if ($label !== '' && $value !== '') {
            $stats[] = ['label' => $label, 'value' => $value];
        }
    }

    if ($business['name'] === '') {
        $errors['name'] = 'Nama bisnis wajib diisi.';
    }
    if (!is_placeholder($business['email']) && !filter_var($business['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email outlet belum valid.';
    }
    if (!is_placeholder($business['whatsapp']) && !preg_match('/^[0-9+\-\s().]{9,20}$/', $business['whatsapp'])) {
        $errors['whatsapp'] = 'Nomor WhatsApp hanya boleh angka, spasi, +, - dan titik.';
    }
    if ($meta['title'] === '' || $meta['description'] === '') {
        $errors['meta'] = 'Judul dan deskripsi meta wajib diisi.';
    }
    if (!preg_match('#^[A-Za-z0-9_\-/]{1,80}$#', $sequence['folder'])) {
        $errors['sequence'] = 'Folder sequence hanya boleh huruf, angka, garis miring, atau tanda hubung.';
    }

    if ($errors === []) {
        lk_setting_save('business', $business);
        lk_setting_save('meta', $meta);
        lk_setting_save('auth', $auth);
        lk_setting_save('legal', $legal);
        lk_setting_save('sequence', $sequence);
        lk_setting_save('stats', $stats);
        admin_audit('settings.save', 'Pengaturan situs diperbarui');
        admin_flash('success', 'Pengaturan disimpan. Halaman publik membaca data ini pada request berikutnya.');
        admin_redirect('settings.php');
    }
    admin_flash('error', 'Ada kolom yang perlu diperbaiki. Periksa tanda merah di bawah.');
}

admin_head('Pengaturan', 'settings');
admin_page_head('Pengaturan', 'Identitas bisnis, meta halaman, integrasi akun, dan legal — semua dipakai halaman publik.', '<a class="abtn abtn--ghost" href="../index.php" target="_blank" rel="noopener">' . admin_icon('external', 15) . 'Buka situs</a>');

if (!$pdo) {
    ?>
    <div class="card"><div class="card__body"><div class="empty">
        <span class="empty__icon"><?= admin_icon('alert', 22) ?></span>
        <strong>Database belum tersambung</strong>
        <p>Nyalakan MySQL lalu muat ulang halaman ini.</p>
    </div></div></div>
    <?php
    admin_foot();
    exit;
}

$placeholders = [];
foreach (['address' => 'Alamat outlet', 'whatsapp' => 'Nomor WhatsApp', 'email' => 'Email outlet', 'instagram' => 'Instagram'] as $key => $label) {
    if (is_placeholder((string) ($business[$key] ?? ''))) {
        $placeholders[] = $label;
    }
}
if (is_placeholder((string) ($legal['privacy'] ?? '')) || is_placeholder((string) ($legal['terms'] ?? ''))) {
    $placeholders[] = 'URL legal';
}
?>

<?php if ($errors !== []): ?>
    <div class="toast toast--error" style="margin-bottom:16px">
        <span class="toast__icon"><?= admin_icon('alert', 17) ?></span>
        <p><?= e(implode(' ', $errors)) ?></p>
    </div>
<?php endif; ?>

<?php if ($placeholders !== []): ?>
    <div class="hintbox" style="margin-bottom:16px">
        <?= admin_icon('alert', 17) ?>
        <span><strong>Masih ada placeholder:</strong> <?= e(implode(', ', $placeholders)) ?>. Selama kosong, CTA terkait akan menampilkan status belum tersedia secara jujur.</span>
    </div>
<?php endif; ?>

<form method="post" action="settings.php">
    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">

    <section class="card" style="margin-bottom:16px">
        <div class="card__head"><div><h2>Identitas bisnis</h2><p>Dipakai di header, footer, tombol WhatsApp, dan data terstruktur.</p></div></div>
        <div class="card__body">
            <div class="formgrid">
                <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
                    <label for="name">Nama bisnis</label>
                    <input id="name" name="name" value="<?= e((string) ($business['name'] ?? '')) ?>" required>
                    <?= admin_field_error($errors, 'name') ?>
                </div>
                <div class="field">
                    <label for="tagline">Tagline</label>
                    <input id="tagline" name="tagline" value="<?= e((string) ($business['tagline'] ?? '')) ?>" maxlength="160">
                </div>
                <div class="field field--full">
                    <label for="region">Wilayah layanan</label>
                    <input id="region" name="region" value="<?= e((string) ($business['region'] ?? '')) ?>" maxlength="160">
                </div>
                <div class="field field--full">
                    <label for="address">Alamat outlet</label>
                    <input id="address" name="address" value="<?= e((string) ($business['address'] ?? '')) ?>" maxlength="160">
                    <p class="field__hint">Ganti <code class="code">[ALAMAT_OUTLET]</code> dengan alamat resmi.</p>
                </div>
                <div class="field<?= isset($errors['whatsapp']) ? ' has-error' : '' ?>">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="<?= e((string) ($business['whatsapp'] ?? '')) ?>" maxlength="24">
                    <?= admin_field_error($errors, 'whatsapp') ?>
                </div>
                <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="text" value="<?= e((string) ($business['email'] ?? '')) ?>" maxlength="120">
                    <?= admin_field_error($errors, 'email') ?>
                </div>
                <div class="field">
                    <label for="instagram">Instagram</label>
                    <input id="instagram" name="instagram" value="<?= e((string) ($business['instagram'] ?? '')) ?>" maxlength="80">
                </div>
                <div class="field">
                    <label for="maps_url">URL peta <span>opsional</span></label>
                    <input id="maps_url" name="maps_url" type="url" value="<?= e((string) ($business['maps_url'] ?? '')) ?>" placeholder="https://maps.google.com/...">
                </div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-bottom:16px">
        <div class="card__head"><div><h2>Meta halaman</h2><p>Judul tab browser dan deskripsi hasil pencarian.</p></div></div>
        <div class="card__body">
            <div class="formgrid">
                <div class="field field--full">
                    <label for="meta_title">Judul meta <span>±60 karakter</span></label>
                    <input id="meta_title" name="meta_title" value="<?= e((string) ($meta['title'] ?? '')) ?>" maxlength="120">
                </div>
                <div class="field field--full">
                    <label for="meta_description">Deskripsi meta <span>±155 karakter</span></label>
                    <textarea id="meta_description" name="meta_description" maxlength="255"><?= e((string) ($meta['description'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-bottom:16px">
        <div class="card__head"><div><h2>Akun &amp; legal</h2><p>Integrasi login pelanggan dan tautan kebijakan.</p></div></div>
        <div class="card__body">
            <div class="formgrid">
                <div class="field">
                    <label for="login_url">URL login</label>
                    <input id="login_url" name="login_url" value="<?= e((string) ($auth['login_url'] ?? 'login.php')) ?>">
                </div>
                <div class="field">
                    <label for="register_url">URL daftar</label>
                    <input id="register_url" name="register_url" value="<?= e((string) ($auth['register_url'] ?? 'register.php')) ?>">
                </div>
                <div class="field">
                    <label for="backend_ready">Status backend akun</label>
                    <select id="backend_ready" name="backend_ready">
                        <option value=""<?= ($auth['backend_ready'] ?? null) === null ? ' selected' : '' ?>>Otomatis (deteksi file)</option>
                        <option value="1"<?= ($auth['backend_ready'] ?? null) === true ? ' selected' : '' ?>>Aktif — tombol langsung menuju login</option>
                        <option value="0"<?= ($auth['backend_ready'] ?? null) === false ? ' selected' : '' ?>>Belum tersedia</option>
                    </select>
                </div>
                <div class="field">
                    <label for="privacy">URL kebijakan privasi</label>
                    <input id="privacy" name="privacy" value="<?= e((string) ($legal['privacy'] ?? '')) ?>" maxlength="190">
                </div>
                <div class="field">
                    <label for="terms">URL syarat layanan</label>
                    <input id="terms" name="terms" value="<?= e((string) ($legal['terms'] ?? '')) ?>" maxlength="190">
                </div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-bottom:16px">
        <div class="card__head"><div><h2>Hero sequence</h2><p>Folder frame gambar untuk animasi scroll di hero.</p></div></div>
        <div class="card__body">
            <div class="formgrid">
                <div class="field<?= isset($errors['sequence']) ? ' has-error' : '' ?>">
                    <label for="sequence_folder">Folder</label>
                    <input id="sequence_folder" name="sequence_folder" value="<?= e((string) ($sequence['folder'] ?? 'sequence')) ?>">
                    <p class="field__hint">Relatif terhadap root proyek. Jangan ubah saat situs live tanpa menyalin gambarnya.</p>
                    <?= admin_field_error($errors, 'sequence') ?>
                </div>
                <div class="field">
                    <label for="sequence_limit">Batas jumlah frame</label>
                    <input id="sequence_limit" name="sequence_limit" type="number" min="1" max="500" value="<?= (int) ($sequence['limit'] ?? 240) ?>">
                    <p class="field__hint">Frame lebih dari batas diabaikan.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-bottom:16px">
        <div class="card__head"><div><h2>Statistik ringkas</h2><p>Tiga angka di bagian bukti sosial. Jangan isi angka karangan.</p></div></div>
        <div class="card__body">
            <div class="formgrid">
                <?php for ($i = 0; $i < max(3, count($stats)); $i++): ?>
                    <div class="field">
                        <label>Label <?= $i + 1 ?></label>
                        <input name="stat_label[]" value="<?= e((string) ($stats[$i]['label'] ?? '')) ?>" placeholder="mis. Rating rata-rata">
                    </div>
                    <div class="field">
                        <label>Nilai</label>
                        <input name="stat_value[]" value="<?= e((string) ($stats[$i]['value'] ?? '')) ?>" placeholder="mis. 4,9/5 dari 120 ulasan">
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <div class="formactions">
        <span class="formactions__note">Perubahan diterapkan ke halaman publik pada request berikutnya. Tidak perlu deploy ulang.</span>
        <button class="abtn abtn--primary" type="submit" data-busy="Menyimpan..."><?= admin_icon('check', 15) ?>Simpan pengaturan</button>
    </div>
</form>

<?php admin_foot(); ?>
