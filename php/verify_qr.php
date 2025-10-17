<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/QRCode.php';
require_once __DIR__ . '/classes/Student.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $qr = new QRCode($db);
    $student = new Student();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $qr_data = $data['qr_data'] ?? '';
    
    if (!$qr_data) {
        echo json_encode(['success' => false, 'message' => 'No QR data received']);
        exit;
    }
    
    $verification = $qr->verifyQRCode($qr_data);
    
    if (!$verification['success']) {
        echo json_encode($verification);
        exit;
    }
    
    // Mark attendance
    $attendance_result = $student->markAttendance($verification['student_id']);
    
    if ($attendance_result) {
        // Mark QR as used
        $qr->markQRAsUsed($verification['session_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'student_id' => $verification['student_id']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
