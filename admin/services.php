<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();

if ($pdo && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $action = admin_post('action');

    if ($action === 'save') {
        $rows = is_array($_POST['s'] ?? null) ? $_POST['s'] : [];
        $errors = [];
        $saved = 0;

        foreach ($rows as $id => $row) {
            $id = (int) $id;
            if ($id <= 0 || !is_array($row)) {
                continue;
            }
            $label = clean_input((string) ($row['label'] ?? ''));
            $unit = clean_input((string) ($row['unit'] ?? 'kg'));
            $price = (int) ($row['price'] ?? 0);
            $min = (float) ($row['min_qty'] ?? 0);
            $step = (float) ($row['step'] ?? 0);
            $hours = (int) ($row['duration_hours'] ?? 0);
            $durationLabel = clean_input((string) ($row['duration_label'] ?? ''));
            $blurb = clean_input((string) ($row['blurb'] ?? ''));
            $detail = clean_input((string) ($row['detail'] ?? ''));
            $active = isset($row['active']) ? 1 : 0;
            $sort = (int) ($row['sort'] ?? 0);

            if ($label === '' || text_len($label) > 60) {
                $errors[] = 'Nama layanan wajib diisi (maks. 60 karakter).';
                continue;
            }
            if ($price < 0 || $price > 10000000) {
                $errors[] = 'Harga ' . $label . ' tidak masuk akal.';
                continue;
            }
            if ($min < 0.5 || $step <= 0 || $hours <= 0) {
                $errors[] = 'Minimal, langkah, dan durasi ' . $label . ' harus lebih dari nol.';
                continue;
            }

            try {
                $statement = $pdo->prepare(
                    'UPDATE services SET label = ?, unit = ?, price = ?, min_qty = ?, step = ?,
                     duration_hours = ?, duration_label = ?, blurb = ?, detail = ?, active = ?, sort = ?
                     WHERE id = ?'
                );
                $statement->execute([
                    $label, $unit, $price, $min, $step, $hours, $durationLabel, $blurb, $detail, $active, $sort, $id,
                ]);
                $saved++;
            } catch (Throwable $error) {
                $errors[] = 'Gagal menyimpan ' . $label . ': ' . $error->getMessage();
            }
        }

        if ($errors !== []) {
            admin_flash('error', implode(' ', array_slice($errors, 0, 3)));
        }
        if ($saved > 0) {
            admin_audit('service.save', $saved . ' layanan diperbarui');
            admin_flash('success', $saved . ' layanan disimpan. Kalkulator publik langsung memakai harga baru.');
        }
        admin_redirect('services.php');
    }

    if ($action === 'add') {
        $key = admin_slug(admin_post('key'));
        $label = admin_post('label');
        $unit = admin_post('unit', 'kg');
        $price = (int) admin_post('price', '0');

        if ($key === '' || !preg_match('/^[a-z0-9-]{3,40}$/', $key)) {
            admin_flash('error', 'Kunci layanan wajib 3–40 karakter huruf kecil, angka, atau tanda hubung.');
        } elseif ($label === '') {
            admin_flash('error', 'Nama layanan belum diisi.');
        } else {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO services (service_key, label, unit, price, min_qty, step, duration_hours, duration_label, blurb, detail, active, sort)
                     VALUES (?, ?, ?, ?, 1, 1, 48, ?, ?, ?, 1, 99)'
                );
                $statement->execute([
                    $key, $label, $unit, max(0, $price), '2 hari',
                    'Layanan baru, lengkapi deskripsi di bawah.', 'Atur rincian layanan ini.',
                ]);
                admin_audit('service.save', 'Layanan baru: ' . $label);
                admin_flash('success', 'Layanan ' . $label . ' ditambahkan. Lengkapi detailnya lalu simpan.');
            } catch (Throwable $error) {
                admin_flash('error', 'Layanan gagal ditambahkan: ' . $error->getMessage());
            }
        }
        admin_redirect('services.php');
    }
}

