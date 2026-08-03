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

**1. Clone & install dependencies**
```bash
git clone <repo-url>
cd hrd-system-ci
composer install
```

**2. Konfigurasi environment**
```bash
cp env .env
```
Edit `.env`:
```
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = hrd_system
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

**3. Import database**
```bash
# Buat database terlebih dahulu
mysql -u root -e "CREATE DATABASE hrd_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema & seed data
mysql -u root hrd_system < database_dump.sql
```

**4. Jalankan server**
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

---

## Akun Default

| NIK | Nama | Role | Password |
|-----|------|------|----------|
| HRD001 | Siti Rahayu | HRD | password123 |
| TL001 | Ahmad Fauzi | Team Leader | password123 |
| TL002 | Dewi Anggraini | Team Leader | password123 |
| EMP001–EMP010 | Karyawan | Probationary Employee | (NIK masing-masing) |

> Password karyawan probation defaultnya adalah NIK mereka sendiri (contoh: EMP001).

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

## Catatan Pengembangan

- File `.env` tidak di-push ke git (berisi kredensial)
- `database_dump.sql` tidak di-push ke git (berisi data)
- Password default user baru = NIK mereka
- Soft delete aktif pada tabel `employees`
- Semua perubahan data dicatat di `audit_logs`
