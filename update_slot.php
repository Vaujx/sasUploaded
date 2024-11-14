<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo 'error';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];

    $stmt = $conn->prepare("UPDATE appointment_slots SET time = ? WHERE id = ? AND date = ? AND service = ?");
    $stmt->bind_param("siss", $time, $id, $date, $service);
    
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