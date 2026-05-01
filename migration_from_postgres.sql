-- ============================================================
-- HRD System – MySQL Schema (Migrated from PostgreSQL)
-- ============================================================

-- Enable strict mode
SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";

-- ================================================================
-- USERS TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nik` VARCHAR(20) UNIQUE NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('hrd', 'team-leader', 'probationary-employee') NOT NULL,
  `departemen` VARCHAR(50),
  `posisi` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_nik (nik),
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- EMPLOYEES TABLE
-- ================================================================
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nik` VARCHAR(20) UNIQUE NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `departemen` VARCHAR(50) NOT NULL,
  `posisi` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100),
  `tanggal_masuk` DATE,
  `mulai_probation` DATE NOT NULL,
  `akhir_probation` DATE,
  `status` ENUM('pending', 'lulus', 'tidak-lulus', 'warning') NOT NULL DEFAULT 'pending',
  `team_leader_id` INT UNSIGNED REFERENCES users(id),
  `created_by` INT UNSIGNED REFERENCES users(id),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_nik (nik),
  INDEX idx_status (status),
  INDEX idx_team_leader_id (team_leader_id),
  INDEX idx_created_by (created_by),
  FOREIGN KEY (team_leader_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PENILAIAN TABLE (Evaluations)
-- ================================================================
CREATE TABLE IF NOT EXISTS `penilaian` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `team_leader_id` INT UNSIGNED NOT NULL,
  `tanggal_penilaian` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `nilai_total` DECIMAL(4, 2),
  `status` ENUM('draft', 'submitted') NOT NULL DEFAULT 'submitted',
  `catatan_team_leader` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_employee_id (employee_id),
  INDEX idx_team_leader_id (team_leader_id),
  INDEX idx_status (status),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (team_leader_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PENILAIAN_DETAIL TABLE (Evaluation Details)
-- ================================================================
CREATE TABLE IF NOT EXISTS `penilaian_detail` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `penilaian_id` INT UNSIGNED NOT NULL,
  `kategori` VARCHAR(100) NOT NULL,
  `aspek` VARCHAR(255) NOT NULL,
  `nilai` TINYINT NOT NULL CHECK (nilai >= 1 AND nilai <= 10),
  `alasan` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_penilaian_id (penilaian_id),
  INDEX idx_kategori (kategori),
  FOREIGN KEY (penilaian_id) REFERENCES penilaian(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- AUDIT_LOGS TABLE (For audit trail)
-- ================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(100) NOT NULL,
  `record_id` INT UNSIGNED,
  `old_values` JSON,
  `new_values` JSON,
  `description` LONGTEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_action (action),
  INDEX idx_table_name (table_name),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SEED DATA
-- ================================================================

-- HRD User
INSERT INTO users (nik, nama, email, password_hash, role, departemen, posisi)
VALUES ('HRD001', 'Siti Nurhaliza', 'siti.nurhaliza@sumbermasan.co.id',
        '$2y$10$DOEjsszsZtxEYkxpWrM4ZuBXo96EHow0pQ0I09XtNQMA9V0aXvh4.',
        'hrd', 'Human Resources', 'HR Manager')
ON DUPLICATE KEY UPDATE nik=nik;

-- Team Leaders
INSERT INTO users (nik, nama, email, password_hash, role, departemen, posisi)
VALUES
  ('TL001', 'Ahmad Hidayat', 'ahmad.hidayat@sumbermasan.co.id',
   '$2y$10$DOEjsszsZtxEYkxpWrM4ZuBXo96EHow0pQ0I09XtNQMA9V0aXvh4.',
   'team-leader', 'Production', 'Production Supervisor'),
  ('TL002', 'Baskara Putra', 'baskara.putra@sumbermasan.co.id',
   '$2y$10$DOEjsszsZtxEYkxpWrM4ZuBXo96EHow0pQ0I09XtNQMA9V0aXvh4.',
   'team-leader', 'Quality Control', 'QC Supervisor')
ON DUPLICATE KEY UPDATE nik=nik;

-- Probationary Employees
INSERT INTO users (nik, nama, email, password_hash, role, departemen, posisi)
VALUES
  ('EMP001', 'Budi Santoso', 'budi.santoso@sumbermasan.co.id',
   '$2y$10$DOEjsszsZtxEYkxpWrM4ZuBXo96EHow0pQ0I09XtNQMA9V0aXvh4.',
   'probationary-employee', 'Production', 'Production Operator'),
  ('EMP009', 'Indah Permata', 'indah.permata@sumbermasan.co.id',
   '$2y$10$DOEjsszsZtxEYkxpWrM4ZuBXo96EHow0pQ0I09XtNQMA9V0aXvh4.',
   'probationary-employee', 'Production', 'Production Operator')
ON DUPLICATE KEY UPDATE nik=nik;

-- Note: All passwords are hashed from 'password123'
-- Hash generated using PHP: password_hash('password123', PASSWORD_BCRYPT)
