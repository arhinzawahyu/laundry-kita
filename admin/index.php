<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();

admin_head('Ringkasan', 'dashboard');

if (!$pdo) {
    ?>
    <div class="card">
        <div class="card__body">
            <div class="empty">
                <span class="empty__icon"><?= admin_icon('alert', 22) ?></span>
                <strong>Database belum tersambung</strong>
                <p>Nyalakan MySQL di XAMPP lalu muat ulang halaman. Detail: <?= e(lk_db_error() ?: 'tidak diketahui') ?></p>
            </div>
            <p style="text-align:center;margin:0"><a class="abtn abtn--ghost" href="login.php">Kembali ke login</a></p>
        </div>
    </div>
    <?php
    admin_foot();
    exit;
}

$statuses = lk_order_statuses();
$counts = array_fill_keys(array_keys($statuses), 0);
$totalOrders = 0;
$monthOrders = 0;
$monthDone = 0;
$monthValue = 0.0;
$todayPickups = 0;
$todayNew = 0;
$newOrders = 0;
$recent = [];
$todaySchedule = [];
$activity = [];
$series = [];

try {
    foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status') as $row) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['c'];
        }
    }
    $totalOrders = array_sum($counts);
    $newOrders = $counts['baru'];

    $monthOrders = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
    )->fetchColumn();
    $monthDone = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE status = 'selesai' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
    )->fetchColumn();
    $monthValue = (float) $pdo->query(
        "SELECT COALESCE(SUM(estimate_total), 0) FROM orders
         WHERE status <> 'batal' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
    )->fetchColumn();
    $todayPickups = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE pickup_date = CURDATE() AND status NOT IN ('batal', 'selesai')"
    )->fetchColumn();
    $todayNew = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
    )->fetchColumn();

    $recent = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT 6')->fetchAll();
    $todaySchedule = $pdo->query(
        "SELECT * FROM orders WHERE pickup_date = CURDATE() AND status NOT IN ('batal', 'selesai')
         ORDER BY slot ASC, id ASC LIMIT 6"
    )->fetchAll();

    $activity = $pdo->query(
        'SELECT a.*, u.name FROM admin_activity a
         LEFT JOIN admin_users u ON u.id = a.admin_id
         ORDER BY a.id DESC LIMIT 6'
    )->fetchAll();

    foreach ($pdo->query(
        'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM orders
         WHERE created_at >= (CURDATE() - INTERVAL 6 DAY)
         GROUP BY DATE(created_at)'
    ) as $row) {
        $series[$row['d']] = (int) $row['c'];
    }
} catch (Throwable $error) {
    $GLOBALS['lk_db_error'] = $error->getMessage();
}

$chartDays = [];
for ($i = 6; $i >= 0; $i--) {
    $day = new DateTimeImmutable('-' . $i . ' days');
    $key = $day->format('Y-m-d');
    $chartDays[] = [
        'label' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][(int) $day->format('w')],
        'date'  => $day->format('d/m'),
        'count' => (int) ($series[$key] ?? 0),
        'today' => $i === 0,
    ];
}
$chartMax = max(1, max(array_column($chartDays, 'count')));

$dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$todayLabel = $dayNames[(int) date('w')] . ', ' . date('j') . ' ' . $monthNames[(int) date('n')] . ' ' . date('Y');

$actions = '<a class="abtn abtn--ghost" href="../index.php" target="_blank" rel="noopener">' . admin_icon('external', 15) . 'Lihat situs</a>'
    . '<a class="abtn abtn--primary" href="orders.php">' . admin_icon('orders', 15) . 'Kelola pesanan</a>';
admin_page_head('Ringkasan', $todayLabel . ' · kondisi operasional hari ini', $actions);
?>

