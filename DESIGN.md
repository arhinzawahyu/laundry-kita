# GO Laundry — Design System (Admin CMS)

Sistem desain untuk dashboard admin dan halaman masuk/instalasi. Halaman publik
punya stylesheet sendiri (`assets/css/main.css`); keduanya berbagi identitas yang
sama: kertas hangat, ungu tua, dan aksen teal.

Sasaran permukaan ini adalah **Operate**: orang datang untuk menyelesaikan
pekerjaan (melihat pesanan, mengubah harga, mengatur konten). Karena itu bahasa
visualnya tenang, rapat, dan dapat diprediksi — bukan tempat eksperimen.

---

## 1. Sumber kebenaran

| Berkas | Isi | Boleh diubah? |
|---|---|---|
| `admin/assets/tokens.css` | Semua nilai: warna, huruf, jarak, radius, bayangan, gerak, z-index | Ya — hanya di sini |
| `admin/assets/admin.css` | Komponen yang memakai token | Ya — tetapi tanpa nilai mentah baru |
| `admin/assets/admin.js` | Perilaku: drawer, toast, dialog, reveal sandi, syarat sandi, progress | Ya |
| `admin/includes/ui.php` | Markup layout, ikon, komponen PHP | Ya |

**Aturan inti:** komponen tidak menulis warna, ukuran huruf, atau jarak sendiri.
Kalau sebuah nilai belum ada, tambahkan token baru lebih dulu, lalu pakai.

```css
/* benar */
.card { border-radius: var(--r-lg); padding: var(--sp-5); color: var(--text); }

/* salah — nilai mentah di komponen */
.card { border-radius: 14px; padding: 20px; color: #241e2e; }
```

Lapisan token: **palette mentah** (`--ink-900`, `--deep-500`, `--paper-300`)
hanya dipakai di `tokens.css`. Komponen memakai **token semantik**
(`--surface-card`, `--text-muted`, `--border`, `--action-bg`).

---

## 2. Warna

### Permukaan
| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--surface` | `#f5f3ed` | Latar halaman |
| `--surface-card` | `#ffffff` | Kartu, tabel, dialog, input |
| `--surface-raised` | `#fcfbf7` | Hover baris, footer kartu |
| `--surface-sunken` | `#faf8f3` | Kepala tabel |
| `--surface-quiet` | `#f3f0e9` | Kode, tombol diam saat hover |
| `--surface-disabled` | `#f5f3ed` | Input nonaktif / read-only |

### Teks
| Token | Nilai | Kontras di atas kartu |
|---|---|---|
| `--text-strong` / `--text` | `#241e2e` | 14.6:1 |
| `--text-muted` | `#6e6879` | 5.4:1 |
| `--text-faint` | `#766f81` | 4.8:1 (placeholder) |

### Garis
`--border` `#e5e0d7` untuk pemisah umum, `--border-strong` `#d4cec2` untuk garis
input dan batas yang perlu terlihat, `--border-soft` `#efebe3` untuk garis baris tabel.

### Aksi dan fokus
| Token | Nilai | Catatan |
|---|---|---|
| `--action-bg*` | `#5a4687 → #473472 → #3f2d68` | Gradien tombol utama; teks putih 10.5:1 |
| `--action-edge` | `#2e2149` | Bayangan bawah 3D tombol utama |
| `--focus` | `#53629e` | Cincin fokus 2 px + `--focus-ring` |

### Status (teks / latar / garis)
| Status | Teks | Latar | Kontras |
|---|---|---|---|
| Sukses | `--ok-fg` `#2c7a57` | `--ok-bg` `#e4f5ec` | 4.6:1 |
| Peringatan | `--warn-fg` `#965f16` | `--warn-bg` `#fbf0de` | 4.7:1 |
| Bahaya | `--danger-fg` `#b0443b` | `--danger-bg` `#fbe9e6` | 4.8:1 |
| Info | `--info-fg` `#4c5f94` | `--info-bg` `#e9eef8` | 5.4:1 |

