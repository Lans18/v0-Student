<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/QRCode.php';
require_once __DIR__ . '/../classes/Admin.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$db = Database::getInstance();
$qr = new QRCode($db);
$admin = new Admin();

// Get all students for QR management
$query = "SELECT id, student_id, first_name, last_name, email, course FROM students ORDER BY student_id";
$stmt = $db->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get QR statistics
$stats_query = "SELECT 
                    COUNT(DISTINCT student_id) as total_students,
                    COUNT(*) as total_qr_generated,
                    SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as total_qr_used,
                    SUM(CASE WHEN expires_at > NOW() AND is_used = 0 THEN 1 ELSE 0 END) as active_qr_codes
                FROM qr_sessions";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Management - Admin Panel</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/admin.css">
    <style>
        .qr-management-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }

        .qr-table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .qr-table {
            width: 100%;
            border-collapse: collapse;
        }

        .qr-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .qr-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        .qr-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .qr-table tbody tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-generate {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-generate:hover {
            background: #5568d3;
        }

        .btn-view {
            background: #48bb78;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-view:hover {
            background: #38a169;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-expired {
            background: #fed7d7;
            color: #742a2a;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .qr-display {
            text-align: center;
            margin: 20px 0;
        }

        .qr-display img {
            max-width: 300px;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 10px;
        }

        .qr-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }

        .qr-info p {
            margin: 8px 0;
        }

        .qr-info strong {
            color: #667eea;
        }

        .download-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #5568d3;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .filter-group button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="qr-management-container">
        <h1>QR Code Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value"><?php echo $stats['total_students'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3>QR Codes Generated</h3>
                <div class="value"><?php echo $stats['total_qr_generated'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3>QR Codes Used</h3>
                <div class="value"><?php echo $stats['total_qr_used'] ?? 0; ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3>Active QR Codes</h3>
                <div class="value"><?php echo $stats['active_qr_codes'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <input type="text" id="searchInput" placeholder="Search by Student ID or Name">
                <select id="courseFilter">
                    <option value="">All Courses</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                </select>
                <button onclick="filterStudents()">Filter</button>
                <button onclick="resetFilter()" style="background: #999;">Reset</button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="qr-table-container">
            <table class="qr-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-generate" onclick="generateQR('<?php echo $student['student_id']; ?>')">Generate QR</button>
                                <button class="btn-view" onclick="viewQRHistory('<?php echo $student['student_id']; ?>')">History</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- QR Display Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Generated QR Code</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="qrContent"></div>
        </div>
    </div>

    <script>
        function generateQR(studentId) {
            fetch('../../php/generate_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ student_id: studentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayQRModal(data, studentId);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function displayQRModal(data, studentId) {
            const content = `
                <div class="qr-display">
                    <img src="${data.qr_url}" alt="QR Code for ${studentId}">
                </div>
                <div class="qr-info">
                    <p><strong>Student ID:</strong> ${studentId}</p>
                    <p><strong>Session ID:</strong> ${data.session_id}</p>
                    <p><strong>Expires In:</strong> ${data.expires_in_seconds} seconds</p>
                    <p><strong>Generated At:</strong> ${new Date().toLocaleString()}</p>
                </div>
                <button class="download-btn" onclick="downloadQR('${data.qr_url}', '${studentId}')">Download QR Code</button>
            `;
            document.getElementById('qrContent').innerHTML = content;
            document.getElementById('qrModal').style.display = 'block';
        }

        function downloadQR(url, studentId) {
            const link = document.createElement('a');
            link.href = url;
            link.download = `QR_${studentId}.png`;
            link.click();
        }

        function viewQRHistory(studentId) {
            alert('QR History for ' + studentId + ' - Feature coming soon');
        }

        function closeModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        function filterStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const courseFilter = document.getElementById('courseFilter').value;
            const rows = document.querySelectorAll('#studentsTable tbody tr');

            rows.forEach(row => {
                const studentId = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                const course = row.cells[3].textContent;

                const matchesSearch = studentId.includes(searchTerm) || name.includes(searchTerm);
                const matchesCourse = courseFilter === '' || course === courseFilter;

                row.style.display = (matchesSearch && matchesCourse) ? '' : 'none';
            });
        }

        function resetFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('courseFilter').value = '';
            filterStudents();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('qrModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
