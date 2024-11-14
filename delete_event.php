<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
require_once 'config.php';

$id = $_GET['id'] ?? '';
$service = $_GET['service'] ?? '';

if (empty($id) || empty($service)) {
    echo "ERROR: Missing required fields";
    exit;
}

$sql = "DELETE FROM calendar_events WHERE id = ? AND service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id, $service);

if ($stmt->execute()) {
    echo "SUCCESS: Event deleted successfully";
} else {
    echo "ERROR: " . $stmt->error;
}

$stmt->close();
$conn->close();