Status selalu tampil sebagai **tiga serangkai** (teks + latar + garis), bukan warna
teks saja — supaya tetap terbaca tanpa persepsi warna.

### Permukaan gelap (sidebar, panel auth)
`--deep-surface-gradient`, `--text-on-deep` (`#f1eef7`, 12.8:1),
`--text-on-deep-muted` (0.72), `--text-on-deep-faint` (0.56 → 5.0:1),
`--border-on-deep`, `--deep-hover`.

---

## 3. Tipografi

Dua keluarga: **Plus Jakarta Sans** untuk judul dan angka (`--font-head`),
**Inter** untuk teks (`--font-body`), dan **monospace** untuk kode
(`--font-mono`). Sembilan ukuran saja:

| Token | px | Dipakai untuk |
|---|---|---|
| `--fs-2xs` | 10.5 | Label huruf besar, tanggal grafik, badge |
| `--fs-xs` | 11.5 | Keterangan, error field, footer kartu |
| `--fs-sm` | 12.5 | Isi tabel, tombol, label form, nav |
| `--fs-md` | 13.5 | Teks isi, input, subjudul halaman |
| `--fs-base` | 14 | Paragraf dasar body |
| `--fs-lg` | 16 | Judul kartu, dialog |
| `--fs-xl` | 20 | Judul halaman (batas bawah clamp) |
| `--fs-2xl` | 26 | Judul halaman, judul auth |
| `--fs-3xl` | 34 | Judul panel auth |

Bobot: 400 / 500 / 600 / 700 / 800 (`--fw-*`). Line-height: `--lh-tight` 1.1
(judul besar), `--lh-snug` 1.25 (judul sedang), `--lh-base` 1.55 (teks),
`--lh-relaxed` 1.65 (paragraf panjang). Tracking: `--track-tight` -0.025em untuk
judul ≥20 px, `--track-wide` 0.08em dan `--track-wider` 0.14em untuk label
huruf besar.

Aturan: angka statistik dan kolom uang selalu `font-variant-numeric: tabular-nums`.

---

## 4. Jarak, radius, elevasi

**Jarak** memakai kelipatan 4 px: `--sp-1` 4, `--sp-2` 8, `--sp-3` 12,
`--sp-4` 16, `--sp-5` 20, `--sp-6` 24, `--sp-8` 32, `--sp-12` 48. Nilai setengah
langkah (`--sp-1-5` 6, `--sp-2-5` 10, `--sp-3-5` 14) hanya untuk kontrol kecil.

Pola yang dipakai konsisten:
- padding kartu `--sp-5` (20 px), kepala/footer kartu `--sp-4`/`--sp-5`
- jarak antar kartu `--sp-4` (16 px)
- jarak antar field `--sp-4`, label ke input `--sp-1-5`
- padding halaman `--sp-6` vertikal, `--sp-8` horizontal

**Radius**: `--r-xs` 6 (kode, ikon kecil), `--r-sm` 8 (input, tombol kecil),
`--r-md` 10 (tombol, ikon), `--r-lg` 14 (kartu), `--r-xl` 18 (kartu auth, dialog),
`--r-pill` untuk chip dan badge.

**Elevasi**: `--elev-1` (garis halus), `--elev-2` (kartu), `--elev-3` (kartu
mengambang, toast), `--elev-4` (dialog). Tombol utama memakai `--elev-action`
(bayangan 3D dua lapis) dan turun saat `:active`.

Bayangan selalu netral (tinta `rgba(36,30,46,…)`), tidak berwarna, kecuali
bayangan aksi utama yang membawa rona ungu.

---

## 5. Gerakan

| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--dur-1` | 120 ms | Hover baris tabel, warna |
| `--dur-2` | 180 ms | Hover tombol, buka-tutup kecil |
| `--dur-3` | 260 ms | Dialog, switch, auth masuk |
| `--dur-4` | 420 ms | Animasi halaman |
| `--ease-out` | `cubic-bezier(.16,1,.3,1)` | Hampir semua gerak |
| `--ease-spring` | `cubic-bezier(.34,1.56,.64,1)` | Knob switch |

Aturan: gerak menjelaskan perubahan status, bukan menghibur. Tidak ada animasi
yang menunda aksi, dan semua durasi menjadi ~0 di bawah
`prefers-reduced-motion: reduce` (blok di akhir `admin.css`).

Micro-interaksi halaman auth (hanya pada `.auth`):

- `.auth__card` masuk dengan `authIn` (420 ms, naik 10 px) sekali saat halaman dibuka.
- Label isian berubah warna saat fokus (`:focus-within`), pesan galat memudar
  turun (`fieldErrIn`, 180 ms).
- Tombol `Tampilkan` kata sandi mengecil 4% saat ditekan.
- Titik syarat sandi `.pwreq li.is-met` memantul sekali (`pwDotPop`, 320 ms),
  bukan animasi berulang.

---

## 6. Komponen

### Tombol — `.abtn`
`--primary` (aksi utama, satu per layar), `--ghost` (aksi sekunder),
`--quiet` (aksi tersier dalam baris), `--danger` (hapus). Ukuran: `--sm` 32 px,
default 40 px, `--lg` 44 px. `--block` selebar wadah, `--icon` persegi.
Status: `:hover` naik 1 px, `:active` turun 2 px, `[disabled]` 55% opasitas,
`.is-busy` menampilkan spinner dan mengunci klik (dipakai lewat atribut
`data-busy` pada form submit).

### Field — `.field`
Anatomi: `label` (+`<span>` keterangan) → kontrol → `.field__hint` atau
`.field__err`. Varian: `.field--full` selebar grid, `.field__wrap` +
`.field__reveal` untuk tombol tampilkan sandi, `.has-error` + `aria-invalid="true"`
untuk galat, `.pwreq` untuk daftar syarat sandi yang menyala saat terpenuhi.
Input memakai `--ctl-input` 42 px; fokus memakai `--focus` + `--focus-ring`.

### Kartu — `.card`
`.card__head` (judul + subjudul + aksi) dan `.card__body`. Header tidak wajib
kalau isi kartu hanya satu blok.

### Tabel — `.table` + `.tablewrap`
Header huruf besar 10.5 px di atas `--surface-sunken`; baris 12.5 px, hover
`--surface-raised`, garis `--border-soft`. Kolom angka memakai `.num`
(rata kanan + tabular). Bungkus selalu dengan `.tablewrap` agar bisa digeser.

### Status & filter
`.pill` (info/ok/warn/danger/accent) untuk status data; `.chip` untuk filter yang
bisa dipilih; `.tab` untuk pindah sub-halaman; `.badge` angka di navigasi memakai
`.nav__badge`.

### Umpan balik
`.toast` (sukses/galat, bisa ditutup), `.confirm` (dialog `showModal`),
`.hintbox` (catatan netral), `.empty` (keadaan kosong dengan ikon + judul + satu
kalimat), `.auth__alert` (galat form).

### Cangkang auth — `.auth`
Dua kolom di desktop (`.auth__art` gelap + `.auth__panel`), satu kolom di
≤900 px dengan `.auth__brand` muncul di atas kartu. Kartu: `.auth__head`
(eyebrow, `h1`, lead) → `.auth__body` (alert, form, detail demo) →
`.auth__cardfoot` (tautan keluar + catatan). Halaman instalasi memakai
`.auth.auth--single` dengan kartu yang sama, jadi pengguna melihat satu keluarga
tampilan dari instalasi sampai login.

---

## 7. Aksesibilitas

- Kontras teks minimum 4.5:1 (lihat tabel; `--text-faint` hanya untuk placeholder).
- Fokus selalu terlihat: `:focus-visible` 2 px `--focus`; input memakai cincin
  `--focus-ring`; di permukaan gelap pakai `--focus-ring-on-deep`.
- Galat form: `.has-error` + `aria-invalid="true"`, pesan `.field__err`, ringkasan
  memakai `role="alert"`.
- Ikon dekoratif `aria-hidden="true"`; tombol ikon wajib punya `aria-label`
  (contoh: tombol tampilkan sandi, menu sidebar).
- `.skip-link` di setiap halaman, `.sr-only` untuk teks yang hanya dibaca mesin.
- Target sentuh minimal 32 px (`.abtn--sm`, `.iconbtn`), 40 px untuk aksi utama.
- `prefers-reduced-motion` mematikan seluruh transisi dan animasi.

---

## 8. Menambah halaman baru

1. `require __DIR__ . '/bootstrap.php';` lalu `admin_require_login()` / `admin_require_owner()`.
2. Bungkus isi dengan `admin_layout(...)` dari `admin/includes/ui.php`.
3. Judul halaman: `.page__head` + `h1` + satu kalimat penjelas + `.page__actions`.
4. Isi memakai `.card`, `.formgrid`, `.table`, `.empty` — jangan membuat komponen baru
   kalau salah satu sudah cocok.
5. Cek di 1360 px dan 390 px, plus `prefers-reduced-motion`.

---

## 9. Larangan

- Tidak ada `#hex`, `rgb()`, atau `px` untuk warna/jarak/huruf di `admin.css`.
- Tidak ada `style="..."` di markup admin (semua lewat kelas).
- Tidak menambah ukuran huruf baru di luar sembilan langkah skala.
- Tidak memakai warna status sebagai latar teks panjang; status hanya untuk penanda.
- Tidak menaruh lebih dari satu `.abtn--primary` dalam satu layar.

