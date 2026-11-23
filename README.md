# GO Laundry — landing page + admin CMS

Situs jemput-antar laundry untuk Delanggu, Klaten, dengan dashboard admin untuk
pesanan dan seluruh konten yang tampil di halaman publik.

## Kebutuhan

- PHP 8.1+ (`str_contains`, `DateTimeImmutable`, PDO MySQL)
- MySQL / MariaDB (XAMPP sudah cukup)
- Ekstensi `pdo_mysql`

## Menjalankan pertama kali

1. Nyalakan **Apache** dan **MySQL** di XAMPP.
2. Buka `http://localhost/Laundry%20Kita/` — database `laundry_kita`, tabel, dan
   konten awal dibuat otomatis dari `config/site.php` pada request pertama.
3. Buka `http://localhost/Laundry%20Kita/admin/` dan masuk dengan akun demo:

   | Email | Kata sandi |
   | --- | --- |
   | `admin@golaundry.id` | `GoLaundry2026!` |

4. Setelah masuk, buka **Akun** untuk mengganti nama, email, dan kata sandi demo.
   Setelah email diganti, banner "Akun demo aktif" hilang otomatis.
5. Pembuat akun `admin/setup.php` hanya muncul jika tabel `admin_users` kosong.
   Karena seed otomatis sudah membuat akun demo, jalankan `DELETE FROM admin_users;`
   lebih dulu bila ingin melewati akun demo dan membuat akun nyata lewat halaman
   instalasi. Kredensial demo jangan dipakai di server publik.

Kredensial database ada di `config/database.php` (bawaan XAMPP: `root`, tanpa
sandi). Bisa ditimpa lewat env `LK_DB_HOST`, `LK_DB_PORT`, `LK_DB_NAME`,
`LK_DB_USER`, `LK_DB_PASS`.

## Design system admin

Sumber kebenaran: `admin/assets/tokens.css` (nilai), `admin/assets/admin.css`
(komponen), `DESIGN.md` (aturan pemakaian). Ringkasnya:

- Skala huruf 9 langkah (10.5 → 34 px), dua keluarga font, jarak kelipatan 4 px.
- Warna hanya lewat token semantik: `--surface-*`, `--text-*`, `--border`,
  `--action-*`, dan status `--ok/warn/danger/info` (teks + latar + garis).
- Radius 6/8/10/14/18/pill, elevasi 4 tingkat, gerak 120–420 ms dengan easing sama.
- Komponen: `.abtn`, `.field`, `.card`, `.table`, `.pill`, `.chip`, `.tabs`,
  `.toast`, `.confirm`, `.empty`, `.hintbox`, `.stat`, cangkang `.auth`.
- Tidak ada nilai mentah di komponen dan tidak ada `style="..."` di markup admin.

## Masuk & membuat akun

- `admin/login.php` — email + kata sandi, rate limit 6 percobaan / 10 menit.
  Akun demo lokal ada di `<details>` halaman masuk dan tidak lagi mengisi kolom
  otomatis; email yang salah tetap dipertahankan agar tidak perlu mengetik ulang.
- URL login/daftar disimpan relatif (`login.php`, `register.php`) agar situs tetap
  jalan di subfolder seperti `/Laundry%20Kita/`.
- `admin/setup.php` — buat pemilik pertama (nama, email, sandi + konfirmasi,
  daftar syarat sandi yang menyala saat terpenuhi). Hanya aktif saat belum ada
  admin.
- `admin/account.php` — ubah profil, ganti sandi, tambah editor/pemilik.

## Akun pelanggan (situs publik)

- `register.php` — daftar akun: nama, email, WhatsApp, kata sandi + konfirmasi.
  Syarat sandi (10 karakter, ada huruf dan angka) menyala langsung saat terpenuhi.
- `login.php` — masuk dengan email + sandi, rate limit 8 percobaan / 10 menit,
  pesan galat `role="alert"`, email yang salah dipertahankan.
