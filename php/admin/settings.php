<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Admin.php';

SessionManager::startSecureSession();

// Redirect if not admin
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../../signin.php");
    exit();
}

$admin = new Admin();
$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('Please fill in all password fields');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match');
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }

        // Verify current password
        $admin_data = $admin->getByUsername($_SESSION['user_id']);
        if (!$admin_data || !password_verify($current_password, $admin_data['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Update password
        if ($admin->updatePassword($_SESSION['user_id'], $new_password)) {
            $message = 'Password updated successfully';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to update password');
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
    <title>Settings - JAVERIANS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-sidebar">
    <div class="admin-sidebar-header">
        <div class="logo"></div>
        <h3 class="javerians-admin-title">JAVERIANS ADMIN</h3>
    </div>
    <nav class="admin-sidebar-nav">
        <a href="dashboard.php"<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' class="active"' : '' ?>>Dashboard</a>
        <a href="manage_students.php"<?= basename($_SERVER['PHP_SELF']) === 'manage_students.php' ? ' class="active"' : '' ?>>Manage Students</a>
        <a href="manage_teachers.php"<?= basename($_SERVER['PHP_SELF']) === 'manage_teachers.php' ? ' class="active"' : '' ?>>Manage Teachers</a>
        <a href="attendance_reports.php"<?= basename($_SERVER['PHP_SELF']) === 'attendance_reports.php' ? ' class="active"' : '' ?>>Attendance Reports</a>
        <a href="settings.php"<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? ' class="active"' : '' ?>>Settings</a>
        <a href="../signout.php">Sign Out</a>
    </nav>
</div>
    
    <div class="admin-content">
        <div class="container">
            <h1 style="margin-bottom: 1.5rem;">Admin Settings</h1>
            
            <?php if ($message): ?>
                <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                            <div class="password-strength">
                                <div class="password-strength-meter" id="password-strength-meter"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
            
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">System Information</h3>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?= phpversion() ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Software:</span>
                        <span class="info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Database Driver:</span>
                        <span class="info-value">MySQL PDO</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">System Time:</span>
                        <span class="info-value"><?= date('Y-m-d H:i:s') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength meter
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('password-strength-meter');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#10b981'];
            const width = (strength / 5) * 100;
            meter.style.width = width + '%';
            meter.style.backgroundColor = colors[strength - 1] || '#e5e7eb';
        });
    </script>
</body>
</html>
