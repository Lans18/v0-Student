<?php
// Start session
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'student_management';

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// ==============================
// Fetch Teachers
// ==============================
$sql = "SELECT id, teacher_id, first_name, last_name, email, phone, department FROM teachers ORDER BY id DESC";

$result = $conn->query($sql);

if ($result === false) {
    die("Database error: " . $conn->error);
} elseif ($result->num_rows === 0) {
    // No error, just no data
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
    <title>Manage Teachers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1300px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        h1 {
            color: #333;
            font-size: 28px;
            margin: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-add {
            background: #28a745;
        }
        
        .btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-back {
            background: #6c757d;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateX(-3px);
        }
        
        .stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-value {
            color: #f5576c;
            font-size: 24px;
            font-weight: 700;
        }
        
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #e0e0e0;
            padding: 14px;
            text-align: left;
        }
        
        th {
            background-color: #f5576c;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #333;
            font-size: 14px;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        tr:hover {
            background-color: #fff0f2;
            transition: background-color 0.2s;
        }
        
        .id-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            min-width: 45px;
            text-align: center;
        }
        
        .name-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .teacher-name {
            font-weight: 500;
            color: #333;
        }
        
        .department-badge {
            display: inline-block;
            background: #e8f4f8;
            color: #0077be;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #0077be;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
            font-size: 16px;
        }
        
        .copy-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 2px 6px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 4px;
            transition: all 0.2s;
            color: #f5576c;
        }
        
        .copy-btn:hover {
            background: #fff0f2;
            border-color: #f5576c;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            h1 {
                font-size: 22px;
            }
            
            .action-buttons {
                width: 100%;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            th, td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🏫 Manage Teachers</h1>
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
            </div>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-label">Total Teachers</span>
                    <span class="stat-value"><?= $result->num_rows; ?></span>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="view_teacher.php?id=<?= $row['id']; ?>" style="text-decoration: none;">
                                        <span class="id-badge">
                                            #<?= safe($row['id']); ?>
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <a href="view_teacher.php?id=<?= $row['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <?= safe($row['teacher_id']); ?>
                                    </a>
                                </td>
                                <td><?= safe($row['first_name']); ?></td>
                                <td><?= safe($row['last_name']); ?></td>
                                <td><?= safe($row['email']); ?></td>
                                <td><?= safe($row['phone']); ?></td>
                                <td>
                                    <span class="department-badge"><?= safe($row['department']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" class="no-data">No teachers found in the database</td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyToClipboard(text, event) {
            event.preventDefault();
            navigator.clipboard.writeText(text).then(function() {
                alert('Teacher ID #' + text + ' copied to clipboard!');
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
