<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
session_start();
require_once 'config.php';

// Check if the user is logged in as a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: login.php');
    exit();
}

$staff_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $interval = $_POST['interval'];

    $current_time = strtotime($start_time);
    $end_time = strtotime($end_time);

    while ($current_time < $end_time) {
        $sql = "INSERT INTO appointment_slots (staff_id, date, time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $time = date('H:i:s', $current_time);
        $stmt->bind_param("iss", $staff_id, $date, $time);
        $stmt->execute();

        $current_time = strtotime("+{$interval} minutes", $current_time);
    }

    $_SESSION['message'] = "Appointment slots created successfully.";
    header('Location: staff_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Appointment Slots</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Create Appointment Slots</h1>
        <form action="create_appointment_slots.php" method="post">
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>

            <label for="start_time">Start Time:</label>
            <input type="time" id="start_time" name="start_time" required>

            <label for="end_time">End Time:</label>
            <input type="time" id="end_time" name="end_time" required>

            <label for="interval">Interval (minutes):</label>
            <input type="number" id="interval" name="interval" min="15" step="15" value="30" required>

            <button type="submit">Create Slots</button>
        </form>
    </div>
</body>
</html>