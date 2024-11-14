<?php
require_once 'config.php';

// Current date and time
$current_datetime = date('Y-m-d H:i:s');

// Start a transaction
$conn->begin_transaction();

try {
    // Move past appointment slots to appointment_logs
    $move_query = "INSERT INTO appointment_logs (slot_id, date, time, service, capacity, booked)
                   SELECT id, date, time, service, capacity, booked
                   FROM appointment_slots
                   WHERE CONCAT(date, ' ', time) < ?";
    
    $move_stmt = $conn->prepare($move_query);
    $move_stmt->bind_param("s", $current_datetime);
    $move_stmt->execute();
    
    // Delete moved slots from appointment_slots
    $delete_query = "DELETE FROM appointment_slots WHERE CONCAT(date, ' ', time) < ?";
    
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("s", $current_datetime);
    $delete_stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    echo "Past appointment slots have been moved to appointment_logs successfully.";
} catch (Exception $e) {
    // An error occurred, rollback the transaction
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>