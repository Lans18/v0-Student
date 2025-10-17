<?php
/**
 * Bulk Operations Class
 * Handles bulk attendance operations and batch processing
 */

class BulkOperations {
    private $db;
    private $table = 'bulk_operations';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create bulk operation
     */
    public function createOperation($operationType, $createdBy, $totalRecords) {
        try {
            $query = "INSERT INTO {$this->table} (operation_type, created_by, total_records, status)
                     VALUES (?, ?, ?, 'pending')";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssi", $operationType, $createdBy, $totalRecords);
            
            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating operation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk mark attendance
     */
    public function bulkMarkAttendance($operationId, $attendanceData) {
        try {
            $this->updateOperationStatus($operationId, 'processing');
            
            $successCount = 0;
            $failureCount = 0;

            foreach ($attendanceData as $record) {
                try {
                    $query = "INSERT INTO attendance (student_id, date, check_in_time, status)
                             VALUES (?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->bind_param("ssss", $record['student_id'], $record['date'], $record['time'], $record['status']);
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (Exception $e) {
                    $failureCount++;
                }
            }

            $this->updateOperationStatus($operationId, 'completed', $successCount, $failureCount);
            return ['success' => $successCount, 'failed' => $failureCount];
        } catch (Exception $e) {
            error_log("Error in bulk mark attendance: " . $e->getMessage());
            $this->updateOperationStatus($operationId, 'failed', 0, count($attendanceData), $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk update attendance
     */
    public function bulkUpdateAttendance($operationId, $updateData) {
        try {
            $this->updateOperationStatus($operationId, 'processing');
            
            $successCount = 0;
            $failureCount = 0;

            foreach ($updateData as $record) {
                try {
                    $query = "UPDATE attendance SET status = ? WHERE id = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->bind_param("si", $record['status'], $record['attendance_id']);
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (Exception $e) {
                    $failureCount++;
                }
            }

            $this->updateOperationStatus($operationId, 'completed', $successCount, $failureCount);
            return ['success' => $successCount, 'failed' => $failureCount];
        } catch (Exception $e) {
            error_log("Error in bulk update attendance: " . $e->getMessage());
            $this->updateOperationStatus($operationId, 'failed', 0, count($updateData), $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk send notifications
     */
    public function bulkSendNotifications($operationId, $notificationData) {
        try {
            $this->updateOperationStatus($operationId, 'processing');
            
            $successCount = 0;
            $failureCount = 0;

            foreach ($notificationData as $notification) {
                try {
                    $query = "INSERT INTO email_notifications (recipient_email, subject, message, notification_type, status)
                             VALUES (?, ?, ?, ?, 'pending')";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->bind_param("ssss", $notification['email'], $notification['subject'], 
                                     $notification['message'], $notification['type']);
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (Exception $e) {
                    $failureCount++;
                }
            }

            $this->updateOperationStatus($operationId, 'completed', $successCount, $failureCount);
            return ['success' => $successCount, 'failed' => $failureCount];
        } catch (Exception $e) {
            error_log("Error in bulk send notifications: " . $e->getMessage());
            $this->updateOperationStatus($operationId, 'failed', 0, count($notificationData), $e->getMessage());
            return false;
        }
    }

    /**
     * Update operation status
     */
    private function updateOperationStatus($operationId, $status, $processedRecords = 0, $failedRecords = 0, $errorMessage = null) {
        try {
            $query = "UPDATE {$this->table} SET status = ?, processed_records = ?, error_message = ?, completed_at = NOW()
                     WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sisi", $status, $processedRecords, $errorMessage, $operationId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating operation status: " . $e->getMessage());
        }
    }

    /**
     * Get operation details
     */
    public function getOperation($operationId) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $operationId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting operation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get operation history
     */
    public function getOperationHistory($limit = 50) {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting operation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Import attendance from CSV
     */
    public function importFromCSV($filePath, $createdBy) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found");
            }

            $operationId = $this->createOperation('mark_attendance', $createdBy, 0);
            $attendanceData = [];
            $rowCount = 0;

            if (($handle = fopen($filePath, "r")) !== false) {
                // Skip header row
                fgetcsv($handle);

                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) >= 4) {
                        $attendanceData[] = [
                            'student_id' => $row[0],
                            'date' => $row[1],
                            'time' => $row[2],
                            'status' => $row[3]
                        ];
                        $rowCount++;
                    }
                }
                fclose($handle);
            }

            // Update total records
            $query = "UPDATE {$this->table} SET total_records = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ii", $rowCount, $operationId);
            $stmt->execute();

            // Process the data
            return $this->bulkMarkAttendance($operationId, $attendanceData);
        } catch (Exception $e) {
            error_log("Error importing CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export attendance to CSV
     */
    public function exportToCSV($studentId = null, $startDate = null, $endDate = null) {
        try {
            $query = "SELECT a.id, a.student_id, s.first_name, s.last_name, a.date, a.check_in_time, a.check_out_time, a.status
                     FROM attendance a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE 1=1";

            if ($studentId) {
                $query .= " AND a.student_id = ?";
            }
            if ($startDate && $endDate) {
                $query .= " AND a.date BETWEEN ? AND ?";
            }

            $query .= " ORDER BY a.date DESC";

            $stmt = $this->db->prepare($query);

            if ($studentId && $startDate && $endDate) {
                $stmt->bind_param("sss", $studentId, $startDate, $endDate);
            } elseif ($studentId) {
                $stmt->bind_param("s", $studentId);
            } elseif ($startDate && $endDate) {
                $stmt->bind_param("ss", $startDate, $endDate);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error exporting to CSV: " . $e->getMessage());
            return [];
        }
    }
}

?>