---

## 10. Verifikasi terakhir

Diukur lewat Chrome headless (1360×900 dan 390×844):

- `document.documentElement.scrollWidth == window.innerWidth` di semua halaman
  (tidak ada overflow horizontal).
- Tidak ada request CSS/aset yang gagal (`tokens.css` termuat).
- Halaman diperiksa: Ringkasan, Pesanan, Layanan, Konten, Pengaturan, Akun,
  Masuk, dan Instalasi.

---

## 11. Permukaan akun pelanggan (situs publik)

Halaman `login.php` dan `register.php` memakai `assets/css/auth.css` dengan
variabel dari `main.css`; `akun.php` tetap memakai `assets/css/account.css`.
Halaman auth memakai satu ide: **satu lembar isian, satu kolom data loket**.

- **Susunan.** `.auth-shell` = `minmax(0, 440px) minmax(0, 380px)` dengan
  `justify-content: space-between`; kiri judul dan kartu isian, kanan
  `.auth-facts`. Di bawah 900 px menjadi satu kolom dan kolom data turun ke
  bawah kartu.
- **Kartu isian.** Putih, garis rambut `--acct-edge-soft`, radius 12 px, padding
  32 px. Input 48 px, radius 8 px, cincin fokus 3 px ungu tua. Tombol
  `.acct-submit` selebar kartu, keadaan `Memproses…` dikunci lewat
  `aria-disabled`.
- **Kolom data.** `acct_auth_facts()` membaca `config/site.php`: tarif kiloan,
  waktu pengerjaan, empat slot jemput, dan jangkauan desa. Baris label–nilai
  bergaris rambut, nilai rata kanan dengan angka tabular; di bawah 520 px label
  menumpuk di atas nilai. Baris placeholder dilewati, bukan dikarang.
- **Tipografi.** Satu keluarga huruf (Poppins 400/500/600). Judul 30–36 px
  semibold, isi 16 px, label dan keterangan 12–14 px. Tanpa Bebas Neue dan tanpa
  huruf miring.
