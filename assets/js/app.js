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

    // Fetch Event Types for Dropdown
    fetch(apiBasePath + 'get_event_types.php')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('event_id');
            select.innerHTML = '';
            data.forEach(type => {
                const opt = document.createElement('option');
                opt.value = type.id;
                opt.textContent = type.title;
                opt.dataset.duration = type.default_duration; // store duration
                select.appendChild(opt);
            });
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
            allDaySlot: false,
            expandRows: true,
            nowIndicator: true,
            themeSystem: 'standard',
            events: apiBasePath + 'get_events.php', // Fetches automatically
            selectable: true,
            selectMirror: true,
            select: function (info) {
                // Prevent booking past dates
                if (info.start < new Date()) {
                    alert("Cannot book past dates!");
                    calendar.unselect();
                    return;
                }

                // Show modal and prepopulate dates
                document.getElementById('start_time').value = formatDateTimeLocal(info.start);
                document.getElementById('end_time').value = formatDateTimeLocal(info.end);
                document.getElementById('description').value = '';

                alertMsg.classList.add('hidden'); // hide alert
                bookingModalEl.classList.remove('hidden');
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

                // Show cancel button only if it's their own event
                // window.currentUserId is set in index.php head
                const actionsEl = document.getElementById('detailActions');
                const cancelBtn = document.getElementById('cancelOwnEventBtn');
                if (props.user_id == window.currentUserId && props.status !== 'cancelled') {
                    actionsEl.classList.remove('hidden');
                    cancelBtn.onclick = function () {
                        cancelEvent(info.event.id);
                    };
                } else {
                    actionsEl.classList.add('hidden');
                }

                detailsModalEl.classList.remove('hidden');
            }
        });
        calendar.render();
    }

    // Booking Form Submit
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';
            alertMsg.classList.add('hidden');

            const formData = new FormData(bookingForm);

            fetch(apiBasePath + 'book_slot.php', {
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
                    submitBtn.innerHTML = 'Submit Booking';
                });
        });
    }

    // Helper: format JS Date to YYYY-MM-DDThh:mm for datetime-local
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

    // Auto-update end time based on event default duration roughly
    const eventSelect = document.getElementById('event_id');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');

    if (eventSelect && startInput && endInput) {
        eventSelect.addEventListener('change', updateEndTime);
        startInput.addEventListener('change', updateEndTime);

        function updateEndTime() {
            if (!startInput.value) return;
            const selectedOpt = eventSelect.options[eventSelect.selectedIndex];
            if (!selectedOpt) return;

            const durationMins = parseInt(selectedOpt.dataset.duration || 60);
            const startD = new Date(startInput.value);
            const endD = new Date(startD.getTime() + durationMins * 60000);

            endInput.value = formatDateTimeLocal(endD);
        }
    }
});
