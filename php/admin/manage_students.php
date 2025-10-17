<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Student.php';

SessionManager::startSecureSession();

// Redirect if not admin
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../signin.php");
    exit();
}

$student = new Student();
$students = $student->getAllStudents();
$message = '';
$messageType = '';

// Handle actions (view, edit, delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $student_id = $_GET['id'];
    
    if ($action === 'delete') {
        if ($student->deleteStudent($student_id)) {
            $message = 'Student deleted successfully';
            $messageType = 'success';
            // Refresh the list
            $students = $student->getAllStudents();
        } else {
            $message = 'Failed to delete student';
            $messageType = 'error';
        }
    }
}

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - JAVERIANS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="logo" style="margin: 0 auto 20px;"></div>
            <h3 style="text-align: center;">JAVERIANS ADMIN</h3>
        </div>
        <nav class="admin-sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_students.php" class="active">Manage Students</a>
            <a href="manage_teachers.php">Manage Teachers</a>
            <a href="attendance_reports.php">Attendance Reports</a>
            <a href="settings.php">Settings</a>
            <a href="../signout.php">Sign Out</a>
        </nav>
    </div>
    
    <div class="admin-content">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1>Manage Students</h1>
                <a href="add_student.php" class="btn btn-primary">Add New Student</a>
            </div>
            
            <?php if ($message): ?>
                <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Students</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($student['year_level'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= date('M j, Y', strtotime($student['created_at'])) ?></td>
                                        <td>
                                            <a href="view_student.php?id=<?= $student['student_id'] ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="manage_students.php?action=delete&id=<?= $student['student_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
