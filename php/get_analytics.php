<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/AttendanceAnalytics.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $analytics = new AttendanceAnalytics($db);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $start_date = $data['start_date'] ?? date('Y-m-01');
    $end_date = $data['end_date'] ?? date('Y-m-d');
    
    $result = [];
    
    switch ($action) {
        case 'overall':
            $result = $analytics->getOverallStatistics($start_date, $end_date);
            break;
        case 'daily_trend':
            $result = $analytics->getDailyTrend($start_date, $end_date);
            break;
        case 'hourly':
            $result = $analytics->getHourlyDistribution($start_date, $end_date);
            break;
        case 'top_students':
            $result = $analytics->getTopStudents($start_date, $end_date, 10);
            break;
        case 'low_attendance':
            $result = $analytics->getLowAttendanceStudents($start_date, $end_date, 75);
            break;
        case 'by_course':
            $result = $analytics->getAttendanceByCourse($start_date, $end_date);
            break;
        case 'by_year':
            $result = $analytics->getAttendanceByYearLevel($start_date, $end_date);
            break;
        default:
            $result = ['error' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
