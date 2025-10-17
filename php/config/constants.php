<?php
/**
 * Application Constants
 * 
 * This file contains all application-wide constants for configuration.
 * Modify these values to change application behavior.
 */

// File Upload Configuration
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/profile_pictures/');
define('UPLOAD_PATH', 'uploads/profile_pictures/');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_BCRYPT_COST', 12);
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Pagination Configuration
define('RECORDS_PER_PAGE', 20);

// Application Information
define('APP_NAME', 'JAVERIANS');
define('APP_DESCRIPTION', 'Student Management System');

// QR Code Configuration
define('QR_CODE_SIZE', '200x200');
define('QR_CODE_API', 'https://api.qrserver.com/v1/create-qr-code/');

// Validation Messages
define('ERROR_MISSING_FIELD', 'Missing required field: %s');
define('ERROR_INVALID_EMAIL', 'Invalid email format');
define('ERROR_PASSWORD_LENGTH', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
define('ERROR_ID_EXISTS', '%s ID already exists');
define('ERROR_EMAIL_EXISTS', 'Email already registered');
define('ERROR_INVALID_FILE_TYPE', 'Only ' . implode(', ', ALLOWED_IMAGE_EXTENSIONS) . ' files are allowed');
define('ERROR_FILE_TOO_LARGE', 'File size must be less than ' . (MAX_FILE_SIZE / 1048576) . 'MB');
define('ERROR_UPLOAD_FAILED', 'Failed to upload file');
define('ERROR_INVALID_CREDENTIALS', 'Invalid credentials');
define('ERROR_INVALID_CSRF', 'Invalid form submission');

// Success Messages
define('SUCCESS_PROFILE_UPDATED', 'Profile updated successfully!');
define('SUCCESS_PASSWORD_UPDATED', 'Password updated successfully!');
define('SUCCESS_PICTURE_UPDATED', 'Profile picture updated successfully!');
define('SUCCESS_REGISTRATION', 'Registration successful!');

?>
