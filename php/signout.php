<?php
require_once __DIR__ . '/classes/SessionManager.php';

SessionManager::startSecureSession();

// Destroy the session and redirect to login page
SessionManager::destroySession();

header("Location: signin.php");
exit();
?>
