<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/Teacher.php';

SessionManager::startSecureSession();

// Redirect if already logged in
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
$role = 'student';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        $role = $_POST['role'] ?? 'student';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validate inputs
        if (empty($username) || empty($password)) {
            throw new Exception('Please fill in all required fields');
        }

        // Student login
        if ($role === 'student') {
            $student = new Student();
            $studentData = $student->authenticate($username, $password);
            
            if ($studentData) {
                $_SESSION['user_id'] = $studentData['student_id'];
                $_SESSION['role'] = 'student';
                $_SESSION['user_data'] = $studentData;
                
                session_regenerate_id(true);
                header("Location: profile.php");
                exit();
            } else {
                throw new Exception('Invalid Student ID or password');
            }
        }
        // Teacher login
        else {
            $teacher = new Teacher();
            $teacherData = $teacher->authenticate($username, $password);
            
            if ($teacherData) {
                $_SESSION['user_id'] = $teacherData['teacher_id'];
                $_SESSION['role'] = 'teacher';
                $_SESSION['user_data'] = $teacherData;
                
                session_regenerate_id(true);
                header("Location: teacher_dashboard.php");
                exit();
            } else {
                throw new Exception('Invalid Teacher ID or password');
            }
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
    <title>Sign In - JAVERIANS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --secondary-color: #7209b7;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --gradient-primary: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --gradient-secondary: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
            --gradient-background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-background);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background elements */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 15s infinite linear;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: var(--primary-color);
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background: var(--secondary-color);
            bottom: -100px;
            right: -100px;
            animation-delay: 5s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            background: var(--success-color);
            top: 40%;
            right: -75px;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }
        
        .login-container {
            width: 100%;
            max-width: 1100px;
            perspective: 1000px;
        }
        
        .login-card {
            display: flex;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            min-height: 650px;
            transform-style: preserve-3d;
            animation: cardAppear 0.8s ease-out;
        }
        
        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(30px) rotateX(10deg);
            }
            100% {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }
        
        .login-branding {
            flex: 1;
            background: var(--gradient-primary);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .branding-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .logo-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            background: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            backdrop-filter: blur(5px);
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .branding-content {
            position: relative;
            z-index: 1;
        }
        
        .branding-content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .branding-content p {
            opacity: 0.9;
            line-height: 1.7;
            font-size: 1.05rem;
            max-width: 90%;
        }
        
        .features-list {
            margin-top: 30px;
            list-style: none;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .features-list i {
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.2);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
        }
        
        .branding-footer {
            margin-top: auto;
            position: relative;
            z-index: 1;
        }
        
        .branding-footer p {
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .branding-footer a {
            color: white;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            backdrop-filter: blur(5px);
        }
        
        .branding-footer a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .branding-footer a i {
            margin-left: 8px;
            transition: var(--transition);
        }
        
        .branding-footer a:hover i {
            transform: translateX(4px);
        }
        
        .login-form {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        
        .form-header {
            margin-bottom: 35px;
        }
        
        .form-header h2 {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-header p {
            color: var(--gray-color);
            font-size: 1rem;
        }
        
        .alert-message {
            padding: 14px 18px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-message.error {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .alert-message.success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-message i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.95rem;
        }
        
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }
        
        .role-option {
            flex: 1;
            padding: 14px 10px;
            text-align: center;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            user-select: none;
            background: white;
        }
        
        .role-option i {
            font-size: 1.4rem;
            margin-bottom: 8px;
            color: var(--gray-color);
            transition: var(--transition);
        }
        
        .role-option span {
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .role-option.active {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.15);
        }
        
        .role-option.active i {
            color: var(--primary-color);
        }
        
        .role-option.active span {
            color: var(--primary-color);
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: var(--gray-color);
            z-index: 1;
            transition: var(--transition);
        }
        
        .form-input {
            width: 100%;
            padding: 14px 15px 14px 48px;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-input:focus + .input-icon {
            color: var(--primary-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            font-size: 1rem;
            z-index: 2;
            transition: var(--transition);
            padding: 5px;
            border-radius: 4px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
            background: var(--light-gray);
        }
        
        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }
        
        .forgot-password i {
            margin-left: 5px;
            font-size: 0.8rem;
            transition: var(--transition);
        }
        
        .forgot-password:hover i {
            transform: translateX(3px);
        }
        
        .submit-button {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
        }
        
        .submit-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }
        
        .submit-button:hover::before {
            left: 100%;
        }
        
        .submit-button:active {
            transform: translateY(-1px);
        }
        
        .submit-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .submit-button:disabled:hover::before {
            left: -100%;
        }
        
        /* Hide the select element but keep it accessible */
        #role {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        /* Loading animation */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .login-card {
                flex-direction: column;
                min-height: auto;
                max-width: 600px;
                margin: 0 auto;
            }
            
            .login-branding {
                padding: 40px 30px;
            }
            
            .login-form {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 576px) {
            .login-branding, .login-form {
                padding: 30px 20px;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
            }
            
            .logo-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .role-selector {
                flex-direction: column;
            }
            
            .branding-content p {
                max-width: 100%;
            }
            
            .bg-shapes {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <!-- Left side with branding -->
            <div class="login-branding">
                <div class="branding-overlay"></div>
                <div class="logo-container">
                    <i class="fas fa-graduation-cap logo-icon"></i>
                    <h1 class="logo-text">JAVERIANS</h1>
                </div>
                <div class="branding-content">
                    <h2>Welcome Back to JAVERIANS!</h2>
                    <p>Sign in to access your personalized dashboard and continue your educational journey with our comprehensive learning platform.</p>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Access to all your courses and materials</li>
                        <li><i class="fas fa-check"></i> Track your progress and achievements</li>
                        <li><i class="fas fa-check"></i> Interactive learning environment</li>
                    </ul>
                </div>
                <div class="branding-footer">
                    <p>New to Javerians?</p>
                    <a href="signup.php">Create an account <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            
            <!-- Right side with login form -->
            <div class="login-form">
                <div class="form-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert-message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas <?= $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="form-group">
                        <label for="role" class="form-label">I am a</label>
                        <div class="role-selector">
                            <div class="role-option <?= $role === 'student' ? 'active' : '' ?>" data-value="student">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </div>
                            <div class="role-option <?= $role === 'teacher' ? 'active' : '' ?>" data-value="teacher">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teacher</span>
                            </div>
                        </div>
                        <select id="role" name="role" class="form-select" required>
                            <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label" id="username-label">Student ID</label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" id="username" name="username" class="form-input" 
                                   placeholder="Enter your Student ID" 
                                   value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" 
                                   required autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-options">
                            <a href="forgot_password.php" class="forgot-password">Forgot password? <i class="fas fa-question-circle"></i></a>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-button">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Role selector functionality
            const roleOptions = document.querySelectorAll('.role-option');
            const roleSelect = document.getElementById('role');
            const usernameLabel = document.getElementById('username-label');
            const usernameInput = document.getElementById('username');
            
            // Function to update username field based on role
            function updateUsernameField(role) {
                if (role === 'student') {
                    usernameLabel.textContent = 'Student ID';
                    usernameInput.placeholder = 'Enter your Student ID';
                } else if (role === 'teacher') {
                    usernameLabel.textContent = 'Teacher ID';
                    usernameInput.placeholder = 'Enter your Teacher ID';
                }
                usernameInput.setAttribute('autocomplete', 'username');
            }
            
            // Initialize based on current role
            updateUsernameField(roleSelect.value);
            
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    
                    // Update visual selection
                    roleOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update hidden select value
                    roleSelect.value = value;
                    
                    // Update username field label and placeholder
                    updateUsernameField(value);
                });
            });
            
            // Also update when the select element changes (for accessibility)
            roleSelect.addEventListener('change', function() {
                const value = this.value;
                
                // Update visual selection
                roleOptions.forEach(opt => {
                    opt.classList.remove('active');
                    if (opt.getAttribute('data-value') === value) {
                        opt.classList.add('active');
                    }
                });
                
                // Update username field label and placeholder
                updateUsernameField(value);
            });
            
            // Password toggle functionality
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            
            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Update icon
                    const icon = this.querySelector('i');
                    icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                    
                    // Update aria-label
                    this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
                });
            }
            
            // Form submission enhancement
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const submitButton = this.querySelector('.submit-button');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                    }
                });
            }
            
            // Auto-focus on username field
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                setTimeout(() => {
                    usernameField.focus();
                }, 500);
            }
        });
    </script>
</body>
</html>
