<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self';");
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php'; // Make sure to install TCPDF using Composer

use TCPDF;

// Check if the user is logged in as a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: index.php');
    exit();
}

$staff_id = $_SESSION['user_id'];

// Fetch logs
$sql = "SELECT al.*, ar.date, ar.time, u.username as student_name 
        FROM appointment_logs al
        JOIN appointment_requests ar ON al.appointment_id = ar.id
        JOIN users u ON ar.student_id = u.id
        WHERE ar.staff_id = ?
        ORDER BY al.performed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Appointment Logs');
$pdf->SetSubject('Appointment Logs');
$pdf->SetKeywords('Appointment, Logs, PDF');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Appointment Logs', 'Generated on ' . date('F j, Y'));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Create the table content
$html = '<table border="1" cellpadding="4">
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Student</th>
                <th>Action</th>
                <th>Performed At</th>
            </tr>';

foreach ($logs as $log) {
    $html .= '<tr>
                <td>' . date('F j, Y', strtotime($log['date'])) . '</td>
                <td>' . date('g:i A', strtotime($log['time'])) . '</td>
                <td>' . $log['student_name'] . '</td>
                <td>' . ucfirst($log['action']) . '</td>
                <td>' . date('F j, Y g:i A', strtotime($log['performed_at'])) . '</td>
              </tr>';
}

$html .= '</table>';

// Print the table
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('appointment_logs.pdf', 'D');