<?php
// ================================
// Database Configuration
// ================================
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'student_management';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ================================
// Initialize Variables
// ================================
$student = [];
$error = '';
$success = '';

// ================================
// Validate Student ID
// ================================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Student ID not provided.");
}

$student_id = trim($_GET['id']); // ✅ Support alphanumeric IDs like STD-001

// ================================
// Fetch Student Data
// ================================
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
} else {
    die("Student not found for ID: " . htmlspecialchars($student_id));
}
$stmt->close();

// ================================
// Update Student Data
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $course = trim($_POST['course']);
    $year_level = trim($_POST['year_level']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($course) || empty($year_level)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $sql = "UPDATE students SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    course = ?, 
                    year_level = ?, 
                    updated_at = NOW()
                WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $course, $year_level, $student_id);

        if ($stmt->execute()) {
            header("Location: manage_students.php?success=Student updated successfully!");
            exit();
        } else {
            $error = "Error updating student: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 800px;
            background: #fff;
            margin: 50px auto;
            border-radius: 12px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2575fc, #6a11cb);
            color: white;
            text-align: center;
            padding: 25px;
        }

        .form-container { padding: 35px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { font-weight: 600; color: #333; }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 1rem;
        }

        .button-group {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2575fc, #6a11cb);
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✏️ Edit Student (<?php echo htmlspecialchars($student['student_id']); ?>)</h1>
        <p>Modify and update student details</p>
    </div>

    <div class="form-container">
        <?php if ($error): ?>
            <div class="alert-error">❌ <?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">

            <div class="form-row">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Course</label>
                    <select name="course" required>
                        <option value="">Select Course</option>
                        <option value="Information Technology" <?php if($student['course']=="Information Technology") echo "selected"; ?>>Information Technology</option>
                        <option value="Computer Science" <?php if($student['course']=="Computer Science") echo "selected"; ?>>Computer Science</option>
                        <option value="Engineering" <?php if($student['course']=="Engineering") echo "selected"; ?>>Engineering</option>
                        <option value="Business Administration" <?php if($student['course']=="Business Administration") echo "selected"; ?>>Business Administration</option>
                    </select>
                </div>
                <div>
                    <label>Year Level</label>
                    <select name="year_level" required>
                        <option value="">Select Year</option>
                        <option value="1st Year" <?php if($student['year_level']=="1st Year") echo "selected"; ?>>1st Year</option>
                        <option value="2nd Year" <?php if($student['year_level']=="2nd Year") echo "selected"; ?>>2nd Year</option>
                        <option value="3rd Year" <?php if($student['year_level']=="3rd Year") echo "selected"; ?>>3rd Year</option>
                        <option value="4th Year" <?php if($student['year_level']=="4th Year") echo "selected"; ?>>4th Year</option>
                        <option value="5th Year" <?php if($student['year_level']=="5th Year") echo "selected"; ?>>5th Year</option>
                    </select>
                </div>
            </div>

            <div class="button-group">
                <a href="manage_students.php" class="btn btn-secondary">← Back</a>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
