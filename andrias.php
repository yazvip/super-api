<?php
session_start();
require_once 'admin_functions.php';

// Jalankan inisialisasi database dari file fungsi
initializeDatabase();

// --- LOGIKA LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'do_login') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (verifyCSRFToken($csrf_token) && verifyAdminPassword($password)) {
        $_SESSION['is_admin'] = true;
        logAdminActivity('ADMIN_LOGIN_SUCCESS');
        // Regenerate session ID for security
        session_regenerate_id(true);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?page=dashboard');
        exit;
    } else {
        logAdminActivity('ADMIN_LOGIN_FAIL');
        $login_error = 'Kata sandi salah!';
    }
}

// --- LOGIKA LOGOUT ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- PEMERIKSAAN AUTENTIKASI ---
$is_logged_in = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Jika tidak login, tampilkan halaman login dan hentikan skrip
if (!$is_logged_in) {
    $csrf_token = generateCSRFToken();
    http_response_code(200);
?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
            .login-container {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .login-card {
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            }
            .floating-animation {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
            }
        </style>
    </head>
    <body class="login-container flex items-center justify-center p-4">
        <div class="w-full max-w-md mx-auto">
            <!-- Floating Elements -->
            <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full floating-animation"></div>
            <div class="absolute top-32 right-16 w-16 h-16 bg-white/10 rounded-full floating-animation" style="animation-delay: -2s;"></div>
            <div class="absolute bottom-20 left-20 w-12 h-12 bg-white/10 rounded-full floating-animation" style="animation-delay: -4s;"></div>

            <div class="login-card p-8 rounded-3xl relative z-10">
                <div class="flex flex-col items-center mb-8">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 rounded-2xl mb-4 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Admin Panel</h1>
                    <p class="text-slate-600 mt-2">Masuk untuk mengakses dashboard</p>
                </div>

                <?php if (isset($login_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium"><?= e($login_error) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="do_login">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Kata Sandi</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                   class="block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                   placeholder="Masukkan kata sandi admin">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Masuk ke Dashboard
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-200">
                    <div class="text-center">
                        <p class="text-xs text-slate-500">
                            © 2024 Admin Panel. Dilindungi dengan keamanan tingkat tinggi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
<?php
    exit;
}

// --- LOGIKA UNTUK PENGGUNA YANG SUDAH LOGIN ---

// ROUTING API INTERNAL
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    $db = getDbConnection();
    $action = $_REQUEST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        switch ($action) {
            case 'get_monitoring_data':
                $monitoringData = getMonitoringData();
                sendJsonResponse(true, 'Data monitoring berhasil diambil.', $monitoringData);
                break;

            case 'clear_cache':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }

                $cache_dir = __DIR__ . '/cache';
                $cleared_count = 0;
                $errors = [];

                if (is_dir($cache_dir)) {
                    $files = scandir($cache_dir);
                    if ($files) {
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..' && $file !== '.gitignore' && $file !== 'game_categories.json') {
                                if (@unlink($cache_dir . '/' . $file)) {
                                    $cleared_count++;
                                } else {
                                    $errors[] = "Gagal menghapus file: {$file}";
                                }
                            }
                        }
                    }
                }

                if (empty($errors)) {
                    logAdminActivity('CACHE_CLEARED', "{$cleared_count} file dibersihkan.");
                    sendJsonResponse(true, "Cache berhasil dibersihkan. {$cleared_count} file dihapus.");
                } else {
                    sendJsonResponse(false, "Gagal membersihkan sebagian cache.", ['errors' => $errors], 500);
                }
                break;

            case 'get_key_details':
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) sendJsonResponse(false, 'ID tidak valid.', [], 400);

                $stmt = $db->prepare("SELECT * FROM api_keys WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $keyData = $stmt->fetch();

                if (!$keyData) sendJsonResponse(false, 'API Key tidak ditemukan.', [], 404);

                sendJsonResponse(true, 'Detail API Key berhasil diambil.', $keyData);
                break;

            case 'get_api_keys_list':
                $stmt = $db->query("SELECT api_key, nama FROM api_keys ORDER BY created_at DESC");
                sendJsonResponse(true, 'Daftar API Keys berhasil diambil.', $stmt->fetchAll());
                break;

            case 'get_history':
                $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
                $api_key_filter = $_GET['api_key_filter'] ?? '';
                $items_per_page = 20;
                $offset = ($page - 1) * $items_per_page;

                $where_clause = '';
                $params = [];
                if (!empty($api_key_filter)) {
                    $where_clause = 'WHERE api_key = :api_key';
                    $params[':api_key'] = $api_key_filter;
                }

                $count_stmt = $db->prepare("SELECT COUNT(id) FROM history $where_clause");
                $count_stmt->execute($params);
                $total_records = $count_stmt->fetchColumn();

                $total_pages = ceil($total_records / $items_per_page);

                $stmt = $db->prepare("SELECT * FROM history $where_clause ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $history_data = $stmt->fetchAll();
                sendJsonResponse(true, 'Data riwayat berhasil diambil.', ['history' => $history_data, 'pagination' => ['currentPage' => $page, 'totalPages' => $total_pages, 'totalRecords' => $total_records]]);
                break;

            // [BARU] Endpoint untuk statistik riwayat
            case 'get_history_stats':
                $api_key_filter = $_GET['api_key_filter'] ?? '';

                $sql = "SELECT
                            COUNT(id) as total,
                            SUM(CASE WHEN status = 'Berhasil' THEN 1 ELSE 0 END) as success,
                            SUM(CASE WHEN status = 'Gagal' THEN 1 ELSE 0 END) as failed
                        FROM history";

                $params = [];
                if (!empty($api_key_filter)) {
                    $sql .= " WHERE api_key = :api_key";
                    $params[':api_key'] = $api_key_filter;
                }

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                // Memastikan nilai adalah integer, bukan null jika tabel kosong
                $stats['total'] = (int)($stats['total'] ?? 0);
                $stats['success'] = (int)($stats['success'] ?? 0);
                $stats['failed'] = (int)($stats['failed'] ?? 0);

                sendJsonResponse(true, 'Statistik berhasil diambil.', $stats);
                break;

            case 'delete_history':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $id = $input['id'] ?? null;
                if (!filter_var($id, FILTER_VALIDATE_INT)) sendJsonResponse(false, 'ID tidak valid.', [], 400);
                $stmt = $db->prepare("DELETE FROM history WHERE id = :id");
                $stmt->execute([':id' => $id]);
                sendJsonResponse(true, 'Data riwayat berhasil dihapus.');
                break;

            case 'delete_all_history':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }

                $api_key_filter = $input['api_key_filter'] ?? '';
                $where_clause = '';
                $params = [];

                if (!empty($api_key_filter)) {
                    $where_clause = 'WHERE api_key = :api_key';
                    $params[':api_key'] = $api_key_filter;
                }

                $stmt = $db->prepare("DELETE FROM history $where_clause");
                $stmt->execute($params);
                $deleted_count = $stmt->rowCount();

                $message = $api_key_filter
                    ? "Berhasil menghapus {$deleted_count} riwayat untuk API Key yang dipilih."
                    : "Berhasil menghapus {$deleted_count} riwayat.";

                sendJsonResponse(true, $message);
                break;

            case 'delete_all_notifications':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $deleted_count = $db->exec("DELETE FROM notifications");
                sendJsonResponse(true, "Berhasil menghapus {$deleted_count} notifikasi.");
                break;

            case 'get_notifications':
                $stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC");
                sendJsonResponse(true, 'Notifikasi berhasil diambil.', ['notifications' => $stmt->fetchAll()]);
                break;

            case 'add_notification':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $message = trim($input['message'] ?? '');
                if (empty($message) || mb_strlen($message) > 500) sendJsonResponse(false, 'Pesan tidak valid (1-500 karakter).', [], 400);
                $stmt = $db->prepare("INSERT INTO notifications (message, created_at) VALUES (:message, NOW())");
                $stmt->execute([':message' => $message]);
                sendJsonResponse(true, 'Notifikasi berhasil ditambahkan.');
                break;

            case 'delete_notification':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $id = $input['id'] ?? null;
                if (!filter_var($id, FILTER_VALIDATE_INT)) sendJsonResponse(false, 'ID tidak valid.', [], 400);
                $stmt = $db->prepare("DELETE FROM notifications WHERE id = :id");
                $stmt->execute([':id' => $id]);
                sendJsonResponse(true, 'Notifikasi berhasil dihapus.');
                break;

            case 'create_backup':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $backupType = $input['backup_type'] ?? '';
                if (!in_array($backupType, ['full', 'database', 'files'])) {
                    sendJsonResponse(false, 'Tipe backup tidak valid.', [], 400);
                }

                $result = createBackup($backupType);
                if ($result['success']) {
                    sendJsonResponse(true, 'Backup berhasil dibuat.', $result);
                } else {
                    sendJsonResponse(false, $result['error'], [], 500);
                }
                break;

            case 'get_backup_logs':
                $stmt = $db->query("SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 20");
                sendJsonResponse(true, 'Log backup berhasil diambil.', ['logs' => $stmt->fetchAll()]);
                break;

            case 'test_api_endpoint':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }

                $endpoint = $input['endpoint'] ?? '';
                $method = strtoupper($input['method'] ?? 'GET');
                $requestData = $input['request_data'] ?? '';

                $startTime = microtime(true);

                try {
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $endpoint,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_CUSTOMREQUEST => $method
                    ]);

                    if ($method === 'POST' && !empty($requestData)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    }

                    $response = curl_exec($ch);
                    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    $responseTime = round((microtime(true) - $startTime) * 1000, 3);

                    $status = $error ? 'error' : 'success';
                    $responseData = $error ? $error : $response;

                    // Log the test
                    $logStmt = $db->prepare("
                        INSERT INTO api_test_logs (endpoint, method, request_data, response_data, response_code, response_time, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $logStmt->execute([$endpoint, $method, $requestData, $responseData, $responseCode, $responseTime, $status]);

                    sendJsonResponse(true, 'Test API berhasil.', [
                        'response' => $responseData,
                        'response_code' => $responseCode,
                        'response_time' => $responseTime,
                        'status' => $status
                    ]);

                } catch (Exception $e) {
                    sendJsonResponse(false, 'Error testing API: ' . $e->getMessage(), [], 500);
                }
                break;

            case 'get_api_test_logs':
                $stmt = $db->query("SELECT * FROM api_test_logs ORDER BY created_at DESC LIMIT 50");
                sendJsonResponse(true, 'Log test API berhasil diambil.', ['logs' => $stmt->fetchAll()]);
                break;

            case 'get_bank_codes':
                $stmt = $db->query("SELECT * FROM bank_codes ORDER BY name ASC");
                sendJsonResponse(true, 'Bank codes berhasil diambil.', ['bank_codes' => $stmt->fetchAll()]);
                break;

            case 'add_bank_code':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $code = trim($input['code'] ?? '');
                $name = trim($input['name'] ?? '');
                $type = $input['type'] ?? 'bank';

                if (empty($code) || empty($name)) {
                    sendJsonResponse(false, 'Kode dan nama harus diisi.', [], 400);
                }

                try {
                    $stmt = $db->prepare("INSERT INTO bank_codes (code, name, type) VALUES (?, ?, ?)");
                    $stmt->execute([$code, $name, $type]);
                    sendJsonResponse(true, 'Bank code berhasil ditambahkan.');
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        sendJsonResponse(false, 'Kode sudah ada.', [], 400);
                    } else {
                        sendJsonResponse(false, 'Error database: ' . $e->getMessage(), [], 500);
                    }
                }
                break;

            case 'update_bank_code':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $id = $input['id'] ?? null;
                $code = trim($input['code'] ?? '');
                $name = trim($input['name'] ?? '');
                $type = $input['type'] ?? 'bank';
                $isActive = $input['is_active'] ?? 1;

                if (!$id || empty($code) || empty($name)) {
                    sendJsonResponse(false, 'Data tidak lengkap.', [], 400);
                }

                try {
                    $stmt = $db->prepare("UPDATE bank_codes SET code = ?, name = ?, type = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$code, $name, $type, $isActive, $id]);
                    sendJsonResponse(true, 'Bank code berhasil diupdate.');
                } catch (PDOException $e) {
                    sendJsonResponse(false, 'Error database: ' . $e->getMessage(), [], 500);
                }
                break;

            case 'delete_bank_code':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $id = $input['id'] ?? null;
                if (!$id) {
                    sendJsonResponse(false, 'ID tidak valid.', [], 400);
                }

                $stmt = $db->prepare("DELETE FROM bank_codes WHERE id = ?");
                $stmt->execute([$id]);
                sendJsonResponse(true, 'Bank code berhasil dihapus.');
                break;

            case 'get_security_logs':
                $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
                $limit = 20;
                $offset = ($page - 1) * $limit;

                $countStmt = $db->query("SELECT COUNT(*) FROM api_security_logs");
                $totalRecords = $countStmt->fetchColumn();
                $totalPages = ceil($totalRecords / $limit);

                $stmt = $db->prepare("
                    SELECT asl.*, ak.nama
                    FROM api_security_logs asl
                    LEFT JOIN api_keys ak ON asl.api_key = ak.api_key
                    ORDER BY asl.last_request DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);
                $logs = $stmt->fetchAll();

                sendJsonResponse(true, 'Security logs berhasil diambil.', [
                    'logs' => $logs,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => $totalPages,
                        'totalRecords' => $totalRecords
                    ]
                ]);
                break;

            case 'block_api_key':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $apiKey = $input['api_key'] ?? '';
                $ipAddress = $input['ip_address'] ?? '';
                $blockHours = (int)($input['block_hours'] ?? 24);

                if (empty($apiKey) || empty($ipAddress)) {
                    sendJsonResponse(false, 'Data tidak lengkap.', [], 400);
                }

                $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$blockHours} hours"));

                $stmt = $db->prepare("
                    UPDATE api_security_logs
                    SET is_blocked = 1, blocked_until = ?
                    WHERE api_key = ? AND ip_address = ?
                ");
                $stmt->execute([$blockedUntil, $apiKey, $ipAddress]);

                sendJsonResponse(true, "API key berhasil diblokir selama {$blockHours} jam.");
                break;

            case 'unblock_api_key':
                if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
                    sendJsonResponse(false, 'Token CSRF tidak valid.', [], 403);
                }
                $apiKey = $input['api_key'] ?? '';
                $ipAddress = $input['ip_address'] ?? '';

                if (empty($apiKey) || empty($ipAddress)) {
                    sendJsonResponse(false, 'Data tidak lengkap.', [], 400);
                }

                $stmt = $db->prepare("
                    UPDATE api_security_logs
                    SET is_blocked = 0, blocked_until = NULL
                    WHERE api_key = ? AND ip_address = ?
                ");
                $stmt->execute([$apiKey, $ipAddress]);

                sendJsonResponse(true, 'API key berhasil di-unblock.');
                break;

            default:
                sendJsonResponse(false, 'Aksi tidak diketahui.', [], 404);
                break;
        }
    } catch (PDOException $e) {
        error_log("API Error: " . $e->getMessage());
        sendJsonResponse(false, 'Terjadi kesalahan pada server.', [], 500);
    }
    exit;
}

