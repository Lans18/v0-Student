<?php
class TimeBasedAttendance {
    private $db;
    private $attendance_table = 'attendance';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Mark check-in for a student
     * @param string $student_id Student ID
     * @param string $qr_session_id QR Session ID
     * @return array Result with attendance record
     */
    public function checkIn(string $student_id, string $qr_session_id = null): array {
        try {
            $time_in = date('Y-m-d H:i:s');
            $today = date('Y-m-d');

            // Check if already checked in today
            $check_query = "SELECT id, time_in FROM {$this->attendance_table} 
                           WHERE student_id = :student_id AND DATE(time_in) = :today AND time_out IS NULL";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindValue(':student_id', $student_id);
            $check_stmt->bindValue(':today', $today);
            $check_stmt->execute();
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Already checked in today',
                    'check_in_time' => $existing['time_in']
                ];
            }

            // Determine status based on time
            $status = $this->determineStatus($time_in);

            // Insert check-in record
            $insert_query = "INSERT INTO {$this->attendance_table} 
                            (student_id, time_in, status, qr_session_id) 
                            VALUES (:student_id, :time_in, :status, :qr_session_id)";
            $insert_stmt = $this->db->prepare($insert_query);
            $insert_stmt->bindValue(':student_id', $student_id);
            $insert_stmt->bindValue(':time_in', $time_in);
            $insert_stmt->bindValue(':status', $status);
            $insert_stmt->bindValue(':qr_session_id', $qr_session_id);

            if ($insert_stmt->execute()) {
                $attendance_id = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Check-in successful',
                    'attendance_id' => $attendance_id,
                    'student_id' => $student_id,
                    'time_in' => $time_in,
                    'status' => $status
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to record check-in'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error during check-in: ' . $e->getMessage()];
        }
    }

    /**
     * Mark check-out for a student
     * @param string $student_id Student ID
     * @return array Result with updated attendance record
     */
    public function checkOut(string $student_id): array {
        try {
            $time_out = date('Y-m-d H:i:s');
            $today = date('Y-m-d');

            // Find today's check-in record
            $find_query = "SELECT id, time_in FROM {$this->attendance_table} 
                          WHERE student_id = :student_id AND DATE(time_in) = :today AND time_out IS NULL";
            $find_stmt = $this->db->prepare($find_query);
            $find_stmt->bindValue(':student_id', $student_id);
            $find_stmt->bindValue(':today', $today);
            $find_stmt->execute();
            $record = $find_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'No active check-in found for today'
                ];
            }

            // Calculate duration
            $time_in = new DateTime($record['time_in']);
            $time_out_obj = new DateTime($time_out);
            $duration = $time_out_obj->diff($time_in);
            $duration_minutes = ($duration->h * 60) + $duration->i;

            // Update with check-out
            $update_query = "UPDATE {$this->attendance_table} 
                            SET time_out = :time_out, duration_minutes = :duration_minutes 
                            WHERE id = :id";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindValue(':time_out', $time_out);
            $update_stmt->bindValue(':duration_minutes', $duration_minutes);
            $update_stmt->bindValue(':id', $record['id']);

            if ($update_stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Check-out successful',
                    'attendance_id' => $record['id'],
                    'student_id' => $student_id,
                    'time_in' => $record['time_in'],
                    'time_out' => $time_out,
                    'duration_minutes' => $duration_minutes
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to record check-out'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error during check-out: ' . $e->getMessage()];
        }
    }

    /**
     * Get today's attendance status for a student
     * @param string $student_id Student ID
     * @return array Attendance status
     */
    public function getTodayStatus(string $student_id): array {
        try {
            $today = date('Y-m-d');
            $query = "SELECT * FROM {$this->attendance_table} 
                     WHERE student_id = :student_id AND DATE(time_in) = :today";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':today', $today);
            $stmt->execute();
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return [
                    'status' => 'not_checked_in',
                    'message' => 'Not checked in yet'
                ];
            }

            if ($record['time_out']) {
                return [
                    'status' => 'checked_out',
                    'time_in' => $record['time_in'],
                    'time_out' => $record['time_out'],
                    'duration_minutes' => $record['duration_minutes']
                ];
            } else {
                return [
                    'status' => 'checked_in',
                    'time_in' => $record['time_in'],
                    'attendance_status' => $record['status']
                ];
            }

        } catch (Exception $e) {
            return ['error' => 'Failed to get status: ' . $e->getMessage()];
        }
    }

    /**
     * Determine attendance status based on time
     * @param string $time_in Check-in time
     * @return string Status (present, late, etc.)
     */
    private function determineStatus(string $time_in): string {
        $check_in_time = new DateTime($time_in);
        $hour = (int)$check_in_time->format('H');
        $minute = (int)$check_in_time->format('i');

        // Class starts at 8:00 AM
        $class_start = new DateTime($time_in);
        $class_start->setTime(8, 0, 0);

        // Grace period: 15 minutes
        $grace_period = new DateTime($time_in);
        $grace_period->setTime(8, 15, 0);

        if ($check_in_time <= $grace_period) {
            return 'present';
        } else {
            return 'late';
        }
    }

    /**
     * Get attendance summary for a date range
     * @param string $student_id Student ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Attendance records
     */
    public function getAttendanceSummary(string $student_id, string $start_date, string $end_date): array {
        try {
            $query = "SELECT * FROM {$this->attendance_table} 
                     WHERE student_id = :student_id 
                     AND DATE(time_in) BETWEEN :start_date AND :end_date
                     ORDER BY time_in DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get attendance statistics
     * @param string $student_id Student ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Statistics
     */
    public function getStatistics(string $student_id, string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                        SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) as checked_out_days,
                        AVG(duration_minutes) as avg_duration_minutes,
                        MIN(duration_minutes) as min_duration_minutes,
                        MAX(duration_minutes) as max_duration_minutes
                     FROM {$this->attendance_table}
                     WHERE student_id = :student_id 
                     AND DATE(time_in) BETWEEN :start_date AND :end_date";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $student_id);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            return [];
        }
    }
}
?>
