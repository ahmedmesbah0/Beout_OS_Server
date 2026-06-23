<?php
namespace BeoutOS\Server;

use PDO;
use Exception;

class UpdateManager {
    private $db;
    private $updatesDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->updatesDir = dirname(__DIR__) . '/public/updates';
        if (!is_dir($this->updatesDir)) {
            mkdir($this->updatesDir, 0755, true);
        }
    }

    public function getUpdates() {
        $stmt = $this->db->query("SELECT * FROM updates ORDER BY published_at DESC");
        return $stmt->fetchAll();
    }

    public function getLatestUpdate() {
        $stmt = $this->db->query("SELECT * FROM updates ORDER BY published_at DESC LIMIT 1");
        return $stmt->fetch();
    }

    public function publishUpdate($version, $uploadedFile) {
        if (empty($version)) {
            throw new Exception("Version string is empty", 400);
        }
        if (!$uploadedFile) {
            throw new Exception("No file uploaded", 400);
        }
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            switch ($uploadedFile['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $maxSize = ini_get('upload_max_filesize');
                    $postSize = ini_get('post_max_size');
                    throw new Exception("The file exceeds the PHP upload limit. Current limits: upload_max_filesize=$maxSize, post_max_size=$postSize.", 400);
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("The file exceeds the MAX_FILE_SIZE limit.", 400);
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("The file was only partially uploaded.", 400);
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("No file was uploaded.", 400);
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("PHP is missing its temporary folder.", 500);
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Failed to write file to disk. Check disk space and server permissions.", 500);
                default:
                    throw new Exception("PHP file upload failed with error code: " . $uploadedFile['error'], 500);
            }
        }

        // Validate version format to prevent path traversal
        if (!preg_match('/^[0-9a-zA-Z.-]+$/', $version)) {
            throw new Exception("Invalid version format", 400);
        }

        $filename = "beout_os-core_" . $version . ".deb";
        $targetPath = $this->updatesDir . '/' . $filename;

        if (!is_writable($this->updatesDir)) {
            throw new Exception("The updates storage directory is not writable: " . $this->updatesDir . ". Please check ownership and folder permissions.", 500);
        }

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            throw new Exception("Failed to save uploaded file", 500);
        }

        // Calculate SHA256 checksum
        $checksum = hash_file('sha256', $targetPath);
        if (!$checksum) {
            @unlink($targetPath);
            throw new Exception("Failed to compute file checksum", 500);
        }

        // Save update metadata in SQLite
        $stmt = $this->db->prepare("\
        INSERT OR REPLACE INTO updates (version, filename, checksum, published_at) \
        VALUES (:ver, :filename, :checksum, datetime('now')) \
        ");
        $stmt->execute([
            ':ver' => $version,
            ':filename' => $filename,
            ':checksum' => $checksum
        ]);

        return $checksum;
    }

    public function activateUpdate($version) {
        $version = trim($version);
        if (empty($version)) {
            throw new Exception("Invalid version", 400);
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM updates WHERE version = :ver");
        $stmt->execute([':ver' => $version]);
        $row = $stmt->fetch();
        if ($row['count'] == 0) {
            throw new Exception("Update version not found", 404);
        }

        // Set published_at to current time to make it the latest update
        $stmt = $this->db->prepare("\
        UPDATE updates \
        SET published_at = datetime('now') \
        WHERE version = :ver \
        ");
        $stmt->execute([':ver' => $version]);
        return true;
    }

    public function deleteUpdate($version) {
        $version = trim($version);
        if (empty($version)) {
            throw new Exception("Invalid version", 400);
        }

        $stmt = $this->db->prepare("SELECT filename FROM updates WHERE version = :ver");
        $stmt->execute([':ver' => $version]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new Exception("Update version not found", 404);
        }

        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM updates WHERE version = :ver");
        $stmt->execute([':ver' => $version]);

        // Delete physical file
        $filepath = $this->updatesDir . '/' . $row['filename'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        return true;
    }

    /**
     * Get the latest update metadata in JSON format for client consumption
     * This endpoint is called by client's check_updates.sh script
     * Expected response:
     * {
     *   "version": "1.2.0",
     *   "url": "https://update.beout.ai/updates/beout_os-core_1.2.0.deb",
     *   "checksum": "sha256:abc123..."
     * }
     */
    public function getLatestUpdateJSON() {
        $latest = $this->getLatestUpdate();

        if (!$latest) {
            return ['version' => '', 'url' => '', 'checksum' => ''];
        }

        // Get the base URL for the server
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'update.beout.ai';

        // Construct the update URL
        $updateUrl = "{$scheme}://{$host}/updates/{$latest['filename']}";

        return [
            'version' => $latest['version'],
            'url' => $updateUrl,
            'checksum' => $latest['checksum']
        ];
    }
} ?>