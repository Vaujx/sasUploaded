<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as staff or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'staff' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: index.php');
    exit();
}

// Set Content Security Policy
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'none';");

$error = null;
$appointment = null;
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_attendance') {
    $appointment_id = $_POST['appointment_id'];
    $stmt = $conn->prepare("UPDATE appointment_requests SET attendance_status = 'attended', verification_timestamp = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    if ($stmt->execute()) {
        $success_message = "Attendance confirmed successfully.";
    } else {
        $error = "Failed to confirm attendance: " . $conn->error;
    }
    $stmt->close();
}

if (isset($_GET['slip_number'])) {
    $slip_number = $_GET['slip_number'];

    $stmt = $conn->prepare("SELECT ar.*, u.name as student_name, u.student_number as student_id FROM appointment_requests ar 
                            JOIN users u ON ar.student_id = u.id 
                            WHERE ar.slip_number = ?");
    $stmt->bind_param("s", $slip_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        $error = 'Invalid slip number';
    }

    $stmt->close();
} else {
    $error = 'No slip number provided';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Appointment Slip</title>
    <link rel="stylesheet" href="verify_slip.css">
</head>
<body>
    <div class="container">
        <h1>Verify Appointment Slip</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        
        <?php if ($appointment): ?>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($appointment['student_id']); ?></p>
            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($appointment['student_name']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
            <p><strong>Attendance Status:</strong> <?php echo htmlspecialchars($appointment['attendance_status']); ?></p>
            
            <?php if ($appointment['status'] === 'approved' && $appointment['attendance_status'] !== 'attended'): ?>
                <form method="post">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                    <input type="hidden" name="action" value="confirm_attendance">
                    <button type="submit" id="submit">Confirm Attendance</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <br>
        <a href="staff_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>