<?php
session_start();

// ==============================
// DATABASE CONNECTION
// ==============================
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'student_management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ==============================
// INITIALIZE VARIABLES
// ==============================
$success = "";
$error = "";

// ==============================
// HANDLE FORM SUBMISSION
// ==============================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $course = trim($_POST["course"]);
    $year_level = trim($_POST["year_level"]);
    $password = $_POST["password"];
    $profile_picture = null;
    $qr_code_url = null;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "⚠️ Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Invalid email format.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "⚠️ Email already exists. Please use a different one.";
        } else {
            // Generate new student ID
            $result = $conn->query("SELECT student_id FROM students ORDER BY id DESC LIMIT 1");
            if ($result && $row = $result->fetch_assoc()) {
                preg_match('/STD-(\d+)/', $row['student_id'], $matches);
                $next = isset($matches[1]) ? str_pad($matches[1] + 1, 3, '0', STR_PAD_LEFT) : '001';
            } else {
                $next = '001';
            }
            $student_id = "STD-" . $next;

            // Upload profile picture
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadDir = "uploads/students/";
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = uniqid("student_") . "." . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                    $profile_picture = $targetPath;
                }
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Simulate QR code URL
            $qr_code_url = "qrcodes/" . strtolower($student_id) . ".png";

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, course, year_level, password, profile_picture, qr_code_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $student_id, $first_name, $last_name, $email, $phone, $course, $year_level, $hashed_password, $profile_picture, $qr_code_url);

            if ($stmt->execute()) {
                $success = "✅ Student added successfully! Student ID: $student_id";
            } else {
                $error = "❌ Error adding student: " . $stmt->error;
            }

            $stmt->close();
        }
        $check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, rgba(17, 13, 49, 1), #00bfa6);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 600px;
            background: #fff;
            margin: 60px auto;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.5s ease-in-out;
        }

        h1 {
            text-align: center;
            color: #0078d7;
            margin-bottom: 25px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 15px;
            color: #222;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: all 0.2s;
        }

        input:focus, select:focus {
            border-color: #0078d7;
            box-shadow: 0 0 5px rgba(0,120,215,0.3);
        }

        .btn {
            margin-top: 25px;
            width: 100%;
            background: #0078d7;
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #005fa3;
        }

        .message {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #0078d7;
            font-weight: 600;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👨‍🎓 Add New Student</h1>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>First Name *</label>
            <input type="text" name="first_name" required>

            <label>Last Name *</label>
            <input type="text" name="last_name" required>

            <label>Email *</label>
            <input type="email" name="email" required>

            <label>Phone</label>
            <input type="text" name="phone" placeholder="+63 912 345 6789">

            <label>Course</label>
            <input type="text" name="course" placeholder="e.g. Information Technology">

            <label>Year Level</label>
            <select name="year_level">
                <option value="">Select Year Level</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
            </select>

            <label>Password *</label>
            <input type="password" name="password" required>

            <label>Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/*">

            <button type="submit" class="btn">Add Student</button>
        </form>

        <a href="dashboard.php" class="back-btn">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
