<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();
$id = max(0, (int) ($_GET['id'] ?? 0));

$order = null;
$events = [];
$serviceLabel = '—';

if ($pdo && $id > 0) {
    try {
        $statement = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $order = $statement->fetch() ?: null;

        if ($order) {
            $eventStatement = $pdo->prepare(
                'SELECT e.*, u.name FROM order_events e
                 LEFT JOIN admin_users u ON u.id = e.admin_id
                 WHERE e.order_id = ? ORDER BY e.id DESC'
            );
            $eventStatement->execute([$id]);
            $events = $eventStatement->fetchAll();

            $serviceStatement = $pdo->prepare('SELECT label FROM services WHERE service_key = ? LIMIT 1');
            $serviceStatement->execute([$order['service_key']]);
            $label = $serviceStatement->fetchColumn();
            if (is_string($label) && $label !== '') {
                $serviceLabel = $label;
            }
        }
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

if ($order && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && admin_post('action') === 'status') {
    admin_check_csrf();
    $statuses = lk_order_statuses();
    $newStatus = admin_post('status');
    $note = admin_post('note');

    if (!isset($statuses[$newStatus])) {
        admin_flash('error', 'Status tidak dikenal.');
    } elseif ($newStatus === $order['status'] && $note === '') {
        admin_flash('error', 'Pilih status baru atau tulis catatan perubahan.');
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $id]);
            $pdo->prepare('INSERT INTO order_events (order_id, status, note, admin_id) VALUES (?, ?, ?, ?)')
                ->execute([$id, $newStatus, $note !== '' ? $note : null, (int) $user['id']]);
            $pdo->commit();
            admin_audit('order.status', $order['code'] . ' → ' . $statuses[$newStatus]['label']);
            admin_flash('success', 'Status ' . $order['code'] . ' diperbarui menjadi ' . $statuses[$newStatus]['label'] . '.');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            admin_flash('error', 'Status gagal disimpan: ' . $error->getMessage());
        }
    }
    admin_redirect('order.php?id=' . $id);
}

admin_head($order ? 'Pesanan ' . $order['code'] : 'Pesanan', 'orders');

if (!$order) {
    ?>
    <div class="card"><div class="card__body"><div class="empty">
        <span class="empty__icon"><?= admin_icon('search', 22) ?></span>
        <strong>Pesanan tidak ditemukan</strong>
        <p>Data mungkin sudah dihapus atau tautannya salah.</p>
        <p style="margin-top:16px"><a class="abtn abtn--ghost" href="orders.php">← Kembali ke daftar pesanan</a></p>
    </div></div></div>
    <?php
    admin_foot();
    exit;
}

$waDigits = preg_replace('/[^0-9]/', '', (string) $order['whatsapp']) ?? '';
$waLink = strlen($waDigits) >= 9 ? 'https://wa.me/' . $waDigits : '';
$statusMeta = lk_order_statuses()[$order['status']] ?? ['label' => ucfirst((string) $order['status']), 'tone' => 'info'];
$isFinal = in_array($order['status'], ['selesai', 'batal'], true);

$actions = '<button class="abtn abtn--ghost" type="button" data-copy="' . e((string) $order['code']) . '">' . admin_icon('copy', 14) . '<span data-copy-label>Salin kode</span></button>'
    . '<a class="abtn abtn--ghost" href="orders.php">' . admin_icon('arrow-left', 14) . 'Semua pesanan</a>';
if ($waLink !== '') {
    $actions .= '<a class="abtn abtn--primary" href="' . e($waLink) . '" target="_blank" rel="noopener">' . admin_icon('whatsapp', 15) . 'Chat pelanggan</a>';
}
admin_page_head(
    'Pesanan ' . $order['code'],
    'Dibuat ' . date('d M Y, H:i', strtotime((string) $order['created_at'])) . ' · ' . $serviceLabel,
    $actions
);
?>

