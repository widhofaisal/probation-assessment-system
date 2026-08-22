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

**4. Siapkan database** — ada dua jalur, pilih salah satu.

```bash
# Buat database kosong terlebih dahulu
mysql -u root -e "CREATE DATABASE hrd_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Jalur A — instalasi bersih (disarankan untuk pemakaian sungguhan)**

```bash
php spark migrate
```

Membuat seluruh tabel tanpa data apa pun. Karena belum ada akun sama sekali dan
akun hanya bisa dibuat dari dalam aplikasi oleh HRD, buat akun HRD pertama
dengan seeder:

```bash
php spark db:seed AkunHrdPertamaSeeder
```

Password acak akan ditampilkan sekali di terminal — catat sebelum menutupnya.
Akun tersebut ditandai wajib ganti password, jadi Anda akan diminta
menggantinya saat login pertama.

NIK dan nama bisa disesuaikan:

```bash
HRD_NIK=HRD001 HRD_NAMA="Nama Lengkap" php spark db:seed AkunHrdPertamaSeeder
```

**Jalur B — dengan data demo (untuk mencoba aplikasi)**

```bash
mysql -u root hrd_system < database_dump.sql
```

Membuat tabel sekaligus mengisi akun dan penilaian contoh. Akun demonya ada di
[DEMO-ACCOUNTS.md](DEMO-ACCOUNTS.md) dan **wajib dihapus** sebelum sistem dipakai
dengan data karyawan sungguhan.

> Kedua jalur menghasilkan struktur tabel yang identik — sudah diverifikasi
> kolom demi kolom beserta seluruh foreign key.

**5. Jalankan server**
```bash
php spark serve
```

Akses di: `http://localhost:8080`

---

## Deployment ke Server

**Panduan lengkapnya ada di [DEPLOYMENT.md](DEPLOYMENT.md)** — delapan langkah
dari memeriksa kesiapan server sampai login pertama, berikut daftar periksa
keamanan dan penanganan masalah yang sering muncul.

Ringkasnya, aplikasi ini mendukung dua susunan folder dan `public/index.php`
mengenali keduanya sendiri tanpa perlu diubah:

| | Susunan A (disarankan) | Susunan B |
|---|---|---|
| Kapan dipakai | document root bisa diarahkan ke subfolder | hosting mengunci web root, atau PHP dibatasi `open_basedir` |
| Letak `app/`, `vendor/`, `writable/` | sejajar dengan `public/`, di luar web root | di dalam folder publik |
| Konfigurasi | `.env` di akar proyek | `env.local.php` sejajar `index.php`, dipakai bila `putenv()` dimatikan |

Susunan A lebih aman karena kode dan berkas kerja berada di luar jangkauan
browser. Pada Susunan B, satu-satunya pelindung adalah `.htaccess` — dan itu
harus diverifikasi, bukan diasumsikan.

### Keamanan

- `.env`, `env.local.php`, dan `database_dump.sql` sudah di-gitignore.
- `.htaccess` memblokir akses langsung ke folder `app/`, `vendor/`, `writable/`, dan file `.env`.
- Proteksi CSRF aktif untuk seluruh request POST/PUT/PATCH/DELETE.
- Kontrol akses dijaga filter di lapisan route, bukan pengecekan manual per method.
- Password awal akun baru dibuat acak dan hanya ditampilkan sekali.
- Percobaan login yang gagal dibatasi 5 per NIK dan 30 per IP dalam 15 menit.
- ID sesi diperbarui setiap kali login berhasil.
- Password yang dibuatkan sistem wajib diganti pemiliknya sebelum aplikasi bisa dipakai.

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

### Aspek Penilaian

Nilai tiap butir 1-10. Nilai akhir adalah rata-rata seluruh butir.

| Kategori | Butir Penilaian |
|---|---|
| **A. Pengetahuan Akan Tugas (Knowledge)** | - Pengetahuan tentang penggunaan & pemeliharaan perangkat kerja (tools) e.g. mesin, komputer dll.<br>- Mengerti & memahami prosedur kerja standar (SOP) yang harus dijalankan.<br>- Mengerti & memahami standar kualitas kerja yang diterapkan perusahaan.<br>- Mengetahui proses pembuatan sepatu secara umum. |
| **B. Keahlian Kerja (Technical Skill)** | - Keahlian dalam menjalankan fungsi kerja utama (e.g. cutting, sewing dll.).<br>- Mampu mengoperasikan perangkat kerja (tools) e.g. mesin, kuas, lem dll.<br>- Bekerja sesuai dengan prosedur kerja standar (SOP) dengan benar/secara keseluruhan.<br>- Bekerja secara cepat & teliti sesuai dengan target (kuantitas dan kualitas).<br>- Mampu memenuhi standar kualitas kerja yang diterapkan oleh perusahaan.<br>- Pengelolaan & pemeliharaan perangkat kerja (tools) e.g. mesin, kuas, lem dll. |
| **C. Sikap Kerja (Attitude)** | - Mampu menjalankan disiplin kerja yang ada di departemen (e.g. jam kerja, seragam, APD dll.).<br>- Memiliki sikap dan perilaku kerja yang sesuai dengan NCOC.<br>- Menunjukkan sikap tidak mudah menyerah dalam menghadapi kesulitan saat bekerja sehari-hari.<br>- Jujur dalam menjalankan tugasnya. |
| **D. Kemampuan Diri (Interpersonal Skill)** | - Mampu bersosialisasi & bekerja sama dengan rekan kerja yang lain.<br>- Berani mengungkapkan pendapat kepada orang lain, baik rekan kerja ataupun atasan.<br>- Bersedia menerima masukan dan pendapat dari orang lain, baik rekan kerja atau atasan. |

Total 4 kategori, 17 butir penilaian, sesuai formulir penilaian probation yang berlaku.

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
├── Filters/
│   ├── AuthFilter.php            # wajib login + wajib ganti password
│   └── RoleFilter.php            # pembatasan berdasarkan role
├── Libraries/
│   ├── SiklusProbation.php       # aturan jadwal penilaian
│   ├── RingkasanDashboard.php    # ringkasan angka di dashboard
│   ├── SuratKeputusan.php        # isi & tata letak SK
│   ├── PdfCache.php              # cache berkas PDF
│   ├── KopSurat.php, SkAssets.php
├── Database/
│   └── Migrations/               # skema basis data, dijalankan `php spark migrate`
├── Models/
│   ├── UserModel.php
│   ├── EmployeeModel.php
│   ├── EvaluationModel.php
│   ├── EvaluationDetailModel.php
│   ├── EvaluationDecisionModel.php
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

tests/
├── feature/         # menembus route & filter
└── unit/            # aturan yang berdiri sendiri

database_dump.sql    # skema + data demo (jalur instalasi B)
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
| [tests/feature/KepemilikanDataTest.php](tests/feature/KepemilikanDataTest.php) | batas kepemilikan data antar pengguna dengan role yang sama |
| [tests/feature/WajibGantiPasswordTest.php](tests/feature/WajibGantiPasswordTest.php) | penegakan dan jalan keluar kewajiban ganti password |
| [tests/unit/AturanBisnisTest.php](tests/unit/AturanBisnisTest.php) | masa probation, kesiapan SK, penomoran SK, aspek penilaian |
| [tests/unit/SuratKeputusanTest.php](tests/unit/SuratKeputusanTest.php) | kelengkapan isi SK dan penulisan tanggal |
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
- Skema basis data ada di `app/Database/Migrations/` — jalankan `php spark migrate`
- Pengguna wajib mengganti password yang dibuatkan sistem sebelum bisa memakai aplikasi
