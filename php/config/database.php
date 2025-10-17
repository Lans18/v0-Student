<?php
/**
 * Database Configuration
 * 
 * IMPORTANT: In production, use environment variables instead of hardcoded values
 * Example: 'host' => getenv('DB_HOST') ?: 'localhost'
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'student_management',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4'
];
?>
