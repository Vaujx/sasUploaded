<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $attendance_status = $_POST['attendance_status'];

    // Validate attendance_status
    if (!in_array($attendance_status, ['attended', 'no_show'])) {
        $error = 'Invalid attendance status';
    } else {
        $stmt = $conn->prepare("UPDATE appointment_requests SET attendance_status = ?, verification_timestamp = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("si", $attendance_status, $appointment_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = 'Attendance status updated successfully';
            } else {
                $error = 'No changes made to attendance status';
            }
        } else {
            $error = 'Error updating attendance status: ' . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();

// Set Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Attendance Status</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Update Attendance Status</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <br>
        <a href="staff_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>