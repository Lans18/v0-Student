<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Admin.php';

SessionManager::startSecureSession();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && SessionManager::validateSession() && SessionManager::isAdmin()) {
    header("Location: dashboard.php");
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
        
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password');
        }

        $admin = new Admin();
        $adminData = $admin->login($username, $password);
        
        if ($adminData) {
            $_SESSION['user_id'] = $adminData['username'];
            $_SESSION['role'] = 'admin';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception('Invalid username or password');
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
    <title>Admin Login - JAVERIANS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="form-container card" style="max-width: 500px; margin: 2rem auto;">
            <div class="form-content" style="padding: 2rem;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div class="logo" style="margin: 0 auto 20px; width: 60px; height: 60px; background-color: var(--primary-color);">
                        <i class="fas fa-lock" style="color: white; font-size: 1.5rem; line-height: 60px;"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">ADMIN LOGIN</h2>
                    <p style="color: var(--text-gray);">Access the administration panel</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                        <i class="fas fa-sign-in-alt"></i> LOGIN
                    </button>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