$services = [];
if ($pdo) {
    try {
        $services = $pdo->query('SELECT * FROM services ORDER BY sort ASC, id ASC')->fetchAll();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

admin_head('Layanan & Harga', 'services');
admin_page_head(
    'Layanan & Harga',
    'Perubahan di sini langsung dipakai kalkulator dan tabel harga di halaman utama.',
    '<a class="abtn abtn--ghost" href="../index.php#kalkulator" target="_blank" rel="noopener">' . admin_icon('external', 15) . 'Lihat kalkulator</a>'
);

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
?>

<form method="post" action="services.php">
    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
    <input type="hidden" name="action" value="save">

    <?php foreach ($services as $index => $service): ?>
        <section class="card" style="margin-bottom:14px">
            <div class="card__head" style="flex-wrap:wrap">
                <div style="display:flex;align-items:center;gap:10px;min-width:0">
                    <span class="stat__icon" style="width:32px;height:32px;border-radius:9px"><?= admin_icon('services', 16) ?></span>
                    <div>
                        <h2 style="display:flex;align-items:center;gap:7px">
                            <?= e($service['label']) ?>
                            <span class="code" style="font-size:10px"><?= e($service['service_key']) ?></span>
                        </h2>
                        <p><?= $service['active'] ? 'Tampil di halaman publik' : 'Disembunyikan dari halaman publik' ?></p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:14px">
                    <label class="switch">
                        <input type="checkbox" name="s[<?= (int) $service['id'] ?>][active]" value="1"<?= $service['active'] ? ' checked' : '' ?>>
                        <span class="switch__track" aria-hidden="true"></span>
                        <span style="font-size:11.5px;color:var(--muted)">Aktif</span>
                    </label>
                    <input type="number" name="s[<?= (int) $service['id'] ?>][sort]" value="<?= (int) $service['sort'] ?>"
                           aria-label="Urutan <?= e($service['label']) ?>" title="Urutan tampil"
                           style="width:56px;height:32px;border:1.5px solid var(--line-2);border-radius:8px;padding:0 8px;font-size:12px">
                </div>
            </div>
            <div class="card__body">
                <div class="formgrid">
                    <div class="field" style="grid-column:span 1">
                        <label>Nama layanan</label>
                        <input name="s[<?= (int) $service['id'] ?>][label]" value="<?= e($service['label']) ?>" maxlength="60" required>
                    </div>
                    <div class="field">
                        <label>Harga per <span id="unit-<?= (int) $service['id'] ?>"><?= e($service['unit']) ?></span></label>
                        <input name="s[<?= (int) $service['id'] ?>][price]" type="number" min="0" step="500" value="<?= (int) $service['price'] ?>" required>
                    </div>
                    <div class="field">
                        <label>Satuan</label>
                        <input name="s[<?= (int) $service['id'] ?>][unit]" value="<?= e($service['unit']) ?>" maxlength="20">
                    </div>
                    <div class="field">
                        <label>Minimal jumlah</label>
                        <input name="s[<?= (int) $service['id'] ?>][min_qty]" type="number" min="0.5" step="0.5" value="<?= e((string) $service['min_qty']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Langkah</label>
                        <input name="s[<?= (int) $service['id'] ?>][step]" type="number" min="0.5" step="0.5" value="<?= e((string) $service['step']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Durasi (jam)</label>
                        <input name="s[<?= (int) $service['id'] ?>][duration_hours]" type="number" min="1" max="720" value="<?= (int) $service['duration_hours'] ?>" required>
                    </div>
                    <div class="field">
                        <label>Label durasi</label>
                        <input name="s[<?= (int) $service['id'] ?>][duration_label]" value="<?= e($service['duration_label']) ?>" maxlength="40">
                    </div>
                    <div class="field field--full">
                        <label>Ringkasan <span>tampil di kartu layanan</span></label>
                        <input name="s[<?= (int) $service['id'] ?>][blurb]" value="<?= e($service['blurb']) ?>" maxlength="190">
                    </div>
                    <div class="field field--full">
                        <label>Detail <span>tampil di tabel / catatan</span></label>
                        <input name="s[<?= (int) $service['id'] ?>][detail]" value="<?= e($service['detail']) ?>" maxlength="255">
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="formactions" style="margin-bottom:22px">
        <span class="formactions__note">Urutan kecil tampil lebih dulu. Nonaktifkan layanan untuk menyembunyikannya tanpa menghapus harga.</span>
        <button class="abtn abtn--primary" type="submit" data-busy="Menyimpan..."><?= admin_icon('check', 15) ?>Simpan semua layanan</button>
    </div>
</form>

<section class="card">
    <div class="card__head">
        <div>
            <h2>Tambah layanan</h2>
            <p>Kunci layanan dipakai di URL dan data pesanan, jadi pilih sekali dan jangan diubah.</p>
        </div>
    </div>
    <div class="card__body">
        <form method="post" action="services.php">
            <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
            <input type="hidden" name="action" value="add">
            <div class="formgrid">
                <div class="field">
                    <label for="new-key">Kunci</label>
                    <input id="new-key" name="key" placeholder="mis. setrika" pattern="[a-z0-9-]{3,40}" required>
                    <p class="field__hint">Huruf kecil, angka, tanda hubung.</p>
                </div>
                <div class="field">
                    <label for="new-label">Nama layanan</label>
                    <input id="new-label" name="label" placeholder="mis. Setrika Saja" maxlength="60" required>
                </div>
                <div class="field">
                    <label for="new-unit">Satuan</label>
                    <input id="new-unit" name="unit" value="kg" maxlength="20">
                </div>
                <div class="field">
                    <label for="new-price">Harga</label>
                    <input id="new-price" name="price" type="number" min="0" step="500" value="7000">
                </div>
            </div>
            <div class="formactions">
                <span class="formactions__note">Setelah ditambahkan, atur minimal, durasi, dan deskripsi pada kartu di atas.</span>
                <button class="abtn abtn--ghost" type="submit" data-busy="Menambah..."><?= admin_icon('plus', 15) ?>Tambah layanan</button>
            </div>
        </form>
    </div>
</section>

<?php admin_foot(); ?>
