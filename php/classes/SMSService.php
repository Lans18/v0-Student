<?php
/**
 * SMS Service Class
 * Handles all SMS sending operations
 */

require_once __DIR__ . '/../config/sms-config.php';

class SMSService {
    private $db;
    private $service;

    public function __construct($db) {
        $this->db = $db;
        $this->service = SMS_SERVICE;
    }

    /**
     * Send SMS message
     */
    public function sendSMS($phoneNumber, $message) {
        try {
            // Log SMS to database
            $this->logSMSNotification($phoneNumber, $message, 'pending');

            // For now, return true (implement actual SMS service integration)
            // In production, integrate with Twilio, Nexmo, or AWS SNS
            $this->updateSMSStatus($phoneNumber, 'sent');
            return true;
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            $this->updateSMSStatus($phoneNumber, 'failed');
            return false;
        }
    }

    /**
     * Send attendance marked SMS
     */
    public function sendAttendanceMarkedSMS($phoneNumber, $studentName, $date) {
        $message = "Hi $studentName, your attendance has been marked for " . date('M d, Y', strtotime($date)) . ".";
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send late arrival SMS
     */
    public function sendLateArrivalSMS($phoneNumber, $studentName, $lateMinutes) {
        $message = "Alert: $studentName, you were marked late by $lateMinutes minutes today.";
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send absence SMS
     */
    public function sendAbsenceSMS($phoneNumber, $studentName, $date) {
        $message = "Alert: $studentName, you were marked absent on " . date('M d, Y', strtotime($date)) . ".";
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send reminder SMS
     */
    public function sendReminderSMS($phoneNumber, $studentName, $className) {
        $message = "Reminder: $studentName, don't forget to mark your attendance for $className today!";
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Log SMS notification to database
     */
    private function logSMSNotification($phoneNumber, $message, $status) {
        try {
            $query = "INSERT INTO sms_notifications (recipient_phone, message, status, notification_type) 
                     VALUES (?, ?, ?, 'attendance_marked')";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $phoneNumber, $message, $status);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log SMS: " . $e->getMessage());
        }
    }

    /**
     * Update SMS status
     */
    private function updateSMSStatus($phoneNumber, $status) {
        try {
            $query = "UPDATE sms_notifications SET status = ?, sent_at = NOW() 
                     WHERE recipient_phone = ? AND status = 'pending' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $status, $phoneNumber);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update SMS status: " . $e->getMessage());
        }
    }
}

?>
