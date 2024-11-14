<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'error';
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$sql = "SELECT ar.id, ar.date, ar.time, ar.service, u.id as student_id, u.name as student_name 
        FROM appointment_requests ar 
        JOIN users u ON ar.student_id = u.id 
        WHERE ar.status = 'accepted'";

if ($user_type === 'student') {
    $sql .= " AND ar.student_id = ?";
} elseif ($user_type === 'staff') {
    $sql .= " AND ar.staff_id = ?";
}

$sql .= " ORDER BY ar.date, ar.time";

$stmt = $conn->prepare($sql);

if ($user_type !== 'admin') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['id'] . '|' . $row['date'] . '|' . $row['time'] . '|' . $row['service'] . '|' . $row['student_id'] . '|' . $row['student_name'] . "\n";
}

$stmt->close();
$conn->close();