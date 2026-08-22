# Akun Demo

Daftar akun berikut ikut terpasang saat `database_dump.sql` diimport. Semuanya
**data dummy** untuk keperluan pengujian — bukan karyawan sungguhan.

**Semua akun demo memakai password yang sama: `password123`.** Sudah diverifikasi
langsung terhadap hash di dalam `database_dump.sql`, dan dicoba login untuk
ketiga role.

> **Sebelum dipakai dengan data karyawan sungguhan:** hapus seluruh akun demo di
> bawah ini, lalu buat akun HRD baru dengan password yang tidak bisa ditebak.
> Selama akun demo masih aktif, siapa pun yang pernah membaca dokumen ini bisa
> masuk ke sistem.

---

## HRD

| NIK | Nama | Departemen | Posisi |
|---|---|---|---|
| HRD001 | Siti Rahayu | Human Resources | HR Manager |

## Team Leader

| NIK | Nama | Departemen | Posisi |
|---|---|---|---|
| TL001 | Ahmad Fauzi | Produksi | Supervisor Produksi |
| TL002 | Dewi Anggraini | Quality Control | Supervisor QC |

## Probationary Employee

| NIK | Nama | Departemen | Posisi |
|---|---|---|---|
| EMP001 | Budi Santoso | Produksi | Operator Produksi |
| EMP002 | Rina Marlina | Produksi | Operator Produksi |
| EMP003 | Joko Susilo | Produksi | Teknisi Mesin |
| EMP005 | Hendra Wijaya | Produksi | Teknisi Mesin |
| EMP006 | Fitri Handayani | Quality Control | Inspector QC |
| EMP007 | Rizal Maulana | Quality Control | Inspector QC |
| EMP008 | Yuni Astuti | Quality Control | Analis QC |

`EMP004`, `EMP009`, dan `EMP010` muncul di data karyawan tetapi **tidak punya
akun login** — ketiganya sudah dihapus saat data demo dibuat. Jadi nomor NIK
karyawan sengaja tidak berurutan.

---

## Bagaimana password akun baru dibuat

Password di tabel atas berlaku untuk data demo bawaan `database_dump.sql`, yang
dibuat ketika sistem masih memakai NIK sebagai password awal.

**Akun yang dibuat setelah ini tidak lagi begitu.** Saat HRD menambah user,
menambah karyawan, atau mereset password, sistem membangkitkan password acak
12 karakter dan menampilkannya **satu kali** di layar. Password tersebut tidak
disimpan dalam bentuk terbaca, jadi tidak bisa dilihat lagi setelah pesannya
ditutup — HRD perlu mencatat dan menyerahkannya ke pemilik akun.

Pesan yang memuat password sengaja tidak hilang sendiri dan harus ditutup manual,
supaya tidak keburu lenyap sebelum sempat dicatat.

Kode yang mengatur hal ini:

- `app/Models/UserModel.php` — `generatePassword()`
- `app/Controllers/UsersController.php` — pembuatan user dan reset password
- `app/Controllers/EmployeesController.php` — pembuatan akun karyawan

## Pembatasan percobaan login

Login yang gagal dibatasi: 5 kali per NIK dan 30 kali per alamat IP dalam
rentang 15 menit. Login yang berhasil tidak mengurangi jatah, jadi pengguna
yang tahu passwordnya tidak akan ikut terkunci. Batas per IP dibuat longgar
karena satu kantor umumnya keluar lewat satu IP publik yang sama.

Kalau perlu disesuaikan, konstantanya ada di bagian atas blok pembatasan pada
`app/Controllers/AuthController.php`.
