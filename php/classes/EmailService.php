<?php
/**
 * Email Service Class
 * Handles all email sending operations
 */

require_once __DIR__ . '/../config/email-config.php';

class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->username = SMTP_USERNAME;
        $this->password = SMTP_PASSWORD;
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName = SMTP_FROM_NAME;
    }

    /**
     * Send email using PHPMailer
     */
    public function sendEmail($to, $subject, $body, $isHtml = true) {
        try {
            // Log email to database
            $this->logEmailNotification($to, $subject, $body, 'pending');

            // For now, use PHP's mail function
            // In production, use PHPMailer or similar
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "Reply-To: {$this->fromEmail}\r\n";

            $result = mail($to, $subject, $body, $headers);

            if ($result) {
                $this->updateEmailStatus($to, 'sent');
                return true;
            } else {
                $this->updateEmailStatus($to, 'failed');
                return false;
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send attendance marked notification
     */
    public function sendAttendanceMarkedEmail($studentEmail, $studentName, $date, $time) {
        $subject = "Attendance Marked - " . date('M d, Y', strtotime($date));
        
        $body = $this->getEmailTemplate('attendance-marked', [
            'student_name' => $studentName,
            'date' => date('F d, Y', strtotime($date)),
            'time' => $time
        ]);

        return $this->sendEmail($studentEmail, $subject, $body);
    }

    /**
     * Send late arrival notification
     */
    public function sendLateArrivalEmail($studentEmail, $studentName, $date, $time, $lateMinutes) {
        $subject = "Late Arrival Alert - " . date('M d, Y', strtotime($date));
        
        $body = $this->getEmailTemplate('late-arrival', [
            'student_name' => $studentName,
            'date' => date('F d, Y', strtotime($date)),
            'time' => $time,
            'late_minutes' => $lateMinutes
        ]);

        return $this->sendEmail($studentEmail, $subject, $body);
    }

    /**
     * Send absence notification
     */
    public function sendAbsenceEmail($studentEmail, $studentName, $date) {
        $subject = "Absence Recorded - " . date('M d, Y', strtotime($date));
        
        $body = $this->getEmailTemplate('absence', [
            'student_name' => $studentName,
            'date' => date('F d, Y', strtotime($date))
        ]);

        return $this->sendEmail($studentEmail, $subject, $body);
    }

    /**
     * Send daily attendance summary
     */
    public function sendDailySummaryEmail($studentEmail, $studentName, $presentCount, $absentCount, $lateCount) {
        $subject = "Daily Attendance Summary - " . date('M d, Y');
        
        $body = $this->getEmailTemplate('daily-summary', [
            'student_name' => $studentName,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'date' => date('F d, Y')
        ]);

        return $this->sendEmail($studentEmail, $subject, $body);
    }

    /**
     * Send weekly attendance summary
     */
    public function sendWeeklySummaryEmail($studentEmail, $studentName, $weekData) {
        $subject = "Weekly Attendance Summary - Week of " . date('M d, Y');
        
        $body = $this->getEmailTemplate('weekly-summary', [
            'student_name' => $studentName,
            'week_data' => $weekData,
            'date' => date('F d, Y')
        ]);

        return $this->sendEmail($studentEmail, $subject, $body);
    }

    /**
     * Send admin alert for low attendance
     */
    public function sendAdminLowAttendanceAlert($adminEmail, $studentName, $studentId, $attendancePercentage) {
        $subject = "Low Attendance Alert - $studentName ($studentId)";
        
        $body = $this->getEmailTemplate('admin-alert', [
            'student_name' => $studentName,
            'student_id' => $studentId,
            'attendance_percentage' => $attendancePercentage
        ]);

        return $this->sendEmail($adminEmail, $subject, $body);
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($templateName, $data = []) {
        $templatePath = EMAIL_TEMPLATES_DIR . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            return $this->getDefaultTemplate($templateName, $data);
        }

        $template = file_get_contents($templatePath);
        
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * Get default email template
     */
    private function getDefaultTemplate($templateName, $data) {
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<h2>Attendance System Notification</h2>';

        switch ($templateName) {
            case 'attendance-marked':
                $html .= '<p>Dear ' . $data['student_name'] . ',</p>';
                $html .= '<p>Your attendance has been marked for <strong>' . $data['date'] . '</strong> at ' . $data['time'] . '.</p>';
                break;
            case 'late-arrival':
                $html .= '<p>Dear ' . $data['student_name'] . ',</p>';
                $html .= '<p>You were marked late by ' . $data['late_minutes'] . ' minutes on ' . $data['date'] . '.</p>';
                break;
            case 'absence':
                $html .= '<p>Dear ' . $data['student_name'] . ',</p>';
                $html .= '<p>You were marked absent on ' . $data['date'] . '.</p>';
                break;
        }

        $html .= '<p>Best regards,<br>Attendance System</p>';
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Log email notification to database
     */
    private function logEmailNotification($email, $subject, $message, $status) {
        try {
            $query = "INSERT INTO email_notifications (recipient_email, subject, message, status, notification_type) 
                     VALUES (?, ?, ?, ?, 'attendance_marked')";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssss", $email, $subject, $message, $status);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }

    /**
     * Update email status
     */
    private function updateEmailStatus($email, $status) {
        try {
            $query = "UPDATE email_notifications SET status = ?, sent_at = NOW() 
                     WHERE recipient_email = ? AND status = 'pending' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $status, $email);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update email status: " . $e->getMessage());
        }
    }
}

?>
