<?php
session_start();

/* ============================================
   DATABASE CONFIGURATION
============================================ */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_management');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/* ============================================
   GET TEACHER ID (e.g., TCHR-001)
============================================ */
$teacher_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($teacher_id)) {
    die("Invalid teacher ID.");
}

/* ============================================
   FETCH TEACHER DATA
============================================ */
$sql = "SELECT * FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Teacher not found.");
}

$teacher = $result->fetch_assoc();
$stmt->close();

/* ============================================
   HANDLE UPDATE FORM SUBMISSION
============================================ */
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $password = trim($_POST['password']);
    $profile_picture = $teacher['profile_picture']; // Default to old picture

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } else {
        /* ============================================
           HANDLE PROFILE PICTURE UPLOAD
        ============================================ */
        if (!empty($_FILES['profile_picture']['name'])) {
            $target_dir = "uploads/teachers/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = basename($_FILES["profile_picture"]["name"]);
            $target_file = $target_dir . uniqid("teacher_") . "_" . $file_name;
            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ["jpg", "jpeg", "png", "gif"];

            if (in_array($image_file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    $profile_picture = $target_file;
                } else {
                    $error = "Error uploading profile picture.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
            }
        }

        /* ============================================
           UPDATE TEACHER RECORD
        ============================================ */
        if (empty($error)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "
                    UPDATE teachers SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        department = ?, 
                        password = ?, 
                        profile_picture = ?, 
                        updated_at = NOW()
                    WHERE teacher_id = ?
                ";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssssssss", $first_name, $last_name, $email, $phone, $department, $hashed_password, $profile_picture, $teacher_id);
            } else {
                $update_sql = "
                    UPDATE teachers SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        department = ?, 
                        profile_picture = ?, 
                        updated_at = NOW()
                    WHERE teacher_id = ?
                ";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $department, $profile_picture, $teacher_id);
            }

            if ($stmt->execute()) {
                header("Location: manage_teachers.php?success=Teacher updated successfully!");
                exit();
            } else {
                $error = "Error updating record: " . $conn->error;
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher - <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }
        .header {
            background: #4b6cb7;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        form {
            padding: 30px;
        }
        label {
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert.error { background: #f8d7da; color: #721c24; }
        .alert.success { background: #d4edda; color: #155724; }
        .profile-preview {
            text-align: center;
            margin-bottom: 15px;
        }
        .profile-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>✏️ Edit Teacher - <?= htmlspecialchars($teacher['teacher_id']) ?></h2>
    </div>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="profile-preview">
            <?php if (!empty($teacher['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($teacher['profile_picture']) ?>" alt="Profile Picture">
            <?php else: ?>
                <img src="default-avatar.png" alt="Default Picture">
            <?php endif; ?>
        </div>

        <label>Profile Picture</label>
        <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.gif">

        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($teacher['first_name']) ?>" required>

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($teacher['last_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" required>

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>">

        <label>Department</label>
        <input type="text" name="department" value="<?= htmlspecialchars($teacher['department']) ?>">

        <label>Password (leave blank to keep current)</label>
        <input type="password" name="password" placeholder="Enter new password if you want to change it">

        <div class="btn-group">
            <a href="manage_teachers.php" class="btn btn-secondary">← Back</a>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>
