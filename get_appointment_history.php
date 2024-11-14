<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    exit('Unauthorized');
}

$search_student_number = isset($_GET['search_student_number']) ? $_GET['search_student_number'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$history_query = "SELECT ar.*, u.name as student_name, u.student_number, aps.service, 
                         s1.name as verifier_name, s2.name as approver_name
                  FROM appointment_requests ar
                  JOIN users u ON ar.student_id = u.id
                  JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                  LEFT JOIN users s1 ON ar.verified_by = s1.id
                  LEFT JOIN users s2 ON ar.approved_by = s2.id
                  WHERE 1=1";

$params = array();
$types = '';

if (!empty($search_student_number)) {
    $history_query .= " AND u.student_number LIKE ?";
    $params[] = "%$search_student_number%";
    $types .= 's';
}

$history_query .= " AND MONTH(ar.date) = ? AND YEAR(ar.date) = ?";
$params[] = $selected_month;
$params[] = $selected_year;
$types .= 'ii';

$history_query .= " ORDER BY ar.date DESC, ar.time DESC";

$stmt = $conn->prepare($history_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$appointment_history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

if (empty($appointment_history)) {
    echo "<p>No appointments found for the selected criteria.</p>";
} else {
    echo "<table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Verified By</th>
                    <th>Approved By</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($appointment_history as $appointment) {
        echo "<tr>
                <td>" . htmlspecialchars($appointment['student_number']) . "</td>
                <td>" . htmlspecialchars($appointment['student_name']) . "</td>
                <td>" . htmlspecialchars($appointment['date']) . "</td>
                <td>" . htmlspecialchars($appointment['time']) . "</td>
                <td>" . htmlspecialchars($appointment['service']) . "</td>
                <td>" . htmlspecialchars($appointment['status']) . "</td>
                <td>" . htmlspecialchars($appointment['verifier_name'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($appointment['approver_name'] ?? 'N/A') . "</td>
              </tr>";
    }
    
    echo "</tbody></table>";
}