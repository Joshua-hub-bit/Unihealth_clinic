// Nurse Dashboard Logic - Updated to use PostgreSQL API
'use strict';

const API = '/api/clinic/nurse';

function nurseApiBase() {
  const currentUser = window.__clinicUser || JSON.parse(sessionStorage.getItem('clinic_user') || 'null');
  return currentUser?.role === 'nurse_head' ? '/api/clinic/nurse-head' : API;
}

let queueCurrent = 1, queueTotal = 3, queueWaiting = 0;
let currentStaffId = null;
let currentCampusId = null;
let currentPatients = [];
let currentVitalRecords = [];
let currentNurseReferrals = [];

// Render the campus badge in the sidebar
function renderCampusBadge(campusName) {
  const badge   = document.getElementById('sidebarCampusBadge');
  const nameEl  = document.getElementById('sidebarCampusName');
  if (!badge || !nameEl) return;
  if (campusName && campusName !== 'All Campuses') {
    nameEl.textContent  = campusName;
    badge.style.display = 'block';
  } else {
    badge.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  // auth-guard.js already verified the session — use the validated user
  const userJson = sessionStorage.getItem('clinic_user');
  const user = userJson ? JSON.parse(userJson) : null;
  if (!user) { window.location.replace('/login'); return; }
  currentStaffId = user.id;
  currentCampusId = user.campusId || null;

  // Render campus badge
  renderCampusBadge(user.campusName || null);

  const nameEl   = document.getElementById('sidebarName');
  const roleEl   = document.getElementById('sidebarRole');
  const avatarEl = document.getElementById('sidebarAvatar');
  if (nameEl)   nameEl.textContent   = user.name   || 'Nurse User';
  if (roleEl)   roleEl.textContent   = 'Nurse / Staff';
  if (avatarEl) avatarEl.textContent = (user.name  || 'N')[0].toUpperCase();

  const queueSearch = document.getElementById('queueTableSearch');
  if (queueSearch) {
    queueSearch.addEventListener('input', () => {
      nRenderQueueSearch(queueSearch.value);
    });
  }

  const vitalRecordEditForm = document.getElementById('vitalRecordEditForm');
  if (vitalRecordEditForm) {
    vitalRecordEditForm.addEventListener('submit', nSaveVitalRecordEdit);
  }

  const h  = new Date().getHours();
  const gr = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const grEl = document.getElementById('greetMsg');
  if (grEl) grEl.textContent = `${gr}!`;
  const subEl = document.getElementById('greetSub');
  if (subEl) {
    const firstName = user.name ? user.name.split(' ')[0] : 'Nurse';
    subEl.innerHTML = `<strong>${firstName}</strong>`;
  }

  const today = nurseLocalDateString();
  ['xray_date','ref_date','rx_date','lab_date','lac_date','bf_date',
   'med_date','dent_date','cert_date'].forEach(id => {
    const el = document.getElementById(id);
    if (el && !el.value) el.value = today;
  });

  const referralStatusFilter = document.getElementById('n-referral-status-filter');
  if (referralStatusFilter) referralStatusFilter.value = '';
  const referralSearch = document.querySelector('#page-referrals-coord [data-uh-table-search]');
  if (referralSearch) referralSearch.value = '';

  setupNursePageObserver();
  updateQueue();
  // Cache signing personnel so the New Referral modal can prefill
  // the "referred to" field the moment the nurse picks a route.
  loadSigningPersonnel();
  await Promise.all([
    loadStats(),
    renderPatients(),
    loadQueueWithFallback(),
  ]);
});

async function apiFetch(action, method = 'GET', body = null) {
  // Map legacy action?param style to REST paths
  const actionMap = {
    'get_nurse_stats':        'stats',
    'get_patients':           'patients',
    'get_patient_for_form':   'patients',
    'get_student_for_form':   'student-form',
    'search_student':         'search-student',
    'save_visit':             'visits',
    'get_visits':             'visits',
    'save_form':              'forms',
    'print_form':             'forms',
    'get_forms':              'forms',
    'get_queue':              'queue',
    'update_queue_status':    'queue',
    'get_consultation':       'consultations',
    'get_signing_personnel':  'signing-personnel',
    'get_referrals':          'referrals',
  };

  window.nSubmitClinicCheckup = async function(event) {
    event.preventDefault();
    const entry = window.__pendingAppointmentSchedule;
    const date = document.getElementById('appointment-checkup-date')?.value || '';
    const time = document.getElementById('appointment-checkup-time')?.value || '';
    const button = document.getElementById('appointment-checkup-submit');
    if (!entry || !date || !time) {
      showToast('Choose the clinic check-up date and time.', 'error');
      return;
    }
    if (button) { button.disabled = true; }
    const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
    const prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
    try {
      const response = await fetch(`/api/clinic/${prefix}/appointments/${entry.id}/checkup-schedule`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ checkup_date: date, checkup_time: time }),
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
        throw new Error(result.message || result.error || validation || 'Could not schedule the clinic check-up.');
      }
      showToast(`Clinic check-up scheduled for ${result.checkup_date} at ${result.checkup_time}.`, 'success');
      window.__pendingAppointmentSchedule = null;
      window.nReturnFromAppointmentScheduler();
    } catch (error) {
      showToast(error.message || 'Could not schedule the clinic check-up.', 'error');
    } finally {
      if (button) { button.disabled = false; }
    }
  };

  window.nCompleteClinicCheckup = async function(id) {
    const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
    const prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
    try {
      const response = await fetch(`/api/clinic/${prefix}/appointments/${Number(id)}/checkup-complete`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
        throw new Error(result.message || result.error || validation || 'Could not complete the clinic check-up.');
      }
      showToast('Clinic check-up marked complete. Schedule the provider appointment next.', 'success');
      if (role === 'nurse_head') {
        if (typeof window.nhRenderAppointments === 'function') { window.nhRenderAppointments(); }
        if (typeof window.nhLoadDashboardStats === 'function') { window.nhLoadDashboardStats(); }
      } else {
        if (typeof window.loadQueueWithFallback === 'function') { window.loadQueueWithFallback(); }
        if (typeof window.loadStats === 'function') { window.loadStats(); }
      }
    } catch (error) {
      showToast(error.message || 'Could not complete the clinic check-up.', 'error');
    }
  };

  try {
    const [rawAction, ...extraParts] = action.split('&');
    const extraParams = new URLSearchParams(extraParts.join('&'));
    const campusParam = currentCampusId ? `staff_campus_id=${currentCampusId}` : '';
    const staffParam  = `staff_id=${currentStaffId}`;
    const bust        = `_=${Date.now()}`;
    const isNurseHead = nurseApiBase() !== API;
    const usesNurseHeadRoute = ['get_student_for_form', 'search_student', 'save_visit', 'save_form', 'print_form', 'get_forms', 'get_consultation'].includes(rawAction)
      || (isNurseHead && ['PATCH', 'DELETE'].includes(method) && rawAction.startsWith('visits/'));
    const endpointAPI = isNurseHead && usesNurseHeadRoute ? nurseApiBase() : API;
    const isVitalRecordDelete = method === 'DELETE' && /^visits\/\d+$/.test(rawAction);

    let path = actionMap[rawAction] || rawAction;
    let url;
    let resolvedMethod = isVitalRecordDelete ? 'POST' : method;

    if (rawAction === 'get_student_for_form') {
      const studentId = extraParams.get('student_id') || '';
      url = `${endpointAPI}/student-form/${encodeURIComponent(studentId)}?${campusParam}&${staffParam}&${bust}`;
    } else if (rawAction === 'search_student') {
      const q = extraParams.get('q') || extraParams.get('student_id') || '';
      url = `${endpointAPI}/search-student?q=${encodeURIComponent(q)}&${campusParam}&${staffParam}&${bust}`;
    } else if (rawAction === 'get_patients') {
      const q = extraParams.get('q') || '';
      url = `${API}/patients?q=${encodeURIComponent(q)}&${campusParam}&${staffParam}&${bust}`;
    } else if (rawAction === 'print_form') {
      const id = body?.id ?? extraParams.get('id') ?? 0;
      url = `${API}/forms/${id}/print?${staffParam}&${bust}`;
      resolvedMethod = 'POST';
    } else if (rawAction === 'get_consultation') {
      const consultationId = extraParams.get('consultation_id') || '';
      url = `${endpointAPI}/consultations/${encodeURIComponent(consultationId)}?${campusParam}&${staffParam}&${bust}`;
    } else {
      const qStr = [campusParam, staffParam, bust, extraParams.toString()].filter(Boolean).join('&');
      url = `${endpointAPI}/${path}${isVitalRecordDelete ? '/delete' : ''}?${qStr}`;
    }

    const opts = {
      method: resolvedMethod,
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      credentials: 'same-origin',
    };
    if (body && resolvedMethod !== 'GET') opts.body = JSON.stringify(body);
    const resp = await fetch(url, opts);
    const contentType = resp.headers.get('content-type') || 'unknown content type';
    const responseText = await resp.text();
    let data = null;
    try {
      data = responseText ? JSON.parse(responseText) : null;
    } catch {
      // Keep non-JSON server responses out of the UI; report their type below.
    }
    if (!resp.ok) {
      const validationMessage = data?.errors
        ? Object.values(data.errors).flat()[0]
        : null;
      const serverMessage = data?.error || validationMessage || data?.message;
      throw new Error(serverMessage || `The request failed (${resp.status}; ${contentType}). Check the failed request's Response tab for server details.`);
    }
    if (!data) {
      throw new Error(`The server returned an unexpected response (${contentType}).`);
    }
    return data;
  } catch (err) {
    console.error('API Error:', err);
    return { error: 'request_failed', message: err.message };
  }
}

function nSaveVitals() {
  const workspace = document.querySelector('#page-vital-signs .vitals-workspace, #page-record-vitals .vitals-workspace');
  const treatmentMode = workspace?.dataset.entryMode === 'treatment';
  const clinicalNotes = document.getElementById('vitals_notes')?.value || '';
  const payload = {
    type: treatmentMode ? 'treatment' : 'vitals',
    student_no: document.getElementById('vitals_patient_id')?.value?.trim() || '',
    bp: document.getElementById('vitals_bp')?.value || '',
    temp: document.getElementById('vitals_temp')?.value || '',
    pulse: document.getElementById('vitals_pulse')?.value || '',
    rr: document.getElementById('vitals_rr')?.value || '',
    o2_sat: document.getElementById('vitals_o2')?.value || '',
    visit_date: document.getElementById('vitals_date')?.value || new Date().toISOString().split('T')[0],
    reason: treatmentMode ? workspace.dataset.appointmentReason || '' : '',
    treatment: treatmentMode ? clinicalNotes : '',
    notes: treatmentMode ? '' : clinicalNotes,
  };
  if (!payload.student_no) {
    showToast('Please select a student first', 'error');
    return;
  }
  if (treatmentMode && !payload.treatment.trim()) {
    showToast('Please enter the treatment provided before saving.', 'error');
    return;
  }
  if (window.nVitalsSaving) { return; }

  window.nVitalsSaving = true;
  const button = document.querySelector('#page-vital-signs .vitals-save-button, #page-record-vitals .vitals-save-button');
  if (button) { button.disabled = true; }

  apiFetch('save_visit', 'POST', payload).then((response) => {
    if (response && (response.success || response.id)) {
      showToast(treatmentMode ? 'Treatment saved successfully' : 'Vitals saved successfully', 'success');
    } else {
      showToast(response?.error || 'Failed to save vitals', 'error');
    }
  }).catch(() => {
    showToast('Could not save vitals. Please try again.', 'error');
  }).finally(() => {
    window.nVitalsSaving = false;
    if (button) { button.disabled = false; }
  });
}

window.nSetVitalsTreatmentMode = function(enabled, appointment = {}) {
  const workspace = document.querySelector('#page-vital-signs .vitals-workspace, #page-record-vitals .vitals-workspace');
  if (!workspace) { return; }

  workspace.dataset.entryMode = enabled ? 'treatment' : 'vitals';
  workspace.dataset.appointmentReason = enabled ? String(appointment?.reason || '') : '';

  const heading = workspace.querySelector('#vitals-notes-heading');
  const description = workspace.querySelector('#vitals-notes-description');
  const label = workspace.querySelector('#vitals-notes-label');
  const saveLabel = workspace.querySelector('#vitals-save-label');
  const notes = workspace.querySelector('#vitals_notes');
  if (heading) { heading.textContent = enabled ? 'Treatment' : 'Clinical notes'; }
  if (description) {
    description.textContent = enabled
      ? 'Record the treatment provided during this appointment.'
      : 'Document context or observations for follow-up.';
  }
  if (label) { label.textContent = enabled ? 'Treatment provided' : 'Notes'; }
  if (saveLabel) { saveLabel.textContent = enabled ? 'Save Treatment' : 'Save Vitals'; }
  if (notes) {
    notes.placeholder = enabled ? 'Enter the treatment provided...' : 'Add symptoms, observations, or follow-up notes...';
    notes.value = '';
  }
};

function nClearVitals() {
  document.querySelector('.vitals-workspace')?.querySelectorAll('input, textarea').forEach((element) => {
    element.value = '';
  });
  window.nSetVitalsTreatmentMode(false);
  const selectedStudent = document.getElementById('vitals-selected-student');
  if (selectedStudent) { selectedStudent.textContent = ''; }
}

async function nLoadVitalRecords() {
  const body = document.getElementById('n-vital-records-body');
  if (!body) { return; }

  body.innerHTML = '<tr><td colspan="9" class="no-data">Loading vital-sign records…</td></tr>';
  const pagination = document.getElementById('n-vital-records-pagination');
  if (pagination) { pagination.innerHTML = ''; }
  const response = await apiFetch('get_visits&days=30');
  if (response.error) {
    body.innerHTML = '<tr><td colspan="9" class="no-data">Vital-sign records could not be loaded.</td></tr>';
    const count = document.querySelector('#page-vital-records [data-uh-table-count]');
    if (count) { count.textContent = ''; }
    showToast(response.message || response.error, 'error');
    return;
  }

  const visits = (response.visits || []).filter((visit) =>
    [visit.bp, visit.temp, visit.pulse, visit.rr, visit.o2_sat]
      .some((value) => value !== null && value !== undefined && value !== ''),
  );
  currentVitalRecords = visits;
  if (!visits.length) {
    body.innerHTML = '<tr><td colspan="9" class="no-data">No vital-sign records found in the last 30 days.</td></tr>';
    const count = document.querySelector('#page-vital-records [data-uh-table-count]');
    if (count) { count.textContent = '0 records'; }
    return;
  }

  const table = document.querySelector('#page-vital-records table[data-uh-searchable]');
  nRenderVitalRecords(table, document.getElementById('n-vital-records-search')?.value || '');
}

function nRenderVitalRecords(table, query = '') {
  const body = document.getElementById('n-vital-records-body');
  if (!body) { return; }

  const normalizedQuery = String(query).trim().toLowerCase();
  const matchingVisits = currentVitalRecords.filter((visit) => {
    const student = visit.student || {};
    const name = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ');
    const searchText = [
      name,
      student.student_id,
      visit.bp,
      visit.temp,
      visit.pulse,
      visit.rr,
      visit.o2_sat,
    ].filter((value) => value !== null && value !== undefined).join(' ').toLowerCase();
    return !normalizedQuery || searchText.includes(normalizedQuery);
  });
  const count = table?.closest('.uh-card')?.querySelector('[data-uh-table-count]');

  if (count) {
    count.textContent = normalizedQuery
      ? `${matchingVisits.length} match${matchingVisits.length === 1 ? '' : 'es'}`
      : `${matchingVisits.length} record${matchingVisits.length === 1 ? '' : 's'}`;
  }

  if (!matchingVisits.length) {
    body.innerHTML = `<tr><td colspan="9" class="no-data">${normalizedQuery
      ? `No matches for “${escapeHtml(query)}”.`
      : 'No vital-sign records found in the last 30 days.'}</td></tr>`;
    const pagination = document.getElementById('n-vital-records-pagination');
    if (pagination) { pagination.innerHTML = ''; }
    return;
  }

  uhPaginateTable('n-vital-records-body', matchingVisits, nRenderVitalRecordRow, 10, 'n-vital-records-pagination');
}

