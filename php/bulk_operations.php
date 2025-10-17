<?php
/**
 * Bulk Operations API
 */

require_once 'config/Database.php';
require_once 'classes/BulkOperations.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    $bulkOps = new BulkOperations($conn);

    $action = $_POST['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'bulk_mark_attendance':
            $operationId = $bulkOps->createOperation('mark_attendance', $_POST['created_by'] ?? 'admin', 0);
            $attendanceData = json_decode($_POST['attendance_data'] ?? '[]', true);
            
            $result = $bulkOps->bulkMarkAttendance($operationId, $attendanceData);
            $response = ['success' => true, 'operation_id' => $operationId, 'result' => $result];
            break;

        case 'bulk_update_attendance':
            $operationId = $bulkOps->createOperation('update_attendance', $_POST['created_by'] ?? 'admin', 0);
            $updateData = json_decode($_POST['update_data'] ?? '[]', true);
            
            $result = $bulkOps->bulkUpdateAttendance($operationId, $updateData);
            $response = ['success' => true, 'operation_id' => $operationId, 'result' => $result];
            break;

        case 'bulk_send_notifications':
            $operationId = $bulkOps->createOperation('send_notification', $_POST['created_by'] ?? 'admin', 0);
            $notificationData = json_decode($_POST['notification_data'] ?? '[]', true);
            
            $result = $bulkOps->bulkSendNotifications($operationId, $notificationData);
            $response = ['success' => true, 'operation_id' => $operationId, 'result' => $result];
            break;

        case 'get_operation':
            $operationId = $_GET['operation_id'] ?? '';
            $operation = $bulkOps->getOperation($operationId);
            $response = ['success' => true, 'data' => $operation];
            break;

        case 'get_history':
            $limit = $_GET['limit'] ?? 50;
            $history = $bulkOps->getOperationHistory($limit);
            $response = ['success' => true, 'data' => $history];
            break;

        case 'import_csv':
            if (!isset($_FILES['csv_file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['csv_file'];
            $result = $bulkOps->importFromCSV($file['tmp_name'], $_POST['created_by'] ?? 'admin');
            $response = ['success' => true, 'result' => $result];
            break;

        case 'export_csv':
            $studentId = $_GET['student_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $data = $bulkOps->exportToCSV($studentId, $startDate, $endDate);
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=attendance_' . date('Y-m-d') . '.csv');
            
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
            exit;

        default:
            throw new Exception('Invalid action');
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
