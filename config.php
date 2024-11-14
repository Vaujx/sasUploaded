<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Manila'); 

$host = 'localhost';
$db   = 'u300133384_sasdatabase';
$user = 'u300133384_root';
$pass = '8K&jRJdu';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Function to check if the form is filled
function hasFilledForm($conn, $student_id, $form_type) {
    $table_name = '';

    // Determine the table based on form type
    switch ($form_type) {
        case 'psychology_counseling':
            $table_name = 'psychology_counseling_form';
            break;
        case 'entrance_exam_current':
            $table_name = 'entrance_exam_current_student';
            break;
        case 'entrance_exam_transferee':
            $table_name = 'entrance_exam_transferee';
            break;
        default:
            return false;
    }

    // Prepare the SQL statement to check if the form has been filled
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table_name WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'] > 0;
}

// Function to get the appropriate form URL based on the service type
function getFormUrl($service) {
    switch ($service) {
        case 'Psychology Test':
        case 'Counseling':
            return 'psychology_counseling_form.php';
        case 'Entrance Exam':
            return null; // No specific URL, handled in the main logic
        default:
            return null;
    }
}
?>