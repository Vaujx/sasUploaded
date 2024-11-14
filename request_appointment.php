<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    $_SESSION['error_message'] = 'Unauthorized access';
    header('Location: student_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $slot_id = $_POST['slot_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // First, fetch the slot details and check availability
        $stmt = $conn->prepare("SELECT * FROM appointment_slots WHERE id = ? AND booked < capacity FOR UPDATE");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $date = $row['date'];
            $time = $row['time'];
            $service = $row['service'];
        } else {
            throw new Exception("Invalid slot ID or slot is full");
        }
        $stmt->close();

        // Generate a unique slip number
        $slip_number = generateSlipNumber();

        // Insert the appointment request
        $stmt = $conn->prepare("INSERT INTO appointment_requests (student_id, date, time, status, slip_number) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param("isss", $student_id, $date, $time, $slip_number);
        $stmt->execute();
        $stmt->close();

        // Update the appointment slot to increment the booked count
        $stmt = $conn->prepare("UPDATE appointment_slots SET booked = booked + 1 WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        $_SESSION['success_message'] = "Appointment requested successfully. Your slip number is: " . $slip_number;
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Invalid request method";
}

$conn->close();
header('Location: student_dashboard.php');
exit();

function generateSlipNumber() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $slip_number = '';
    for ($i = 0; $i < 7; $i++) {
        $slip_number .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $slip_number;
}