<?php
/**
 * Send Reminders Cron Job
 * This script should be run periodically (e.g., every hour) via cron job
 * 0 * * * * php /path/to/send_reminders.php
 */

require_once 'config/Database.php';
require_once 'classes/ReminderService.php';

try {
    $db = new Database();
    $conn = $db->connect();
    $reminderService = new ReminderService($conn);

    // Get current time
    $currentTime = date('H:i:00');

    // Send reminders for the current time
    $reminderCount = $reminderService->scheduleReminders($currentTime);

    // Log the execution
    $logMessage = "Reminders sent at " . date('Y-m-d H:i:s') . ": $reminderCount reminders sent";
    error_log($logMessage);

    echo $logMessage;
} catch (Exception $e) {
    error_log("Error in send_reminders.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}

?>
