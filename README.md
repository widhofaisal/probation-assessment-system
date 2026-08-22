# Sistem HRD - Penilaian Probation

Aplikasi manajemen penilaian karyawan masa probasi berbasis CodeIgniter 4.

---

## Teknologi

- **Backend**: CodeIgniter 4.7.2 (PHP 8.3+)
- **Database**: MySQL 8.0+
- **Frontend**: Tailwind CSS v3 (CDN), Font Awesome 6.4.0, Vanilla JS
- **Library**: DomPDF (generate PDF)

---

## Instalasi

### Prasyarat
- PHP 8.1+
- MySQL 8.0+
- Composer
- Laragon / XAMPP / server lokal lainnya

### Langkah Setup

**1. Siapkan source code & install dependencies**
```bash
# Dari paket ZIP: ekstrak, lalu masuk ke foldernya
cd hrd-system-ci

# Atau dari git:
# git clone <repo-url> && cd hrd-system-ci

composer install
```

> Folder `vendor/` sengaja tidak disertakan di paket ZIP karena isinya murni
> hasil unduhan Composer. `composer install` akan membuatnya.

**2. Konfigurasi environment**
```bash
cp .env.example .env      # Windows: copy .env.example .env
```
Buka `.env`, lalu sesuaikan minimal bagian berikut:
```
CI_ENVIRONMENT = development          # pakai "production" di server sungguhan
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = hrd_system
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

**3. Buat encryption key**
```bash
php spark key:generate
```
Perintah ini mengisi `encryption.key` di `.env` dengan nilai acak. Wajib
dijalankan, dan setiap instalasi harus punya key sendiri.

**4. Import database**
```bash
# Buat database terlebih dahulu
mysql -u root -e "CREATE DATABASE hrd_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema & seed data
mysql -u root hrd_system < database_dump.sql
```

**5. Jalankan server**
```bash
php spark serve
```

Akses di: `http://localhost:8080`

---

## Deployment ke Hosting (InfinityFree)

Codebase ini **plug-n-play** — file yang sama bisa jalan di lokal (`php spark serve`) maupun di shared hosting seperti InfinityFree, tanpa perlu mengubah `index.php`.

### Perbedaan Layout

| | Lokal | InfinityFree |
|---|---|---|
| Lokasi `app/`, `vendor/`, `writable/` | sejajar dengan `public/` | **di dalam** `htdocs/` (= `public/`) |
| Konfigurasi env | `.env` di project root (CI4 DotEnv) | `env.local.php` di `htdocs/` (inject `$_ENV` langsung — `putenv()` di-disable) |
| `RewriteBase` | tidak relevan (spark serve abaikan `.htaccess`) | `/` (sudah disetel) |

`public/index.php` melakukan **auto-detect** layout dan mekanisme env, jadi cukup pilih salah satu pola di atas.

### Langkah Deploy ke InfinityFree

**1. Upload file via FileZilla** ke `htdocs/`:

```
htdocs/
├── app/                ← upload dari ./app/
├── vendor/             ← upload dari ./vendor/
├── writable/           ← upload dari ./writable/
├── index.php           ← upload dari ./public/index.php
├── .htaccess           ← upload dari ./public/.htaccess
├── favicon.ico, favicon.svg, robots.txt
└── env.local.php       ← buat di langkah 2
```

> InfinityFree memberlakukan `open_basedir` yang membatasi PHP hanya bisa baca dari `htdocs/`, sehingga `app/`, `vendor/`, `writable/` **harus** berada di dalam `htdocs/`.

**2. Buat `env.local.php` di `htdocs/`** — dua cara:

- **Manual**: copy dari [env.local.php.example](env.local.php.example), edit kredensial database & `app.baseURL`, upload sebagai `htdocs/env.local.php`.
- **Otomatis**: edit nilai konstanta di [public/setup_env.php](public/setup_env.php) (database, baseURL, dst.) sebelum upload, lalu akses sekali di browser:
  ```
  https://your-domain.infinityfreeapp.com/setup_env.php?key=rahasia123
  ```
  File `env.local.php` akan ter-generate otomatis di `htdocs/`. **Hapus `setup_env.php` setelah selesai.**

