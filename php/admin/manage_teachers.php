<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Teacher.php';

SessionManager::startSecureSession();

// Redirect if not admin
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../signin.php");
    exit();
}

$teacher = new Teacher();
$teachers = $teacher->getAllTeachers();
$message = '';
$messageType = '';

// Handle actions (view, edit, delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $teacher_id = $_GET['id'];
    
    if ($action === 'delete') {
        if ($teacher->deleteTeacher($teacher_id)) {
            $message = 'Teacher deleted successfully';
            $messageType = 'success';
            // Refresh the list
            $teachers = $teacher->getAllTeachers();
        } else {
            $message = 'Failed to delete teacher';
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
    <title>Manage Teachers - JAVERIANS</title>
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
            <a href="manage_students.php">Manage Students</a>
            <a href="manage_teachers.php" class="active">Manage Teachers</a>
            <a href="attendance_reports.php">Attendance Reports</a>
            <a href="settings.php">Settings</a>
            <a href="../signout.php">Sign Out</a>
        </nav>
    </div>
    
    <div class="admin-content">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1>Manage Teachers</h1>
                <a href="add_teacher.php" class="btn btn-primary">Add New Teacher</a>
            </div>
            
            <?php if ($message): ?>
                <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Teachers</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($teacher['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= date('M j, Y', strtotime($teacher['created_at'])) ?></td>
                                        <td>
                                            <a href="view_teacher.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="edit_teacher.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="manage_teachers.php?action=delete&id=<?= $teacher['teacher_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($teachers)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No teachers found</td>
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
