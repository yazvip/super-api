<?php
/**
 * File ini berisi semua fungsi dan konfigurasi untuk panel admin.
 */

const DB_HOST    = 'localhost';
const DB_NAME = 'andn3765_api';
const DB_USER = 'andn3765_api';
const DB_PASS = 'andn3765_api';
const DB_CHARSET = 'utf8mb4';
const ERROR_LOG_PATH = __DIR__ . '/../error.log'; // Path to the PHP error log. Sesuaikan jika perlu.

/**
 * Get admin password from database
 * @return string|null
 */
function getAdminPassword(): ?string
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'admin_password'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        error_log("Error getting admin password: " . $e->getMessage());
        return null;
    }
}

/**
 * Update admin password
 * @param string $newPassword
 * @return bool
 */
function updateAdminPassword(string $newPassword): bool
{
    $db = getDbConnection();
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admin_settings (setting_key, setting_value)
            VALUES ('admin_password', :password)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([':password' => $hashedPassword]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating admin password: " . $e->getMessage());
        return false;
    }
}

/**
 * Mengambil nilai pengaturan dari database.
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting(string $key, $default = null)
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        error_log("Error getting setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

/**
 * Memperbarui nilai pengaturan di database.
 * @param string $key
 * @param string $value
 * @return bool
 */
function updateSetting(string $key, string $value): bool
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            INSERT INTO admin_settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([':key' => $key, ':value' => $value]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating setting '{$key}': " . $e->getMessage());
        return false;
    }
}


/**
 * Verify admin password
 * @param string $password
 * @return bool
 */
function verifyAdminPassword(string $password): bool
{
    $hashedPassword = getAdminPassword();
    if (!$hashedPassword) {
        return false;
    }
    return password_verify($password, $hashedPassword);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Fungsi untuk mendapatkan koneksi database PDO.
 * @return PDO Objek koneksi PDO.
 */
function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Kesalahan koneksi database MySQL: " . $e->getMessage());
            http_response_code(503);
            die(json_encode(['success' => false, 'message' => 'Layanan sedang tidak tersedia.']));
        }
    }
    return $pdo;
}

/**
 * Inisialisasi struktur database.
 * Akan otomatis menambahkan kolom 'nama' dan 'nomor_wa' jika belum ada.
 */
