<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];

// Update all unviewed notifications to 'viewed'
$stmt = $conn->prepare("UPDATE appointment_requests SET notification_status = 'viewed' WHERE student_id = ? AND notification_status = 'unviewed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$conn->close();

echo "success";