document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    const appointments = JSON.parse(document.getElementById('appointments-data').textContent);
    console.log('Initial appointments:', appointments);
    updateCalendarWithAppointments(appointments);
});