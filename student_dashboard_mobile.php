<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student name and student number
$stmt = $conn->prepare("SELECT name, student_number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_name = $user_data['name'];
$student_number = $user_data['student_number'];
$stmt->close();

// Get current datetime for upcoming appointments query
$current_datetime = date('Y-m-d H:i:s');

// Fetch upcoming appointments (end time hasn't passed)
$stmt = $conn->prepare("SELECT ar.*, aps.service, aps.time AS start_time, aps.end_time 
                        FROM appointment_requests ar 
                        LEFT JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.student_id = ? AND CONCAT(ar.date, ' ', aps.end_time) > ?
                        ORDER BY ar.date, ar.time");
$stmt->bind_param("is", $user_id, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch appointment history (end time has passed)
$stmt = $conn->prepare("SELECT ar.*, aps.service, aps.time AS start_time, aps.end_time 
                        FROM appointment_requests ar 
                        LEFT JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.student_id = ? AND CONCAT(ar.date, ' ', aps.end_time) <= ?
                        ORDER BY ar.date DESC, ar.time DESC");
$stmt->bind_param("is", $user_id, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
$appointment_history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Available services
$available_services = ['Counseling', 'Psychology Test', 'Entrance Exam'];

// Fetch available appointment slots
$stmt = $conn->prepare("SELECT al.id, al.date, al.time, al.service 
                        FROM appointment_slots al 
                        LEFT JOIN appointment_logs ar ON al.id = ar.slot_id 
                        WHERE al.date >= CURDATE() AND ar.id IS NULL 
                        ORDER BY al.date ASC, al.time ASC");
$stmt->execute();
$result = $stmt->get_result();
$available_slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Process appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $selected_slot_id = $_POST['selected_slot_id'];
    // Here you would add the logic to insert the new appointment into the database
    // For now, we'll just set a message
    $booking_message = "Appointment booking request submitted for slot ID: $selected_slot_id";
}

// Notification function
function getNotifications($user_id, $upcoming_appointments) {
    $notifications = [];
    // Add a welcome notification
    $notifications[] = [
        'message' => 'Welcome to the Student Appointment System!',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add a notification about upcoming appointments
    if (!empty($upcoming_appointments)) {
        $next_appointment = $upcoming_appointments[0];
        $notifications[] = [
            'message' => "You have an upcoming appointment on {$next_appointment['date']} at {$next_appointment['time']} for {$next_appointment['service']}.",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        $notifications[] = [
            'message' => "You have no upcoming appointments. Book one now!",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    // Add a random tip
    $tips = [
        "Don't forget to bring your student ID to your appointments.",
        "You can reschedule an appointment up to 24 hours before the scheduled time.",
        "Check your email for important updates about your appointments.",
        "Make sure to arrive 5 minutes early for your appointment."
    ];
    $random_tip = $tips[array_rand($tips)];
    $notifications[] = [
        'message' => "Tip: $random_tip",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    return $notifications;
}

// Get notifications
$notifications = getNotifications($user_id, $upcoming_appointments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
            padding-bottom: 60px;
        }
        .container {
            padding: 20px;
        }
        h1, h2 {
            color: #000080;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .appointment {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .appointment:last-child {
            border-bottom: none;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #000080;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .navbar {
            background-color: #000080;
            color: #fff;
            padding: 10px 20px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-around;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
        }
        #calendar {
            width: 100%;
            border-collapse: collapse;
        }
        #calendar th, #calendar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        #calendar .has-slots {
            background-color: #e6f3ff;
            cursor: pointer;
        }
        .time-slots {
            display: none;
            margin-top: 10px;
        }
        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 5px 10px;
            background-color: #e6f3ff;
            border-radius: 3px;
            cursor: pointer;
        }
        #service-select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .notification {
            background-color: #e6f3ff;
            border-left: 4px solid #000080;
            padding: 10px;
            margin-bottom: 10px;
        }
        .tutorial-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .tutorial-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 80%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p>Student Number: <?php echo htmlspecialchars($student_number); ?></p>

        <?php if (isset($booking_message)): ?>
            <div class="card">
                <p><?php echo htmlspecialchars($booking_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="card" id="notifications">
            <h2>Notifications</h2>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification">
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small><?php echo htmlspecialchars($notification['timestamp']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card" id="book-appointment">
            <h2>Book an Appointment</h2>
            <select id="service-select">
                <option value="">Select a service</option>
                <?php foreach ($available_services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service); ?>">
                        <?php echo htmlspecialchars($service); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="calendar"></div>
            <div id="time-slots" class="time-slots"></div>
            <form id="booking-form" method="post" style="display:none;">
                <input type="hidden" name="selected_slot_id" id="selected_slot_id">
                <button type="submit" name="book_appointment" class="btn">Book Appointment</button>
            </form>
        </div>

        <div class="card" id="appointments">
            <h2>Upcoming Appointments</h2>
            <?php if (empty($upcoming_appointments)): ?>
                <p>No upcoming appointments.</p>
            <?php else: ?>
                <?php foreach ($upcoming_appointments as $appointment): ?>
                    <div class="appointment">
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
                        <?php if ($appointment['status'] === 'approved'): ?>
                            <button onclick="printSlip(<?php echo $appointment['id']; ?>)" class="btn">Print Slip</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card" id="history">
            <h2>Appointment History</h2>
            <?php if (empty($appointment_history)): ?>
                <p>No appointment history.</p>
            <?php else: ?>
                <?php foreach ($appointment_history as $appointment): ?>
                    <div class="appointment">
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <nav class="navbar">
        <a href="#book-appointment"><i class="fas fa-calendar-plus"></i></a>
        <a href="#appointments"><i class="fas fa-calendar-check"></i></a>
        <a href="#history"><i class="fas fa-history"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </nav>

    <div id="tutorial-overlay" class="tutorial-overlay">
        <div class="tutorial-content">
            <h2>Welcome to the Student Dashboard!</h2>
            <p>Here's a quick guide to help you get started:</p>
            <ol>
                <li>Use the "Book an Appointment" section to schedule new appointments.</li>
                <li>View your upcoming appointments in the "Upcoming Appointments" section.</li>
                <li>Check your past appointments in the "Appointment History" section.</li>
                <li>Use the navigation bar at the bottom to quickly access different sections.</li>
            </ol>
            <button id="close-tutorial" class="btn">Got it!</button>
        </div>
    </div>

    <script>
        const availableSlots = <?php echo json_encode($available_slots); ?>;
        let selectedService = '';

        document.getElementById('service-select').addEventListener('change', function() {
            selectedService = this.value;
            generateCalendar(new Date().getFullYear(), new Date().getMonth());
        });

        function generateCalendar(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDay = firstDay.getDay();

            let calendarHTML = '<table id="calendar"><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr><tr>';

            for (let i = 0; i < startingDay; i++) {
                calendarHTML += '<td></td>';
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const hasSlots = availableSlots.some(slot => slot.date === currentDate && (selectedService === '' || slot.service === selectedService));
                const cellClass = hasSlots ? 'has-slots' : '';
                calendarHTML += `<td class="${cellClass}" data-date="${currentDate}">${day}</td>`;

                if ((startingDay + day) % 7 === 0) {
                    calendarHTML += '</tr><tr>';
                }
            }

            calendarHTML += '</tr></table>';
            document.getElementById('calendar').innerHTML = calendarHTML;

            document.querySelectorAll('.has-slots').forEach(cell => {
                cell.addEventListener('click', function() {
                const selectedDate = this.getAttribute('data-date');
                    showTimeSlots(selectedDate);
                });
            });
        }

        function showTimeSlots(date) {
            const slots = availableSlots.filter(slot => slot.date === date && (selectedService === '' || slot.service === selectedService));
            if (slots.length > 0) {
                let timeSlotsHTML = '<h3>Available Time Slots for ' + date + '</h3>';
                slots.forEach(slot => {
                    timeSlotsHTML += `<div class="time-slot" data-slot-id="${slot.id}">${slot.time} - ${slot.service}</div>`;
                });
                document.getElementById('time-slots').innerHTML = timeSlotsHTML;
                document.getElementById('time-slots').style.display = 'block';

                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', function() {
                        const selectedSlotId = this.getAttribute('data-slot-id');
                        document.getElementById('selected_slot_id').value = selectedSlotId;
                        document.getElementById('booking-form').style.display = 'block';
                    });
                });
            }
        }

        // Generate calendar for the current month
        const currentDate = new Date();
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());

        function printSlip(appointmentId) {
            // Implement the print slip functionality
            // This could open a new window with the slip details or redirect to a print page
            window.open(`print_slip.php?id=${appointmentId}`, '_blank');
        }

        // Tutorial functionality
        document.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('tutorialShown')) {
                document.getElementById('tutorial-overlay').style.display = 'flex';
            }

            document.getElementById('close-tutorial').addEventListener('click', function() {
                document.getElementById('tutorial-overlay').style.display = 'none';
                localStorage.setItem('tutorialShown', 'true');
            });
        });
    </script>
</body>
</html>