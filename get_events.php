<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
require_once 'config.php';

$date = $_GET['date'] ?? '';
$service = $_GET['service'] ?? '';

if (empty($date) || empty($service)) {
    echo "ERROR: Missing date or service";
    exit;
}

// Ensure the date is in the correct format
$dateObj = new DateTime($date);
$formattedDate = $dateObj->format('Y-m-d');

$sql = "SELECT * FROM calendar_events WHERE date = ? AND service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $formattedDate, $service);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['id'] . "|" . $row['title'] . "|" . $row['time'] . "|" . $row['service'] . "\n";
    }
} else {
    echo "No events found";
}

$stmt->close();
$conn->close();