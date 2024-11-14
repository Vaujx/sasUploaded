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
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
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
                    $success_message = "Password changed successfully.";
                } else {
                    $error_message = "Failed to change password.";
                }
                $stmt->close();
            } else {
                $error_message = "Current password is incorrect.";
            }
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
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="staff_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2 class="sidebar-title">SAS - Staff Dashboard</h2>
            <nav class="sidebar-nav">
                <a href="#current-appointments" class="sidebar-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Current Appointments</span>
                </a>
                <a href="#appointment-history" class="sidebar-link">
                    <i class="fas fa-history"></i>
                    <span>Appointment History</span>
                </a>
                <a href="#verify-slip" class="sidebar-link">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Verify Slip</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <button id="change-password-btn" onclick="showChangePasswordForm()" class="btn btn-black">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </button>
                <a href="logout.php" class="btn btn-red">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <main class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            
            <?php if (isset($success_message)): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            
            <section id="current-appointments">
                <h2>Current Appointment Requests</h2>
                <?php if (empty($appointments)): ?>
                    <p class="no-appointments">No current appointment requests.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['SN']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="post" class="inline-form">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-approve">Approve</button>
                                                </form>
                                                <form method="post" class="inline-form">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="action" value="decline">
                                                    <button type="submit" class="btn btn-decline">Decline</button>
                                                </form>
                                            <?php else: ?>
                                                <?php echo ucfirst($appointment['status']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section id="appointment-history">
                <h2>Appointment History</h2>
                <div class="month-selector">
                    <?php
                    $months = [
                        '01' => 'January', '02' => 'February', '03' => 'March',
                        '04' => 'April', '05' => 'May', '06' => 'June',
                        '07' => 'July', '08' => 'August', '09' => 'September',
                        '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    foreach ($months as $num => $name): ?>
                        <a href="?month=<?php echo $num; ?>&year=<?php echo $selected_year; ?>" 
                           class="month-button <?php echo $selected_month === $num ? 'active' : ''; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($history_appointments)): ?>
                    <p class="no-appointments">No appointment history for <?php echo $months[$selected_month]; ?> <?php echo $selected_year; ?>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['SN']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section id="verify-slip">
                <h2>Verify Appointment Slip</h2>
                <form method="get" action="verify_slip.php" class="verify-form">
                    <input type="text" name="slip_number" id="slip_number" placeholder="Enter slip number" required>
                    <button type="submit" class="btn btn-verify">Verify</button>
                </form>
            </section>
        </main>
    </div>

    <div class="popup-overlay" id="popup-overlay"></div>
    <div id="change-password-popup" class="popup">
        <h3>Change Password</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="change_password">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
        <button onclick="closeChangePasswordForm()" class="btn btn-secondary">Cancel</button>
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