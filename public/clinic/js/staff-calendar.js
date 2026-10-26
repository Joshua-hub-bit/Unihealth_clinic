'use strict';

(function () {
    const today = new Date();
    const calendarTimeZone = document.getElementById('page-calendar')?.dataset.timezone || 'Asia/Manila';
    const calendarState = {
        clinic: { month: new Date(today.getFullYear(), today.getMonth(), 1), selected: calendarDateKey(today), events: [] },
        provider: { month: new Date(today.getFullYear(), today.getMonth(), 1), selected: calendarDateKey(today), events: [] },
    };
    let loadPromise = null;
    let loaded = false;

    function calendarDateKey(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function normalizeCalendarDate(value) {
        const dateValue = String(value || '').trim();
        const isoDate = dateValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoDate && !/[T ]/.test(dateValue)) return `${isoDate[1]}-${isoDate[2]}-${isoDate[3]}`;
        if (isoDate && /(?:Z|[+-]\d{2}:?\d{2})$/i.test(dateValue)) {
            const parsedDate = new Date(dateValue);
            if (Number.isNaN(parsedDate.getTime())) return '';
            const parts = new Intl.DateTimeFormat('en', {
                timeZone: calendarTimeZone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).formatToParts(parsedDate);
            const dateParts = Object.fromEntries(parts.map(part => [part.type, part.value]));
            return `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
        }
        if (isoDate) return `${isoDate[1]}-${isoDate[2]}-${isoDate[3]}`;

        const localizedDate = dateValue.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (localizedDate) {
            return `${localizedDate[3]}-${String(localizedDate[2]).padStart(2, '0')}-${String(localizedDate[1]).padStart(2, '0')}`;
        }

        const parsedDate = new Date(dateValue);
        return Number.isNaN(parsedDate.getTime()) ? '' : calendarDateKey(parsedDate);
    }

    function escapeCalendarText(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[character]);
    }

    function currentUserRole() {
        const user = window.__clinicUser || window.uhGetUser?.() || {};
        return user.role || document.body.dataset.clinicRole || 'nurse';
    }

    async function fetchAppointments() {
        const role = currentUserRole();
        if (role === 'nurse_head') {
            const appointments = [];
            let page = 1;
            let pages = 1;
            do {
                const response = await fetch(`/api/clinic/nurse-head/appointments?appointment_group=upcoming&sort_order=asc&limit=100&page=${page}`, {
                    headers: { Accept: 'application/json' }, credentials: 'same-origin',
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Could not load the appointment calendar.');
                }
                appointments.push(...(data.appointments || []));
                pages = Number(data.pagination?.pages || 1);
                page += 1;
            } while (page <= pages);
            return appointments;
        }

        const response = await fetch('/api/clinic/nurse/queue?days=366', {
            headers: { Accept: 'application/json' }, credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || data.error || 'Could not load the appointment calendar.');
        }
        return data.queue || [];
    }

    function normalizeEvents(appointments) {
        const events = { clinic: [], provider: [] };
        appointments.forEach(appointment => {
            const studentRecord = appointment.student || {};
            const student = [
                appointment.first_name || studentRecord.first_name,
                appointment.last_name || studentRecord.last_name,
            ].filter(Boolean).join(' ').trim()
                || studentRecord.name
                || appointment.student_name
                || appointment.student?.student_id
                || 'Student appointment';
            const studentId = appointment.student_id || studentRecord.student_id || '';
            const service = String(appointment.service_type || '').toLowerCase();
            const status = String(appointment.status || '').toLowerCase();

            if (status === 'checkup_scheduled') {
                const date = normalizeCalendarDate(appointment.checkup_date || appointment.date);
                if (date) {
                    events.clinic.push({
                        id: appointment.id, date, time: appointment.checkup_time || appointment.time,
                        patient: student, studentId, status, campus: appointment.campus?.name || appointment.campus_name || '',
                        type: 'checkup', title: 'Clinic check-up',
                    });
                }
            }

            const providerDate = normalizeCalendarDate(appointment.appt_date || (status === 'confirmed' ? appointment.date : null));
            if (status === 'confirmed' && providerDate) {
                const dentist = service.includes('dent') || service.includes('oral');
                events.provider.push({
                    id: appointment.id, date: providerDate, time: appointment.appt_time || appointment.time,
                    patient: student, studentId, status, campus: appointment.campus?.name || appointment.campus_name || '',
                    type: dentist ? 'dentist' : 'physician', title: dentist ? 'Dentist appointment' : 'Physician appointment',
                });
            }
        });
        return events;
    }

    function renderCalendar(key) {
        const state = calendarState[key];
        const monthEl = document.getElementById(`calendar-${key}-month`);
        const daysEl = document.getElementById(`calendar-${key}-days`);
        const countEl = document.getElementById(`calendar-${key}-count`);
        const eventsEl = document.getElementById(`calendar-${key}-events`);
        if (!monthEl || !daysEl || !eventsEl) return;

        monthEl.textContent = state.month.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        const monthKey = `${state.month.getFullYear()}-${String(state.month.getMonth() + 1).padStart(2, '0')}`;
        const monthEvents = state.events.filter(event => event.date.startsWith(`${monthKey}-`));
        countEl.textContent = String(monthEvents.length);
        const firstDay = new Date(state.month.getFullYear(), state.month.getMonth(), 1);
        const gridStart = new Date(firstDay.getFullYear(), firstDay.getMonth(), 1 - firstDay.getDay());
        const todayKey = calendarDateKey(new Date());
        const eventMap = new Map();
        state.events.forEach(event => {
            if (!eventMap.has(event.date)) eventMap.set(event.date, []);
            eventMap.get(event.date).push(event);
        });

        daysEl.innerHTML = Array.from({ length: 42 }, (_, index) => {
            const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + index);
            const dateKey = calendarDateKey(date);
            const dayEvents = eventMap.get(dateKey) || [];
            const classes = ['nurse-calendar-day'];
            if (date.getMonth() !== state.month.getMonth()) classes.push('is-outside');
            if (dateKey === todayKey) classes.push('is-today');
            if (dateKey === state.selected) classes.push('is-selected');
            const indicators = dayEvents.length
                ? `<span class="nurse-calendar-day-indicator" aria-hidden="true">${dayEvents.length}</span>`
                : '<span class="nurse-calendar-day-indicator is-empty" aria-hidden="true"></span>';
            const appointmentLabel = dayEvents.length
                ? `, ${dayEvents.length} ${dayEvents.length === 1 ? 'appointment' : 'appointments'}`
                : ', no appointments';
            return `<button class="${classes.join(' ')}" type="button" role="gridcell" aria-label="${date.toLocaleDateString(undefined, { dateStyle: 'full' })}${appointmentLabel}" aria-pressed="${dateKey === state.selected}" onclick="clinicCalendarSelect('${key}','${dateKey}')"><span class="nurse-calendar-day-number">${date.getDate()}</span>${indicators}</button>`;
        }).join('');
        renderDayEvents(key);
    }

    function renderDayEvents(key) {
        const state = calendarState[key];
        const container = document.getElementById(`calendar-${key}-events`);
        if (!container) return;
        const selectedDate = new Date(`${state.selected}T12:00:00`);
        const events = state.events.filter(event => event.date === state.selected)
            .sort((left, right) => String(left.time || '').localeCompare(String(right.time || '')));
        const heading = selectedDate.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
        const patientCount = new Set(events.map(event => event.studentId || event.patient)).size;
        container.innerHTML = `<div class="nurse-calendar-events-heading"><strong>${escapeCalendarText(heading)}</strong><span>${patientCount} ${patientCount === 1 ? 'patient' : 'patients'} · ${events.length} ${events.length === 1 ? 'appointment' : 'appointments'}</span></div>`;
        if (container) {
            if (events.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'nurse-calendar-empty';
                empty.textContent = 'No appointments scheduled for this day.';
                container.appendChild(empty);
                return;
            }
            events.forEach(event => {
                const card = document.createElement('article');
                card.className = `nurse-calendar-event is-${event.type}`;
                const marker = document.createElement('span');
                marker.className = 'nurse-calendar-event-marker';
                const content = document.createElement('div');
                content.className = 'nurse-calendar-event-content';
                const title = document.createElement('div');
                title.className = 'nurse-calendar-event-title';
                title.textContent = event.title;
                const meta = document.createElement('div');
                meta.className = 'nurse-calendar-event-meta';
                meta.textContent = [event.patient, event.studentId, event.campus].filter(Boolean).join(' · ');
                const time = document.createElement('span');
                time.className = 'nurse-calendar-event-time';
                time.textContent = event.time ? new Date(`2000-01-01T${String(event.time).slice(0, 5)}:00`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : 'Time TBD';
                content.append(title, meta);
                card.append(marker, content, time);
                container.appendChild(card);
            });
        }
    }

    function showCalendarDayDetails(key) {
        const dialog = document.getElementById('calendar-day-dialog');
        const inlineEvents = document.getElementById(`calendar-${key}-events`);
        const title = document.getElementById('calendar-day-dialog-title');
        const summary = document.getElementById('calendar-day-dialog-summary');
        const details = document.getElementById('calendar-day-dialog-events');
        if (!dialog || !inlineEvents || !title || !summary || !details) return;

        title.textContent = inlineEvents.querySelector('.nurse-calendar-events-heading strong')?.textContent || 'Appointments';
        summary.textContent = inlineEvents.querySelector('.nurse-calendar-events-heading span')?.textContent || '';
        details.replaceChildren(...Array.from(inlineEvents.children).slice(1).map(element => element.cloneNode(true)));
        if (!dialog.open) dialog.showModal();
    }

    window.clinicCalendarSelect = function (key, date) {
        if (!calendarState[key]) return;
        calendarState[key].selected = date;
        const selectedDate = new Date(`${date}T12:00:00`);
        calendarState[key].month = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        renderCalendar(key);
        showCalendarDayDetails(key);
    };

    window.clinicCalendarMove = function (key, change) {
        const state = calendarState[key];
        if (!state) return;
        const selected = new Date(`${state.selected}T12:00:00`);
        const month = new Date(state.month.getFullYear(), state.month.getMonth() + change, 1);
        const day = Math.min(selected.getDate(), new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate());
        state.month = month;
        state.selected = calendarDateKey(new Date(month.getFullYear(), month.getMonth(), day));
        renderCalendar(key);
    };

    window.clinicCalendarToday = function (key) {
        const state = calendarState[key];
        if (!state) return;
        const now = new Date();
        state.month = new Date(now.getFullYear(), now.getMonth(), 1);
        state.selected = calendarDateKey(now);
        renderCalendar(key);
    };

    window.clinicCalendarLoad = async function (refresh = false) {
        if (loaded && !refresh) return;
        if (loadPromise) return loadPromise;
        renderCalendar('clinic');
        renderCalendar('provider');
        ['clinic', 'provider'].forEach(key => {
            const events = document.getElementById(`calendar-${key}-events`);
            if (events) events.innerHTML = '<p class="nurse-calendar-empty">Loading appointments…</p>';
        });
        loadPromise = fetchAppointments()
            .then(appointments => {
                const events = normalizeEvents(appointments);
                calendarState.clinic.events = events.clinic;
                calendarState.provider.events = events.provider;
                loaded = true;
                renderCalendar('clinic');
                renderCalendar('provider');
            })
            .catch(error => {
                ['clinic', 'provider'].forEach(key => {
                    const events = document.getElementById(`calendar-${key}-events`);
                    if (events) events.innerHTML = `<p class="nurse-calendar-empty nurse-calendar-error">${escapeCalendarText(error.message || 'Could not load appointments. Please try again.')}</p>`;
                });
                if (typeof window.uhToast === 'function') window.uhToast(error.message || 'Could not load appointments.', 'error');
                throw error;
            })
            .finally(() => { loadPromise = null; });
        return loadPromise;
    };
}());
