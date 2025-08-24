<?php
// BARU: Mengaktifkan pelaporan error untuk development. Matikan di produksi.
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 'On');

// --- HEADERS ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- KONFIGURASI ---
const DB_HOST = 'localhost';
const DB_NAME = 'andn3765_api';
const DB_USER = 'andn3765_api';
const DB_PASS = 'andn3765_api';
const DB_CHARSET = 'utf8mb4';

const ITEMS_PER_PAGE = 10;
const GAME_CODES_WITH_ZONE = ['MOBILE_LEGENDS', 'MOBILE_LEGENDS_REG', 'MOBILE_LEGENDS_SO', 'MOBILE_LEGENDS_VC'];
const EWALLET_CODES = ['shopeepay', 'dana', 'gopay', 'ovo', 'gopay_driver', 'linkaja','isaku'];

const ARIEPULSA_API_KEY = 'fPZKLcPwR04zMyxGZYU58rcxMTfXaFNh';

// Konfigurasi Cache & Lock
const GAME_CATEGORY_CACHE_FILE = __DIR__ . '/cache/game_categories.json';
const CACHE_DURATION = 21600; // 6 jam dalam detik (6 * 60 * 60)
const LOCK_DIR = __DIR__ . '/locks'; // Direktori untuk file lock

// --- FUNGSI-FUNGSI BANTUAN (HELPERS) ---

function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            error_log('Kesalahan koneksi database: ' . $e->getMessage());
            die(json_encode(['ok' => false, 'msg' => 'Layanan sedang tidak tersedia.']));
        }
    }
    return $pdo;
}

function getRequestApiKey(): ?string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if ($authHeader && preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }
    return $_REQUEST['apikey'] ?? $_REQUEST['api_key'] ?? null;
}

function getSetting(string $key, $default = null)
{
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        error_log("Error getting setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

function getBankCodesFromDB(): array
{
    try {
        $db = getDbConnection();
        $stmt = $db->query("SELECT code, name FROM bank_codes WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting bank codes: " . $e->getMessage());
        return [];
    }
}

function getBankOptions(): array
{
    static $banks = null;
    if ($banks === null) {
        $banks = getBankCodesFromDB();
    }
    return $banks;
}

// --- FUNGSI-FUNGSI API EKSTERNAL ---

function getGameCategories(): array
{
    if (file_exists(GAME_CATEGORY_CACHE_FILE) && (time() - filemtime(GAME_CATEGORY_CACHE_FILE) < CACHE_DURATION)) {
        $cachedData = file_get_contents(GAME_CATEGORY_CACHE_FILE);
        $decodedData = json_decode($cachedData, true);
        if (is_array($decodedData)) {
            return $decodedData;
        }
    }

    $url = 'https://ariepulsa.my.id/api/get-nickname-game';
    $postData = [
        'api_key' => ARIEPULSA_API_KEY,
        'action'  => 'kategori-game',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Gagal mengambil kategori game: " . $error);
        if (file_exists(GAME_CATEGORY_CACHE_FILE)) {
            return json_decode(file_get_contents(GAME_CATEGORY_CACHE_FILE), true) ?: [];
        }
        return [];
    }
    
    $result = json_decode($response, true);
    if (isset($result['status']) && $result['status'] === true && is_array($result['data'])) {
        $formattedData = array_map(fn($game) => ['code' => $game['kode'], 'label' => $game['nama']], $result['data']);
        
        $cacheDir = dirname(GAME_CATEGORY_CACHE_FILE);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents(GAME_CATEGORY_CACHE_FILE, json_encode($formattedData));
        
        return $formattedData;
    }

    if (file_exists(GAME_CATEGORY_CACHE_FILE)) {
        return json_decode(file_get_contents(GAME_CATEGORY_CACHE_FILE), true) ?: [];
    }

    return [];
}

function getApiDelay(): int
{
    return (int) getSetting('api_request_delay', 0);
}

function validateAccount(string $bankCode, string $accountNumber): array
{
    $url = 'https://ariepulsa.my.id/api/get-nickname-bank';
    $postData = ['api_key' => ARIEPULSA_API_KEY, 'action'  => 'get-nickname-bank', 'layanan' => $bankCode, 'target'  => $accountNumber];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) return ['error' => 'Gagal menghubungi server bank: ' . $error];
    return json_decode($response, true) ?: ['error' => 'Respons tidak valid dari server bank.'];
}

function validateEwalletServer1(string $ewalletCode, string $accountNumber): array
{
    $url = 'https://ariepulsa.my.id/api/get-nickname-ewallet';
    $postData = ['api_key' => ARIEPULSA_API_KEY, 'action'  => 'get-nickname-ewallet', 'layanan' => $ewalletCode, 'target'  => $accountNumber];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) return ['error' => 'Gagal menghubungi server e-wallet (1): ' . $error];
    return json_decode($response, true) ?: ['error' => 'Respons tidak valid dari server e-wallet (1).'];
}

