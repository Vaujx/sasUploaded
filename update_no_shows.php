<?php
require_once 'config.php';

$stmt = $conn->prepare("UPDATE appointment_requests 
                        SET attendance_status = 'no_show' 
                        WHERE status = 'approved' 
                        AND attendance_status = 'pending' 
                        AND CONCAT(date, ' ', time) < NOW()");

if ($stmt->execute()) {
    echo "No-shows updated successfully";
} else {
    echo "Error updating no-shows";
}

$stmt->close();
$conn->close();