<div class="stats">
    <article class="stat stat--hero">
        <div class="stat__top">
            <span class="stat__label">Pesanan baru</span>
            <span class="stat__icon"><?= admin_icon('inbox', 18) ?></span>
        </div>
        <p class="stat__value"><?= $newOrders ?></p>
        <p class="stat__note"><b><?= $todayPickups ?></b> jemput dijadwalkan hari ini</p>
    </article>

    <article class="stat">
        <div class="stat__top">
            <span class="stat__label">Pesanan bulan ini</span>
            <span class="stat__icon"><?= admin_icon('calendar', 18) ?></span>
        </div>
        <p class="stat__value"><?= $monthOrders ?></p>
        <p class="stat__note"><b>+<?= $todayNew ?></b> masuk hari ini</p>
    </article>

    <article class="stat">
        <div class="stat__top">
            <span class="stat__label">Estimasi nilai</span>
            <span class="stat__icon"><?= admin_icon('wallet', 18) ?></span>
        </div>
        <p class="stat__value"><?= e(admin_short_money($monthValue)) ?></p>
        <p class="stat__note">Dari berat yang diisi pelanggan</p>
    </article>

    <article class="stat">
        <div class="stat__top">
            <span class="stat__label">Selesai bulan ini</span>
            <span class="stat__icon"><?= admin_icon('check', 18) ?></span>
        </div>
        <p class="stat__value"><?= $monthDone ?></p>
        <p class="stat__note"><?= $monthOrders > 0 ? round($monthDone / $monthOrders * 100) . '% dari pesanan bulan ini' : 'Belum ada pesanan' ?></p>
    </article>
</div>

