<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/TimeBasedAttendance.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $attendance = new TimeBasedAttendance($db);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $student_id = $data['student_id'] ?? '';
    $qr_session_id = $data['qr_session_id'] ?? null;
    
    if (!$action || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    if ($action === 'check_in') {
        $result = $attendance->checkIn($student_id, $qr_session_id);
    } elseif ($action === 'check_out') {
        $result = $attendance->checkOut($student_id);
    } elseif ($action === 'status') {
        $result = $attendance->getTodayStatus($student_id);
    } else {
        $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
