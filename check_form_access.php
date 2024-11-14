<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$service = $_GET['service'] ?? '';

$form_type = ($service === 'Psychology Test' || $service === 'Counseling') ? 'psychology_counseling' : 'entrance_exam';

$canAccess = !hasFilledForm($conn, $user_id, $form_type);
$formUrl = getFormUrl($service);

// Return a string with the format "canAccess|formUrl"
echo ($canAccess ? 'true' : 'false') . '|' . $formUrl;  