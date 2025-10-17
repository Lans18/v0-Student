<?php
/**
 * Send SMS Notifications API
 * Handles sending SMS notifications via Twilio or similar service
 */

require_once 'config/Database.php';
require_once 'classes/SMSService.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    $smsService = new SMSService($conn);

    $action = $_POST['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'attendance_marked':
            $phoneNumber = $_POST['phone_number'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');

            if ($smsService->sendAttendanceMarkedSMS($phoneNumber, $studentName, $date)) {
                $response = ['success' => true, 'message' => 'SMS sent successfully'];
            } else {
                throw new Exception('Failed to send SMS');
            }
            break;

        case 'late_arrival':
            $phoneNumber = $_POST['phone_number'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $lateMinutes = $_POST['late_minutes'] ?? 0;

            if ($smsService->sendLateArrivalSMS($phoneNumber, $studentName, $lateMinutes)) {
                $response = ['success' => true, 'message' => 'Late arrival SMS sent'];
            } else {
                throw new Exception('Failed to send SMS');
            }
            break;

        case 'absence':
            $phoneNumber = $_POST['phone_number'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');

            if ($smsService->sendAbsenceSMS($phoneNumber, $studentName, $date)) {
                $response = ['success' => true, 'message' => 'Absence SMS sent'];
            } else {
                throw new Exception('Failed to send SMS');
            }
            break;

        case 'reminder':
            $phoneNumber = $_POST['phone_number'] ?? '';
            $studentName = $_POST['student_name'] ?? '';
            $className = $_POST['class_name'] ?? 'your class';

            if ($smsService->sendReminderSMS($phoneNumber, $studentName, $className)) {
                $response = ['success' => true, 'message' => 'Reminder SMS sent'];
            } else {
                throw new Exception('Failed to send SMS');
            }
            break;

        case 'bulk_send':
            $recipients = json_decode($_POST['recipients'] ?? '[]', true);
            $smsType = $_POST['sms_type'] ?? '';
            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    switch ($smsType) {
                        case 'attendance_marked':
                            $smsService->sendAttendanceMarkedSMS(
                                $recipient['phone'],
                                $recipient['name'],
                                $recipient['date'] ?? date('Y-m-d')
                            );
                            break;
                        case 'late_arrival':
                            $smsService->sendLateArrivalSMS(
                                $recipient['phone'],
                                $recipient['name'],
                                $recipient['late_minutes'] ?? 0
                            );
                            break;
                        case 'reminder':
                            $smsService->sendReminderSMS(
                                $recipient['phone'],
                                $recipient['name'],
                                $recipient['class_name'] ?? 'your class'
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
                'message' => "Sent $successCount SMS, $failureCount failed",
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
