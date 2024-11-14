<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
require_once 'config.php';

$date = $_POST['date'] ?? '';
$title = $_POST['title'] ?? '';
$time = $_POST['time'] ?? '';
$service = $_POST['service'] ?? '';

if (empty($date) || empty($title) || empty($time) || empty($service)) {
    echo "ERROR: Missing required fields";
    exit;
}

// Ensure the date is in the correct format
$dateObj = new DateTime($date);
$formattedDate = $dateObj->format('Y-m-d');

$sql = "INSERT INTO calendar_events (date, title, time, service) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $formattedDate, $title, $time, $service);

if ($stmt->execute()) {
    echo "SUCCESS: Event added successfully";
} else {
    echo "ERROR: " . $stmt->error;
}

$stmt->close();
$conn->close();