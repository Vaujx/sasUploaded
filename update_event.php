<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
require_once 'config.php';

$id = $_POST['id'] ?? '';
$title = $_POST['title'] ?? '';
$time = $_POST['time'] ?? '';
$service = $_POST['service'] ?? '';

if (empty($id) || empty($title) || empty($time) || empty($service)) {
    echo "ERROR: Missing required fields";
    exit;
}

$sql = "UPDATE calendar_events SET title = ?, time = ?, service = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $title, $time, $service, $id);

if ($stmt->execute()) {
    echo "SUCCESS: Event updated successfully";
} else {
    echo "ERROR: " . $stmt->error;
}

$stmt->close();
$conn->close();