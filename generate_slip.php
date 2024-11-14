<?php
require_once 'config.php';
require_once 'fpdf/fpdf.php'; // Ensure the FPDF library is included

if (isset($_GET['slip_number'])) {
    $slip_number = $_GET['slip_number'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT ar.*, u.name AS student_name FROM appointment_requests ar 
                            JOIN users u ON ar.student_id = u.id 
                            WHERE ar.slip_number = ?");
    if (!$stmt) {
        die("Database query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $slip_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();

        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // Add logo to the top-left corner
        $pdf->Image('images/prmsu.jpeg', 10, 10, 30); // Adjust size and position as needed

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Ln(40); // Add space below the logo for the header
        $pdf->Cell(0, 10, 'Appointment Slip', 0, 1, 'C');
        $pdf->Ln(10);

        // Content
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Slip Number: ' . $appointment['slip_number'], 0, 1);
        $pdf->Cell(0, 10, 'Student Name: ' . $appointment['student_name'], 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . date("F j, Y", strtotime($appointment['date'])), 0, 1);
        $pdf->Cell(0, 10, 'Time: ' . date("g:i A", strtotime($appointment['time'])), 0, 1);
        $pdf->Cell(0, 10, 'Service: ' . $appointment['service'], 0, 1);
        $pdf->Cell(0, 10, 'Status: ' . $appointment['status'], 0, 1);

        // Output PDF
        $pdf->Output('D', 'appointment_slip.pdf');
    } else {
        echo "Invalid slip number";
    }

    $stmt->close();
} else {
    echo "No slip number provided";
}

$conn->close();
?>