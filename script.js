let currentDate = new Date();
let selectedDate = null;
let selectedTime = null;
let events = {};
let editingEventId = null;
let currentService = 'counseling';
let appointments = [];
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
        updateServiceColors();
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
        dayElement.addEventListener('click', () => showAppointments(year, month, day));
        calendar.appendChild(dayElement);
    }

    updateCalendarWithAppointments(appointments);
    fetchAvailableSlots();
}

function showAppointments(year, month, day) {
    const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    document.getElementById('popupDate').textContent = date;
    
    const appointmentList = document.getElementById('appointmentList');
    appointmentList.innerHTML = '';

    const dayAppointments = appointments.filter(app => app.date === date && app.service === currentService);
    const daySlots = availableSlots.filter(slot => slot.date === date && slot.service === currentService);
    
    console.log('Day appointments:', dayAppointments);
    console.log('Day slots:', daySlots);

    if (dayAppointments.length === 0 && daySlots.length === 0) {
        appointmentList.innerHTML = '<p>No appointments or available slots for this date.</p>';
    } else {
        if (dayAppointments.length > 0) {
            const appointmentsHeader = document.createElement('h3');
            appointmentsHeader.textContent = 'Appointments:';
            appointmentList.appendChild(appointmentsHeader);
            dayAppointments.forEach(app => {
                const appElement = document.createElement('div');
                appElement.classList.add('appointment-item');
                appElement.innerHTML = `
                    <p><strong>Time:</strong> ${app.time}</p>
                    <p><strong>Student:</strong> ${app.student_name}</p>
                    <p><strong>Service:</strong> ${app.service}</p>
                `;
                appointmentList.appendChild(appElement);
            });
        }
        
        if (daySlots.length > 0) {
            const slotsHeader = document.createElement('h3');
            slotsHeader.textContent = 'Available Slots:';
            appointmentList.appendChild(slotsHeader);
            daySlots.forEach(slot => {
                const slotElement = document.createElement('div');
                slotElement.classList.add('slot-item');
                slotElement.innerHTML = `
                    <p><strong>Time:</strong> ${slot.time}</p>
                    <p><strong>Service:</strong> ${slot.service}</p>
                `;
                const editButton = document.createElement('button');
                editButton.textContent = 'Edit';
                editButton.addEventListener('click', () => editSlot(slot.id, slot.date, slot.time, slot.service));
                slotElement.appendChild(editButton);

                const deleteButton = document.createElement('button');
                deleteButton.textContent = 'Delete';
                deleteButton.addEventListener('click', () => deleteSlot(slot.id));
                slotElement.appendChild(deleteButton);

                appointmentList.appendChild(slotElement);
            });
        }
    }

    document.getElementById('appointmentPopup').style.display = 'block';
}

function editSlot(id, date, time, service) {
    const newTime = prompt("Enter new time (HH:MM:SS):", time);
    if (newTime) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('date', date);
        formData.append('time', newTime);
        formData.append('service', service);

        fetch('update_slot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data === 'success') {
                alert('Slot updated successfully');
                fetchAvailableSlots();
            } else {
                alert('Failed to update slot');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the slot');
        });
    }
}

function deleteSlot(id) {
    if (confirm("Are you sure you want to delete this slot?")) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('delete_slot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data === 'success') {
                alert('Slot deleted successfully');
                fetchAvailableSlots();
            } else {
                alert('Failed to delete slot');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the slot');
        });
    }
}

function closePopup() {
    document.getElementById('appointmentPopup').style.display = 'none';
}

function updateCalendarWithAppointments(appointmentsData) {
    appointments = appointmentsData;
    updateCalendarDays();
}

function updateCalendarDays() {
    const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
    calendarDays.forEach(day => {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), parseInt(day.textContent));
        const dateString = formatDate(date);
        const dayAppointments = appointments.filter(app => app.date === dateString && app.service === currentService);
        const daySlots = availableSlots.filter(slot => slot.date === dateString && slot.service === currentService);
        day.classList.remove('has-event', 'has-available-slot', 'counseling', 'psychology', 'entrance');
        if (dayAppointments.length > 0) {
            day.classList.add('has-event', currentService);
        }
        if (daySlots.length > 0) {
            day.classList.add('has-available-slot');
        }
    });
}

function formatDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
}

function updateServiceColors() {
    document.body.className = currentService;
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

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    // Fetch initial appointments and available slots
    fetchAppointments();
    fetchAvailableSlots();
});

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    // Fetch initial appointments and available slots
    fetchAppointments();
    fetchAvailableSlots();
});

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    // Fetch initial appointments and available slots
    fetchAppointments();
    fetchAvailableSlots();
});


document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    // Fetch initial appointments and available slots
    fetchAppointments();
    fetchAvailableSlots();
});

function updateCalendarWithAvailableSlots(availableSlots) {
    const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
    calendarDays.forEach(day => {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), parseInt(day.textContent));
        const dateString = formatDate(date);
        const daySlots = availableSlots.filter(slot => slot.date === dateString);
        if (daySlots.length > 0) {
            day.classList.add('has-available-slot', currentService);
        } else {
            day.classList.remove('has-available-slot', 'counseling', 'psychology', 'entrance');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    const addEventButton = document.getElementById('addEvent');
    if (addEventButton) {
        addEventButton.addEventListener('click', addEvent);
    }

    const updateEventButton = document.getElementById('updateEvent');
    if (updateEventButton) {
        updateEventButton.addEventListener('click', updateEvent);
    }

    const cancelEditButton = document.getElementById('cancelEdit');
    if (cancelEditButton) {
        cancelEditButton.addEventListener('click', resetForm);
    }

    // Fetch initial appointments
    fetchAppointments();
});

function fetchAppointments() {
    fetch('get_appointments.php')
        .then(response => response.text())
        .then(data => {
            console.log('Appointments data:', data);
            appointments = data.trim().split('\n').filter(line => line.length > 0).map(line => {
                const [id, date, time, service, student_id, student_name] = line.split('|');
                return { id, date, time, service, student_id, student_name };
            });
            console.log('Processed appointments:', appointments);
            updateCalendarWithAppointments(appointments);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch appointments. Please try again.');
        });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    fetchAppointments();
    fetchAvailableSlots();
});


function renderTimeGrid() {
    const timeGrid = document.getElementById('timeGrid');
    if (timeGrid) {
        timeGrid.innerHTML = '';

        for (let hour = 0; hour < 24; hour++) {
            for (let minute of ['00', '15', '30', '45']) {
                const timeSlot = document.createElement('div');
                timeSlot.classList.add('time-slot');
                const time = `${hour.toString().padStart(2, '0')}:${minute}`;
                timeSlot.textContent = time;
                timeSlot.addEventListener('click', () => selectTime(time));
                timeGrid.appendChild(timeSlot);
            }
        }
    }
}

function selectDate(year, month, day) {
    selectedDate = new Date(year, month, day);
    selectedTime = null;
    renderCalendar();
    renderTimeGrid();
    fetchEvents();
}

function selectTime(time) {
    selectedTime = time;
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.toggle('selected', slot.textContent === time);
    });
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.style.display = 'block';
    }
}

function fetchEvents() {
    const dateString = formatDate(selectedDate);
    fetch(`get_events.php?date=${dateString}&service=${currentService}`)
        .then(response => response.text())
        .then(data => {
            if (data.startsWith('ERROR:')) {
                throw new Error(data);
            }
            events[dateString] = [];
            if (data !== "No events found") {
                const eventLines = data.trim().split('\n');
                events[dateString] = eventLines.map(line => {
                    const [id, title, time, service] = line.split('|');
                    return { id: parseInt(id), title, time, service };
                });
            }
            renderEvents();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch events. Please try again.');
        });
}

