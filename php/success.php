<?php
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Student.php';

SessionManager::startSecureSession();

if (!isset($_SESSION['user_id']) || !SessionManager::validateSession()) {
    header("Location: signin.php");
    exit();
}

if (!isset($_SESSION['user_data'])) {
    header("Location: signin.php");
    exit();
}

$studentData = $_SESSION['user_data'];

$qr_data = "STUDENT_ID:" . $studentData['student_id']
         . "|NAME:" . $studentData['first_name'] . " " . $studentData['last_name'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);

$student = new Student();
$student->updateQRCode($studentData['student_id'], $qr_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration Successful — JAVERIANS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
  <style>
    :root {
      --primary: #4f46e5;
      --primary-dark: #4338ca;
      --accent: #10b981;
      --text-dark: #1f2937;
      --text-light: #6b7280;
      --bg-light: #f9fafb;
      --white: #ffffff;
      --radius: 12px;
      --spacing: 1rem;
      --spacing-lg: 1.5rem;
      --shadow-md: 0 4px 10px rgba(0,0,0,0.1);
      --shadow-lg: 0 8px 20px rgba(0,0,0,0.15);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-dark);
      background: var(--bg-light);
      line-height: 1.5;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .header {
      background: var(--white);
      padding: var(--spacing) var(--spacing-lg);
      box-shadow: var(--shadow-md);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .header-logo {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .header-logo .logo {
      width: 40px;
      height: 40px;
      background: var(--primary);
      border-radius: 50%;
    }

    .header-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-dark);
    }

    .header-nav a {
      margin-left: 1rem;
      padding: 0.5rem 1rem;
      border-radius: var(--radius);
      transition: background 0.2s;
    }

    .header-nav a:hover {
      background: rgba(79, 70, 229, 0.1);
    }

    .header-nav .btn-danger {
      background: #ef4444;
      color: white;
    }

    .container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 var(--spacing-lg);
    }

    .card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1.5fr;
    }

    @media (max-width: 768px) {
      .card {
        grid-template-columns: 1fr;
      }
    }

    .profile-sidebar {
      background: var(--primary);
      color: var(--white);
      padding: var(--spacing-lg);
      text-align: center;
      position: relative;
    }

    .profile-sidebar h2 {
      margin-bottom: var(--spacing-lg);
      font-size: 1.8rem;
    }

    .qr-container {
      background: var(--white);
      color: var(--text-dark);
      border-radius: var(--radius);
      padding: var(--spacing);
      margin: auto;
      width: 260px;
      box-shadow: var(--shadow-md);
    }

    .qr-container h3 {
      margin-bottom: 0.25rem;
      color: var(--primary-dark);
    }

    .qr-container p {
      margin-bottom: var(--spacing-lg);
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .qr-code img {
      width: 100%;
      height: auto;
      display: block;
    }

    .btn-primary {
      background: var(--primary);
      color: var(--white);
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius);
      display: inline-block;
      transition: background 0.2s;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
    }

    .profile-content {
      padding: var(--spacing-lg);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .profile-image {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary);
      margin-bottom: var(--spacing-lg);
      box-shadow: var(--shadow-md);
    }

    .profile-content h3 {
      font-size: 1.6rem;
      margin-bottom: 0.5rem;
      color: var(--text-dark);
    }

    .profile-content p {
      margin-bottom: 0.5rem;
      color: var(--text-light);
    }
  </style>
</head>
<body>
  <header class="header">
    <a href="index.php" class="header-logo">
      <div class="logo"></div>
      <div class="header-title">JAVERIANS</div>
    </a>
    <nav class="header-nav">
      <a href="profile.php">Profile</a>
      <a href="attendance.php">Attendance</a>
      <a href="signout.php" class="btn-danger">Sign Out</a>
    </nav>
  </header>

  <div class="container">
    <div class="card">
      <div class="profile-sidebar">
        <h2>REGISTRATION SUCCESSFUL</h2>
        <div class="qr-container">
          <h3>YOUR QR CODE</h3>
          <p>For attendance marking</p>
          <div class="qr-code">
            <img src="<?= htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8') ?>"
                 alt="QR Code" />
          </div>
        </div>
        <div style="margin-top: var(--spacing-lg);">
          <a href="profile.php" class="btn-primary">GO TO PROFILE</a>
        </div>
      </div>
      <div class="profile-content">
        <?php if (!empty($studentData['profile_picture'])): ?>
          <img src="<?= htmlspecialchars($studentData['profile_picture'], ENT_QUOTES, 'UTF-8') ?>"
               alt="Profile Picture" class="profile-image" />
        <?php else: ?>
          <img src="assets/images/avatar.png"
               alt="Default Avatar" class="profile-image" />
        <?php endif; ?>

        <h3><?= htmlspecialchars($studentData['first_name'] . ' ' . $studentData['last_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p>ID: <?= htmlspecialchars($studentData['student_id'], ENT_QUOTES, 'UTF-8') ?></p>
        <p>Course: <?= htmlspecialchars($studentData['course'], ENT_QUOTES, 'UTF-8') ?></p>
        <p>Year: <?= htmlspecialchars($studentData['year_level'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
  </div>
</body>
</html>
