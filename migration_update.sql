-- ============================================================
-- MIGRATION UPDATE - HRD System
-- Jalankan script ini di Navicat / MySQL CLI
-- ============================================================

-- 1. Tambah kolom ke tabel users
ALTER TABLE `users`
    ADD COLUMN `jenis_kelamin` ENUM('L','P') NULL AFTER `posisi`,
    ADD COLUMN `tanggal_lahir` DATE NULL AFTER `jenis_kelamin`,
    ADD COLUMN `alamat` TEXT NULL AFTER `tanggal_lahir`;

-- 2. Tambah kolom ke tabel employees
ALTER TABLE `employees`
    ADD COLUMN `jenis_kelamin` ENUM('L','P') NULL AFTER `email`,
    ADD COLUMN `tanggal_lahir` DATE NULL AFTER `jenis_kelamin`,
    ADD COLUMN `alamat` TEXT NULL AFTER `tanggal_lahir`;

-- 3. Tambah kolom ke tabel penilaian
ALTER TABLE `penilaian`
    ADD COLUMN `nomor_penilaian` TINYINT NOT NULL DEFAULT 1 AFTER `employee_id`,
    ADD COLUMN `tanggal_mulai_penilaian` DATE NULL AFTER `tanggal_penilaian`,
    ADD COLUMN `tanggal_selesai_penilaian` DATE NULL AFTER `tanggal_mulai_penilaian`;

-- 4. Isi dummy data untuk users yang sudah ada
UPDATE `users` SET
    `jenis_kelamin` = 'L',
    `tanggal_lahir` = '1988-03-15',
    `alamat` = 'Jl. Merdeka No. 10, Jakarta Pusat'
WHERE `jenis_kelamin` IS NULL AND `role` = 'hrd';

UPDATE `users` SET
    `jenis_kelamin` = 'L',
    `tanggal_lahir` = '1990-07-22',
    `alamat` = 'Jl. Sudirman No. 5, Jakarta Selatan'
WHERE `jenis_kelamin` IS NULL AND `role` = 'team-leader';

UPDATE `users` SET
    `jenis_kelamin` = 'L',
    `tanggal_lahir` = '1998-11-05',
    `alamat` = 'Jl. Gatot Subroto No. 3, Jakarta'
WHERE `jenis_kelamin` IS NULL AND `role` = 'probationary-employee';

-- 5. Isi dummy data untuk employees yang sudah ada
UPDATE `employees` SET
    `jenis_kelamin` = 'L',
    `tanggal_lahir` = '1998-11-05',
    `alamat` = 'Jl. Gatot Subroto No. 3, Jakarta'
WHERE `jenis_kelamin` IS NULL AND `deleted_at` IS NULL;