function nRenderVitalRecordRow(visit) {
  const student = visit.student || {};
  const studentNo = escapeHtml(student.student_id || '');
  const name = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ');
  const safeName = escapeHtml(name || 'Unknown patient');
  const safeStudentNo = String(student.student_id || '').replace(/[^A-Za-z0-9\-_]/g, '');
  return `<tr>
    <td>${safeName}</td>
    <td>${studentNo || '—'}</td>
    <td>${escapeHtml(visit.bp || '—')}</td>
    <td>${escapeHtml(visit.temp ?? '—')}</td>
    <td>${escapeHtml(visit.pulse ?? '—')}</td>
    <td>${escapeHtml(visit.rr ?? '—')}</td>
    <td>${escapeHtml(visit.o2_sat ?? '—')}</td>
    <td>${safeStudentNo ? `<button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhViewStudentHealthRecord('${safeStudentNo}')">View</button>` : '—'}</td>
    <td class="n-vital-record-actions">
      <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nEditVitalRecord(${Number(visit.id)})" aria-label="Edit vital signs for ${safeName}">Edit</button>
      <button type="button" class="uh-btn uh-btn-danger uh-btn-xs" onclick="nDeleteVitalRecord(${Number(visit.id)})" aria-label="Delete vital signs for ${safeName}">Delete</button>
    </td>
  </tr>`;
}

function nEditVitalRecord(visitId) {
  const visit = currentVitalRecords.find((record) => Number(record.id) === Number(visitId));
  if (!visit) {
    showToast('This vital-sign record is no longer available. Reload the list and try again.', 'error');
    return;
  }

  const form = document.getElementById('vitalRecordEditForm');
  if (!form) { return; }
  form.dataset.visitId = String(visit.id);
  ['bp', 'temp', 'pulse', 'rr', 'o2_sat'].forEach((field) => {
    const input = form.elements.namedItem(field);
    if (input) { input.value = visit[field] ?? ''; }
  });
  document.getElementById('vitalRecordEditModal')?.classList.remove('hidden');
}

function nCloseVitalRecordEdit() {
  document.getElementById('vitalRecordEditModal')?.classList.add('hidden');
}

async function nSaveVitalRecordEdit(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const visitId = form.dataset.visitId;
  if (!visitId) { return; }

  const payload = {};
  ['bp', 'temp', 'pulse', 'rr', 'o2_sat'].forEach((field) => {
    const input = form.elements.namedItem(field);
    payload[field] = input?.value.trim() || null;
  });
  const response = await apiFetch(`visits/${encodeURIComponent(visitId)}`, 'PATCH', payload);
  if (!response.success) {
    showToast(response.message || response.error || 'Vital-sign record could not be updated.', 'error');
    return;
  }

  nCloseVitalRecordEdit();
  showToast('Vital-sign record updated.', 'success');
  await nLoadVitalRecords();
}

async function nDeleteVitalRecord(visitId) {
  if (!await uhConfirm('This will permanently delete this vital-sign record. This cannot be undone.', {
    title: 'Delete vital-sign record?',
    confirmText: 'Delete record',
    danger: true,
  })) {
    return;
  }

  const response = await apiFetch(`visits/${encodeURIComponent(visitId)}`, 'DELETE');
  if (!response.success) {
    showToast(response.message || response.error || 'Vital-sign record could not be deleted.', 'error');
    return;
  }

  showToast('Vital-sign record deleted.', 'success');
  await nLoadVitalRecords();
}

async function loadStats() {
  const res = await apiFetch('get_nurse_stats');
  if (!res.stats) {
    renderVisitVolumeChartError();
    return;
  }

  setV('statPatients', res.stats.total_patients);
  setV('rptPatients', res.stats.total_patients);
  setV('statVisits', res.stats.todays_visits);
  setV('statMonthlyVisits', res.stats.visits_this_month || 0);
  setV('rptConsult', res.stats.todays_visits);
  setV('rptAppointments', res.stats.pending_appointments);
  setV('rptStaffs', res.stats.active_staff);
  setV('statPrescriptions', 0); // Will load from forms
  setV('statAlerts', res.stats.alerts_count || 0);
  setV('todayPatientCount', res.stats.todays_visits);

  const appointmentStatuses = res.stats.appointments_by_status || {};
  const waiting = Number(appointmentStatuses.waiting ?? appointmentStatuses.pending ?? 0);
  const accepted = Number(appointmentStatuses.accepted || 0);
  const checkupScheduled = Number(appointmentStatuses.checkup_scheduled || 0);
  const completed = Number(appointmentStatuses.completed || 0);
  const cancelled = Number(appointmentStatuses.cancelled || 0);
  const deleted = Number(appointmentStatuses.deleted || 0);
  setV('queueStatWaiting', waiting);
  setV('queueStatAccepted', accepted);
  setV('queueStatCheckupScheduled', checkupScheduled);
  setV('queueStatDone', completed);
  setV('queueStatCancelled', cancelled);
  setV('queueStatDeleted', deleted);

  const subEl = document.getElementById('greetSub');
  if (subEl) {
    const firstName = (sessionStorage.getItem('clinic_user') ? JSON.parse(sessionStorage.getItem('clinic_user')).name : 'Nurse').split(' ')[0];
    subEl.innerHTML = `<strong>${firstName}</strong>`;
  }

  renderVisitVolumeChart(res.stats.daily_visit_volume);
}

function renderVisitVolumeChartError() {
  const container = document.getElementById('nurse-visit-chart');
  if (container) {
    container.innerHTML = '<p class="student-empty-state">Visit chart could not be loaded.</p>';
  }
}

function renderVisitVolumeChart(dailyVisitVolume) {
  const container = document.getElementById('nurse-visit-chart');
  if (!container) return;

  const points = Array.isArray(dailyVisitVolume) ? dailyVisitVolume : [];
  const visits = points.map((point) => Math.max(0, Number(point.count) || 0));
  if (!points.length || visits.every((count) => count === 0)) {
    container.innerHTML = '<p class="student-empty-state">No visits recorded in the last 7 days.</p>';
    return;
  }

  const namespace = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(namespace, 'svg');
  svg.setAttribute('viewBox', '0 0 640 180');
  svg.setAttribute('role', 'img');
  svg.setAttribute('aria-label', 'Daily clinic visit volume for the last 7 days');

  const createSvgElement = (name, attributes, text = null) => {
    const element = document.createElementNS(namespace, name);
    Object.entries(attributes).forEach(([attribute, value]) => {
      element.setAttribute(attribute, String(value));
    });
    if (text !== null) element.textContent = text;
    return element;
  };

  const maxCount = Math.max(...visits);
  const chartLeft = 44;
  const chartRight = 628;
  const chartTop = 20;
  const chartBottom = 132;
  const chartHeight = chartBottom - chartTop;
  const chartWidth = chartRight - chartLeft;
  const xForIndex = (index) => points.length === 1
    ? chartLeft + chartWidth / 2
    : chartLeft + (chartWidth * index) / (points.length - 1);
  const yForCount = (count) => chartBottom - (count / maxCount) * chartHeight;

  [0, 0.5, 1].forEach((ratio) => {
    const y = chartBottom - chartHeight * ratio;
    svg.appendChild(createSvgElement('line', {
      x1: chartLeft,
      y1: y,
      x2: chartRight,
      y2: y,
      class: 'student-chart-gridline',
    }));
  });

  const coordinates = points.map((point, index) => ({
    point,
    count: visits[index],
    x: xForIndex(index),
    y: yForCount(visits[index]),
  }));
  const linePoints = coordinates.map(({ x, y }) => `${x},${y}`).join(' ');
  const areaPoints = `${chartLeft},${chartBottom} ${linePoints} ${chartRight},${chartBottom}`;

  svg.appendChild(createSvgElement('polygon', {
    points: areaPoints,
    class: 'student-chart-area',
  }));
  svg.appendChild(createSvgElement('polyline', {
    points: linePoints,
    class: 'student-chart-line',
  }));

  coordinates.forEach(({ point, count, x, y }) => {
    const [year, month, day] = String(point.date || '').split('-').map(Number);
    const label = year && month && day
      ? new Date(year, month - 1, day).toLocaleDateString(undefined, { weekday: 'short' })
      : '—';

    const marker = createSvgElement('circle', {
      cx: x,
      cy: y,
      r: 4,
      class: 'student-chart-point',
    });
    marker.appendChild(createSvgElement('title', {}, `${count} visit${count === 1 ? '' : 's'} on ${point.date}`));
    svg.appendChild(marker);
    svg.appendChild(createSvgElement('text', {
      x,
      y: Math.max(y - 10, 12),
      class: 'student-chart-count',
      'text-anchor': 'middle',
    }, String(count)));

    svg.appendChild(createSvgElement('text', {
      x,
      y: 158,
      class: 'student-chart-label',
      'text-anchor': 'middle',
    }, label));
  });

  container.replaceChildren(svg);
}

function setV(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

async function renderPatients(patients = null) {
  const wrap = document.getElementById('patientListWrap');
  const wrap2 = document.getElementById('patientListWrap2');
  if (!wrap && !wrap2) return;

  if (!patients) {
    const q = (document.getElementById('patientSearch')?.value || document.getElementById('patientSearchPage')?.value || '').trim();
    const res = await apiFetch(`get_patients${q ? `&q=${encodeURIComponent(q)}` : ''}`);
    patients = res.patients || [];
    currentPatients = patients;
  }

  if (patients.length === 0) {
    const html = '<p style="text-align:center;padding:24px;color:var(--text-muted);font-size:0.82rem;">No patients found.</p>';
    if (wrap) wrap.innerHTML = html;
    if (wrap2) wrap2.innerHTML = html;
    return;
  }

  const html = patients.map(p => `
    <div class="patient-row">
      <div class="p-avatar" style="background:${p.color || '#3498db'}">${p.first_name ? p.first_name[0] : p.last_name?.[0] || '?'}</div>
      <div style="flex:1">
        <div class="p-name">${p.last_name ? `${p.first_name} ${p.last_name}` : p.name}</div>
        <div class="p-detail">${p.student_id || '—'} &bull; ${p.department || p.course_year || 'Health record submitted'}</div>
      </div>
      <div class="p-time">${formatShortDate(p.health_record_updated)}</div>
      <span class="p-status s-${p.health_record_status || 'submitted'}">${capitalize(p.health_record_status || 'submitted')}</span>
      <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhViewStudentHealthRecord('${String(p.student_id || '').replace(/[^A-Za-z0-9\-_]/g, '')}')">Health record</button>
    </div>`).join('');

  if (wrap) wrap.innerHTML = html;
  if (wrap2) wrap2.innerHTML = html;
}

function filterPatients() {
  renderPatients();
}

function capitalize(value) {
  return String(value || '').charAt(0).toUpperCase() + String(value || '').slice(1);
}

// XSS-safe HTML escaper for values rendered via innerHTML in the
// Coordinate Referrals and other table renderers. (Previously missing —
// the absence was a runtime error in loadReferrals.)
function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatShortDate(value) {
  if (!value) return '—';
  return uhFormatDate(value, { year: undefined });
}

// -------------------------------------------------------
//  QUEUE — Coordinates with NurseController::queue.
//  When today has no items, automatically retry with the next
//  7 days so the page isn't blank just because the seed data
//  lives in the future. The fallback banner lets the nurse know
//  the queue includes overdue open items and a 30-day future window.
// -------------------------------------------------------
const QUEUE_FALLBACK_DAYS = 30;
const QUEUE_TABLE_PAGE_SIZE = 10;
let queueTableItems = [];
let queueTablePage = 1;
let queueTablePriority = '';
let queueTableStatus = '';
let queueTableSortOrder = 'asc'; // 'asc' = earliest first, 'desc' = latest first
const autoTriageAttemptedAppointments = new Set();
const autoTriageInFlightAppointments = new Set();

function nQueuePriorityFilterChanged(priority) {
  queueTablePriority = priority;
  queueTablePage = 1;
  nRenderQueueTablePage();
}

function nQueueStatusFilterChanged(status) {
  queueTableStatus = status;
  queueTablePage = 1;
  nRenderQueueTablePage();
}

async function nTriageAppointment(id) {
  if (autoTriageInFlightAppointments.has(String(id))) {
    showToast('Triage is already running for this appointment.', 'info');
    return;
  }

  try {
    const response = await fetch(nurseAppointmentTriageUrl(id), {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    const data = await response.json();
    if (!response.ok || !data.success) {
      showToast(data.message || data.error || 'Could not run triage for this appointment.', 'error');
      return;
    }

    showToast(`Triage result: ${data.priority}. A clinician should review this assessment.`, 'success');
    await loadQueueWithFallback();
  } catch (error) {
    console.error('Failed to triage appointment:', error);
    showToast('Could not run triage for this appointment. Check your connection and try again.', 'error');
  }
}

function nurseAppointmentTriageUrl(id) {
  const base = nurseApiBase();
  return base === API
    ? `${base}/queue/appointment/${id}/triage`
    : `${base}/appointments/${id}/triage`;
}

function nAutoTriageAppointments(items) {
  const eligible = items.filter((item) => {
    const id = String(item.id);
    return item.type === 'appointment'
      && !item.priority
      && String(item.reason || '').trim() !== ''
      && !autoTriageAttemptedAppointments.has(id);
  });

  if (!eligible.length) return;
  eligible.forEach((item) => {
    autoTriageAttemptedAppointments.add(String(item.id));
    autoTriageInFlightAppointments.add(String(item.id));
  });

  Promise.all(eligible.map(async (item) => {
    try {
      const response = await fetch(nurseAppointmentTriageUrl(item.id), {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        throw new Error(data.message || data.error || 'Could not automatically triage this appointment.');
      }

      return { id: item.id, priority: data.priority, error: null };
    } catch (error) {
      console.error(`Failed to automatically triage appointment ${item.id}:`, error);
      return { id: item.id, priority: null, error };
    }
  })).then((results) => {
    results.forEach((result) => autoTriageInFlightAppointments.delete(String(result.id)));
    const assessed = results.filter((result) => !result.error);
    const failed = results.filter((result) => result.error);

    assessed.forEach((result) => {
      const item = queueTableItems.find((queueItem) => queueItem.type === 'appointment'
        && String(queueItem.id) === String(result.id));
      if (item) item.priority = result.priority;
    });

    if (assessed.length) {
      showToast(`Triage completed for ${assessed.length} appointment${assessed.length === 1 ? '' : 's'}. A clinician should review the assessments.`, 'success');
      nRenderQueueTablePage();
    }
    if (failed.length) {
      showToast(`Could not automatically triage ${failed.length} appointment${failed.length === 1 ? '' : 's'}. Review the appointment reason and try again.`, 'error');
    }
  });
}

function nurseLocalDateString(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function nurseCheckupTimeHasArrived(date, time) {
  const checkupDate = String(date || '').slice(0, 10);
  const today = nurseLocalDateString();
  if (!checkupDate || checkupDate > today) return false;
  if (checkupDate < today || !time) return true;
  const now = new Date();
  const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  return String(time).slice(0, 5) <= currentTime;
}

async function loadQueueWithFallback() {
  const tbody = document.getElementById('queueTableBody');
  if (!tbody) return;

  const today = nurseLocalDateString();
  const res = await apiFetch(`get_queue&date=${today}&days=${QUEUE_FALLBACK_DAYS}`);
  const items = (res && res.queue) || [];
  renderPatientQueue(items);
  nRenderQueueTable(items, res?.completed_count, res?.summary || {});
}

function nRenderQueueTable(items, completedCount = null, summary = {}) {
  const tbody = document.getElementById('queueTableBody');
  if (!tbody) return;

  const queueTotal = Number.isFinite(Number(summary.in_queue))
    ? Number(summary.in_queue)
    : items.length;
  const totalElement = document.getElementById('queueStatTotal');
  if (totalElement) totalElement.textContent = queueTotal;

  queueTableItems = items;
  queueTablePage = 1;
  nRenderQueueTablePage();
}

function nRenderQueueTablePage(query = '') {
  const tbody = document.getElementById('queueTableBody');
  const pagination = document.getElementById('queueTablePagination');
  const searchInput = document.getElementById('queueTableSearch');
  const count = document.querySelector('#queueTableSearch')?.parentElement?.querySelector('[data-uh-table-count]');
  if (!tbody) return;

  const searchTerm = String(query || searchInput?.value || '').trim().toLowerCase();
  const filteredItems = queueTableItems.filter((item) => {
    if (queueTableStatus && item.status !== queueTableStatus) {
      return false;
    }

    if (queueTablePriority && (item.type !== 'appointment' || item.priority !== queueTablePriority)) {
      return false;
    }

    return [
      item.id,
      item.type,
      item.status,
      item.student_id,
      item.first_name,
      item.middle_name,
      item.last_name,
      item.date,
      item.time,
      item.detail,
      item.service_type,
      item.reason,
      item.preferred_time,
      item.campus_name,
      item.priority,
    ].filter(Boolean).join(' ').toLowerCase().includes(searchTerm);
  });

  if (count) {
    count.textContent = filteredItems.length
      ? `Showing ${Math.min((queueTablePage - 1) * QUEUE_TABLE_PAGE_SIZE + 1, filteredItems.length)}–${Math.min(queueTablePage * QUEUE_TABLE_PAGE_SIZE, filteredItems.length)} of ${filteredItems.length}`
      : searchTerm ? 'No matches' : '';
  }

  if (!filteredItems.length) {
    const hasFilters = searchTerm || queueTableStatus || queueTablePriority;
    tbody.innerHTML = `<p class="student-empty-state">${hasFilters ? 'No queue items match the selected filters.' : `No open appointments or walk-ins. No upcoming appointments in the next ${QUEUE_FALLBACK_DAYS} days.`}</p>`;
    if (pagination) pagination.innerHTML = '';
    return;
  }

  const totalPages = Math.ceil(filteredItems.length / QUEUE_TABLE_PAGE_SIZE);
  queueTablePage = Math.min(Math.max(queueTablePage, 1), totalPages);

  // Sort by appointment date + time
  filteredItems.sort((a, b) => {
    const aTime = `${a.date || ''} ${a.time || ''}`.trim();
    const bTime = `${b.date || ''} ${b.time || ''}`.trim();
    const cmp = aTime.localeCompare(bTime);
    return queueTableSortOrder === 'asc' ? cmp : -cmp;
  });

  const startIndex = (queueTablePage - 1) * QUEUE_TABLE_PAGE_SIZE;
  const pageItems = filteredItems.slice(startIndex, startIndex + QUEUE_TABLE_PAGE_SIZE);

  tbody.innerHTML = pageItems.map((q, pageIndex) => {
    const name = [q.first_name, q.last_name].filter(Boolean).join(' ') || 'Unknown patient';
    const sid = q.student_id || '';
    const reason = q.type === 'appointment' ? q.service_type || q.detail || '—' : q.detail || '—';
    const statusLabels = { accepted: 'Accepted · schedule check-up', checkup_scheduled: 'Clinic check-up scheduled', checkup_completed: 'Check-up done · schedule provider', confirmed: 'Provider visit confirmed', completed: 'Completed' };
    const statusLabel = statusLabels[q.status] || q.status || 'Waiting';
    const statusClass = ['confirmed', 'checkup_scheduled', 'checkup_completed', 'accepted'].includes(q.status) ? 'status-active' : q.status === 'completed' ? 'status-done' : 'status-waiting';
    const initial = escapeHtml((q.first_name || q.last_name || 'U')[0].toUpperCase());
    const priorityValue = String(q.priority || '').trim().toLowerCase();
    const priorityLevels = {
      high: 'High',
      'priority 1': 'High',
      '1': 'High',
      medium: 'Medium',
      'priority 2': 'Medium',
      '2': 'Medium',
      low: 'Low',
      'priority 3': 'Low',
      '3': 'Low',
    };
    const priorityLabel = priorityLevels[priorityValue] || '';
    const priorityClass = priorityLabel ? priorityLabel.toLowerCase() : 'unassigned';
    const modelPrediction = q.model_prediction && typeof q.model_prediction === 'object'
      ? q.model_prediction
      : null;
    const modelConfidence = Number(modelPrediction?.confidence);
    const triageConfidence = Number(q.confidence);
    const confidenceLabel = modelPrediction && Number.isFinite(modelConfidence) && modelConfidence >= 0 && modelConfidence <= 1
      ? `Model: ${modelPrediction.priority || 'Priority unavailable'} · ${Math.round(modelConfidence * 100)}% confidence`
      : q.confidence !== null && q.confidence !== undefined && q.confidence !== '' && Number.isFinite(triageConfidence) && triageConfidence >= 0 && triageConfidence <= 1
        ? `Triage confidence: ${Math.round(triageConfidence * 100)}%`
        : 'Confidence unavailable';
    const entry = {
      id: q.id,
      type: q.type || 'appointment',
      status: q.status || 'pending',
      availability_id: q.availability_id ? Number(q.availability_id) : null,
      consultation_id: q.consultation_id ? Number(q.consultation_id) : null,
      campus_id: q.campus_id ? Number(q.campus_id) : null,
      student_id: q.student_id || '',
      patient_type: q.patient_type || '',
      first_name: q.first_name || '',
      middle_name: q.middle_name || '',
      last_name: q.last_name || '',
      created_at: q.created_at || '',
      date: q.date || '',
      time: q.time || '',
      checkup_date: q.checkup_date || '',
      checkup_time: q.checkup_time || '',
      detail: q.detail || '',
      service_type: q.service_type || '',
      reason: q.reason || '',
      preferred_time: q.preferred_time || '',
      campus_name: q.campus_name || '',
      is_overdue: !!q.is_overdue,
    };
    let actions = '';
    if (q.type === 'appointment' && q.status !== 'completed' && q.status !== 'cancelled' && q.status !== 'deleted') {
      const serializedEntry = escapeHtml(JSON.stringify(entry));
      actions += `<button type="button" class="nurse-queue-workflow-action" data-queue-entry="${serializedEntry}" onclick="nViewQueueEntry(JSON.parse(this.dataset.queueEntry))" aria-label="View ${escapeHtml(name)} appointment" title="View appointment"><i class="fa fa-eye" aria-hidden="true"></i><span>View</span></button>`;
      if (!q.priority) {
        actions += `<button type="button" class="nurse-queue-workflow-action" onclick="nTriageAppointment(${q.id})" aria-label="Run triage for ${escapeHtml(name)} appointment" title="Run triage"><i class="fa fa-heart-pulse" aria-hidden="true"></i><span>Triage</span></button>`;
      }
      if (q.status === 'pending') {
        actions += `<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" data-queue-entry="${serializedEntry}" onclick="nConfirmAppt(${q.id}, JSON.parse(this.dataset.queueEntry))"><i class="fa fa-check" aria-hidden="true"></i><span>Accept</span></button>`;
        actions += `<button type="button" class="nurse-queue-workflow-action" onclick="nDeclineAppt(${q.id})"><i class="fa fa-xmark" aria-hidden="true"></i><span>Decline</span></button>`;
      }
      if (q.status === 'accepted' || q.status === 'checkup_completed') {
        const scheduleLabel = q.status === 'accepted' ? 'Schedule check-up' : 'Schedule provider';
        actions += `<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" data-queue-entry="${serializedEntry}" onclick="nScheduleAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-calendar-check" aria-hidden="true"></i><span>${scheduleLabel}</span></button>`;
      }
      if (q.status === 'accepted' || q.status === 'checkup_scheduled' || q.status === 'checkup_completed' || q.status === 'confirmed') {
        actions += `<button type="button" class="nurse-queue-workflow-action" data-queue-entry="${serializedEntry}" onclick="nReferAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-share-from-square" aria-hidden="true"></i><span>Refer</span></button>`;
      }
      if (q.status === 'checkup_scheduled') {
        const checkupDue = nurseCheckupTimeHasArrived(q.checkup_date, q.checkup_time);
        actions += `<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" onclick="nCompleteClinicCheckup(${Number(q.id)})" aria-label="Mark ${escapeHtml(name)} clinic check-up as complete"${checkupDue ? '' : ' disabled title="Available when the scheduled check-up time arrives"'}><i class="fa fa-check-double" aria-hidden="true"></i><span>Done</span></button>`;
      }
      if (q.status === 'confirmed' && q.date && q.date <= nurseLocalDateString()) {
        actions += `<button type="button" class="nurse-queue-workflow-action" onclick="nCompleteAppt(${Number(q.id)})" aria-label="Mark ${escapeHtml(name)} appointment as done"><i class="fa fa-check-double" aria-hidden="true"></i><span>Done</span></button>`;
      }
    }
    const itemType = q.type === 'appointment' ? 'Appointment' : 'Walk-in';
    const appointmentDateTime = [
      itemType,
      q.date ? uhFormatDate(q.date) : '',
      q.time ? uhFormatTime(q.time) : '',
    ].filter(Boolean).map(escapeHtml).join(' · ');
    return `<article class="nurse-recent-visit nurse-current-queue-item">
      <div class="nurse-recent-visit-avatar" aria-hidden="true">${initial}</div>
      <div class="nurse-recent-visit-main">
        <strong class="nurse-recent-visit-name">${escapeHtml(name)}</strong>
        ${uhPatientTypeBadge(q.patient_type)}
        <span class="nurse-recent-visit-id">${sid ? `ID ${escapeHtml(sid)}` : itemType}</span>
      </div>
      <div class="nurse-recent-visit-detail">
        <span class="nurse-recent-visit-reason">${escapeHtml(reason)}</span>
        <span class="nurse-current-queue-meta">${appointmentDateTime}</span>
      </div>
      <div class="nurse-current-queue-priority">
        <span class="cw-priority-indicator cw-priority-indicator--${priorityClass}">${priorityLabel || 'Not assessed'}</span>
        <small>${escapeHtml(confidenceLabel)}</small>
      </div>
      <span class="nurse-recent-visit-status ${statusClass}">${escapeHtml(statusLabel)}</span>
      <div class="nurse-current-queue-actions">${actions || '<span class="uh-text-muted">—</span>'}</div>
    </article>`;
  }).join('');

  if (pagination) {
    pagination.innerHTML = `<div class="uh-pagination">
      <button class="uh-pg-btn" type="button" aria-label="Previous queue page" ${queueTablePage === 1 ? 'disabled' : ''} onclick="nQueueGoToPage(${queueTablePage - 1})">‹</button>
      <span class="uh-pg-info">Page ${queueTablePage} of ${totalPages} · ${QUEUE_TABLE_PAGE_SIZE} per page</span>
      <button class="uh-pg-btn" type="button" aria-label="Next queue page" ${queueTablePage === totalPages ? 'disabled' : ''} onclick="nQueueGoToPage(${queueTablePage + 1})">›</button>
    </div>`;
  }

  nAutoTriageAppointments(pageItems);
}

