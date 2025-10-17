<?php
/**
 * Attendance Reports Class
 * Generates real-time attendance reports with filtering and analytics
 */

class AttendanceReports {
    private $db;
    private $table = 'attendance';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get real-time attendance report for a student
     */
    public function getStudentReport($studentId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        $query = "SELECT 
                    a.id, a.student_id, a.date, a.check_in_time, a.check_out_time,
                    a.status, a.duration_minutes, s.first_name, s.last_name, s.course
                  FROM {$this->table} a
                  JOIN students s ON a.student_id = s.student_id
                  WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
                  ORDER BY a.date DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sss", $studentId, $startDate, $endDate);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get class-wise attendance report
     */
    public function getClassReport($courseId, $yearLevel, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        $query = "SELECT 
                    s.student_id, s.first_name, s.last_name,
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
                    COUNT(a.id) as total_classes,
                    ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(a.id) * 100, 2) as attendance_percentage
                  FROM students s
                  LEFT JOIN {$this->table} a ON s.student_id = a.student_id AND a.date BETWEEN ? AND ?
                  WHERE s.course = ? AND s.year_level = ?
                  GROUP BY s.student_id
                  ORDER BY attendance_percentage DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssss", $startDate, $endDate, $courseId, $yearLevel);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get daily attendance report
     */
    public function getDailyReport($date = null) {
        $date = $date ?? date('Y-m-d');

        $query = "SELECT 
                    s.student_id, s.first_name, s.last_name, s.course, s.year_level,
                    a.status, a.check_in_time, a.check_out_time, a.duration_minutes
                  FROM students s
                  LEFT JOIN {$this->table} a ON s.student_id = a.student_id AND a.date = ?
                  ORDER BY s.student_id";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get attendance statistics
     */
    public function getStatistics($studentId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        $query = "SELECT 
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                    COUNT(id) as total,
                    ROUND(COUNT(CASE WHEN status = 'present' THEN 1 END) / COUNT(id) * 100, 2) as percentage
                  FROM {$this->table}
                  WHERE student_id = ? AND date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sss", $studentId, $startDate, $endDate);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get low attendance students
     */
    public function getLowAttendanceStudents($threshold = 75, $courseId = null, $yearLevel = null) {
        $query = "SELECT 
                    s.student_id, s.first_name, s.last_name, s.email, s.course, s.year_level,
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                    COUNT(a.id) as total_classes,
                    ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(a.id) * 100, 2) as attendance_percentage
                  FROM students s
                  LEFT JOIN {$this->table} a ON s.student_id = a.student_id
                  WHERE a.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        if ($courseId) {
            $query .= " AND s.course = ?";
        }
        if ($yearLevel) {
            $query .= " AND s.year_level = ?";
        }

        $query .= " GROUP BY s.student_id
                   HAVING attendance_percentage < ?
                   ORDER BY attendance_percentage ASC";

        $stmt = $this->db->prepare($query);
        
        if ($courseId && $yearLevel) {
            $stmt->bind_param("ssi", $courseId, $yearLevel, $threshold);
        } elseif ($courseId) {
            $stmt->bind_param("si", $courseId, $threshold);
        } elseif ($yearLevel) {
            $stmt->bind_param("si", $yearLevel, $threshold);
        } else {
            $stmt->bind_param("i", $threshold);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Export report to PDF (requires TCPDF or similar)
     */
    public function exportToPDF($data, $filename) {
        // This would require TCPDF library
        // For now, return JSON for frontend to handle
        return json_encode($data);
    }
}

?>
