-- =====================================================================
-- Tao bang `exams` — De thi / kiem tra trac nghiem (AI sinh)
-- Tuong duong migration: 2026_07_22_100002_create_exams_table
--
-- Dung khi KHONG chay duoc `php artisan migrate` tren production.
-- CACH DUNG: chon dung database roi chay toan bo file nay.
-- An toan chay lai nhieu lan (IF NOT EXISTS + guard migration).
-- =====================================================================

-- 1. Bang de thi. `content` (JSON) = {questions:[{content, options[4], correct, difficulty, topic}]}.
CREATE TABLE IF NOT EXISTS `exams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `grade` TINYINT UNSIGNED NOT NULL,
  `topics` VARCHAR(255) DEFAULT NULL,
  `difficulty` ENUM('easy','medium','hard','mixed') NOT NULL DEFAULT 'mixed',
  `question_count` TINYINT UNSIGNED NOT NULL DEFAULT 10,
  `status` ENUM('draft','generating','ready','failed') NOT NULL DEFAULT 'draft',
  `error` TEXT DEFAULT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `generated_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exams_created_by_foreign` (`created_by`),
  KEY `exams_status_grade_index` (`status`, `grade`),
  CONSTRAINT `exams_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ghi nhan migration da chay -> `php artisan migrate` sau nay KHONG chay lai.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT `newrow`.`migration`, `newrow`.`batch`
FROM (
    SELECT '2026_07_22_100002_create_exams_table' AS `migration`,
           (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM (SELECT `batch` FROM `migrations`) AS b) AS `batch`
) AS `newrow`
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS m2
    WHERE m2.`migration` = '2026_07_22_100002_create_exams_table'
);

-- 3. Kiem tra
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exams';
