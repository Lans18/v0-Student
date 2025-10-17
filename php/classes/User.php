<?php
require_once __DIR__ . '/Database.php';

abstract class User {
    protected $conn;
    protected $table_name;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    abstract public function register(array $data, string $profile_picture = null): bool;
    abstract public function login(string $identifier, string $password): ?array;

    public function getById(int $id): ?array {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }

    public function updateProfilePicture(int $id, string $profile_picture): bool {
        $query = "UPDATE {$this->table_name} SET profile_picture = :profile_picture WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":profile_picture", $profile_picture);
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }

    public function updatePassword(int $id, string $new_password): bool {
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $query = "UPDATE {$this->table_name} SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":id", $id);
        return $stmt->execute();
    }

    protected function sanitizeInput(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    protected function handleFileUpload(string $field_name, string $upload_dir, array $allowed_ext, int $max_size): string {
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Only " . implode(', ', $allowed_ext) . " files are allowed");
        }

        if ($_FILES[$field_name]['size'] > $max_size) {
            throw new Exception("File size must be less than " . ($max_size / 1024 / 1024) . "MB");
        }

        $filename = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $filename;

        if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $destination)) {
            throw new Exception("Failed to upload file");
        }

        return $destination;
    }
}
?>
