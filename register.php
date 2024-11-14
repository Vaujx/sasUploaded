<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_type = $_POST['student_type'];
    
    if ($student_type == 'new') {
        header("Location: register_new_student.php");
    } elseif ($student_type == 'current') {
        header("Location: register_current_student.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="index.css">
</head>
<header>
        <div id="navbar">
            <h1 id="name"> SAS - Student Appointment System</h1>
        </div>
    </header>
<body>
<div id="login">
    <div class="container">
        <h2>Register</h2>
        <form method="post" action="">
            <p>Are you a new student or a current student?</p>
            <div class="form-group">
                <button type="submit" name="student_type" value="new">New Student</button>
                <button type="submit" name="student_type" value="current">Current Student</button>
            </div>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</div>
</body>
</html>