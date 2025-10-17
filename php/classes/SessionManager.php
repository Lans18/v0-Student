<?php
/**
 * Session Manager Class
 * 
 * Handles secure session management including CSRF protection,
 * session validation, and role-based access control.
 */

require_once __DIR__ . '/../config/constants.php';

class SessionManager {
    /**
     * Start a secure session with proper security settings
     * 
     * @return void
     */
    public static function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session parameters
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict' // CSRF protection
            ]);
            
            session_start();
            
            // Initialize session security on first access
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }
    }
    
    /**
     * Validate current session
     * 
     * Checks if session is valid by verifying user ID, IP address, and user agent
     * 
     * @return bool True if session is valid
     */
    public static function validateSession(): bool {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['ip_address']) && 
               $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'] &&
               isset($_SESSION['user_agent']) && 
               $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
    }
    
    /**
     * Destroy current session and clear all session data
     * 
     * @return void
     */
    public static function destroySession(): void {
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Generate CSRF token for form protection
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    public static function validateCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check if current user is an admin
     * 
     * @return bool True if user is admin
     */
    public static function isAdmin(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Check if current user is a teacher
     * 
     * @return bool True if user is teacher
     */
    public static function isTeacher(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
    }
    
    /**
     * Check if current user is a student
     * 
     * @return bool True if user is student
     */
    public static function isStudent(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
    }
}
?>
