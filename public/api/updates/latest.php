<?php
/**
 * Beout_OS Update Metadata Endpoint
 * Serves the latest update metadata in JSON format for client consumption
 * Expected by client's check_updates.sh script
 *
 * Returns:
 * {
 *   "version": "1.2.0",
 *   "url": "https://update.beout.ai/updates/beout_os-core_1.2.0.deb",
 *   "checksum": "sha256:abc123..."
 * }
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set Content-Type header
header('Content-Type: application/json');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Bootstrap the application
$basePath = dirname(__DIR__, 2);

// Include the autoloader
$vendorAutoload = $basePath . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    // Auto-register autoloader fallback if composer install wasn't run
    spl_autoload_register(function ($class) {
        $prefix = 'BeoutOS\Server\\';
        $baseDir = $basePath . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Create UpdateManager instance
$updateManager = new BeoutOS\Server\UpdateManager();

// Get latest update metadata
$latestUpdate = $updateManager->getLatestUpdateJSON();

// Return JSON response
echo json_encode($latestUpdate);

// Log the request if there's an error
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON encoding error: ' . json_last_error_msg());
    echo json_encode(['error' => 'Failed to encode JSON response']);
}
?>