<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (hasFilledForm($conn, $user_id, 'entrance_exam')) {
    $_SESSION['error_message'] = "You have already filled out this form.";
    header('Location: student_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO entrance_exam_transferee (student_id, last_name, first_name, middle_name, citizenship, email, age, sex, birthdate, ethnic_group, mobile_no, emergency_contact_name, emergency_contact_address, emergency_contact_phone, emergency_contact_relationship, school, course, junior_hs_completion_year, senior_hs_completion_year, category_of_applicant, first_program, second_program, third_program, campus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issssssisssssssssiiissss", 
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
        $_POST['school'], 
        $_POST['course'], 
        $_POST['junior_hs_completion_year'], 
        $_POST['senior_hs_completion_year'], 
        $_POST['category_of_applicant'], 
        $_POST['first_program'], 
        $_POST['second_program'], 
        $_POST['third_program'], 
        $_POST['campus']
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
    <title>Entrance Exam Form (Transferee)</title>
    <link rel="stylesheet" href="entrance_exam_transferee.css">
</head>
<body>
    <div class="container">
        <h1>Entrance Exam Form (Transferee)</h1>
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
                    <label for="school">School:</label>
                    <input type="text" id="school" name="school" required>
                </div>

                <div class="form-group">
                    <label for="course">Course:</label>
                    <input type="text" id="course" name="course" required>
                </div>

                <div class="form-group">
                    <label for="junior_hs_completion_year">Junior HS Completion Year:</label>
                    <input type="number" id="junior_hs_completion_year" name="junior_hs_completion_year" required>
                </div>

                <div class="form-group">
                    <label for="senior_hs_completion_year">Senior HS Completion Year:</label>
                    <input type="number" id="senior_hs_completion_year" name="senior_hs_completion_year" required>
                </div>

                <div class="form-group">
                    <label for="category_of_applicant">Category of Applicant:</label>
                    <select id="category_of_applicant" name="category_of_applicant" required>
                        <option value="Regular Admission">Regular Admission</option>
                        <option value="Alternative Learning System">Alternative Learning System</option>
                    </select>
                </div>
            </div>
                
            <h2>III. Choose and Rank Three(3) Programs Based on Your Interest</h2>
            <div class="two-column-form">
                <div class="form-group">
                    <label for="first_program">First Program:</label>
                    <input type="text" id="first_program" name="first_program" required>
                </div>

                <div class="form-group">
                    <label for="second_program">Second Program:</label>
                    <input type="text" id="second_program" name="second_program" required>
                </div>

                <div class="form-group">
                    <label for="third_program">Third Program:</label>
                    <input type="text" id="third_program" name="third_program" required>
                </div>

                <div class="form-group">
                    <label for="campus">Campus:</label>
                    <select id="campus" name="campus" required>
                        <option value="Castillejos">Castillejos</option>
                        <option value="San Marcelino">San Marcelino</option>
                        <option value="Botolan">Botolan</option>
                        <option value="Iba-Main">Iba-Main</option>
                        <option value="Masinloc">Masinloc</option>
                        <option value="Candelaria">Candelaria</option>
                        <option value="Sta.Cruz">Sta.Cruz</option>
                    </select>
                </div>
            </div>
            <div class="form-group full-width">
                <button type="submit" id="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>