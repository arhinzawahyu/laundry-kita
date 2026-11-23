<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();

$tabs = [
    'promo'     => ['label' => 'Promo',          'title' => 'Promo & Penawaran', 'sub' => 'Atur diskon pesanan pertama dan voucher member yang tampil di halaman utama.'],
    'area'      => ['label' => 'Area & Slot',    'title' => 'Area & Slot Jemput', 'sub' => 'Daftar desa yang dilayani dan pilihan jam penjemputan di formulir.'],
    'jam'       => ['label' => 'Jam Operasional', 'title' => 'Jam Operasional',   'sub' => 'Jam outlet dan slot kurir yang ditampilkan di bagian jangkauan.'],
    'testimoni' => ['label' => 'Testimoni',      'title' => 'Testimoni',          'sub' => 'Kutipan pelanggan yang tampil di bagian bukti sosial.'],
    'jaminan'   => ['label' => 'Jaminan',        'title' => 'Jaminan Layanan',    'sub' => 'Tiga komitmen yang tampil di sebelah statistik.'],
];
$tab = isset($_GET['tab']) && is_string($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'promo';

if ($pdo && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $action = admin_post('action');

    try {
        if ($action === 'promo_save') {
            $promo = [
                'first_order_percent' => max(0, min(100, (int) admin_post('first_order_percent', '0'))),
                'free_pickup_area'    => admin_post('free_pickup_area'),
                'member_voucher'      => admin_post('member_voucher'),
                'note'                => admin_post('note'),
            ];
            lk_setting_save('promo', $promo);
            admin_audit('content.save', 'Promo diperbarui');
            admin_flash('success', 'Promo disimpan dan langsung tampil di halaman utama.');
            admin_redirect('content.php?tab=promo');
        }

        if ($action === 'area_save') {
            $rows = is_array($_POST['areas'] ?? null) ? $_POST['areas'] : [];
            $statement = $pdo->prepare('UPDATE areas SET name = ?, sort = ?, active = ? WHERE id = ?');
            foreach ($rows as $id => $row) {
                $name = clean_input((string) ($row['name'] ?? ''));
                if ($name === '' || (int) $id <= 0) {
                    continue;
                }
                $statement->execute([$name, (int) ($row['sort'] ?? 0), isset($row['active']) ? 1 : 0, (int) $id]);
            }
            admin_audit('content.save', 'Area layanan diperbarui');
            admin_flash('success', 'Area layanan disimpan. Formulir publik memakai daftar terbaru.');
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'area_add') {
            $name = admin_post('name');
            if ($name === '') {
                admin_flash('error', 'Nama area belum diisi.');
            } else {
                $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM areas')->fetchColumn();
                $pdo->prepare('INSERT IGNORE INTO areas (name, sort) VALUES (?, ?)')->execute([$name, $sort]);
                admin_audit('content.save', 'Area ditambah: ' . $name);
                admin_flash('success', 'Area ' . $name . ' ditambahkan.');
            }
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'area_delete') {
            $id = (int) admin_post('id', '0');
            $pdo->prepare('DELETE FROM areas WHERE id = ?')->execute([$id]);
            admin_audit('content.save', 'Area dihapus #' . $id);
            admin_flash('success', 'Area dihapus.');
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'slot_save') {
            $rows = is_array($_POST['slots'] ?? null) ? $_POST['slots'] : [];
            $statement = $pdo->prepare('UPDATE slots SET label = ?, sort = ?, active = ? WHERE id = ?');
            foreach ($rows as $id => $row) {
                $label = clean_input((string) ($row['label'] ?? ''));
                if ($label === '' || (int) $id <= 0) {
                    continue;
                }
                $statement->execute([$label, (int) ($row['sort'] ?? 0), isset($row['active']) ? 1 : 0, (int) $id]);
            }
            admin_audit('content.save', 'Slot jemput diperbarui');
            admin_flash('success', 'Slot jemput disimpan.');
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'slot_add') {
            $label = admin_post('label');
            if ($label === '') {
                admin_flash('error', 'Label slot belum diisi.');
            } else {
                $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM slots')->fetchColumn();
                $pdo->prepare('INSERT IGNORE INTO slots (label, sort) VALUES (?, ?)')->execute([$label, $sort]);
                admin_audit('content.save', 'Slot ditambah: ' . $label);
                admin_flash('success', 'Slot ' . $label . ' ditambahkan.');
            }
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'slot_delete') {
            $id = (int) admin_post('id', '0');
            $pdo->prepare('DELETE FROM slots WHERE id = ?')->execute([$id]);
            admin_audit('content.save', 'Slot dihapus #' . $id);
            admin_flash('success', 'Slot dihapus.');
            admin_redirect('content.php?tab=area');
        }

        if ($action === 'hours_save') {
            $rows = is_array($_POST['hours'] ?? null) ? $_POST['hours'] : [];
            $statement = $pdo->prepare('UPDATE hours SET day = ?, time_label = ?, sort = ? WHERE id = ?');
            foreach ($rows as $id => $row) {
                $day = clean_input((string) ($row['day'] ?? ''));
                $time = clean_input((string) ($row['time'] ?? ''));
                if ($day === '' || $time === '' || (int) $id <= 0) {
                    continue;
                }
                $statement->execute([$day, $time, (int) ($row['sort'] ?? 0), (int) $id]);
            }
            admin_audit('content.save', 'Jam operasional diperbarui');
            admin_flash('success', 'Jam operasional disimpan.');
            admin_redirect('content.php?tab=jam');
        }

        if ($action === 'hours_add') {
            $scope = admin_post('scope') === 'courier' ? 'courier' : 'outlet';
            $day = admin_post('day');
            $time = admin_post('time');
            if ($day === '' || $time === '') {
                admin_flash('error', 'Hari dan jam wajib diisi.');
            } else {
                $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM hours WHERE scope = ' . $pdo->quote($scope))->fetchColumn();
                $pdo->prepare('INSERT INTO hours (scope, day, time_label, sort) VALUES (?, ?, ?, ?)')
                    ->execute([$scope, $day, $time, $sort]);
                admin_flash('success', 'Baris jam ditambahkan.');
            }
            admin_redirect('content.php?tab=jam');
        }

        if ($action === 'hours_delete') {
            $id = (int) admin_post('id', '0');
            $pdo->prepare('DELETE FROM hours WHERE id = ?')->execute([$id]);
            admin_flash('success', 'Baris jam dihapus.');
            admin_redirect('content.php?tab=jam');
        }

        if ($action === 'testimonial_save') {
            $rows = is_array($_POST['testimonials'] ?? null) ? $_POST['testimonials'] : [];
            $statement = $pdo->prepare(
                'UPDATE testimonials SET name = ?, service = ?, quote = ?, rating = ?, active = ?, sort = ? WHERE id = ?'
            );
            foreach ($rows as $id => $row) {
                $name = clean_input((string) ($row['name'] ?? ''));
                $quote = clean_input((string) ($row['quote'] ?? ''));
                $rating = (int) ($row['rating'] ?? 0);
                if ((int) $id <= 0 || $name === '' || $quote === '') {
                    continue;
                }
                $statement->execute([
                    $name,
                    clean_input((string) ($row['service'] ?? '')),
                    $quote,
                    $rating >= 1 && $rating <= 5 ? $rating : null,
                    isset($row['active']) ? 1 : 0,
                    (int) ($row['sort'] ?? 0),
                    (int) $id,
                ]);
            }
            admin_audit('content.save', 'Testimoni diperbarui');
            admin_flash('success', 'Testimoni disimpan.');
            admin_redirect('content.php?tab=testimoni');
        }

        if ($action === 'testimonial_add') {
            $name = admin_post('name');
            $quote = admin_post('quote');
            $rating = (int) admin_post('rating', '0');
            if ($name === '' || $quote === '') {
                admin_flash('error', 'Nama dan kutipan wajib diisi.');
            } else {
                $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM testimonials')->fetchColumn();
                $pdo->prepare('INSERT INTO testimonials (name, service, quote, rating, active, sort) VALUES (?, ?, ?, ?, 1, ?)')
                    ->execute([$name, admin_post('service'), $quote, $rating >= 1 && $rating <= 5 ? $rating : null, $sort]);
                admin_audit('content.save', 'Testimoni ditambah: ' . $name);
                admin_flash('success', 'Testimoni ditambahkan.');
            }
            admin_redirect('content.php?tab=testimoni');
        }

        if ($action === 'testimonial_delete') {
            $id = (int) admin_post('id', '0');
            $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
            admin_flash('success', 'Testimoni dihapus.');
            admin_redirect('content.php?tab=testimoni');
        }

        if ($action === 'guarantee_save') {
            $rows = is_array($_POST['guarantees'] ?? null) ? $_POST['guarantees'] : [];
            $statement = $pdo->prepare('UPDATE guarantees SET title = ?, body = ?, sort = ? WHERE id = ?');
            foreach ($rows as $id => $row) {
                $title = clean_input((string) ($row['title'] ?? ''));
                $body = clean_input((string) ($row['body'] ?? ''));
                if ((int) $id <= 0 || $title === '' || $body === '') {
                    continue;
                }
                $statement->execute([$title, $body, (int) ($row['sort'] ?? 0), (int) $id]);
            }
            admin_audit('content.save', 'Jaminan diperbarui');
            admin_flash('success', 'Jaminan layanan disimpan.');
            admin_redirect('content.php?tab=jaminan');
        }

        if ($action === 'guarantee_add') {
            $title = admin_post('title');
            $body = admin_post('body');
            if ($title === '' || $body === '') {
                admin_flash('error', 'Judul dan isi jaminan wajib diisi.');
            } else {
                $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM guarantees')->fetchColumn();
                $pdo->prepare('INSERT INTO guarantees (title, body, sort) VALUES (?, ?, ?)')->execute([$title, $body, $sort]);
                admin_flash('success', 'Jaminan ditambahkan.');
            }
            admin_redirect('content.php?tab=jaminan');
        }

        if ($action === 'guarantee_delete') {
            $id = (int) admin_post('id', '0');
            $pdo->prepare('DELETE FROM guarantees WHERE id = ?')->execute([$id]);
            admin_flash('success', 'Jaminan dihapus.');
            admin_redirect('content.php?tab=jaminan');
        }
    } catch (Throwable $error) {
        admin_flash('error', 'Perubahan gagal disimpan: ' . $error->getMessage());
        admin_redirect('content.php?tab=' . $tab);
    }
}

$promo = is_array(lk_setting('promo')) ? lk_setting('promo') : [];
$areas = $slots = $hours = $testimonials = $guarantees = [];
if ($pdo) {
    try {
        $areas = $pdo->query('SELECT * FROM areas ORDER BY sort ASC, id ASC')->fetchAll();
        $slots = $pdo->query('SELECT * FROM slots ORDER BY sort ASC, id ASC')->fetchAll();
        foreach ($pdo->query('SELECT * FROM hours ORDER BY scope ASC, sort ASC, id ASC') as $row) {
            $hours[$row['scope']][] = $row;
        }
        $testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY sort ASC, id ASC')->fetchAll();
        $guarantees = $pdo->query('SELECT * FROM guarantees ORDER BY sort ASC, id ASC')->fetchAll();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

$tabMeta = $tabs[$tab];
admin_head($tabMeta['title'], $tab);
admin_page_head($tabMeta['title'], $tabMeta['sub'], '<a class="abtn abtn--ghost" href="../index.php" target="_blank" rel="noopener">' . admin_icon('external', 15) . 'Lihat hasil</a>');

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

<nav class="tabs" aria-label="Bagian konten">
    <?php foreach ($tabs as $key => $meta): ?>
        <a class="tab<?= $tab === $key ? ' is-active' : '' ?>" href="content.php?tab=<?= e($key) ?>"><?= e($meta['label']) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === 'promo'): ?>
    <div class="dashgrid">
        <section class="card">
            <div class="card__head">
                <div><h2>Pengaturan promo</h2><p>Tampil di tiket promo bawah kalkulator.</p></div>
            </div>
            <div class="card__body">
                <form method="post" action="content.php?tab=promo">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="promo_save">
                    <div class="formgrid">
                        <div class="field">
                            <label for="first_order_percent">Diskon pesanan pertama (%)</label>
                            <input id="first_order_percent" name="first_order_percent" type="number" min="0" max="100" value="<?= (int) ($promo['first_order_percent'] ?? 0) ?>">
                            <p class="field__hint">Isi 0 untuk menyembunyikan angka diskon.</p>
                        </div>
                        <div class="field">
                            <label for="free_pickup_area">Area gratis jemput</label>
                            <input id="free_pickup_area" name="free_pickup_area" value="<?= e((string) ($promo['free_pickup_area'] ?? '')) ?>" maxlength="80">
                        </div>
                        <div class="field field--full">
                            <label for="member_voucher">Voucher member</label>
                            <input id="member_voucher" name="member_voucher" value="<?= e((string) ($promo['member_voucher'] ?? '')) ?>" maxlength="80">
                        </div>
                        <div class="field field--full">
                            <label for="promo_note">Catatan <span>tampil kecil di bawah tiket</span></label>
                            <textarea id="promo_note" name="note" maxlength="255"><?= e((string) ($promo['note'] ?? '')) ?></textarea>
                        </div>
                    </div>
                    <div class="formactions">
                        <span class="formactions__note">Harga dan promo adalah konten publik. Pastikan angkanya sudah disetujui pemilik.</span>
                        <button class="abtn abtn--primary" type="submit" data-busy="Menyimpan...">Simpan promo</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card" style="align-self:start;background:linear-gradient(160deg,#3B2A5C,#2A1F42);border-color:transparent;color:#fff">
            <div class="card__body">
                <p style="font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.5);margin:0 0 10px">Pratinjau tiket</p>
                <p style="font-family:var(--font-head);font-size:44px;font-weight:800;line-height:1;margin:0;letter-spacing:-.03em">
                    <?= (int) ($promo['first_order_percent'] ?? 0) ?><span style="font-size:22px">%</span>
                </p>
                <p style="margin:6px 0 0;font-size:12.5px;color:rgba(255,255,255,.72)">untuk pesanan pertama</p>
                <div style="border-top:1px dashed rgba(255,255,255,.22);margin:16px 0;padding-top:14px;display:grid;gap:9px;font-size:12px;color:rgba(255,255,255,.82)">
                    <span style="display:flex;justify-content:space-between;gap:10px"><span style="color:rgba(255,255,255,.55)">Gratis jemput</span><strong><?= e((string) ($promo['free_pickup_area'] ?? '—')) ?></strong></span>
                    <span style="display:flex;justify-content:space-between;gap:10px"><span style="color:rgba(255,255,255,.55)">Voucher</span><strong><?= e((string) ($promo['member_voucher'] ?? '—')) ?></strong></span>
                </div>
                <p style="margin:0;font-size:10.5px;line-height:1.5;color:rgba(255,255,255,.45)"><?= e((string) ($promo['note'] ?? '')) ?></p>
            </div>
        </section>
    </div>

<?php elseif ($tab === 'area'): ?>
    <div class="dashgrid">
        <section class="card">
            <div class="card__head">
                <div><h2>Area layanan</h2><p>Daftar desa di formulir dan bagian jangkauan.</p></div>
                <span class="pill pill--info"><?= count($areas) ?> area</span>
            </div>
            <div class="card__body">
                <form method="post" action="content.php?tab=area">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="area_save">
                    <?php foreach ($areas as $area): ?>
                        <div class="minirow">
                            <label class="switch" style="gap:0">
                                <input type="checkbox" name="areas[<?= (int) $area['id'] ?>][active]" value="1"<?= $area['active'] ? ' checked' : '' ?>>
                                <span class="switch__track" aria-hidden="true"></span>
                                <span class="sr-only" style="position:absolute;left:-9999px">Aktifkan <?= e($area['name']) ?></span>
                            </label>
                            <span class="minirow__main">
                                <input name="areas[<?= (int) $area['id'] ?>][name]" value="<?= e($area['name']) ?>" maxlength="80"
                                       style="width:100%;height:34px;border:1.5px solid transparent;border-radius:8px;padding:0 8px;background:transparent;font-size:12.8px;font-weight:600">
                            </span>
                            <input type="number" name="areas[<?= (int) $area['id'] ?>][sort]" value="<?= (int) $area['sort'] ?>" aria-label="Urutan"
                                   style="width:52px;height:30px;border:1.5px solid var(--line-2);border-radius:7px;padding:0 6px;font-size:11.5px">
                            <button class="abtn abtn--quiet abtn--sm" type="submit" form="del-area-<?= (int) $area['id'] ?>" aria-label="Hapus <?= e($area['name']) ?>"><?= admin_icon('close', 13) ?></button>
                        </div>
                    <?php endforeach; ?>
                    <div class="formactions">
                        <span class="formactions__note">Area nonaktif tetap tersimpan agar riwayat pesanan lama terbaca.</span>
                        <button class="abtn abtn--primary abtn--sm" type="submit" data-busy="Menyimpan...">Simpan area</button>
                    </div>
                </form>

                <?php foreach ($areas as $area): ?>
                    <form id="del-area-<?= (int) $area['id'] ?>" method="post" action="content.php?tab=area" data-confirm="Hapus area <?= e($area['name']) ?>? Pesanan lama tetap menyimpan nama desa." style="display:none">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="area_delete">
                        <input type="hidden" name="id" value="<?= (int) $area['id'] ?>">
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <div style="display:grid;gap:16px;align-content:start">
            <section class="card">
                <div class="card__head"><div><h2>Tambah area</h2><p>Desa atau kelurahan baru.</p></div></div>
                <div class="card__body">
                    <form method="post" action="content.php?tab=area">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="area_add">
                        <div class="field">
                            <label for="new-area">Nama area</label>
                            <input id="new-area" name="name" maxlength="80" placeholder="mis. Karangnongko" required>
                        </div>
                        <div class="formactions"><button class="abtn abtn--ghost abtn--block" type="submit" data-busy="Menambah..."><?= admin_icon('plus', 14) ?>Tambah area</button></div>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Slot jemput</h2><p>Pilihan jam di formulir jadwal.</p></div></div>
                <div class="card__body">
                    <form method="post" action="content.php?tab=area">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="slot_save">
                        <?php foreach ($slots as $slot): ?>
                            <div class="minirow">
                                <label class="switch" style="gap:0">
                                    <input type="checkbox" name="slots[<?= (int) $slot['id'] ?>][active]" value="1"<?= $slot['active'] ? ' checked' : '' ?>>
                                    <span class="switch__track" aria-hidden="true"></span>
                                    <span class="sr-only" style="position:absolute;left:-9999px">Aktifkan <?= e($slot['label']) ?></span>
                                </label>
                                <span class="minirow__main">
                                    <input name="slots[<?= (int) $slot['id'] ?>][label]" value="<?= e($slot['label']) ?>" maxlength="60"
                                           style="width:100%;height:34px;border:1.5px solid transparent;border-radius:8px;padding:0 8px;background:transparent;font-size:12.8px;font-weight:600">
                                </span>
                                <input type="number" name="slots[<?= (int) $slot['id'] ?>][sort]" value="<?= (int) $slot['sort'] ?>" aria-label="Urutan"
                                       style="width:52px;height:30px;border:1.5px solid var(--line-2);border-radius:7px;padding:0 6px;font-size:11.5px">
                                <button class="abtn abtn--quiet abtn--sm" type="submit" form="del-slot-<?= (int) $slot['id'] ?>" aria-label="Hapus <?= e($slot['label']) ?>"><?= admin_icon('close', 13) ?></button>
                            </div>
                        <?php endforeach; ?>
                        <div class="formactions">
                            <button class="abtn abtn--primary abtn--sm" type="submit" data-busy="Menyimpan...">Simpan slot</button>
                        </div>
                    </form>

                    <?php foreach ($slots as $slot): ?>
                        <form id="del-slot-<?= (int) $slot['id'] ?>" method="post" action="content.php?tab=area" data-confirm="Hapus slot <?= e($slot['label']) ?>?" style="display:none">
                            <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                            <input type="hidden" name="action" value="slot_delete">
                            <input type="hidden" name="id" value="<?= (int) $slot['id'] ?>">
                        </form>
                    <?php endforeach; ?>

                    <form method="post" action="content.php?tab=area" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--line)">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="slot_add">
                        <div class="field">
                            <label for="new-slot">Slot baru</label>
                            <input id="new-slot" name="label" maxlength="60" placeholder="mis. 18.00 - 20.00" required>
                        </div>
                        <div class="formactions"><button class="abtn abtn--ghost abtn--block abtn--sm" type="submit" data-busy="Menambah...">Tambah slot</button></div>
                    </form>
                </div>
            </section>
        </div>
    </div>

<?php elseif ($tab === 'jam'): ?>
    <div class="dashgrid">
        <?php foreach (['outlet' => 'Jam outlet', 'courier' => 'Slot kurir'] as $scope => $scopeLabel): ?>
            <section class="card">
                <div class="card__head">
                    <div>
                        <h2><?= e($scopeLabel) ?></h2>
                        <p><?= $scope === 'outlet' ? 'Jam fisik outlet / drop-off.' : 'Rentang waktu kurir menjemput.' ?></p>
                    </div>
                </div>
                <div class="card__body">
                    <form method="post" action="content.php?tab=jam">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="hours_save">
                        <?php foreach (($hours[$scope] ?? []) as $row): ?>
                            <div class="editrow" style="grid-template-columns:1.1fr 1.1fr 54px auto">
                                <div class="field">
                                    <label>Hari</label>
                                    <input name="hours[<?= (int) $row['id'] ?>][day]" value="<?= e($row['day']) ?>" maxlength="60">
                                </div>
                                <div class="field">
                                    <label>Jam</label>
                                    <input name="hours[<?= (int) $row['id'] ?>][time]" value="<?= e($row['time_label']) ?>" maxlength="80">
                                </div>
                                <input type="number" name="hours[<?= (int) $row['id'] ?>][sort]" value="<?= (int) $row['sort'] ?>" aria-label="Urutan"
                                       style="width:54px;height:41px;border:1.5px solid var(--line-2);border-radius:9px;padding:0 7px;font-size:12px;align-self:end">
                                <button class="abtn abtn--quiet abtn--sm" type="submit" form="del-hour-<?= (int) $row['id'] ?>" aria-label="Hapus baris" style="align-self:end;margin-bottom:2px"><?= admin_icon('close', 13) ?></button>
                            </div>
                        <?php endforeach; ?>
                        <?php if (($hours[$scope] ?? []) === []): ?>
                            <p class="muted" style="font-size:12.3px">Belum ada baris. Halaman publik memakai nilai cadangan dari config.</p>
                        <?php endif; ?>
                        <div class="formactions">
                            <button class="abtn abtn--primary abtn--sm" type="submit" data-busy="Menyimpan...">Simpan</button>
                        </div>
                    </form>

                    <?php foreach (($hours[$scope] ?? []) as $row): ?>
                        <form id="del-hour-<?= (int) $row['id'] ?>" method="post" action="content.php?tab=jam" data-confirm="Hapus baris jam ini?" style="display:none">
                            <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                            <input type="hidden" name="action" value="hours_delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        </form>
                    <?php endforeach; ?>

                    <form method="post" action="content.php?tab=jam" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--line)">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="hours_add">
                        <input type="hidden" name="scope" value="<?= e($scope) ?>">
                        <div class="field" style="margin-bottom:10px">
                            <label>Hari baru</label>
                            <input name="day" maxlength="60" placeholder="mis. Senin sampai Jumat" required>
                        </div>
                        <div class="field">
                            <label>Jam</label>
                            <input name="time" maxlength="80" placeholder="mis. 08.00 - 20.00" required>
                        </div>
                        <div class="formactions"><button class="abtn abtn--ghost abtn--block abtn--sm" type="submit" data-busy="Menambah...">Tambah baris</button></div>
                    </form>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <div class="hintbox" style="margin-top:16px">
        <?= admin_icon('shield', 16) ?>
        <span>Pastikan jam outlet dan slot kurir tidak bertabrakan. Perubahan langsung terlihat di bagian jangkauan halaman utama.</span>
    </div>

<?php elseif ($tab === 'testimoni'): ?>
    <div class="dashgrid">
        <section class="card">
            <div class="card__head">
                <div><h2>Kutipan tampil</h2><p>Hanya testimoni aktif yang dirender. Nama dan wajah tidak ditampilkan publik.</p></div>
                <span class="pill pill--info"><?= count(array_filter($testimonials, static fn ($row) => (int) $row['active'] === 1)) ?> aktif</span>
            </div>
            <div class="card__body">
                <form method="post" action="content.php?tab=testimoni">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="testimonial_save">
                    <?php foreach ($testimonials as $row): ?>
                        <div style="padding:14px 0;border-bottom:1px dashed var(--line)">
                            <div class="formgrid" style="gap:10px">
                                <div class="field">
                                    <label>Nama <span>internal, tidak tampil</span></label>
                                    <input name="testimonials[<?= (int) $row['id'] ?>][name]" value="<?= e($row['name']) ?>" maxlength="120">
                                </div>
                                <div class="field">
                                    <label>Layanan</label>
                                    <input name="testimonials[<?= (int) $row['id'] ?>][service]" value="<?= e($row['service']) ?>" maxlength="40">
                                </div>
                                <div class="field field--full">
                                    <label>Kutipan</label>
                                    <textarea name="testimonials[<?= (int) $row['id'] ?>][quote]" maxlength="500"><?= e($row['quote']) ?></textarea>
                                </div>
                                <div class="field">
                                    <label>Rating <span>1–5, kosongkan jika tidak ada</span></label>
                                    <input name="testimonials[<?= (int) $row['id'] ?>][rating]" type="number" min="1" max="5" value="<?= $row['rating'] !== null ? (int) $row['rating'] : '' ?>">
                                </div>
                                <div class="field" style="grid-template-columns:1fr auto;display:flex;align-items:end;justify-content:space-between;gap:10px">
                                    <label class="switch">
                                        <input type="checkbox" name="testimonials[<?= (int) $row['id'] ?>][active]" value="1"<?= $row['active'] ? ' checked' : '' ?>>
                                        <span class="switch__track" aria-hidden="true"></span>
                                        <span>Tampil</span>
                                    </label>
                                    <button class="abtn abtn--quiet abtn--sm" type="submit" form="del-testi-<?= (int) $row['id'] ?>" aria-label="Hapus testimoni"><?= admin_icon('close', 13) ?></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="formactions">
                        <span class="formactions__note">Jangan mengarang kutipan. Ganti placeholder hanya setelah ada izin pelanggan.</span>
                        <button class="abtn abtn--primary abtn--sm" type="submit" data-busy="Menyimpan...">Simpan testimoni</button>
                    </div>
                </form>
                <?php foreach ($testimonials as $row): ?>
                    <form id="del-testi-<?= (int) $row['id'] ?>" method="post" action="content.php?tab=testimoni" data-confirm="Hapus testimoni dari <?= e($row['name']) ?>?" style="display:none">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="testimonial_delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card" style="align-self:start">
            <div class="card__head"><div><h2>Tambah testimoni</h2><p>Catat nama dan konteks, bukan hanya bintang.</p></div></div>
            <div class="card__body">
                <form method="post" action="content.php?tab=testimoni">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="testimonial_add">
                    <div class="field" style="margin-bottom:10px">
                        <label for="t-name">Nama &amp; desa</label>
                        <input id="t-name" name="name" maxlength="120" placeholder="mis. Ibu Sari — Kepanjen" required>
                    </div>
                    <div class="field" style="margin-bottom:10px">
                        <label for="t-service">Layanan</label>
                        <input id="t-service" name="service" maxlength="40" placeholder="mis. Kiloan">
                    </div>
                    <div class="field" style="margin-bottom:10px">
                        <label for="t-rating">Rating</label>
                        <input id="t-rating" name="rating" type="number" min="1" max="5" placeholder="5">
                    </div>
                    <div class="field" style="margin-bottom:12px">
                        <label for="t-quote">Kutipan</label>
                        <textarea id="t-quote" name="quote" maxlength="500" required placeholder="Tulis apa adanya, tanpa dibesar-besarkan."></textarea>
                    </div>
                    <button class="abtn abtn--ghost abtn--block" type="submit" data-busy="Menambah...">Tambah testimoni</button>
                </form>
            </div>
        </section>
    </div>

<?php else: ?>
    <div class="dashgrid">
        <section class="card">
            <div class="card__head"><div><h2>Jaminan tampil</h2><p>Tiga komitmen di bagian bukti sosial.</p></div></div>
            <div class="card__body">
                <form method="post" action="content.php?tab=jaminan">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="guarantee_save">
                    <?php foreach ($guarantees as $row): ?>
                        <div style="padding:12px 0;border-bottom:1px dashed var(--line)">
                            <div class="formgrid" style="gap:10px">
                                <div class="field">
                                    <label>Judul</label>
                                    <input name="guarantees[<?= (int) $row['id'] ?>][title]" value="<?= e($row['title']) ?>" maxlength="80">
                                </div>
                                <div class="field" style="grid-column:span 1;justify-self:end;display:flex;align-items:end;gap:8px">
                                    <input type="number" name="guarantees[<?= (int) $row['id'] ?>][sort]" value="<?= (int) $row['sort'] ?>" aria-label="Urutan"
                                           style="width:54px;height:41px;border:1.5px solid var(--line-2);border-radius:9px;padding:0 7px;font-size:12px">
                                    <button class="abtn abtn--quiet abtn--sm" type="submit" form="del-guar-<?= (int) $row['id'] ?>" aria-label="Hapus jaminan"><?= admin_icon('close', 13) ?></button>
                                </div>
                                <div class="field field--full">
                                    <label>Isi</label>
                                    <textarea name="guarantees[<?= (int) $row['id'] ?>][body]" maxlength="255"><?= e($row['body']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="formactions">
                        <button class="abtn abtn--primary abtn--sm" type="submit" data-busy="Menyimpan...">Simpan jaminan</button>
                    </div>
                </form>
                <?php foreach ($guarantees as $row): ?>
                    <form id="del-guar-<?= (int) $row['id'] ?>" method="post" action="content.php?tab=jaminan" data-confirm="Hapus jaminan <?= e($row['title']) ?>?" style="display:none">
                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="action" value="guarantee_delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card" style="align-self:start">
            <div class="card__head"><div><h2>Tambah jaminan</h2><p>Janji yang benar-benar bisa dipenuhi.</p></div></div>
            <div class="card__body">
                <form method="post" action="content.php?tab=jaminan">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="guarantee_add">
                    <div class="field" style="margin-bottom:10px">
                        <label for="g-title">Judul</label>
                        <input id="g-title" name="title" maxlength="80" required>
                    </div>
                    <div class="field" style="margin-bottom:12px">
                        <label for="g-body">Isi</label>
                        <textarea id="g-body" name="body" maxlength="255" required></textarea>
                    </div>
                    <button class="abtn abtn--ghost abtn--block" type="submit" data-busy="Menambah...">Tambah jaminan</button>
                </form>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php admin_foot(); ?>