**3. Import database** lewat phpMyAdmin (panel InfinityFree): import `database_dump.sql`.

**4. Verifikasi (opsional)** — akses script debug, lalu **hapus** setelah dipakai:
- `https://your-domain/debug_boot.php?key=rahasia123` — cek struktur folder & autoload
- `https://your-domain/check_writable.php?key=rahasia123` — cek izin tulis di `writable/`

**5. Hapus file sensitif dari server** setelah deploy stabil:
- `setup_env.php`
- `debug_boot.php`
- `check_writable.php`

> ⚠️ **Ganti `SETUP_KEY`** (`rahasia123`) di ketiga script di atas dengan string acak yang kuat sebelum upload.

### Keamanan

- `.env`, `env.local.php`, dan `database_dump.sql` sudah di-gitignore.
- `.htaccess` memblokir akses langsung ke folder `app/`, `vendor/`, `writable/`, dan file `.env`.
- Proteksi CSRF aktif untuk seluruh request POST/PUT/PATCH/DELETE.
- Kontrol akses dijaga filter di lapisan route, bukan pengecekan manual per method.
- Password awal akun baru dibuat acak dan hanya ditampilkan sekali.
- Percobaan login yang gagal dibatasi 5 per NIK dan 30 per IP dalam 15 menit.
- ID sesi diperbarui setiap kali login berhasil.

