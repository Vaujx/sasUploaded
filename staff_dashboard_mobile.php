<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch staff name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_name = $user_data['name'];
$stmt->close();

// Get selected month for history (default to current month)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
        $appointment_id = $_POST['appointment_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'decline') {
            $status = ($action === 'approve') ? 'approved' : 'declined';
            $stmt = $conn->prepare("UPDATE appointment_requests SET status = ?, notification_status = 'unviewed', approved_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $status, $user_id, $appointment_id);
            if ($stmt->execute()) {
                $success_message = "Appointment " . $status . " Successfully.";
                
                // Generate slip number if approved
                if ($status === 'approved') {
                    $slip_number = generateSlipNumber();
                    $stmt = $conn->prepare("UPDATE appointment_requests SET slip_number = ? WHERE id = ?");
                    $stmt->bind_param("si", $slip_number, $appointment_id);
                    $stmt->execute();
                }
            } else {
                $error_message = "Failed to Update Appointment Status: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch current appointment requests
$current_date = date('Y-m-d');
$stmt = $conn->prepare("SELECT ar.*, u.student_number as SN, aps.service
                        FROM appointment_requests ar 
                        JOIN users u ON ar.student_id = u.id 
                        JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.date >= ? 
                        ORDER BY ar.date, ar.time");
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch appointment history for selected month
$start_date = "$selected_year-$selected_month-01";
$end_date = date('Y-m-t', strtotime($start_date));
$stmt = $conn->prepare("SELECT ar.*, u.student_number as SN, aps.service
                        FROM appointment_requests ar 
                        JOIN users u ON ar.student_id = u.id 
                        JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.date BETWEEN ? AND ?
                        ORDER BY ar.date DESC, ar.time DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$history_appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Function to generate a unique slip number
function generateSlipNumber() {
    return 'SLIP-' . strtoupper(substr(uniqid(), -6));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
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
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 15px;
        }
        .month-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .month-button {
            padding: 5px 10px;
            background-color: #000080;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#home"><i class="fas fa-home"></i></a>
        <a href="#appointments"><i class="fas fa-calendar"></i></a>
        <a href="#history"><i class="fas fa-history"></i></a>
        <a href="#verify"><i class="fas fa-check-circle"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>

        <?php if (isset($success_message)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <div class="card" id="appointments">
            <h2>Current Appointment Requests</h2>
            <?php if (empty($appointments)): ?>
                <p>No current appointment requests.</p>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment">
                        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($appointment['SN']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
                        <?php if ($appointment['status'] === 'pending'): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn" style="background-color: #4CAF50;">Approve</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="btn" style="background-color: #f44336;">Decline</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card" id="history">
            <h2>Appointment History</h2>
            <div class="month-selector">
                <?php
                $months = [
                    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
                    '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                ];
                foreach ($months as $num => $name): ?>
                    <a href="?month=<?php echo $num; ?>&year=<?php echo $selected_year; ?>" 
                       class="month-button <?php echo $selected_month === $num ? 'active' : ''; ?>">
                        <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($history_appointments)): ?>
                <p>No appointment history for <?php echo $months[$selected_month]; ?> <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <?php foreach ($history_appointments as $appointment): ?>
                    <div class="appointment">
                        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($appointment['SN']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($appointment['time']); ?></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card" id="verify">
            <h2>Verify Appointment Slip</h2>
            <form method="get" action="verify_slip.php">
                <input type="text" name="slip_number" placeholder="Enter slip number" required>
                <button type="submit" class="btn">Verify</button>
            </form>
        </div>
    </div>

    <script>
       function showChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'block';
            document.getElementById('change-password-popup').style.display = 'block';
        }

        function closeChangePasswordForm() {
            document.getElementById('popup-overlay').style.display = 'none';
            document.getElementById('change-password-popup').style.display = 'none';
        }
    </script>
</body>
</html>