<?php
require_once __DIR__ . '/config.php';

class Database {
    private ?PDO $conn = null;

    public function getConnection(): ?PDO {
        if ($this->conn !== null) return $this->conn;
        $host = appConfig('DB_HOST', '127.0.0.1');
        $port = appConfig('DB_PORT', '3306');
        $name = appConfig('DB_NAME', 'kitchenmart_db');
        $user = appConfig('DB_USER', 'root');
        $pass = appConfig('DB_PASS', '');
        try {
            $this->conn = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $this->conn = null;
        }
        return $this->conn;
    }
}
