<?php
/**
 * Database Connection Class
 * 
 * Handles database connections using PDO with proper error handling
 * and security configurations.
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private $conn;
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
    }

    /**
     * Get database connection
     * 
     * @return PDO Database connection object
     * @throws Exception If connection fails
     */
    public function getConnection(): PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['dbname'],
                $this->config['charset']
            );
            
            $this->conn = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );
            
            return $this->conn;
        } catch (PDOException $exception) {
            // Log error for debugging (in production, use proper logging)
            error_log("Database connection error: " . $exception->getMessage());
            
            // Throw generic exception to avoid exposing sensitive information
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
}
?>
