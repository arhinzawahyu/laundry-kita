<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();
$isOwner = ($user['role'] ?? '') === 'owner';

if ($pdo && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $action = admin_post('action');

    if ($action === 'profile') {
        $name = admin_post('name');
        $email = strtolower(admin_post('email'));
        if (text_len($name) < 2 || text_len($name) > 60) {
            admin_flash('error', 'Nama harus 2–60 karakter.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_flash('error', 'Email belum valid.');
        } else {
            try {
                $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? AND id <> ? LIMIT 1');
                $check->execute([$email, (int) $user['id']]);
                if ($check->fetchColumn() !== false) {
                    admin_flash('error', 'Email itu sudah dipakai admin lain.');
                } else {
                    $pdo->prepare('UPDATE admin_users SET name = ?, email = ? WHERE id = ?')
                        ->execute([$name, $email, (int) $user['id']]);
                    admin_audit('account.update', 'Profil admin diperbarui');
                    admin_flash('success', 'Profil diperbarui.');
                }
            } catch (Throwable $error) {
                admin_flash('error', 'Profil gagal disimpan: ' . $error->getMessage());
            }
        }
        admin_redirect('account.php');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, (string) $user['password_hash'])) {
            admin_flash('error', 'Kata sandi saat ini tidak cocok.');
        } elseif (strlen($new) < 10 || !preg_match('/[a-zA-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
            admin_flash('error', 'Kata sandi baru minimal 10 karakter dan harus campur huruf & angka.');
        } elseif ($new !== $confirm) {
            admin_flash('error', 'Konfirmasi kata sandi baru belum sama.');
        } else {
            try {
                $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
                admin_audit('account.update', 'Kata sandi diubah');
                session_regenerate_id(true);
                admin_flash('success', 'Kata sandi diperbarui. Sesi lain tetap aktif sampai logout.');
            } catch (Throwable $error) {
                admin_flash('error', 'Kata sandi gagal diubah: ' . $error->getMessage());
            }
        }
        admin_redirect('account.php');
    }

    if ($action === 'team_add') {
        if (!$isOwner) {
            admin_flash('error', 'Hanya pemilik yang bisa menambah admin.');
            admin_redirect('account.php');
        }
        $name = admin_post('team_name');
        $email = strtolower(admin_post('team_email'));
        $password = (string) ($_POST['team_password'] ?? '');
        $role = admin_post('team_role') === 'owner' ? 'owner' : 'editor';

        if (text_len($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_flash('error', 'Nama dan email admin baru belum lengkap.');
        } elseif (strlen($password) < 10) {
            admin_flash('error', 'Kata sandi admin baru minimal 10 karakter.');
        } else {
            try {
                $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                admin_audit('team.add', $name . ' (' . $role . ')');
                admin_flash('success', 'Admin ' . $name . ' ditambahkan sebagai ' . ($role === 'owner' ? 'pemilik' : 'editor') . '.');
            } catch (Throwable $error) {
                admin_flash('error', 'Admin gagal ditambahkan: ' . $error->getMessage());
            }
        }
        admin_redirect('account.php');
    }

    if ($action === 'team_remove') {
        if (!$isOwner) {
            admin_flash('error', 'Hanya pemilik yang bisa menghapus admin.');
            admin_redirect('account.php');
        }
        $id = (int) admin_post('id', '0');
        if ($id === (int) $user['id']) {
            admin_flash('error', 'Anda tidak bisa menghapus akun sendiri.');
            admin_redirect('account.php');
        }
        try {
            $target = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
            $target->execute([$id]);
            $member = $target->fetch();
            if (!$member) {
                admin_flash('error', 'Admin tidak ditemukan.');
            } else {
                $owners = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'owner'")->fetchColumn();
                if ($member['role'] === 'owner' && $owners <= 1) {
                    admin_flash('error', 'Pemilik terakhir tidak boleh dihapus.');
                } else {
                    $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
                    admin_audit('team.remove', (string) $member['name']);
                    admin_flash('success', 'Admin ' . $member['name'] . ' dihapus.');
                }
            }
        } catch (Throwable $error) {
            admin_flash('error', 'Admin gagal dihapus: ' . $error->getMessage());
        }
        admin_redirect('account.php');
    }
}

$team = [];
if ($pdo) {
    try {
        $team = $pdo->query('SELECT * FROM admin_users ORDER BY role ASC, name ASC')->fetchAll();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

admin_head('Akun & Tim', 'account');
admin_page_head('Akun & Tim', 'Profil yang sedang masuk, keamanan kata sandi, dan akses admin lain.');
?>

<div class="dashgrid">
    <section class="card">
        <div class="card__head"><div><h2>Profil admin</h2><p>Nama tampil di sidebar dan jejak aktivitas.</p></div></div>
        <div class="card__body">
            <form method="post" action="account.php">
                <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                <input type="hidden" name="action" value="profile">
                <div class="formgrid">
                    <div class="field">
                        <label for="acc-name">Nama</label>
                        <input id="acc-name" name="name" value="<?= e((string) $user['name']) ?>" maxlength="60" required>
                    </div>
                    <div class="field">
                        <label for="acc-email">Email</label>
                        <input id="acc-email" name="email" type="email" value="<?= e((string) $user['email']) ?>" required>
                    </div>
                </div>
                <div class="formactions">
                    <span class="formactions__note">Terakhir masuk: <?= e(admin_ago((string) $user['last_login_at'])) ?>.</span>
                    <button class="abtn abtn--ghost" type="submit" data-busy="Menyimpan...">Simpan profil</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card__head"><div><h2>Kata sandi</h2><p>Ganti dengan kombinasi yang tidak dipakai di layanan lain.</p></div></div>
        <div class="card__body">
            <form method="post" action="account.php">
                <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                <input type="hidden" name="action" value="password">
                <div class="field" style="margin-bottom:10px">
                    <label for="current_password">Kata sandi saat ini</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="field" style="margin-bottom:10px">
                    <label for="new_password">Kata sandi baru</label>
                    <input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label for="confirm_password">Ulangi kata sandi baru</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                </div>
                <button class="abtn abtn--ghost abtn--block" type="submit" data-busy="Menyimpan...">Perbarui kata sandi</button>
            </form>
        </div>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <div class="card__head">
        <div>
            <h2>Tim admin</h2>
            <p><?= $isOwner ? 'Pemilik bisa menambah editor atau pemilik lain.' : 'Hanya pemilik yang bisa mengubah daftar admin.' ?></p>
        </div>
        <span class="pill pill--info"><?= count($team) ?> akun</span>
    </div>
    <div class="card__body">
        <div class="tablewrap" style="margin:-6px 0 14px">
            <table class="table">
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>Peran</th><th class="hide-sm">Terakhir masuk</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($team as $member): ?>
                        <tr>
                            <td>
                                <div class="cust">
                                    <span class="cust__avatar"><?= e(admin_initials((string) $member['name'])) ?></span>
                                    <span class="cust__text"><strong><?= e($member['name']) ?><?= (int) $member['id'] === (int) $user['id'] ? ' (Anda)' : '' ?></strong></span>
                                </div>
                            </td>
                            <td class="muted"><?= e($member['email']) ?></td>
                            <td><span class="pill <?= $member['role'] === 'owner' ? 'pill--accent' : 'pill--info' ?>"><i></i><?= $member['role'] === 'owner' ? 'Pemilik' : 'Editor' ?></span></td>
                            <td class="hide-sm muted"><?= e(admin_ago((string) $member['last_login_at'])) ?></td>
                            <td class="num">
                                <?php if ($isOwner && (int) $member['id'] !== (int) $user['id']): ?>
                                    <form method="post" action="account.php" data-confirm="Hapus akses <?= e($member['name']) ?>?">
                                        <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                                        <input type="hidden" name="action" value="team_remove">
                                        <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                        <button class="abtn abtn--quiet abtn--sm" type="submit">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($isOwner): ?>
            <form method="post" action="account.php" style="border-top:1px solid var(--line);padding-top:16px">
                <input type="hidden" name="_token" value="<?= e(admin_csrf()) ?>">
                <input type="hidden" name="action" value="team_add">
                <p class="formsec__title" style="margin-bottom:12px">Tambah admin</p>
                <div class="formgrid">
                    <div class="field">
                        <label for="team_name">Nama</label>
                        <input id="team_name" name="team_name" required>
                    </div>
                    <div class="field">
                        <label for="team_email">Email</label>
                        <input id="team_email" name="team_email" type="email" required>
                    </div>
                    <div class="field">
                        <label for="team_password">Kata sandi awal</label>
                        <input id="team_password" name="team_password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="team_role">Peran</label>
                        <select id="team_role" name="team_role">
                            <option value="editor">Editor — konten &amp; pesanan</option>
                            <option value="owner">Pemilik — akses penuh</option>
                        </select>
                    </div>
                </div>
                <div class="formactions">
                    <span class="formactions__note">Sampaikan kata sandi lewat kanal pribadi, lalu minta admin menggantinya.</span>
                    <button class="abtn abtn--primary" type="submit" data-busy="Menambah...">Tambah admin</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php admin_foot(); ?>
