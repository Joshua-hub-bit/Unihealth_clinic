'use strict';

window.uhScheduleAppointment = function (endpoint, onScheduled) {
    var dialog = document.createElement('dialog');
    dialog.className = 'uh-appointment-schedule-dialog';
    dialog.setAttribute('aria-labelledby', 'uh-appointment-schedule-title');
    dialog.innerHTML = '<form class="uh-appointment-schedule-form">' +
        '<header class="uh-appointment-schedule-header">' +
        '<span class="uh-appointment-schedule-icon" aria-hidden="true"><i class="fa fa-calendar-check"></i></span>' +
        '<div><h2 id="uh-appointment-schedule-title">Schedule appointment</h2>' +
        '<p>Choose a date and time for the patient\'s visit.</p></div></header>' +
        '<div class="uh-appointment-schedule-picker">' +
        '<section class="uh-appointment-calendar" aria-label="Choose appointment date">' +
        '<div class="uh-appointment-calendar-heading">' +
        '<button type="button" class="uh-appointment-month-nav" data-month-step="-1" aria-label="Previous month"><i class="fa fa-chevron-left"></i></button>' +
        '<h3 data-month-title></h3>' +
        '<button type="button" class="uh-appointment-month-nav" data-month-step="1" aria-label="Next month"><i class="fa fa-chevron-right"></i></button></div>' +
        '<div class="uh-appointment-weekdays" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>' +
        '<div class="uh-appointment-days" role="group" aria-label="Calendar dates"></div>' +
        '<p class="uh-appointment-timezone"><i class="fa fa-globe" aria-hidden="true"></i> Philippine time (UTC+08:00)</p>' +
        '</section>' +
        '<section class="uh-appointment-time-panel" aria-label="Choose appointment time">' +
        '<label class="uh-appointment-field-label" for="uh-appointment-hour">Appointment time</label>' +
        '<div class="uh-appointment-time-inputs">' +
        '<select id="uh-appointment-hour" aria-label="Hour" required><option value="">HH</option></select>' +
        '<span aria-hidden="true">:</span>' +
        '<select id="uh-appointment-minute" aria-label="Minute" required><option value="">MM</option></select>' +
        '<select id="uh-appointment-period" aria-label="AM or PM" required><option value="">—</option><option value="AM">AM</option><option value="PM">PM</option></select></div>' +
        '<p class="uh-appointment-time-hint">Select a time in Philippine Standard Time.</p>' +
        '<div class="uh-appointment-selection" aria-live="polite"><i class="fa fa-calendar-day" aria-hidden="true"></i><span data-selection-summary>Select a date and time</span></div>' +
        '</section></div>' +
        '<p class="uh-appointment-schedule-error" role="alert" hidden></p>' +
        '<footer class="uh-appointment-schedule-actions">' +
        '<button type="button" class="uh-btn uh-btn-outline uh-btn-sm" data-action="cancel">Cancel</button>' +
        '<button type="submit" class="uh-btn uh-btn-primary uh-btn-sm" disabled><i class="fa fa-calendar-check" aria-hidden="true"></i> Save schedule</button>' +
        '</footer></form>';

    var form = dialog.querySelector('form');
    var error = dialog.querySelector('[role="alert"]');
    var cancel = dialog.querySelector('[data-action="cancel"]');
    var submit = form.querySelector('[type="submit"]');
    var monthTitle = dialog.querySelector('[data-month-title]');
    var daysGrid = dialog.querySelector('.uh-appointment-days');
    var summary = dialog.querySelector('[data-selection-summary]');
    var hourSelect = dialog.querySelector('#uh-appointment-hour');
    var minuteSelect = dialog.querySelector('#uh-appointment-minute');
    var periodSelect = dialog.querySelector('#uh-appointment-period');
    var manilaNow = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date()).reduce(function (values, part) {
        if (part.type !== 'literal') { values[part.type] = part.value; }
        return values;
    }, {});
    var today = manilaNow.year + '-' + manilaNow.month + '-' + manilaNow.day;
    var viewYear = Number(manilaNow.year);
    var viewMonth = Number(manilaNow.month) - 1;
    var selectedDate = '';
    var previousMonthButton = dialog.querySelector('[data-month-step="-1"]');

    for (var hour = 1; hour <= 12; hour += 1) {
        hourSelect.add(new Option(String(hour).padStart(2, '0'), String(hour)));
    }
    for (var minute = 0; minute < 60; minute += 1) {
        minuteSelect.add(new Option(String(minute).padStart(2, '0'), String(minute).padStart(2, '0')));
    }

    function formatDate(dateValue, options) {
        var parts = dateValue.split('-').map(Number);
        return new Intl.DateTimeFormat('en-PH', Object.assign({ timeZone: 'UTC' }, options))
            .format(new Date(Date.UTC(parts[0], parts[1] - 1, parts[2], 12)));
    }

    function renderCalendar() {
        var monthDate = new Date(Date.UTC(viewYear, viewMonth, 1, 12));
        var daysInMonth = new Date(Date.UTC(viewYear, viewMonth + 1, 0)).getUTCDate();
        var firstDay = monthDate.getUTCDay();
        previousMonthButton.disabled = viewYear === Number(manilaNow.year)
            && viewMonth === Number(manilaNow.month) - 1;
        monthTitle.textContent = new Intl.DateTimeFormat('en-PH', {
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(monthDate);

        var buttons = [];
        for (var blank = 0; blank < firstDay; blank += 1) {
            buttons.push('<span class="uh-appointment-day-empty" aria-hidden="true"></span>');
        }
        for (var day = 1; day <= daysInMonth; day += 1) {
            var dateValue = viewYear + '-' + String(viewMonth + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            var isPast = dateValue < today;
            var isSelected = dateValue === selectedDate;
            var isToday = dateValue === today;
            buttons.push('<button type="button" class="uh-appointment-day' +
                (isSelected ? ' is-selected' : '') + (isToday ? ' is-today' : '') +
                '" data-date="' + dateValue + '" aria-label="' + formatDate(dateValue, {
                    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
                }) + (isToday ? ', today' : '') + '"' +
                (isSelected ? ' aria-pressed="true"' : ' aria-pressed="false"') +
                (isPast ? ' disabled' : '') + '>' + day + '</button>');
        }
        daysGrid.innerHTML = buttons.join('');
    }

    function currentTimeValue() {
        var hour = Number(hourSelect.value);
        var minute = Number(minuteSelect.value);
        if (!hour || minuteSelect.value === '' || !periodSelect.value) { return ''; }
        if (periodSelect.value === 'AM') {
            hour = hour === 12 ? 0 : hour;
        } else if (hour !== 12) {
            hour += 12;
        }
        return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
    }

    function updateSelection() {
        var time = currentTimeValue();
        if (selectedDate && time) {
            var dateText = formatDate(selectedDate, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            var displayHour = Number(hourSelect.value);
            var displayMinute = minuteSelect.value;
            summary.textContent = dateText + ' · ' + displayHour + ':' + displayMinute + ' ' + periodSelect.value;
            submit.disabled = false;
        } else {
            summary.textContent = selectedDate ? 'Choose a time to continue' : 'Select a date and time';
            submit.disabled = true;
        }
    }

    function close() {
        dialog.close();
        dialog.remove();
    }

    dialog.querySelectorAll('[data-month-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            viewMonth += Number(button.dataset.monthStep);
            if (viewMonth < 0) { viewMonth = 11; viewYear -= 1; }
            if (viewMonth > 11) { viewMonth = 0; viewYear += 1; }
            renderCalendar();
        });
    });
    daysGrid.addEventListener('click', function (event) {
        var dayButton = event.target.closest('[data-date]');
        if (!dayButton || dayButton.disabled) { return; }
        selectedDate = dayButton.dataset.date;
        renderCalendar();
        updateSelection();
        error.hidden = true;
    });
    [hourSelect, minuteSelect, periodSelect].forEach(function (select) {
        select.addEventListener('change', updateSelection);
    });

    cancel.addEventListener('click', close);
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) { close(); }
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        error.textContent = '';
        error.hidden = true;
        var time = currentTimeValue();
        var scheduledAt = selectedDate && time ? selectedDate + 'T' + time : '';
        var manilaMinimum = today + 'T' + manilaNow.hour + ':' + manilaNow.minute;
        if (!scheduledAt || scheduledAt <= manilaMinimum) {
            error.textContent = 'Choose a date and time in the future.';
            error.hidden = false;
            return;
        }
        submit.disabled = true;

        try {
            var response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ scheduled_at: scheduledAt }),
            });
            var data = await response.json().catch(function () { return {}; });
            if (!response.ok || !data.success) {
                var validationErrors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(validationErrors[0] || data.message || data.error || 'Could not schedule this appointment.');
            }

            close();
            if (typeof onScheduled === 'function') { await onScheduled(data); }
        } catch (requestError) {
            error.textContent = requestError.message || 'Could not schedule this appointment.';
            error.hidden = false;
            submit.disabled = false;
        }
    });

    renderCalendar();
    document.body.appendChild(dialog);
    dialog.showModal();
    (dialog.querySelector('.uh-appointment-day.is-selected')
        || dialog.querySelector('.uh-appointment-day[data-date="' + today + '"]')
        || dialog.querySelector('.uh-appointment-day:not([disabled])'))?.focus();
};
