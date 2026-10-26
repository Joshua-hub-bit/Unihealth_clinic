'use strict';

let nAvailabilitySchedules = [];
let nAvailabilityEditingBookedTimes = new Set();
let nAvailabilityMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
let nAvailabilitySelectedDate = nurseLocalDateString();
const nAvailabilityWeekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function nAvailabilityMinutes(time) {
  const [hours, minutes] = String(time || '').split(':').map(Number);
  return hours * 60 + minutes;
}

function nAvailabilityDateString(year, month, day) {
  return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function nAvailabilityRenderCalendar() {
  const calendar = document.getElementById('n-availability-calendar');
  const monthLabel = document.getElementById('n-availability-month-label');
  if (!calendar || !monthLabel) return;

  const year = nAvailabilityMonth.getFullYear();
  const month = nAvailabilityMonth.getMonth();
  const monthName = nAvailabilityMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  monthLabel.textContent = monthName;
  const schedulesByDate = new Map();
  nAvailabilitySchedules.forEach((schedule) => {
    if (!schedule.available_date.startsWith(`${year}-${String(month + 1).padStart(2, '0')}-`)) return;
    const schedules = schedulesByDate.get(schedule.available_date) || [];
    schedules.push(schedule);
    schedulesByDate.set(schedule.available_date, schedules);
  });

  const firstWeekday = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const cellCount = Math.ceil((firstWeekday + daysInMonth) / 7) * 7;
  const today = nurseLocalDateString();
  const cells = Array.from({ length: cellCount }, (_, index) => {
    const day = index - firstWeekday + 1;
    if (day < 1 || day > daysInMonth) {
      return '<div class="availability-calendar-day is-outside" aria-hidden="true"></div>';
    }

    const date = nAvailabilityDateString(year, month, day);
    const classes = [
      'availability-calendar-day',
      date === today ? 'is-today' : '',
      date === nAvailabilitySelectedDate ? 'is-selected' : '',
    ].filter(Boolean).join(' ');
    const items = (schedulesByDate.get(date) || [])
      .sort((a, b) => a.provider_type.localeCompare(b.provider_type))
      .map((schedule) => {
        const type = schedule.provider_type === 'dentist' ? 'dentist' : 'physician';
        const booked = Number(schedule.booked_count || 0);
        const capacity = Number(schedule.capacity || 0);
        const statusClass = schedule.is_available ? '' : ' availability-calendar-event--unavailable';
        const scheduleLabel = schedule.is_available
          ? `${escapeHtml(uhFormatTime(schedule.opens_at))}–${escapeHtml(uhFormatTime(schedule.closes_at))}`
          : 'Not available';
        return `<button type="button" class="availability-calendar-event availability-calendar-event--${type}${statusClass}" onclick="nEditAvailability(${Number(schedule.id)});event.stopPropagation()" title="${escapeHtml(type === 'dentist' ? 'Dentist' : 'Physician')} · ${scheduleLabel} · ${booked}/${capacity} booked">
          <strong>${type === 'dentist' ? 'Dentist' : 'Physician'}</strong>
          <span>${scheduleLabel}</span>
          <small>${booked}/${capacity} booked</small>
        </button>`;
      }).join('');

    return `<div class="${classes}">
      <button type="button" class="availability-calendar-date" onclick="nAvailabilitySelectDate('${date}')" aria-label="Select ${escapeHtml(monthName)} ${day}">
        <span>${day}</span>
      </button>
      <div class="availability-calendar-events">${items || '<span class="availability-calendar-empty"></span>'}</div>
    </div>`;
  }).join('');

  calendar.innerHTML = `<div class="availability-calendar-weekdays">${nAvailabilityWeekdays.map((weekday) => `<div>${weekday}</div>`).join('')}</div>
    <div class="availability-calendar-grid">${cells}</div>`;
}

window.nAvailabilityChangeMonth = function (offset) {
  nAvailabilityMonth = new Date(nAvailabilityMonth.getFullYear(), nAvailabilityMonth.getMonth() + offset, 1);
  nAvailabilityRenderCalendar();
};

window.nAvailabilityGoToToday = function () {
  nAvailabilitySelectedDate = nurseLocalDateString();
  nAvailabilityMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  const input = document.getElementById('n-availability-date');
  if (input) input.value = nAvailabilitySelectedDate;
  nAvailabilityRenderCalendar();
};

window.nAvailabilitySelectDate = function (date) {
  nAvailabilitySelectedDate = date;
  const input = document.getElementById('n-availability-date');
  if (input) input.value = date;
  document.getElementById('n-availability-form')?.reset();
  nAvailabilityEditingBookedTimes = new Set();
  nAvailabilityToggleStatus();
  const opens = document.getElementById('n-availability-opens');
  const closes = document.getElementById('n-availability-closes');
  const capacity = document.getElementById('n-availability-capacity');
  if (opens) opens.value = '08:00';
  if (closes) closes.value = '17:00';
  if (capacity) capacity.value = '10';
  if (input) input.value = date;
  nRenderAvailabilityTimes();
  nAvailabilityRenderCalendar();
  document.getElementById('n-availability-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  document.getElementById('n-availability-provider')?.focus({ preventScroll: true });
};

function nRenderAvailabilityTimes(selectedTimes = []) {
  const container = document.getElementById('n-availability-times');
  const opens = document.getElementById('n-availability-opens')?.value;
  const closes = document.getElementById('n-availability-closes')?.value;
  if (!container) return;

  const selected = new Set([
    ...Array.from(container.querySelectorAll('input:checked'), (input) => input.value),
    ...selectedTimes,
    ...nAvailabilityEditingBookedTimes,
  ]);
  const start = nAvailabilityMinutes(opens);
  const end = nAvailabilityMinutes(closes);
  const options = new Set();
  if (Number.isFinite(start) && Number.isFinite(end) && start < end) {
    for (let minutes = Math.ceil(start / 60) * 60; minutes < end; minutes += 60) {
      options.add(`${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`);
    }
  }
  selected.forEach((time) => {
    if (nAvailabilityEditingBookedTimes.has(time) || (nAvailabilityMinutes(time) >= start && nAvailabilityMinutes(time) < end)) {
      options.add(time);
    }
  });

  const times = Array.from(options).sort();
  container.replaceChildren();
  if (!times.length) {
    container.textContent = 'Choose opening and closing hours to see time options.';
  } else {
    times.forEach((time) => {
      const booked = nAvailabilityEditingBookedTimes.has(time);
      const label = document.createElement('label');
      label.className = 'availability-time-choice';
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = time;
      checkbox.checked = selected.has(time) || booked;
      checkbox.disabled = booked;
      checkbox.addEventListener('change', nUpdateAvailabilityTimeSelection);
      const text = document.createElement('span');
      text.textContent = `${uhFormatTime(time)}${booked ? ' · booked' : ''}`;
      label.append(checkbox, text);
      container.appendChild(label);
    });
  }
  nUpdateAvailabilityTimeSelection();
}

function nUpdateAvailabilityTimeSelection() {
  const container = document.getElementById('n-availability-times');
  const capacityInput = document.getElementById('n-availability-capacity');
  const count = document.getElementById('n-availability-times-count');
  if (!container || !capacityInput) return;
  const selected = container.querySelectorAll('input:checked').length;
  const capacity = Number(capacityInput.value) || 0;
  if (count) count.textContent = `${selected} time${selected === 1 ? '' : 's'} selected (maximum ${capacity || '—'}).`;
  container.querySelectorAll('input:not(:checked):not(:disabled)').forEach((checkbox) => {
    checkbox.disabled = selected >= capacity;
  });
}

function nAvailabilityIsHead() {
  return document.body?.dataset.clinicRole === 'nurse_head';
}

function nAvailabilityCampusId() {
  return document.getElementById('n-availability-campus')?.value || '';
}

async function nAvailabilityRequest(path, options = {}) {
  const campusId = nAvailabilityCampusId();
  const query = nAvailabilityIsHead() && campusId ? `?campus_id=${encodeURIComponent(campusId)}` : '';
  const response = await fetch(`/api/clinic/availability${path}${query}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
    credentials: 'same-origin',
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok || !data.success) {
    const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
    throw new Error(data.message || data.error || validation || `Availability request failed (${response.status}).`);
  }
  return data;
}

window.nOpenAvailabilityManager = async function (requestedCampusId, providerType) {
  const page = document.getElementById('page-availability-calendar');
  if (!page) return;
  const isHead = nAvailabilityIsHead();
  document.querySelectorAll('.nurse-head-only').forEach((field) => {
    field.hidden = !isHead;
  });
  if (isHead) {
    const campusSelect = document.getElementById('n-availability-campus');
    if (campusSelect && !campusSelect.dataset.availabilityChangeBound) {
      campusSelect.addEventListener('change', nLoadAvailabilitySchedules);
      campusSelect.dataset.availabilityChangeBound = 'true';
    }
    if (campusSelect && campusSelect.options.length <= 1) {
      campusSelect.disabled = true;
      campusSelect.innerHTML = '<option value="">Loading campuses…</option>';
      try {
        const response = await fetch('/api/clinic/auth/campuses', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Could not load campuses.');
        const campuses = data.campuses || [];
        if (!campuses.length) throw new Error('No active campuses are available.');
        campusSelect.innerHTML = '<option value="">Choose a campus</option>' + campuses.map((campus) =>
          `<option value="${Number(campus.id)}">${escapeHtml(campus.name)}</option>`
        ).join('');
      } catch (error) {
        console.error('Could not load availability campuses:', error);
        campusSelect.innerHTML = '<option value="">Could not load campuses</option>';
        showToast(error.message || 'Could not load campuses.', 'error');
      } finally {
        campusSelect.disabled = false;
      }
    }
    if (campusSelect && requestedCampusId
      && Array.from(campusSelect.options).some((option) => option.value === String(requestedCampusId))) {
      campusSelect.value = String(requestedCampusId);
    }
  }
  const providerSelect = document.getElementById('n-availability-provider');
  if (providerSelect && ['physician', 'dentist'].includes(providerType)) {
    providerSelect.value = providerType;
  }
  const date = document.getElementById('n-availability-date');
  if (date && !date.min) date.min = nurseLocalDateString();
  if (date && !date.dataset.calendarDateBound) {
    date.addEventListener('change', () => {
      nAvailabilitySelectedDate = date.value;
      const [year, month] = date.value.split('-').map(Number);
      if (year && month) nAvailabilityMonth = new Date(year, month - 1, 1);
      nAvailabilityRenderCalendar();
    });
    date.dataset.calendarDateBound = 'true';
  }
  nAvailabilityEditingBookedTimes = new Set();
  nBindAvailabilityTimeControls();
  nRenderAvailabilityTimes();
  nAvailabilityRenderCalendar();
  await nLoadAvailabilitySchedules();
};

function nBindAvailabilityTimeControls() {
  ['n-availability-opens', 'n-availability-closes'].forEach((id) => {
    const input = document.getElementById(id);
    if (input && !input.dataset.availabilityTimeBound) {
      input.addEventListener('change', () => nRenderAvailabilityTimes());
      input.dataset.availabilityTimeBound = 'true';
    }
  });
  const capacity = document.getElementById('n-availability-capacity');
  if (capacity && !capacity.dataset.availabilityCapacityBound) {
    capacity.addEventListener('input', nUpdateAvailabilityTimeSelection);
    capacity.dataset.availabilityCapacityBound = 'true';
  }
}

window.nLoadAvailabilitySchedules = async function () {
  const list = document.getElementById('n-availability-list');
  const calendar = document.getElementById('n-availability-calendar');
  if (!list || !calendar) return;
  if (nAvailabilityIsHead() && !nAvailabilityCampusId()) {
    list.textContent = 'Choose a campus to view its schedules.';
    calendar.innerHTML = '<p class="uh-text-muted">Choose a campus to view its schedules.</p>';
    nAvailabilitySchedules = [];
    nAvailabilityRenderCalendar();
    return;
  }
  list.textContent = 'Loading schedules…';
  calendar.innerHTML = '<p class="uh-text-muted">Loading calendar…</p>';
  try {
    const data = await nAvailabilityRequest('');
    nAvailabilitySchedules = data.availability || [];
    nAvailabilityRenderCalendar();
    list.innerHTML = nAvailabilitySchedules.length
      ? '<div class="uh-table-wrap"><table class="uh-table"><thead><tr><th>Professional / date</th><th>Hours</th><th>Pending / confirmed / capacity</th><th>Times</th><th>Action</th></tr></thead><tbody>' +
        nAvailabilitySchedules.map((item) => `<tr>
          <td>${escapeHtml(item.provider_type)} · ${escapeHtml(uhFormatDate(item.available_date))}</td>
            <td>${item.is_available ? `${escapeHtml(uhFormatTime(item.opens_at))}–${escapeHtml(uhFormatTime(item.closes_at))}` : '<span class="uh-badge cancelled">Not available</span>'}</td>
          <td>${Number(item.pending_count || 0)} pending · ${Number(item.confirmed_count || 0)} confirmed / ${Number(item.capacity)}</td>
            <td>${item.is_available ? (item.available_times || []).map((time) => {
            const booked = (item.booked_times || []).includes(time);
            return `<span class="uh-badge ${booked ? 'in-progress' : 'completed'}">${escapeHtml(uhFormatTime(time))}${booked ? ' · booked' : ''}</span>`;
            }).join(' ') : 'No new appointment times'}</td>
          <td><button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nEditAvailability(${Number(item.id)})">Edit</button>
          <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nDeleteAvailability(${Number(item.id)})">Remove</button></td>
        </tr>`).join('') + '</tbody></table></div>'
      : '<p class="uh-text-muted">No upcoming schedules published.</p>';
  } catch (error) {
    console.error('Could not load availability schedules:', error);
    list.textContent = error.message || 'Could not load availability schedules.';
    calendar.innerHTML = `<p class="uh-text-muted">${escapeHtml(error.message || 'Could not load availability calendar.')}</p>`;
  }
}

window.nEditAvailability = function (id) {
  const item = nAvailabilitySchedules.find((schedule) => Number(schedule.id) === Number(id));
  if (!item) return;
  nAvailabilitySelectedDate = item.available_date;
  const [year, month] = item.available_date.split('-').map(Number);
  nAvailabilityMonth = new Date(year, month - 1, 1);
  nAvailabilityRenderCalendar();
  document.getElementById('n-availability-provider').value = item.provider_type;
  document.getElementById('n-availability-date').value = item.available_date;
  document.getElementById('n-availability-status').value = item.is_available ? 'available' : 'unavailable';
  document.getElementById('n-availability-opens').value = item.opens_at;
  document.getElementById('n-availability-closes').value = item.closes_at;
  document.getElementById('n-availability-capacity').value = item.capacity;
  nAvailabilityEditingBookedTimes = new Set(item.booked_times || []);
  nAvailabilityToggleStatus();
  nBindAvailabilityTimeControls();
  nRenderAvailabilityTimes(item.available_times || []);
  const campus = document.getElementById('n-availability-campus');
  if (campus && item.campus_id) campus.value = String(item.campus_id);
  document.getElementById('n-availability-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

window.nAvailabilityToggleStatus = function () {
  const isAvailable = document.getElementById('n-availability-status')?.value !== 'unavailable';
  const details = document.getElementById('n-availability-details');
  const note = document.getElementById('n-availability-unavailable-note');
  const button = document.getElementById('n-availability-save');

  if (details) {
    details.hidden = !isAvailable;
    details.querySelectorAll('input').forEach((input) => {
      input.disabled = !isAvailable;
    });
  }
  if (note) note.hidden = isAvailable;
  if (button) {
    button.innerHTML = isAvailable
      ? '<i class="fa fa-calendar-plus" aria-hidden="true"></i> Publish availability'
      : '<i class="fa fa-calendar-xmark" aria-hidden="true"></i> Set as not available';
  }
};

window.nDeleteAvailability = async function (id) {
  if (!await uhConfirm('Remove this availability schedule?', { title: 'Remove schedule?', confirmText: 'Remove', danger: true })) return;
  try {
    await nAvailabilityRequest(`/${encodeURIComponent(id)}`, { method: 'DELETE' });
    showToast('Availability removed.', 'success');
    await window.nLoadAvailabilitySchedules();
  } catch (error) {
    showToast(error.message || 'Could not remove availability.', 'error');
  }
};

window.nSaveAvailability = async function (event) {
  event.preventDefault();
  const button = document.getElementById('n-availability-save');
  const times = Array.from(
    document.querySelectorAll('#n-availability-times input:checked'),
    (input) => input.value,
  );
  const payload = {
    provider_type: document.getElementById('n-availability-provider').value,
    available_date: document.getElementById('n-availability-date').value,
    is_available: document.getElementById('n-availability-status').value !== 'unavailable',
  };
  if (payload.is_available) {
    payload.opens_at = document.getElementById('n-availability-opens').value;
    payload.closes_at = document.getElementById('n-availability-closes').value;
    payload.capacity = Number(document.getElementById('n-availability-capacity').value);
    payload.available_times = times;
  }
  if (nAvailabilityIsHead()) payload.campus_id = Number(nAvailabilityCampusId());
  if (nAvailabilityIsHead() && !payload.campus_id) {
    showToast('Choose a campus first.', 'error');
    return;
  }
  if (payload.is_available && times.length === 0) {
    showToast('Select at least one bookable appointment time.', 'error');
    return;
  }
  if (payload.is_available && times.length > payload.capacity) {
    showToast('The number of selected times cannot exceed the patient capacity.', 'error');
    return;
  }
  if (button) button.disabled = true;
  try {
    await nAvailabilityRequest('', { method: 'POST', body: JSON.stringify(payload) });
    showToast(payload.is_available
      ? 'Availability published.'
      : 'Provider marked not available. Existing appointments remain unchanged.', 'success');
    const selectedCampus = nAvailabilityCampusId();
    document.getElementById('n-availability-form')?.reset();
    nAvailabilityEditingBookedTimes = new Set();
    const campus = document.getElementById('n-availability-campus');
    if (campus && selectedCampus) campus.value = selectedCampus;
    const date = document.getElementById('n-availability-date');
    if (date) {
      date.min = nurseLocalDateString();
      date.value = nAvailabilitySelectedDate;
    }
    const opens = document.getElementById('n-availability-opens');
    const closes = document.getElementById('n-availability-closes');
    const capacity = document.getElementById('n-availability-capacity');
    if (opens) opens.value = '08:00';
    if (closes) closes.value = '17:00';
    if (capacity) capacity.value = '10';
    nAvailabilityToggleStatus();
    nRenderAvailabilityTimes();
    await window.nLoadAvailabilitySchedules();
    if (window.__pendingAppointmentSchedule) {
      window.uhShowPage('appointment-scheduler');
      await window.nLoadAppointmentScheduleSlots();
    }
  } catch (error) {
    showToast(error.message || 'Could not publish availability.', 'error');
  } finally {
    if (button) button.disabled = false;
  }
};