window.nAcceptAppointment = async function(entry) {
  const appointmentId = Number(entry?.id);
  if (!Number.isSafeInteger(appointmentId) || appointmentId < 1) {
    showToast('The appointment could not be identified.', 'error');
    return;
  }
  const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
  const prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
  try {
    const response = await fetch(`/api/clinic/${prefix}/appointments/${appointmentId}/accept`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) {
      const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
      throw new Error(result.message || result.error || validation || 'Could not accept the appointment.');
    }
    showToast('Appointment accepted. Schedule the initial clinic check-up next.', 'success');
  } catch (error) {
    showToast(error.message || 'Could not accept the appointment.', 'error');
    return;
  }

  if (role === 'nurse_head') {
    if (typeof window.nhRenderAppointments === 'function') { window.nhRenderAppointments(); }
    if (typeof window.nhLoadDashboardStats === 'function') { window.nhLoadDashboardStats(); }
  } else {
    if (typeof window.loadStats === 'function') { window.loadStats(); }
    if (typeof window.loadQueueWithFallback === 'function') { window.loadQueueWithFallback(); }
  }
};

window.nScheduleAppointment = async function(entry) {
  const appointmentId = Number(entry?.id);
  const campusId = Number(entry?.campus_id || currentCampusId);
  const stage = entry?.status === 'accepted' ? 'checkup' : 'provider';
  if (!Number.isSafeInteger(appointmentId) || appointmentId < 1) {
    showToast('The appointment could not be identified.', 'error');
    return;
  }
  if (stage === 'provider' && (!Number.isSafeInteger(campusId) || campusId < 1)) {
    showToast('The student campus is unavailable, so appointment times cannot be loaded.', 'error');
    return;
  }

  window.__pendingAppointmentSchedule = {
    ...entry,
    id: appointmentId,
    campus_id: campusId,
    provider_type: String(entry?.service_type || '').toLowerCase().includes('dental') ? 'dentist' : 'physician',
    stage,
  };
  const patient = [entry?.first_name, entry?.middle_name, entry?.last_name].filter(Boolean).join(' ') || entry?.student_id || 'Patient';
  const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) { element.textContent = value || '—'; }
  };
  setText('appointment-schedule-patient', patient);
  setText('appointment-schedule-service', entry?.service_type || '—');
  setText('appointment-schedule-campus', entry?.campus_name || (campusId ? `Campus ${campusId}` : '—'));
  window.uhShowPage('appointment-scheduler');
  if (window.__pendingAppointmentSchedule.stage === 'checkup') {
    const message = document.getElementById('appointment-schedule-message');
    const emptyState = document.getElementById('appointment-schedule-empty');
    const providerForm = document.getElementById('appointment-schedule-form');
    const checkupForm = document.getElementById('appointment-checkup-form');
    const dateInput = document.getElementById('appointment-checkup-date');
    if (message) { message.hidden = true; }
    if (emptyState) { emptyState.hidden = true; }
    if (providerForm) { providerForm.hidden = true; }
    if (checkupForm) { checkupForm.hidden = false; }
    if (dateInput) {
      dateInput.min = nurseLocalDateString();
      dateInput.value = entry?.checkup_date || '';
    }
    const timeInput = document.getElementById('appointment-checkup-time');
    if (timeInput) { timeInput.value = String(entry?.checkup_time || '').slice(0, 5); }
    const instructions = document.getElementById('appointment-schedule-instructions');
    if (instructions) { instructions.textContent = 'Set the first clinic check-up. The provider appointment can only be scheduled after this check-up is marked complete.'; }
    return;
  }
  const instructions = document.getElementById('appointment-schedule-instructions');
  if (instructions) { instructions.textContent = 'Schedule the required physician or dentist visit using the clinic’s published availability.'; }
  await window.nLoadAppointmentScheduleSlots();
};

