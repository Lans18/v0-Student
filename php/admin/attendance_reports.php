<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Database.php';

SessionManager::startSecureSession();

// Ensure admin privileges
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../signin.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Default filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$course = $_GET['course'] ?? '';

// Build SQL query dynamically
$query = "
    SELECT a.student_id, a.time_in, a.time_out,
           s.first_name, s.last_name, s.course, s.year_level
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.student_id
    WHERE DATE(a.time_in) BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if (!empty($course)) {
    $query .= " AND s.course = :course";
    $params[':course'] = $course;
}

$query .= " ORDER BY a.time_in DESC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available courses for filter dropdown
$course_stmt = $conn->prepare("SELECT DISTINCT course FROM students ORDER BY course");
$course_stmt->execute();
$courses = $course_stmt->fetchAll(PDO::FETCH_COLUMN);

$csrf_token = SessionManager::generateCSRFToken();

// Calculate total attendance count
$total_records = count($attendance_records);

// Optional: calculate total duration (only if time_out exists)
$total_minutes = 0;
foreach ($attendance_records as $r) {
    if (!empty($r['time_out'])) {
        $time_in = new DateTime($r['time_in']);
        $time_out = new DateTime($r['time_out']);
        $diff = $time_in->diff($time_out);
        $total_minutes += ($diff->h * 60) + $diff->i;
    }
}
$total_hours = floor($total_minutes / 60);
$total_rem_minutes = $total_minutes % 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - JAVERIANS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        table tr:hover { background: #f5f5f5; }
        .summary-box {
            display: flex;
            gap: 2rem;
            padding: 1rem;
            background: #f0f4f8;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .summary-item h4 {
            margin: 0;
            font-weight: 600;
        }
        .summary-item span {
            font-size: 0.9rem;
            color: #555;
        }
    </style>
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
            <h1 style="margin-bottom: 1.5rem;">Attendance Reports</h1>

            <!-- Summary Section -->
            <div class="summary-box">
                <div class="summary-item">
                    <h4><?= $total_records ?></h4>
                    <span>Total Records</span>
                </div>
                <div class="summary-item">
                    <h4><?= "$total_hours hr $total_rem_minutes min" ?></h4>
                    <span>Total Attendance Duration</span>
                </div>
                <div class="summary-item">
                    <h4><?= htmlspecialchars($course ?: "All Courses") ?></h4>
                    <span>Course Filter</span>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Filter Reports</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                            <div>
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control"
                                       value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div>
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control"
                                       value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div>
                                <label for="course">Course</label>
                                <select id="course" name="course" class="form-control form-select">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>" <?= $course === $c ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Apply Filters</button>
                        <a href="attendance_reports.php" class="btn btn-secondary" style="margin-top: 1rem; margin-left: .5rem;">Reset</a>
                    </form>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attendance Records</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($attendance_records): ?>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($record['student_id']) ?></td>
                                            <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                            <td><?= htmlspecialchars($record['course']) ?></td>
                                            <td><?= htmlspecialchars($record['year_level']) ?></td>
                                            <td><?= date('M j, Y', strtotime($record['time_in'])) ?></td>
                                            <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                            <td><?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '—' ?></td>
                                            <td>
                                                <?php
                                                if ($record['time_out']) {
                                                    $time_in = new DateTime($record['time_in']);
                                                    $time_out = new DateTime($record['time_out']);
                                                    $interval = $time_in->diff($time_out);
                                                    echo $interval->format('%h hr %i min');
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;">No records found for the selected filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="export_attendance.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&course=<?= urlencode($course) ?>&download=1" class="btn btn-info" style="margin-left:0.5rem;">Download</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
