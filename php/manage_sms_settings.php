<?php
/**
 * Manage SMS Settings
 * Configure SMS service and phone numbers
 */

require_once 'config/Database.php';
require_once 'config/sms-config.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();

    $action = $_POST['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'add_phone':
            $userId = $_POST['user_id'] ?? '';
            $userType = $_POST['user_type'] ?? '';
            $phoneNumber = $_POST['phone_number'] ?? '';

            // Validate phone number
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber)) {
                throw new Exception('Invalid phone number format');
            }

            $query = "UPDATE {$userType}s SET phone = ? WHERE " . ($userType === 'student' ? 'student_id' : 'teacher_id') . " = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $phoneNumber, $userId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Phone number updated'];
            } else {
                throw new Exception('Failed to update phone number');
            }
            break;

        case 'verify_phone':
            $userId = $_POST['user_id'] ?? '';
            $userType = $_POST['user_type'] ?? '';
            $verificationCode = $_POST['verification_code'] ?? '';

            // Check verification code
            $query = "SELECT verification_code FROM phone_verifications WHERE user_id = ? AND user_type = ? AND expires_at > NOW()";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $userId, $userType);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result && $result['verification_code'] === $verificationCode) {
                // Mark phone as verified
                $updateQuery = "UPDATE phone_verifications SET verified_at = NOW() WHERE user_id = ? AND user_type = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ss", $userId, $userType);
                $updateStmt->execute();

                $response = ['success' => true, 'message' => 'Phone number verified'];
            } else {
                throw new Exception('Invalid or expired verification code');
            }
            break;

        case 'get_sms_status':
            $userId = $_POST['user_id'] ?? '';
            $userType = $_POST['user_type'] ?? '';

            $query = "SELECT phone FROM {$userType}s WHERE " . ($userType === 'student' ? 'student_id' : 'teacher_id') . " = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            $response = [
                'success' => true,
                'phone' => $result['phone'] ?? null,
                'sms_enabled' => !empty($result['phone'])
            ];
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
