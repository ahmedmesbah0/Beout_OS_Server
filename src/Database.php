<?php
namespace BeoutOS\Server;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbPath = dirname(__DIR__) . '/server.db';
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeSchema();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
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

        // Create updates table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS updates (
                version TEXT PRIMARY KEY,
                filename TEXT NOT NULL,
                checksum TEXT NOT NULL,
                published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Create settings table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            );
        ");

        // Seed default admin password if not present (default: admin)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM settings WHERE key = 'admin_password_hash'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row['count'] == 0) {
            $defaultHash = password_hash('admin', PASSWORD_BCRYPT);
            $seedStmt = $this->pdo->prepare("INSERT INTO settings (key, value) VALUES ('admin_password_hash', :hash)");
            $seedStmt->execute([':hash' => $defaultHash]);
        }
    }
}
