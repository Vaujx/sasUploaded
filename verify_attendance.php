<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    echo 'error';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];

    $stmt = $conn->prepare("UPDATE appointment_requests SET attendance_status = 'attended', verification_timestamp = NOW() WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
} else {
    echo 'error';
}

$conn->close();