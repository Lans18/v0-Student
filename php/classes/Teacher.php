<?php
/**
 * Teacher Class
 * 
 * Handles all teacher-related database operations including
 * authentication and registration.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/constants.php';

class Teacher {
    private $conn;
    private $table_name = "teachers";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Authenticate teacher with credentials
     * 
     * @param string $teacher_id Teacher ID
     * @param string $password Plain text password
     * @return array|null Teacher data without password, or null if authentication fails
     */
    public function authenticate(string $teacher_id, string $password): ?array {
        $teacher = $this->getByTeacherId($teacher_id);
        
        if (!$teacher || !password_verify($password, $teacher['password'])) {
            return null;
        }
        
        unset($teacher['password']);
        return $teacher;
    }

    /**
     * Register a new teacher
     * 
     * @param array $data Teacher data (teacher_id, first_name, last_name, email, password, department)
     * @param string|null $profile_picture Path to profile picture
     * @return bool True on success
     * @throws Exception If validation fails or teacher already exists
     */
    public function register(array $data, string $profile_picture = null): bool {
        // Validate required fields
        $required = ['teacher_id', 'first_name', 'last_name', 'email', 'password', 'department'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception(sprintf(ERROR_MISSING_FIELD, $field));
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception(ERROR_INVALID_EMAIL);
        }

        // Validate password length
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            throw new Exception(ERROR_PASSWORD_LENGTH);
        }

        // Check for existing teacher ID
        if ($this->getByTeacherId($data['teacher_id'])) {
            throw new Exception(sprintf(ERROR_ID_EXISTS, 'Teacher'));
        }

        // Check for existing email
        if ($this->getByEmail($data['email'])) {
            throw new Exception(ERROR_EMAIL_EXISTS);
        }

        // Prepare and execute insert query
        $query = "INSERT INTO {$this->table_name} 
                 (teacher_id, first_name, last_name, email, phone, department, password, profile_picture) 
                 VALUES (:teacher_id, :first_name, :last_name, :email, :phone, :department, :password, :profile_picture)";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $stmt->bindValue(":teacher_id", $this->sanitizeInput($data['teacher_id']));
        $stmt->bindValue(":first_name", $this->sanitizeInput($data['first_name']));
        $stmt->bindValue(":last_name", $this->sanitizeInput($data['last_name']));
        $stmt->bindValue(":email", filter_var($data['email'], FILTER_SANITIZE_EMAIL));
        $stmt->bindValue(":phone", $this->sanitizeInput($data['phone'] ?? ''));
        $stmt->bindValue(":department", $this->sanitizeInput($data['department']));
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":profile_picture", $profile_picture);
        
        return $stmt->execute();
    }

    /**
     * Get teacher by teacher ID
     * 
     * @param string $teacher_id Teacher ID
     * @return array|null Teacher data or null if not found
     */
    public function getByTeacherId(string $teacher_id): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE teacher_id = :teacher_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":teacher_id", $this->sanitizeInput($teacher_id));
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Get teacher by email
     * 
     * @param string $email Email address
     * @return array|null Teacher data or null if not found
     */
    public function getByEmail(string $email): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", filter_var($email, FILTER_SANITIZE_EMAIL));
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Update teacher profile picture
     * 
     * @param string $teacher_id Teacher ID
     * @param string $profile_picture Path to new profile picture
     * @return bool True on success
     */
    public function updateProfilePicture(string $teacher_id, string $profile_picture): bool {
        $query = "UPDATE {$this->table_name} SET profile_picture = :profile_picture WHERE teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":profile_picture", $profile_picture);
        $stmt->bindValue(":teacher_id", $this->sanitizeInput($teacher_id));
        return $stmt->execute();
    }

    /**
     * Update teacher password
     * 
     * @param string $teacher_id Teacher ID
     * @param string $new_password New plain text password
     * @return bool True on success
     * @throws Exception If password is too short
     */
    public function updatePassword(string $teacher_id, string $new_password): bool {
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            throw new Exception(ERROR_PASSWORD_LENGTH);
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $query = "UPDATE {$this->table_name} SET password = :password WHERE teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":teacher_id", $this->sanitizeInput($teacher_id));
        return $stmt->execute();
    }
    
    /**
     * Get all teachers (without passwords)
     * 
     * @return array Array of teacher records
     */
    public function getAllTeachers(): array {
        $query = "SELECT teacher_id, first_name, last_name, email, phone, department, profile_picture, created_at 
                  FROM {$this->table_name} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a teacher
     * 
     * @param string $teacher_id Teacher ID
     * @return bool True on success
     */
    public function deleteTeacher(string $teacher_id): bool {
        $query = "DELETE FROM {$this->table_name} WHERE teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":teacher_id", $this->sanitizeInput($teacher_id));
        return $stmt->execute();
    }

    /**
     * Sanitize user input to prevent XSS attacks
     * 
     * @param string $input Raw input string
     * @return string Sanitized string
     */
    private function sanitizeInput(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
?>
