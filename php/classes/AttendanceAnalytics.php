<?php
class AttendanceAnalytics {
    private $db;
    private $attendance_table = 'attendance';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get overall attendance statistics
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Statistics
     */
    public function getOverallStatistics(string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT student_id) as total_students,
                        COUNT(*) as total_attendance_records,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as total_late,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                        ROUND(AVG(duration_minutes), 2) as avg_duration_minutes,
                        SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) as total_checked_out
                     FROM {$this->attendance_table}
                     WHERE DATE(time_in) BETWEEN :start_date AND :end_date";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate percentages
            if ($stats['total_attendance_records'] > 0) {
                $stats['present_percentage'] = round(($stats['total_present'] / $stats['total_attendance_records']) * 100, 2);
                $stats['late_percentage'] = round(($stats['total_late'] / $stats['total_attendance_records']) * 100, 2);
                $stats['absent_percentage'] = round(($stats['total_absent'] / $stats['total_attendance_records']) * 100, 2);
            }
            
            return $stats ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get daily attendance trend
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Daily data
     */
    public function getDailyTrend(string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        DATE(time_in) as date,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                     FROM {$this->attendance_table}
                     WHERE DATE(time_in) BETWEEN :start_date AND :end_date
                     GROUP BY DATE(time_in)
                     ORDER BY date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get hourly attendance distribution
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Hourly data
     */
    public function getHourlyDistribution(string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        HOUR(time_in) as hour,
                        COUNT(*) as count,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                     FROM {$this->attendance_table}
                     WHERE DATE(time_in) BETWEEN :start_date AND :end_date
                     GROUP BY HOUR(time_in)
                     ORDER BY hour ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get top students by attendance
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param int $limit Number of records
     * @return array Top students
     */
    public function getTopStudents(string $start_date, string $end_date, int $limit = 10): array {
        try {
            $query = "SELECT 
                        a.student_id,
                        s.first_name,
                        s.last_name,
                        COUNT(*) as attendance_count,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                        ROUND(AVG(a.duration_minutes), 2) as avg_duration
                     FROM {$this->attendance_table} a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
                     GROUP BY a.student_id
                     ORDER BY attendance_count DESC
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get students with low attendance
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param float $threshold Attendance percentage threshold
     * @return array Low attendance students
     */
    public function getLowAttendanceStudents(string $start_date, string $end_date, float $threshold = 75): array {
        try {
            $query = "SELECT 
                        a.student_id,
                        s.first_name,
                        s.last_name,
                        s.email,
                        COUNT(*) as attendance_count,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
                     FROM {$this->attendance_table} a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
                     GROUP BY a.student_id
                     HAVING attendance_percentage < :threshold
                     ORDER BY attendance_percentage ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->bindValue(':threshold', $threshold);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get attendance by course
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Course statistics
     */
    public function getAttendanceByCourse(string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        s.course,
                        COUNT(DISTINCT a.student_id) as total_students,
                        COUNT(*) as total_attendance,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as present_percentage
                     FROM {$this->attendance_table} a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
                     GROUP BY s.course
                     ORDER BY present_percentage DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get attendance by year level
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Year level statistics
     */
    public function getAttendanceByYearLevel(string $start_date, string $end_date): array {
        try {
            $query = "SELECT 
                        s.year_level,
                        COUNT(DISTINCT a.student_id) as total_students,
                        COUNT(*) as total_attendance,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as present_percentage
                     FROM {$this->attendance_table} a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
                     GROUP BY s.year_level
                     ORDER BY present_percentage DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Export attendance data to CSV
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return string CSV data
     */
    public function exportToCSV(string $start_date, string $end_date): string {
        try {
            $query = "SELECT 
                        a.id,
                        a.student_id,
                        s.first_name,
                        s.last_name,
                        s.course,
                        s.year_level,
                        a.time_in,
                        a.time_out,
                        a.duration_minutes,
                        a.status
                     FROM {$this->attendance_table} a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
                     ORDER BY a.time_in DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $csv = "ID,Student ID,First Name,Last Name,Course,Year Level,Check-In,Check-Out,Duration (min),Status\n";
            
            foreach ($records as $record) {
                $csv .= implode(',', [
                    $record['id'],
                    $record['student_id'],
                    $record['first_name'],
                    $record['last_name'],
                    $record['course'],
                    $record['year_level'],
                    $record['time_in'],
                    $record['time_out'] ?? 'N/A',
                    $record['duration_minutes'] ?? 'N/A',
                    $record['status']
                ]) . "\n";
            }
            
            return $csv;
        } catch (Exception $e) {
            return '';
        }
    }
}
?>
