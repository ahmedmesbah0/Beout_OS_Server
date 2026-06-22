<?php
/**
 * Beout OS CLI Update Publisher
 * Usage: php publish_update.php <version> <path_to_deb>
 */

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Auto-register autoloader fallback if composer install wasn't run
spl_autoload_register(function ($class) {
    $prefix = 'BeoutOS\\Server\\';
    $base_dir = dirname(__DIR__) . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use BeoutOS\Server\UpdateManager;

if ($argc < 3) {
    echo "Usage: php " . $argv[0] . " <version> <path_to_deb>\n";
    exit(1);
}

$version = $argv[1];
$debPath = $argv[2];

if (!file_exists($debPath)) {
    echo "Error: Debian package file does not exist at: $debPath\n";
    exit(1);
}

try {
    $updateManager = new UpdateManager();
    
    // Simulate a $_FILES array structure for publishUpdate compatibility
    $mockUploadedFile = [
        'tmp_name' => $debPath,
        'error' => UPLOAD_ERR_OK
    ];

    echo "Publishing version $version...\n";
    
    // Override move_uploaded_file check by copying manually in CLI context
    $filename = "beout_os-core_" . $version . ".deb";
    $updatesDir = dirname(__DIR__) . '/public/updates';
    if (!is_dir($updatesDir)) {
        mkdir($updatesDir, 0755, true);
    }
    $targetPath = $updatesDir . '/' . $filename;
    
    if (!copy($debPath, $targetPath)) {
        throw new Exception("Failed to copy file to updates directory.");
    }
    
    $checksum = hash_file('sha256', $targetPath);
    if (!$checksum) {
        @unlink($targetPath);
        throw new Exception("Failed to compute SHA256 checksum.");
    }

    $db = BeoutOS\Server\Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO updates (version, filename, checksum, published_at) 
        VALUES (:ver, :filename, :checksum, datetime('now'))
    ");
    $stmt->execute([
        ':ver' => $version,
        ':filename' => $filename,
        ':checksum' => $checksum
    ]);

    echo "SUCCESS: Version $version successfully published!\n";
    echo "File: $filename\n";
    echo "Checksum: $checksum\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
