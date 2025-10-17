-- QR Code Sessions Table for tracking active QR codes
CREATE TABLE IF NOT EXISTS `qr_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL UNIQUE,
  `student_id` varchar(50) NOT NULL,
  `qr_data` longtext NOT NULL,
  `qr_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_used` (`is_used`),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Enhanced Table with check-in/check-out
ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `time_out` datetime DEFAULT NULL;
ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `duration_minutes` int(11) DEFAULT NULL;
ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `status` enum('present', 'late', 'absent', 'excused') DEFAULT 'present';
ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `qr_session_id` varchar(100) DEFAULT NULL;
ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL;

-- QR Code History Table for analytics
CREATE TABLE IF NOT EXISTS `qr_code_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `qr_code_data` longtext NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `scans_count` int(11) DEFAULT 0,
  `last_scanned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_generated_at` (`generated_at`),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for better performance
ALTER TABLE `attendance` ADD KEY IF NOT EXISTS `idx_qr_session_id` (`qr_session_id`);
