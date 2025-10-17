<?php
/**
 * Get Attendance Reports API
 */

require_once 'config/Database.php';
require_once 'classes/AttendanceReports.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    $reports = new AttendanceReports($conn);

    $action = $_GET['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'student_report':
            $studentId = $_GET['student_id'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $response = $reports->getStudentReport($studentId, $startDate, $endDate);
            break;

        case 'class_report':
            $courseId = $_GET['course_id'] ?? '';
            $yearLevel = $_GET['year_level'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $response = $reports->getClassReport($courseId, $yearLevel, $startDate, $endDate);
            break;

        case 'daily_report':
            $date = $_GET['date'] ?? date('Y-m-d');
            $response = $reports->getDailyReport($date);
            break;

        case 'statistics':
            $studentId = $_GET['student_id'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $response = $reports->getStatistics($studentId, $startDate, $endDate);
            break;

        case 'low_attendance':
            $threshold = $_GET['threshold'] ?? 75;
            $courseId = $_GET['course_id'] ?? null;
            $yearLevel = $_GET['year_level'] ?? null;
            $response = $reports->getLowAttendanceStudents($threshold, $courseId, $yearLevel);
            break;

        default:
            throw new Exception('Invalid action');
    }

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
