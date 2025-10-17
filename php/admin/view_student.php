<?php
// ==============================
// Database Configuration
// ==============================
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

// ==============================
// Fetch Students
// ==============================
$sql = "SELECT * FROM students ORDER BY id DESC";
$result = $conn->query($sql);
if ($result === false) {
    die("Database error: " . $conn->error);
}

// Helper function to safely output values
function safe($value) {
    return htmlspecialchars($value ?? 'N/A');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Students</title>
  <link rel="stylesheet" href="../frontend/css/styles.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 1100px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: center;
    }
    th {
      background-color: #0d6efd;
      color: #fff;
    }
    tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    tr:hover {
      background-color: #e9ecef;
    }
    .btn {
      padding: 6px 12px;
      text-decoration: none;
      color: #fff;
      border-radius: 4px;
      display: inline-block;
      margin: 2px;
      font-size: 14px;
      border: none;
      cursor: pointer;
    }
    .edit-btn { background: #28a745; }
    .delete-btn { background: #dc3545; }
    .btn:hover { opacity: 0.8; transform: translateY(-1px); }
    .no-data {
      text-align: center;
      color: #6c757d;
      font-style: italic;
      padding: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>👩‍🎓 Student Details</h2>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Student ID</th>
          <th>Full Name</th>
          <th>Course</th>
          <th>Year Level</th>
          <th>Email</th>
          <th>Phone</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= safe($row['id']); ?></td>
              <td><?= safe($row['student_id']); ?></td>
              <td><?= safe($row['first_name'] . ' ' . $row['last_name']); ?></td>
              <td><?= safe($row['course']); ?></td>
              <td><?= safe($row['year_level']); ?></td>
              <td><?= safe($row['email']); ?></td>
              <td><?= safe($row['phone']); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="no-data">No students found in the database</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

<?php $conn->close(); ?>
