<?php
/**
 * Student Class
 * 
 * Handles all student-related database operations including
 * authentication, registration, and attendance tracking.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/constants.php';

class Student {
    private $conn;
    private $table_name = "students";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Authenticate student with credentials
     * 
     * @param string $student_id Student ID
     * @param string $password Plain text password
     * @return array|null Student data without password, or null if authentication fails
     */
    public function authenticate(string $student_id, string $password): ?array {
        $student = $this->getByStudentId($student_id);
        
        if (!$student || !password_verify($password, $student['password'])) {
            return null;
        }
        
        unset($student['password']);
        return $student;
    }

    /**
     * Register a new student
     * 
     * @param array $data Student data (student_id, first_name, last_name, email, password, course, year_level)
     * @param string|null $profile_picture Path to profile picture
     * @return bool True on success
     * @throws Exception If validation fails or student already exists
     */
    public function register(array $data, ?string $profile_picture = null): bool {
        // Validate required fields
        $required = ['student_id', 'first_name', 'last_name', 'email', 'password', 'course', 'year_level'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
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

        // Check for existing student ID
        if ($this->getByStudentId($data['student_id'])) {
            throw new Exception(sprintf(ERROR_ID_EXISTS, 'Student'));
        }

        // Check for existing email
        if ($this->getByEmail($data['email'])) {
            throw new Exception(ERROR_EMAIL_EXISTS);
        }

        // Prepare and execute insert query
        $query = "INSERT INTO {$this->table_name} 
                 (student_id, first_name, last_name, email, phone, course, year_level, password, profile_picture) 
                 VALUES (:student_id, :first_name, :last_name, :email, :phone, :course, :year_level, :password, :profile_picture)";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $stmt->bindValue(":student_id", $this->sanitizeInput($data['student_id']));
        $stmt->bindValue(":first_name", $this->sanitizeInput($data['first_name']));
        $stmt->bindValue(":last_name", $this->sanitizeInput($data['last_name']));
        $stmt->bindValue(":email", filter_var($data['email'], FILTER_SANITIZE_EMAIL));
        $stmt->bindValue(":phone", $this->sanitizeInput($data['phone'] ?? ''));
        $stmt->bindValue(":course", $this->sanitizeInput($data['course']));
        $stmt->bindValue(":year_level", $this->sanitizeInput($data['year_level']));
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":profile_picture", $profile_picture);
        
        return $stmt->execute();
    }

    /**
     * Get student by student ID
     * 
     * @param string $student_id Student ID
     * @return array|null Student data or null if not found
     */
    public function getByStudentId(string $student_id): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE student_id = :student_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Get student by email
     * 
     * @param string $email Email address
     * @return array|null Student data or null if not found
     */
    public function getByEmail(string $email): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", filter_var($email, FILTER_SANITIZE_EMAIL));
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Update student profile picture
     * 
     * @param string $student_id Student ID
     * @param string $profile_picture Path to new profile picture
     * @return bool True on success
     */
    public function updateProfilePicture(string $student_id, string $profile_picture): bool {
        $query = "UPDATE {$this->table_name} SET profile_picture = :profile_picture WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":profile_picture", $profile_picture);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        return $stmt->execute();
    }

    /**
     * Update student QR code URL
     * 
     * @param string $student_id Student ID
     * @param string $qr_code_url QR code URL
     * @return bool True on success
     */
    public function updateQRCode(string $student_id, string $qr_code_url): bool {
        $query = "UPDATE {$this->table_name} SET qr_code_url = :qr_code_url WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":qr_code_url", $qr_code_url);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        return $stmt->execute();
    }

    /**
     * Record student time in for attendance
     * 
     * @param string $student_id Student ID
     * @return bool True on success, false if already timed in today
     */
    public function recordTimeIn(string $student_id): bool {
        // Check if already timed in today
        $query = "SELECT id FROM attendance WHERE student_id = :student_id AND DATE(time_in) = CURDATE() AND time_out IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Record time in
        $query = "INSERT INTO attendance (student_id, time_in) VALUES (:student_id, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        return $stmt->execute();
    }

    /**
     * Record student time out for attendance
     * 
     * @param string $student_id Student ID
     * @return bool True on success, false if no active time in record
     */
    public function recordTimeOut(string $student_id): bool {
        // Find active time in record
        $query = "SELECT id FROM attendance WHERE student_id = :student_id AND DATE(time_in) = CURDATE() AND time_out IS NULL ORDER BY time_in DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return false;
        }

        // Update time out
        $record = $stmt->fetch();
        $query = "UPDATE attendance SET time_out = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $record['id']);
        return $stmt->execute();
    }

    /**
     * Get all attendance records for a student
     * 
     * @param string $student_id Student ID
     * @return array Array of attendance records
     */
    public function getAttendanceRecords(string $student_id): array {
        $query = "SELECT * FROM attendance WHERE student_id = :student_id ORDER BY time_in DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update student password
     * 
     * @param string $student_id Student ID
     * @param string $new_password New plain text password
     * @return bool True on success
     * @throws Exception If password is too short
     */
    public function updatePassword(string $student_id, string $new_password): bool {
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            throw new Exception(ERROR_PASSWORD_LENGTH);
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $query = "UPDATE {$this->table_name} SET password = :password WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
        return $stmt->execute();
    }
    
    /**
     * Get all students (without passwords)
     * 
     * @return array Array of student records
     */
    public function getAllStudents(): array {
        $query = "SELECT student_id, first_name, last_name, email, phone, course, year_level, profile_picture, created_at 
                  FROM {$this->table_name} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a student
     * 
     * @param string $student_id Student ID
     * @return bool True on success
     */
    public function deleteStudent(string $student_id): bool {
        $query = "DELETE FROM {$this->table_name} WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":student_id", $this->sanitizeInput($student_id));
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