// LOGIKA PENANGANAN FORM POST
$db = getDbConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $post_action = $_POST['action'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';

        // Verify CSRF token for all admin actions
        if (!verifyCSRFToken($csrf_token)) {
            header('Location: ?page=dashboard&error=csrf_invalid');
            exit;
        }

        switch ($post_action) {
            case 'change_admin_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (!verifyAdminPassword($current_password)) {
                    header('Location: ?page=settings&error=current_password_wrong');
                    exit;
                }

                if ($new_password !== $confirm_password) {
                    header('Location: ?page=settings&error=password_mismatch');
                    exit;
                }

                if (strlen($new_password) < 6) {
                    header('Location: ?page=settings&error=password_too_short');
                    exit;
                }

                if (updateAdminPassword($new_password)) {
                    logAdminActivity('ADMIN_PASSWORD_CHANGE');
                    header('Location: ?page=settings&status=password_changed');
                } else {
                    header('Location: ?page=settings&error=password_change_failed');
                }
                exit;

            // [BARU] Menangani pembaruan pengaturan keamanan
            case 'update_security_settings':
                $rate_limit = filter_input(INPUT_POST, 'rate_limit_count', FILTER_VALIDATE_INT, ['options' => ['default' => 60, 'min_range' => 1]]);
                $block_duration = filter_input(INPUT_POST, 'auto_block_duration', FILTER_VALIDATE_INT, ['options' => ['default' => 24, 'min_range' => 1]]);
                $auto_block_enabled = isset($_POST['auto_block_enabled']) ? '1' : '0';

                updateSetting('rate_limit_count', (string)$rate_limit);
                updateSetting('auto_block_duration', (string)$block_duration);
                updateSetting('auto_block_enabled', $auto_block_enabled);

                logAdminActivity('SETTINGS_UPDATE_SECURITY');
                header('Location: ?page=security&status=security_settings_updated');
                exit;

            case 'update_api_settings':
                $delay = filter_input(INPUT_POST, 'api_request_delay', FILTER_VALIDATE_INT);

                if ($delay === false || $delay < 0) {
                    $delay = 0; // Default ke 0 jika input tidak valid
                }

                if (updateSetting('api_request_delay', (string)$delay)) {
                    logAdminActivity('SETTINGS_UPDATE_API', "Set request delay to {$delay}");
                    header('Location: ?page=settings&status=api_settings_updated');
                } else {
                    header('Location: ?page=settings&error=api_settings_failed');
                }
                exit;

            case 'update_key':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $nama = trim($_POST['nama']);
                $nomor_wa = trim($_POST['nomor_wa']);
                $limit = filter_input(INPUT_POST, 'monthly_limit', FILTER_VALIDATE_INT);
                $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                if (!$id || !$limit) {
                    header('Location: ?page=api_keys&error=invalid_input'); exit;
                }
                $stmt = $db->prepare("UPDATE api_keys SET nama = :nama, nomor_wa = :nomor_wa, monthly_limit = :limit, expiry_date = :expiry, is_active = :active WHERE id = :id");
                $stmt->execute([':nama' => $nama, ':nomor_wa' => $nomor_wa, ':limit' => $limit, ':expiry' => $expiry, ':active' => $is_active, ':id' => $id]);
                logAdminActivity('API_KEY_UPDATE', "ID: {$id}, Nama: {$nama}");
                header('Location: ?page=api_keys&status=key_updated'); exit;

            case 'add_key':
                $new_key = bin2hex(random_bytes(6));
                $nama = trim($_POST['nama']);
                $nomor_wa = trim($_POST['nomor_wa']);
                $limit = filter_input(INPUT_POST, 'monthly_limit', FILTER_VALIDATE_INT, ['options' => ['default' => 100]]);
                $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

                $stmt = $db->prepare("INSERT INTO api_keys (api_key, nama, nomor_wa, monthly_limit, expiry_date, created_at) VALUES (:api_key, :nama, :nomor_wa, :limit, :expiry, NOW())");
                $stmt->execute([':api_key' => $new_key, ':nama' => $nama, ':nomor_wa' => $nomor_wa, ':limit' => $limit, ':expiry' => $expiry]);

                $new_key_id = $db->lastInsertId();
                logAdminActivity('API_KEY_CREATE', "Key: {$new_key}, Nama: {$nama}");

                header('Location: ?page=api_keys&new_key_id=' . $new_key_id);
                exit;

            case 'delete_key':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if ($id) {
                    $db->beginTransaction();
                    $keyStmt = $db->prepare("SELECT api_key FROM api_keys WHERE id = :id");
                    $keyStmt->execute([':id' => $id]);
                    $apiKey = $keyStmt->fetchColumn();
                    if ($apiKey) {
                        $db->prepare("DELETE FROM `usage` WHERE api_key = :api_key")->execute([':api_key' => $apiKey]);
                        $db->prepare("DELETE FROM history WHERE api_key = :api_key")->execute([':api_key' => $apiKey]);
                        $db->prepare("DELETE FROM api_keys WHERE id = :id")->execute([':id' => $id]);
                        $db->commit();
                        logAdminActivity('API_KEY_DELETE', "Key: {$apiKey}");
                        header('Location: ?page=api_keys&status=key_deleted');
                    } else {
                        $db->rollBack();
                        header('Location: ?page=api_keys&error=key_not_found');
                    }
                }
                exit;

            case 'reset_usage':
                $api_key_to_reset = $_POST['api_key'] ?? '';
                if (!empty($api_key_to_reset)) {
                    $cycle_info = getCurrentCycleUsage($api_key_to_reset);
                    $stmt = $db->prepare("UPDATE `usage` SET `count` = 0 WHERE api_key = :api_key AND usage_date = :usage_date");
                    $stmt->execute([':api_key' => $api_key_to_reset, ':usage_date' => $cycle_info['usage_date_key']]);
                    header('Location: ?page=api_keys&status=usage_reset');
                }
                exit;

            case 'do_update_notification':
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $message = trim($_POST['message'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                if ($id && !empty($message)) {
                    $stmt = $db->prepare("UPDATE notifications SET message = :message, is_active = :is_active WHERE id = :id");
                    $stmt->execute([':message' => $message, ':is_active' => $is_active, ':id' => $id]);
                    header('Location: ?page=notifications&status=notification_updated');
                } else {
                    header('Location: ?page=notifications&error=invalid_input');
                }
                exit;
        }
    } catch (PDOException $e) {
        error_log("Admin Action Error: " . $e->getMessage());
        header('Location: ?page=dashboard&error=db_error');
        exit;
    }
}

