<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/QRCode.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/AttendanceVerification.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $qr = new QRCode($db);
    $student = new Student();
    $verification = new AttendanceVerification($db);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $qr_data = $data['qr_data'] ?? '';
    
    if (!$qr_data) {
        echo json_encode(['success' => false, 'message' => 'No QR data received']);
        exit;
    }
    
    $qr_verification = $qr->verifyQRCode($qr_data);
    
    if (!$qr_verification['success']) {
        echo json_encode($qr_verification);
        exit;
    }
    
    $student_id = $qr_verification['student_id'];
    $session_id = $qr_verification['session_id'];
    
    $eligibility = $verification->verifyAttendanceEligibility($student_id, $session_id);
    
    if (!$eligibility['success']) {
        // Log failed attempt
        $verification->logVerificationAttempt($student_id, $session_id, 'FAILED_' . $eligibility['code'], $_SERVER['REMOTE_ADDR']);
        echo json_encode($eligibility);
        exit;
    }
    
    $attendance_result = $student->markAttendance($student_id);
    
    if ($attendance_result) {
        // Mark QR as used
        $qr->markQRAsUsed($session_id);
        
        // Log successful verification
        $verification->logVerificationAttempt($student_id, $session_id, 'SUCCESS', $_SERVER['REMOTE_ADDR']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'student_id' => $student_id,
            'student_name' => $eligibility['student']['first_name'] . ' ' . $eligibility['student']['last_name'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        $verification->logVerificationAttempt($student_id, $session_id, 'FAILED_ATTENDANCE_MARK', $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
