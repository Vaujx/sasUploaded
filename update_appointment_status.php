<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    echo 'error: Unauthorized access';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    // Validate status
    if (!in_array($status, ['approved', 'declined'])) {
        echo 'error: Invalid status';
        exit();
    }

    $stmt = $conn->prepare("UPDATE appointment_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo 'success: Status updated';
        } else {
            echo 'error: No rows updated';
        }
    } else {
        echo 'error: ' . $stmt->error;
    }

    $stmt->close();
} else {
    echo 'error: Invalid request method';
}

$conn->close();