<div class="dashgrid">
    <section class="card">
        <div class="card__head">
            <div>
                <h2>Pesanan 7 hari terakhir</h2>
                <p>Dihitung dari waktu pesanan masuk, bukan jadwal jemput.</p>
            </div>
            <div class="legend">
                <span><i></i>Masuk</span>
                <span><i class="is-now"></i>Hari ini</span>
            </div>
        </div>
        <div class="card__body">
            <div class="chart" role="img" aria-label="Jumlah pesanan masuk per hari selama tujuh hari terakhir">
                <?php foreach ($chartDays as $day): ?>
                    <div class="chart__col">
                        <div class="chart__bar<?= $day['today'] ? ' is-now' : '' ?>"
                             style="--h:<?= max(3, (int) round($day['count'] / $chartMax * 100)) ?>%"
                             title="<?= e($day['label'] . ' ' . $day['date'] . ': ' . $day['count'] . ' pesanan') ?>">
                            <span class="chart__value"><?= $day['count'] ?></span>
                        </div>
                        <span class="chart__label"><b><?= e($day['label']) ?></b><?= e($day['date']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card__head">
            <div>
                <h2>Status pesanan</h2>
                <p>Distribusi seluruh pesanan yang tercatat.</p>
            </div>
            <span class="pill pill--info"><?= $totalOrders ?> total</span>
        </div>
        <div class="card__body" style="display:grid;gap:12px">
            <?php foreach ($statuses as $key => $meta): ?>
                <?php $count = (int) ($counts[$key] ?? 0); ?>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:5px">
                        <span style="font-size:12px;color:var(--muted)"><?= e($meta['label']) ?></span>
                        <strong style="font-size:12px;font-variant-numeric:tabular-nums"><?= $count ?></strong>
                    </div>
                    <div style="height:5px;border-radius:999px;background:#F0EDE5;overflow:hidden">
                        <span style="display:block;height:100%;width:<?= $totalOrders > 0 ? max(0, round($count / $totalOrders * 100)) : 0 ?>%;border-radius:999px;background:<?= $key === 'baru' ? 'var(--deep)' : ($key === 'batal' ? 'var(--danger)' : 'var(--base)') ?>;opacity:<?= $count === 0 ? '0' : '1' ?>"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="card" style="margin-bottom:16px">
    <div class="card__head">
        <div>
            <h2>Pesanan terbaru</h2>
            <p>Enam pesanan terakhir dari formulir jadwal.</p>
        </div>
        <a class="card__link" href="orders.php">Semua pesanan <?= admin_icon('arrow-right', 13) ?></a>
    </div>
    <?php if ($recent === []): ?>
        <div class="empty">
            <span class="empty__icon"><?= admin_icon('inbox', 22) ?></span>
            <strong>Belum ada pesanan</strong>
            <p>Pesanan akan muncul di sini setelah pelanggan mengirim formulir jadwal jemput di halaman utama.</p>
        </div>
    <?php else: ?>
        <div class="tablewrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th class="hide-sm">Jadwal jemput</th>
                        <th>Status</th>
                        <th class="num hide-sm">Estimasi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $order): ?>
                        <tr>
                            <td><span class="code"><?= e($order['code']) ?></span></td>
                            <td>
                                <div class="cust">
                                    <span class="cust__avatar"><?= e(admin_initials((string) $order['name'])) ?></span>
                                    <span class="cust__text">
                                        <strong><?= e($order['name']) ?></strong>
                                        <small><?= e($order['desa']) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="hide-sm">
                                <div style="font-size:12.3px"><?= e(date('d/m/Y', strtotime((string) $order['pickup_date']))) ?></div>
                                <small class="muted"><?= e($order['slot']) ?></small>
                            </td>
                            <td><?= admin_pill((string) $order['status']) ?></td>
                            <td class="num hide-sm"><?= $order['estimate_total'] !== null ? e(rupiah((float) $order['estimate_total'])) : '<span class="muted">—</span>' ?></td>
                            <td class="num"><a class="abtn abtn--quiet abtn--sm" href="order.php?id=<?= (int) $order['id'] ?>">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="dashgrid">
    <section class="card">
        <div class="card__head">
            <div>
                <h2>Jadwal jemput hari ini</h2>
                <p>Pesanan aktif dengan tanggal jemput hari ini.</p>
            </div>
            <a class="card__link" href="orders.php?status=aktif">Lihat semua <?= admin_icon('arrow-right', 13) ?></a>
        </div>
        <div class="card__body">
            <?php if ($todaySchedule === []): ?>
                <div class="empty" style="padding:22px 10px">
                    <span class="empty__icon" style="width:38px;height:38px"><?= admin_icon('calendar', 18) ?></span>
                    <strong>Belum ada jemput hari ini</strong>
                    <p>Jadwal baru akan tampil otomatis di sini.</p>
                </div>
            <?php else: ?>
                <div class="stack">
                    <?php foreach ($todaySchedule as $order): ?>
                        <div class="minirow">
                            <span class="code"><?= e($order['slot']) ?></span>
                            <span class="minirow__main">
                                <strong><?= e($order['name']) ?></strong>
                                <small><?= e($order['desa']) ?> · <?= e($order['code']) ?></small>
                            </span>
                            <?= admin_pill((string) $order['status']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card__head">
            <div>
                <h2>Aktivitas admin</h2>
                <p>Jejak perubahan terakhir di dashboard.</p>
            </div>
        </div>
        <div class="card__body">
            <?php if ($activity === []): ?>
                <div class="empty" style="padding:22px 10px">
                    <span class="empty__icon" style="width:38px;height:38px"><?= admin_icon('shield', 18) ?></span>
                    <strong>Belum ada aktivitas</strong>
                    <p>Setiap perubahan penting akan dicatat.</p>
                </div>
            <?php else: ?>
                <?php
                $actionLabel = [
                    'setup'         => 'Instalasi akun',
                    'login'         => 'Masuk dashboard',
                    'order.status'  => 'Ubah status pesanan',
                    'service.save'  => 'Simpan layanan',
                    'content.save'  => 'Simpan konten',
                    'settings.save' => 'Simpan pengaturan',
                    'account.update'=> 'Perbarui akun',
                    'team.add'      => 'Tambah admin',
                    'team.remove'   => 'Hapus admin',
                ];
                ?>
                <div class="stack">
                    <?php foreach ($activity as $item): ?>
                        <div class="minirow" style="align-items:flex-start">
                            <span class="cust__avatar" style="margin-top:1px"><?= admin_icon('check', 13) ?></span>
                            <span class="minirow__main">
                                <strong><?= e($actionLabel[$item['action']] ?? ucfirst(str_replace(['.', '_'], ' ', (string) $item['action']))) ?></strong>
                                <small><?= e(($item['name'] ?: 'Sistem') . ' · ' . ($item['detail'] ?: 'tanpa catatan')) ?></small>
                            </span>
                            <small class="muted nowrap"><?= e(admin_ago((string) $item['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php admin_foot(); ?>