function initializeDatabase(): void
{
    $db = getDbConnection();
    try {
        // Query untuk membuat tabel-tabel yang dibutuhkan
        $db->exec("CREATE TABLE IF NOT EXISTS `usage` (`api_key` VARCHAR(255) NOT NULL, `usage_date` DATE NOT NULL, `count` INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`api_key`, `usage_date`)) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `history` (`id` INT AUTO_INCREMENT PRIMARY KEY, `api_key` VARCHAR(255) NOT NULL, `bank` VARCHAR(255), `account_number` VARCHAR(255), `account_name` VARCHAR(255), `status` VARCHAR(50), `message` TEXT, `timestamp` DATETIME NOT NULL, INDEX `idx_api_key` (`api_key`)) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `api_keys` (`id` INT AUTO_INCREMENT PRIMARY KEY, `api_key` VARCHAR(255) NOT NULL UNIQUE, `monthly_limit` INT UNSIGNED NOT NULL DEFAULT 100, `expiry_date` DATE, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `notifications` (`id` INT AUTO_INCREMENT PRIMARY KEY, `message` TEXT NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `is_active` TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);

        // Tabel baru untuk fitur tambahan
        $db->exec("CREATE TABLE IF NOT EXISTS `admin_settings` (`id` INT AUTO_INCREMENT PRIMARY KEY, `setting_key` VARCHAR(100) NOT NULL UNIQUE, `setting_value` TEXT NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `bank_codes` (`id` INT AUTO_INCREMENT PRIMARY KEY, `code` VARCHAR(50) NOT NULL UNIQUE, `name` VARCHAR(255) NOT NULL, `type` ENUM('bank', 'ewallet') NOT NULL DEFAULT 'bank', `is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `api_security_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY, `api_key` VARCHAR(255) NOT NULL, `ip_address` VARCHAR(45) NOT NULL, `request_count` INT NOT NULL DEFAULT 1, `last_request` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `is_blocked` TINYINT(1) NOT NULL DEFAULT 0, `blocked_until` DATETIME NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY `unique_key_ip` (`api_key`,`ip_address`), INDEX `idx_last_request` (`last_request`)) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `api_test_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY, `endpoint` VARCHAR(255) NOT NULL, `method` VARCHAR(10) NOT NULL, `request_data` TEXT, `response_data` TEXT, `response_code` INT, `response_time` DECIMAL(10,3), `status` ENUM('success', 'error') NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `backup_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY, `backup_type` ENUM('full', 'database', 'files') NOT NULL, `filename` VARCHAR(255) NOT NULL, `file_size` BIGINT, `status` ENUM('success', 'failed', 'in_progress') NOT NULL, `error_message` TEXT, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
        $db->exec("CREATE TABLE IF NOT EXISTS `admin_activity_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY, `admin_user` VARCHAR(100) NOT NULL, `action` VARCHAR(100) NOT NULL, `details` TEXT, `ip_address` VARCHAR(45) NOT NULL, `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX `idx_action` (`action`), INDEX `idx_timestamp` (`timestamp`)) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);

        // Cek dan tambahkan kolom baru ke tabel api_keys jika belum ada
        $stmt = $db->query("SHOW COLUMNS FROM `api_keys` LIKE 'nama'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE `api_keys` ADD `nama` VARCHAR(255) NULL AFTER `api_key`, ADD `nomor_wa` VARCHAR(25) NULL AFTER `nama`");
        }

        // Insert default settings jika belum ada
        $db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('admin_password', '" . password_hash('andrias', PASSWORD_DEFAULT) . "')");
        $db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('api_request_delay', '3')");
        $db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('rate_limit_count', '60')");
        $db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('auto_block_duration', '24')");
        $db->exec("INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES ('auto_block_enabled', '1')");

    } catch (PDOException $e) {
        error_log("Gagal inisialisasi database: " . $e->getMessage());
        die("Gagal mempersiapkan database.");
    }
}

/**
 * Get bank codes from database
 * @return array
 */
function getBankCodesFromDB(): array
{
    $db = getDbConnection();
    try {
        $stmt = $db->query("SELECT code, name FROM bank_codes WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting bank codes: " . $e->getMessage());
        return [];
    }
}

/**
 * Log security event
 * @param string $apiKey
 * @param string $ipAddress
 * @return void
 */
function logSecurityEvent(string $apiKey, string $ipAddress): void
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            INSERT INTO api_security_logs (api_key, ip_address, request_count, last_request)
            VALUES (:api_key, :ip, 1, NOW())
            ON DUPLICATE KEY UPDATE
            request_count = request_count + 1,
            last_request = NOW()
        ");
        $stmt->execute([':api_key' => $apiKey, ':ip' => $ipAddress]);
    } catch (PDOException $e) {
        error_log("Error logging security event: " . $e->getMessage());
    }
}

/**
 * [BARU] Mencatat aktivitas admin ke database.
 * @param string $action Aksi yang dilakukan (misal: 'LOGIN', 'CREATE_KEY').
 * @param string $details Detail tambahan mengenai aksi tersebut.
 */
function logAdminActivity(string $action, string $details = ''): void
{
    try {
        $db = getDbConnection();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $stmt = $db->prepare(
            "INSERT INTO admin_activity_logs (admin_user, action, details, ip_address)
             VALUES (:user, :action, :details, :ip)"
        );

        $stmt->execute([
            ':user' => 'admin', // Hardcoded for now
            ':action' => $action,
            ':details' => $details,
            ':ip' => $ip_address
        ]);
    } catch (PDOException $e) {
        // Gagal mencatat tidak boleh menghentikan aplikasi
        error_log("Gagal mencatat aktivitas admin: " . $e->getMessage());
    }
}

/**
 * Check if API key is blocked
 * @param string $apiKey
 * @param string $ipAddress
 * @return bool
 */