- **Warna.** Kertas `--paper`, tinta `--ink`, satu aksen `--deep`. Galat memakai
  `#8F2614` di atas `#FCF3F1`. Teks sekunder 7,2:1 pada kertas, placeholder
  5:1 pada putih.
- **Sketsa tangan.** `acct_auth_doodle()` menggambar satu adegan selebar
  lembar (1080 x 320) di atas dua kolom: ruko laundry beratap limas dengan
  kanopi bergerigi, pintu, dan jendela kaca berisi mesin cuci depan; jemuran
  antara dua tiang berisi kaos, handuk, dan dua kaus kaki; meja timbang
  gantung pada tiang kanan; keranjang cucian anyam. Alasan: loket laundry
  memang berisi benda itu, dan gambar tangan membuat halaman terasa dibuat
  orang, bukan template. Ketebalan garis bertingkat (badan 2,2 px, kain dan
  jeruji 2,2 px, kerut dan tenunan 1,2–1,4 px), ujung dan sambungan bulat.
  Tinta `--ink`, dua aksen: biru `#53629E` untuk kain dan kuning `#D9A227`
  untuk gelembung serta tarikan kuas judul. Bidang kain, kanopi, dinding, dan
  drum diisi warna datar sangat muda (`#E3E7F4`, `#EBEEF8`, `#EDEFF7`,
  `#F6F7FC`) setelah garisnya selesai ditarik, seperti mewarnai sketsa. Tanpa
  gradasi. Pita gambar memakai `grid-column: 1 / -1` sehingga selebar lembar
  di desktop, dan turun ke atas kartu isian di bawah 900 px.
- **Keranjang sebagai penanda isian.** Empat lipatan cucian di keranjang
  mewakili empat perempat isian wajib; `account.js` menambah `.is-filled` pada
  lipatan sesuai jumlah kolom yang sudah terisi. Alasan: memberi umpan balik
  kemajuan tanpa menambah elemen UI baru. Penanda ini `aria-hidden` dan murni
  hiasan; validasi tetap lewat pesan galat per kolom.
- **Butir kertas.** `.auth-body::after` menaruh satu lapis `feTurbulence`
  (opasitas 0,055, `multiply`) supaya kertas, tinta, dan gambar terasa satu
  bahan. Bukan pola dekoratif, tidak menurunkan kontras di bawah AA (teks
  sekunder tetap 6,3:1 pada kertas yang paling gelap oleh tekstur).
- **Gerak.** Garis ditarik sekali lewat `pathLength="1"` +
  `stroke-dashoffset` (920 ms), warna menyusul 460 ms; kaos, handuk, dan kaus
  kaki bergoyang ±1,7° dari jepitan (6,4 detik,
  `cubic-bezier(0.45, 0, 0.55, 1)`, dua fase berbeda); gelembung naik 44 px
  selama 9 detik; `.auth-drum` berputar bolak balik saat `body.is-washing`
  (dipasang `account.js` ketika formulir dikirim). Kartu dan kolom data naik
  16 px sambil memudar (500 ms, `--ease`), sekali saja. Semua gerak hanya pada
  `transform` dan `opacity`, dan semuanya dimatikan pada
  `prefers-reduced-motion: reduce` (sketsa langsung tampil utuh). Tidak ada
  tiket terisi, jam berjalan, atau stempel.
- **Ikon baris data.** `acct_fact_icon()` memakai gaya garis yang sama, 16 px,
  supaya ikon tidak terasa dari pustaka lain. Judul diberi satu tarikan kuas
  `acct_auth_underline()`.
- **Kerangka sendiri.** `acct_auth_page_start()` / `acct_auth_page_end()`
  memuat `auth.css` saja (bukan `account.css`) supaya dua halaman ini tidak
  saling menimpa gaya dengan `akun.php`.
