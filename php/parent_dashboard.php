<?php
/**
 * Parent/Guardian Dashboard
 */

require_once 'config/Database.php';
require_once 'classes/ParentGuardian.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    $parentGuardian = new ParentGuardian($conn);

    $action = $_GET['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'get_students':
            $parentEmail = $_GET['parent_email'] ?? '';
            $students = $parentGuardian->getStudentsByParentEmail($parentEmail);
            $response = ['success' => true, 'data' => $students];
            break;

        case 'get_attendance':
            $studentId = $_GET['student_id'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $attendance = $parentGuardian->getStudentAttendanceForParent($studentId, $startDate, $endDate);
            $response = ['success' => true, 'data' => $attendance];
            break;

        case 'get_summary':
            $studentId = $_GET['student_id'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $summary = $parentGuardian->getAttendanceSummary($studentId, $startDate, $endDate);
            $response = ['success' => true, 'data' => $summary];
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
