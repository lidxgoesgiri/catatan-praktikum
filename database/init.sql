-- Dijalankan otomatis oleh image MySQL saat volume pertama kali dibuat.
-- Database dan pengguna dibuat dari environment variable pada compose.yaml.
CREATE TABLE IF NOT EXISTS catatan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    isi TEXT NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
