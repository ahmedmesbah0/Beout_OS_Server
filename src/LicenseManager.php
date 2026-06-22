<?php
namespace BeoutOS\Server;

use PDO;
use Exception;

class LicenseManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getLicenses() {
        $stmt = $this->db->query("SELECT * FROM licenses ORDER BY last_seen DESC, license_key ASC");
        return $stmt->fetchAll();
    }

    public function generateLicenses($count = 1) {
        $stmt = $this->db->prepare("INSERT INTO licenses (license_key, status) VALUES (:key, 'PENDING')");
        for ($i = 0; $i < $count; $i++) {
            $key = $this->generateRandomKey();
            try {
                $stmt->execute([':key' => $key]);
            } catch (Exception $e) {
                // Ignore duplicates and retry once
                $key = $this->generateRandomKey();
                $stmt->execute([':key' => $key]);
            }
        }
        return true;
    }

    public function importLicenses($keys) {
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO licenses (license_key, status) VALUES (:key, 'PENDING')");
        foreach ($keys as $key) {
            $cleanKey = strtoupper(trim($key));
            if (!empty($cleanKey)) {
                $stmt->execute([':key' => $cleanKey]);
            }
        }
        return true;
    }

    public function activateLicense($licenseKey, $machineId) {
        $licenseKey = strtoupper(trim($licenseKey));
        $stmt = $this->db->prepare("SELECT * FROM licenses WHERE license_key = :key");
        $stmt->execute([':key' => $licenseKey]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("Invalid license key", 403);
        }

        if ($row['status'] === 'REVOKED') {
            throw new Exception("License key has been revoked by administrator", 403);
        }

        if ($row['status'] === 'ACTIVE' && $row['machine_id'] !== $machineId) {
            throw new Exception("License key already active on another machine", 403);
        }

        // Cryptographically sign the machine_id
        $activationToken = Crypto::signPayload($machineId);
        if (!$activationToken) {
            throw new Exception("Cryptographic signing failed", 500);
        }

        // Update database status
        $updateStmt = $this->db->prepare("
            UPDATE licenses 
            SET status = 'ACTIVE', machine_id = :mid, activated_at = datetime('now'), last_seen = datetime('now') 
            WHERE license_key = :key
        ");
        $updateStmt->execute([
            ':mid' => $machineId,
            ':key' => $licenseKey
        ]);

        return $activationToken;
    }

    public function verifyHeartbeat($licenseKey, $machineId, $machineIp, $osVersion) {
        $licenseKey = strtoupper(trim($licenseKey));
        $stmt = $this->db->prepare("SELECT * FROM licenses WHERE license_key = :key");
        $stmt->execute([':key' => $licenseKey]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['status' => 'INACTIVE', 'error' => 'License key not found'];
        }

        if ($row['machine_id'] !== $machineId) {
            return ['status' => 'INACTIVE', 'error' => 'Machine ID mismatch'];
        }

        // Update check-in details
        $updateStmt = $this->db->prepare("
            UPDATE licenses 
            SET machine_ip = :ip, os_version = :ver, last_seen = datetime('now') 
            WHERE license_key = :key
        ");
        $updateStmt->execute([
            ':ip' => $machineIp,
            ':ver' => $osVersion,
            ':key' => $licenseKey
        ]);

        return ['status' => $row['status']];
    }

    public function revokeLicense($licenseKey) {
        $stmt = $this->db->prepare("UPDATE licenses SET status = 'REVOKED' WHERE license_key = :key");
        return $stmt->execute([':key' => $licenseKey]);
    }

    public function reactivateLicense($licenseKey) {
        $stmt = $this->db->prepare("UPDATE licenses SET status = 'ACTIVE' WHERE license_key = :key");
        return $stmt->execute([':key' => $licenseKey]);
    }

    public function deleteLicense($licenseKey) {
        $stmt = $this->db->prepare("DELETE FROM licenses WHERE license_key = :key");
        return $stmt->execute([':key' => $licenseKey]);
    }

    private function generateRandomKey() {
        $bytes = random_bytes(8);
        $hex = strtoupper(bin2hex($bytes));
        return implode('-', str_split($hex, 4));
    }
}