window.nLoadAppointmentScheduleSlots = async function() {
  const entry = window.__pendingAppointmentSchedule;
  const message = document.getElementById('appointment-schedule-message');
  const emptyState = document.getElementById('appointment-schedule-empty');
  const form = document.getElementById('appointment-schedule-form');
  const checkupForm = document.getElementById('appointment-checkup-form');
  const select = document.getElementById('appointment-schedule-slot');
  if (!entry || !message || !emptyState || !form || !checkupForm || !select) {
    showToast('The appointment scheduler could not be loaded. Refresh the page and try again.', 'error');
    return;
  }

  message.hidden = false;
  message.textContent = 'Loading published appointment times…';
  emptyState.hidden = true;
  checkupForm.hidden = true;
  form.hidden = true;
  select.replaceChildren(new Option('Loading available times…', ''));

  let availability;
  try {
    const query = new URLSearchParams({ campus_id: String(entry.campus_id) });
    const response = await fetch(`/api/clinic/availability?${query}`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
      throw new Error(data.message || data.error || validation || 'Could not load published appointment times.');
    }
    availability = data.availability || [];
  } catch (error) {
    message.textContent = error.message || 'Could not load published appointment times.';
    return;
  }

  const now = new Date();
  const currentLocalTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  const slots = availability
    .filter((schedule) => schedule.is_available && schedule.provider_type === entry.provider_type)
    .flatMap((schedule) => (schedule.available_times || [])
      .filter((time) => {
        const normalizedTime = String(time).slice(0, 5);
        const isBooked = (schedule.booked_times || []).some((bookedTime) => String(bookedTime).slice(0, 5) === normalizedTime);
        const isFuture = schedule.available_date !== nurseLocalDateString() || normalizedTime > currentLocalTime;
        return Number(schedule.remaining_capacity) > 0 && !isBooked && isFuture;
      })
      .map((time) => ({
        value: `${schedule.id}|${String(time).slice(0, 5)}`,
        label: `${schedule.available_date} · ${String(time).slice(0, 5)} · ${schedule.remaining_capacity} spots left`,
      })));
  if (!slots.length) {
    message.hidden = true;
    emptyState.hidden = false;
    return;
  }

  select.replaceChildren(new Option('Choose a date and time', ''));
  slots.forEach((slot) => select.add(new Option(slot.label, slot.value)));
  message.hidden = true;
  emptyState.hidden = true;
  form.hidden = false;
};

window.nOpenAvailabilityForAppointment = async function() {
  const entry = window.__pendingAppointmentSchedule;
  if (!entry || typeof window.nOpenAvailabilityManager !== 'function') {
    showToast('The Availability Calendar could not be opened. Refresh the page and try again.', 'error');
    return;
  }
  window.uhShowPage('availability-calendar');
  await window.nOpenAvailabilityManager(entry.campus_id, entry.provider_type);
};

window.nReturnFromAppointmentScheduler = function() {
  const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
  if (role === 'nurse_head') {
    window.uhShowPage('appointments');
    if (typeof window.nhRenderAppointments === 'function') { window.nhRenderAppointments(); }
  } else {
    window.uhShowPage('queue');
    if (typeof window.loadQueueWithFallback === 'function') { window.loadQueueWithFallback(); }
  }
};

window.nSubmitAppointmentSchedule = async function(event) {
  event.preventDefault();
  const entry = window.__pendingAppointmentSchedule;
  const select = document.getElementById('appointment-schedule-slot');
  const button = document.getElementById('appointment-schedule-submit');
  const selectedSlot = select?.value || '';
  if (!entry || !selectedSlot) {
    showToast('Choose an available appointment date and time.', 'error');
    return;
  }

  const [availabilityId, apptTime] = selectedSlot.split('|');
  const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
  const prefix = role === 'nurse_head' ? 'nurse-head' : 'nurse';
  let result;
  if (button) { button.disabled = true; }
  try {
    const response = await fetch(`/api/clinic/${prefix}/appointments/${entry.id}/schedule`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ availability_id: Number(availabilityId), appt_time: apptTime }),
    });
    result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) {
      const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
      throw new Error(result.message || result.error || validation || 'Could not schedule the appointment.');
    }
  } catch (error) {
    showToast(error.message || 'Could not schedule the appointment.', 'error');
    return;
  } finally {
    if (button) { button.disabled = false; }
  }

  showToast(`Appointment scheduled for ${result.appt_date} at ${result.appt_time}.`, 'success');
  window.__pendingAppointmentSchedule = null;
  if (role === 'nurse_head') {
    if (typeof window.nhRenderAppointments === 'function') { window.nhRenderAppointments(); }
    if (typeof window.nhLoadDashboardStats === 'function') { window.nhLoadDashboardStats(); }
  } else {
    if (typeof window.loadStats === 'function') { window.loadStats(); }
    if (typeof window.loadQueueWithFallback === 'function') { window.loadQueueWithFallback(); }
  }
  window.uhShowPage(role === 'nurse_head' ? 'appointments' : 'queue');
};

window.nQueueGoToPage = function(page) {
  queueTablePage = page;
  nRenderQueueTablePage();
};