function isApiKeyBlocked(string $apiKey, string $ipAddress): bool
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT is_blocked, blocked_until
            FROM api_security_logs
            WHERE api_key = :api_key AND ip_address = :ip
        ");
        $stmt->execute([':api_key' => $apiKey, ':ip' => $ipAddress]);
        $result = $stmt->fetch();

        if (!$result || !$result['is_blocked']) {
            return false;
        }

        // Check if block period has expired
        if ($result['blocked_until'] && $result['blocked_until'] < date('Y-m-d H:i:s')) {
            // Unblock the API key
            $unblockStmt = $db->prepare("
                UPDATE api_security_logs
                SET is_blocked = 0, blocked_until = NULL
                WHERE api_key = :api_key AND ip_address = :ip
            ");
            $unblockStmt->execute([':api_key' => $apiKey, ':ip' => $ipAddress]);
            return false;
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error checking API block status: " . $e->getMessage());
        return false;
    }
}

/**
 * Create backup
 * @param string $type ('full', 'database', 'files')
 * @return array
 */
function createBackup(string $type): array
{
    $db = getDbConnection();
    $backupDir = __DIR__ . '/backups';

    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
             return ['success' => false, 'error' => 'Gagal membuat direktori backup.'];
        }
    }

    $timestamp = date('Y-m-d_H-i-s');

    try {
        $result = ['success' => false, 'error' => 'Tipe backup tidak valid'];

        if ($type === 'database' || $type === 'full') {
            $filename = "backup_database_{$timestamp}";
            $logId = logBackupStatus($db, 'database', $filename, 'in_progress');
            $dbResult = createDatabaseBackup($backupDir, $filename);
            updateBackupLog($db, $logId, $dbResult);
            if (!$dbResult['success']) return $dbResult; // Stop if db backup fails
            $result = $dbResult;
        }

        if ($type === 'files' || $type === 'full') {
            $filename = "backup_files_{$timestamp}";
            $logId = logBackupStatus($db, 'files', $filename, 'in_progress');
            $filesResult = createFilesBackup($backupDir, $filename);
            updateBackupLog($db, $logId, $filesResult);
            if (!$filesResult['success']) return $filesResult;
            $result = $filesResult;
        }

        if ($type === 'full') {
            // Jika full, kita kembalikan pesan sukses umum
            return ['success' => true, 'message' => 'Backup database dan file berhasil.'];
        }

        return $result;

    } catch (Exception $e) {
        error_log("Backup error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function logBackupStatus($db, $type, $filename, $status, $size = null, $error = null) {
    $stmt = $db->prepare("INSERT INTO backup_logs (backup_type, filename, status, file_size, error_message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$type, $filename, $status, $size, $error]);
    return $db->lastInsertId();
}

function updateBackupLog($db, $logId, $result) {
    $stmt = $db->prepare("UPDATE backup_logs SET status = ?, file_size = ?, error_message = ? WHERE id = ?");
    $stmt->execute([
        $result['success'] ? 'success' : 'failed',
        $result['size'] ?? null,
        $result['error'] ?? null,
        $logId
    ]);
}


/**
 * [REVISED] Create database backup using pure PHP.
 * @param string $backupDir
 * @param string $filename
 * @return array
 */
function createDatabaseBackup(string $backupDir, string $filename): array
{
    try {
        $db = getDbConnection();
        $sqlFile = $backupDir . '/' . $filename . '.sql';
        $handle = fopen($sqlFile, 'w');

        if ($handle === false) {
            return ['success' => false, 'error' => 'Tidak dapat membuat file backup.'];
        }

        fwrite($handle, "-- Backup Database: " . DB_NAME . "\n");
        fwrite($handle, "-- Tanggal: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Host: " . DB_HOST . "\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            fwrite($handle, "--\n-- Struktur tabel `{$table}`\n--\n\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createTableStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($handle, $createTableStmt['Create Table'] . ";\n\n");

            $dataStmt = $db->query("SELECT * FROM `{$table}`");
            $numFields = $dataStmt->columnCount();

            if ($dataStmt->rowCount() > 0) {
                fwrite($handle, "--\n-- Dumping data untuk tabel `{$table}`\n--\n\n");
                fwrite($handle, "LOCK TABLES `{$table}` WRITE;\n");
                fwrite($handle, "INSERT INTO `{$table}` VALUES ");

                $isFirstRow = true;
                while ($row = $dataStmt->fetch(PDO::FETCH_NUM)) {
                    if (!$isFirstRow) {
                        fwrite($handle, ",");
                    }
                    fwrite($handle, "\n(");
                    for ($j = 0; $j < $numFields; $j++) {
                        if (isset($row[$j])) {
                            fwrite($handle, $db->quote($row[$j]));
                        } else {
                            fwrite($handle, 'NULL');
                        }
                        if ($j < ($numFields - 1)) {
                            fwrite($handle, ',');
                        }
                    }
                    fwrite($handle, ")");
                    $isFirstRow = false;
                }
                fwrite($handle, ";\n");
                fwrite($handle, "UNLOCK TABLES;\n\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return [
            'success' => true,
            'filename' => basename($sqlFile),
            'size' => filesize($sqlFile)
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database backup failed: ' . $e->getMessage()];
    }
}


/**
 * Create files backup
 * @param string $backupDir
 * @param string $filename
 * @return array
 */
function createFilesBackup(string $backupDir, string $filename): array
{
    $zipFile = $backupDir . '/' . $filename . '.zip';
    $projectDir = dirname(__DIR__); // Get the parent directory of admin_functions.php

    // Periksa apakah kelas ZipArchive ada
    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'error' => 'Kelas ZipArchive tidak ditemukan. Ekstensi PHP Zip diperlukan.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return ['success' => false, 'error' => 'Tidak dapat membuat file zip'];
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($projectDir) + 1);

            // Skip backup directory and other unnecessary files
            if (strpos($filePath, $backupDir) === 0 ||
                strpos($relativePath, '.git') === 0 ||
                strpos($relativePath, 'node_modules') === 0) {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();

    if (file_exists($zipFile) && filesize($zipFile) > 0) {
        return [
            'success' => true,
            'filename' => basename($zipFile),
            'size' => filesize($zipFile)
        ];
    } else {
        // Hapus file zip yang gagal atau kosong
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
        return [
            'success' => false,
            'error' => 'Backup file gagal atau menghasilkan file kosong.'
        ];
    }
}

/**
 * Menghitung informasi siklus penggunaan API key.
 * @param string $apiKey
 * @return array
 */
function getCurrentCycleUsage(string $apiKey): array
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT created_at FROM api_keys WHERE api_key = :api_key");
    $stmt->execute([':api_key' => $apiKey]);
    $key_data = $stmt->fetch();
    if (!$key_data) {
        return ['usage_count' => 0, 'next_reset' => date('Y-m-d')];
    }
    $created_at_ts = strtotime($key_data['created_at']);
    $now_ts = time();
    $seconds_in_cycle = 28 * 24 * 60 * 60;
    $cycles_passed = ($now_ts < $created_at_ts) ? 0 : floor(($now_ts - $created_at_ts) / $seconds_in_cycle);
    $current_cycle_start_ts = $created_at_ts + ($cycles_passed * $seconds_in_cycle);
    $usage_date_key = date('Y-m-d', $current_cycle_start_ts);
    $next_reset_date = date('Y-m-d', $current_cycle_start_ts + $seconds_in_cycle);
    $usageStmt = $db->prepare("SELECT `count` FROM `usage` WHERE api_key = :api_key AND usage_date = :usage_date");
    $usageStmt->execute([':api_key' => $apiKey, ':usage_date' => $usage_date_key]);
    $usage_count = (int)$usageStmt->fetchColumn();
    return ['usage_count' => $usage_count, 'next_reset' => $next_reset_date];
}

/**
 * Mengambil statistik dasar untuk dashboard.
 * @return array
 */
function getDashboardStats(): array
{
    $db = getDbConnection();
    $stats = [];
    $stats['total_keys'] = $db->query("SELECT COUNT(id) FROM api_keys")->fetchColumn();
    $stats['active_keys'] = $db->query("SELECT COUNT(id) FROM api_keys WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetchColumn();
    $stats['total_history'] = $db->query("SELECT COUNT(id) FROM history")->fetchColumn();
    $stats['total_notifications'] = $db->query("SELECT COUNT(id) FROM notifications WHERE is_active = 1")->fetchColumn();
    return $stats;
}

/**
 * [BARU] Mengambil statistik lanjutan untuk dashboard baru.
 * @return array
 */
function getAdvancedDashboardStats(): array
{
    $db = getDbConnection();
    $stats = [];

    // 1. Top 5 API Keys by usage
    $stats['top_keys'] = $db->query("
        SELECT a.nama, h.api_key, COUNT(h.id) as total_hits
        FROM history h
        JOIN api_keys a ON h.api_key = a.api_key
        GROUP BY h.api_key, a.nama
        ORDER BY total_hits DESC
        LIMIT 5
    ")->fetchAll();

    // 2. Hits per periode
    $stats['hits_today'] = $db->query("SELECT COUNT(id) FROM history WHERE DATE(timestamp) = CURDATE()")->fetchColumn();
    $stats['hits_week'] = $db->query("SELECT COUNT(id) FROM history WHERE timestamp >= CURDATE() - INTERVAL 7 DAY")->fetchColumn();
    $stats['hits_month'] = $db->query("SELECT COUNT(id) FROM history WHERE YEAR(timestamp) = YEAR(CURDATE()) AND MONTH(timestamp) = MONTH(CURDATE())")->fetchColumn();

    // 3. Data untuk grafik (30 hari terakhir)
    $chart_data = $db->query("
        SELECT DATE(timestamp) as date, COUNT(id) as hits
        FROM history
        WHERE timestamp >= CURDATE() - INTERVAL 30 DAY
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $labels = [];
    $data = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d M', strtotime($date));
        $data[] = (int)($chart_data[$date] ?? 0);
    }
    $stats['chart'] = ['labels' => $labels, 'data' => $data];

    return $stats;
}

/**
 * [DIROMBAK] Mengambil data komprehensif untuk halaman monitoring.
 * @return array
 */
function getMonitoringData(): array
{
    $db = getDbConnection();
    $data = [];

    // --- 1. Informasi Sistem ---
    $disk_path = '/';
    $disk_total = @disk_total_space($disk_path);
    $disk_free = @disk_free_space($disk_path);
    $disk_used = ($disk_total !== false && $disk_free !== false) ? $disk_total - $disk_free : 0;
    $disk_usage_percentage = ($disk_total > 0) ? round(($disk_used / $disk_total) * 100, 2) : 0;
    $data['system_info'] = [
        'php_version' => phpversion(),
        'web_server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'os' => php_uname('s'),
        'disk_total' => $disk_total ?: 0,
        'disk_used' => $disk_used,
        'disk_usage_percentage' => $disk_usage_percentage,
    ];

    // --- 2. Live Error Log ---
    $log_content = 'File log tidak ditemukan atau tidak dapat dibaca di: ' . ERROR_LOG_PATH;
    if (defined('ERROR_LOG_PATH') && file_exists(ERROR_LOG_PATH) && is_readable(ERROR_LOG_PATH)) {
        $lines = file(ERROR_LOG_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $log_content = implode("\n", array_slice($lines, -20));
        } else {
            $log_content = 'File log kosong.';
        }
    }
    $data['error_log'] = ['content' => $log_content];

    // --- 3. Informasi Database ---
    try {
        $dbName = DB_NAME;
        $db_version_full = $db->query("SELECT VERSION()")->fetchColumn();
        preg_match('/^[0-9]+\.[0-9]+\.[0-9]+/', $db_version_full, $matches);
        $db_version = $matches[0] ?? $db_version_full;
        $tables_stmt = $db->prepare("SELECT table_name as name, table_rows as `rows`, (data_length + index_length) as size_bytes FROM information_schema.TABLES WHERE table_schema = ? ORDER BY size_bytes DESC");
        $tables_stmt->execute([$dbName]);
        $tables = $tables_stmt->fetchAll();
        $total_size_bytes = array_sum(array_column($tables, 'size_bytes'));
    } catch (PDOException $e) {
        $db_version = 'N/A';
        $tables = [];
        $total_size_bytes = 0;
        error_log("Monitoring page - DB Info Error: " . $e->getMessage());
    }
    $data['database_info'] = ['version' => $db_version, 'total_size_bytes' => $total_size_bytes, 'tables' => $tables];

    // --- 4. Slow Query Log ---
    try {
        $slow_log_stmt = $db->query("SELECT start_time, user_host, query_time, lock_time, rows_sent, rows_examined, sql_text FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10");
        $data['slow_query_log'] = $slow_log_stmt->fetchAll();
    } catch (PDOException $e) {
        $data['slow_query_log'] = [['error' => 'Tidak dapat mengakses log kueri lambat. Periksa hak akses database.']];
        error_log("Monitoring page - Slow Query Log Error: " . $e->getMessage());
    }

    // --- 5. Statistik & Analitik API ---
    $top_consumers_stmt = $db->query("SELECT a.nama, COUNT(h.id) as total_hits FROM history h JOIN api_keys a ON h.api_key = a.api_key WHERE h.timestamp >= CURDATE() - INTERVAL 1 DAY GROUP BY h.api_key, a.nama ORDER BY total_hits DESC LIMIT 5");
    $data['api_analytics']['top_consumers_today'] = $top_consumers_stmt->fetchAll();

    $keys_nearing_limit = [];
    $active_keys_stmt = $db->query("SELECT api_key, nama, monthly_limit FROM api_keys WHERE is_active = 1 AND monthly_limit > 0");
    while ($key = $active_keys_stmt->fetch()) {
        $usage_info = getCurrentCycleUsage($key['api_key']);
        if ($key['monthly_limit'] > 0) {
            $usage_percentage = round(($usage_info['usage_count'] / $key['monthly_limit']) * 100, 2);
            if ($usage_percentage >= 80) {
                $keys_nearing_limit[] = [
                    'nama' => $key['nama'],
                    'api_key' => $key['api_key'],
                    'usage' => $usage_info['usage_count'],
                    'limit' => $key['monthly_limit'],
                    'percentage' => $usage_percentage
                ];
            }
        }
    }
    $data['api_analytics']['nearing_limit'] = $keys_nearing_limit;

    // --- 6. Feeds ---
    $data['feeds']['security'] = $db->query("SELECT * FROM api_security_logs ORDER BY last_request DESC LIMIT 5")->fetchAll();
    $data['feeds']['admin_activity'] = $db->query("SELECT * FROM admin_activity_logs ORDER BY timestamp DESC LIMIT 10")->fetchAll();

    // --- 7. Cache Stats ---
    $cache_dir = __DIR__ . '/../cache';
    $file_count = 0;
    $total_size = 0;
    if (is_dir($cache_dir)) {
        $cache_files = scandir($cache_dir);
        if($cache_files) {
            foreach ($cache_files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($cache_dir . '/' . $file)) {
                    $file_count++;
                    $total_size += @filesize($cache_dir . '/' . $file);
                }
            }
        }
    }
    $data['cache_info'] = ['file_count' => $file_count, 'total_size' => $total_size];

    // --- 8. Chart Data ---
    $chart_data_stmt = $db->query("SELECT DATE(timestamp) as date, COUNT(id) as hits FROM history WHERE timestamp >= CURDATE() - INTERVAL 30 DAY GROUP BY DATE(timestamp) ORDER BY date ASC");
    $chart_data_raw = $chart_data_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $labels = [];
    $chart_values = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d M', strtotime($date));
        $chart_values[] = (int)($chart_data_raw[$date] ?? 0);
    }
    $data['chart_data'] = ['labels' => $labels, 'data' => $chart_values];

    return $data;
}

/**
 * Mengirim respons JSON dan menghentikan skrip.
 * @param bool $success
 * @param string $message
 * @param array $data
 * @param int $statusCode
 */
function sendJsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Membersihkan output untuk mencegah XSS.
 * @param string|null $string
 * @return string
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
