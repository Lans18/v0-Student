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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        $role = $_POST['role'] ?? 'student';
        $profile_picture = null;

        // Handle file upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }

            if ($_FILES['profile_picture']['size'] > 2097152) {
                throw new Exception("File size must be less than 2MB");
            }

            $filename = 'profile_' . ($role === 'student' ? $_POST['student_id'] : $_POST['teacher_id']) . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                $profile_picture = 'uploads/profile_pictures/' . $filename;
            }
        }

        $user_data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password']
        ];

        if ($role === 'student') {
            $user_data['student_id'] = $_POST['student_id'];
            $user_data['course'] = $_POST['course'];
            $user_data['year_level'] = $_POST['year_level'];
            
            $student = new Student();
            $result = $student->register($user_data, $profile_picture);

            if ($result) {
                // Generate token
                $token = bin2hex(random_bytes(16));
                $student->saveEmailToken($user_data['student_id'], $token);

                // Send confirmation email
                $confirmation_link = "http://localhost/student/confirm_email.php?token=$token";
                mail($user_data['email'], "Confirm your email", "Click this link to confirm your account: $confirmation_link");

                $message = "Registration successful! Please check your email to confirm your account.";
                $messageType = 'success';
                // Do not log in or redirect until confirmed
                exit();
            }
        } else {
            $user_data['teacher_id'] = $_POST['teacher_id'];
            $user_data['department'] = $_POST['department'];
            
            $teacher = new Teacher();
            $result = $teacher->register($user_data, $profile_picture);
            
            if ($result) {
                $_SESSION['user_id'] = $user_data['teacher_id'];
                $_SESSION['role'] = 'teacher';
                $_SESSION['user_data'] = $teacher->getByTeacherId($user_data['teacher_id']);
                header("Location: teacher_dashboard.php");
                exit();
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
    <title>Sign Up - JAVERIANS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --secondary-light: #b5179e;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --text-gray: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Background animation elements */
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
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 120px;
            height: 120px;
            background: var(--secondary-light);
            top: 70%;
            left: 80%;
            animation-delay: 3s;
        }

        .shape-3 {
            width: 60px;
            height: 60px;
            background: var(--success);
            top: 20%;
            left: 85%;
            animation-delay: 6s;
        }

        .shape-4 {
            width: 100px;
            height: 100px;
            background: var(--warning);
            top: 80%;
            left: 15%;
            animation-delay: 9s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .form-sidebar {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 8s infinite linear;
        }

        @keyframes pulse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .logo i {
            font-size: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-sidebar h2 {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .form-sidebar p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .features {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature i {
            color: var(--success);
            font-size: 18px;
        }

        .form-content {
            flex: 1.5;
            padding: 40px;
            overflow-y: auto;
            max-height: 90vh;
            position: relative;
        }

        .form-title {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            position: relative;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
            background: #fafafa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background: white;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-block {
            width: 100%;
            display: block;
        }

        .error-message {
            background: #fee;
            color: var(--danger);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            animation: shake 0.5s ease;
        }

        .success-message {
            background: #efe;
            color: #0a7c2f;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0a7c2f;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }

        .password-strength-meter {
            height: 100%;
            width: 0%;
            border-radius: 5px;
            transition: var(--transition);
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .required::after {
            content: " *";
            color: var(--danger);
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            background: #f8f9fa;
            border: 1px dashed var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .file-upload-label i {
            margin-right: 8px;
            color: var(--primary);
        }

        .file-name {
            margin-top: 5px;
            font-size: 14px;
            color: var(--text-gray);
        }

        input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-gray);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .login-link a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-gray);
            position: relative;
            z-index: 2;
            transition: var(--transition);
        }

        .step.active {
            background: var(--primary);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            white-space: nowrap;
            color: var(--text-gray);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        /* New styles for enhanced features */
        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            z-index: 2;
        }

        .input-icon .form-control {
            padding-left: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-check-label {
            font-size: 14px;
            color: var(--text-gray);
        }

        .terms-link {
            color: var(--primary);
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .validation-message {
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .validation-message.valid {
            color: #0a7c2f;
        }

        .validation-message.invalid {
            color: var(--danger);
        }

        .preview-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            display: none;
            border: 2px solid var(--primary);
        }

        .role-selection {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .role-option:hover {
            border-color: var(--primary-light);
            background-color: #f8f9ff;
        }

        .role-option.selected {
            border-color: var(--primary);
            background-color: #f0f4ff;
        }

        .role-option i {
            font-size: 40px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .role-option h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .role-option p {
            font-size: 14px;
            color: var(--text-gray);
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .form-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .form-sidebar {
                padding: 30px 20px;
            }
            
            .form-content {
                padding: 30px 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .progress-steps {
                margin-bottom: 20px;
            }
            
            .step-label {
                display: none;
            }
            
            .role-selection {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="form-sidebar">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>JAVERIANS</h2>
                <p>Join our academic community today and unlock a world of learning opportunities with expert educators and innovative programs.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Access to premium courses</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Expert instructors</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Interactive learning</span>
                    </div>
                </div>
            </div>
            
            <div class="form-content">
                <h2 class="form-title">CREATE ACCOUNT</h2>
                
                <div class="progress-steps">
                    <div class="step active" data-step="1">
                        1
                        <span class="step-label">Account Type</span>
                    </div>
                    <div class="step" data-step="2">
                        2
                        <span class="step-label">Personal Info</span>
                    </div>
                    <div class="step" data-step="3">
                        3
                        <span class="step-label">Security</span>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="<?php echo htmlspecialchars($messageType === 'error' ? 'error' : 'success', ENT_QUOTES, 'UTF-8'); ?>-message">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="signup-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <!-- Step 1: Account Type -->
                    <div class="form-section active" id="step-1">
                        <div class="role-selection">
                            <div class="role-option <?php echo $role === 'student' ? 'selected' : ''; ?>" data-role="student">
                                <i class="fas fa-user-graduate"></i>
                                <h3>Student</h3>
                                <p>Join as a student to access courses and learning materials</p>
                            </div>
                            <div class="role-option <?php echo $role === 'teacher' ? 'selected' : ''; ?>" data-role="teacher">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <h3>Teacher</h3>
                                <p>Join as an educator to create and manage courses</p>
                            </div>
                        </div>
                        
                        <input type="hidden" id="role" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div id="student-fields" style="<?php echo $role === 'student' ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-group input-icon">
                                <i class="fas fa-id-card"></i>
                                <label for="student_id" class="required">Student ID</label>
                                <input type="text" id="student_id" name="student_id" class="form-control" placeholder="Enter your student ID" value="<?php echo htmlspecialchars($_POST['student_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="validation-message" id="student-id-validation"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="course" class="required">Course</label>
                                    <select id="course" name="course" class="form-control form-select">
                                        <option value="">Select Course</option>
                                        <option value="Computer Science" <?php echo (($_POST['course'] ?? '') === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Information Technology" <?php echo (($_POST['course'] ?? '') === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Business Administration" <?php echo (($_POST['course'] ?? '') === 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Engineering" <?php echo (($_POST['course'] ?? '') === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Education" <?php echo (($_POST['course'] ?? '') === 'Education') ? 'selected' : ''; ?>>Education</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="year_level" class="required">Year Level</label>
                                    <select id="year_level" name="year_level" class="form-control form-select">
                                        <option value="">Select Year</option>
                                        <option value="1st Year" <?php echo (($_POST['year_level'] ?? '') === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo (($_POST['year_level'] ?? '') === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo (($_POST['year_level'] ?? '') === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo (($_POST['year_level'] ?? '') === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="teacher-fields" style="<?php echo $role === 'teacher' ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-group input-icon">
                                <i class="fas fa-id-card"></i>
                                <label for="teacher_id" class="required">Teacher ID</label>
                                <input type="text" id="teacher_id" name="teacher_id" class="form-control" placeholder="Enter your teacher ID" value="<?php echo htmlspecialchars($_POST['teacher_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="validation-message" id="teacher-id-validation"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="department" class="required">Department</label>
                                <select id="department" name="department" class="form-control form-select">
                                    <option value="">Select Department</option>
                                    <option value="Computer Science" <?php echo (($_POST['department'] ?? '') === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                    <option value="Information Technology" <?php echo (($_POST['department'] ?? '') === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                    <option value="Business Administration" <?php echo (($_POST['department'] ?? '') === 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                                    <option value="Engineering" <?php echo (($_POST['department'] ?? '') === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                    <option value="Education" <?php echo (($_POST['department'] ?? '') === 'Education') ? 'selected' : ''; ?>>Education</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-navigation">
                            <div></div> <!-- Empty spacer -->
                            <button type="button" class="btn btn-primary" onclick="validateStep1()">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Personal Information -->
                    <div class="form-section" id="step-2">
                        <div class="form-row">
                            <div class="form-group input-icon">
                                <i class="fas fa-user"></i>
                                <label for="first_name" class="required">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Enter your first name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="validation-message" id="first-name-validation"></div>
                            </div>
                            
                            <div class="form-group input-icon">
                                <i class="fas fa-user"></i>
                                <label for="last_name" class="required">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Enter your last name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="validation-message" id="last-name-validation"></div>
                            </div>
                        </div>
                        
                        <div class="form-group input-icon">
                            <i class="fas fa-envelope"></i>
                            <label for="email" class="required">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email address" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="validation-message" id="email-validation"></div>
                        </div>
                        
                        <div class="form-group input-icon">
                            <i class="fas fa-phone"></i>
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" placeholder="+63" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="validation-message" id="phone-validation"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <div class="file-upload">
                                <label for="profile_picture" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose a profile picture</span>
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                                <div class="file-name" id="file-name">No file chosen</div>
                            </div>
                            <img id="preview-image" class="preview-image" src="" alt="Profile Preview">
                            <small style="display: block; margin-top: 5px; color: var(--text-gray);">Max size: 2MB (JPG, PNG, GIF)</small>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn" onclick="prevStep(1)" style="background: #e9ecef; color: var(--text-gray);">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" onclick="validateStep2()">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Security -->
                    <div class="form-section" id="step-3">
                        <div class="form-group input-icon">
                            <label for="password" class="required">Password</label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="Create a password">
                                <span class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-meter" id="password-strength-meter"></div>
                            </div>
                            <div class="validation-message" id="password-validation"></div>
                            <small style="display: block; margin-top: 5px; color: var(--text-gray);">Must be at least 8 characters long</small>
                        </div>
                        
                        <div class="form-group input-icon">
                            <label for="confirm_password" class="required">Confirm Password</label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8" placeholder="Confirm your password">
                                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="validation-message" id="confirm-password-validation"></div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                            <label for="terms" class="form-check-label">
                                I agree to the <a href="assets\css\terms of services.html" class="terms-link">Terms of Service and Privacy Policy.</a>
                            </label>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn" onclick="prevStep(2)" style="background: #e9ecef; color: var(--text-gray);">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <div class="loading-spinner" id="submit-spinner"></div>
                                <i class="fas fa-user-plus"></i> SIGN UP
                            </button>
                        </div>
                    </div>
                    
                    <div class="login-link">
                        Already have an account? <a href="signin.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Enhanced role selection with visual feedback
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                const role = this.getAttribute('data-role');
                document.getElementById('role').value = role;
                toggleFormFields();
            });
        });
        
        // Toggle form fields based on role selection
        function toggleFormFields() {
            const role = document.getElementById('role').value;
            const studentFields = document.getElementById('student-fields');
            const teacherFields = document.getElementById('teacher-fields');
            
            if (role === 'student') {
                studentFields.style.display = 'block';
                teacherFields.style.display = 'none';
                
                // Set required fields
                document.getElementById('student_id').required = true;
                document.getElementById('course').required = true;
                document.getElementById('year_level').required = true;
                document.getElementById('teacher_id').required = false;
                document.getElementById('department').required = false;
            } else {
                studentFields.style.display = 'none';
                teacherFields.style.display = 'block';
                
                // Set required fields
                document.getElementById('student_id').required = false;
                document.getElementById('course').required = false;
                document.getElementById('year_level').required = false;
                document.getElementById('teacher_id').required = true;
                document.getElementById('department').required = true;
            }
        }
        
        // Multi-step form functionality
        function nextStep(step) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`step-${step}`).classList.add('active');
            
            document.querySelectorAll('.step').forEach(stepEl => {
                stepEl.classList.remove('active');
            });
            document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
        }
        
        function prevStep(step) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`step-${step}`).classList.add('active');
            
            document.querySelectorAll('.step').forEach(stepEl => {
                stepEl.classList.remove('active');
            });
            document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
        }
        
        // Initialize form fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFormFields();
            
            // File upload display with preview
            document.getElementById('profile_picture').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const fileName = file ? file.name : 'No file chosen';
                document.getElementById('file-name').textContent = fileName;
                
                // Show image preview
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('preview-image');
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('preview-image').style.display = 'none';
                }
            });
            
            // Real-time validation for fields
            setupRealTimeValidation();
        });
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
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
            
            // Update password validation message
            validatePassword();
        });
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('.password-toggle i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Real-time validation setup
        function setupRealTimeValidation() {
            // Student/Teacher ID validation
            document.getElementById('student_id').addEventListener('input', validateStudentId);
            document.getElementById('teacher_id').addEventListener('input', validateTeacherId);
            
            // Name validation
            document.getElementById('first_name').addEventListener('input', validateFirstName);
            document.getElementById('last_name').addEventListener('input', validateLastName);
            
            // Email validation
            document.getElementById('email').addEventListener('input', validateEmail);
            
            // Phone validation
            document.getElementById('phone').addEventListener('input', validatePhone);
            
            // Password validation
            document.getElementById('password').addEventListener('input', validatePassword);
            document.getElementById('confirm_password').addEventListener('input', validateConfirmPassword);
        }
        
        // Validation functions
        function validateStudentId() {
            const id = document.getElementById('student_id').value;
            const validation = document.getElementById('student-id-validation');
            
            if (id.length < 3) {
                showValidation(validation, 'Student ID must be at least 3 characters', false);
                return false;
            } else {
                showValidation(validation, 'Student ID looks good!', true);
                return true;
            }
        }
        
        function validateTeacherId() {
            const id = document.getElementById('teacher_id').value;
            const validation = document.getElementById('teacher-id-validation');
            
            if (id.length < 3) {
                showValidation(validation, 'Teacher ID must be at least 3 characters', false);
                return false;
            } else {
                showValidation(validation, 'Teacher ID looks good!', true);
                return true;
            }
        }
        
        function validateFirstName() {
            const name = document.getElementById('first_name').value;
            const validation = document.getElementById('first-name-validation');
            
            if (name.length < 2) {
                showValidation(validation, 'First name must be at least 2 characters', false);
                return false;
            } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                showValidation(validation, 'First name can only contain letters', false);
                return false;
            } else {
                showValidation(validation, 'First name looks good!', true);
                return true;
            }
        }
        
        function validateLastName() {
            const name = document.getElementById('last_name').value;
            const validation = document.getElementById('last-name-validation');
            
            if (name.length < 2) {
                showValidation(validation, 'Last name must be at least 2 characters', false);
                return false;
            } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                showValidation(validation, 'Last name can only contain letters', false);
                return false;
            } else {
                showValidation(validation, 'Last name looks good!', true);
                return true;
            }
        }
        
        function validateEmail() {
            const email = document.getElementById('email').value;
            const validation = document.getElementById('email-validation');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                showValidation(validation, 'Please enter a valid email address', false);
                return false;
            } else {
                showValidation(validation, 'Email looks good!', true);
                return true;
            }
        }
        
        function validatePhone() {
            const phone = document.getElementById('phone').value;
            const validation = document.getElementById('phone-validation');
            const phoneRegex = /^[\0]?[1-9][\d]{0,15}$/;
            
            if (phone && !phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''))) {
                showValidation(validation, 'Please enter a valid phone number', false);
                return false;
            } else if (phone) {
                showValidation(validation, 'Phone number looks good!', true);
                return true;
            } else {
                validation.textContent = '';
                validation.className = 'validation-message';
                return true;
            }
        }
        
        function validatePassword() {
            const password = document.getElementById('password').value;
            const validation = document.getElementById('password-validation');
            
            if (password.length < 8) {
                showValidation(validation, 'Password must be at least 8 characters', false);
                return false;
            } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                showValidation(validation, 'Include uppercase, lowercase, and numbers', false);
                return false;
            } else {
                showValidation(validation, 'Password is strong!', true);
                return true;
            }
        }
        
        function validateConfirmPassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const validation = document.getElementById('confirm-password-validation');
            
            if (password !== confirmPassword) {
                showValidation(validation, 'Passwords do not match', false);
                return false;
            } else if (confirmPassword.length > 0) {
                showValidation(validation, 'Passwords match!', true);
                return true;
            } else {
                validation.textContent = '';
                validation.className = 'validation-message';
                return false;
            }
        }
        
        function showValidation(element, message, isValid) {
            element.textContent = message;
            element.className = `validation-message ${isValid ? 'valid' : 'invalid'}`;
            element.innerHTML = `${isValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>'} ${message}`;
        }
        
        // Step validation functions
        function validateStep1() {
            const role = document.getElementById('role').value;
            let isValid = true;
            
            if (role === 'student') {
                isValid = validateStudentId() && 
                         document.getElementById('course').value !== '' && 
                         document.getElementById('year_level').value !== '';
            } else {
                isValid = validateTeacherId() && 
                         document.getElementById('department').value !== '';
            }
            
            if (isValid) {
                nextStep(2);
            } else {
                alert('Please fill in all required fields correctly before proceeding.');
            }
        }
        
        function validateStep2() {
            const isValid = validateFirstName() && 
                           validateLastName() && 
                           validateEmail() && 
                           validatePhone();
            
            if (isValid) {
                nextStep(3);
            } else {
                alert('Please fill in all required fields correctly before proceeding.');
            }
        }
        
        // Form validation
        document.getElementById('signup-form').addEventListener('submit', function(e) {
            // Validate all fields
            const role = document.getElementById('role').value;
            let isValid = true;
            
            // Step 1 validation
            if (role === 'student') {
                isValid = validateStudentId() && 
                         document.getElementById('course').value !== '' && 
                         document.getElementById('year_level').value !== '';
            } else {
                isValid = validateTeacherId() && 
                         document.getElementById('department').value !== '';
            }
            
            // Step 2 validation
            isValid = isValid && validateFirstName() && 
                      validateLastName() && 
                      validateEmail() && 
                      validatePhone();
            
            // Step 3 validation
            isValid = isValid && validatePassword() && 
                      validateConfirmPassword() && 
                      document.getElementById('terms').checked;
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly before submitting.');
                return;
            }
            
            // Show loading spinner
            const submitBtn = document.getElementById('submit-btn');
            const spinner = document.getElementById('submit-spinner');
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            
            // Form will submit normally to PHP backend
        });
    </script>
</body>
</html>