<div class="ordergrid">
    <div style="display:grid;gap:16px">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Detail pelanggan</h2>
                    <p>Data dari formulir jadwal jemput.</p>
                </div>
                <?= admin_pill((string) $order['status']) ?>
            </div>
            <div class="card__body">
                <dl class="kv">
                    <dt>Nama</dt><dd><?= e($order['name']) ?></dd>
                    <dt>WhatsApp</dt>
                    <dd>
                        <?= e($order['whatsapp']) ?>
                        <?php if ($waLink !== ''): ?>
                            <a class="card__link" href="<?= e($waLink) ?>" target="_blank" rel="noopener" style="margin-left:6px">Chat <?= admin_icon('whatsapp', 12) ?></a>
                        <?php endif; ?>
                    </dd>
                    <dt>Desa</dt><dd><?= e($order['desa']) ?></dd>
                    <dt>Alamat</dt><dd><?= e($order['address']) ?></dd>
                    <dt>Catatan</dt><dd><?= $order['notes'] !== null && $order['notes'] !== '' ? e($order['notes']) : '<span class="muted">Tidak ada catatan</span>' ?></dd>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Rincian jadwal</h2>
                    <p>Estimasi bukan tagihan final; kurir menimbang ulang di lokasi.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="formgrid" style="gap:16px 14px">
                    <div class="field">
                        <label>Tanggal jemput</label>
                        <input value="<?= e(date('d/m/Y', strtotime((string) $order['pickup_date']))) ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Slot jemput</label>
                        <input value="<?= e($order['slot']) ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Layanan</label>
                        <input value="<?= e($serviceLabel) ?> (<?= e($order['service_key']) ?>)" readonly>
                    </div>
                    <div class="field">
                        <label>Berat perkiraan</label>
                        <input value="<?= $order['weight'] !== null ? e(rtrim(rtrim(number_format((float) $order['weight'], 2, ',', '.'), '0'), ',')) . ' kg' : 'Tidak diisi' ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Estimasi biaya</label>
                        <input value="<?= $order['estimate_total'] !== null ? e(rupiah((float) $order['estimate_total'])) : 'Menunggu penimbangan' ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Sumber</label>
                        <input value="<?= e($order['source']) ?>" readonly>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div style="display:grid;gap:16px">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Perbarui status</h2>
                    <p>Setiap perubahan dicatat di riwayat.</p>
                </div>
            </div>
            <div class="card__body">
                <form class="statusform" method="post" action="order.php?id=<?= (int) $order['id'] ?>">
                    <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="action" value="status">
                    <div class="field">
                        <label for="status">Status baru</label>
                        <select id="status" name="status">
                            <?php foreach (lk_order_statuses() as $key => $meta): ?>
                                <option value="<?= e($key) ?>"<?= $order['status'] === $key ? ' selected' : '' ?>><?= e($meta['label']) ?> — <?= e($meta['desc']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="note">Catatan <span>opsional</span></label>
                        <textarea id="note" name="note" maxlength="190" placeholder="Contoh: kurir otw, pelanggan minta setelah jam 15.00"></textarea>
                    </div>
                    <button class="abtn abtn--primary abtn--block" type="submit" data-busy="Menyimpan...">Simpan status</button>
                    <p class="field__hint">Status saat ini: <?= e($statusMeta['label']) ?>. <?= $isFinal ? 'Pesanan sudah final, perubahan tetap bisa dilakukan bila perlu.' : '' ?></p>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Riwayat pesanan</h2>
                    <p><?= count($events) ?> catatan perubahan.</p>
                </div>
            </div>
            <div class="card__body">
                <?php if ($events === []): ?>
                    <p class="muted" style="font-size:12.3px;margin:0">Belum ada riwayat.</p>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($events as $index => $event): ?>
                            <?php $meta = lk_order_statuses()[$event['status']] ?? ['label' => ucfirst((string) $event['status'])]; ?>
                            <li class="<?= $index === 0 ? 'is-done is-last' : 'is-done' ?>">
                                <span class="timeline__dot"></span>
                                <span class="timeline__main">
                                    <strong><?= e($meta['label']) ?></strong>
                                    <small><?= date('d M Y, H:i', strtotime((string) $event['created_at'])) ?> · <?= e($event['name'] ?: 'Sistem') ?></small>
                                    <?php if ($event['note']): ?><small style="color:var(--ink);margin-top:2px">“<?= e($event['note']) ?>”</small><?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php admin_foot(); ?>
