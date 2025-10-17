<?php
/**
 * Reminder Service Class
 * Handles scheduling and sending attendance reminders
 */

require_once __DIR__ . '/../classes/EmailService.php';
require_once __DIR__ . '/../classes/SMSService.php';

class ReminderService {
    private $db;
    private $emailService;
    private $smsService;

    public function __construct($db) {
        $this->db = $db;
        $this->emailService = new EmailService($db);
        $this->smsService = new SMSService($db);
    }

    /**
     * Schedule reminders for a specific time
     */
    public function scheduleReminders($reminderTime) {
        try {
            // Get all students with reminders enabled
            $query = "SELECT s.student_id, s.first_name, s.email, s.phone, np.reminder_time
                     FROM students s
                     JOIN notification_preferences np ON s.student_id = np.user_id
                     WHERE np.reminder_enabled = TRUE AND np.user_type = 'student'
                     AND TIME(np.reminder_time) = ?";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $reminderTime);
            $stmt->execute();
            $result = $stmt->get_result();

            $reminderCount = 0;
            while ($student = $result->fetch_assoc()) {
                $this->sendReminder($student);
                $reminderCount++;
            }

            return $reminderCount;
        } catch (Exception $e) {
            error_log("Error scheduling reminders: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Send reminder to a student
     */
    private function sendReminder($student) {
        try {
            $studentId = $student['student_id'];
            $studentName = $student['first_name'];
            $email = $student['email'];
            $phone = $student['phone'];

            // Check if student has already marked attendance today
            $checkQuery = "SELECT id FROM attendance WHERE student_id = ? AND date = CURDATE()";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bind_param("s", $studentId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            // Only send reminder if no attendance marked today
            if ($checkResult->num_rows === 0) {
                // Send email reminder
                if ($email) {
                    $this->sendEmailReminder($email, $studentName);
                }

                // Send SMS reminder
                if ($phone) {
                    $this->sendSMSReminder($phone, $studentName);
                }

                // Log reminder
                $this->logReminder($studentId, 'sent');
            }
        } catch (Exception $e) {
            error_log("Error sending reminder: " . $e->getMessage());
        }
    }

    /**
     * Send email reminder
     */
    private function sendEmailReminder($email, $studentName) {
        $subject = "Attendance Reminder - Mark Your Attendance Today";
        $body = $this->getEmailReminderTemplate($studentName);
        return $this->emailService->sendEmail($email, $subject, $body);
    }

    /**
     * Send SMS reminder
     */
    private function sendSMSReminder($phone, $studentName) {
        $message = "Hi $studentName, don't forget to mark your attendance today! Visit the attendance system now.";
        return $this->smsService->sendSMS($phone, $message);
    }

    /**
     * Get email reminder template
     */
    private function getEmailReminderTemplate($studentName) {
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<h2>Attendance Reminder</h2>';
        $html .= '<p>Dear ' . htmlspecialchars($studentName) . ',</p>';
        $html .= '<p>This is a friendly reminder to mark your attendance for today.</p>';
        $html .= '<p>Please visit the attendance system and scan your QR code or mark your attendance manually.</p>';
        $html .= '<p><strong>Don\'t forget!</strong> Attendance is important for your academic progress.</p>';
        $html .= '<p>Best regards,<br>Attendance System</p>';
        $html .= '</div></body></html>';
        return $html;
    }

    /**
     * Log reminder
     */
    private function logReminder($studentId, $status) {
        try {
            $query = "INSERT INTO reminders (student_id, reminder_date, status) VALUES (?, CURDATE(), ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $studentId, $status);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging reminder: " . $e->getMessage());
        }
    }

    /**
     * Get reminder statistics
     */
    public function getReminderStats($studentId, $days = 7) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_reminders,
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_reminders,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_reminders
                     FROM reminders
                     WHERE student_id = ? AND reminder_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";

            $stmt = $this->db->prepare($query);
            $stmt->bind_param("si", $studentId, $days);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting reminder stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create custom reminder
     */
    public function createCustomReminder($studentId, $reminderTime, $message) {
        try {
            $query = "INSERT INTO custom_reminders (student_id, reminder_time, message, created_at) 
                     VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $studentId, $reminderTime, $message);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating custom reminder: " . $e->getMessage());
            return false;
        }
    }
}

?>
