let currentDate = new Date();
let selectedDate = null;
let selectedTime = null;
let currentService = 'counseling';
let availableSlots = [];

function initializeCalendar() {
    renderCalendar();
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });
    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });
    document.getElementById('todayButton').addEventListener('click', goToToday);
    document.getElementById('serviceSelector').addEventListener('change', (e) => {
        currentService = e.target.value;
        renderCalendar();
    });

    // Close popup when clicking on the close button or outside the popup
    const closeButton = document.querySelector('.popup .close');
    if (closeButton) {
        closeButton.addEventListener('click', closePopup);
    }
    window.addEventListener('click', (event) => {
        if (event.target === document.getElementById('appointmentPopup')) {
            closePopup();
        }
    });
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    document.getElementById('currentMonth').textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const calendar = document.getElementById('calendar');
    calendar.innerHTML = '';

    for (let i = 0; i < firstDay.getDay(); i++) {
        const emptyDay = document.createElement('div');
        emptyDay.classList.add('calendar-day', 'empty');
        calendar.appendChild(emptyDay);
    }

    for (let day = 1; day <= lastDay.getDate(); day++) {
        const dayElement = document.createElement('div');
        dayElement.classList.add('calendar-day');
        dayElement.textContent = day;
        dayElement.addEventListener('click', () => showAvailableSlots(year, month, day));
        calendar.appendChild(dayElement);
    }

    fetchAvailableSlots();
}

function showAvailableSlots(year, month, day) {
    const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    document.getElementById('popupDate').textContent = date;
    
    const availableSlotsList = document.getElementById('availableSlotsList');
    availableSlotsList.innerHTML = '';

    const daySlots = availableSlots.filter(slot => slot.date === date && slot.service === currentService);
    
    if (daySlots.length === 0) {
        availableSlotsList.innerHTML = '<p>No available slots for this date.</p>';
    } else {
        daySlots.forEach(slot => {
            const slotElement = document.createElement('div');
            slotElement.classList.add('slot-item');
            slotElement.innerHTML = `
                <p><strong>Time:</strong> ${slot.time}</p>
                <p><strong>Service:</strong> ${slot.service}</p>
                <button class="request-appointment" data-id="${slot.id}" data-date="${date}" data-time="${slot.time}" data-service="${slot.service}">Request Appointment</button>
            `;
            availableSlotsList.appendChild(slotElement);
        });

        // Add event listeners to the newly created buttons
        const requestButtons = availableSlotsList.querySelectorAll('.request-appointment');
        requestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const slotId = this.getAttribute('data-id');
                const date = this.getAttribute('data-date');
                const time = this.getAttribute('data-time');
                const service = this.getAttribute('data-service');
                requestAppointment(slotId, date, time, service);
            });
        });
    }

    document.getElementById('appointmentPopup').style.display = 'block';
}

function requestAppointment(slotId, date, time, service) {
    if (confirm(`Are you sure you want to request an appointment on ${date} at ${time} for ${service}?`)) {
        const formData = new FormData();
        formData.append('slot_id', slotId);
        formData.append('date', date);
        formData.append('time', time);
        formData.append('service', service);

        fetch('request_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log('Server response:', data); // Add this line for debugging
            if (data.startsWith('success')) {
                const slipNumber = data.split('|')[1];
                alert(`Appointment requested successfully. Your slip number is: ${slipNumber}`);
                closePopup();
                fetchAvailableSlots();
                location.reload(); // Reload the page to update the appointments list
            } else {
                alert('Failed to request appointment: ' + data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while requesting the appointment');
        });
    }
}

function closePopup() {
    document.getElementById('appointmentPopup').style.display = 'none';
}

function formatDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
}

function fetchAvailableSlots() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;
    fetch(`get_available_slots.php?year=${year}&month=${month}&service=${currentService}`)
        .then(response => response.text())
        .then(data => {
            console.log('Available slots data:', data);
            availableSlots = data.trim().split('\n').filter(line => line.length > 0).map(line => {
                const [id, date, time, service] = line.split('|');
                return { id, date, time, service };
            });
            console.log('Processed available slots:', availableSlots);
            updateCalendarDays();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch available slots. Please try again.');
        });
}

function updateCalendarDays() {
    const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
    calendarDays.forEach(day => {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), parseInt(day.textContent));
        const dateString = formatDate(date);
        const daySlots = availableSlots.filter(slot => slot.date === dateString && slot.service === currentService);
        day.classList.remove('has-available-slot');
        if (daySlots.length > 0) {
            day.classList.add('has-available-slot');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
});