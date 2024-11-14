document.addEventListener('DOMContentLoaded', function() {
    const approveButtons = document.querySelectorAll('.approve-btn');
    const declineButtons = document.querySelectorAll('.decline-btn');
    const verifyButton = document.getElementById('verifySlip');

    approveButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateAppointmentStatus(this.getAttribute('data-id'), 'approved');
        });
    });

    declineButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateAppointmentStatus(this.getAttribute('data-id'), 'declined');
        });
    });

    verifyButton.addEventListener('click', verifySlip);
});

function updateAppointmentStatus(appointmentId, status) {
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('status', status);

    fetch('update_appointment_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.startsWith('success')) {
            alert(`Appointment ${status} successfully`);
            location.reload(); // Reload the page to reflect the changes
        } else {
            alert('Failed to update appointment status: ' + data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the appointment status');
    });
}

function verifySlip() {
    const slipNumber = document.getElementById('slipNumber').value;
    
    fetch('verify_slip.php?slip_number=' + encodeURIComponent(slipNumber))
    .then(response => response.text())
    .then(data => {
        const resultDiv = document.getElementById('verificationResult');
        if (data.startsWith('error:')) {
            resultDiv.innerHTML = '<p class="error">' + data + '</p>';
        } else {
            const appointmentData = data.split('|');
            resultDiv.innerHTML = `
                <p><strong>Student ID:</strong> ${appointmentData[0]}</p>
                <p><strong>Student Name:</strong> ${appointmentData[1]}</p>
                <p><strong>Date:</strong> ${appointmentData[2]}</p>
                <p><strong>Time:</strong> ${appointmentData[3]}</p>
                <p><strong>Status:</strong> ${appointmentData[4]}</p>
                <button id="confirmVerification" data-id="${appointmentData[5]}">Confirm Attendance</button>
            `;
            document.getElementById('confirmVerification').addEventListener('click', function() {
                updateAttendanceStatus(this.getAttribute('data-id'), 'attended');
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while verifying the slip');
    });
}

function updateAttendanceStatus(appointmentId, attendanceStatus) {
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('attendance_status', attendanceStatus);

    fetch('update_attendance_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.startsWith('success')) {
            alert('Attendance confirmed successfully');
            location.reload(); // Reload the page to reflect the changes
        } else {
            alert('Failed to update attendance status: ' + data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the attendance status');
    });
}