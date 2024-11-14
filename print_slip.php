<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    die('No appointment ID specified');
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT ar.*, u.name as student_name, u.student_number, aps.service, aps.time as start_time, aps.end_time
                        FROM appointment_requests ar
                        JOIN users u ON ar.student_id = u.id
                        JOIN appointment_slots aps ON ar.date = aps.date AND ar.time = aps.time
                        WHERE ar.id = ? AND ar.student_id = ? AND ar.status = 'approved'");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Appointment not found or not approved');
}

$appointment = $result->fetch_assoc();

$conn->close();

// Set Content Security Policy to allow inline styles
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self';");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Slip</title>
    <style>
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .appointment-slip {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background-color: white;
            padding: 10mm;
            box-sizing: border-box;
        }
        .slip-content {
            border: 2px solid #000080;
            padding: 10mm;
            position: relative;
            height: calc(100% - 20mm);
        }
        .slip-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .slip-background::before,
        .slip-background::after {
            content: '';
            position: absolute;
            border: 4px solid #000080;
            top: 5mm;
            left: 5mm;
            right: 5mm;
            bottom: 5mm;
            opacity: 0.1;
        }
        .slip-background::after {
            border: 2px solid #FFD700;
        }
        .slip-header {
            text-align: center;
            margin-bottom: 10mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slip-logo {
            width: 50px;
            height: auto;
            margin-right: 10mm;
        }
        .slip-title {
            font-size: 24px;
            font-weight: bold;
            color: #000080;
        }
        .slip-subtitle {
            font-size: 18px;
            font-weight: 600;
            color: #FFD700;
        }
        .slip-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            margin-bottom: 10mm;
        }
        .slip-info-item {
            margin-bottom: 2mm;
        }
        .slip-info-label {
            font-size: 10px;
            font-weight: 500;
            color: #666;
        }
        .slip-info-value {
            font-size: 14px;
            font-weight: bold;
            color: #000080;
        }
        .slip-footer {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            margin-top: 10mm;
        }
        .slip-signature {
            text-align: right;
        }
        .slip-signature-label {
            font-size: 10px;
            font-weight: 500;
            color: #666;
        }
        .slip-signature-line {
            width: 120px;
            height: 1px;
            background-color: #000;
            margin-top: 15mm;
        }
        .slip-notes {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 5mm;
        }
        .slip-cut-line {
            border-top: 1px dashed #ccc;
            margin: 10mm 0 5mm;
            position: relative;
        }
        .slip-cut-label {
            position: absolute;
            top: 0;
            left: 0;
            transform: translateY(-50%);
            background-color: white;
            padding: 0 2mm;
            font-size: 10px;
            color: #666;
        }
        .student-copy {
            background-color: #f8f8f8;
            padding: 5mm;
        }
        .student-copy-title {
            font-size: 14px;
            font-weight: 600;
            color: #000080;
            text-align: center;
            margin-bottom: 3mm;
        }
        .student-copy-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="appointment-slip">
        <div class="slip-content">
            <div class="slip-background"></div>
            <div class="slip-header">
                <img src="prmsu.jpeg" alt="PRMSU Logo" class="slip-logo">
                <div>
                    <h1 class="slip-title">SAS - Student Appointment System</h1>
                    <h2 class="slip-subtitle">Appointment Slip</h2>
                </div>
            </div>
            <div class="slip-info">
                <div class="slip-info-item">
                    <p class="slip-info-label">Slip Number:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['slip_number']); ?></p>
                </div>
                <div class="slip-info-item">
                    <p class="slip-info-label">Student Number:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['student_number']); ?></p>
                </div>
                <div class="slip-info-item">
                    <p class="slip-info-label">Student Name:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['student_name']); ?></p>
                </div>
                <div class="slip-info-item">
                    <p class="slip-info-label">Date:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['date']); ?></p>
                </div>
                <div class="slip-info-item">
                    <p class="slip-info-label">Time:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['start_time']) . ' - ' . htmlspecialchars($appointment['end_time']); ?></p>
                </div>
                <div class="slip-info-item">
                    <p class="slip-info-label">Service:</p>
                    <p class="slip-info-value"><?php echo htmlspecialchars($appointment['service']); ?></p>
                </div>
            </div>
            <div class="slip-footer">
                <div class="slip-signature">
                    <p class="slip-signature-label">Verified by:</p>
                    <div class="slip-signature-line"></div>
                </div>
            </div>
            <div class="slip-notes">
                <p>Please present this slip on the day of your appointment.</p>
                <p>This slip is valid only for the date and time specified.</p>
            </div>
            <div class="slip-cut-line">
                <span class="slip-cut-label">✂️ Cut along this line</span>
            </div>
            <div class="student-copy">
                <h3 class="student-copy-title">Student Copy</h3>
                <div class="student-copy-info">
                    <div>
                        <p class="slip-info-label">Slip Number:</p>
                        <p class="slip-info-value"><?php echo htmlspecialchars($appointment['slip_number']); ?></p>
                    </div>
                    <div>
                        <p class="slip-info-label">Date & Time:</p>
                        <p class="slip-info-value"><?php echo htmlspecialchars($appointment['date']) . ' ' . htmlspecialchars($appointment['start_time']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="no-print" style="text-align: center; margin-top: 10mm;">
        <button onclick="window.print()">Print Slip</button>
    </div>
</body>
</html>