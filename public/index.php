<?php
// Route static files directly if they exist on disk
if (file_exists(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load core files
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Crypto.php';
require_once dirname(__DIR__) . '/src/LicenseManager.php';
require_once dirname(__DIR__) . '/src/UpdateManager.php';

use BeoutOS\Server\Database;
use BeoutOS\Server\Crypto;
use BeoutOS\Server\LicenseManager;
use BeoutOS\Server\UpdateManager;

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Helper to send JSON responses
function sendJson($data, $statusCode = 200) {
    $code = (int)$statusCode;
    if ($code < 100 || $code > 599) {
        $code = 400; // Default to 400 Bad Request for custom exception codes
    }
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper to get raw JSON post body
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

// Helper to enforce admin session authentication
function enforceAdminAuth() {
    if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
        sendJson(['error' => 'Unauthorized. Session expired or missing.'], 401);
    }
}

// --- Debug log buffer ---
define('DEBUG_LOG_FILE', dirname(__DIR__) . '/debug.log');
define('MAX_DEBUG_LINES', 500);

function debugLog($message, $level = 'INFO') {
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] {$level}: {$message}\n";
    @file_put_contents(DEBUG_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
function getDebugLogLines($lines = 100) {
    if (!file_exists(DEBUG_LOG_FILE)) return [];
    $all = @file(DEBUG_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$all) return [];
    return array_slice($all, -min($lines, count($all)));
}
function clearDebugLog() { @file_put_contents(DEBUG_LOG_FILE, ''); }

// Request logging
$requestStart = microtime(true);
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
debugLog("REQ {$clientIp} {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");

try {
    $licenseMgr = new LicenseManager();
    $updateMgr = new UpdateManager();

    // 1. Health check route
    if ($requestUri === '/api/health') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT value FROM settings WHERE key = 'server_version'");
        $versionRow = $stmt->fetch();
        $version = $versionRow ? $versionRow['value'] : '1.0.0';

        $stmt = $db->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'ACTIVE'");
        $activeRow = $stmt->fetch();
        $activeLicenses = $activeRow ? (int)$activeRow['count'] : 0;

        sendJson([
            'status' => 'ok',
            'app' => 'Beout_OS Main PHP Server',
            'version' => $version,
            'server_time' => date('c'),
            'active_licenses' => $activeLicenses
        ]);
    }

    // 2. GET Latest Update Metadata (for VM clients)
    if ($requestUri === '/api/updates/latest') {
        $result = $updateMgr->getLatestUpdateJSON();
        if (!$result) {
            sendJson(['error' => 'No updates available'], 404);
        }
        sendJson($result);
    }

    // 3. Download release package binary (admin download path)
    if (preg_match('#^/api/updates/download/([^/]+)$#', $requestUri, $matches)) {
        $filename = urldecode($matches[1]);
        // Path traversal protection
        if (strpos($filename, '..') !== false) {
            sendJson(['error' => 'Invalid filename'], 400);
        }
        $filePath = dirname(__DIR__) . '/public/updates/' . $filename;
        if (!file_exists($filePath) || !is_file($filePath)) {
            sendJson(['error' => 'File not found'], 404);
        }
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            sendJson(['error' => 'Cannot determine file size'], 500);
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        if (readfile($filePath) === false) {
            error_log("BeoutOS: Failed to serve file: {$filePath}");
        }
        exit;
    }

    // 4. Activate License Key (from VM clients)
    if ($requestUri === '/api/license/activate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = getJsonInput();
        $machineId = $input['machine_id'] ?? null;
        $licenseKey = $input['license_key'] ?? null;

        if (!$machineId || !$licenseKey) {
            sendJson(['error' => 'Missing machine_id or license_key'], 400);
        }

        try {
            $token = $licenseMgr->activateLicense($licenseKey, $machineId);
            sendJson(['status' => 'success', 'activation_token' => $token]);
        } catch (\Exception $e) {
            sendJson(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // 5. Heartbeat Ping Check-in (from VM clients)
    if ($requestUri === '/api/license/heartbeat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = getJsonInput();
        $machineId = $input['machine_id'] ?? null;
        $licenseKey = $input['license_key'] ?? null;
        $machineIp = $input['machine_ip'] ?? $_SERVER['REMOTE_ADDR'];
        $osVersion = $input['os_version'] ?? '';

        if (!$machineId || !$licenseKey) {
            sendJson(['error' => 'Missing parameters'], 400);
        }

        $res = $licenseMgr->verifyHeartbeat($licenseKey, $machineId, $machineIp, $osVersion);
        sendJson($res);
    }

    // ==========================================
    // ADMIN ROUTES (Session Protected)
    // ==========================================

    // Admin Authentication Login API
    if ($requestUri === '/api/admin/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = getJsonInput();
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        
        // Fetch email
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'admin_email'");
        $stmt->execute();
        $emailRow = $stmt->fetch();
        $adminEmail = $emailRow ? $emailRow['value'] : 'admin@beout.local';
        
        // Fetch password hash
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'admin_password_hash'");
        $stmt->execute();
        $passRow = $stmt->fetch();
        
        if ($email === $adminEmail && $passRow && password_verify($password, $passRow['value'])) {
            $_SESSION['admin_auth'] = true;
            sendJson(['status' => 'success']);
        } else {
            sendJson(['error' => 'Invalid operator email or access key.'], 401);
        }
    }

    // Admin Logout API
    if ($requestUri === '/api/admin/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['admin_auth'] = false;
        session_destroy();
        sendJson(['status' => 'success']);
    }

    // Admin API: Update Settings Password & Profile
    if ($requestUri === '/api/admin/settings/password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $newEmail = $input['new_email'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            sendJson(['error' => 'Invalid email address.'], 400);
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Update Email
        $stmt = $db->prepare("UPDATE settings SET value = ? WHERE key = 'admin_email'");
        $stmt->execute([$newEmail]);
        
        // Update Password if provided
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 4) {
                sendJson(['error' => 'Password must be at least 4 characters.'], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE settings SET value = ? WHERE key = 'admin_password_hash'");
            $stmt->execute([$hash]);
        }
        sendJson(['status' => 'success']);
    }

    // Admin API: Get System settings (Time, Timezone, email)
    if ($requestUri === '/api/admin/settings' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        enforceAdminAuth();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT key, value FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        $response = [
            'admin_email' => $settings['admin_email'] ?? 'admin@beout.local',
            'base_server_url' => $settings['base_server_url'] ?? '',
            'server_timezone' => $settings['server_timezone'] ?? 'UTC',
            'server_time_server' => $settings['server_time_server'] ?? 'pool.ntp.org',
            'client_timezone' => $settings['client_timezone'] ?? 'UTC',
            'client_time_server' => $settings['client_time_server'] ?? 'pool.ntp.org'
        ];
        sendJson($response);
    }

    // Admin API: Save System settings
    if ($requestUri === '/api/admin/settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        
        $db = Database::getInstance()->getConnection();
        
        $allowedKeys = [
            'base_server_url',
            'server_timezone',
            'server_time_server',
            'client_timezone',
            'client_time_server'
        ];
        
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
        
        foreach ($allowedKeys as $key) {
            if (isset($input[$key])) {
                $val = trim($input[$key]);
                $stmt->execute([$key, $val]);
                
                // If server timezone, try to apply to server system
                if ($key === 'server_timezone') {
                    if (file_exists("/usr/share/zoneinfo/" . $val)) {
                        @exec("timedatectl set-timezone " . escapeshellarg($val) . " 2>&1");
                        @exec("ln -sf /usr/share/zoneinfo/" . escapeshellarg($val) . " /etc/localtime 2>&1");
                    }
                }
                
                // If server time server, try to apply to server system timesyncd
                if ($key === 'server_time_server') {
                    if (file_exists("/etc/systemd/timesyncd.conf")) {
                        $content = @file_get_contents("/etc/systemd/timesyncd.conf");
                        if ($content !== false) {
                            $content = preg_replace('/^#?NTP=.*/m', 'NTP=' . $val, $content);
                            @file_put_contents("/etc/systemd/timesyncd.conf", $content);
                            @exec("systemctl restart systemd-timesyncd 2>&1");
                        }
                    }
                }
            }
        }
        sendJson(['status' => 'success']);
    }

    // 6. Admin API: Get Licenses List
    if ($requestUri === '/api/admin/licenses') {
        enforceAdminAuth();
        sendJson($licenseMgr->getLicenses());
    }

    // 7. Admin API: Generate Licenses
    if ($requestUri === '/api/admin/license/generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $count = (int)($input['count'] ?? 1);
        $licenseMgr->generateLicenses($count);
        sendJson(['status' => 'success']);
    }

    // 8. Admin API: Import Licenses
    if ($requestUri === '/api/admin/license/import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $keys = $input['keys'] ?? [];
        $licenseMgr->importLicenses($keys);
        sendJson(['status' => 'success']);
    }

    // 9. Admin API: Revoke License
    if ($requestUri === '/api/admin/license/revoke' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $key = $input['license_key'] ?? null;
        if (!$key) {
            sendJson(['error' => 'Missing license_key'], 400);
        }
        $licenseMgr->revokeLicense($key);
        sendJson(['status' => 'success']);
    }

    // 10. Admin API: Reactivate License
    if ($requestUri === '/api/admin/license/reactivate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $key = $input['license_key'] ?? null;
        if (!$key) {
            sendJson(['error' => 'Missing license_key'], 400);
        }
        $licenseMgr->reactivateLicense($key);
        sendJson(['status' => 'success']);
    }

    // 11. Admin API: Delete License
    if ($requestUri === '/api/admin/license/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $input = getJsonInput();
        $key = $input['license_key'] ?? null;
        if (!$key) {
            sendJson(['error' => 'Missing license_key'], 400);
        }
        $licenseMgr->deleteLicense($key);
        sendJson(['status' => 'success']);
    }

    // 12. Admin API: Get Updates List
    if ($requestUri === '/api/admin/updates') {
        enforceAdminAuth();
        sendJson($updateMgr->getUpdates());
    }

    // 13. Admin API: Publish Update File
    if ($requestUri === '/api/admin/update/publish' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $version = $_POST['version'] ?? null;
        $file = $_FILES['file'] ?? null;
        try {
            $checksum = $updateMgr->publishUpdate($version, $file);
            sendJson(['status' => 'success', 'checksum' => $checksum]);
        } catch (\Exception $e) {
            sendJson(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // 13.1. Admin API: Activate/Rollback Update
    if ($requestUri === '/api/admin/update/activate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $version = $data['version'] ?? null;
        try {
            $updateMgr->activateUpdate($version);
            sendJson(['status' => 'success']);
        } catch (\Exception $e) {
            sendJson(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // 13.2. Admin API: Delete Update
    if ($requestUri === '/api/admin/update/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $version = $data['version'] ?? null;
        try {
            $updateMgr->deleteUpdate($version);
            sendJson(['status' => 'success']);
        } catch (\Exception $e) {
            sendJson(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // 14. Admin API: Debug Logs (view + clear)
    if ($requestUri === '/api/admin/debug/logs') {
        enforceAdminAuth();
        $limit = isset($_GET['lines']) ? min((int)$_GET['lines'], MAX_DEBUG_LINES) : 100;
        sendJson(['logs' => getDebugLogLines($limit), 'file' => DEBUG_LOG_FILE]);
    }
    if ($requestUri === '/api/admin/debug/clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        enforceAdminAuth();
        clearDebugLog();
        debugLog("Debug log cleared by admin");
        sendJson(['status' => 'success']);
    }

    // 15. Render UI
    if ($requestUri === '/admin' || $requestUri === '/') {
        if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
            include dirname(__DIR__) . '/public/login.php';
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'admin_email'");
        $stmt->execute();
        $emailRow = $stmt->fetch();
        $adminEmail = $emailRow ? $emailRow['value'] : 'admin@beout.local';

        $publicKey = '';
        $keysAvailable = false;
        try {
            if (Crypto::isKeyPairAvailable()) {
                $publicKey = Crypto::getPublicKey();
                $keysAvailable = true;
            }
        } catch (\Exception $e) {
            // Keys not available — dashboard will show warning
        }
        include dirname(__DIR__) . '/public/dashboard.php';
        exit;
    }

    // Default 404
    sendJson(['error' => 'Route not found'], 404);

} catch (\Throwable $e) {
    error_log("BeoutOS Server Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    sendJson(['error' => 'Internal server error', 'debug' => $e->getMessage()], 500);
}
