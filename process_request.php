<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'staff') {
    $staff_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'confirm' || $action === 'reject') {
        $sql = "UPDATE appointment_requests SET status = ? WHERE id = ? AND staff_id = ?";
        $stmt = $conn->prepare($sql);
        $status = ($action === 'confirm') ? 'confirmed' : 'rejected';
        $stmt->bind_param("sii", $status, $request_id, $staff_id);
        
        if ($stmt->execute()) {
            // Log the action
            $sql = "INSERT INTO appointment_logs (appointment_id, action, performed_by) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $request_id, $action, $staff_id);
            $stmt->execute();

            $_SESSION['message'] = "Appointment request " . $status . " successfully.";
        } else {
            $_SESSION['error'] = "Failed to process the appointment request.";
        }
    } else {
        $_SESSION['error'] = "Invalid action.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

$conn->close();
header('Location: staff_dashboard.php');
exit();