- `akun.php` — ringkasan akun: total/sedang berjalan/selesai, daftar pesanan
  dengan linimasa tujuh tahap + waktu per tahap, catatan outlet, dan formulir
  klaim pesanan lama.
- `logout.php` — hanya menerima POST + token CSRF.
- Pesanan baru dari form jadwal otomatis menempel ke akun bila pelanggan sudah
  masuk. Pesanan lama diklaim dengan kode pesanan **dan** nomor WhatsApp yang sama.
- Tabel `customers` + kolom `orders.customer_id` dibuat oleh migrasi otomatis di
  `config/db.php` (`lk_db_migrate`), jadi database lama tidak perlu direset.
- Tampilan memakai `assets/css/account.css` (var dari `main.css`) dan
  `assets/js/account.js`; tidak ada nilai warna/huruf mentah di markup.
- Halaman masuk/daftar punya lembar sendiri, `assets/css/auth.css`, berisi
  **satu lembar isian dan satu kolom data loket**. Kiri: judul 30–36 px, satu
  kalimat, lalu kartu isian 440 px. Kanan: `acct_auth_facts()` membaca
  `config/site.php` dan menampilkan tarif kiloan, waktu pengerjaan, empat slot
  jemput, dan desa jangkauan sebagai baris label–nilai bergaris rambut. Baris
  yang masih placeholder dilewati. Kolom data pindah ke bawah kartu di bawah
  900 px.
- Sketsa tangan di kolom kanan: `acct_auth_doodle()` menggambar satu adegan
  loket 340 x 275 (tali jemuran berisi kaos, handuk, kaus kaki, keranjang
  cucian, mesin cuci depan, gelembung sabun) dengan garis bertingkat dan bidang
  warna datar muda, tanpa gradasi. Semua garis ditarik sekali saat halaman
  dibuka (`pathLength` + `stroke-dashoffset`) lalu warnanya menyusul, kain
  bergoyang dari jepitannya, gelembung naik pelan, dan drum mesin berputar
  balik selama formulir dikirim (`body.is-washing`). Empat lipatan di keranjang
  menyala sesuai jumlah kolom yang sudah terisi. Satu tarikan kuas kuning di
  bawah judul dari `acct_auth_underline()`. Semua gerak berhenti di
  `prefers-reduced-motion: reduce` dan sketsa langsung tampil utuh.
- Tidak ada tiket animasi, judul display raksasa, atau panggung gelap lagi:
  `assets/js/auth.js` (tiket + jam WIB) dihapus, halaman hanya memakai
  `assets/js/account.js` untuk tampilkan sandi, syarat sandi, dan kunci kirim.
- Halaman auth memakai kerangka sendiri (`acct_auth_page_start()` /
  `acct_auth_page_end()`) yang memuat `auth.css` saja, jadi tidak menimpa gaya
  `akun.php` yang masih memakai `account.css`.
- Komponen bersama ada di `includes/account-ui.php` (`acct_page_start`,
  `acct_auth_page_start`/`acct_auth_page_end`, `acct_auth_facts`,
  `acct_sheet_start`/`acct_sheet_end`, `acct_field`, `acct_leader_row`,
  `acct_alert`).
- Micro-interaksi dibatasi pada umpan balik: isi halaman memudar masuk sekali,
  kotak syarat sandi menyala saat terpenuhi, tombol berubah menjadi
  "Memproses…" + spinner saat dikirim, dan angka statistik menghitung naik.
  Tidak ada gradasi, kilau, atau denyut berulang; semuanya berhenti di bawah
  `prefers-reduced-motion: reduce`.

## Struktur file

