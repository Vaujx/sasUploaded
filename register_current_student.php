<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $student_number = $_POST['student_number'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!preg_match('/^\d{2}-\d-\d-\d{4}$/', $student_number)) {
        $error = "Invalid student number format. Please use the format: YY-X-X-XXXX";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "This student number is already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, student_number, user_type) VALUES (?, ?, ?, ?, 'student')");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $student_number);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login with your student number.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Current Student</title>
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
        <h2>Register Current Student</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php else: ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="student_number">Student Number:</label>
                    <input type="text" id="student_number" name="student_number" required pattern="\d{2}-\d-\d-\d{4}" title="Please use the format: YY-X-X-XXXX">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Register</button>
            </form>
        <?php endif; ?>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</div>
</body>
</html>