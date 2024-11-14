<?php
session_start();
require_once 'config.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Define the available services
$services = ['Counseling', 'Psychology Test', 'Entrance Exam'];

// Function to generate a random student number
function generateRandomStudentNumber() {
    $year = date('y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $year . '-1-1-' . $random;
}

// Function to send email using PHPMailer
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sas.prmsu@gmail.com'; // Replace with your actual email
        $mail->Password   = 'wrya syzj epnr hjbk'; // Replace with your actual app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('sas.prmsu@gmail.com', 'SAS Team');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Move past appointment slots to appointment_logs
$current_datetime = date('Y-m-d H:i:s');
$move_query = "INSERT INTO appointment_logs (slot_id, date, time, end_time, service, capacity, booked)
             SELECT id, date, time, end_time, service, capacity, booked
             FROM appointment_slots
             WHERE CONCAT(date, ' ', time) < ?";
$move_stmt = $conn->prepare($move_query);
$move_stmt->bind_param("s", $current_datetime);
$move_stmt->execute();
$move_stmt->close();

// Delete moved slots from appointment_slots
$delete_query = "DELETE FROM appointment_slots WHERE CONCAT(date, ' ', time) < ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("s", $current_datetime);
$delete_stmt->execute();
$delete_stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_slot':
                $date = $_POST['date'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $capacity = $_POST['capacity'];
                $service = $_POST['service'];

                $stmt = $conn->prepare("INSERT INTO appointment_slots (date, time, end_time, capacity, service, booked) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->bind_param("sssis", $date, $start_time, $end_time, $capacity, $service);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "New appointment slot created successfully. Service: " . $service;
                } else {
                    $_SESSION['error_message'] = "Failed to create appointment slot: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'edit_slot':
                $slot_id = $_POST['slot_id'];
                $date = $_POST['date'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $capacity = $_POST['capacity'];
                $service = $_POST['service'];

                $stmt = $conn->prepare("UPDATE appointment_slots SET date = ?, time = ?, end_time = ?, capacity = ?, service = ? WHERE id = ?");
                $stmt->bind_param("sssisi", $date, $start_time, $end_time, $capacity, $service, $slot_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Appointment Slot Updated Successfully. Service: " . $service;
                } else {
                    $_SESSION['error_message'] = "Failed to Update Appointment Slot: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'delete_slot':
                $slot_id = $_POST['slot_id'];

                $stmt = $conn->prepare("DELETE FROM appointment_slots WHERE id = ?");
                $stmt->bind_param("i", $slot_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Appointment Slot Deleted Successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to Delete Appointment Slot: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'approve_staff':
                $staff_id = $_POST['staff_id'];
                $student_number = generateRandomStudentNumber();
                
                // Update the staff member's information
                $stmt = $conn->prepare("UPDATE users SET approved = 1, student_number = ? WHERE id = ? AND user_type = 'staff'");
                $stmt->bind_param("si", $student_number, $staff_id);
                
                if ($stmt->execute()) {
                    // Fetch the staff member's email
                    $email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $email_stmt->bind_param("i", $staff_id);
                    $email_stmt->execute();
                    $email_result = $email_stmt->get_result();
                    $staff_email = $email_result->fetch_assoc()['email'];
                    $email_stmt->close();
                    
                    // Send email to the staff member
                    $subject = "Staff Account Approved";
                    $message = "Your staff account has been approved. Your student number is: " . $student_number;
                    
                    if (sendEmail($staff_email, $subject, $message)) {
                        $_SESSION['success_message'] = "Staff account approved and email sent successfully.";
                    } else {
                        $_SESSION['success_message'] = "Staff account approved, but failed to send email.";
                    }
                } else {
                    $_SESSION['error_message'] = "Failed to approve staff account: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'reject_staff':
                $staff_id = $_POST['staff_id'];
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND user_type = 'staff' AND approved = 0");
                $stmt->bind_param("i", $staff_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Staff account rejected and removed.";
                } else {
                    $_SESSION['error_message'] = "Failed to reject staff account: " . $conn->error;
                }
                $stmt->close();
                break;

            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if ($new_password !== $confirm_password) {
                    $_SESSION['error_message'] = "New passwords do not match.";
                } else {
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if (password_verify($current_password, $user['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Password changed successfully.";
                        } else {
                            $_SESSION['error_message'] = "Failed to change password.";
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['error_message'] = "Current password is incorrect.";
                    }
                }
                break;
        }
    }
}

// Fetch existing appointment slots (only current and future)
$stmt = $conn->prepare("SELECT *, (capacity - booked) AS available FROM appointment_slots WHERE CONCAT(date, ' ', time) >= ? ORDER BY date, time");
$stmt->bind_param("s", $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$appointment_slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending staff registrations
$stmt = $conn->prepare("SELECT * FROM users WHERE user_type = 'staff' AND approved = 0");
$stmt->execute();
$result = $stmt->get_result();
$pending_staff = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Search and History functionality
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

// Set Content Security Policy
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: var(--light-color);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2, h3 {
            margin-bottom: 20px;
        }

        .success {
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .error {
            color: var(--danger-color);
            margin-bottom: 10px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .search-history-section {
            margin-bottom: 30px;
        }

        .two-column-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-group {
            flex: 1 1 calc(50% - 10px);
            margin-bottom: 15px;
        }

        .full-width {
            flex-basis: 100%;
        }

        input[type="date"],
        input[type="time"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        .slot-available {
            background-color: #e6ffe6;
        }

        .slot-almost-full {
            background-color: #fff3cd;
        }

        .slot-full {
            background-color: #f8d7da;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        #change-password-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            z-index: 1001;
        }

        @media (max-width: 768px) {
            .two-column-form {
                flex-direction: column;
            }

            .form-group {
                flex-basis: 100%;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <h3 id="name">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h3>
        
        <?php
        if (isset($_SESSION['success_message'])) {
            echo "<p class='success'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo "<p class='error'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
            unset($_SESSION['error_message']);
        }
        ?>
        
        <button onclick="toggleSearchHistory()" id="toggleSearchHistory">
            <i class="fas fa-search"></i> Toggle Search & History
        </button>

        <div id="searchHistorySection" class="search-history-section" style="display: none;">
            <h2>Search and Appointment History</h2>
            <input type="text" id="searchInput" placeholder="Enter student number" value="<?php echo htmlspecialchars($search_student_number); ?>" oninput="updateHistory()">

            <div class="month-selector">
                <select id="monthYearSelect" onchange="updateHistory()">
                    <?php
                    $months = [
                        '01' => 'January', '02' => 'February', '03' => 'March',
                        '04' => 'April', '05' => 'May', '06' => 'June',
                        '07' => 'July', '08' => 'August', '09' => 'September',
                        '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    for ($year = date('Y'); $year >= date('Y') - 5; $year--) {
                        for ($month = 12; $month >= 1; $month--) {
                            $month_num = str_pad($month, 2, '0', STR_PAD_LEFT);
                            $selected = ($selected_month == $month_num && $selected_year == $year) ? 'selected' : '';
                            echo "<option value='$year-$month_num' $selected>{$months[$month_num]} $year</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div id="appointmentHistoryTable">
                <!-- Appointment history table will be dynamically inserted here -->
            </div>
        </div>
        
        <h2>Create New Appointment Slot</h2>
        <form method="post" class="two-column-form">
            <div class="form-group">
                <input type="hidden" name="action" value="create_slot">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-group">
                <label for="start_time">Start Time:</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>

            <div class="form-group">
                <label for="end_time">End Time:</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>

            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" id="capacity" name="capacity" min="1" required>
            </div>
            
            <div class="form-group full-width">
                <label for="service">Service:</label>
                <select id="service" name="service" required>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo htmlspecialchars($service); ?>"><?php echo htmlspecialchars($service); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group full-width">
                <button type="submit" id="submit">Create Slot</button>
            </div>
        </form>
        
        <h2>Existing Appointment Slots</h2>
        <?php if (empty($appointment_slots)): ?>
            <p>No appointment slots available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Capacity</th>
                            <th>Booked</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointment_slots as $slot): ?>
                            <?php
                            $availability_percentage = ($slot['booked'] / $slot['capacity']) * 100;
                            if ($availability_percentage < 50) {
                                $status_class = 'slot-available';
                                $status = 'Available';
                            } elseif ($availability_percentage < 90) {
                                $status_class = 'slot-almost-full';
                                $status = 'Almost Full';
                            } else {
                                $status_class = 'slot-full';
                                $status = 'Full';
                            }
                            ?>
                            <tr class="<?php echo $status_class; ?>">
                                <td><?php echo htmlspecialchars($slot['date']); ?></td>
                                <td><?php echo htmlspecialchars($slot['time']) . ' - ' . htmlspecialchars($slot['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($slot['service']); ?></td>
                                <td><?php echo htmlspecialchars($slot['capacity']); ?></td>
                                <td><?php echo htmlspecialchars($slot['booked']); ?></td>
                                <td><?php echo htmlspecialchars($slot['available']); ?></td>
                                <td><?php echo $status; ?></td>
                                <td>
                                    <button onclick="showEditForm(<?php echo $slot['id']; ?>)">Edit</button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_slot">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this slot?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="edit-form" id="edit-form-<?php echo $slot['id']; ?>" style="display: none;">
                                <td colspan="8">
                                    <form method="post">
                                        <input type="hidden" name="action" value="edit_slot">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <label>Date: <input type="date" name="date" value="<?php echo $slot['date']; ?>" required></label>
                                        <label>Start Time: <input type="time" name="start_time" value="<?php echo $slot['time']; ?>" required></label>
                                        <label>End Time: <input type="time" name="end_time" value="<?php echo $slot['end_time']; ?>" required></label>
                                        <label>Capacity: <input type="number" name="capacity" value="<?php echo $slot['capacity']; ?>" min="1" required></label>
                                        <label>Service: 
                                            <select name="service" required>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?php echo htmlspecialchars($service); ?>" <?php echo ($service == $slot['service']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($service); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <button type="submit">Save</button>
                                        <button type="button" onclick="hideEditForm(<?php echo $slot['id']; ?>)">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2>Pending Staff Registrations</h2>
        <?php if (empty($pending_staff)): ?>
            <p>No pending staff registrations.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_staff as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_staff">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit">Approve</button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_staff">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to reject this staff registration?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button onclick="showChangePasswordForm()" id="cpb">
                <i class="fas fa-key"></i> Change Password
            </button>
            <a href="logout.php">
                <button id="logout">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </button>
            </a>
        </div>
    </div>

    <div class="popup-overlay" id="popup-overlay"></div>
    <div id="change-password-popup">
        <h3>Change Password</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="change_password">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit">Change Password</button>
        </form>
        <button onclick="closeChangePasswordForm()">Cancel</button>
    </div>

    <script>
        function showEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'table-row';
        }

        function hideEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }

        function toggleSearchHistory() {
            var section = document.getElementById('searchHistorySection');
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        function updateHistory() {
            var searchInput = document.getElementById('searchInput').value;
            var monthYear = document.getElementById('monthYearSelect').value;
            var [year, month] = monthYear.split('-');

            fetch(`get_appointment_history.php?search_student_number=${searchInput}&month=${month}&year=${year}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('appointmentHistoryTable').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        }

        function showChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'block';
            document.getElementById('change-password-popup').style.display = 'block';
        }

        function closeChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'none';
            document.getElementById('change-password-popup').style.display = 'none';
        }

        // Initial load of appointment history
        updateHistory();
    </script>
</body>
</html>