function validateEwalletServer2(string $ewalletCode, string $accountNumber): array
{
    $url = "https://billpaketdata.com/cekid/ewallet/check_packages/{$accountNumber}/{$ewalletCode}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) return ['error' => 'Gagal menghubungi server e-wallet (2): ' . $error];
    
    $decodedRes = json_decode($response, true);
    if (!$decodedRes) {
        return ['error' => 'Respons tidak valid dari server e-wallet (2).'];
    }

    if (isset($decodedRes['success']) && $decodedRes['success'] == 1 && !empty($decodedRes['cust_name'])) {
        $nameParts = explode(' ', $decodedRes['cust_name'], 2);
        $name = count($nameParts) > 1 ? $nameParts[1] : $decodedRes['cust_name'];
        return [
            'status' => true,
            'data' => [
                'nama' => trim($name),
                'nomor' => $decodedRes['cust_id'] ?? $accountNumber,
                'pesan' => 'Validasi berhasil dari server 2.'
            ]
        ];
    } else {
        return [
            'status' => false,
            'data' => [
                'pesan' => $decodedRes['message'] ?? 'Gagal validasi dari server 2.'
            ]
        ];
    }
}

function validateEwallet(string $ewalletCode, string $accountNumber): array
{
    $response1 = validateEwalletServer1($ewalletCode, $accountNumber);
    if (isset($response1['status']) && $response1['status'] === true && !empty($response1['data']['nama'])) {
        return $response1;
    }

    $response2 = validateEwalletServer2($ewalletCode, $accountNumber);
    if (isset($response2['status']) && $response2['status'] === true && !empty($response2['data']['nama'])) {
        return $response2;
    }

    $errorMsg = $response1['data']['pesan'] ?? $response1['error'] ?? 'Nama pemilik e-wallet tidak dapat ditemukan.';
    return [
        'status' => false,
        'data' => ['pesan' => $errorMsg]
    ];
}

function validateGame(string $gameCode, string $gameId, ?string $zoneId): array
{
    $url = 'https://ariepulsa.my.id/api/get-nickname-game';
    $postData = [
        'api_key' => ARIEPULSA_API_KEY,
        'action'  => 'get-nickname-game',
        'layanan' => $gameCode,
        'target'  => $gameId
    ];
    if ($zoneId) {
        $postData['no_meter'] = $zoneId;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) return ['error' => 'Gagal menghubungi server game: ' . $error];
    return json_decode($response, true) ?: ['error' => 'Respons tidak valid dari server game.'];
}


// --- FUNGSI-FUNGSI DATABASE & LOGIKA INTI ---

// [MODIFIKASI] Fungsi sekarang menerima ewalletCode
function getEwalletAttempt(PDO $db, string $apiKey, string $accountNumber, string $ewalletCode): ?array
{
    $stmt = $db->prepare("SELECT attempt_count FROM ewallet_validation_attempts WHERE api_key = :api_key AND account_number = :number AND ewallet_code = :code");
    $stmt->execute([':api_key' => $apiKey, ':number' => $accountNumber, ':code' => $ewalletCode]);
    $result = $stmt->fetch();
    return $result ?: null;
}

