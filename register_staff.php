<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, approved) VALUES (?, ?, ?, 'staff', 0)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = "Staff registration submitted successfully. Please wait for admin approval.";
        } else {
            $error = "Error registering staff account: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div id="navbar">
            <h1 id="name">SAS - Student Appointment System</h1>
        </div>
    </header>
    <div id="login">
        <div class="container">
            <h2>Staff Registration</h2>
            <?php
            if (isset($error)) {
                echo "<p  class='error'>$error</p>";
            }
            if (isset($success)) {
                echo "<p class='success'>$success</p>";
            }
            ?>
            <form action="" method="post">
                <div class="user">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="user">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="pass">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="pass">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div>
                    <input type="submit" class="submit" value="Register">
                </div>
            </form>
            <p>Already have an account? <a href="index.php?type=staff">Login here</a></p>
        </div>
    </div>
</body>
</html>