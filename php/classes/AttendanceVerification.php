<?php
class AttendanceVerification {
    private $db;
    private $attendance_table = 'attendance';
    private $qr_sessions_table = 'qr_sessions';
    private $max_daily_attendance = 1; // One attendance per day per student
    private $grace_period = 300; // 5 minutes grace period for late arrivals

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Verify attendance eligibility before marking
     * @param string $student_id Student ID
     * @param string $session_id QR Session ID
     * @return array Verification result with details
     */
    public function verifyAttendanceEligibility(string $student_id, string $session_id): array {
        try {
            // Check if student exists
            $student_query = "SELECT id, student_id, first_name, last_name FROM students WHERE student_id = :student_id";
            $student_stmt = $this->db->prepare($student_query);
            $student_stmt->bindValue(':student_id', $student_id);
            $student_stmt->execute();
            $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                return [
                    'success' => false,
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Student not found in the system'
                ];
            }

            // Check if QR session is valid
            $session_query = "SELECT * FROM {$this->qr_sessions_table} WHERE session_id = :session_id AND student_id = :student_id";
            $session_stmt = $this->db->prepare($session_query);
            $session_stmt->bindValue(':session_id', $session_id);
            $session_stmt->bindValue(':student_id', $student_id);
            $session_stmt->execute();
            $session = $session_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                return [
                    'success' => false,
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'QR session not found'
                ];
            }

            // Check if session is expired
            if (strtotime($session['expires_at']) < time()) {
                return [
                    'success' => false,
                    'code' => 'SESSION_EXPIRED',
                    'message' => 'QR code has expired',
                    'expired_at' => $session['expires_at']
                ];
            }

            // Check if session already used
            if ($session['is_used']) {
                return [
                    'success' => false,
                    'code' => 'SESSION_ALREADY_USED',
                    'message' => 'This QR code has already been used',
                    'used_at' => $session['used_at']
                ];
            }

            // Check if student already marked attendance today
            $today = date('Y-m-d');
            $today_attendance_query = "SELECT id, time_in FROM {$this->attendance_table} 
                                       WHERE student_id = :student_id AND DATE(time_in) = :today";
            $today_stmt = $this->db->prepare($today_attendance_query);
            $today_stmt->bindValue(':student_id', $student_id);
            $today_stmt->bindValue(':today', $today);
            $today_stmt->execute();
            $today_attendance = $today_stmt->fetch(PDO::FETCH_ASSOC);

            if ($today_attendance) {
                return [
                    'success' => false,
                    'code' => 'ALREADY_MARKED_TODAY',
                    'message' => 'Attendance already marked for today',
                    'marked_at' => $today_attendance['time_in']
                ];
            }

            // All checks passed
            return [
                'success' => true,
                'code' => 'ELIGIBLE',
                'message' => 'Student is eligible for attendance',
                'student' => $student,
                'session' => $session
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'code' => 'VERIFICATION_ERROR',
                'message' => 'Error during verification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Determine attendance status based on time
     * @param DateTime $time_in Check-in time
     * @return string Status (present, late, etc.)
     */
    public function determineAttendanceStatus($time_in): string {
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        $current_time = $current_hour * 60 + $current_minute;

        // Assuming class starts at 8:00 AM
        $class_start_time = 8 * 60; // 480 minutes
        $check_in_time = (int)date('H', strtotime($time_in)) * 60 + (int)date('i', strtotime($time_in));

        if ($check_in_time <= $class_start_time + $this->grace_period) {
            return 'present';
        } else {
            return 'late';
        }
    }

    /**
     * Get detailed verification report
     * @param string $student_id Student ID
     * @return array Verification report
     */
    public function getVerificationReport(string $student_id): array {
        try {
            $report = [
                'student_id' => $student_id,
                'today_attendance' => null,
                'this_week_attendance' => [],
                'this_month_attendance' => [],
                'statistics' => []
            ];

            // Today's attendance
            $today = date('Y-m-d');
            $today_query = "SELECT * FROM {$this->attendance_table} 
                           WHERE student_id = :student_id AND DATE(time_in) = :today";
            $today_stmt = $this->db->prepare($today_query);
            $today_stmt->bindValue(':student_id', $student_id);
            $today_stmt->bindValue(':today', $today);
            $today_stmt->execute();
            $report['today_attendance'] = $today_stmt->fetch(PDO::FETCH_ASSOC);

            // This week's attendance
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $week_query = "SELECT * FROM {$this->attendance_table} 
                          WHERE student_id = :student_id AND DATE(time_in) >= :week_start
                          ORDER BY time_in DESC";
            $week_stmt = $this->db->prepare($week_query);
            $week_stmt->bindValue(':student_id', $student_id);
            $week_stmt->bindValue(':week_start', $week_start);
            $week_stmt->execute();
            $report['this_week_attendance'] = $week_stmt->fetchAll(PDO::FETCH_ASSOC);

            // This month's attendance
            $month_start = date('Y-m-01');
            $month_query = "SELECT * FROM {$this->attendance_table} 
                           WHERE student_id = :student_id AND DATE(time_in) >= :month_start
                           ORDER BY time_in DESC";
            $month_stmt = $this->db->prepare($month_query);
            $month_stmt->bindValue(':student_id', $student_id);
            $month_stmt->bindValue(':month_start', $month_start);
            $month_stmt->execute();
            $report['this_month_attendance'] = $month_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Statistics
            $stats_query = "SELECT 
                            COUNT(*) as total_attendance,
                            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                            AVG(duration_minutes) as avg_duration
                            FROM {$this->attendance_table}
                            WHERE student_id = :student_id AND DATE(time_in) >= :month_start";
            $stats_stmt = $this->db->prepare($stats_query);
            $stats_stmt->bindValue(':student_id', $student_id);
            $stats_stmt->bindValue(':month_start', $month_start);
            $stats_stmt->execute();
            $report['statistics'] = $stats_stmt->fetch(PDO::FETCH_ASSOC);

            return $report;

        } catch (Exception $e) {
            return ['error' => 'Failed to generate report: ' . $e->getMessage()];
        }
    }

    /**
     * Log verification attempt for security audit
     * @param string $student_id Student ID
     * @param string $session_id Session ID
     * @param string $status Verification status
     * @param string $ip_address IP address
     * @return bool Success status
     */
    public function logVerificationAttempt(string $student_id, string $session_id, string $status, string $ip_address): bool {
        try {
            // Create audit log table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS `verification_audit_log` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `student_id` varchar(50) NOT NULL,
                            `session_id` varchar(100) NOT NULL,
                            `status` varchar(50) NOT NULL,
                            `ip_address` varchar(45) NOT NULL,
                            `user_agent` varchar(255) DEFAULT NULL,
                            `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`),
                            KEY `idx_student_id` (`student_id`),
                            KEY `idx_timestamp` (`timestamp`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->exec($create_table);

            $query = "INSERT INTO verification_audit_log (student_id, session_id, status, ip_address, user_agent) 
                     VALUES (:student_id, :session_id, :status, :ip_address, :user_agent)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':session_id', $session_id);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':ip_address', $ip_address);
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

            return $stmt->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get verification audit log for a student
     * @param string $student_id Student ID
     * @param int $limit Number of records to fetch
     * @return array Audit log records
     */
    public function getAuditLog(string $student_id, int $limit = 50): array {
        try {
            $query = "SELECT * FROM verification_audit_log 
                     WHERE student_id = :student_id 
                     ORDER BY timestamp DESC 
                     LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