$page = $_GET['page'] ?? 'dashboard';
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #a7b5c9; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        #notification { position: fixed; top: 1.5rem; right: 1.5rem; z-index: 50; }
        .sidebar {
            transition: transform 0.3s ease-in-out;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
        }
        .nav-item {
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .nav-item:hover {
            transform: translateX(4px);
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #3b82f6, #8b5cf6);
            border-radius: 0 2px 2px 0;
        }
        .main-content {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.8);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="antialiased text-slate-700">
<div id="app-container">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed top-0 left-0 h-full w-64 text-gray-300 flex-shrink-0 p-6 flex flex-col z-40 lg:translate-x-0">
        <div class="flex items-center gap-3 px-2 mb-10">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-xl shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white tracking-wide">Admin Panel</h1>
                <p class="text-xs text-gray-400">Management System</p>
            </div>
        </div>
        <nav class="flex-grow space-y-3">
            <a href="?page=dashboard" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'dashboard' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="?page=monitoring" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'monitoring' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-medium">Monitoring</span>
            </a>
            <a href="?page=api_keys" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= in_array($page, ['api_keys', 'edit_key']) ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H5v-2H3v-2H1v-4a6 6 0 016-6h4a6 6 0 016 6z"></path></svg>
                <span class="font-medium">API Keys</span>
            </a>
            <a href="?page=history" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'history' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-medium">Riwayat</span>
            </a>
            <a href="?page=notifications" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= in_array($page, ['notifications', 'edit_notification']) ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span class="font-medium">Notifikasi</span>
            </a>
            <a href="?page=backup" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'backup' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                <span class="font-medium">Backup</span>
            </a>
            <a href="?page=api_tester" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'api_tester' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364-.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <span class="font-medium">API Tester</span>
            </a>
            <a href="?page=bank_codes" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'bank_codes' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span class="font-medium">Bank Codes</span>
            </a>
            <a href="?page=security" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'security' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span class="font-medium">Security</span>
            </a>
            <a href="?page=settings" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl <?= $page === 'settings' ? 'active bg-gray-700/50 text-white' : 'hover:bg-gray-700/30 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="font-medium">Settings</span>
            </a>
        </nav>
        <div class="mt-auto pt-6 border-t border-gray-700">
            <a href="?action=logout" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/20 hover:text-red-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

    <!-- Main Content -->
    <div class="lg:ml-64 flex-1 flex flex-col min-h-screen">
        <header class="main-content sticky top-0 border-b border-white/20 p-4 z-20 flex items-center justify-between lg:hidden">
            <button id="menu-toggle" class="p-2 rounded-md hover:bg-slate-100">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <h1 class="text-lg font-bold text-slate-800">Admin Panel</h1>
        </header>

        <main class="flex-1 p-6 md:p-8 main-content">
            <div id="notification"></div>
            <?php
            // Menampilkan notifikasi status/error
            if (isset($_GET['status'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-r-xl mb-6 shadow-lg card-hover">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-bold">Berhasil</p>
                        <p><?= e($_GET['status']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-xl mb-6 shadow-lg card-hover">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 10.414l1.707-1.707a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-bold">Gagal</p>
                        <p><?= e($_GET['error']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Router untuk memuat konten halaman
            $page_file = "admin_pages/{$page}.php";
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                include "admin_pages/dashboard.php"; // Default ke dashboard jika file tidak ada
            }
            ?>
        </main>
    </div>
</div>

<!-- Modal Konfirmasi Global -->
<div id="confirm-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-800 w-full max-w-md p-6 rounded-2xl shadow-xl">
        <div class="flex items-center gap-4 mb-4">
            <div id="confirm-icon" class="w-12 h-12 rounded-full flex items-center justify-center">
                <!-- Icon akan diisi oleh JavaScript -->
            </div>
            <div>
                <h3 id="confirm-title" class="text-lg font-bold text-slate-800 dark:text-white"></h3>
                <p id="confirm-subtitle" class="text-sm text-slate-600 dark:text-slate-400"></p>
            </div>
        </div>
        <div class="mb-6">
            <p id="confirm-message" class="text-slate-700 dark:text-slate-300"></p>
            <div id="confirm-warning" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mt-3 hidden">
                <p class="text-red-800 dark:text-red-200 text-sm font-medium"></p>
            </div>
        </div>
        <div class="flex gap-3">
            <button id="confirm-yes" class="flex-1 px-4 py-2 text-white rounded-lg font-semibold">
                <!-- Text akan diisi oleh JavaScript -->
            </button>
            <button id="confirm-no" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const page = '<?= e($page) ?>';
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('hidden');
        });
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // Global confirmation modal
    window.showConfirmModal = function(options) {
        const modal = document.getElementById('confirm-modal');
        const icon = document.getElementById('confirm-icon');
        const title = document.getElementById('confirm-title');
        const subtitle = document.getElementById('confirm-subtitle');
        const message = document.getElementById('confirm-message');
        const warning = document.getElementById('confirm-warning');
        const yesBtn = document.getElementById('confirm-yes');
        const noBtn = document.getElementById('confirm-no');

        // Set content
        title.textContent = options.title || 'Konfirmasi';
        subtitle.textContent = options.subtitle || '';
        message.textContent = options.message || '';
        yesBtn.textContent = options.confirmText || 'Ya';

        // Set icon and colors
        const iconClass = options.type === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600';
        const btnClass = options.type === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700';

        icon.className = `w-12 h-12 rounded-full flex items-center justify-center ${iconClass}`;
        icon.innerHTML = options.type === 'danger' ?
            '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>' :
            '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

        yesBtn.className = `flex-1 px-4 py-2 text-white rounded-lg font-semibold ${btnClass}`;

        // Show/hide warning
        if (options.warning) {
            warning.querySelector('p').textContent = options.warning;
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }

        // Set up event handlers
        yesBtn.onclick = () => {
            modal.classList.add('hidden');
            if (options.onConfirm) options.onConfirm();
        };

        noBtn.onclick = () => {
            modal.classList.add('hidden');
            if (options.onCancel) options.onCancel();
        };

        modal.classList.remove('hidden');
    };

    window.showNotification = function(message, type = 'success') {
        const notifContainer = document.getElementById('notification');
        const notifId = 'notif-' + Date.now();
        const bgColor = type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600';
        const icon = type === 'success'
            ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`
            : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

        const notifElement = document.createElement('div');
        notifElement.id = notifId;
        notifElement.className = `flex items-center gap-3 w-full max-w-sm p-4 text-white ${bgColor} rounded-xl shadow-2xl transition-all duration-300 transform translate-x-full opacity-0`;
        notifElement.innerHTML = `<div>${icon}</div><p class="text-sm font-medium">${escapeHTML(message)}</p>`;
        notifContainer.appendChild(notifElement);
        setTimeout(() => {
            notifElement.classList.remove('translate-x-full', 'opacity-0');
            notifElement.classList.add('translate-x-0', 'opacity-100');
        }, 10);
        setTimeout(() => {
            notifElement.classList.add('opacity-0');
            notifElement.addEventListener('transitionend', () => notifElement.remove());
        }, 4000);
    };

    async function apiCall(action, options = {}) {
        try {
            const response = await fetch(`?api=true&action=${action}`, options);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || `HTTP error! status: ${response.status}`);
            }
            return result;
        } catch (error) {
            window.showNotification(error.message, 'error');
            throw error;
        }
    }

    function escapeHTML(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
    }

    if (page === 'history') {
        const historyTbody = document.getElementById('history-tbody');
        const paginationControls = document.getElementById('pagination-controls');
        let currentPage = 1;

        window.fetchHistory = async function(page = 1) {
            try {
                historyTbody.innerHTML = `<tr><td colspan="8" class="text-center p-6 text-slate-500">Memuat data...</td></tr>`;
                const apiKeyFilter = window.getCurrentApiKeyFilter ? window.getCurrentApiKeyFilter() : '';
                const result = await apiCall(`get_history&page=${page}&api_key_filter=${apiKeyFilter}`);
                currentPage = result.data.pagination.currentPage;
                renderTable(result.data.history);
                renderPagination(result.data.pagination);
            } catch (error) {
                historyTbody.innerHTML = `<tr><td colspan="8" class="text-center text-red-500 p-4">Gagal memuat data.</td></tr>`;
            }
        };

        function renderTable(data) {
            if (!data || data.length === 0) {
                historyTbody.innerHTML = `<tr><td colspan="8" class="text-center p-6 text-slate-500">Tidak ada data riwayat.</td></tr>`; return;
            }
            historyTbody.innerHTML = data.map(row => {
                const statusBadge = row.status === 'Berhasil'
                    ? `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Berhasil</span>`
                    : `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Gagal</span>`;
                const formattedDate = new Date(row.timestamp).toLocaleString('id-ID');
                return `<tr class="hover:bg-slate-50/50" data-id="${row.id}">
                    <td class="p-3 font-mono text-xs">${escapeHTML(row.api_key)}</td><td class="p-3">${escapeHTML(row.bank)}</td>
                    <td class="p-3">${escapeHTML(row.account_number)}</td><td class="p-3">${escapeHTML(row.account_name)}</td>
                    <td class="p-3">${statusBadge}</td><td class="p-3 max-w-xs truncate" title="${escapeHTML(row.message)}">${escapeHTML(row.message)}</td>
                    <td class="p-3">${escapeHTML(formattedDate)}</td><td class="p-3"><button class="text-red-600 hover:underline text-xs delete-item-btn" data-id="${row.id}">Hapus</button></td>
                </tr>`;
            }).join('');
        }

        function renderPagination({ currentPage, totalPages, totalRecords }) {
            if (totalPages <= 1) {
                paginationControls.innerHTML = `<div class="text-sm text-slate-600">Total ${totalRecords} data</div>`; return;
            }
            let paginationHTML = `<div class="text-sm text-slate-600">Total ${totalRecords} data</div><div class="flex items-center gap-2">
                <button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&laquo;</button>`;
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-3 py-1 border border-indigo-500 rounded-md bg-indigo-500 text-white text-sm pagination-btn" data-page="${i}">${i}</button>`;
                } else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    paginationHTML += `<button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${i}">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    paginationHTML += `<span class="px-3 py-1 text-sm">...</span>`;
                }
            }
            paginationHTML += `<button class="px-3 py-1 border border-slate-300 rounded-md bg-white hover:bg-slate-50 text-sm pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>&raquo;</button></div>`;
            paginationControls.innerHTML = paginationHTML;
        }

        document.body.addEventListener('click', async function(e) {
            if (e.target.matches('.delete-item-btn')) {
                if (!confirm('Anda yakin ingin menghapus riwayat ini?')) return;
                try {
                    const result = await apiCall('delete_history', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: e.target.dataset.id, csrf_token: '<?= $csrf_token ?>' }) });
                    window.showNotification(result.message);
                    window.fetchHistory(currentPage);
                } catch (error) {}
            }
            if (e.target.matches('.pagination-btn:not(:disabled)')) {
                window.fetchHistory(parseInt(e.target.dataset.page));
            }
        });

        window.fetchHistory(1);
    }

    if (page === 'notifications') {
        const form = document.getElementById('add-notification-form');
        const listContainer = document.getElementById('notification-list-container');
        const deleteAllBtn = document.getElementById('delete-all-notifications-btn');

        async function fetchNotifications() {
            try {
                const result = await apiCall('get_notifications');
                renderNotificationList(result.data.notifications);
            } catch (error) {
                listContainer.innerHTML = `<p class="text-center p-4 text-red-500">Gagal memuat notifikasi.</p>`;
            }
        }

        function renderNotificationList(data) {
            if (!data || data.length === 0) {
                listContainer.innerHTML = `<p class="text-center p-4 text-slate-500">Belum ada notifikasi.</p>`; return;
            }
            listContainer.innerHTML = data.map(notif => `
                <div class="p-4 border border-slate-200 rounded-xl flex justify-between items-start gap-4 card-hover ${!notif.is_active ? 'bg-slate-50 opacity-60' : 'bg-white'}">
                    <div>
                        <p class="text-slate-800">${escapeHTML(notif.message)}</p>
                        <small class="text-slate-500">${new Date(notif.created_at).toLocaleString('id-ID')} - ${notif.is_active ? 'Aktif' : 'Non-Aktif'}</small>
                    </div>
                    <div class="flex-shrink-0 flex gap-2">
                        <a href="?page=edit_notification&id=${notif.id}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                        <button class="text-red-600 hover:underline text-xs delete-notification-btn" data-id="${notif.id}">Hapus</button>
                    </div>
                </div>
            `).join('');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = form.elements.message.value;
            try {
                const result = await apiCall('add_notification', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message, csrf_token: '<?= $csrf_token ?>' }) });
                window.showNotification(result.message); form.reset(); fetchNotifications();
            } catch (error) {}
        });

        listContainer.addEventListener('click', async (e) => {
            if (e.target.matches('.delete-notification-btn')) {
                if (!confirm('Yakin ingin menghapus notifikasi ini?')) return;
                try {
                    const result = await apiCall('delete_notification', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: e.target.dataset.id, csrf_token: '<?= $csrf_token ?>' }) });
                    window.showNotification(result.message); fetchNotifications();
                } catch (error) {}
            }
        });

        deleteAllBtn.addEventListener('click', async () => {
             if (!confirm('PERHATIAN! Anda yakin ingin menghapus SEMUA notifikasi?')) return;
             try {
                const result = await apiCall('delete_all_notifications', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: '<?= $csrf_token ?>' }) });
                window.showNotification(result.message); fetchNotifications();
            } catch (error) {}
        });

        fetchNotifications();
    }

    // JS untuk modal tambah API Key
    if (page === 'api_keys') {
        const addKeyBtn = document.getElementById('add-key-btn');
        const addKeyModal = document.getElementById('add-key-modal');
        const closeBtn1 = document.getElementById('close-modal-btn');
        const closeBtn2 = document.getElementById('close-modal-btn-2');

        if (addKeyBtn) {
            addKeyBtn.addEventListener('click', () => {
                addKeyModal.classList.remove('hidden');
            });
        }

        const closeModal = () => {
            addKeyModal.classList.add('hidden');
        };

        if(closeBtn1) closeBtn1.addEventListener('click', closeModal);
        if(closeBtn2) closeBtn2.addEventListener('click', closeModal);
    }
});
</script>
</body>
</html>
