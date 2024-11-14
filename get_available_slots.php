<?php
require_once 'config.php';

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$service = $_GET['service'] ?? 'counseling';

$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $conn->prepare("SELECT id, date, time, service FROM appointment_slots WHERE date BETWEEN ? AND ? AND service = ? AND is_available = 1 ORDER BY date, time");
$stmt->bind_param("sss", $start_date, $end_date, $service);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['id'] . '|' . $row['date'] . '|' . $row['time'] . '|' . $row['service'] . "\n";
}

$conn->close();