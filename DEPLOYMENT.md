# Panduan Deploy — Sistem Penilaian Probation

Langkah memasang aplikasi di server Anda sendiri, dari berkas mentah sampai HRD
bisa login dengan aman. Perkiraan waktu 30–45 menit.

**Aplikasi**: CodeIgniter 4.7 · PHP 8.2+ · MySQL 8

---

## Urutan pengerjaan

1. [Periksa kesiapan server](#1-periksa-kesiapan-server)
2. [Siapkan berkas aplikasi](#2-siapkan-berkas-aplikasi)
3. [Tentukan letak berkas di server](#3-tentukan-letak-berkas-di-server)
4. [Atur izin tulis folder `writable/`](#4-atur-izin-tulis-folder-writable)
5. [Buat berkas konfigurasi](#5-buat-berkas-konfigurasi)
6. [Bangkitkan kunci enkripsi](#6-bangkitkan-kunci-enkripsi)
7. [Siapkan basis data](#7-siapkan-basis-data)
8. [Uji dan login pertama](#8-uji-dan-login-pertama)

Setelah itu: [daftar periksa](#daftar-periksa-sebelum-diserahkan-ke-hrd) ·
[penanganan masalah](#kalau-ada-yang-tidak-beres) ·
[catatan operasional](#catatan-operasional)

---

## 1. Periksa kesiapan server

Pastikan semuanya tersedia sebelum mengunggah apa pun. Kekurangan satu ekstensi
PHP baru akan terlihat sebagai halaman kosong setelah semua berkas terpasang,
dan itu jauh lebih sulit ditelusuri.

| Kebutuhan | Keterangan |
|---|---|
| **PHP 8.2** atau lebih baru | Aplikasi tidak berjalan di PHP 8.1 ke bawah |
| Ekstensi `intl` | Wajib untuk CodeIgniter 4 |
| Ekstensi `mbstring` | Wajib untuk CodeIgniter dan pembuatan PDF |
| Ekstensi `dom` | Wajib untuk PDF laporan dan Surat Keputusan |
| Ekstensi `mysqli` | Koneksi basis data |
| **MySQL 8.0** atau MariaDB 10.4+ | Skema memakai tipe `JSON` dan `CHECK` |
| **Apache** dengan `mod_rewrite` | Aplikasi mengandalkan `.htaccess`. Untuk Nginx lihat langkah 3 |
| Akses **SSH** | Sangat disarankan. Tanpa SSH masih bisa — lihat langkah 6 dan 7 |

```bash
php -v
php -m | grep -E 'intl|mbstring|dom|mysqli'
```

> **Cara tahu sudah benar** — perintah kedua menampilkan keempat nama ekstensi.
> Kalau ada yang tidak muncul, aktifkan lewat panel hosting atau `php.ini`
> sebelum melanjutkan.

---

## 2. Siapkan berkas aplikasi

Ekstrak paket yang Anda terima, lalu unduh pustaka pihak ketiga. Folder
`vendor/` sengaja tidak disertakan karena isinya murni hasil unduhan Composer.

```bash
composer install --no-dev --optimize-autoloader
```

Tanda `--no-dev` penting: tanpa itu, perkakas pengujian ikut terpasang —
sekitar 18 MB berkas yang tidak dipakai di server produksi.

> **Kalau server tidak punya Composer** — jalankan perintah di atas di komputer
> lokal, lalu unggah folder `vendor/` hasilnya bersama berkas lain.

---

## 3. Tentukan letak berkas di server

Aplikasi mendukung dua susunan folder. Pilih sesuai keleluasaan yang Anda punya
atas server.

### Susunan A — disarankan

Dipakai kalau Anda bisa mengarahkan *document root* ke subfolder. Hanya isi
`public/` yang bisa diakses dari internet.

```
/home/namauser/hrd-app/     ← di luar web root
├── app/
├── vendor/
├── writable/
├── .env
└── public/                 ← arahkan document root ke sini
    ├── index.php
    └── .htaccess
```

### Susunan B — untuk hosting yang mengunci web root

Sebagian hosting bersama tidak mengizinkan document root dipindah, atau
membatasi PHP hanya boleh membaca dari dalam web root.

```
public_html/                ← document root
├── app/
├── vendor/
├── writable/
├── index.php               ← dari public/index.php
├── .htaccess               ← dari public/.htaccess
├── favicon.ico, favicon.svg, robots.txt
└── env.local.php           ← dibuat di langkah 5
```

`index.php` mengenali kedua susunan itu sendiri, tanpa perlu diubah.

> **Penting untuk Susunan B** — pada susunan ini `app/`, `vendor/`, dan
> `writable/` berada di dalam web root. Yang mencegahnya diakses lewat browser
> hanyalah `.htaccess`. Pastikan Apache benar-benar membacanya (butuh
> `AllowOverride All`), dan buktikan lewat uji di langkah 8. Kalau `.htaccess`
> diabaikan, isi berkas sesi dan log bisa diunduh siapa saja.

### Kalau server memakai Nginx

Nginx tidak membaca `.htaccess` sama sekali. Pakai Susunan A, lalu tambahkan:

```nginx
root /home/namauser/hrd-app/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php$is_args$args;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Tanpa blok `try_files`, semua halaman selain beranda akan 404.

---

## 4. Atur izin tulis folder `writable/`

Aplikasi menulis sesi login, cache, log, dan PDF sementara ke folder ini. Tanpa
izin tulis, gejalanya membingungkan: halaman login terbuka, tetapi setiap
percobaan login seolah gagal tanpa pesan.

```bash
chmod -R 755 writable/
chown -R www-data:www-data writable/   # kalau proses web berjalan sebagai user lain
```

Subfolder yang harus ada: `cache`, `logs`, `session`, `uploads`, `pdfs`,
`debugbar`.

> **Jangan pakai 777** — izin itu membuat berkas bisa ditulis siapa pun yang
> punya akses ke server, termasuk proses milik akun lain pada hosting bersama.
> Kalau 755 tidak cukup, perbaiki kepemilikan berkasnya dengan `chown`, bukan
> melonggarkan izinnya.

---

## 5. Buat berkas konfigurasi

Ada dua mekanisme. Coba yang pertama; pindah ke yang kedua hanya kalau server
mematikan `putenv()` — hal yang lazim di hosting bersama.

### Cara 1 — berkas `.env`

```bash
cp .env.example .env
```

Isi minimal bagian ini:

```
CI_ENVIRONMENT = production

app.baseURL = 'https://hrd.perusahaan-anda.co.id/'
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = NAMA_DATABASE
database.default.username = USER_DATABASE
database.default.password = PASSWORD_DATABASE
database.default.DBDriver = MySQLi
database.default.port     = 3306
```

### Cara 2 — berkas `env.local.php`

Salin `env.local.php.example` menjadi `env.local.php`, letakkan sejajar dengan
`index.php`, lalu isi nilainya. Berkas ini mengisi `$_ENV` langsung sehingga
tetap bekerja walau `putenv()` dimatikan.

### Tiga hal yang sering salah, dan akibatnya

**1. `CI_ENVIRONMENT` dibiarkan `development`.** Setiap error akan menampilkan
jejak program lengkap berikut username dan password basis data, di halaman yang
bisa dilihat pengunjung mana pun. Harus `production`.

**2. `app.baseURL` tidak diakhiri garis miring.** Tautan dan pengalihan antar
halaman akan patah dengan cara yang sulit ditebak. Tulis
`https://domain-anda.co.id/`, bukan tanpa garis miring di ujung.

**3. `session.savePath` diisi path relatif** seperti `writable/session`. Path
relatif dihitung dari direktori kerja PHP, yaitu folder publik — sehingga
berkas sesi berpindah ke dalam web root dan bisa diunduh lewat browser. Isi
berkas sesi memuat `user_id` dan `role`, cukup bagi seseorang untuk menyamar
sebagai HRD. **Biarkan baris itu kosong.**

> **Tentang HTTPS** — setel `app.forceGlobalSecureRequests = true` hanya setelah
> sertifikat HTTPS benar-benar aktif. Kalau diaktifkan lebih dulu, situs akan
> mengalihkan ke `https://` yang belum bisa dilayani.

---

## 6. Bangkitkan kunci enkripsi

Kunci ini mengamankan data sesi. Nilainya **wajib unik untuk instalasi ini** dan
tidak boleh disalin dari instalasi lain atau dari berkas contoh.

```bash
php spark key:generate
```

> **Kalau tidak punya akses SSH** — jalankan `php spark key:generate --show` di
> komputer lokal, lalu salin nilainya ke `encryption.key` pada `.env` atau
> `env.local.php` di server.

> **Cara tahu sudah benar** — baris `encryption.key` berisi nilai acak 32
> karakter atau lebih, dan berbeda dari contoh mana pun di dalam paket.

---

## 7. Siapkan basis data

```sql
CREATE DATABASE nama_database
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Lalu pilih salah satu jalur. Keduanya menghasilkan struktur tabel yang identik.

### Jalur A — instalasi bersih (disarankan untuk pemakaian sungguhan)

```bash
php spark migrate
php spark db:seed AkunHrdPertamaSeeder
```

Perintah kedua menampilkan password acak **satu kali** di layar. Catat sebelum
menutup terminal — nilainya tidak disimpan dalam bentuk yang bisa dibaca lagi.

NIK dan nama bisa disesuaikan:

```bash
HRD_NIK=HRD001 HRD_NAMA="Nama Lengkap" php spark db:seed AkunHrdPertamaSeeder
```

### Jalur B — dengan data contoh

```bash
mysql -u USER -p nama_database < database_dump.sql
```

Cocok untuk mencoba aplikasi lebih dulu, dan satu-satunya pilihan kalau server
tidak punya akses SSH.

> **Kalau memilih Jalur B** — data contoh menyertakan sepuluh akun demo yang
> **seluruhnya memakai password yang sama**, dan password itu tertulis di
> `DEMO-ACCOUNTS.md` yang ikut dalam paket. Sebelum sistem dipakai dengan data
> karyawan sungguhan: buat akun HRD baru, lalu **hapus seluruh akun demo**.

> **Cara tahu sudah benar** — basis data punya enam tabel: `users`, `employees`,
> `penilaian`, `penilaian_detail`, `penilaian_keputusan`, `audit_logs`. Jalur A
> menambahkan satu tabel `migrations` berisi catatan versi skema.

---

## 8. Uji dan login pertama

Buka alamat aplikasi di browser. Anda akan diarahkan ke halaman login.

### Uji bahwa berkas internal tidak bisa diakses

Buka alamat berikut di browser — **semuanya harus menolak** dengan 403 atau 404,
bukan menampilkan isi berkas.

```
https://domain-anda.co.id/app/Config/Database.php
https://domain-anda.co.id/writable/logs/
https://domain-anda.co.id/vendor/
https://domain-anda.co.id/.env
```

> **Kalau salah satu menampilkan isinya** — berhenti dan perbaiki sebelum
> aplikasi dipakai. Yang paling mungkin: Apache belum diizinkan membaca
> `.htaccess` (butuh `AllowOverride All`), atau server memakai Nginx yang memang
> tidak membacanya. Berkas `Database.php` dan `.env` memuat kredensial basis
> data Anda.

### Login pertama

Masuk dengan NIK dan password dari langkah 7. Aplikasi akan **langsung meminta
Anda mengganti password** sebelum halaman lain bisa dibuka — ini disengaja,
karena password awal dibuat sistem dan sempat melewati layar terminal.

Setelah diganti, seluruh menu terbuka. Buat akun Team Leader dan karyawan lewat
menu di dalam aplikasi.

> **Password akun baru** — setiap kali HRD membuat akun atau mereset password,
> sistem membangkitkan password acak dan menampilkannya **sekali saja**. HRD
> perlu mencatat dan menyerahkannya ke pemilik akun. Pemiliknya wajib
> menggantinya saat login pertama.

---

## Daftar periksa sebelum diserahkan ke HRD

Lewati satu pun dari ini dan sistem tetap terlihat berjalan normal — masalahnya
baru muncul belakangan, saat sudah berisi data karyawan sungguhan.

- [ ] **`CI_ENVIRONMENT` bernilai `production`** — kalau masih `development`, halaman error menampilkan kredensial basis data ke pengunjung
- [ ] **`encryption.key` terisi nilai unik** — bukan disalin dari berkas contoh atau instalasi lain
- [ ] **`session.savePath` dibiarkan kosong** — path relatif memindahkan berkas sesi ke dalam web root
- [ ] **Seluruh akun demo sudah dihapus** — hanya berlaku kalau memakai Jalur B pada langkah 7
- [ ] **Berkas internal menolak diakses lewat browser** — uji keempat alamat di langkah 8
- [ ] **HTTPS aktif dan `forceGlobalSecureRequests` bernilai `true`** — tanpa HTTPS, password dan sesi melintas dalam bentuk terbaca
- [ ] **`logger.threshold` bernilai 3 atau lebih kecil** — nilai 4 mencatat segalanya dan membuat log membengkak
- [ ] **Pencadangan basis data terjadwal** — data penilaian tidak bisa disusun ulang kalau hilang
- [ ] **Berkas bantu apa pun sudah dihapus dari server** — skrip setup atau debug sementara berada di dalam web root

---

## Kalau ada yang tidak beres

### Halaman putih kosong, tanpa pesan apa pun

Hampir selalu ada error PHP yang sengaja disembunyikan karena `CI_ENVIRONMENT`
sudah `production` — dan itu memang seharusnya begitu. Lihat penyebabnya di log:

```bash
tail -n 50 writable/logs/log-$(date +%Y-%m-%d).log
```

Penyebab tersering: ekstensi PHP kurang (langkah 1), atau `writable/` tidak bisa
ditulis (langkah 4).

### Beranda terbuka, tetapi halaman lain 404

`mod_rewrite` belum aktif, atau Apache belum diizinkan membaca `.htaccess`.
Aktifkan `mod_rewrite` dan set `AllowOverride All`. Untuk Nginx, pakai blok
`try_files` di langkah 3.

### Login selalu kembali ke halaman login, tanpa pesan salah password

Sesi tidak bisa disimpan. Periksa izin tulis `writable/session/` (langkah 4).
Periksa juga `app.baseURL` — kalau alamatnya tidak sama persis dengan yang
dibuka di browser, cookie sesi tidak akan terkirim balik.

### Muncul pesan "Terlalu banyak percobaan login yang gagal"

Perilaku yang disengaja: percobaan gagal dibatasi 5 kali per NIK dan 30 kali per
alamat IP dalam 15 menit, untuk mencegah penebakan password. Tunggu sesuai waktu
yang disebutkan. Login yang berhasil tidak mengurangi jatah.

### Tombol simpan atau hapus tidak berfungsi, muncul error 403

Token keamanan formulir tidak terkirim. Paling sering karena halaman dibuka
terlalu lama sehingga sesinya berakhir — muat ulang lalu ulangi. Kalau terjadi
terus-menerus, periksa apakah cookie diblokir proxy atau pengaturan browser.

### Error "Unknown column" saat menambah user atau mengganti password

Struktur basis data tertinggal dari versi aplikasi. Jalankan `php spark migrate`
untuk menyelaraskannya. Kalau basis data dibuat dari `database_dump.sql`,
pastikan berkas dump berasal dari paket yang sama dengan kode aplikasinya.

### PDF laporan atau Surat Keputusan gagal dibuat

Periksa ekstensi `dom` dan `mbstring` (langkah 1), lalu izin tulis
`writable/pdfs/` (langkah 4).

---

## Catatan operasional

### Tampilan Surat Keputusan

Surat Keputusan dicetak dengan dua mesin berbeda tergantung sistem operasi
server. Di Windows, berkas dibuat langsung dari templat Word. Di **Linux — yang
paling mungkin Anda pakai** — berkas dibuat dari templat HTML bawaan aplikasi.

Seluruh isi surat sama pada kedua jalur: nomor surat, rujukan hukum, identitas
karyawan, penutup, penandatangan, dan alamat perusahaan. Yang bisa sedikit
berbeda hanya detail tata letak seperti jarak baris. Cetak satu SK contoh dan
tunjukkan ke bagian HRD sebelum sistem dipakai.

### Pencadangan

Yang wajib dicadangkan hanya basis data — berkas aplikasi selalu bisa dipasang
ulang dari paket.

```bash
mysqldump -u USER -p nama_database > cadangan-$(date +%F).sql
```

Jadwalkan lewat cron atau penjadwal di panel hosting. Simpan salinannya di
tempat lain, bukan di server yang sama.

### Berkas log

Log ditulis harian ke `writable/logs/` dan tidak terhapus sendiri. Bersihkan
berkas lama secara berkala:

```bash
find writable/logs -name "log-*.log" -mtime +30 -delete
```

### Memperbarui aplikasi

1. Cadangkan basis data terlebih dahulu.
2. Timpa folder `app/`, `public/`, dan `vendor/`.
3. **Jangan menimpa** `.env` atau `env.local.php`, dan jangan menimpa isi `writable/`.
4. Jalankan `php spark migrate` untuk menyelaraskan struktur basis data.

---

Rincian teknis lebih lanjut ada di [README.md](README.md); daftar akun contoh
ada di [DEMO-ACCOUNTS.md](DEMO-ACCOUNTS.md).
