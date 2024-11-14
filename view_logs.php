<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
session_start();
require_once 'config.php';

// Check if the user is logged in as a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: index.php');
    exit();
}

$staff_id = $_SESSION['user_id'];

// Fetch logs
$sql = "SELECT al.*, ar.date, ar.time, u.username as student_name 
        FROM appointment_logs al
        JOIN appointment_requests ar ON al.appointment_id = ar.id
        JOIN users u ON ar.student_id = u.id
        WHERE ar.staff_id = ?
        ORDER BY al.performed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Appointment Logs</h1>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Student</th>
                    <th>Action</th>
                    <th>Performed At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('F j, Y', strtotime($log['date'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($log['time'])); ?></td>
                        <td><?php echo $log['student_name']; ?></td>
                        <td><?php echo ucfirst($log['action']); ?></td>
                        <td><?php echo date('F j, Y g:i A', strtotime($log['performed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="staff_dashboard.php" class="button">Back to Dashboard</a>
    </div>
</body>
</html>