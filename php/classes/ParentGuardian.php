<?php
/**
 * Parent/Guardian Class
 * Manages parent and guardian accounts and their access to student data
 */

class ParentGuardian {
    private $db;
    private $table = 'parent_guardians';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Add parent/guardian
     */
    public function addParent($studentId, $parentName, $parentEmail, $parentPhone, $relationship, $isPrimary = false) {
        try {
            $query = "INSERT INTO {$this->table} (student_id, parent_name, parent_email, parent_phone, relationship, is_primary)
                     VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssi", $studentId, $parentName, $parentEmail, $parentPhone, $relationship, $isPrimary);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error adding parent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get parents for a student
     */
    public function getStudentParents($studentId) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE student_id = ? ORDER BY is_primary DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting parents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get student by parent email
     */
    public function getStudentsByParentEmail($parentEmail) {
        try {
            $query = "SELECT DISTINCT s.* FROM students s
                     JOIN {$this->table} pg ON s.student_id = pg.student_id
                     WHERE pg.parent_email = ?";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $parentEmail);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting students: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update parent information
     */
    public function updateParent($parentId, $parentName, $parentEmail, $parentPhone, $relationship) {
        try {
            $query = "UPDATE {$this->table} SET parent_name = ?, parent_email = ?, parent_phone = ?, relationship = ?
                     WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssssi", $parentName, $parentEmail, $parentPhone, $relationship, $parentId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating parent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete parent
     */
    public function deleteParent($parentId) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $parentId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting parent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student attendance for parent view
     */
    public function getStudentAttendanceForParent($studentId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        try {
            $query = "SELECT a.date, a.status, a.check_in_time, a.check_out_time, a.duration_minutes
                     FROM attendance a
                     WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
                     ORDER BY a.date DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $studentId, $startDate, $endDate);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting attendance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attendance summary for parent
     */
    public function getAttendanceSummary($studentId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        try {
            $query = "SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                        COUNT(id) as total,
                        ROUND(COUNT(CASE WHEN status = 'present' THEN 1 END) / COUNT(id) * 100, 2) as percentage
                     FROM attendance
                     WHERE student_id = ? AND date BETWEEN ? AND ?";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $studentId, $startDate, $endDate);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting summary: " . $e->getMessage());
            return null;
        }
    }
}

?>