function renderEvents() {
    const eventList = document.getElementById('eventList');
    if (eventList) {
        eventList.innerHTML = '';

        if (selectedDate) {
            const dateString = formatDate(selectedDate);
            const dayEvents = events[dateString] || [];

            dayEvents.forEach(event => {
                const eventItem = document.createElement('div');
                eventItem.classList.add('event-item');
                eventItem.innerHTML = `
                    <span>${event.title} - ${event.time}</span>
                    <div>
                        <button onclick="editEvent(${event.id})">Edit</button>
                        <button onclick="deleteEvent(${event.id})">Delete</button>
                    </div>
                `;
                eventList.appendChild(eventItem);
            });
        }
    }
}

function addEvent() {
    const title = document.getElementById('eventTitle').value;

    if (title && selectedDate && selectedTime) {
        const dateString = formatDate(selectedDate);
        const data = new FormData();
        data.append('date', dateString);
        data.append('title', title);
        data.append('time', selectedTime);
        data.append('service', currentService);

        fetch('add_event.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.text())
        .then(result => {
            if (result.startsWith('SUCCESS:')) {
                fetchEvents();
                renderCalendar();
                resetForm();
                alert('Event added successfully!');
            } else {
                throw new Error(result);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add event. Please try again.');
        });
    }
}

function editEvent(id) {
    const dateString = formatDate(selectedDate);
    const event = events[dateString].find(e => e.id === id);
    document.getElementById('eventTitle').value = event.title;
    selectedTime = event.time;
    document.getElementById('addEvent').style.display = 'none';
    document.getElementById('updateEvent').style.display = 'inline-block';
    document.getElementById('cancelEdit').style.display = 'inline-block';
    editingEventId = id;

    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.toggle('selected', slot.textContent === event.time);
    });
}

function updateEvent() {
    const title = document.getElementById('eventTitle').value;

    if (title && selectedDate && selectedTime && editingEventId !== null) {
        const data = new FormData();
        data.append('id', editingEventId);
        data.append('title', title);
        data.append('time', selectedTime);
        data.append('service', currentService);

        fetch('update_event.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.text())
        .then(result => {
            if (result.startsWith('SUCCESS:')) {
                fetchEvents();
                renderCalendar();
                resetForm();
                alert('Event updated successfully!');
            } else {
                throw new Error(result);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update event. Please try again.');
        });
    }
}

function deleteEvent(id) {
    fetch(`delete_event.php?id=${id}&service=${currentService}`)
        .then(response => response.text())
        .then(result => {
            if (result.startsWith('SUCCESS:')) {
                fetchEvents();
                renderCalendar();
                alert('Event deleted successfully!');
            } else {
                throw new Error(result);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete event. Please try again.');
        });
}

function resetForm() {
    const eventTitleInput = document.getElementById('eventTitle');
    if (eventTitleInput) {
        eventTitleInput.value = '';
    }
    const addEventButton = document.getElementById('addEvent');
    if (addEventButton) {
        addEventButton.style.display = 'inline-block';
    }
    const updateEventButton = document.getElementById('updateEvent');
    if (updateEventButton) {
        updateEventButton.style.display = 'none';
    }
    const cancelEditButton = document.getElementById('cancelEdit');
    if (cancelEditButton) {
        cancelEditButton.style.display = 'none';
    }
    editingEventId = null;
    selectedTime = null;
    document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    updateServiceColors();
    
    const addEventButton = document.getElementById('addEvent');
    if (addEventButton) {
        addEventButton.addEventListener('click', addEvent);
    }

    const updateEventButton = document.getElementById('updateEvent');
    if (updateEventButton) {
        updateEventButton.addEventListener('click', updateEvent);
    }

    const cancelEditButton = document.getElementById('cancelEdit');
    if (cancelEditButton) {
        cancelEditButton.addEventListener('click', resetForm);
    }

    // Fetch initial appointments
    fetchAppointments();
});

function fetchAppointments() {
    fetch('get_appointments.php')
        .then(response => response.text())
        .then(data => {
            const appointmentsData = data.trim().split('\n').map(line => {
                const [id, date, time, service, student_id, student_name] = line.split('|');
                return { id, date, time, service, student_id, student_name };
            });
            updateCalendarWithAppointments(appointmentsData);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch appointments. Please try again.');
        });
}