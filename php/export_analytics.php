<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/AttendanceAnalytics.php';

try {
    $db = Database::getInstance();
    $analytics = new AttendanceAnalytics($db);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $start_date = $data['start_date'] ?? date('Y-m-01');
    $end_date = $data['end_date'] ?? date('Y-m-d');
    
    $csv = $analytics->exportToCSV($start_date, $end_date);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . $start_date . '_to_' . $end_date . '.csv"');
    echo $csv;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
?>