window.nReferAppointment = async function(entry) {
  window.__pendingReferralSourceAppointment = null;
  const studentId = String(entry?.student_id || '').trim();
  if (!studentId) {
    showToast('This appointment does not have a student ID for referral.', 'error');
    return;
  }

  const routeTo = String(entry?.service_type || '').toLowerCase().includes('dental') ? 'dentist' : 'physician';
  const formType = routeTo === 'dentist' ? 'referral-dentist' : 'referral';
  const searchId = routeTo === 'dentist' ? 'referral_dentist_search' : 'referral_search';
  const complaintId = routeTo === 'dentist' ? 'rfd_chief_complaint' : 'rf_chief_complaint';
  uhShowPage(`form-${formType}`, document.querySelector('[data-title="Generate Forms"]'));
  const search = document.getElementById(searchId);
  if (!search) {
    showToast(`The ${routeTo} referral form is unavailable.`, 'error');
    return;
  }
  search.value = studentId;

  const campusId = Number(entry?.campus_id || currentCampusId);
  if (!Number.isSafeInteger(campusId) || campusId < 1) {
    showToast('The student campus is unavailable, so appointment times cannot be loaded.', 'error');
    return;
  }

  let scheduleResponse;
  try {
    const scheduleQuery = new URLSearchParams({ campus_id: String(campusId) });
    const response = await fetch(`/api/clinic/availability?${scheduleQuery}`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    scheduleResponse = await response.json().catch(() => ({}));
    if (!response.ok || !scheduleResponse.success) {
      const validation = scheduleResponse.errors ? Object.values(scheduleResponse.errors).flat()[0] : null;
      throw new Error(scheduleResponse.message || scheduleResponse.error || validation || 'Could not load published appointment times.');
    }
  } catch (error) {
    showToast(error.message || 'Could not load published appointment times.', 'error');
    return;
  }

  const availableSchedules = (scheduleResponse.availability || [])
    .filter((schedule) => schedule.is_available && schedule.provider_type === routeTo && Number(schedule.remaining_capacity) > 0);
  const now = new Date();
  const currentLocalTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  const scheduleOptions = availableSchedules.flatMap((schedule) =>
    (schedule.available_times || [])
      .filter((time) => !(schedule.booked_times || []).includes(time)
        && (schedule.available_date !== nurseLocalDateString() || time > currentLocalTime))
      .map((time) => ({
        value: `${schedule.id}|${time}`,
        label: `${schedule.available_date} · ${time} · ${schedule.remaining_capacity} spots left`,
      }))
  );
  let availabilityId = null;
  let apptTime = null;
  if (scheduleOptions.length) {
    const selectedSlot = await window.uhPrompt('Choose a published date and time. Only slots with remaining capacity are shown.', {
      title: `Schedule ${routeTo} referral`,
      selectOptions: scheduleOptions,
      placeholder: 'Select an available date and time',
      confirmText: 'Continue',
    });
    if (!selectedSlot) { return; }
    [availabilityId, apptTime] = selectedSlot.split('|');
  }

  let complaintReason = String(entry?.reason || '').trim();
  if (entry?.consultation_id) {
    let response = await apiFetch(`get_consultation&consultation_id=${encodeURIComponent(entry.consultation_id)}`);
    if (!response?.success) {
      showToast(response?.message || 'Could not load the consultation details.', 'error');
      return;
    }

    let consultation = response.consultation || {};
    let aiSummary = consultation.ai_summary || {};
    const writtenConcern = String(consultation.concern || '').trim();
    const audioOnlyRequest = consultation.student_voice_path
      && (!writtenConcern || writtenConcern === 'Audio recording submitted without written concern.');
    let generatedSummary = aiSummary.status === 'ready'
      ? String(aiSummary.summary || '').trim()
      : '';

    if (generatedSummary) {
      complaintReason = generatedSummary;
    } else if (audioOnlyRequest) {
      complaintReason = '';
    } else if (writtenConcern && writtenConcern !== 'Audio recording submitted without written concern.') {
      complaintReason = writtenConcern;
    }
  } else if (complaintReason === 'Audio recording submitted without written concern.') {
    complaintReason = '';
  }

  const sourceAppointmentId = Number(entry?.id);
  if (
    Number.isSafeInteger(sourceAppointmentId)
    && sourceAppointmentId > 0
    && ['accepted', 'confirmed', 'checkup_scheduled', 'checkup_completed'].includes(String(entry?.status || ''))
  ) {
    const referralSource = {
      appointmentId: sourceAppointmentId,
      studentId,
    };
    if (availabilityId && apptTime) {
      referralSource.availabilityId = Number(availabilityId);
      referralSource.apptTime = apptTime;
    }
    window.__pendingReferralSourceAppointment = referralSource;
    window.setTimeout(() => {
      if (window.__pendingReferralSourceAppointment === referralSource) {
        window.__pendingReferralSourceAppointment = null;
      }
    }, 5 * 60 * 1000);
  } else {
    window.__pendingReferralSourceAppointment = null;
  }

  search.value = studentId;
  handleFormSearch(formType, studentId).then(() => {
    const complaint = document.getElementById(complaintId);
    if (complaint && complaintReason) {
      complaint.value = complaintReason;
      complaint.dispatchEvent(new Event('input', { bubbles: true }));
      complaint.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
};

window.nTreatAppointment = function(entry) {
  const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
  const studentId = String(entry?.student_id || '').trim();

  if (role === 'nurse_head') {
    uhShowPage('record-vitals', document.querySelector('[data-title="Record Vital Signs"]'));
    window.nSetVitalsTreatmentMode(true, entry);
    const patientInput = document.getElementById('vitals_patient_id');
    if (patientInput && studentId) {
      patientInput.value = studentId;
      patientInput.dispatchEvent(new Event('input', { bubbles: true }));
      patientInput.focus();
    }
    return;
  }

  uhShowPage('encounter', document.querySelector('[data-title="Create Encounter"]'));
  const patientId = document.getElementById('enc_patient_id');
  const patientName = document.getElementById('enc_patient_name');
  const complaint = document.getElementById('enc_complaint');
  if (patientId) { patientId.value = studentId; }
  if (patientName) {
    patientName.value = [entry?.first_name, entry?.middle_name, entry?.last_name].filter(Boolean).join(' ');
  }
  if (complaint && !complaint.value.trim()) {
    complaint.value = String(entry?.reason || entry?.detail || '');
  }
};

function nRenderQueueSearch(query = '') {
  queueTablePage = 1;
  nRenderQueueTablePage(query);
}

function nViewQueueEntry(entry) {
  const record = entry && typeof entry === 'object' ? entry : null;
  if (!record) {
    showToast('Queue item details are unavailable.', 'error');
    return;
  }

  const modal = document.getElementById('queueDetailModal');
  const body = document.getElementById('queueDetailBody');
  if (!modal || !body) {
    if (record.student_id) {
      const studentNo = String(record.student_id).replace(/[^A-Za-z0-9\-_]/g, '');
      if (studentNo) {
        uhViewStudentHealthRecord(studentNo);
        return;
      }
    }
    showToast('Queue item details are unavailable.', 'error');
    return;
  }

  const patientName = [record.first_name, record.middle_name, record.last_name].filter(Boolean).join(' ') || 'Unknown patient';
  const submittedDate = record.created_at ? uhFormatDateTime(record.created_at) : (record.date ? uhFormatDate(record.date) : '—');
  const rows = [
    ['Patient', patientName],
    ['Patient type', uhPatientTypeLabel(record.patient_type)],
    ['Patient ID', record.student_id || '—'],
    ['Record type', record.type === 'visit' ? 'Walk-in' : 'Appointment'],
    ['Service type', record.service_type || (record.type === 'visit' ? 'Walk-in' : '—')],
    ['Date submitted', submittedDate],
    ['Campus', record.campus_name || '—'],
    ['Status', record.status || 'pending'],
    ['Reason for visit', record.type === 'visit' ? record.detail || '—' : record.reason || '—'],
    ['Overdue', record.is_overdue ? 'Yes' : 'No'],
  ];

  body.innerHTML = '<div class="uh-detail-grid">' + rows.map(([label, value]) => `
    <div class="uh-detail-label">${escapeHtml(label)}</div>
    <div class="uh-detail-value">${escapeHtml(String(value || '—'))}</div>
  `).join('') + '</div>';
  modal.classList.remove('hidden');

  if (record.student_id) {
    const studentNo = String(record.student_id).replace(/[^A-Za-z0-9\-_]/g, '');
    const button = document.getElementById('queueDetailHealthRecord');
    if (button) {
      button.style.display = studentNo ? 'inline-flex' : 'none';
      button.onclick = function () {
        if (studentNo) { uhViewStudentHealthRecord(studentNo); }
      };
    }
  }
}

function nConfirmAppt(id, entry) {
  window.nAcceptAppointment({ ...(entry || {}), id: Number(id) });
}

function fmtTimeForDisplay(raw) {
  return uhFormatTime(raw);
}

// -------------------------------------------------------
//  REFERRALS — campus-scoped nurse page
//  Refreshes whenever the user navigates back to the page so newly-created
//  referrals appear.
// -------------------------------------------------------
async function loadReferrals() {
  const tbody = document.getElementById('referralsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="7" class="no-data">Loading referrals…</td></tr>';
  const pagination = document.getElementById('n-referrals-pagination');
  if (pagination) { pagination.innerHTML = ''; }

  const status = document.getElementById('n-referral-status-filter')?.value || '';
  if (status === 'waiting_to_schedule') {
    try {
      const response = await fetch('/api/clinic/referrals/awaiting-schedule?limit=100', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await response.json();
      if (!response.ok || !data?.success) {
        throw new Error(data?.message || data?.error || 'Unable to load referrals waiting to be scheduled.');
      }
      currentNurseReferrals = data.referrals || [];
    } catch (error) {
      tbody.innerHTML = '<tr><td colspan="7" class="no-data">Unable to load referrals waiting to be scheduled.</td></tr>';
      uhToast(error.message || 'Unable to load referrals waiting to be scheduled.', 'error');
      return;
    }
    const table = document.querySelector('#page-referrals-coord table[data-uh-searchable]');
    nRenderNurseReferrals(table, document.querySelector('#page-referrals-coord [data-uh-table-search]')?.value || '');
    return;
  }
  const params = new URLSearchParams({ limit: '100', sort_order: nurseReferralSortOrder });
  params.set('_', String(Date.now()));
  if (status) params.set('status', status);
  try {
    const response = await fetch(`/api/clinic/nurse/referrals?${params.toString()}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      cache: 'no-store',
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data?.success) {
      throw new Error(data?.message || data?.error || 'Unable to load referrals.');
    }
    currentNurseReferrals = data.records || [];
  } catch (error) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">Unable to load referrals.</td></tr>';
    uhToast(error.message || 'Unable to load referrals.', 'error');
    return;
  }
  if (!currentNurseReferrals.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">No referrals found.</td></tr>';
    const count = document.querySelector('#page-referrals-coord [data-uh-table-count]');
    if (count) { count.textContent = '0 referrals'; }
    return;
  }

  const table = document.querySelector('#page-referrals-coord table[data-uh-searchable]');
  nRenderNurseReferrals(table, document.querySelector('#page-referrals-coord [data-uh-table-search]')?.value || '');
}

let nurseReferralSortOrder = 'desc';

function nToggleReferralTimeSort() {
  nurseReferralSortOrder = nurseReferralSortOrder === 'asc' ? 'desc' : 'asc';
  const indicator = document.getElementById('n-referrals-sort-indicator');
  if (indicator) { indicator.textContent = nurseReferralSortOrder === 'asc' ? '↑' : '↓'; }
  loadReferrals();
}

function nRenderNurseReferrals(table, query = '') {
  const tbody = document.getElementById('referralsTableBody');
  if (!tbody) { return; }

  const normalizedQuery = String(query).trim().toLowerCase();
  const matchingReferrals = currentNurseReferrals.filter((referral) => {
    const student = referral.student || {};
    const patientName = [student.last_name, student.first_name].filter(Boolean).join(', ')
      || student.student_id || '';
    const referredTo = referral.referred_to_name
      || (referral.to_campus && referral.to_campus.name) || '';
    const searchableText = [
      patientName,
      student.student_id,
      referredTo,
      referral.route_to,
      referral.reason,
      referral.status,
      referral.created_at,
    ].filter(Boolean).join(' ').toLowerCase();
    return !normalizedQuery || searchableText.includes(normalizedQuery);
  });
  const count = table?.closest('.uh-card')?.querySelector('[data-uh-table-count]');

  if (count) {
    count.textContent = normalizedQuery
      ? `${matchingReferrals.length} match${matchingReferrals.length === 1 ? '' : 'es'}`
      : `${matchingReferrals.length} referral${matchingReferrals.length === 1 ? '' : 's'}`;
  }

  if (!matchingReferrals.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="no-data">${normalizedQuery
      ? `No matches for “${escapeHtml(query)}”.`
      : 'No referrals found.'}</td></tr>`;
    const pagination = document.getElementById('n-referrals-pagination');
    if (pagination) { pagination.innerHTML = ''; }
    return;
  }

  uhPaginateTable('referralsTableBody', matchingReferrals, nRenderNurseReferralRow, 7, 'n-referrals-pagination', 10);
}

function nRenderNurseReferralRow(referral) {
  const student = referral.student || {};
  const last = student.last_name || '';
  const first = student.first_name || '';
  const patientName = (last && first) ? `${last}, ${first}` : (student.student_id || '—');
  const referredTo = referral.referred_to_name
    || (referral.to_campus && referral.to_campus.name) || '—';
  const date = uhFormatDateTime(referral.created_at);
  const waitingToSchedule = document.getElementById('n-referral-status-filter')?.value === 'waiting_to_schedule';
  const status = waitingToSchedule ? 'Waiting to schedule' : (referral.status || 'pending');
  const statusClass = ({
    pending: 'waiting', accepted: 'in-progress',
    completed: 'completed', cancelled: 'cancelled', rejected: 'cancelled',
  })[referral.status || 'pending'] || 'waiting';

  // Route-to badge: green for physician, blue for dentist.
  // Legacy referrals without route_to fall back to a neutral badge.
  const routeTo = referral.route_to || '';
  const routeClass = routeTo === 'physician' ? 'in-progress'
    : routeTo === 'dentist' ? 'completed' : 'waiting';
  const routeLabel = routeTo ? routeTo.charAt(0).toUpperCase() + routeTo.slice(1) : '—';
  return `<tr>
    <td>${escapeHtml(date)}</td>
    <td>${escapeHtml(patientName)}</td>
    <td>${escapeHtml(referredTo)}</td>
    <td><span class="uh-badge ${routeClass}">${escapeHtml(routeLabel)}</span></td>
    <td>${escapeHtml(referral.reason || '—')}</td>
    <td><span class="uh-badge ${statusClass}">${escapeHtml(status)}</span></td>
    <td>
      <button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nViewReferral(${Number(referral.id)})">Details</button>
      <button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nViewReferralForm(${Number(referral.id)})">View Form</button>
    </td>
  </tr>`;
}

function nViewReferralForm(id) {
  let overlay = document.getElementById('clinicReferralFormViewer');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'clinicReferralFormViewer';
    overlay.className = 'uh-modal-overlay';
    overlay.setAttribute('role', 'presentation');
    overlay.style.zIndex = '1200';
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) nCloseReferralForm();
    });
    overlay.innerHTML = `
      <section class="uh-modal" role="dialog" aria-modal="true" aria-labelledby="clinicReferralFormTitle"
        style="display:flex;flex-direction:column;width:min(1100px,96vw);height:92vh;max-height:920px;">
        <header class="uh-modal-header">
          <div class="uh-modal-title" id="clinicReferralFormTitle"><i class="fa fa-file-medical"></i> Referral Form</div>
          <button class="uh-modal-close" type="button" onclick="nCloseReferralForm()" aria-label="Close">&times;</button>
        </header>
        <iframe title="Saved referral form" style="display:block;flex:1;width:100%;min-height:0;border:0;background:#f3f4f6;"></iframe>
      </section>`;
    document.body.appendChild(overlay);
  }

  const frame = overlay.querySelector('iframe');
  if (!frame) {
    uhToast('Unable to open the referral form viewer.', 'error');
    return;
  }
  frame.src = `/clinic/referrals/${Number(id)}/form?embedded=1`;
  overlay.classList.remove('hidden');
}

function nCloseReferralForm() {
  const overlay = document.getElementById('clinicReferralFormViewer');
  if (!overlay) return;
  overlay.classList.add('hidden');
  const frame = overlay.querySelector('iframe');
  if (frame) frame.src = 'about:blank';
}

// -------------------------------------------------------
//  REFERRAL DETAIL MODAL
//  Fetches a single referral and renders it in the detail modal.
//  The modal markup is in nurse.blade.php; we only populate + open it.
// -------------------------------------------------------
async function nViewReferral(id) {
  const overlay = document.getElementById('referralDetailModal');
  const body = document.getElementById('referralDetailBody');
  if (!overlay || !body) return;

  body.innerHTML = '<p style="text-align:center;color:var(--uh-muted);padding:24px;">Loading…</p>';
  overlay.classList.remove('hidden');

  const res = await fetch(`/api/clinic/nurse/referrals/${id}`, {
    headers: { 'Accept': 'application/json' },
  }).then((r) => r.json()).catch(() => ({ error: 'offline' }));

  if (!res || !res.referral) {
    body.innerHTML = `<p style="color:var(--uh-danger-text);">${escapeHtml((res && res.error) || 'Failed to load referral.')}</p>`;
    return;
  }

  const r = res.referral;
  const stu = r.student || {};
  const studentName = [stu.first_name, stu.last_name].filter(Boolean).join(' ') || stu.student_id || '—';
  const fromCampus = r.from_campus ? r.from_campus.name : '—';
  const toCampus = r.to_campus ? r.to_campus.name : '—';
  const refBy = r.referred_by
    ? [r.referred_by.first_name, r.referred_by.last_name].filter(Boolean).join(' ')
    : '—';
  const routeLabel = r.route_to
    ? r.route_to.charAt(0).toUpperCase() + r.route_to.slice(1)
    : '—';
  const routeClass = r.route_to === 'physician' ? 'in-progress'
    : r.route_to === 'dentist' ? 'completed' : 'waiting';
  const statusClass = ({
    pending: 'waiting', accepted: 'in-progress',
    completed: 'completed', cancelled: 'cancelled', rejected: 'cancelled',
  })[r.status] || 'waiting';

  const rows = [
    ['Referral No', r.referral_no || '—'],
    ['Date Created', r.created_at ? String(r.created_at).substring(0, 10) : '—'],
    ['Patient', `${studentName} (${stu.student_id || '—'})`],
    ['From Campus', fromCampus],
    ['To Campus', toCampus],
    ['Referred By', refBy + (r.referred_by && r.referred_by.role ? ` (${r.referred_by.role})` : '')],
    ['Route To', `<span class="uh-badge ${routeClass}">${escapeHtml(routeLabel)}</span>`],
    ['Referred To', r.referred_to_name || '—'],
    ['Specialty', r.route_to === 'physician' ? 'Physician' : r.route_to === 'dentist' ? 'Dentist' : '—'],
    ['Urgency', r.urgency ? r.urgency.charAt(0).toUpperCase() + r.urgency.slice(1) : '—'],
    ['Status', `<span class="uh-badge ${statusClass}">${escapeHtml(r.status || 'pending')}</span>`],
    ['Reason', r.reason || '—'],
    ['Diagnosis', r.diagnosis || '—'],
    ['History', r.history || '—'],
    ['Management Given', r.management_given || '—'],
    ['Feedback', r.feedback || '—'],
    ['Accepted At', r.accepted_at || '—'],
    ['Completed At', r.completed_at || '—'],
  ];

  body.innerHTML = '<div class="uh-detail-grid">' + rows.map(([label, value]) => `
    <div class="uh-detail-row">
      <div class="uh-detail-label">${escapeHtml(label)}</div>
      <div class="uh-detail-value">${value}</div>
    </div>`).join('') + '</div>';
}

function nCloseQueueDetail() {
  const overlay = document.getElementById('queueDetailModal');
  if (overlay) overlay.classList.add('hidden');
}

function nCloseReferralDetail() {
  const overlay = document.getElementById('referralDetailModal');
  if (overlay) overlay.classList.add('hidden');
}

// -------------------------------------------------------
//  NEW REFERRAL MODAL
//  Opens the create-referral modal and prefills the
//  "Referred to" field based on the chosen route_to
//  (physician → default physician, dentist → default dentist).
// -------------------------------------------------------
function nOpenNewReferral() {
  const overlay = document.getElementById('newReferralModal');
  if (!overlay) return;
  overlay.classList.remove('hidden');
  // Reset form
  const form = document.getElementById('newReferralForm');
  if (form) {
    form.reset();
    document.getElementById('nrf_route_physician').checked = true;
    nApplyRouteToPrefill('physician');
  }
}

function nCloseNewReferral() {
  const overlay = document.getElementById('newReferralModal');
  if (overlay) overlay.classList.add('hidden');
}

// Cache the signing-personnel so we only fetch once.
let _signingPersonnelCache = null;
let _signingPersonnelRequest = null;
async function loadSigningPersonnel() {
  if (!_signingPersonnelCache) {
    if (!_signingPersonnelRequest) {
      _signingPersonnelRequest = (async () => {
        const user = uhGetUser();
        const endpoint = user?.role === 'nurse_head'
          ? '/nurse-head/signing-personnel'
          : '/nurse/signing-personnel';
        const res = await uhGet(endpoint);
        if (res && res.personnel) {
          _signingPersonnelCache = res.personnel;
        } else {
          _signingPersonnelCache = { physician: [], dentist: [], nurse: [] };
          showToast(res?.message || 'Unable to load physician and dentist names.', 'error');
        }
        return _signingPersonnelCache;
      })().finally(() => {
        _signingPersonnelRequest = null;
      });
    }
    await _signingPersonnelRequest;
  }

  document.querySelectorAll('[data-signing-role]').forEach(select => {
    const role = select.dataset.signingRole;
    const personnel = _signingPersonnelCache[role] || [];
    const selectedValue = select.value;
    const placeholder = select.options[0] || document.createElement('option');
    select.replaceChildren(placeholder);

    if (personnel.length === 0) {
      placeholder.value = '';
      placeholder.textContent = `No active ${role} found`;
      select.disabled = true;
      return;
    }

    placeholder.value = '';
    placeholder.textContent = `Select ${role}`;
    select.disabled = false;

    personnel.forEach(person => {
      const name = person.name || [
        person.first_name,
        person.middle_name,
        person.last_name,
      ].filter(Boolean).join(' ');
      const suffix = role === 'dentist' ? ', D.M.D' : role === 'physician' ? ', M.D' : '';
      const displayName = suffix && !name.endsWith(suffix) ? `${name}${suffix}` : name;
      const option = document.createElement('option');

      option.value = select.id === 'dr_dentist_name'
        ? String(person.id)
        : displayName;
      option.textContent = displayName;
      option.dataset.license = person.license_no || '';
      option.dataset.designation = person.designation || '';
      select.appendChild(option);
    });
    select.value = selectedValue;
  });

  return _signingPersonnelCache;
}

function updateSigningPersonnelPreview(select) {
  const licenseFieldIds = {
    dr_dentist_name: 'dr_dentist_license',
    mc_sig_name: 'mc_sig_license',
    dc_sig_name: 'dc_sig_license',
    rx_physician_name: 'rx_physician_lic',
    rxd_physician_name: 'rxd_physician_lic',
    rf_physician_name: 'rf_physician_lic',
    rfd_physician_name: 'rfd_physician_lic',
    lab_physician_name: 'lab_physician_lic',
  };
  const licenseField = document.getElementById(licenseFieldIds[select.id]);
  const designationFieldIds = {
    mc_sig_name: 'mc_sig_title',
    dc_sig_name: 'dc_sig_title',
    rx_physician_name: 'rx_physician_role',
    rxd_physician_name: 'rxd_physician_role',
    rf_physician_name: 'rf_physician_role',
    rfd_physician_name: 'rfd_physician_role',
  };
  const designationField = document.getElementById(designationFieldIds[select.id]);

  if (licenseField) {
    licenseField.value = select.selectedOptions[0]?.dataset.license || '';
  }
  if (designationField) {
    const roleLabel = select.dataset.signingRole === 'dentist' ? 'School Dentist' : 'School Physician';
    designationField.value = select.selectedOptions[0]?.dataset.designation || roleLabel;
  }
}

// Apply the cached signing personnel as a suggested value for the
// "referred_to_name" field. Called when the route_to radio changes
// or when the modal opens.
function nApplyRouteToPrefill(routeTo) {
  const input = document.getElementById('nrf_referred_to');
  if (!input) return;
  const cache = _signingPersonnelCache || {};
  const list = (routeTo === 'dentist') ? (cache.dentist || []) : (cache.physician || []);
  if (list && list.length > 0) {
    const p = list[0];
    const name = [p.first_name, p.last_name].filter(Boolean).join(' ');
    const specialtyInput = document.getElementById('nrf_specialty');
    if (specialtyInput) {
      specialtyInput.value = routeTo === 'dentist' ? 'Dentist' : 'Physician';
    }
    if (name) {
      // Add ", M.D" / ", D.M.D" suffix based on role when missing
      const suffix = routeTo === 'dentist' ? ', D.M.D' : ', M.D';
      input.value = name.endsWith(suffix) ? name : `${name}${suffix}`;
    }
  }
}

// Submit the new-referral form to POST /api/clinic/referrals.
async function nCreateReferral(event) {
  if (event) event.preventDefault();
  const form = document.getElementById('newReferralForm');
  if (!form) return;

  const routeTo = (form.querySelector('input[name="route_to"]:checked') || {}).value || '';
  const payload = {
    student_no: (document.getElementById('nrf_student_id')?.value || '').trim(),
    route_to: routeTo,
    referred_to_name: (document.getElementById('nrf_referred_to')?.value || '').trim(),
    specialty: (document.getElementById('nrf_specialty')?.value || '').trim(),
    reason: (document.getElementById('nrf_reason')?.value || '').trim(),
    urgency: (document.getElementById('nrf_urgency')?.value || 'routine'),
    diagnosis: (document.getElementById('nrf_diagnosis')?.value || '').trim() || null,
    notes: (document.getElementById('nrf_notes')?.value || '').trim() || null,
  };

  if (!payload.student_no) { showToast('Student ID is required', 'error'); return; }
  if (!payload.route_to)   { showToast('Please choose a route (Physician or Dentist)', 'error'); return; }
  if (!payload.reason)     { showToast('Reason is required', 'error'); return; }

  // Mirror the staff/campus params the rest of the JS uses by including
  // them as query string on the fetch.
  const params = new URLSearchParams({
    staff_campus_id: currentCampusId || '',
    staff_id: currentStaffId || '',
  });
  const res = await fetch(`/api/clinic/referrals?${params.toString()}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(payload),
  }).then((r) => r.json()).catch(() => ({ error: 'offline' }));

  if (res && (res.success || res.id)) {
    showToast('Referral created', 'success');
    nCloseNewReferral();
    loadReferrals();
  } else {
    const msg = (res && (res.error || (res.errors && res.errors.route_to && res.errors.route_to[0])))
      || 'Failed to create referral';
    showToast(msg, 'error');
  }
}

// Page-enter observer: re-fire the loader when the user navigates to
// the Coordinate Referrals page after creating a new referral.
function setupNursePageObserver() {
  const map = {
    'page-referrals-coord': loadReferrals,
  };
  document.querySelectorAll('.uh-page').forEach((page) => {
    if (!map[page.id]) return;
    new MutationObserver(() => {
      if (page.classList.contains('active')) map[page.id]();
    }).observe(page, { attributes: true, attributeFilter: ['class'] });
  });
  const active = document.querySelector('.uh-page.active');
  if (active && map[active.id]) map[active.id]();
}

// Reuses the global showToast in nurse.js. If the page is loaded without
// the toasts div (e.g. an embedded view), the showToast guard handles it.

function updateQueue() {
  const badge = document.getElementById('queueBadge');
  const count = document.getElementById('queueCount');
  if (badge) badge.textContent = `${String(queueCurrent).padStart(2,'0')}/${String(queueTotal).padStart(2,'0')}`;
  if (count) count.textContent = String(queueWaiting).padStart(4,'0');
}

function incrementQueue() {
  queueWaiting++;
  if (queueWaiting > 99) queueWaiting = 0;
  updateQueue();
}

function renderPatientQueue(items) {
  const container = document.getElementById('recentVisitsList');
  if (!container) return;

  if (!items || items.length === 0) {
    container.innerHTML = '<p class="student-empty-state">No patients currently in the queue.</p>';
    return;
  }

  /* Paginate — show 5 queue items per page */
  const PAGE_SIZE = 5;
  let currentPage = 0;
  const totalPages = Math.ceil(items.length / PAGE_SIZE);

  function fmtTime(raw) {
    return raw ? uhFormatTime(raw) : '';
  }

  function render() {
    var slice = items.slice(currentPage * PAGE_SIZE, (currentPage + 1) * PAGE_SIZE);
    var html = slice.map(function(item) {
      var name = [item.first_name, item.last_name].filter(Boolean).join(' ') || 'Unknown patient';
      var initial = escapeHtml((item.first_name || item.last_name || 'U')[0].toUpperCase());
      var studentId = item.student_id ? 'ID ' + item.student_id : 'Patient visit';
      var reason = item.detail || (item.type === 'appointment' ? 'Appointment' : 'Clinic visit');
      var time = fmtTime(item.time);
      var detail = [reason, time].filter(Boolean).join(' · ');
      var status = item.status === 'accepted' ? 'Accepted' : item.status === 'confirmed' ? 'Confirmed' : 'Waiting';
      var statusClass = item.status === 'confirmed' || item.status === 'accepted' ? 'status-active' : 'status-waiting';
      var safeStudentId = String(item.student_id || '').replace(/[^A-Za-z0-9\-_]/g, '');
      return '<article class="nurse-recent-visit">' +
        '<div class="nurse-recent-visit-avatar" aria-hidden="true">' + initial + '</div>' +
        '<div class="nurse-recent-visit-main">' +
          '<strong class="nurse-recent-visit-name">' + escapeHtml(name) + '</strong>' +
          '<span class="nurse-recent-visit-id">' + escapeHtml(studentId) + '</span>' +
        '</div>' +
        '<div class="nurse-recent-visit-detail">' +
          '<span class="nurse-recent-visit-reason">' + escapeHtml(reason) + '</span>' +
          (time ? '<span class="nurse-recent-visit-time"><i class="fa fa-clock" aria-hidden="true"></i>' + escapeHtml(time) + '</span>' : '') +
        '</div>' +
        '<span class="nurse-recent-visit-status ' + statusClass + '">' + status + '</span>' +
        (safeStudentId ? '<button type="button" class="nurse-recent-visit-action" onclick="uhViewStudentHealthRecord(\'' + safeStudentId + '\')"><i class="fa fa-file-medical" aria-hidden="true"></i><span>Health record</span></button>' : '') +
      '</article>';
    }).join('');

    /* Pagination controls */
    if (totalPages > 1) {
      html += '<div class="nurse-recent-visit-pagination">' +
        '<button type="button" class="nurse-recent-visit-page" aria-label="Previous visits" ' + (currentPage === 0 ? 'disabled' : '') +
          ' onclick="window._rvPage(' + (currentPage - 1) + ')"><i class="fa fa-chevron-left"></i></button>' +
        '<span>Page ' + (currentPage + 1) + ' of ' + totalPages + '</span>' +
        '<button type="button" class="nurse-recent-visit-page" aria-label="Next visits" ' + (currentPage >= totalPages - 1 ? 'disabled' : '') +
          ' onclick="window._rvPage(' + (currentPage + 1) + ')"><i class="fa fa-chevron-right"></i></button>' +
      '</div>';
    }

    container.innerHTML = html;
  }

  window._rvPage = function(page) {
    currentPage = page;
    render();
  };

  render();
}

async function renderAlerts() {
  const container = document.getElementById('alertsList');
  if (!container) return;
  // Fetch alerts from student health records
  container.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px;font-size:0.85rem">Loading alerts...</p>';
}

function renderWeekChart() {
  const chart  = document.getElementById('weekChart');
  const labels = document.getElementById('weekLabels');
  if (!chart) return;
  const days  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const values= [8, 13, 6, 10, 14, 5, 2];
  const max   = Math.max(...values);
  chart.innerHTML = values.map((v, i) => `
    <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:4px;">
      <span style="font-size:0.68rem;font-weight:600;color:var(--primary)">${v}</span>
      <div style="width:100%;background:${i===4?'var(--primary)':'rgba(42,122,111,0.2)'};
        border-radius:6px 6px 0 0;height:${Math.round((v/max)*100)}px;transition:all 0.3s;cursor:pointer"
        title="${days[i]}: ${v} visits"></div>
    </div>`).join('');
  if (labels) labels.innerHTML = days.map(d => `<span style="flex:1;text-align:center">${d}</span>`).join('');
}

/* Override showPage to handle nurse-specific title map */
function showPage(pageId, el) {
  document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
  const page = document.getElementById('page-' + pageId);
  if (page) page.classList.add('active');

  const titleMap = {
    'dashboard':       'Overview',
    'patient':         'Patient',
    'appointment':     'Appointment',
    'queue':           'Queue',
    'consultation':    'Consultation',
    'form-xray':       'X-Ray Request Form',
    'form-referral':   'Referral Form',
    'form-referral-dentist':'Referral Form — Dentist',
    'form-rx-physician':'Prescription — Physician',
    'form-rx-dentist': 'Prescription — Dentist',
    'form-lab':        'Laboratory Request Form',
    'form-lactating':  'Lactating Mother Form',
    'form-bf':         'Breastfeeding Agreement',
    'med-cert':        'Medical Certificate',
    'med-records':     'Medical Records',
    'dental-record':   'Dental Record',
    'dental-cert':     'Dental Certificate',
    'staffs':          'Staffs',
    'reports':         'Reports',
  };
  const titleEl = document.getElementById('pageTitle');
  if (titleEl) titleEl.textContent = titleMap[pageId] || pageId;

  if (el) {
    document.querySelectorAll('.nav-item').forEach(n => { if (!n.querySelector('.chevron')) n.classList.remove('active'); });
    document.querySelectorAll('.nav-sub-item').forEach(n => n.classList.remove('active'));
    if (el.classList.contains('nav-sub-item')) el.classList.add('active');
    else el.classList.add('active');
  }
}

function toggleSub(menuId, btn) {
  const menu = document.getElementById(menuId);
  if (!menu) return;
  const isOpen = menu.classList.contains('open');
  document.querySelectorAll('.nav-sub').forEach(m => m.classList.remove('open'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('open'));
  if (!isOpen) { menu.classList.add('open'); btn.classList.add('open'); }
}

function doLogout() {
  sessionStorage.removeItem('clinic_user');
  window.location.href = '/login';
}

// Clock
function updateClock() {
  const now = new Date();
  const dateEl = document.getElementById('todayDate');
  if (dateEl) dateEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(now);
}
setInterval(updateClock, 1000);
updateClock();

// ============================================================
// STUDENT SEARCH & AUTO-FILL FOR FORMS
// ============================================================

function getFormSearchInput(formType) {
  const inputIds = {
    'prescription-dentist': 'prescription_dentist_search',
    'referral-dentist': 'referral_dentist_search',
  };

  return document.getElementById(inputIds[formType] || `${formType}_search`);
}

let currentStudentData = null;
const currentStudentDataByForm = {};
const generatedFormTypeBySearchType = {
  medcert: 'medical_certificate',
  dentalcert: 'dental_certificate',
  prescription: 'physician_prescription',
  'prescription-dentist': 'dentist_prescription',
  referral: 'physician_referral',
  'referral-dentist': 'dentist_referral',
  lab: 'laboratory_request',
  xray: 'xray',
  lactating: 'lactating_accommodation',
  breastfeeding: 'breastfeeding_agreement',
  'dental-record': 'dental_record',
};

function setCurrentStudentForForm(formType, student) {
  currentStudentData = student;
  currentStudentDataByForm[formType] = student;

  const generatedFormType = generatedFormTypeBySearchType[formType];
  if (generatedFormType) {
    currentStudentDataByForm[generatedFormType] = student;
  }
}

async function searchStudentForForm(studentId, formType) {
  if (!studentId) return;

  const res = await apiFetch(`get_student_for_form&student_id=${encodeURIComponent(studentId)}`);
  if (res.student) {
    setCurrentStudentForForm(formType, res.student);
    autoFillForm(formType, res.student, res.medications, res.appointments);
    showToast(`Loaded data for ${res.student.first_name} ${res.student.last_name}`, 'success');
  } else {
    showToast(res.error || 'Student not found', 'error');
  }
}

function formatStudentVitals(student) {
  const vitals = student?.latest_vitals || {};
  const healthRecord = student || {};
  const values = [
    ['BP', vitals.bp || healthRecord.bp],
    ['T', vitals.temp || healthRecord.temp],
    ['PR', vitals.pulse || healthRecord.pr],
    ['RR', vitals.rr],
    ['O2', vitals.o2_sat],
  ].filter(([, value]) => value !== null && value !== undefined && value !== '');

  return values.map(([label, value]) => `${label} ${value}`).join(', ');
}

function autoFillForm(formType, student, medications = [], appointments = []) {
  console.log('[autoFillForm] Called with:', { formType, student: student?.student_id, medicationsCount: medications?.length, appointmentsCount: appointments?.length });
  const latestVitals = formatStudentVitals(student);

  // Common fields for all forms
  const commonFields = {
    // Student info
    'mc_name': `${student.first_name} ${student.last_name}`,
    'mc_surname':    student.last_name   || '',
    'mc_firstname':  student.first_name  || '',
    'mc_middlename': student.middle_name || '',
    'mc_id': student.student_id,
    'mc_age': student.age || calculateAge(student.date_of_birth),
    'mc_sex': student.sex,
    'mc_civil': student.civil_status || '',
    'mc_address': student.address || '',
    'mc_dept': student.department,
    'mc_date': new Date().toISOString().split('T')[0],
    'mc_bp': student.bp || '',
    'mc_temp': student.temp || '',
    'mc_pulse': student.pr || '',
    'mc_rr': '',
    'mc_o2': '',
    'mc_weight': student.weight_kg || '',
    'mc_complaint': '',
    'mc_assessment': '',
    'mc_treatment': '',
    'mc_recommendation': '',
    'mc_disposition': '',
    'mc_nurse': '',
    'mc_sig_title': 'School Physician',

    'mrf_student_id': student.student_id || '',
    'mrf_student_number': student.student_id || '',
    'mrf_last_name': student.last_name || '',
    'mrf_first_name': student.first_name || '',
    'mrf_middle_name': student.middle_name || '',
    'mrf_birth_date': student.date_of_birth || '',
    'mrf_age': student.age || calculateAge(student.date_of_birth),
    'mrf_course': student.department?.match(/\(([^)]+)\)/)?.[1] || student.department || '',
    'mrf_gender': student.sex || '',
    'mrf_contact': student.contact || '',
    'mrf_address': student.address || '',
    'mrf_visit_date': uhManilaDateKey(new Date()),
    'mrf_visit_time': new Intl.DateTimeFormat('en-GB', {
      timeZone: 'Asia/Manila',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23',
    }).format(new Date()),
    'mrf_bp': student.latest_vitals?.bp || student.bp || '',
    'mrf_temp': student.latest_vitals?.temp || student.temp || '',
    'mrf_pulse': student.latest_vitals?.pulse || student.pr || '',

    'dr_student_id': student.student_id || '',
    'dr_last_name': student.last_name || '',
    'dr_first_name': student.first_name || '',
    'dr_middle_name': student.middle_name || '',
    'dr_age': student.age || calculateAge(student.date_of_birth),
    'dr_course': [student.course_year, student.section].filter(Boolean).join(' / '),
    'dr_department': student.department || '',
    'dr_contact': student.contact || '',
    'dr_address': student.address || '',
    'dr_designation': '',

    'cr_name': `${student.first_name} ${student.last_name}`,
    'cr_id': student.student_id,
    'cr_age': student.age || calculateAge(student.date_of_birth),
    'cr_sex': student.sex,
    'cr_dept': student.department,
    'cr_date': uhManilaDateKey(new Date()),
    'cr_time': uhFormatTime(new Date()),
    'cr_bp': student.bp || '',
    'cr_temp': student.temp || '',
    'cr_pulse': student.pr || '',
    'cr_rr': '',
    'cr_complaint': '',
    'cr_hpi': '',
    'cr_pe': '',
    'cr_assessment': '',
    'cr_plan': '',
    'cr_meds': medications.map(m => `${m.name} ${m.dosage} ${m.schedule}`).join('; '),
    'cr_disposition': '',
    'cr_nurse': '',

    'xr_name': `${student.first_name} ${student.last_name}`,
    'xr_id': student.student_id,
    'xr_course': [student.department, student.course_year].filter(Boolean).join(' · '),
    'xr_date': new Date().toISOString().split('T')[0],
    'xr_type': '',
    'xr_priority': 'Routine',
    'xr_reason': '',
    'xr_physician': '',

    'lb_name': `${student.first_name} ${student.last_name}`,
    'lb_id': student.student_id,
    'lb_age': student.age || calculateAge(student.date_of_birth),
    'lb_sex': student.sex,
    'lb_date': new Date().toISOString().split('T')[0],
    'lb_diagnosis': '',
    'lb_priority': 'Routine',
    'lb_notes': '',
    'lb_nurse': '',

    'rf_name': `${student.first_name} ${student.last_name}`,
    'rf_id': student.student_id,
    'rf_age': student.age || calculateAge(student.date_of_birth),
    'rf_address': student.address || '',
    'rf_chief_complaint': '',
    'rf_vital_signs': latestVitals,
    'rf_remarks': '',
    'rf_dept': student.department,
    'rf_date': new Date().toISOString().split('T')[0],
    'rf_to': '',
    'rf_specialty': '',
    'rf_diagnosis': '',
    'rf_history': student.allergy_detail || student.accident_detail || student.hospitalization_detail || student.surgical_detail || student.family_history || '',
    'rf_reason': '',
    'rf_nurse': '',
    'rf_contact': '',
    // rf_physician_name / role / lic intentionally NOT auto-filled — defaulted to Cyrille in HTML

    // Laboratory request form
    'lab_name': `${student.first_name || ''} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name || ''}`.replace(/\s+/g, ' ').trim(),
    'lab_id': student.student_id,
    'lab_age': student.age || calculateAge(student.date_of_birth),
    'lab_sex': student.sex,
    'lab_dob': student.date_of_birth || '',
    'lab_address': student.address || '',
    'lab_date_requested': new Date().toISOString().split('T')[0],
    'lab_physician_ptr': '',

    'rfd_name': `${student.first_name} ${student.last_name}`,
    'rfd_id': student.student_id,
    'rfd_age': student.age || calculateAge(student.date_of_birth),
    'rfd_address': student.address || '',
    'rfd_chief_complaint': '',
    'rfd_vital_signs': latestVitals,
    'rfd_remarks': '',
    // rfd_physician_name / role / lic intentionally NOT auto-filled — defaulted to Rhizza in HTML

    'rxp_name': `${student.first_name} ${student.last_name}`,
    'rxp_age': student.age || calculateAge(student.date_of_birth),
    'rxp_sex': student.sex,
    'rxp_date': new Date().toISOString().split('T')[0],
    'rxp_id': student.student_id,
    'rxp_dept': student.department,
    'rxp_diagnosis': '',
    'rxp_allergies': student.allergy_detail || 'None',
    'rxp_notes': '',
    'rxp_refills': '',
    'rxp_physician_name': '',
    'rxp_prc': '',
    'rxp_lic': '',
    'rxp_ptr': '',

    'rxd_name': `${student.first_name} ${student.last_name}`,
    'rxd_age': student.age || calculateAge(student.date_of_birth),
    'rxd_sex': student.sex,
    'rxd_date': new Date().toISOString().split('T')[0],
    'rxd_id': student.student_id,
    'rxd_diagnosis': '',
    'rxd_tooth': '',
    'rxd_allergies': student.allergy_detail || 'None',
    'rxd_notes': '',
    'rxd_next_appt': '',
    'rxd_dentist_name': '',
    'rxd_prc': '',
    'rxd_ptr': '',

    'rxd_name': `${student.first_name} ${student.last_name}`,
    'rxd_full_name': `${student.first_name || ''} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name || ''}`.replace(/\s+/g, ' ').trim(),
    'rxd_id': student.student_id,
    'rxd_age': student.age || calculateAge(student.date_of_birth),
    'rxd_sex': student.sex,
    'rxd_address': student.address || '',
    'rxd_date': new Date().toISOString().split('T')[0],
    'rxd_notes': '',
    // rxd_physician_name / role / lic / ptr / date intentionally NOT touched here —
    // the dentist form pre-fills "Rhizza Faye T. Barcenilla, D.M.D" / "School Dentist"
    // and the user can edit them per session. Searching a student must not overwrite them.

    'rx_name': `${student.first_name} ${student.last_name}`,
    'rx_full_name': `${student.first_name || ''} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name || ''}`.replace(/\s+/g, ' ').trim(),
    'rx_id': student.student_id,
    'rx_age': student.age || calculateAge(student.date_of_birth),
    'rx_sex': student.sex,
    'rx_address': student.address || '',
    'rx_date': new Date().toISOString().split('T')[0],
    'rx_notes': '',
    'rx_physician_role': 'School Physician',

    'dc_name': `${student.first_name} ${student.last_name}`,
    'dc_surname':    student.last_name   || '',
    'dc_firstname':  student.first_name  || '',
    'dc_middlename': student.middle_name || '',
    'dc_full_name':  `${student.first_name || ''} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name || ''}`.replace(/\s+/g, ' ').trim(),
    'dc_age': student.age || calculateAge(student.date_of_birth),
    'dc_sex': student.sex,
    'dc_civil': student.civil_status || '',
    'dc_address': student.address || '',
    'dc_date': new Date().toISOString().split('T')[0],
    'dc_purpose': '',
    'dc_sig_title': 'School Dentist',
    'dc_sig_lic': '',
    'dc_sig_ptr': '',

    'lac_name': `${student.first_name} ${student.last_name}`,
    'lac_dept': student.department,
    'lac_jobtitle': 'Student',
    'lac_email': student.email,
    'lac_contact': student.contact,
    'lm_name': `${student.first_name} ${student.last_name}`,
    'lm_dept': student.department,
    'lm_jobtitle': 'Student',
    'lm_email': student.email,
    'lm_contact': student.contact,
    'lac_child_dob': '',
    'lac_begin_date': new Date().toISOString().split('T')[0],
    'lac_end_date': '',
    'lac_desc1': '',
    'lac_desc2': '',
    'lac_req_by': '',
    'lac_rec_by': '',
    'lac_app_by': '',

    'bf_name': `${student.first_name} ${student.last_name}`,
    'bf_dept': student.department,
    'bf_jobtitle': 'Student',
    'bf_email': student.email,
    'bf_contact': student.contact,
    'bf_signature': '',
    'bf_date': new Date().toISOString().split('T')[0],

    'xc_name': `${student.first_name} ${student.last_name}`,
    'xc_course': `${student.course_year} - ${student.section}`,
    'xc_result': '',
    'xc_nurse': '',

    // Health record form fields
    'phr_last_name': student.last_name,
    'phr_first_name': student.first_name,
    'phr_middle_name': student.middle_name,
    'phr_course_year': student.course_year,
    'phr_age': student.age || calculateAge(student.date_of_birth),
    'phr_date_of_birth': student.date_of_birth || '',
    'phr_blood_type': student.blood_type || student.hr_blood_type || '',
    'phr_height_cm': student.height_cm || '',
    'phr_weight_kg': student.weight_kg || '',
    'phr_address': student.address || '',
    'phr_contact': student.contact || '',
    'phr_ec_name': student.ec_name || '',
    'phr_ec_relationship': student.ec_relationship || '',
    'phr_ec_address': student.ec_address || '',
    'phr_ec_contact': student.ec_contact || '',
    'phr_allergy_detail': student.allergy_detail || '',
    'phr_accident_detail': student.accident_detail || '',
    'phr_hospitalization_detail': student.hospitalization_detail || '',
    'phr_surgical_detail': student.surgical_detail || '',
    'phr_family_history': student.family_history || '',
    'phr_bp': student.bp || '',
    'phr_pr': student.pr || '',
    'phr_temp': student.temp || '',
    'phr_signature_name': `${student.first_name} ${student.last_name}`,
    'phr_sex': student.sex,
    'phr_civil_status': student.civil_status,
  };

  // Fill all matching fields
  let filledCount = 0;
  Object.entries(commonFields).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) {
      if (el.type === 'checkbox' || el.type === 'radio') {
        el.checked = value === true || value === 'true' || value === 1 || value === '1';
      } else if (el.tagName === 'SELECT') {
        el.value = value;
      } else {
        el.value = value;
      }
      filledCount++;
    }
  });
  console.log('[autoFillForm] Filled', filledCount, 'fields');

  // Re-render the form preview now that fields are populated.
  // triggerFormPreviewUpdate uses the correct render map (renderMedCert, renderConsult, etc.)
  triggerFormPreviewUpdate(formType);

  // Show alert if student has critical conditions
  if (student.allergy_detail && student.allergy_detail.toLowerCase() !== 'none') {
    showToast(`⚠ Allergy Alert: ${student.allergy_detail.substring(0, 100)}`, 'info');
  }
}

function calculateAge(dob) {
  if (!dob) return '';
  const birth = new Date(dob);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age;
}

function showToast(msg, type = 'success') {
  if (typeof uhToast === 'function') {
    uhToast(msg, type);
    return;
  }
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.className = `toast ${type}`;
  setTimeout(() => toast.classList.add('hidden'), 3000);
}

// ============================================================
// FORM & CERTIFICATE GENERATION SYSTEM
// ============================================================

function fmtDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function clearForm(formId) {
  const form = document.getElementById(formId);
  if (form) form.reset();
  updatePreview(formId);
}

function getFormData(formId) {
  const form = document.getElementById(formId);
  if (!form) return {};
  const data = {};
  const inputs = form.querySelectorAll('input, textarea, select');
  inputs.forEach(el => {
    if (!el.id || el.type === 'button' || el.type === 'submit' || el.type === 'reset') return;
    if (el.type === 'checkbox') {
      data[el.id] = el.checked;
    } else if (el.type === 'radio') {
      if (el.checked) data[el.name || el.id] = el.value;
    } else {
      data[el.id] = el.value;
    }
  });
  return data;
}

async function saveGeneratedForm(formId, formType, successMessage = 'Form saved successfully.') {
  const form = document.getElementById(formId);
  if (!form) {
    showToast('Could not find this form.', 'error');
    return;
  }

  const student = currentStudentDataByForm[formType];
  if (!student || !student.student_id) {
    showToast('Search for a student before saving this form.', 'error');
    return;
  }

  const response = await apiFetch('save_form', 'POST', {
    form_type: formType,
    student_no: student.student_id,
    form_data: getFormData(formId),
  });

  if (response && response.success) {
    showToast(successMessage, 'success');
    return;
  }

  const validationMessage = response && response.errors
    ? Object.values(response.errors).flat()[0]
    : null;
  showToast(validationMessage || response?.error || 'Could not save this form.', 'error');
}

function updatePreview(formId) {
  const form = document.getElementById(formId);
  if (!form) return;

  const formData = getFormData(formId);
  const previewContentId = `${formId}-preview-content`;
  const previewEl = document.getElementById(previewContentId);
  if (!previewEl) return;

  let htmlContent = '';

  if (formId.includes('dental-cert')) {
    htmlContent = renderDentalCertificate(formData);
  } else if (formId.includes('med-form')) {
    htmlContent = renderMedicalCertificate(formData);
  }

  if (htmlContent) {
    previewEl.innerHTML = htmlContent;
  }
}

function renderMedicalCertificate(data) {
  return `
    <div class="page">
      <div class="letterhead">
        <img class="logo" src="/clinic/images/sunn-logo.png" alt="logo" style="width:66px;"/>
        <div class="titles">
          <div class="republic">Republic of the Philippines</div>
          <div class="university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
          <div class="dept">MEDICAL AND DENTAL SERVICES OFFICE</div>
          <div class="address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>
        </div>
      </div>

      <div class="form-title">MEDICAL CERTIFICATE</div>
      <div class="doc-body">
        <p style="margin-bottom: 20px; line-height: 1.6;">This is to certify that <strong>${data.cert_name || data.mc_name || '___________________'}</strong>, Student ID <strong>${data.cert_id || data.mc_id || '___________'}</strong>, age ${data.cert_age || data.mc_age || '__'} of ${data.cert_dept || data.mc_dept || '______________'} has been examined on <strong>${fmtDate(data.cert_date || data.mc_date) || '__________________'}</strong> and found to be in <strong>${data.cert_condition || data.mc_condition || '______________'}</strong>.</p>

        <p style="margin-bottom: 20px;"><strong>Findings:</strong> ${data.cert_remarks || data.mc_remarks || data.mc_assessment || '___________________________________________________________________________'}</p>

        <p style="margin-bottom: 20px;"><strong>Purpose:</strong> ${data.cert_purpose || data.mc_purpose || '______________'}</p>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
          <div style="flex: 1;">
            <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px;"></div>
            <p style="font-size: 12px; margin: 0;">${data.cert_issuer || data.mc_nurse || '______________'}</p>
            <p style="font-size: 12px; margin: 0;">License No: ${data.cert_license || data.mc_license || '___________'}</p>
          </div>
          <div style="flex: 1; text-align: right;">
            <p style="font-size: 12px; margin: 0;">Date Issued: ${fmtDate(data.cert_date || data.mc_date) || '______________'}</p>
          </div>
        </div>
      </div>

      <div class="page-footer">
        <div class="tagline">The Future Shines Brightest</div>
        <p style="text-align: center; font-size: 11px; color: #003C8F; margin-top: 10px;">☎ (034) 703 8802 | ✉ info@sunn.edu.ph | 🌐 www.sunn.edu.ph</p>
      </div>
    </div>
  `;
}

function renderDentalCertificate(data) {
  return `
    <div class="page">
      <div class="letterhead">
        <img class="logo" src="/clinic/images/sunn-logo.png" alt="logo" style="width:66px;"/>
        <div class="titles">
          <div class="republic">Republic of the Philippines</div>
          <div class="university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
          <div class="dept">MEDICAL AND DENTAL SERVICES OFFICE</div>
          <div class="address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>
        </div>
      </div>

      <div class="form-title">DENTAL CERTIFICATE</div>
      <div class="doc-body">
        <p style="margin-bottom: 20px; line-height: 1.6;">This is to certify that <strong>${data.cert_name || data.dc_name || '___________________'}</strong>, Student ID <strong>${data.cert_id || data.dc_id || '___________'}</strong>, age ${data.cert_age || data.dc_age || '__'} of ${data.cert_dept || data.dc_dept || '______________'} has been examined on <strong>${fmtDate(data.cert_date || data.dc_date) || '__________________'}</strong> and found to be in <strong>${data.cert_condition || data.dc_condition || '______________'}</strong> dental health condition.</p>

        <p style="margin-bottom: 20px;"><strong>Dental Findings:</strong> ${data.cert_remarks || data.dc_remarks || '___________________________________________________________________________'}</p>

        <p style="margin-bottom: 20px;"><strong>Purpose of Certificate:</strong> ${data.cert_purpose || data.dc_purpose || '______________'}</p>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
          <div style="flex: 1;">
            <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px;"></div>
            <p style="font-size: 12px; margin: 0;">${data.cert_issuer || data.dc_issuer || '______________'}</p>
            <p style="font-size: 12px; margin: 0;">License No: ${data.cert_license || data.dc_license || '___________'}</p>
          </div>
          <div style="flex: 1; text-align: right;">
            <p style="font-size: 12px; margin: 0;">Date Issued: ${fmtDate(data.cert_date || data.dc_date) || '______________'}</p>
          </div>
        </div>
      </div>

      <div class="page-footer">
        <div class="tagline">The Future Shines Brightest</div>
        <p style="text-align: center; font-size: 11px; color: #003C8F; margin-top: 10px;">☎ (034) 703 8802 | ✉ info@sunn.edu.ph | 🌐 www.sunn.edu.ph</p>
      </div>
    </div>
  `;
}

function renderMedicalForm(data) {
  return `
    <div class="page">
      <div class="letterhead">
        <img class="logo" src="/clinic/images/sunn-logo.png" alt="logo" style="width:66px;"/>
        <div class="titles">
          <div class="republic">Republic of the Philippines</div>
          <div class="university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
          <div class="dept">MEDICAL AND DENTAL SERVICES OFFICE</div>
          <div class="address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>
        </div>
      </div>

      <div class="form-title">MEDICAL RECORD / CLEARANCE</div>
      <div class="doc-body">
        <div class="form-row"><span class="flabel">Patient Name:</span><span class="fline">${data.med_name || '___________________'}</span></div>
        <div class="form-row"><span class="flabel">Student ID:</span><span class="fline">${data.med_id || '___________'}</span></div>
        <div class="form-row"><span class="flabel">Age:</span><span class="fline">${data.med_age || '__'}</span> | <span class="flabel">Sex:</span><span class="fline">${data.med_sex || '__'}</span></div>
        <div class="form-row"><span class="flabel">Date of Visit:</span><span class="fline">${fmtDate(data.med_date) || '______________'}</span></div>

        <div class="form-row"><span class="flabel">Chief Complaint:</span><span class="fline">${data.med_complaint || '______________'}</span></div>
        <div class="form-row"><span class="flabel">History of Present Illness:</span><span class="fline">${data.med_hpi || '______________'}</span></div>

        <div style="margin: 20px 0;"><span class="flabel" style="display: block; margin-bottom: 10px;"><strong>Vital Signs</strong></span></div>
        <div class="form-row"><span class="flabel">BP:</span><span class="fline">${data.med_bp || '______________'}</span> | <span class="flabel">Temp:</span><span class="fline">${data.med_temp || '__'}</span>°C | <span class="flabel">Pulse:</span><span class="fline">${data.med_pulse || '__'}</span></div>
        <div class="form-row"><span class="flabel">RR:</span><span class="fline">${data.med_rr || '__'}</span> | <span class="flabel">O2 Sat:</span><span class="fline">${data.med_o2 || '__'}</span>% | <span class="flabel">Weight:</span><span class="fline">${data.med_weight || '__'}</span>kg</div>

        <div class="form-row"><span class="flabel">Physical Examination:</span><span class="fline">${data.med_pe || '______________'}</span></div>
        <div class="form-row"><span class="flabel">Assessment / Diagnosis:</span><span class="fline">${data.med_assessment || '______________'}</span></div>
        <div class="form-row"><span class="flabel">Treatment / Management:</span><span class="fline">${data.med_treatment || '______________'}</span></div>
        <div class="form-row"><span class="flabel">Disposition:</span><span class="fline">${data.med_disposition || '______________'}</span></div>

        <div style="margin-top: 30px; display: flex; justify-content: space-between;">
          <div style="flex: 1;">
            <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px;"></div>
            <p style="font-size: 12px; margin: 0;">${data.med_nurse || '______________'}</p>
            <p style="font-size: 12px; margin: 0;">Attending Nurse / Staff</p>
          </div>
          <div style="flex: 1; text-align: right;">
            <p style="font-size: 12px; margin: 0;">Date: ${fmtDate(data.med_date) || '______________'}</p>
          </div>
        </div>
      </div>

      <div class="page-footer">
        <div class="tagline">The Future Shines Brightest</div>
        <p style="text-align: center; font-size: 11px; color: #003C8F; margin-top: 10px;">☎ (034) 703 8802 | ✉ info@sunn.edu.ph | 🌐 www.sunn.edu.ph</p>
      </div>
    </div>
  `;
}

function saveForm(event, formType) {
  event.preventDefault();
  const form = event.target;
  const formId = form.id;
  const data = getFormData(formId);

  // Save to API
  apiFetch('save_form', 'POST', {
    form_type: formType,
    student_no: data.mc_id || data.cr_id || data.cert_id || data.med_id || data.xr_id || data.lb_id || data.rf_id || data.rxp_id || data.rxd_id || data.dc_id || '',
    form_data: data
  }).then(res => {
    if (res.success) {
      showToast(`${formType} saved successfully!`, 'success');
      clearForm(formId);
    } else {
      showToast(res.error || 'Failed to save', 'error');
    }
  });
}

function printForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return;

  const formData = getFormData(formId);
  const formType = formId.split('-')[0];

  let htmlContent = '';

  if (formType === 'dental' && formId.includes('cert')) {
    htmlContent = renderDentalCertificate(formData);
  } else if (formType === 'med') {
    htmlContent = renderMedicalCertificate(formData);
  } else if (formType === 'med' && !formId.includes('cert')) {
    htmlContent = renderMedicalForm(formData);
  }

  if (!htmlContent) {
    showToast('Cannot generate form', 'error');
    return;
  }

  const printWindow = window.open('', '', 'width=800,height=1000');
  printWindow.document.write(`<!DOCTYPE html><html><head>
    <style>
      :root { --navy: #003C8F; --gold: #F5A623; }
      body { font-family: 'Segoe UI', Arial; margin: 0; padding: 24px; }
      .page { width: 816px; min-height: 1056px; padding: 40px 55px; }
      .letterhead { display: flex; gap: 14px; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 18px; }
      .letterhead img { width: 66px; }
      .republic { font-size: 12px; font-weight: 700; color: var(--navy); }
      .university { color: #F6BF00; font-weight: 800; font-size: 19px; }
      .dept { color: var(--navy); font-weight: 800; font-size: 16px; }
      .address { color: var(--navy); font-size: 11.5px; font-weight: 600; margin-top: 4px; }
      .form-title { text-align: center; font-weight: 800; font-size: 15px; margin-bottom: 16px; }
      .form-row { display: flex; margin-bottom: 12px; gap: 8px; }
      .flabel { white-space: nowrap; flex-shrink: 0; font-weight: 600; }
      .fline { flex: 1; border-bottom: 1px solid #222; padding-bottom: 2px; }
      .page-footer { margin-top: 40px; text-align: center; }
      .tagline { color: var(--navy); font-style: italic; font-size: 12px; }
      @media print { body { padding: 0; } .page { margin: 0; width: auto; } }
    </style></head><body>${htmlContent}</body></html>`);
  printWindow.document.close();
  setTimeout(() => printWindow.print(), 500);
}

function loadPatientIntoForm(patientId, formId) {
  searchStudentForForm(patientId, formId);
}

// ============================================================
// FORM SEARCH HANDLER — handles search input on form pages
// ============================================================

/**
 * Handles the search input on form pages.
 * Searches for a student by ID or name and auto-fills the form.
 * @param {string} formType - The type of form (e.g., 'medcert', 'consultation', 'xray', etc.)
 * @param {string} query - The search query (student ID or name)
 */
async function handleFormSearch(formType, query) {
  console.log('[handleFormSearch] Called with:', { formType, query });
  if (!query || !query.trim()) {
    showToast('Please enter a student ID or name', 'error');
    return;
  }

  const trimmedQuery = query.trim();
  console.log('[handleFormSearch] Searching for:', trimmedQuery);

  // Mark the search input as "searching" so the icon spins and the user
  // gets visual feedback that the request is in flight.
  const searchInput = getFormSearchInput(formType);
  const searchToolbar = searchInput?.closest('.search-toolbar');
  if (searchToolbar) {
    searchToolbar.classList.add('is-searching');
    searchToolbar.classList.remove('is-loaded');
  }

  try {
    searchInput?._cancelNurseTypeaheadDebounce?.();

    const cachedSearch = searchInput?._nurseTypeaheadSearchCache;
    const pendingSearch = searchInput?._nurseTypeaheadSearchRequest;
    let searchRes;

    if (cachedSearch?.query === trimmedQuery) {
      searchRes = { students: cachedSearch.students };
    } else if (pendingSearch?.query === trimmedQuery) {
      searchRes = await pendingSearch.promise;
    } else {
      searchRes = await apiFetch(`search_student&q=${encodeURIComponent(trimmedQuery)}`);
    }

    const students = searchRes.students || [];
    const exactMatch = students.find(student =>
      String(student.student_id || '').toLowerCase() === trimmedQuery.toLowerCase()
    );
    const matchedStudent = exactMatch || (students.length === 1 ? students[0] : null);

    if (matchedStudent) {
      const formRes = await apiFetch(`get_student_for_form&student_id=${encodeURIComponent(matchedStudent.student_id)}`);
      if (!formRes.student) {
        showToast(formRes.error || 'Student not found', 'error');
        return;
      }

      setCurrentStudentForForm(formType, formRes.student);
      autoFillForm(formType, formRes.student, formRes.medications, formRes.appointments);
      showToast(`Loaded data for ${formRes.student.first_name} ${formRes.student.last_name}`, 'success');
      if (searchToolbar) { searchToolbar.classList.add('is-loaded'); }

      if (typeof uhShowPage === 'function') {
        uhShowPage('form-' + formType, document.querySelector('[data-title="Generate Forms"]'));
      }

      triggerFormPreviewUpdate(formType);
    } else if (students.length > 1) {
      showStudentSelectionDialog(students, formType);
    } else {
      showToast('No student found with that ID or name', 'error');
    }
  } catch (err) {
    console.error('[handleFormSearch] Error:', err);
    showToast('Search failed. Please try again.', 'error');
  } finally {
    if (searchToolbar) { searchToolbar.classList.remove('is-searching'); }
  }
}

/**
 * Shows a dialog to select from multiple matching students
 * @param {Array} students - Array of student objects
 * @param {string} formType - The form type to load the selected student into
 */
function showStudentSelectionDialog(students, formType) {
  // Remove any existing dialog
  const existing = document.getElementById('studentSelectDialog');
  if (existing) existing.remove();

  const dialog = document.createElement('div');
  dialog.id = 'studentSelectDialog';
  dialog.style.cssText = `
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4); z-index: 10000;
    display: flex; align-items: center; justify-content: center;
  `;

  const studentRows = students.map(s => `
    <div class="student-select-row" data-student-id="${s.student_id}" style="
      padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #eee;
      display: flex; align-items: center; gap: 12px;
      transition: background 0.15s;
    " onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: #3498db; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
        ${s.first_name?.[0] || s.last_name?.[0] || '?'}
      </div>
      <div style="flex: 1;">
        <div style="font-weight: 600;">${s.first_name} ${s.last_name}</div>
        <div style="font-size: 0.8rem; color: #666;">${s.student_id} • ${s.department || s.course_year || ''}</div>
        ${uhPatientTypeBadge(s.patient_type)}
      </div>
      <i class="fa fa-chevron-right" style="color: #999;"></i>
    </div>
  `).join('');

  dialog.innerHTML = `
    <div style="background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); width: 90%; max-width: 480px; max-height: 80vh; overflow: hidden;">
      <div style="padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.1rem;">Select Patient</h3>
        <button onclick="document.getElementById('studentSelectDialog').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">&times;</button>
      </div>
      <div style="max-height: 60vh; overflow-y: auto;">
        ${studentRows}
      </div>
      <div style="padding: 12px; text-align: center; color: #666; font-size: 0.85rem; border-top: 1px solid #eee;">
        ${students.length} patient(s) found
      </div>
    </div>
  `;

  document.body.appendChild(dialog);

  // Add click handlers for student rows
  dialog.querySelectorAll('.student-select-row').forEach(row => {
    row.addEventListener('click', async () => {
      const studentId = row.dataset.studentId;
      dialog.remove();

      const res = await apiFetch(`get_student_for_form&student_id=${encodeURIComponent(studentId)}`);
      if (res.student) {
        setCurrentStudentForForm(formType, res.student);
        autoFillForm(formType, res.student, res.medications, res.appointments);
        showToast(`Loaded data for ${res.student.first_name} ${res.student.last_name}`, 'success');
        // Mark search toolbar as loaded for the green-border success state.
        const searchInput = getFormSearchInput(formType);
        const searchToolbar = searchInput?.closest('.search-toolbar');
        if (searchToolbar) { searchToolbar.classList.add('is-loaded'); }

        // Auto-navigate to the form page
        if (typeof uhShowPage === 'function') {
          uhShowPage('form-' + formType, document.querySelector('[data-title="Generate Forms"]'));
        }

        triggerFormPreviewUpdate(formType);
      }
    });
  });

  // Close on backdrop click
  dialog.addEventListener('click', (e) => {
    if (e.target === dialog) dialog.remove();
  });
}

/**
 * Triggers the preview update for the specific form type
 * @param {string} formType - The form type
 */
function triggerFormPreviewUpdate(formType) {
  console.log('[triggerFormPreviewUpdate] Called with:', formType);
  // Map formType to the render function in nurse-forms.js
  const renderMap = {
    'medcert': 'renderMedCert',
    'dentalcert': 'updateDentalCertIframe',
    'consultation': 'renderConsult',
    'xray': 'renderXrayPreview',
    'lab': 'renderLabPreview',
    'referral': 'renderReferralPreview',
    'rx-physician': 'renderRxPhysician',
    'rx-dentist': 'renderRxDentist',
    'dental-cert': 'renderDentalCert',
    'lactating': 'renderLactatingPreview',
    'breastfeeding': 'renderBreastfeedingPreview',
    'xray-clearance': 'renderXrayClearance',
    'lab-slip': 'renderLabSlip',
    'rx-slip': 'renderRxSlip',
    'referral-slip': 'renderReferralSlip',
    'medical-record': 'renderMedicalRecord',
    'dental-record': 'renderDentalRecordForm',
    'dental-cert-new': 'renderDentalCertNew',
    'rx-dentist-slip': 'renderRxDentistSlip',
    'prescription-dentist': 'renderPrescriptionDentistPreview',
    'prescription': 'renderPrescriptionPreview',
    'referral-dentist': 'renderReferralDentistPreview',
    'medcert-new': 'renderMedCertNew',
  };

  const renderFunc = renderMap[formType];
  console.log('[triggerFormPreviewUpdate] Render function:', renderFunc);
  if (renderFunc && typeof window[renderFunc] === 'function') {
    console.log('[triggerFormPreviewUpdate] Calling render function');
    window[renderFunc]();
  } else {
    console.warn('[triggerFormPreviewUpdate] Render function not found:', renderFunc);
  }
}


// ── Render-on-navigate patch ──────────────────────────
// Wrap uhShowPage so that navigating to a form page always triggers
// its preview render, even when no student has been searched yet.
(function () {
  const _formPageMap = {
    'form-medcert':      'medcert',
    'form-dentalcert':   'dentalcert',
    'form-medcert-new':  'medcert-new',
    'form-consultation': 'consultation',
    'form-xray':         'xray',
    'form-lab':          'lab',
    'form-referral':     'referral',
    'form-referral-dentist':'referral-dentist',
    'form-rx-physician': 'rx-physician',
    'form-rx-dentist':   'rx-dentist',
    'form-dental-cert':  'dental-cert',
    'form-lactating':    'lactating',
    'form-bf':           'bf',
    'form-xray-clearance':'xray-clearance',
    'form-lab-slip':     'lab-slip',
    'form-rx-slip':      'rx-slip',
    'form-referral-slip':'referral-slip',
    'form-dental-record':'dental-record',
    'form-medical-record':'medical-record',
    'form-dental-cert-new':'dental-cert-new',
    'form-rx-dentist-slip':'rx-dentist-slip',
  };

  function patchUhShowPage() {
    if (typeof window.uhShowPage !== 'function') {
      // unihealth.js may not have loaded yet; retry briefly
      setTimeout(patchUhShowPage, 50);
      return;
    }
    const _original = window.uhShowPage;
    window.uhShowPage = function (pageId, navEl) {
      _original(pageId, navEl);
      if (
        window.__pendingReferralSourceAppointment
        && !['form-referral', 'form-referral-dentist'].includes(pageId)
      ) {
        window.__pendingReferralSourceAppointment = null;
      }
      if (pageId === 'vital-records') {
        nLoadVitalRecords();
      }
      const formType = _formPageMap[pageId];
      if (formType) {
        // Defer so the page is visible (display:block) before rendering
        setTimeout(function () {
          triggerFormPreviewUpdate(formType);
        }, 0);
      }
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patchUhShowPage);
  } else {
    patchUhShowPage();
  }
}());

// ── Appointment queue time sort ───────────────────────────────────────────
window.nToggleQueueTimeSort = function () {
  queueTableSortOrder = queueTableSortOrder === 'asc' ? 'desc' : 'asc';
  const indicator = document.getElementById('n-queue-sort-indicator');
  if (indicator) { indicator.textContent = queueTableSortOrder === 'asc' ? 'Oldest' : 'Newest'; }
  const sortButton = indicator?.closest('button');
  if (sortButton) {
    sortButton.setAttribute('aria-label', `Sort appointments: ${queueTableSortOrder === 'asc' ? 'oldest' : 'newest'} first`);
  }
  queueTablePage = 1;
  nRenderQueueTablePage();
};