// [MODIFIKASI] Fungsi sekarang menerima ewalletCode
function logEwalletFailure(PDO $db, string $apiKey, string $accountNumber, string $ewalletCode): void
{
    $stmt = $db->prepare("
        INSERT INTO ewallet_validation_attempts (api_key, account_number, ewallet_code, attempt_count)
        VALUES (:api_key, :number, :code, 1)
        ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1
    ");
    $stmt->execute([':api_key' => $apiKey, ':number' => $accountNumber, ':code' => $ewalletCode]);
}

// [MODIFIKASI] Fungsi sekarang menerima ewalletCode
function clearEwalletAttempts(PDO $db, string $apiKey, string $accountNumber, string $ewalletCode): void
{
    $stmt = $db->prepare("DELETE FROM ewallet_validation_attempts WHERE api_key = :api_key AND account_number = :number AND ewallet_code = :code");
    $stmt->execute([':api_key' => $apiKey, ':number' => $accountNumber, ':code' => $ewalletCode]);
}

function enforceRequestDelay(string $apiKey): void
{
    $delay = getApiDelay();
    if ($delay <= 0) {
        return;
    }

    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT MAX(last_request) as last_request FROM api_security_logs WHERE api_key = :api_key");
        $stmt->execute([':api_key' => $apiKey]);
        $log = $stmt->fetch();

        if ($log && $log['last_request']) {
            $lastRequestTime = strtotime($log['last_request']);
            $currentTime = time();
            $elapsed = $currentTime - $lastRequestTime;

            if ($elapsed < $delay) {
                $sleepTime = $delay - $elapsed;
                sleep($sleepTime);
            }
        }
    } catch (PDOException $e) {
        error_log("Error enforcing request delay: " . $e->getMessage());
    }
}


