<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Teacher.php';

SessionManager::startSecureSession();

// Redirect if not teacher
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isTeacher()) {
    header("Location: signin.php");
    exit();
}

$teacher = new Teacher();
$teacherData = $teacher->getByTeacherId($_SESSION['user_id']);

if (!$teacherData) {
    SessionManager::destroySession();
    header("Location: signin.php");
    exit();
}

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - JAVERIANS</title>
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
           <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="teacher_dashboard.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</a>
            <a href="signout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </nav>
    </div>
    
    <div class="container">
        <h1 style="margin-bottom: 1.5rem;">Teacher Dashboard</h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Total Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Pending Tasks</div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3 class="card-title">Welcome, <?= htmlspecialchars($teacherData['first_name'] . ' ' . $teacherData['last_name'], ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <div class="card-body">
                <p>Department: <?= htmlspecialchars($teacherData['department'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Email: <?= htmlspecialchars($teacherData['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>Phone: <?= htmlspecialchars($teacherData['phone'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="card-footer">
                <a href="profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
        
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="#" class="btn btn-primary">View Students</a>
                    <a href="#" class="btn btn-secondary">Upload Materials</a>
                    <a href="#" class="btn btn-success">Create Assignment</a>
                    <a href="#" class="btn btn-info">View Attendance</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
