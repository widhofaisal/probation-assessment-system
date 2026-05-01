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
mysql -u root hrd_system < migration_from_postgres.sql
```

**4. Jalankan server**
```bash
php spark serve
```

Akses di: `http://localhost:8080`

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
├── Mengubah status karyawan (pending → lulus / tidak-lulus / warning)
└── Mengelola user (HRD & Team Leader)

Team Leader
├── Menilai karyawan yang ada di timnya
└── Maksimal 2x penilaian per karyawan selama probasi

Karyawan Probation
└── Melihat hasil penilaian sendiri
```

### Status Karyawan

| Status | Keterangan |
|--------|-----------|
| `pending` | Masih dalam masa probasi, belum ada keputusan |
| `lulus` | Dinyatakan lulus, lanjut jadi karyawan tetap |
| `tidak-lulus` | Tidak dilanjutkan |
| `warning` | Diberi kesempatan/peringatan, perlu evaluasi lanjut |

---

## Struktur Database

```
users           → Akun login (HRD, Team Leader, Probationary Employee)
employees       → Data karyawan masa probasi
penilaian       → Rekap penilaian per karyawan
penilaian_detail → Nilai per aspek penilaian
audit_logs      → Log semua aktivitas sistem
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
