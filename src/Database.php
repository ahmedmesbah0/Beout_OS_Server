<?php
namespace BeoutOS\Server;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;
    private $dbPath;

    private function __construct() {
        $this->dbPath = dirname(__DIR__) . '/server.db';
        // Let PDOException propagate to caller — index.php has a global try/catch
        $this->pdo = new PDO('sqlite:' . $this->dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // WAL mode enables concurrent reads + writes (no SQLITE_BUSY under load)
        $this->pdo->exec("PRAGMA journal_mode=WAL");
        // Wait up to 5 seconds before giving up on a locked database
        $this->pdo->exec("PRAGMA busy_timeout=5000");
        // Enable foreign keys
        $this->pdo->exec("PRAGMA foreign_keys=ON");

        $this->initializeSchema();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initializeSchema() {
        // Create licenses table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                license_key TEXT PRIMARY KEY,
                status TEXT NOT NULL,
                machine_id TEXT,
                machine_ip TEXT,
                os_version TEXT,
                activated_at TIMESTAMP,
                last_seen TIMESTAMP
            );
        ");

        // Indexes for common queries
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_licenses_status ON licenses(status);");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_licenses_last_seen ON licenses(last_seen);");

        // Create updates table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS updates (
                version TEXT PRIMARY KEY,
                filename TEXT NOT NULL,
                checksum TEXT NOT NULL,
                published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_updates_published_at ON updates(published_at);");

        // Create settings table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            );
        ");

        // -- Seed admin password (atomic INSERT OR IGNORE avoids race condition) --
        $initialPassword = substr(bin2hex(random_bytes(8)), 0, 12);
        $defaultHash = password_hash($initialPassword, PASSWORD_BCRYPT);
        $this->pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('admin_password_hash', :hash)")
            ->execute([':hash' => $defaultHash]);

        // Check if we were the ones who actually inserted (first run)
        $stmt = $this->pdo->prepare("SELECT value FROM settings WHERE key = 'admin_password_hash'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && password_verify($initialPassword, $row['value'])) {
            // We just seeded a fresh password — log it once
            $msg = "INITIAL ADMIN PASSWORD: " . $initialPassword;
            error_log("========================================");
            error_log("  " . $msg);
            error_log("  Change this on first login.");
            error_log("========================================");
            // Also write to a file so it's discoverable without grep'ing logs
            @file_put_contents(dirname($this->dbPath) . '/initial_admin_password.txt', $msg . "\n", LOCK_EX);
        }

        // Seed default admin email (atomic, no race)
        $this->pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('admin_email', :email)")
            ->execute([':email' => 'admin@beout.local']);
    }
}
