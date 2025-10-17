<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Admin.php';

SessionManager::startSecureSession();

// Only allow access if already an admin OR if no admins exist in the system
$admin = new Admin();
$isFirstAdmin = false;

try {
    $checkQuery = "SELECT COUNT(*) as count FROM admins";
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute();
    $result = $stmt->fetch();
    $isFirstAdmin = ($result['count'] == 0);
} catch (Exception $e) {
    $isFirstAdmin = true; // Assume first admin if table doesn't exist
}

// Redirect if not admin and not first admin setup
if (!$isFirstAdmin && (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin())) {
    header("Location: admin_login.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (empty($username) || empty($password) || empty($confirm_password)) {
            throw new Exception('Please fill in all required fields');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        if ($admin->createAdmin($username, $password, $email)) {
            $message = 'Admin account created successfully';
            $messageType = 'success';
            
            // If this was the first admin, redirect to login
            if ($isFirstAdmin) {
                header("Location: admin_login.php");
                exit();
            }
        } else {
            throw new Exception('Failed to create admin account');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isFirstAdmin ? 'Setup Admin Account' : 'Create Admin Account' ?> - JAVERIANS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (!$isFirstAdmin): ?>
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="logo" style="margin: 0 auto 20px;"></div>
            <h3 style="text-align: center;">JAVERIANS ADMIN</h3>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_students.php">Manage Students</a>
            <a href="manage_teachers.php">Manage Teachers</a>
            <a href="attendance_reports.php">Attendance Reports</a>
            <a href="admin/settings.php">Settings</a>
            <a href="../signout.php">Sign Out</a>
        </nav>
    </div>
    <div class="admin-content">
    <?php endif; ?>
    
        <div class="container">
            <div class="form-container card" style="max-width: 600px; margin: 2rem auto;">
                <div class="form-content" style="padding: 2rem;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div class="logo" style="margin: 0 auto 20px; width: 60px; height: 60px; background-color: var(--primary-color);">
                            <i class="fas fa-user-shield" style="color: white; font-size: 1.5rem; line-height: 60px;"></i>
                        </div>
                        <h2 style="margin-bottom: 0.5rem;"><?= $isFirstAdmin ? 'SETUP ADMIN ACCOUNT' : 'CREATE NEW ADMIN' ?></h2>
                        <p style="color: var(--text-gray);">
                            <?= $isFirstAdmin ? 'Create the first administrator account' : 'Add a new administrator to the system' ?>
                        </p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required minlength="3">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email (Optional)</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8">
                            <small style="color: var(--text-gray);">Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                            <i class="fas fa-user-plus"></i> CREATE ADMIN ACCOUNT
                        </button>
                        
                        <?php if (!$isFirstAdmin): ?>
                        <div style="text-align: center; margin-top: 1.5rem;">
                            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    
    <?php if (!$isFirstAdmin): ?>
    </div>
    <?php endif; ?>
</body>
</html>
