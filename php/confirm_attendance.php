<?php
require_once __DIR__ . '/classes/Student.php';

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $student = new Student();
    $studentData = $student->getByStudentId($student_id);

    if ($studentData) {
        // Record attendance (time in)
        if ($student->recordTimeIn($student_id)) {
            echo "<h2>Attendance confirmed for {$studentData['first_name']} {$studentData['last_name']}!</h2>";
        } else {
            echo "<h2>Attendance already recorded or error occurred.</h2>";
        }
    } else {
        echo "<h2>Invalid student QR code.</h2>";
    }
} else {
    echo "<h2>No student ID provided.</h2>";
}
?>