```
index.php                     Markup + data tampilan halaman publik.
login.php                     Masuk akun pelanggan.
register.php                  Daftar akun pelanggan.
akun.php                      Ringkasan pesanan + klaim pesanan lama.
logout.php                    Keluar dari akun pelanggan (POST + CSRF).
config/site.php               Nilai awal/fallback seluruh konten bisnis.
config/database.php           Kredensial MySQL.
config/db.php                 Koneksi PDO, auto-create DB, auto-migrasi + seed.
database/schema.sql           Skema tabel CMS + customers (dijalankan otomatis).
includes/bootstrap.php        Session publik, merge konten CMS, auth, sequence.
includes/helpers.php          e(), rupiah(), CSRF, JSON, pembersih input.
includes/customer.php         Sesi, daftar, masuk, klaim pesanan pelanggan.
includes/account-ui.php       Kerangka halaman akun, kartu tiket, blok status.
includes/sequence.php         Pemindai frame hero (natural sort, WebP menang).
includes/pickup.php           Validasi + handler form jadwal.
includes/repository.php       Merge konten DB ↔ config, status pesanan, simpan order.
assets/css/main.css           CSS publik + micro-interactions.
assets/css/auth.css           Halaman masuk & daftar: lembar isian, kolom data loket, sketsa SVG.
assets/css/account.css        Komponen halaman ringkasan pesanan (akun.php).
assets/js/main.js             JS publik (Lenis, GSAP, kalkulator, form, dll).
assets/js/account.js          Tampilkan sandi, syarat sandi, fokus isian.
sequence/*.webp               Frame hero (75 frame, 1280x720) yang dipakai situs.
tools/sequence-webp.sh        Ubah ulang sequence/*.png jadi WebP q88.
go-laundry.png                Logo resmi GO Laundry (dipakai langsung di navbar, favicon, dan admin).
assets/img/reviews/           Foto profil anonim untuk bagian ulasan (720 px, wajah tidak tampak).


admin/bootstrap.php           Session admin + require seluruh dependensi.
admin/includes/auth.php       Login, CSRF admin, rate limit, audit, flash.
admin/includes/ui.php         Layout, sidebar, ikon, komponen.
admin/assets/tokens.css       Token desain: warna, huruf, jarak, radius, bayangan, gerak.
admin/assets/admin.css        Komponen dashboard; hanya memakai token.
admin/assets/admin.js         Drawer, toast, dialog konfirmasi, busy state, reveal sandi.
admin/setup.php               Instalasi: buat akun pemilik pertama.
admin/login.php               Masuk admin.
DESIGN.md                     Dokumentasi design system admin (token, komponen, aturan).
admin/logout.php              Keluar.
admin/index.php               Ringkasan: statistik, grafik 7 hari, pesanan terbaru.
admin/orders.php              Daftar pesanan, filter, pencarian, ekspor CSV.
admin/order.php               Detail pesanan + ubah status + riwayat.
admin/services.php            CRUD layanan & harga (langsung ke kalkulator).
admin/content.php             Promo, area, slot, jam, testimoni, jaminan.
admin/settings.php            Identitas bisnis, meta, auth, legal, sequence, stats.
admin/account.php             Profil, kata sandi, tim admin (owner/editor).
```

## Frame hero (WebP)

Hero memakai 75 frame 1280x720 dari `sequence/`. Yang di-commit hanya versi WebP
(3,8 MB), sedangkan PNG asli (48 MB) tetap di lokal dan di-ignore git.

- `includes/sequence.php` mendeteksi frame otomatis dan mendahulukan WebP,
  jadi menambah atau mengganti frame cukup dengan menaruh berkasnya.
- Kualitas konversi 88 (SSIM ~0,992 terhadap PNG) dengan `cwebp -m 6`.
- Ubah ulang setelah menambah PNG:

```bash
tools/sequence-webp.sh        # default q88
tools/sequence-webp.sh 92     # kalau butuh lebih tajam
```

## Ulasan anonim (carousel)

Tiga kartu ulasan tampil sebagai carousel sederhana di bawah statistik.

- Satu kartu per tampilan: potret anonim, satu kutipan pendek, dan baris "Anonim · layanan".
- Navigasi lewat tombol panah, titik indikator, tombol keyboard kiri/kanan, dan geser di layar sentuh.
- Kutipan placeholder tidak dikarang: kartu menampilkan "Ulasan layanan … segera hadir." dengan label **Contoh** sampai admin mengisi kutipan asli di **Konten → Testimoni**.
- Wajah dan nama pelanggan selalu disamarkan; nama asli tidak pernah dirender.