Rinciannya di [Kontrol Akses](#kontrol-akses) dan [Sebelum Dipakai Produksi](#sebelum-dipakai-produksi).

---

## Akun Default

**Seluruh akun demo memakai password yang sama: `password123`** — sudah
diverifikasi terhadap hash di dalam `database_dump.sql`.

| NIK | Nama | Role |
|-----|------|------|
| HRD001 | Siti Rahayu | HRD |
| TL001 | Ahmad Fauzi | Team Leader |
| TL002 | Dewi Anggraini | Team Leader |
| EMP001, EMP002, EMP003, EMP005, EMP006, EMP007, EMP008 | Karyawan | Probationary Employee |

`EMP004`, `EMP009`, dan `EMP010` ada di data karyawan tetapi **tidak punya akun
login** — ketiganya sudah dihapus saat data demo dibuat.

Daftar lengkapnya ada di [DEMO-ACCOUNTS.md](DEMO-ACCOUNTS.md).

> Akun demo di atas wajib dihapus sebelum sistem dipakai dengan data karyawan
> sungguhan.

---

## Fitur

### HRD
- Dashboard statistik karyawan & penilaian
- Manajemen karyawan probation (tambah, edit, hapus)
- Lihat semua penilaian dari semua Team Leader
- Mengisi Keputusan HRD setelah penilaian ke-2 selesai — inilah yang menentukan status akhir karyawan
- Manajemen user (tambah/edit/hapus HRD & Team Leader)
- Export data CSV
- Audit trail (log semua aktivitas)
- Generate laporan PDF

### Team Leader
- Dashboard tim & statistik penilaian
- Lihat daftar anggota tim
- Buat & edit penilaian karyawan
- Siklus penilaian: 2x selama probasi (setiap ~45 hari)

### Karyawan Probation
- Lihat hasil penilaian sendiri
- Download laporan PDF penilaian

---

## Struktur Roles & Alur

```
HRD
├── Menambah karyawan probation → akun login otomatis terbuat
├── Melihat semua penilaian
├── Mengisi Keputusan HRD pada penilaian ke-2 → status karyawan ikut berubah
└── Mengelola user (HRD & Team Leader)

Team Leader
├── Menilai karyawan yang ada di timnya
└── Maksimal 2x penilaian per karyawan selama probasi

Karyawan Probation
└── Melihat hasil penilaian sendiri
```

### Alur Akhir Masa Probasi

```
Team Leader menilai (ke-1)
        ↓
Team Leader menilai (ke-2)          → status karyawan masih `pending`
        ↓
HRD mengisi Keputusan HRD           → kotak "(Diisi oleh Dept. HRD)" pada form
   di /evaluations                     · Diangkat sebagai karyawan tetap per tanggal
                                       · Diakhiri masa kerjanya per tanggal
                                       · Lain-lain
                                       · Status akhir (lulus / tidak-lulus / warning)
        ↓
Status karyawan berubah + isian tercetak di PDF penilaian ke-2
```

> Status probation **hanya** bisa diubah lewat Keputusan HRD. Dropdown status di
> form data karyawan sengaja dikunci agar karyawan tidak bisa dinyatakan lulus /
> tidak lulus sebelum kotak HRD diisi.

### Status Karyawan

| Status | Keterangan |
|--------|-----------|
| `pending` | Masih dalam masa probasi, belum ada keputusan HRD |
| `lulus` | Dinyatakan lulus, lanjut jadi karyawan tetap |
| `tidak-lulus` | Tidak dilanjutkan |
| `warning` | Diberi kesempatan/peringatan, perlu evaluasi lanjut |

---

## Struktur Database

```
users               → Akun login (HRD, Team Leader, Probationary Employee)
employees           → Data karyawan masa probasi
penilaian           → Rekap penilaian per karyawan
penilaian_detail    → Nilai per aspek penilaian
penilaian_keputusan → Keputusan HRD atas penilaian ke-2 (kotak "Diisi oleh Dept. HRD")
audit_logs          → Log semua aktivitas sistem
```

### Aspek Penilaian (10 aspek, nilai 1–10)

| Kategori | Aspek |
|----------|-------|
| Kedisiplinan | Ketepatan waktu hadir, Kepatuhan peraturan, Kerapian penampilan |
| Kinerja | Kecepatan kerja, Kualitas hasil, Kemampuan memenuhi target |
| Sikap Kerja | Kerjasama tim, Komunikasi dengan atasan, Inisiatif & motivasi |
| Kompetensi | Penguasaan teknis pekerjaan |

---

## Struktur Direktori

```
app/
├── Controllers/
│   ├── AuthController.php        # Login & logout
│   ├── DashboardController.php   # Dashboard per role
│   ├── EmployeesController.php   # CRUD karyawan
│   ├── EvaluationsController.php # CRUD penilaian
│   ├── UsersController.php       # Manajemen user HRD & TL
│   ├── ReportsController.php     # PDF, CSV, audit trail
│   └── ProfileController.php     # Profil & ganti password
├── Models/
│   ├── UserModel.php
│   ├── EmployeeModel.php
│   ├── EvaluationModel.php
│   ├── EvaluationDetailModel.php
│   └── AuditLogModel.php
└── Views/
    ├── layouts/        # Layout utama & blank
    ├── Auth/           # Halaman login
    ├── Dashboard/      # Dashboard HRD, TL, karyawan
    ├── Employees/      # Daftar & detail karyawan
    ├── Evaluations/    # Form & hasil penilaian
    ├── Users/          # Manajemen user
    ├── Reports/        # Audit trail & laporan
    └── Profile/        # Profil pengguna

migration_from_postgres.sql   # Schema + seed data
```

---

## Kontrol Akses

Setiap route dijaga filter yang didaftarkan di [app/Config/Routes.php](app/Config/Routes.php):

| Filter | Arti |
|---|---|
| `auth` | wajib sudah login |
| `role:hrd` | wajib login dan role-nya HRD |
| `role:hrd,team-leader` | wajib login dan role-nya salah satu dari daftar |

Implementasinya di [app/Filters/AuthFilter.php](app/Filters/AuthFilter.php) dan
[app/Filters/RoleFilter.php](app/Filters/RoleFilter.php), didaftarkan sebagai
alias di [app/Config/Filters.php](app/Config/Filters.php).

**Route baru wajib diberi filter.** Route tanpa filter terbuka untuk siapa saja,
termasuk pengunjung yang belum login. Ada tes yang menjaga hal ini
(`testSemuaRoutePunyaFilterKecualiYangDikecualikan`) — kalau ada route baru yang
lupa difilter, tes itu gagal dan menyebutkan route mana saja.

Pengecekan role yang ada di dalam controller sengaja dipertahankan sebagai
lapisan kedua. Untuk sebagian endpoint, controller juga masih memeriksa hal yang
tidak bisa diketahui filter, yaitu **kepemilikan data** — misalnya Team Leader
hanya boleh membuka penilaian milik anggota timnya sendiri.

### CSRF

Filter `csrf` aktif global. Konsekuensinya:

- setiap `<form>` wajib memuat `<?= csrf_field() ?>`;
- setiap pemanggilan JavaScript yang mengubah data wajib memakai `csrfFetch()`
  (didefinisikan di [app/Views/layouts/main.php](app/Views/layouts/main.php)),
  bukan `fetch()` biasa — helper itu melampirkan header `X-CSRF-TOKEN`.

Request `GET` tidak butuh token dan boleh tetap memakai `fetch()` biasa.

---

## Pengujian

```bash
composer test              # seluruh tes
composer test:coverage     # dengan laporan coverage (butuh Xdebug)
```

Tesnya **tidak membutuhkan database**, jadi bisa langsung dijalankan setelah
`composer install`.

| Berkas | Isi |
|---|---|
| [tests/feature/KontrolAksesTest.php](tests/feature/KontrolAksesTest.php) | tamu diarahkan ke login, role salah dikembalikan ke dashboardnya, kelengkapan filter route |
| [tests/unit/PasswordAwalTest.php](tests/unit/PasswordAwalTest.php) | pembuatan password acak dan penyimpanannya sebagai hash |
| [tests/unit/PembatasanLoginTest.php](tests/unit/PembatasanLoginTest.php) | pembatasan percobaan login |
| [tests/unit/SiklusProbationTest.php](tests/unit/SiklusProbationTest.php) | aturan jadwal penilaian dan ringkasan dashboard |

---

## Sebelum Dipakai Produksi

Daftar periksa untuk instalasi dengan data karyawan sungguhan:

1. **Setel `CI_ENVIRONMENT = production`** di `.env`. Dengan `development`,
   setiap error menampilkan stack trace lengkap berikut kredensial database di
   halaman yang bisa dilihat pengunjung.
2. **Jalankan `php spark key:generate`.** Setiap instalasi harus punya
   `encryption.key` sendiri.
3. **Hapus seluruh akun demo** (lihat [DEMO-ACCOUNTS.md](DEMO-ACCOUNTS.md)), lalu
   buat akun HRD baru dengan password yang tidak bisa ditebak.
4. **Kosongkan `session.savePath` di `.env`.** Jangan diisi path relatif seperti
   `writable/session` — path relatif dihitung dari direktori kerja PHP yaitu
   `public/`, sehingga file sesi tertulis ke dalam web root dan berpotensi bisa
   diunduh lewat browser. Isi file sesi memuat `user_id` dan `role`.
5. **Arahkan document root ke folder `public/`**, bukan ke akar proyek. Kalau
   tidak bisa, pastikan `.htaccess` yang memblokir `app/`, `vendor/`, dan
   `writable/` benar-benar aktif.
6. **Aktifkan HTTPS** lalu setel `app.forceGlobalSecureRequests = true`.
7. **Turunkan `logger.threshold`** ke 3 atau lebih kecil agar file log tidak
   membengkak.
8. **Siapkan cadangan basis data** secara berkala.

---

## Catatan Pengembangan

- File `.env` tidak di-push ke git (berisi kredensial)
- `database_dump.sql` tidak di-push ke git (berisi data)
- Password awal user baru dibuat acak dan hanya ditampilkan sekali
- Soft delete aktif pada tabel `employees`
- Semua perubahan data dicatat di `audit_logs`
- Belum ada migration/seeder — skema dibuat dengan mengimpor `database_dump.sql`
