-- =====================================================================
-- Them cot token vao ai_logs (theo doi luong dung + uoc tinh chi phi AI)
-- Tuong duong migration: 2026_07_17_100001_add_tokens_to_ai_logs
--
-- Dung khi KHONG chay duoc `php artisan migrate` tren production
-- (vd: chay tay qua phpMyAdmin / aaPanel Database).
--
-- CACH DUNG: chon dung database roi chay toan bo file nay.
-- =====================================================================

-- 1. Them 3 cot token. Nullable vi cac log CU (truoc tinh nang nay) khong co token.
ALTER TABLE `ai_logs`
  ADD COLUMN `prompt_tokens`     INT UNSIGNED NULL DEFAULT NULL AFTER `latency_ms`,
  ADD COLUMN `completion_tokens` INT UNSIGNED NULL DEFAULT NULL AFTER `prompt_tokens`,
  ADD COLUMN `total_tokens`      INT UNSIGNED NULL DEFAULT NULL AFTER `completion_tokens`;

-- 2. Ghi nhan migration da chay -> `php artisan migrate` sau nay KHONG chay lai
--    (neu chay lai se loi "Duplicate column name").
--    Subquery long de MySQL cho phep doc `migrations` khi dang INSERT vao no.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_17_100001_add_tokens_to_ai_logs',
       COALESCE(MAX(`batch`), 0) + 1
FROM (SELECT `batch` FROM `migrations`) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) AS m2
    WHERE m2.`migration` = '2026_07_17_100001_add_tokens_to_ai_logs'
);

-- 3. Kiem tra ket qua
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ai_logs'
  AND COLUMN_NAME IN ('prompt_tokens', 'completion_tokens', 'total_tokens');
