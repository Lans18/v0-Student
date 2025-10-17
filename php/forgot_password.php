<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/Teacher.php';

SessionManager::startSecureSession();

// Redirect to appropriate dashboard if already logged in
if (isset($_SESSION['user_id']) && SessionManager::validateSession()) {
    if (SessionManager::isTeacher()) {
        header("Location: teacher_dashboard.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

$message = '';
$messageType = '';
$email = '';
$role = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission. Please try again.');
        }

        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $role = $_POST['role'] ?? 'student';
        
        if (!$email) {
            throw new Exception('Please enter a valid email address.');
        }

        $userData = null;
        
        switch ($role) {
            case 'teacher':
                $teacher = new Teacher();
                $userData = $teacher->getByEmail($email);
                break;
            default:
                $student = new Student();
                $userData = $student->getByEmail($email);
        }
        
        if (!$userData) {
            // Don't reveal whether an email exists in the system
            // For security, show the same message regardless
            $message = 'If an account with that email exists, password reset instructions have been sent.';
            $messageType = 'success';
            
            // Still set the session variables to prevent timing attacks
            $reset_token = bin2hex(random_bytes(32));
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $role;
            $_SESSION['reset_expires'] = time() + 3600;
        } else {
            // Generate password reset token for valid user
            $reset_token = bin2hex(random_bytes(32));
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $role;
            $_SESSION['reset_expires'] = time() + 3600; // 1 hour expiration
            
            $message = 'If an account with that email exists, password reset instructions have been sent.';
            $messageType = 'success';
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
    <title>Reset Password - JAVERIANS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #4895ef;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --warning: #f8961e;
            --error: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #adb5bd;
            --border-radius: 12px;
            --border-radius-sm: 6px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 15px 50px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 480px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .logo {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .logo-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 40px;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 30px 25px;
            }
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
            text-align: center;
        }

        .form-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            text-align: center;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            z-index: 2;
        }

        .input-with-icon .form-control {
            padding-left: 48px;
        }

        .btn {
            display: inline-block;
            padding: 15px 20px;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            margin-right: 8px;
        }

        .message {
            padding: 14px 16px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 24px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .success-message {
            background: rgba(76, 201, 240, 0.1);
            color: #0c5460;
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: rgba(247, 37, 133, 0.1);
            color: #721c24;
            border-left: 4px solid var(--error);
        }

        .message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--gray);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .form-footer a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .password-reset-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 24px;
            font-size: 0.9rem;
            color: var(--gray);
            border-left: 4px solid var(--primary);
        }

        .role-options {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
            background: white;
        }

        .role-option:hover {
            border-color: var(--primary-light);
        }

        .role-option.selected {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .role-option i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .role-option input {
            display: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 10px 0;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="logo">JAVERIANS</div>
                <div class="logo-subtitle">Education Portal</div>
            </div>
            <div class="card-body">
                <h2 class="form-title">Reset Your Password</h2>
                <p class="form-subtitle">Enter your email address and we'll send you instructions to reset your password.</p>
                
                <?php if ($message): ?>
                    <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                        <i class="<?= $messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <div class="password-reset-info">
                    <i class="fas fa-info-circle"></i> For security reasons, we'll send a reset link to your email that will expire in 1 hour.
                </div>
                
                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="form-group">
                        <label for="role" class="form-label">Select Your Account Type</label>
                        <div class="role-options">
                            <label class="role-option <?= $role === 'student' ? 'selected' : '' ?>">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                                <input type="radio" name="role" value="student" <?= $role === 'student' ? 'checked' : '' ?>>
                            </label>
                            <label class="role-option <?= $role === 'teacher' ? 'selected' : '' ?>">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teacher</span>
                                <input type="radio" name="role" value="teacher" <?= $role === 'teacher' ? 'checked' : '' ?>>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="Enter your email address" 
                                   value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="loading" id="loadingIndicator">
                        <div class="loading-spinner"></div> Sending reset instructions...
                    </div>
                    
                    <button type="submit" class="btn" id="submitButton">
                        <i class="fas fa-paper-plane"></i> Send Reset Instructions
                    </button>
                    
                    <div class="form-footer">
                        Remember your password? <a href="signin.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const emailInput = document.getElementById('email');
            const roleOptions = document.querySelectorAll('.role-option');
            const submitButton = document.getElementById('submitButton');
            const loadingIndicator = document.getElementById('loadingIndicator');
            
            // Role selection
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    roleOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input').checked = true;
                });
            });
            
            // Form submission with loading indicator
            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                
                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showMessage('Please enter a valid email address.', 'error');
                    emailInput.focus();
                    return;
                }
                
                // Show loading indicator
                submitButton.disabled = true;
                loadingIndicator.style.display = 'block';
            });
            
            // Input validation on blur
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                
                if (email && !isValidEmail(email)) {
                    this.style.borderColor = 'var(--error)';
                    showMessage('Please enter a valid email address.', 'error');
                } else {
                    this.style.borderColor = '';
                }
            });
            
            // Real-time email validation
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                
                if (email && isValidEmail(email)) {
                    this.style.borderColor = 'var(--success)';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            function showMessage(text, type) {
                // Remove existing messages
                const existingMessage = document.querySelector('.message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                
                // Create new message
                const message = document.createElement('div');
                message.className = `${type}-message message`;
                message.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${text}`;
                
                // Insert message
                form.insertBefore(message, form.firstChild);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (message.parentNode) {
                        message.style.opacity = '0';
                        setTimeout(() => message.remove(), 300);
                    }
                }, 5000);
            }
            
            // Add subtle animation to form elements on page load
            const formElements = form.querySelectorAll('.form-group, .password-reset-info, .btn');
            formElements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100 + (index * 100));
            });
        });
    </script>
</body>
</html>
