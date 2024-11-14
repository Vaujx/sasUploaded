<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Generate temporary student number
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE temp_student_sequence SET last_number = last_number + 1");
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("SELECT last_number FROM temp_student_sequence");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $temp_number = $row['last_number'];
            $stmt->close();

            $year = date("y");
            $temp_student_number = sprintf("%02d-0-0-%04d", $year, $temp_number);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, student_number, user_type, temporary_student) VALUES (?, ?, ?, ?, 'student', TRUE)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $temp_student_number);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $success = "Registration successful! Your temporary student number is: " . $temp_student_number;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed. Please try again.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Student</title>
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
        <h2>Register New Student</h2>
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