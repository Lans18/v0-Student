<?php
/**
 * Send Email Notifications API
 * Handles sending various email notifications
 */

require_once 'config/Database.php';
require_once 'classes/EmailService.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    $emailService = new EmailService($conn);

    $action = $_POST['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'attendance_marked':
            $studentEmail = $_POST['student_email'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');
            $time = $_POST['time'] ?? date('H:i:s');

            if ($emailService->sendAttendanceMarkedEmail($studentEmail, $studentName, $date, $time)) {
                $response = ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                throw new Exception('Failed to send email');
            }
            break;

        case 'late_arrival':
            $studentEmail = $_POST['student_email'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');
            $time = $_POST['time'] ?? date('H:i:s');
            $lateMinutes = $_POST['late_minutes'] ?? 0;

            if ($emailService->sendLateArrivalEmail($studentEmail, $studentName, $date, $time, $lateMinutes)) {
                $response = ['success' => true, 'message' => 'Late arrival email sent'];
            } else {
                throw new Exception('Failed to send email');
            }
            break;

        case 'absence':
            $studentEmail = $_POST['student_email'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');

            if ($emailService->sendAbsenceEmail($studentEmail, $studentName, $date)) {
                $response = ['success' => true, 'message' => 'Absence email sent'];
            } else {
                throw new Exception('Failed to send email');
            }
            break;

        case 'daily_summary':
            $studentEmail = $_POST['student_email'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $presentCount = $_POST['present_count'] ?? 0;
            $absentCount = $_POST['absent_count'] ?? 0;
            $lateCount = $_POST['late_count'] ?? 0;

            if ($emailService->sendDailySummaryEmail($studentEmail, $studentName, $presentCount, $absentCount, $lateCount)) {
                $response = ['success' => true, 'message' => 'Daily summary email sent'];
            } else {
                throw new Exception('Failed to send email');
            }
            break;

        case 'admin_alert':
            $adminEmail = $_POST['admin_email'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $studentId = $_POST['student_id'] ?? '';
            $attendancePercentage = $_POST['attendance_percentage'] ?? 0;

            if ($emailService->sendAdminLowAttendanceAlert($adminEmail, $studentName, $studentId, $attendancePercentage)) {
                $response = ['success' => true, 'message' => 'Admin alert sent'];
            } else {
                throw new Exception('Failed to send email');
            }
            break;

        case 'bulk_send':
            $recipients = json_decode($_POST['recipients'] ?? '[]', true);
            $emailType = $_POST['email_type'] ?? '';
            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    switch ($emailType) {
                        case 'attendance_marked':
                            $emailService->sendAttendanceMarkedEmail(
                                $recipient['email'],
                                $recipient['name'],
                                $recipient['date'] ?? date('Y-m-d'),
                                $recipient['time'] ?? date('H:i:s')
                            );
                            break;
                        case 'daily_summary':
                            $emailService->sendDailySummaryEmail(
                                $recipient['email'],
                                $recipient['name'],
                                $recipient['present_count'] ?? 0,
                                $recipient['absent_count'] ?? 0,
                                $recipient['late_count'] ?? 0
                            );
                            break;
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $failureCount++;
                }
            }

            $response = [
                'success' => true,
                'message' => "Sent $successCount emails, $failureCount failed",
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
