<?php
require_once __DIR__ . '/Database.php';

class Admin {
    private $conn;
    private $table_name = "admins";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login(string $username, string $password): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":username", $this->sanitizeInput($username));
        $stmt->execute();
        
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password, $admin['password'])) {
            return null;
        }
        
        return $admin;
    }

    public function createAdmin(string $username, string $password, string $email = ''): bool {
        // Check if username already exists
        if ($this->getByUsername($username)) {
            throw new Exception("Username already exists");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $query = "INSERT INTO {$this->table_name} (username, password, email, created_at) 
                  VALUES (:username, :password, :email, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":username", $this->sanitizeInput($username));
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":email", $this->sanitizeInput($email));
        
        return $stmt->execute();
    }

    public function getByUsername(string $username): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":username", $this->sanitizeInput($username));
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(string $username, string $new_password): bool {
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $query = "UPDATE {$this->table_name} SET password = :password WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":username", $this->sanitizeInput($username));
        return $stmt->execute();
    }
    
    public function getDashboardStats(): array {
        $stats = [];
        
        // Get total students
        $query = "SELECT COUNT(*) as total FROM students";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_students'] = $stmt->fetch()['total'];
        
        // Get total teachers
        $query = "SELECT COUNT(*) as total FROM teachers";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_teachers'] = $stmt->fetch()['total'];
        
        // Get today's attendance
        $query = "SELECT COUNT(DISTINCT student_id) as total FROM attendance WHERE DATE(time_in) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['today_attendance'] = $stmt->fetch()['total'];
        
        // Get pending approvals (if any)
        $stats['pending_approvals'] = 0;
        
        return $stats;
    }

    private function sanitizeInput(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
?>
