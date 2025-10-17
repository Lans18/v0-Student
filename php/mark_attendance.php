<?php
require_once __DIR__ . '/classes/Student.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$qr_data = $data['qr_data'] ?? '';

if (!$qr_data) {
    echo json_encode(['success' => false, 'message' => 'No QR data received']);
    exit;
}

// If QR is JSON, decode and verify hash
if ($qr_data && ($qr_data[0] === '{' || $qr_data[0] === '[')) {
    $decoded = json_decode($qr_data, true);
    if (!is_array($decoded) || !isset($decoded['student_id'], $decoded['timestamp'], $decoded['verify'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid QR format']);
        exit;
    }
    // Verify hash
    $expected_hash = md5($decoded['student_id'] . $decoded['timestamp'] . 'JAVERIANS_QR_SECRET');
    if ($decoded['verify'] !== $expected_hash) {
        echo json_encode(['success' => false, 'message' => 'QR verification failed']);
        exit;
    }
    $student_id = $decoded['student_id'];
} else {
    // Simple format: #student_id
    $student_id = ltrim($qr_data, '#');
    if (empty($student_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
}

// Mark attendance
$student = new Student();
$result = $student->markAttendance($student_id);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Attendance marked!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
}
?>
