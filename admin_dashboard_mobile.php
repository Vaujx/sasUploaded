<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch admin name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_name = $user_data['name'];
$stmt->close();

// Fetch user statistics
$stmt = $conn->prepare("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
$stmt->execute();
$result = $stmt->get_result();
$user_stats = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch appointment statistics
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM appointment_requests GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
$appointment_stats = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
        }
        .container {
            padding: 20px;
        }
        h1, h2 {
            color: #000080;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #000080;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .navbar {
            background-color: #000080;
            color: #fff;
            padding: 10px 20px;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 15px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#home"><i class="fas fa-home"></i></a>
        <a href="#users"><i class="fas fa-users"></i></a>
        <a href="#appointments"><i class="fas fa-calendar"></i></a>
        <a href="#settings"><i class="fas fa-cog"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>

        <div class="card" id="users">
            <h2>User Statistics</h2>
            <?php foreach ($user_stats as $stat): ?>
                <div class="stat-item">
                    <span><?php echo ucfirst(htmlspecialchars($stat['user_type'])); ?>s:</span>
                    <span><?php echo htmlspecialchars($stat['count']); ?></span>
                </div>
            <?php endforeach; ?>
            <a href="manage_users.php" class="btn">Manage Users</a>
        </div>

        <div class="card" id="appointments">
            <h2>Appointment Statistics</h2>
            <?php foreach ($appointment_stats as $stat): ?>
                <div class="stat-item">
                    <span><?php echo ucfirst(htmlspecialchars($stat['status'])); ?>:</span>
                    <span><?php echo htmlspecialchars($stat['count']); ?></span>
                </div>
            <?php endforeach; ?>
            <a href="manage_appointments.php" class="btn">Manage Appointments</a>
        </div>

        <div class="card" id="settings">
            <h2>System Settings</h2>
            <a href="manage_services.php" class="btn">Manage Services</a>
            <a href="manage_timeslots.php" class="btn">Manage Time Slots</a>
            <a href="system_logs.php" class="btn">View System Logs</a>
        </div>
    </div>

    <script>
   function showEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'table-row';
        }

        function hideEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }

        function toggleSearchHistory() {
            var section = document.getElementById('searchHistorySection');
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        function updateHistory() {
            var searchInput = document.getElementById('searchInput').value;
            var monthYear = document.getElementById('monthYearSelect').value;
            var [year, month] = monthYear.split('-');

            fetch(`get_appointment_history.php?search_student_number=${searchInput}&month=${month}&year=${year}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('appointmentHistoryTable').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        }

        function showChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'block';
            document.getElementById('change-password-popup').style.display = 'block';
        }

        function closeChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'none';
            document.getElementById('change-password-popup').style.display = 'none';
        }

        // Initial load of appointment history
        updateHistory();
    </script>
</body>
</html>