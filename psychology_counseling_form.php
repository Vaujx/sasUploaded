<?php
header("Content-Security-Policy: script-src 'self' 'unsafe-inline';");

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (hasFilledForm($conn, $user_id, 'psychology_counseling')) {
    $_SESSION['error_message'] = "You have already filled out this form.";
    header('Location: student_dashboard.php');
    exit();
}


$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO psychology_counseling_form (student_id, last_name, first_name, middle_name, citizenship, email, age, sex, birthdate, ethnic_group, mobile_no, emergency_contact_name, emergency_contact_address, emergency_contact_phone, emergency_contact_relationship, student_number, campus, college, course, year, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issssssisssssssssssss", 
        $user_id, 
        $_POST['last_name'], 
        $_POST['first_name'], 
        $_POST['middle_name'], 
        $_POST['citizenship'], 
        $_POST['email'], 
        $_POST['age'], 
        $_POST['sex'], 
        $_POST['birthdate'], 
        $_POST['ethnic_group'], 
        $_POST['mobile_no'], 
        $_POST['emergency_contact_name'], 
        $_POST['emergency_contact_address'], 
        $_POST['emergency_contact_phone'], 
        $_POST['emergency_contact_relationship'], 
        $_POST['student_number'], 
        $_POST['campus'],
        $_POST['college'], 
        $_POST['course'], 
        $_POST['year'], 
        $_POST['section']
    );

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Form submitted successfully!";
        header('Location: student_dashboard.php');
        exit();
    } else {
        $error_message = "Error submitting form. Please try again.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychology Test & Counseling Session Form</title>
    <link rel="stylesheet" href="psychology_counseling_form.css">
</head>
<body>
    <div class="container">
        <h1>Psychology Test & Counseling Session Form</h1>
        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <h2>I. Personal Profile</h2>
            <div class="two-column-form">
                <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
                </div>

                <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
                </div>

                <div class="form-group">
                <label for="middle_name">Middle Name:</label>
                <input type="text" id="middle_name" name="middle_name">
                </div>

                <div class="form-group">
                <label for="citizenship">Citizenship:</label>
                <input type="text" id="citizenship" name="citizenship" required>
                </div>

                <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" required>
                </div>

                <div class="form-group">
                <label for="sex">Sex:</label>
                <select id="sex" name="sex" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                </div>

                <div class="form-group">
                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" required>
                </div>

                <div class="form-group">
                <label for="ethnic_group">Ethnic Group:</label>
                <input type="text" id="ethnic_group" name="ethnic_group">
                </div>

                <div class="form-group">
                <label for="mobile_no">Mobile No:</label>
                <input type="tel" id="mobile_no" name="mobile_no" required>
                </div>
            </div>

            <h3>Contact person in case of emergency</h3>
            <div class="two-column-form">
                <div class="form-group">
                    <label for="emergency_contact_name">Name:</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" required>
                </div>

                <div class="form-group">
                    <label for="emergency_contact_address">Address:</label>
                    <textarea id="emergency_contact_address" name="emergency_contact_address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="emergency_contact_phone">Mobile/Telephone No.:</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" required>
                </div>

                <div class="form-group">
                    <label for="emergency_contact_relationship">Relationship:</label>
                    <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                </div>
            </div>

            <h2>II. Educational Information</h2>
            <div class="two-column-form">
                <div class="form-group">  
                    <label for="student_number">Student Number:</label>
                    <input type="text" id="student_number" name="student_number" required>
                </div>

                <div class="form-group">
                    <label for="campus">Campus:</label>
                    <select id="campus" name="campus" required>
                    <option value="">Select Campus</option>
                        <option value="Castillejos">Castillejos</option>
                        <option value="San_Marcelino">San Marcelino</option>
                        <option value="Botolan">Botolan</option>
                        <option value="Iba_Main">Iba-Main</option>
                        <option value="Masinloc">Masinloc</option>
                        <option value="Candelaria">Candelaria</option>
                        <option value="Sta_Cruz">Sta.Cruz</option>
                     </select>
                </div>
                <div class="form-group">
                    <label for="college">College:</label>
                    <select id="college" name="college">
                    <option value="">Select College</option>
                    <option value="CABA">CABA</option>
                    <option value="CAS">CAS</option>
                    <option value="CCIT">CCIT</option>
                    <option value="CTE">CTE</option>
                    <option value="COE">COE</option>
                    <option value="CIT">CIT</option>
                    <option value="CAF">CAF</option>
                    <option value="CON">CON</option>
                    <option value="CTHM">CTHM</option>
                    <option value="NONE"> None </option>
                </select>
                </div>

                <div class="form-group">
                    <label for="course">Course:</label>
                    <select id="course" name="course" required>
                    <option value="">Select Item</option>
                </select>
                </div>

                <div class="form-group">
                    <label for="year">Year:</label>
                    <input type="number" id="year" name="year" required>
                </div>

                <div class="form-group">
                    <label for="section">Section:</label>
                    <input type="text" id="section" name="section" required>
                </div>
            </div>
            <div class="form-group full-width">
            <button type="submit" id="submit">Submit</button>
            </div>
        </form>
    </div>
    <script src="psychology_counseling_form.js"></script>
</body>
</html>