function logSecurityEventAndCheckRateLimit(string $apiKey, string $ipAddress): void
{
    try {
        $db = getDbConnection();
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT request_count, last_request FROM api_security_logs WHERE api_key = :api_key AND ip_address = :ip FOR UPDATE");
        $stmt->execute([':api_key' => $apiKey, ':ip' => $ipAddress]);
        $log = $stmt->fetch();

        $current_count = 1;
        if ($log) {
            $lastRequestTime = strtotime($log['last_request']);
            if (time() - $lastRequestTime > 60) {
                $current_count = 1;
            } else {
                $current_count = $log['request_count'] + 1;
            }
        }

        $stmt = $db->prepare("
            INSERT INTO api_security_logs (api_key, ip_address, request_count, last_request) 
            VALUES (:api_key, :ip, :count, NOW())
            ON DUPLICATE KEY UPDATE 
            request_count = VALUES(request_count), 
            last_request = VALUES(last_request)
        ");
        $stmt->execute([':api_key' => $apiKey, ':ip' => $ipAddress, ':count' => $current_count]);

        $auto_block_enabled = (bool) getSetting('auto_block_enabled', 1);
        if ($auto_block_enabled) {
            $rate_limit_count = (int) getSetting('rate_limit_count', 60);
            if ($current_count > $rate_limit_count) {
                $block_duration = (int) getSetting('auto_block_duration', 24);
                $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$block_duration} hours"));
                
                $blockStmt = $db->prepare("
                    UPDATE api_security_logs 
                    SET is_blocked = 1, blocked_until = ? 
                    WHERE api_key = ? AND ip_address = ?
                ");
                $blockStmt->execute([$blockedUntil, $apiKey, $ipAddress]);
                
                $db->commit();
                http_response_code(429); // Too Many Requests
                die(json_encode(['ok' => false, 'msg' => 'Terlalu banyak permintaan. API Key Anda diblokir sementara.']));
            }
        }

        $db->commit();

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error in security event logging: " . $e->getMessage());
    }
}

function isApiKeyBlocked(string $apiKey, string $ipAddress): bool
{
    try {
        $db = getDbConnection();
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
        
        if ($result['blocked_until'] && $result['blocked_until'] < date('Y-m-d H:i:s')) {
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

function getApiKeyDetails(PDO $db, string $apiKey)
{
    $stmt = $db->prepare("SELECT * FROM api_keys WHERE api_key = :api_key");
    $stmt->execute([':api_key' => $apiKey]);
    return $stmt->fetch();
}

function getCycleInfo(PDO $db, array $keyDetails): array
{
    $created_at_ts = strtotime($keyDetails['created_at']);
    $now_ts = time();
    $seconds_in_cycle = 28 * 24 * 60 * 60;
    $cycles_passed = ($created_at_ts > $now_ts) ? 0 : floor(($now_ts - $created_at_ts) / $seconds_in_cycle);
    $current_cycle_start_ts = $created_at_ts + ($cycles_passed * $seconds_in_cycle);
    $cycle_start_date = date('Y-m-d', $current_cycle_start_ts);
    $usageStmt = $db->prepare("SELECT count FROM `usage` WHERE api_key = :api_key AND usage_date = :usage_date");
    $usageStmt->execute([':api_key' => $keyDetails['api_key'], ':usage_date' => $cycle_start_date]);
    $usage_count = (int)$usageStmt->fetchColumn();
    $limit = (int)$keyDetails['monthly_limit'];
    $remain = max(0, $limit - $usage_count);
    return ['usage_count' => $usage_count, 'limit' => $limit, 'remain' => $remain, 'cycle_start_date' => $cycle_start_date, 'cycle_start_ts' => $current_cycle_start_ts];
}

function incrementUsage(PDO $db, string $apiKey, string $cycleStartDate): void
{
    try {
        $stmt = $db->prepare("INSERT INTO `usage` (api_key, usage_date, count) VALUES (:api_key, :date, 1) ON DUPLICATE KEY UPDATE count = count + 1");
        $stmt->execute([':api_key' => $apiKey, ':date' => $cycleStartDate]);
    } catch (Exception $e) {
        error_log("Gagal menambah penggunaan: " . $e->getMessage());
    }
}

function saveHistory(PDO $db, string $apiKey, array $item, string $ipAddress): void
{
    $stmt = $db->prepare("
        INSERT INTO history (api_key, bank, account_number, account_name, status, message, ip_address, timestamp) 
        VALUES (:api_key, :bank, :number, :name, :status, :message, :ip, NOW())
    ");
    $stmt->execute([
        ':api_key' => $apiKey, 
        ':bank' => $item['bank'], 
        ':number' => substr($item['number'], 0, -4) . '****', 
        ':name' => $item['name'], 
        ':status' => $item['status'], 
        ':message' => $item['message'],
        ':ip' => $ipAddress
    ]);
}

function getHistory(PDO $db, string $apiKey, int $page = 1): array
{
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    $stmt = $db->prepare("SELECT bank, account_number, account_name, status, message, timestamp FROM history WHERE api_key = :api_key ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':api_key', $apiKey, PDO::PARAM_STR);
    $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $historyData = $stmt->fetchAll();

    $statsStmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Berhasil' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'Gagal' THEN 1 ELSE 0 END) as failed
        FROM history WHERE api_key = :api_key
    ");
    $statsStmt->execute([':api_key' => $apiKey]);
    $summary = $statsStmt->fetch();
    
    $totalRecords = (int)($summary['total'] ?? 0);
    $totalPages = ceil($totalRecords / ITEMS_PER_PAGE);

    return [
        'data' => $historyData,
        'summary' => [
            'total' => $totalRecords,
            'success' => (int)($summary['success'] ?? 0),
            'failed' => (int)($summary['failed'] ?? 0)
        ],
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords
        ]
    ];
}

function getNotifications(PDO $db): array
{
    try {
        $stmt = $db->query("SELECT message, created_at FROM notifications WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}


// --- LOGIKA UTAMA APLIKASI ---

$apiKey = getRequestApiKey();
$action = $_REQUEST['action'] ?? '';
$result = ['ok' => false];

if ($action === 'get_notifications') {
    $db = getDbConnection();
    die(json_encode(['ok' => true, 'notifications' => getNotifications($db)]));
}

if (empty($apiKey)) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'msg' => 'API Key tidak disediakan.']));
}

if (!is_dir(LOCK_DIR)) {
    mkdir(LOCK_DIR, 0755, true);
}
$lockFile = LOCK_DIR . '/' . md5($apiKey) . '.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false) {
    http_response_code(500);
    error_log("Gagal membuat atau membuka lock file: {$lockFile}");
    die(json_encode(['ok' => false, 'msg' => 'Terjadi kesalahan internal pada server.']));
}

flock($lockHandle, LOCK_EX);

try {
    $db = getDbConnection();
    $keyDetails = getApiKeyDetails($db, $apiKey);

    if (!$keyDetails || !$keyDetails['is_active']) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'msg' => 'API Key tidak valid atau tidak aktif.']));
    }

    if ($keyDetails['expiry_date'] && $keyDetails['expiry_date'] < date('Y-m-d')) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'msg' => 'API Key sudah kedaluwarsa.']));
    }

    $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    enforceRequestDelay($apiKey);
    logSecurityEventAndCheckRateLimit($apiKey, $clientIP);

    if (isApiKeyBlocked($apiKey, $clientIP)) {
        http_response_code(429);
        die(json_encode(['ok' => false, 'msg' => 'API Key diblokir sementara karena aktivitas mencurigakan.']));
    }

    switch ($action) {
        case 'get_options':
            $result['ok'] = true;
            $result['options'] = array_map(fn($opt) => ['code' => $opt['code'], 'label' => $opt['name']], getBankOptions());
            $result['game_options'] = getGameCategories();
            break;

        case 'validate_account':
            $cycleInfo = getCycleInfo($db, $keyDetails);
            
            $code = $_POST['account_type'] ?? '';
            $num = $_POST['account_number'] ?? '';
            if (empty($code) || empty($num) || !ctype_digit($num)) {
                $result['msg'] = 'Mohon lengkapi semua field dengan benar.';
                break;
            }

            $all_options = getBankOptions();
            $optionsMap = array_column($all_options, 'name', 'code');
            if (!isset($optionsMap[$code])) {
                $result['msg'] = 'Kode bank/e-wallet tidak valid.';
                break;
            }

            $isEwallet = in_array(strtolower($code), array_map('strtolower', EWALLET_CODES));
            if ($isEwallet && (strlen($num) < 9 || strlen($num) > 15)) {
                $result['msg'] = 'Panjang nomor e-wallet harus antara 9 hingga 15 digit.';
                break;
            }
            
            if ($isEwallet) {
                // [MODIFIKASI] Panggil fungsi dengan ewallet code
                $attemptInfo = getEwalletAttempt($db, $apiKey, $num, $code);
                $attemptCount = $attemptInfo['attempt_count'] ?? 0;
                
                if ($attemptCount >= 2) {
                    if ($cycleInfo['remain'] <= 0) {
                        $result['msg'] = 'Limit penggunaan untuk siklus ini telah tercapai.';
                        break;
                    }
                    incrementUsage($db, $apiKey, $cycleInfo['cycle_start_date']);
                }
            } else { 
                 if ($cycleInfo['remain'] <= 0) {
                    $result['msg'] = 'Limit penggunaan untuk siklus ini telah tercapai.';
                    break;
                }
            }


            $historyItem = ['bank' => $optionsMap[$code], 'number' => $num];
            
            if ($isEwallet) {
                $apiRes = validateEwallet($code, $num);
                if (isset($apiRes['status']) && $apiRes['status'] === true && !empty($apiRes['data']['nama'])) {
                    // [MODIFIKASI] Panggil fungsi dengan ewallet code
                    clearEwalletAttempts($db, $apiKey, $num, $code);

                    if ($attemptCount == 0) {
                         if ($cycleInfo['remain'] <= 0) {
                            $result['msg'] = 'Limit penggunaan untuk siklus ini telah tercapai.';
                            break;
                        }
                        incrementUsage($db, $apiKey, $cycleInfo['cycle_start_date']);
                    }

                    $accountName = trim($apiRes['data']['nama']);
                    $accountNo = $apiRes['data']['nomor'] ?? $num;
                    $result = ['ok' => true, 'account_name' => $accountName, 'account_number' => $accountNo, 'bank_label' => $optionsMap[$code]];
                    $historyItem += ['name' => $accountName, 'status' => 'Berhasil', 'message' => 'Validasi berhasil'];
                } else {
                    // [MODIFIKASI] Panggil fungsi dengan ewallet code
                    logEwalletFailure($db, $apiKey, $num, $code);
                    $newAttemptCount = $attemptCount + 1;
                    $defaultMessage = $apiRes['data']['pesan'] ?? $apiRes['error'] ?? 'Nama pemilik e-wallet tidak dapat ditemukan.';

                    if ($newAttemptCount < 3) {
                        $result['msg'] = "{$defaultMessage} (Percobaan ke-{$newAttemptCount} dari 2). Kuota tidak akan dikurangi untuk percobaan ini.";
                    } else {
                        $result['msg'] = $defaultMessage;
                    }
                    $historyItem += ['name' => 'N/A', 'status' => 'Gagal', 'message' => $result['msg']];
                }
            } else { 
                incrementUsage($db, $apiKey, $cycleInfo['cycle_start_date']);
                $apiRes = validateAccount($code, $num);
                if (isset($apiRes['status']) && $apiRes['status'] === true && !empty($apiRes['data']['nama'])) {
                    $accountName = trim($apiRes['data']['nama']);
                    $accountNo = $apiRes['data']['nomor'] ?? $num;
                    $result = ['ok' => true, 'account_name' => $accountName, 'account_number' => $accountNo, 'bank_label' => $optionsMap[$code]];
                    $historyItem += ['name' => $accountName, 'status' => 'Berhasil', 'message' => 'Validasi berhasil'];
                } else {
                    $result['msg'] = $apiRes['error'] ?? $apiRes['data']['pesan'] ?? 'Nama pemilik rekening tidak dapat ditemukan.';
                    $historyItem += ['name' => 'N/A', 'status' => 'Gagal', 'message' => $result['msg']];
                }
            }
            saveHistory($db, $apiKey, $historyItem, $clientIP);
            break;

        case 'validate_game':
            $cycleInfo = getCycleInfo($db, $keyDetails);
            if ($cycleInfo['remain'] <= 0) {
                $result['msg'] = 'Limit penggunaan untuk siklus ini telah tercapai.';
                break;
            }
            $gameCode = $_POST['game_code'] ?? '';
            $gameId = $_POST['game_id'] ?? '';
            $zoneId = $_POST['zone_id'] ?? null;

            if (empty($gameCode) || empty($gameId)) {
                $result['msg'] = 'Mohon lengkapi Kode Game dan ID Game.';
                break;
            }
            if (in_array(strtoupper($gameCode), GAME_CODES_WITH_ZONE) && empty($zoneId)) {
                $result['msg'] = 'Game ini memerlukan Zona ID.';
                break;
            }

            incrementUsage($db, $apiKey, $cycleInfo['cycle_start_date']);
            $historyItem = ['bank' => "GAME: {$gameCode}", 'number' => "{$gameId}|{$zoneId}"];
            
            $apiRes = validateGame($gameCode, $gameId, $zoneId);

            if (isset($apiRes['status']) && $apiRes['status'] === true && !empty($apiRes['data']['nickname'])) {
                $nickname = trim($apiRes['data']['nickname']);
                $result = ['ok' => true, 'nickname' => $nickname, 'game_id' => $gameId, 'zone_id' => $zoneId, 'game_label' => $gameCode];
                $historyItem += ['name' => $nickname, 'status' => 'Berhasil', 'message' => 'Validasi nickname berhasil'];
            } else {
                $result['msg'] = $apiRes['data']['pesan'] ?? $apiRes['error'] ?? 'Nickname tidak dapat ditemukan.';
                $historyItem += ['name' => 'N/A', 'status' => 'Gagal', 'message' => $result['msg']];
            }
            saveHistory($db, $apiKey, $historyItem, $clientIP);
            break;


        case 'get_history':
            $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $result = ['ok' => true] + getHistory($db, $apiKey, $page);
            break;

        case 'get_stats':
            $cycleInfo = getCycleInfo($db, $keyDetails);
            $cycleStartDate = date('Y-m-d', $cycleInfo['cycle_start_ts']);

            $stmt = $db->prepare("
                SELECT DATE(timestamp) as date, COUNT(id) as count 
                FROM history 
                WHERE api_key = :api_key AND status = 'Berhasil' AND timestamp >= :start_date
                GROUP BY DATE(timestamp) 
                ORDER BY date ASC
            ");
            $stmt->execute([':api_key' => $apiKey, ':start_date' => $cycleStartDate]);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $labels = [];
            $data = [];
            $currentDate = new DateTime($cycleStartDate);
            $endDate = new DateTime(); // Hari ini

            while ($currentDate <= $endDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->format('d M');
                $data[] = (int)($rows[$dateKey] ?? 0);
                $currentDate->modify('+1 day');
            }

            $stats = ['labels' => $labels, 'data' => $data];
            $result['ok'] = true;
            $result['stats'] = $stats;
            break;
        
        default:
            http_response_code(400);
            $result['msg'] = 'Aksi tidak valid.';
            break;
    }

    $finalCycleInfo = getCycleInfo($db, $keyDetails);
    $result['limit'] = $finalCycleInfo['limit'];
    $result['remain'] = $finalCycleInfo['remain'];

    echo json_encode($result, JSON_PRETTY_PRINT);

} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
?>

