-- Create notifications tables for email and SMS tracking
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('student', 'teacher', 'admin', 'parent') NOT NULL,
    recipient_id VARCHAR(50),
    subject VARCHAR(255) NOT NULL,
    message LONGTEXT NOT NULL,
    notification_type ENUM('attendance_marked', 'late_arrival', 'absence', 'summary', 'reminder', 'appeal', 'admin_alert') NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_email, status),
    INDEX idx_type (notification_type),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS sms_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_phone VARCHAR(20) NOT NULL,
    recipient_type ENUM('student', 'teacher', 'parent') NOT NULL,
    recipient_id VARCHAR(50),
    message VARCHAR(160) NOT NULL,
    notification_type ENUM('attendance_marked', 'late_arrival', 'absence', 'reminder') NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_phone, status),
    INDEX idx_type (notification_type)
);

CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'teacher', 'parent') NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    attendance_alerts BOOLEAN DEFAULT TRUE,
    daily_summary BOOLEAN DEFAULT TRUE,
    weekly_summary BOOLEAN DEFAULT FALSE,
    reminder_enabled BOOLEAN DEFAULT TRUE,
    reminder_time TIME DEFAULT '08:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id, user_type),
    INDEX idx_user (user_id)
);

CREATE TABLE IF NOT EXISTS parent_guardians (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL,
    parent_name VARCHAR(100) NOT NULL,
    parent_email VARCHAR(255),
    parent_phone VARCHAR(20),
    relationship VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_student (student_id)
);

CREATE TABLE IF NOT EXISTS attendance_appeals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL,
    attendance_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    description LONGTEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by VARCHAR(50),
    reviewed_at TIMESTAMP NULL,
    admin_notes LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS bulk_operations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    operation_type ENUM('mark_attendance', 'update_attendance', 'send_notification') NOT NULL,
    created_by VARCHAR(50) NOT NULL,
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
