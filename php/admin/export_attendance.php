<?php
require_once __DIR__ . '/../classes/SessionManager.php';
require_once __DIR__ . '/../classes/Database.php';

SessionManager::startSecureSession();

// Verify admin privileges
if (!isset($_SESSION['user_id']) || !SessionManager::validateSession() || !SessionManager::isAdmin()) {
    header("Location: ../signin.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get filters from URL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$course = $_GET['course'] ?? '';
$download = isset($_GET['download']) ? true : false;

// Prepare SQL with filters
$query = "
    SELECT a.student_id, s.first_name, s.last_name, s.course, s.year_level,
           a.time_in, a.time_out
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
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV export
$filename = "attendance_report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
if ($download) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline; filename="' . $filename . '"');
}

// Open output buffer
$output = fopen('php://output', 'w');

// CSV Header Row
fputcsv($output, [
    'Student ID',
    'Full Name',
    'Course',
    'Year Level',
    'Date',
    'Time In',
    'Time Out',
    'Duration (HH:MM)'
]);

if (empty($records)) {
    fputcsv($output, ['No records found for the selected filters.']);
} else {
    foreach ($records as $row) {
        $full_name = $row['first_name'] . ' ' . $row['last_name'];
        $date = date('M j, Y', strtotime($row['time_in']));
        $time_in = date('h:i A', strtotime($row['time_in']));
        $time_out = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '—';

        // Compute duration
        $duration = 'N/A';
        if (!empty($row['time_out'])) {
            $in = new DateTime($row['time_in']);
            $out = new DateTime($row['time_out']);
            $interval = $in->diff($out);
            $duration = sprintf('%02d:%02d', ($interval->h + $interval->d * 24), $interval->i);
        }

        fputcsv($output, [
            $row['student_id'],
            $full_name,
            $row['course'],
            $row['year_level'],
            $date,
            $time_in,
            $time_out,
            $duration
        ]);
    }
}

fclose($output);
exit();
?>