## Modul admin

| Modul | Yang bisa diubah | Efek di halaman publik |
|---|---|---|
| Ringkasan | — | Statistik pesanan, grafik 7 hari, jadwal hari ini |
| Pesanan | Status + catatan | Riwayat tersimpan, status tampil di daftar |
| Layanan & Harga | Harga, minimal, langkah, durasi, aktif | Kalkulator + tabel harga |
| Promo | Diskon, area gratis, voucher, catatan | Tiket promo |
| Area & Slot | Desa layanan, slot jam | Select desa + slot di formulir |
| Jam | Jam outlet & slot kurir | Bagian jangkauan |
| Testimoni / Jaminan | Kutipan, nama, rating, komitmen | Bagian bukti sosial |
| Pengaturan | Kontak, meta, legal, auth, stats | Header/footer/meta/CTA |
| Akun & Tim | Profil, sandi, admin baru | Akses dashboard |

Perubahan konten langsung dipakai request berikutnya — tidak perlu deploy ulang.
Konten yang masih placeholder tetap ditampilkan sebagai placeholder agar situs
tidak mengarang data bisnis.

## Alur pesanan

`baru → dikonfirmasi → dijemput → proses → siap → diantar → selesai`
(tersedia juga `batal`). Setiap perubahan dicatat di `order_events` beserta
admin dan catatan; ringkasan aktivitas ada di dashboard.

## Motion dashboard

- Halaman masuk dengan fade halus, kartu dan baris tabel menyusul berurutan.
- Angka statistik menghitung naik sekali (dilewati saat `prefers-reduced-motion`).
- Bar chart tumbuh dari bawah; hover tetap bekerja setelah animasi selesai.
- Pindah halaman memakai progress bar tipis 2 px + konten meredup 160 ms.
- Salin kode pesanan berubah menjadi "Tersalin" selama 1,4 detik.
- Dialog konfirmasi, tab aktif, marker sidebar, dan toast punya gerak masuk sendiri.
- Semua animasi/transition dimatikan oleh blok `prefers-reduced-motion` di akhir
  `admin/assets/admin.css`; tidak ada konten yang bergantung pada animasi.

## Keamanan

- Kata sandi `password_hash()` + `password_verify()`, sesi digenerate ulang saat
  login dan saat ganti sandi.
- CSRF token 2 jam di semua form admin dan form publik.
- Rate limit login 6 percobaan / 10 menit per sesi + jeda 250 ms.
- Rate limit form publik 6 kiriman / 10 menit + honeypot.
- Semua query pakai prepared statement; output di-escape dengan `e()`.
- Dashboard `noindex, nofollow`; `config/` dan `database/` ditutup lewat
  `.htaccess` (Apache).
- Role: `owner` (semua) dan `editor` (tanpa kelola tim). Pemilik terakhir tidak
  bisa dihapus.

Untuk produksi: ganti password MySQL, aktifkan HTTPS, dan batasi akses
`/admin/` lewat firewall/VPN bila perlu.

## Reset / pemulihan

- Lupa sandi pemilik: buat ulang lewat phpMyAdmin —
  `DELETE FROM admin_users;` lalu buka `/admin/setup.php` (akun demo juga
  dibuat ulang otomatis saat database di-seed dari nol).
- Cadangkan data:
  `mysqldump -u root laundry_kita > backup.sql`
- Hapus semua data pesanan:
  `DELETE FROM order_events; DELETE FROM orders;`

## Catatan pengembangan

- Halaman publik **tidak wajib** MySQL: bila koneksi gagal, situs memakai
  `config/site.php` dan tetap hidup. Dashboard menampilkan error yang jelas.
- Jangan mengubah `service_key` setelah ada pesanan; kunci itu dipakai untuk
  membaca layanan pada riwayat.
- File `database/schema.sql` hanya berisi DDL; seed diambil dari `config/site.php`.
- Butuh PHP 8.1+. Jalankan lokal: `php -S 127.0.0.1:8000`.
