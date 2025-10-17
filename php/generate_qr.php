<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/QRCode.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $qr = new QRCode($db);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $student_id = $data['student_id'] ?? '';
    
    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit;
    }
    
    $result = $qr->generateQRCode($student_id);
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
