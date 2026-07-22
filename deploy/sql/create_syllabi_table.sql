-- =====================================================================
-- Tao bang `syllabi` — Lên giáo trình bằng AI (thu vien giao trinh mau)
-- Tuong duong migration: 2026_07_22_100001_create_syllabi_table
--
-- Dung khi KHONG chay duoc `php artisan migrate` tren production
-- (vd: chay tay qua phpMyAdmin / aaPanel Database).
--
-- CACH DUNG: chon dung database roi chay toan bo file nay.
-- An toan chay lai nhieu lan (IF NOT EXISTS + guard migration).
-- =====================================================================

-- 1. Bang giao trinh mau. `content` (JSON) giu toan bo cau truc modules->lessons->exercises.
CREATE TABLE IF NOT EXISTS `syllabi` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `grade` TINYINT UNSIGNED NOT NULL,
  `topic` VARCHAR(200) DEFAULT NULL,
  `goal` TEXT DEFAULT NULL,
  `planned_sessions` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','generating','ready','failed') NOT NULL DEFAULT 'draft',
  `error` TEXT DEFAULT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `generated_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `syllabi_created_by_foreign` (`created_by`),
  KEY `syllabi_status_grade_index` (`status`, `grade`),
  CONSTRAINT `syllabi_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ghi nhan migration da chay -> `php artisan migrate` sau nay KHONG chay lai.
--    `newrow` la 1 dong (khong tong hop) nen WHERE NOT EXISTS loc dung -> chay lai
--    khong chen trung. Cac lan doc `migrations` deu boc trong subquery de tranh loi 1093.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT `newrow`.`migration`, `newrow`.`batch`
FROM (
    SELECT '2026_07_22_100001_create_syllabi_table' AS `migration`,
           (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM (SELECT `batch` FROM `migrations`) AS b) AS `batch`
) AS `newrow`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS m2
    WHERE m2.`migration` = '2026_07_22_100001_create_syllabi_table'
);

-- 3. Kiem tra ket qua
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'syllabi';
