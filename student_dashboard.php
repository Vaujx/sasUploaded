<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the tutorial_completed column exists
$stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'tutorial_completed'");
$stmt->execute();
$result = $stmt->get_result();
$column_exists = $result->num_rows > 0;
$stmt->close();

// If the column doesn't exist, add it
if (!$column_exists) {
    $stmt = $conn->prepare("ALTER TABLE users ADD COLUMN tutorial_completed BOOLEAN DEFAULT FALSE");
    $stmt->execute();
    $stmt->close();
}

// Fetch user data including tutorial status
$stmt = $conn->prepare("SELECT name, student_number, temporary_student, tutorial_completed FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$user_name = $user_data['name'];
$student_number = $user_data['student_number'];
$is_temporary = $user_data['temporary_student'];
$tutorial_completed = $user_data['tutorial_completed'] ?? false;

// Handle tutorial completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_tutorial'])) {
    $stmt = $conn->prepare("UPDATE users SET tutorial_completed = TRUE WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $tutorial_completed = true;
}

// Handle student number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student_number'])) {
    $new_student_number = $_POST['new_student_number'];
    if (preg_match('/^\d{2}-\d-\d-\d{4}$/', $new_student_number)) {
        $stmt = $conn->prepare("UPDATE users SET student_number = ?, temporary_student = 0 WHERE id = ?");
        $stmt->bind_param("si", $new_student_number, $user_id);
        if ($stmt->execute()) {
            $student_number = $new_student_number;
            $is_temporary = 0;
            $_SESSION['success_message'] = "Student number updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update student number.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid student number format. Please use the format: YY-X-X-XXXX";
    }
}

// Define the available services
$services = array('Counseling', 'Psychology Test', 'Entrance Exam');

// Get the current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get the selected service (default to 'Counseling' if not set)
$selected_service = isset($_GET['service']) ? $_GET['service'] : 'Counseling';

// Calculate the number of days in the month
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Get the first day of the month
$first_day = date('N', strtotime("$year-$month-01"));

// Fetch available appointment slots for the current month and selected service
$start_date = "$year-$month-01";
$end_date = "$year-$month-$num_days";
$current_datetime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT * FROM appointment_slots WHERE date BETWEEN ? AND ? AND service = ? AND booked < capacity AND CONCAT(date, ' ', end_time) > ? ORDER BY date, time");
$stmt->bind_param("ssss", $start_date, $end_date, $selected_service, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$available_slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch student's current appointments (end time hasn't passed)
$stmt = $conn->prepare("SELECT ar.*, aps.service, aps.time as start_time, aps.end_time 
                        FROM appointment_requests ar 
                        LEFT JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.student_id = ? AND CONCAT(ar.date, ' ', aps.end_time) > ?
                        ORDER BY ar.date, ar.time");
$stmt->bind_param("is", $user_id, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$current_appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch student's past appointments (end time has passed)
$stmt = $conn->prepare("SELECT ar.*, aps.service, aps.time as start_time, aps.end_time 
                        FROM appointment_requests ar 
                        LEFT JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.student_id = ? AND CONCAT(ar.date, ' ', aps.end_time) <= ?
                        ORDER BY ar.date DESC, ar.time DESC");
$stmt->bind_param("is", $user_id, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$past_appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unviewed notifications
$stmt = $conn->prepare("SELECT COUNT(*) as notification_count FROM appointment_requests WHERE student_id = ? AND notification_status = 'unviewed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notification_count = $result->fetch_assoc()['notification_count'];
$stmt->close();

// Check if the student has already filled out the form for the selected service
$form_filled = false;
if ($selected_service == 'Counseling' || $selected_service == 'Psychology Test') {
    $stmt = $conn->prepare("SELECT * FROM psychology_counseling_form WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form_filled = $result->num_rows > 0;
    $stmt->close();
} elseif ($selected_service == 'Entrance Exam') {
    $stmt = $conn->prepare("SELECT * FROM entrance_exam_current_student WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form_filled = $result->num_rows > 0;
    $stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
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
}

$conn->close();

// Set Content Security Policy
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="student_dashboard.css">
    <link rel="stylesheet" href="change_password_popup.css">
    <style>    
        .calendar-cover {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: none;
            z-index: 10;
        }
        .notification-badge {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        #appointments-popup,
        #history-popup,
        #slot-popup,
        #update-student-number-popup,
        #change-password-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-width: 80%;
            max-height: 80vh;
            overflow-y: auto;
            width: 500px;
        }
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .close-popup {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            font-size: 20px;
            color: #666;
        }
        .history-item,
        .appointment-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            margin-bottom: 10px;
        }
        .history-item:last-child,
        .appointment-item:last-child {
            border-bottom: none;
        }
        .calendar-day.slot-available {
            background-color: #e6ffe6;
        }
        #tutorial-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        #tutorial-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 80%;
            text-align: center;
            position: absolute;
        }
        #tutorial-next {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .highlight {
            position: relative;
            z-index: 10000;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.5);
        }
    </style>
</head>
<body>

    <div id="tutorial-overlay" style="display: none;">
        <div id="tutorial-content">
            <p id="tutorial-text"></p>
            <button id="tutorial-next">Next</button>
        </div>
    </div>

    <div class="popup-overlay" id="popup-overlay"></div>

    <div class="sidebar">
        <h2>SAS - Student Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
        <p>Student Number: <?php echo htmlspecialchars($student_number); ?></p>
        <?php if ($is_temporary): ?>
            <p><a href="#" onclick="showUpdateStudentNumber()">Update Student Number</a></p>
        <?php endif; ?>
        <ul>
            <li>
                <a href="#" onclick="showAppointments()" id="appointments-link">
                    My Appointments
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge" id="notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="#" onclick="showHistory()" id="history-link">Appointment History</a></li>
            <li><a href="#" onclick="showChangePasswordForm()">Change Password</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Appointment Calendar</h1>
        
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
        
        <form method="get" action="" id="serviceForm">
            <label for="service">Select Service:</label>&nbsp;
            <select id="service" name="service">
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service); ?>" <?php echo ($service == $selected_service) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($service); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="month" value="<?php echo $month; ?>">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
        </form>

        <div class="calendar-navigation">
            <a href="?service=<?php echo urlencode($selected_service); ?>&month=<?php echo $month - 1; ?>&year=<?php echo $year; ?>"><button id="prevmonth">&lt; Previous Month</button></a>
            <span class="month"><?php echo date('F Y', strtotime("$year-$month-01")); ?></span>
            <a href="?service=<?php echo urlencode($selected_service); ?>&month=<?php echo $month + 1; ?>&year=<?php echo $year; ?>"><button id="nextmonth">Next Month &gt;</button></a>
        </div>
        <div class="calendar" id="calendar">
            <?php
            $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
            foreach ($days as $day) {
                echo "<div class='calendar-header'>$day</div>";
            }
            
            for ($i = 1; $i < $first_day; $i++) {
                echo "<div class='calendar-day other-month'></div>";
            }
            
            for ($day = 1; $day <= $num_days; $day++) {
                $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                $day_slots = array_filter($available_slots, function($slot) use ($date) {
                    return $slot['date'] == $date;
                });
                
                $slot_class = !empty($day_slots) ? 'slot-available' : '';
                
                echo "<div class='calendar-day $slot_class' data-date='$date'>";
                echo "<div class='day-number'>$day</div>";
                echo "</div>";
            }
            
            $remaining_days = 7 - (($num_days + $first_day - 1) % 7);
            if ($remaining_days < 7) {
                for ($i = 0; $i < $remaining_days; $i++) {
                    echo "<div class='calendar-day other-month'></div>";
                }
            }
            ?>
        </div>

        <div id="slot-popup">
            <span class="close-popup" onclick="closePopup('slot-popup')">&times;</span>
            <h3>Available Slots</h3>
            <div id="slot-list"></div>
        </div>

        <div id="appointments-popup">
            <span class="close-popup" onclick="closePopup('appointments-popup')">&times;</span>
            <h3>Your Appointments</h3>
            <div id="appointments-list"></div>
        </div>

        <div id="history-popup">
            <span class="close-popup" onclick="closePopup('history-popup')">&times;</span>
            <h3>Appointment History</h3>
            <div id="history-list"></div>
        </div>

        <div id="update-student-number-popup">
            <span class="close-popup" onclick="closePopup('update-student-number-popup')">&times;</span>
            <h3>Update Student Number</h3>
            <form method="post" action="">
                <input type="hidden" name="update_student_number" value="1">
                <label for="new_student_number">New Student Number:</label>
                <input type="text" id="new_student_number" name="new_student_number" required pattern="\d{2}-\d-\d-\d{4}" title="Please use the format: YY-X-X-XXXX">
                <button type="submit">Update</button>
            </form>
        </div>

        <div id="change-password-popup">
            <span class="close-popup" onclick="closePopup('change-password-popup')">&times;</span>
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
        </div>
    </div>

    <script>
        const availableSlots = <?php echo json_encode($available_slots); ?>;
        const currentAppointments = <?php echo json_encode($current_appointments); ?>;
        const pastAppointments = <?php echo json_encode($past_appointments); ?>;
        const formFilled = <?php echo json_encode($form_filled); ?>;
        const selectedService = '<?php echo $selected_service; ?>';
        let tutorialCompleted = <?php echo $tutorial_completed ? 'true' : 'false'; ?>;

        const tutorialSteps = [
            {
                target: '#service',
                content: 'Welcome to your Student Dashboard! This is where you can manage your appointments and services. Let\'s start by selecting a service.',
            },
            {
                target: '#calendar',
                content: 'This calendar shows available appointment slots. Green dates indicate available slots.',
            },
            {
                target: '#appointments-link',
                content: 'Here you can view your current appointments and their status.',
            },
            {
                target: '#history-link',
                content: 'Check your appointment history here.',
            },
            {
                target: '#service',
                content: 'Now, let\'s select either Counseling or Psychology Test. You\'ll need to fill out a form for these services.',
            },
            {
                target: '#service',
                content: 'After that, select Entrance Exam and fill out the required form.',
            },
            {
                target: '#calendar',
                content: 'Once you\'ve filled out the forms, you can select available slots on the calendar to book your appointments.',
            },
        ];

        let currentTutorialStep = 0;

        function showTutorial() {
            if (currentTutorialStep >= tutorialSteps.length) {
                document.getElementById('tutorial-overlay').style.display = 'none';
                completeTutorial();
                return;
            }

            const step = tutorialSteps[currentTutorialStep];
            const targetElement = document.querySelector(step.target);
            const tutorialOverlay = document.getElementById('tutorial-overlay');
            const tutorialContent = document.getElementById('tutorial-content');
            const tutorialText = document.getElementById('tutorial-text');

            tutorialText.textContent = step.content;
            tutorialOverlay.style.display = 'flex';

            if (targetElement) {
                const rect = targetElement.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;

                let top = rect.bottom + 10;
                let left = rect.left;

                // Adjust vertical position if it's outside the viewport
                if (top + tutorialContent.offsetHeight > viewportHeight) {
                    top = rect.top - tutorialContent.offsetHeight - 10;
                }

                // Adjust horizontal position if it's outside the viewport
                if (left + tutorialContent.offsetWidth > viewportWidth) {
                    left = viewportWidth - tutorialContent.offsetWidth - 10;
                }

                tutorialContent.style.top = `${top}px`;
                tutorialContent.style.left = `${left}px`;
                targetElement.classList.add('highlight');
            } else {
                tutorialContent.style.top = '50%';
                tutorialContent.style.left = '50%';
                tutorialContent.style.transform = 'translate(-50%, -50%)';
            }
        }

        function nextTutorialStep() {
            const currentTarget = document.querySelector(tutorialSteps[currentTutorialStep].target);
            if (currentTarget) {
                currentTarget.classList.remove('highlight');
            }
            currentTutorialStep++;
            showTutorial();
        }

        function completeTutorial() {
            tutorialCompleted = true;
            fetch('student_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'complete_tutorial=1'
            });
        }

        document.getElementById('tutorial-next').addEventListener('click', nextTutorialStep);

        document.addEventListener('DOMContentLoaded', function() {
            const calendarDays = document.querySelectorAll('.calendar-day:not(.other-month)');
            const slotPopup = document.getElementById('slot-popup');
            const slotList = document.getElementById('slot-list');
            const serviceSelect = document.getElementById('service');

            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    const date = this.dataset.date;
                    showAvailableSlots(date);
                });
            });

            serviceSelect.addEventListener('change', function() {
                checkFormAccess(this.value);
            });

            if (!tutorialCompleted) {
                showTutorial();
            }
        });

        function showPopup(popupId) {
            document.getElementById('popup-overlay').style.display = 'block';
            document.getElementById(popupId).style.display = 'block';
        }

        function closePopup(popupId) {
            document.getElementById('popup-overlay').style.display = 'none';
            document.getElementById(popupId).style.display = 'none';
        }

        function showAvailableSlots(date) {
            const now = new Date();
            const daySlots = availableSlots.filter(slot => {
                const slotDateTime = new Date(slot.date + ' ' + slot.end_time);
                return slot.date === date && slotDateTime > now;
            });

            if (daySlots.length === 0) {
                alert('No available slots for this day.');
                return;
            }

            const slotList = document.getElementById('slot-list');
            slotList.innerHTML = '';
            daySlots.forEach(slot => {
                const slotElement = document.createElement('div');
                slotElement.innerHTML = `
                    <p>${slot.time} - ${slot.end_time}: ${slot.service}</p>
                    <p>Available: ${slot.capacity - slot.booked}/${slot.capacity}</p>
                    <form method="post" action="request_appointment.php">
                        <input type="hidden" name="slot_id" value="${slot.id}">
                        <button type="submit" id="submit">Request</button>
                    </form>
                `;
                slotList.appendChild(slotElement);
            });

            showPopup('slot-popup');
        }

        function showAppointments() {
            const appointmentsList = document.getElementById('appointments-list');
            appointmentsList.innerHTML = '';
            
            if (currentAppointments.length === 0) {
                appointmentsList.innerHTML = '<p>You have no upcoming appointments.</p>';
            } else {
                currentAppointments.forEach(appointment => {
                    const appointmentElement = document.createElement('div');
                    appointmentElement.className = 'appointment-item';
                    appointmentElement.innerHTML = `
                        <p>Date: ${appointment.date}</p>
                        <p>Time: ${appointment.start_time} - ${appointment.end_time}</p>
                        <p>Service: ${appointment.service}</p>
                        <p>Status: ${appointment.status}</p>
                        ${appointment.status === 'approved' ? `
                            <p>Slip Number: ${appointment.slip_number}</p>
                            <a href="print_slip.php?id=${appointment.id}" target="_blank"><button class="print">Print Slip</button></a>
                        ` : ''}
                        ${appointment.status === 'declined' ? `
                            <form method="post" action="student_dashboard.php">
                                <input type="hidden" name="appointment_id" value="${appointment.id}">
                                <button type="submit">Reselect Appointment</button>
                            </form>
                        ` : ''}
                    `;
                    appointmentsList.appendChild(appointmentElement);
                });
            }

            showPopup('appointments-popup');

            // Reset notification count
            fetch('reset_notification_count.php')
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        const badge = document.getElementById('notification-badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showHistory() {
            const historyList = document.getElementById('history-list');
            historyList.innerHTML = '';
            
            if (pastAppointments.length === 0) {
                historyList.innerHTML = '<p>You have no past appointments.</p>';
            } else {
                pastAppointments.forEach(appointment => {
                    const historyElement = document.createElement('div');
                    historyElement.className = 'history-item';
                    historyElement.innerHTML = `
                        <p>Date: ${appointment.date}</p>
                        <p>Time: ${appointment.start_time} - ${appointment.end_time}</p>
                        <p>Service: ${appointment.service}</p>
                        <p>Status: ${appointment.status}</p>
                        ${appointment.slip_number ? `<p>Slip Number: ${appointment.slip_number}</p>` : ''}
                    `;
                    historyList.appendChild(historyElement);
                });
            }

            showPopup('history-popup');
        }

        function checkFormAccess(service) {
            if (formFilled) {
                document.getElementById('serviceForm').submit();
            } else {
                if (service === 'Entrance Exam') {
                    const studentType = prompt('Are you a current student or a transferee?', 'current');
                    if (studentType) {
                        if (studentType.toLowerCase() === 'current') {
                            window.location.href = 'entrance_exam_current_student.php';
                        } else if (studentType.toLowerCase() === 'transferee') {
                            window.location.href = 'entrance_exam_transferee.php';
                        } else {
                            alert('Invalid student type. Please try again.');
                            return;
                        }
                    }
                } else {
                    window.location.href = 'psychology_counseling_form.php';
                }
            }
        }

        function showUpdateStudentNumber() {
            showPopup('update-student-number-popup');
        }

        function showChangePasswordForm() {
            showPopup('change-password-popup');
        }

        window.addEventListener('resize', function() {
            if (!tutorialCompleted) {
                showTutorial();
            }
        });
    </script>
</body>
</html>