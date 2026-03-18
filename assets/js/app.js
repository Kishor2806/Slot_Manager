// assets/js/app.js
document.addEventListener('DOMContentLoaded', function () {

    // Base API path depends on where the script is called from (root index.php or admin/calendar.php)
    const apiBasePath = window.API_BASE_PATH || 'api/';

    // Elements
    const calendarEl = document.getElementById('calendar');
    // Removed Bootstrap Modals
    const bookingModalEl = document.getElementById('bookingModal');
    const detailsModalEl = document.getElementById('eventDetailsModal');
    const bookingForm = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBookingBtn');
    const alertMsg = document.getElementById('bookingAlertMsg');

    let calendar; // to access globally

    // 12-hour time picker elements
    const startDateEl = document.getElementById('start_date');
    const startHourEl = document.getElementById('start_hour');
    const startMinuteEl = document.getElementById('start_minute');
    const startAmpmEl = document.getElementById('start_ampm');
    const endDateEl = document.getElementById('end_date');
    const endHourEl = document.getElementById('end_hour');
    const endMinuteEl = document.getElementById('end_minute');
    const endAmpmEl = document.getElementById('end_ampm');
    const startTimeHidden = document.getElementById('start_time');
    const endTimeHidden = document.getElementById('end_time');

    // Helper: Convert 12-hour to 24-hour
    function to24Hour(hour, ampm) {
        hour = parseInt(hour);
        if (ampm === 'AM') {
            return hour === 12 ? 0 : hour;
        } else {
            return hour === 12 ? 12 : hour + 12;
        }
    }

    // Helper: Convert 24-hour to 12-hour
    function to12Hour(hour24) {
        hour24 = parseInt(hour24);
        if (hour24 === 0) return { hour: 12, ampm: 'AM' };
        if (hour24 < 12) return { hour: hour24, ampm: 'AM' };
        if (hour24 === 12) return { hour: 12, ampm: 'PM' };
        return { hour: hour24 - 12, ampm: 'PM' };
    }

    // Helper: Pad number with leading zero
    function pad(n) {
        return String(n).padStart(2, '0');
    }

    // Helper: Combine 12-hour fields into datetime-local string (YYYY-MM-DDThh:mm)
    function combineDateTime(dateVal, hourVal, minuteVal, ampmVal) {
        const h24 = to24Hour(hourVal, ampmVal);
        return dateVal + 'T' + pad(h24) + ':' + pad(minuteVal);
    }

    // Helper: Populate 12-hour fields from a JS Date object
    function populateFields(date, dateEl, hourEl, minuteEl, ampmEl) {
        if (!date || !dateEl || !hourEl || !minuteEl || !ampmEl) return;
        const y = date.getFullYear();
        const m = pad(date.getMonth() + 1);
        const d = pad(date.getDate());
        dateEl.value = y + '-' + m + '-' + d;

        const h24 = date.getHours();
        const min = date.getMinutes();
        const { hour, ampm } = to12Hour(h24);
        hourEl.value = hour;
        minuteEl.value = pad(min);
        ampmEl.value = ampm;
    }

    // Sync hidden inputs before form submission
    function syncHiddenInputs() {
        if (startDateEl && startHourEl && startMinuteEl && startAmpmEl && startTimeHidden) {
            startTimeHidden.value = combineDateTime(startDateEl.value, startHourEl.value, startMinuteEl.value, startAmpmEl.value);
        }
        if (endDateEl && endHourEl && endMinuteEl && endAmpmEl && endTimeHidden) {
            endTimeHidden.value = combineDateTime(endDateEl.value, endHourEl.value, endMinuteEl.value, endAmpmEl.value);
        }
    }

    // Fetch Event Types for Dropdown
    fetch(apiBasePath + 'get_event_types.php')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('event_id');
            if (select) {
                select.innerHTML = '';
                data.forEach(type => {
                    const opt = document.createElement('option');
                    opt.value = type.id;
                    opt.textContent = type.title;
                    opt.dataset.duration = type.default_duration; // store duration
                    select.appendChild(opt);
                });
            }
        });

    // Initialize FullCalendar
    if (calendarEl) {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: "08:00:00",
            slotMaxTime: "20:00:00",
            slotLabelFormat: {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: false,
                meridiem: 'short'
            },
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            allDaySlot: false,
            expandRows: true,
            nowIndicator: true,
            themeSystem: 'standard',
            events: apiBasePath + 'get_events.php', // Fetches automatically
            selectable: true,
            selectMirror: true,
            dateClick: function (info) {
                // Single click on a time slot (Week/Day view) or date cell (Month view)
                if (info.date < new Date()) {
                    alert("Cannot book past dates!");
                    return;
                }

                document.getElementById('bookingModalTitle').textContent = 'Book a Slot';
                document.getElementById('booking_id').value = '';

                // Populate start fields from clicked date/time
                populateFields(info.date, startDateEl, startHourEl, startMinuteEl, startAmpmEl);
                // Default end = start + 1 hour
                const defaultEnd = new Date(info.date.getTime() + 60 * 60000);
                populateFields(defaultEnd, endDateEl, endHourEl, endMinuteEl, endAmpmEl);

                document.getElementById('description').value = '';
                document.getElementById('submitBookingBtn').textContent = 'Submit Booking';

                alertMsg.classList.add('hidden');
                bookingModalEl.classList.remove('hidden');
            },
            select: function (info) {
                // Prevent booking past dates
                if (info.start < new Date()) {
                    alert("Cannot book past dates!");
                    calendar.unselect();
                    return;
                }

                // Show modal and prepopulate dates for New Booking
                document.getElementById('bookingModalTitle').textContent = 'Book a Slot';
                document.getElementById('booking_id').value = '';

                // Populate 12-hour fields
                populateFields(info.start, startDateEl, startHourEl, startMinuteEl, startAmpmEl);
                let endDate = info.end;
                if (!endDate) {
                    // if clicking a single slot without dragging, end is null
                    endDate = new Date(info.start.getTime() + 60 * 60000); 
                }
                populateFields(endDate, endDateEl, endHourEl, endMinuteEl, endAmpmEl);

                document.getElementById('description').value = '';
                document.getElementById('submitBookingBtn').textContent = 'Submit Booking';

                alertMsg.classList.add('hidden'); // hide alert
                bookingModalEl.classList.remove('hidden');
            },
            eventContent: function (info) {
                const titleStr = info.event.title;
                const emailStr = info.event.extendedProps.user_email || '';
                
                // Format event time manually for the card if needed, or rely on FullCalendar
                let html = `<div class="p-1 h-full w-full overflow-hidden leading-tight flex flex-col gap-[2px]">
                    <div class="font-semibold text-[11px] truncate">${titleStr}</div>`;
                
                if (emailStr) {
                    html += `<div class="font-normal text-[10px] opacity-90 truncate">Booked by: ${emailStr}</div>`;
                }
                
                html += `</div>`;
                
                return { html: html };
            },
            eventClick: function (info) {
                const props = info.event.extendedProps;

                document.getElementById('detailTitle').textContent = info.event.title;
                document.getElementById('detailUser').textContent = props.user_name || 'N/A';
                document.getElementById('detailTime').textContent = info.event.start.toLocaleString() + ' - ' + (info.event.end ? info.event.end.toLocaleString() : '');
                document.getElementById('detailDesc').textContent = props.description || 'No description provided.';

                const statusEl = document.getElementById('detailStatus');
                statusEl.textContent = props.status.toUpperCase();

                // Color header based on theme Color
                document.getElementById('detailHeaderColor').style.backgroundColor = props.themeColor || '#0b5ed7';

                if (props.status === 'approved') statusEl.className = 'inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800';
                else if (props.status === 'pending') statusEl.className = 'inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800';
                else statusEl.className = 'inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800';

                // Show actions button if it's their own event, OR if they are an admin.
                // window.currentUserId and window.currentUserRole are set in index.php head
                const actionsEl = document.getElementById('detailActions');
                const cancelBtn = document.getElementById('cancelOwnEventBtn');
                const editBtn = document.getElementById('editOwnEventBtn'); // New

                const isOwner = props.user_id == window.currentUserId;
                const isAdmin = window.currentUserRole === 'admin' || window.currentUserRole === 'super_admin';

                if ((isOwner || isAdmin) && props.status !== 'cancelled') {
                    actionsEl.style.display = 'flex';
                    cancelBtn.onclick = function () {
                        cancelEvent(info.event.id);
                    };
                    editBtn.onclick = function() {
                        editEvent(info.event);
                    };
                } else {
                    actionsEl.style.display = 'none';
                }

                detailsModalEl.classList.remove('hidden');
            }
        });
        calendar.render();
    }

    // Edit Event Action Helper
    function editEvent(event) {
        detailsModalEl.classList.add('hidden');
        const props = event.extendedProps;
        
        // Populate modal with existing data
        document.getElementById('bookingModalTitle').textContent = 'Edit Slot';
        document.getElementById('booking_id').value = event.id;
        document.getElementById('event_id').value = props.event_id;

        // Populate 12-hour fields
        populateFields(event.start, startDateEl, startHourEl, startMinuteEl, startAmpmEl);
        let endDate = event.end;
        if (!endDate) {
            endDate = new Date(event.start.getTime() + 60 * 60000);
        }
        populateFields(endDate, endDateEl, endHourEl, endMinuteEl, endAmpmEl);

        document.getElementById('description').value = props.description || '';
        document.getElementById('submitBookingBtn').textContent = 'Update Booking';

        alertMsg.classList.add('hidden');
        bookingModalEl.classList.remove('hidden');
    }

    // Booking Form Submit
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            // Sync 12-hour fields into hidden datetime-local inputs before submitting
            syncHiddenInputs();

            const isEdit = document.getElementById('booking_id').value !== '';

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';
            alertMsg.classList.add('hidden');

            const formData = new FormData(bookingForm);
            const endpoint = isEdit ? 'edit_slot.php' : 'book_slot.php';

            fetch(apiBasePath + endpoint, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bookingForm.reset();
                        bookingModalEl.classList.add('hidden');
                        calendar.refetchEvents();
                        // optional toast
                    } else {
                        alertMsg.textContent = data.error || 'An error occurred';
                        alertMsg.className = 'text-sm font-bold p-3 rounded-lg bg-red-100 text-red-700';
                        alertMsg.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    alertMsg.textContent = 'Network Error';
                    alertMsg.className = 'text-sm font-bold p-3 rounded-lg bg-red-100 text-red-700';
                    alertMsg.classList.remove('hidden');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = isEdit ? 'Update Booking' : 'Submit Booking';
                });
        });
    }

    // Helper: format JS Date to YYYY-MM-DDThh:mm for datetime-local (kept for backward compat)
    function formatDateTimeLocal(date) {
        const offset = date.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(date - offset)).toISOString().slice(0, 16);
        return localISOTime;
    }

    // Cancel own event
    function cancelEvent(eventId) {
        if (!confirm("Are you sure you want to cancel this booking?")) return;

        const formData = new FormData();
        formData.append('booking_id', eventId);

        fetch(apiBasePath + 'cancel_own_slot.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    detailsModalEl.classList.add('hidden');
                    calendar.refetchEvents();
                } else {
                    alert(data.error || "Failed to cancel.");
                }
            });
    }

    // Auto-update end time based on event default duration
    const eventSelect = document.getElementById('event_id');

    if (eventSelect && startDateEl && startHourEl && startMinuteEl && startAmpmEl) {
        eventSelect.addEventListener('change', updateEndTime);
        startDateEl.addEventListener('change', updateEndTime);
        startHourEl.addEventListener('change', updateEndTime);
        startMinuteEl.addEventListener('change', updateEndTime);
        startAmpmEl.addEventListener('change', updateEndTime);

        function updateEndTime() {
            if (!startDateEl.value || !startHourEl.value || !startMinuteEl.value) return;
            const selectedOpt = eventSelect.options[eventSelect.selectedIndex];
            if (!selectedOpt) return;

            const durationMins = parseInt(selectedOpt.dataset.duration || 60);
            const dtStr = combineDateTime(startDateEl.value, startHourEl.value, startMinuteEl.value, startAmpmEl.value);
            const startD = new Date(dtStr);
            const endD = new Date(startD.getTime() + durationMins * 60000);

            populateFields(endD, endDateEl, endHourEl, endMinuteEl, endAmpmEl);
        }
    }
});
