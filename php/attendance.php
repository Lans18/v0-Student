<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Student.php';

SessionManager::startSecureSession();

// Redirect if not student
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isStudent()) {
    header("Location: signin.php");
    exit();
}

$student = new Student();
$studentData = $student->getByStudentId($_SESSION['user_id']);

if (!$studentData) {
    SessionManager::destroySession();
    header("Location: signin.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!SessionManager::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid form submission');
        }

        if (isset($_POST['time_in'])) {
            if ($student->recordTimeIn($_SESSION['user_id'])) {
                $message = 'Time in recorded successfully!';
                $messageType = 'success';
            } else {
                $message = 'You have already timed in today without timing out';
                $messageType = 'warning';
            }
        } elseif (isset($_POST['time_out'])) {
            if ($student->recordTimeOut($_SESSION['user_id'])) {
                $message = 'Time out recorded successfully!';
                $messageType = 'success';
            } else {
                $message = 'No time in record found to update';
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$qr_data = "STUDENT_ID:" . $studentData['student_id'] . "|NAME:" . $studentData['first_name'] . " " . $studentData['last_name'] . "COURSE" . $studentData['course'] . "YEAR LEVEL" . $studentData['year_level'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
$attendance_records = $student->getAttendanceRecords($_SESSION['user_id']);

// Get today's records
$today_records = array_filter($attendance_records, function($record) {
    return date('Y-m-d', strtotime($record['time_in'])) === date('Y-m-d');
});

// Get weekly summary
$week_records = array_filter($attendance_records, function($record) {
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $recordDate = date('Y-m-d', strtotime($record['time_in']));
    return $recordDate >= $startOfWeek && $recordDate <= $endOfWeek;
});

$csrf_token = SessionManager::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - JAVERIANS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .header {
            background:rgba(255, 255, 255, 0.95);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .header-nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .header-nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .btn-danger {
            background-color: var(--danger);
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .attendance-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        @media (max-width: 968px) {
            .attendance-container {
                grid-template-columns: 1fr;
            }
        }

        .attendance-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            height: fit-content;
        }

        .attendance-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--light-gray);
        }

        .profile-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .profile-info p {
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
        }

        .qr-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .qr-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .qr-subtitle {
            color: var(--gray);
            margin-bottom: 1.5rem;
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
        }

        .qr-code img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        }

        .attendance-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
        }

        .section-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .table-container {
            overflow-x: auto;
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
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-present {
            background-color: #e7f5ef;
            color: #2e8b57;
        }

        .status-absent {
            background-color: #fde8e8;
            color: #d9534f;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            text-align: center;
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

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background-color: #e7f5ef;
            color: #2e8b57;
            border-left: 4px solid #2e8b57;
        }

        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .error-message {
            background-color: #fde8e8;
            color: #d9534f;
            border-left: 4px solid #d9534f;
        }

        .time-display {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1rem 0;
            text-align: center;
            color: var(--dark);
        }

        .current-time {
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: 700;
        }

        .today-date {
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 0.25rem;
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
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="header-logo">
            <div class="logo">J</div>
            <div class="header-title">JAVERIANS</div>
        </a>
        <nav class="header-nav">
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="signout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="attendance-container">
            <div class="attendance-sidebar">
                <h2 style="margin-bottom: 1.5rem; color: var(--dark); text-align: center;">ATTENDANCE</h2>
                
                <?php if ($message): ?>
                    <div class="<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>-message">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <div class="time-display">
                    <div class="current-time" id="current-time"><?= date('h:i A') ?></div>
                    <div class="today-date"><?= date('l, F j, Y') ?></div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?= htmlspecialchars($studentData['student_id'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= htmlspecialchars($studentData['first_name'] . ' ' . $studentData['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Course:</span>
                        <span class="info-value"><?= htmlspecialchars($studentData['course'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Year Level:</span>
                        <span class="info-value"><?= htmlspecialchars($studentData['year_level'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                
                <form method="POST" class="action-buttons">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="time_in" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> TIME IN
                    </button>
                    <button type="submit" name="time_out" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> TIME OUT
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> BACK TO PROFILE
                    </a>
                </div>
            </div>
            
            <div class="attendance-content">
                <div class="profile-card">
                    <?php if (!empty($studentData['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($studentData['profile_picture'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="profile-image">
                    <?php else: ?>
                        <img src="assets/images/avatar.png" alt="Profile" class="profile-image">
                    <?php endif; ?>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($studentData['first_name'] . ' ' . $studentData['last_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($studentData['course'], ENT_QUOTES, 'UTF-8') ?> • Year <?= htmlspecialchars($studentData['year_level'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Student ID: <?= htmlspecialchars($studentData['student_id'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= count($today_records) ?></div>
                        <div class="stat-label">Today's Records</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count($week_records) ?></div>
                        <div class="stat-label">This Week</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count($attendance_records) ?></div>
                        <div class="stat-label">Total Records</div>
                    </div>
                </div>
                
                <div class="qr-section">
                    <h3 class="qr-title">SCAN QR CODE</h3>
                    <p class="qr-subtitle">For attendance marking</p>
                    <div class="qr-code">
                        <img src="<?= htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code">
                    </div>
                </div>
                
                <div class="attendance-section">
                    <h3 class="section-title"><i class="fas fa-calendar-day"></i> TODAY'S ATTENDANCE</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($today_records)): ?>
                                    <?php foreach ($today_records as $record): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($record['time_in'])) ?></td>
                                            <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                            <td>
                                                <?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '<span class="status-badge status-pending">Pending</span>' ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-present">Present</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <i class="fas fa-clock"></i>
                                            <div>No attendance records for today</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="attendance-section">
                    <h3 class="section-title"><i class="fas fa-history"></i> RECENT ATTENDANCE</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recent_records = array_slice($attendance_records, 0, 5);
                                ?>
                                <?php if (!empty($recent_records)): ?>
                                    <?php foreach ($recent_records as $record): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($record['time_in'])) ?></td>
                                            <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                            <td>
                                                <?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '<span class="status-badge status-pending">Pending</span>' ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-present">Present</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <i class="fas fa-history"></i>
                                            <div>No attendance records found</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Initial call and set interval
        updateTime();
        setInterval(updateTime, 1000);
        
        // Add confirmation for time out if no time in today
        document.querySelector('button[name="time_out"]').addEventListener('click', function(e) {
            const todayRecords = <?= count($today_records) ?>;
            if (todayRecords === 0) {
                if (!confirm('You haven\'t timed in today. Are you sure you want to time out?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
