<?php
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
    http_response_code($statusCode);
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

try {
    $licenseMgr = new LicenseManager();
    $updateMgr = new UpdateManager();

    // 1. Health check route
    if ($requestUri === '/api/health') {
        sendJson(['status' => 'ok', 'app' => 'Beout_OS Main PHP Server']);
    }

    // 2. GET Latest Update Metadata (for VM clients)
    if ($requestUri === '/api/updates/latest') {
        $latest = $updateMgr->getLatestUpdate();
        if (!$latest) {
            sendJson(['error' => 'No updates available'], 404);
        }
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $downloadUrl = "{$protocol}://{$host}/updates/" . urlencode($latest['filename']);
        sendJson([
            'version' => $latest['version'],
            'url' => $downloadUrl,
            'checksum' => $latest['checksum']
        ]);
    }

    // 3. Download release package binary
    if (preg_match('#^/api/updates/download/([^/]+)$#', $requestUri, $matches)) {
        $filename = urldecode($matches[1]);
        $filePath = dirname(__DIR__) . '/public/updates/' . $filename;
        if (!file_exists($filePath) || strpos($filename, '..') !== false) {
            sendJson(['error' => 'File not found'], 404);
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
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
        $password = $input['password'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'admin_password_hash'");
        $stmt->execute();
        $row = $stmt->fetch();
        
        if ($row && password_verify($password, $row['value'])) {
            $_SESSION['admin_auth'] = true;
            sendJson(['status' => 'success']);
        } else {
            sendJson(['error' => 'Invalid administrator password.'], 401);
        }
    }

    // Admin Logout API
    if ($requestUri === '/api/admin/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['admin_auth'] = false;
        session_destroy();
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

    // 14. Render UI
    if ($requestUri === '/admin' || $requestUri === '/') {
        if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
            include dirname(__DIR__) . '/public/login.php';
            exit;
        }
        $publicKey = Crypto::getPublicKey();
        include dirname(__DIR__) . '/public/dashboard.php';
        exit;
    }

    // Default 404
    sendJson(['error' => 'Route not found'], 404);

} catch (\Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
