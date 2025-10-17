<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Teacher.php';

SessionManager::startSecureSession();

// Redirect if not admin
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../signin.php");
    exit();
}

$admin = new Admin();
$student = new Student();
$teacher = new Teacher();

$stats = $admin->getDashboardStats();
$recentStudents = array_slice($student->getAllStudents(), 0, 5);
$recentTeachers = array_slice($teacher->getAllTeachers(), 0, 5);

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JAVERIANS</title>
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
            <h1 style="margin-bottom: 1.5rem;">Admin Dashboard</h1>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_students'] ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_teachers'] ?></div>
                    <div class="stat-label">Total Teachers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['today_attendance'] ?></div>
                    <div class="stat-label">Today's Attendance</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['pending_approvals'] ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>
            
            <!-- Recent Students -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Recent Students</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStudents as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="manage_students.php?action=view&id=<?= $student['student_id'] ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="manage_students.php?action=edit&id=<?= $student['student_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="manage_students.php?action=delete&id=<?= $student['student_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentStudents)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="manage_students.php" class="btn btn-primary">View All Students</a>
                </div>
            </div>
            
            <!-- Recent Teachers -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Teachers</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTeachers as $teacher): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="manage_teachers.php?action=view&id=<?= $teacher['teacher_id'] ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="manage_teachers.php?action=edit&id=<?= $teacher['teacher_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="manage_teachers.php?action=delete&id=<?= $teacher['teacher_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentTeachers)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No teachers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="manage_teachers.php" class="btn btn-primary">View All Teachers</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
