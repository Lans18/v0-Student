<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/Teacher.php';

SessionManager::startSecureSession();

if (!isset($_SESSION['user_id']) || !SessionManager::validateSession()) {
    header("Location: signin.php");
    exit();
}

$userData = $_SESSION['user_data'] ?? null;
$role = $_SESSION['role'] ?? '';

if (!$userData) {
    if ($role === 'student') {
        $student = new Student();
        $userData = $student->getByStudentId($_SESSION['user_id']);
    } elseif ($role === 'teacher') {
        $teacher = new Teacher();
        $userData = $teacher->getByTeacherId($_SESSION['user_id']);
    }
    
    if (!$userData) {
        SessionManager::destroySession();
        header("Location: signin.php");
        exit();
    }
    
    $_SESSION['user_data'] = $userData;
}

// Handle profile picture update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_profile_picture'])) {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        if ($_FILES['new_profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['new_profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }

            if ($_FILES['new_profile_picture']['size'] > 2097152) {
                throw new Exception("File size must be less than 2MB");
            }

            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['new_profile_picture']['tmp_name'], $destination)) {
                // Delete old profile picture if exists
                if (!empty($userData['profile_picture']) && file_exists(__DIR__ . '/' . $userData['profile_picture'])) {
                    unlink(__DIR__ . '/' . $userData['profile_picture']);
                }
                
                $profile_path = 'uploads/profile_pictures/' . $filename;
                
                if ($role === 'student') {
                    $student = new Student();
                    if ($student->updateProfilePicture($_SESSION['user_id'], $profile_path)) {
                        $userData = $student->getByStudentId($_SESSION['user_id']);
                        $_SESSION['user_data'] = $userData;
                        $message = "Profile picture updated successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to update profile picture in database");
                    }
                } elseif ($role === 'teacher') {
                    $teacher = new Teacher();
                    if ($teacher->updateProfilePicture($_SESSION['user_id'], $profile_path)) {
                        $userData = $teacher->getByTeacherId($_SESSION['user_id']);
                        $_SESSION['user_data'] = $userData;
                        $message = "Profile picture updated successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to update profile picture in database");
                    }
                }
            } else {
                throw new Exception("Failed to upload new profile picture");
            }
        } elseif ($_FILES['new_profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("File upload error: " . $_FILES['new_profile_picture']['error']);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Generate QR code for students
$qr_url = '';
if ($role === 'student') {
    // Format: JSON data that includes student_id, name, and timestamp (for verification)
    $timestamp = time();
    $qr_data = [
        'student_id' => $userData['student_id'],
        'name' => $userData['first_name'] . ' ' . $userData['last_name'],
        'timestamp' => $timestamp,
        // Add a simple verification hash to prevent tampering
        'verify' => md5($userData['student_id'] . $timestamp . 'JAVERIANS_QR_SECRET')
    ];
    
    // Convert to JSON and encode for QR
    $qr_json = json_encode($qr_data);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_json);
    
    // For backward compatibility, also store the simple format
    $simple_qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode("#" . $userData['student_id']);
    
    // Use the simple format for display as it's more reliable for quick scanning
    $qr_url = $simple_qr_url;
}

// Get attendance records for students
$attendance_records = [];
if ($role === 'student') {
    $student = new Student();
    $attendance_records = $student->getAttendanceRecords($_SESSION['user_id']);
}

// Calculate attendance statistics
$total_records = count($attendance_records);
$completed_sessions = count(array_filter($attendance_records, function($record) {
    return !empty($record['time_out']);
}));
$attendance_rate = $total_records > 0 ? round(($completed_sessions / $total_records) * 100) : 0;

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - JAVERIANS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--dark);
        }

        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 8px;
            margin-right: 10px;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-nav a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .header-nav a:hover {
            color: var(--primary);
            background-color: var(--light-gray);
        }

        .header-nav a.active {
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
        }

        .btn-danger {
            background: var(--danger);
            color: white !important;
        }

        .btn-danger:hover {
            background: #e11571;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                padding: 1rem;
            }
            
            .header-nav {
                margin-top: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        .profile-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            height: fit-content;
        }

        .profile-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .user-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
        }

        .user-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--light);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .user-avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
        }

        .user-avatar-container:hover .user-avatar-overlay {
            opacity: 1;
        }

        .user-avatar-overlay i {
            color: white;
            font-size: 1.5rem;
        }

        .user-name {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .user-role {
            text-align: center;
            color: var(--gray);
            margin-bottom: 1.5rem;
            text-transform: capitalize;
            font-size: 0.9rem;
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--light);
            border-radius: 20px;
        }

        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .info-label {
            font-weight: 500;
            color: var(--gray);
        }

        .info-value {
            font-weight: 600;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .profile-picture-form {
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: none;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            text-align: center;
            font-family: inherit;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: var(--secondary);
        }

        .btn-secondary:hover {
            background: #651a98;
        }

        .btn-primary {
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .qr-container {
            text-align: center;
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            margin: 1rem auto;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #4361ee; /* Fixed border color and thickness */
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 0 0 1px var(--light-gray);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        th {
            background: var(--light);
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        .success-message {
            background: rgba(76, 201, 240, 0.1);
            color: #0c5460;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: rgba(247, 37, 133, 0.1);
            color: #721c24;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-button {
            display: block;
            padding: 0.75rem 1.5rem;
            background: var(--light-gray);
            color: var(--gray);
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload-button:hover {
            background: #dde1e7;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }

        .tab-container {
            margin-bottom: 2rem;
        }

        .tab-buttons {
            display: flex;
            border-bottom: 1px solid var(--light-gray);
            margin-bottom: 1.5rem;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }

        .tab-button.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .attendance-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-present {
            background: rgba(76, 201, 240, 0.2);
            color: #0c5460;
        }

        .status-absent {
            background: rgba(247, 37, 133, 0.2);
            color: #721c24;
        }

        .progress-bar {
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="header-logo">
            <div class="logo"></div>
            <div class="header-title">JAVERIANS</div>
        </a>
        <nav class="header-nav">
            <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
            <?php if ($role === 'student'): ?>
                <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <a href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
            <?php endif; ?>
            <?php if ($role === 'teacher'): ?>
                <a href="teacher_dashboard.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</a>
            <?php endif; ?>
            <a href="signout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="user-avatar-container">
                    <?php if (!empty($userData['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($userData['profile_picture'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="user-avatar">
                    <?php else: ?>
                        <img src="assets/images/avatar.png" alt="Profile" class="user-avatar">
                    <?php endif; ?>
                    <div class="user-avatar-overlay" id="changeAvatarBtn">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                
                <h2 class="user-name"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="user-role"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></div>
                
                <?php if ($message): ?>
                    <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <?php if ($role === 'student'): ?>
                        <a href="attendance.php" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Attendance</a>
                    <?php endif; ?>
                    <button class="btn btn-outline" id="editProfileBtn"><i class="fas fa-edit"></i> Edit</button>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-button active" data-tab="profile">Profile Information</button>
                        <?php if ($role === 'student'): ?>
                            <button class="tab-button" data-tab="attendance">Attendance</button>
                            <button class="tab-button" data-tab="qrcode">QR Code</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-content active" id="profile-tab">
                        <h3 class="section-title"><i class="fas fa-user-circle"></i> Profile Details</h3>
                        
                        <div class="profile-picture-form" id="profilePictureForm">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-group">
                                    <label for="new_profile_picture">Choose a new profile picture</label>
                                    <div class="file-upload-wrapper">
                                        <div class="file-upload-button">
                                            <i class="fas fa-cloud-upload-alt"></i> Choose File
                                        </div>
                                        <input type="file" id="new_profile_picture" name="new_profile_picture" accept="image/*" required>
                                    </div>
                                    <small style="display: block; margin-top: 5px; color: var(--gray);">Max size: 2MB (JPG, PNG, GIF)</small>
                                </div>
                                <button type="submit" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Update Picture</button>
                                <button type="button" class="btn btn-outline" id="cancelUpload">Cancel</button>
                            </form>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?= htmlspecialchars($userData['phone'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            
                            <?php if ($role === 'student'): ?>
                                <div class="info-item">
                                    <span class="info-label">Student ID:</span>
                                    <span class="info-value"><?= htmlspecialchars($userData['student_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Course:</span>
                                    <span class="info-value"><?= htmlspecialchars($userData['course'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Year Level:</span>
                                    <span class="info-value"><?= htmlspecialchars($userData['year_level'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php elseif ($role === 'teacher'): ?>
                                <div class="info-item">
                                    <span class="info-label">Teacher ID:</span>
                                    <span class="info-value"><?= htmlspecialchars($userData['teacher_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Department:</span>
                                    <span class="info-value"><?= htmlspecialchars($userData['department'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($role === 'student'): ?>
                        <div class="tab-content" id="attendance-tab">
                            <h3 class="section-title"><i class="fas fa-history"></i> Attendance History</h3>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value"><?= $total_records ?></div>
                                    <div class="stat-label">Total Records</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?= $completed_sessions ?></div>
                                    <div class="stat-label">Completed Sessions</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?= $attendance_rate ?>%</div>
                                    <div class="stat-label">Attendance Rate</div>
                                </div>
                            </div>
                            
                            <?php if (!empty($attendance_records)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time In</th>
                                                <th>Time Out</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($record['time_in'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                                    <td>
                                                        <?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : 'Not recorded' ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($record['time_out']) {
                                                            $timeIn = new DateTime($record['time_in']);
                                                            $timeOut = new DateTime($record['time_out']);
                                                            $interval = $timeIn->diff($timeOut);
                                                            echo $interval->format('%h h %i m');
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="attendance-status <?= $record['time_out'] ? 'status-present' : 'status-absent' ?>">
                                                            <?= $record['time_out'] ? 'Present' : 'Absent' ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No attendance records found</h3>
                                    <p>Your attendance records will appear here once you start attending classes.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content" id="qrcode-tab">
                            <?php if (!empty($qr_url)): ?>
                                <h3 class="section-title"><i class="fas fa-qrcode"></i> Student QR Code</h3>
                                <div class="qr-container">
                                    <p>Use this QR code for attendance marking</p>
                                    <div class="qr-code">
                                        <img src="<?= htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code">
                                    </div>
                                    <p class="small">Student ID: <?= htmlspecialchars($userData['student_id'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <form id="editProfileForm">
                <div class="form-group">
                    <label for="editFirstName">First Name</label>
                    <input type="text" id="editFirstName" class="form-control" value="<?= htmlspecialchars($userData['first_name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="editLastName">Last Name</label>
                    <input type="text" id="editLastName" class="form-control" value="<?= htmlspecialchars($userData['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" class="form-control" value="<?= htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="editPhone">Phone</label>
                    <input type="text" id="editPhone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelEdit">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // File input display
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('new_profile_picture');
            const fileUploadButton = document.querySelector('.file-upload-button');
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    fileUploadButton.innerHTML = `<i class="fas fa-file-image"></i> ${this.files[0].name}`;
                } else {
                    fileUploadButton.innerHTML = `<i class="fas fa-cloud-upload-alt"></i> Choose File`;
                }
            });

            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    button.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });

            // Profile picture form toggle
            const changeAvatarBtn = document.getElementById('changeAvatarBtn');
            const profilePictureForm = document.getElementById('profilePictureForm');
            const cancelUpload = document.getElementById('cancelUpload');
            
            changeAvatarBtn.addEventListener('click', () => {
                profilePictureForm.style.display = 'block';
            });
            
            cancelUpload.addEventListener('click', () => {
                profilePictureForm.style.display = 'none';
                fileInput.value = '';
                fileUploadButton.innerHTML = `<i class="fas fa-cloud-upload-alt"></i> Choose File`;
            });

            // Edit profile modal
            const editProfileBtn = document.getElementById('editProfileBtn');
            const editProfileModal = document.getElementById('editProfileModal');
            const closeModal = document.getElementById('closeModal');
            const cancelEdit = document.getElementById('cancelEdit');
            const editProfileForm = document.getElementById('editProfileForm');
            
            editProfileBtn.addEventListener('click', () => {
                editProfileModal.style.display = 'flex';
            });
            
            closeModal.addEventListener('click', () => {
                editProfileModal.style.display = 'none';
            });
            
            cancelEdit.addEventListener('click', () => {
                editProfileModal.style.display = 'none';
            });
            
            editProfileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                // In a real application, you would send this data to the server
                alert('Profile updated successfully!');
                editProfileModal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === editProfileModal) {
                    editProfileModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
