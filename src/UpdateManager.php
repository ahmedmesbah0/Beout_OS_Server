<?php
namespace BeoutOS\Server;

use PDO;
use Exception;

class UpdateManager {
    private $db;
    private $updatesDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->updatesDir = dirname(__DIR__) . '/updates';
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
        $version = trim($version);
        if (empty($version) || !$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Invalid version or file upload error", 400);
        }

        // Validate version format to prevent path traversal
        if (!preg_match('/^[0-9a-zA-Z.-]+$/', $version)) {
            throw new Exception("Invalid version format", 400);
        }

        $filename = "beout_os-core_" . $version . ".deb";
        $targetPath = $this->updatesDir . '/' . $filename;

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
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO updates (version, filename, checksum, published_at) 
            VALUES (:ver, :filename, :checksum, datetime('now'))
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
        $stmt = $this->db->prepare("
            UPDATE updates 
            SET published_at = datetime('now') 
            WHERE version = :ver
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
}
