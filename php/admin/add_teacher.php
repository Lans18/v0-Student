<?php
session_start();

// ==============================
// DATABASE CONNECTION (PDO)
// ==============================
$host = 'localhost';
$dbname = 'student_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
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
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $password = trim($_POST['password']);
    $profile_picture = null;

    // ✅ Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($password)) {
        $error = "All required fields must be filled out.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // ✅ Generate unique teacher ID
            $lastTeacher = $pdo->query("SELECT teacher_id FROM teachers ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($lastTeacher && preg_match('/TCHR-(\d+)/', $lastTeacher['teacher_id'], $matches)) {
                $newNumber = str_pad($matches[1] + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }
            $teacher_id = "TCHR-" . $newNumber;

            // ✅ Handle profile picture upload
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadDir = "uploads/teachers/";
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = uniqid("teacher_") . "." . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                    $profile_picture = $targetPath;
                } else {
                    $error = "Failed to upload profile picture.";
                }
            }

            // ✅ Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Insert into database
            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO teachers (teacher_id, first_name, last_name, email, phone, department, password, profile_picture)
                    VALUES (:teacher_id, :first_name, :last_name, :email, :phone, :department, :password, :profile_picture)
                ");
                $stmt->execute([
                    ':teacher_id' => $teacher_id,
                    ':first_name' => $first_name,
                    ':last_name'  => $last_name,
                    ':email'      => $email,
                    ':phone'      => $phone ?: null,
                    ':department' => $department,
                    ':password'   => $hashedPassword,
                    ':profile_picture' => $profile_picture
                ]);

                $success = "✅ Teacher added successfully! (ID: $teacher_id)";
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A teacher with this email already exists.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        label {
            font-weight: 600;
            color: #444;
        }
        input, select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .message {
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 10px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        /* Back button */
        .back-btn {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #5a6268;
        }
        .btn-container {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Teacher 👩‍🏫</h1>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <label>First Name *</label>
            <input type="text" name="first_name" required>

            <label>Last Name *</label>
            <input type="text" name="last_name" required>

            <label>Email *</label>
            <input type="email" name="email" required>

            <label>Phone</label>
            <input type="text" name="phone" placeholder="e.g. 09123456789">

            <label>Department *</label>
            <input type="text" name="department" required>

            <label>Password *</label>
            <input type="password" name="password" required>

            <label>Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/*">

            <button type="submit" class="btn">Add Teacher</button>
        </form>

        <!-- Back to Dashboard button -->
        <div class="btn-container">
            <a href="manage_teachers.php" class="back-btn">⬅️ Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
