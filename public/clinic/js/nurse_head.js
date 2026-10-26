// Nurse Head Dashboard Logic
// Mirrors the staff/records slices of admin.js but hits the
// /api/clinic/nurse-head prefix instead of /api/clinic/admin.
//
// The nurse-head blade includes nurse.js after this file to share the
// patient / queue / stats boot, so any top-level `const` or `function`
// here must use a unique name — otherwise nurse.js's `const API` and
// the modal helpers will collide.
'use strict';

const NURSE_HEAD_API = '/api/clinic/nurse-head';

let _campusCache = [];
let _campusLoadPromise = null;
let _nhManagedStaff = [];
let nhSelectedCampusId = null;

function nhCampusQuery(prefix = '?') {
  if (!nhSelectedCampusId) return '';
  return `${prefix}campus_id=${encodeURIComponent(nhSelectedCampusId)}`;
}

function nhSetDashboardCampus(campusId) {
  nhSelectedCampusId = campusId === 'all' ? null : Number(campusId);
  nhLoadDashboardStats();
  nhRenderAppointments();
}

function nhJoinQuery(url, params = '') {
  if (!params) return url;
  return url + (url.includes('?') ? '&' : '?') + params.replace(/^\?/, '');
}

document.addEventListener('DOMContentLoaded', () => {
  const userJson = sessionStorage.getItem('clinic_user');
  const user = userJson ? JSON.parse(userJson) : null;
  if (!user) { window.location.replace('/login'); return; }

  const nameEl = document.getElementById('sidebarName');
  if (nameEl) nameEl.textContent = user.name || 'Head Nurse';

  loadCampusFilter();
  renderDoctorsTable();
  renderStaffTable();
  nhRenderAppointments();
  nhLoadDashboardStats();
});

// -------------------------------------------------------
//  DASHBOARD STATS
//  Pulls the full status breakdown + KPIs from /admin/dashboard
//  (admin/head roles share the same dashboard endpoint).
//  Also writes the per-status tiles on the Appointments page.
// -------------------------------------------------------
async function nhLoadDashboardStats() {
  try {
    const statsUrl = nhJoinQuery(
      `/api/clinic/admin/dashboard?_=${Date.now()}`,
      nhCampusQuery(),
    );
    const res = await fetch(statsUrl, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    const s = (data && data.stats) || {};

    // Main dashboard cards
    setV('nh-stat-patients',   Number(s.total_students || s.total_patients || 0).toLocaleString());
    setV('nh-stat-appointments', Number(s.todays_appointments || 0).toLocaleString());
    setV('nh-stat-referrals',  Number(s.pending_referrals || 0).toLocaleString());

    const apptTrend = document.getElementById('nh-stat-appointments-trend');
    if (apptTrend) {
      const ap = s.appointments_by_status || {};
      const parts = [];
      if (ap.completed) parts.push(ap.completed + ' completed');
      if (ap.waiting)   parts.push(ap.waiting   + ' waiting');
      if (ap.accepted)  parts.push(ap.accepted  + ' accepted');
      if (ap.checkup_scheduled) parts.push(ap.checkup_scheduled + ' check-up scheduled');
      if (ap.confirmed) parts.push(ap.confirmed + ' confirmed');
      if (ap.deleted)   parts.push(ap.deleted   + ' deleted');
      apptTrend.textContent = parts.length ? parts.join(' · ') : '—';
    }
    const refTrend = document.getElementById('nh-stat-referrals-trend');
    if (refTrend && s.urgent_referrals) {
      refTrend.textContent = s.urgent_referrals + ' urgent';
    }

    // Appointments page status tiles
    const ap = s.appointments_by_status || {};
    const waiting = Number(ap.waiting ?? ap.pending ?? 0);
    const accepted = Number(ap.accepted || 0);
    const checkupScheduled = Number(ap.checkup_scheduled || 0);
    const checkupCompleted = Number(ap.checkup_completed || 0);
    const confirmed = Number(ap.confirmed || 0);
    const completed = Number(ap.completed || 0);
    const cancelled = Number(ap.cancelled || 0);
    const deleted = Number(ap.deleted || 0);
    const total = waiting + accepted + checkupScheduled + checkupCompleted + confirmed + completed + cancelled + deleted;
    setV('nh-appt-stat-total',     Number(total).toLocaleString());
    setV('nh-appt-stat-waiting',   waiting.toLocaleString());
    setV('nh-appt-stat-accepted',  accepted.toLocaleString());
    setV('nh-appt-stat-checkup-scheduled', checkupScheduled.toLocaleString());
    setV('nh-appt-stat-completed', completed.toLocaleString());
    setV('nh-appt-stat-cancelled', cancelled.toLocaleString());
    setV('nh-appt-stat-deleted',   deleted.toLocaleString());
  } catch (e) {
    console.warn('Failed to load dashboard stats:', e);
  }
}

// -------------------------------------------------------
//  API helper (action-based, mirrors admin.js apiFetch)
// -------------------------------------------------------
async function nhApiFetch(action, method = 'GET', body = null) {
  const actionMap = {
    'get_staff':           'staff',
    'add_staff':           'staff',
    'update_staff':        'staff',
    'delete_staff':        'staff',
    'assign_staff_campus': 'staff',
    'get_campuses':        'campuses',
    'get_records':         'records',
    'get_reports':         'reports',
  };

  try {
    const [rawAction, ...extraParts] = action.split('&');
    const extraParams = extraParts.join('&');
    const bust = `_=${Date.now()}`;
    const baseParams = [extraParams, bust].filter(Boolean).join('&');

    let path = actionMap[rawAction] || rawAction;
    let url;
    let resolvedMethod = method;

    if (rawAction === 'delete_staff') {
      const empId = body?.employee_id ?? '';
      url = `${NURSE_HEAD_API}/staff/${encodeURIComponent(empId)}?${bust}`;
      resolvedMethod = 'DELETE';
    } else if (rawAction === 'assign_staff_campus') {
      const empId = body?.employee_id ?? '';
      url = `${NURSE_HEAD_API}/staff/${encodeURIComponent(empId)}/campus?${bust}`;
      resolvedMethod = 'POST';
    } else if (rawAction === 'update_staff') {
      const empId = body?.employee_id ?? '';
      url = `${NURSE_HEAD_API}/staff/${encodeURIComponent(empId)}?${bust}`;
      resolvedMethod = 'PUT';
    } else {
      url = `${NURSE_HEAD_API}/${path}?${baseParams}`;
    }

    const opts = {
      method: resolvedMethod,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Cache-Control': 'no-cache',
      },
      credentials: 'same-origin',
    };
    if (body && resolvedMethod !== 'GET') opts.body = JSON.stringify(body);
    let resp;
    try {
      resp = await fetch(url, opts);
    } catch (err) {
      console.error('API Network Error:', err);
      return { error: 'offline', message: err.message };
    }

    const responseText = await resp.text();
    let data = {};
    try {
      const parsed = responseText ? JSON.parse(responseText) : {};
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
        data = parsed;
      }
    } catch (error) {
      if (resp.ok) {
        return { error: 'The server returned an unexpected response.', status: resp.status };
      }
    }
    if (!resp.ok) {
      const validationError = data.errors && Object.values(data.errors).flat()[0];
      return {
        ...data,
        error: data.error || validationError || data.message || `Request failed (${resp.status}).`,
        status: resp.status,
      };
    }
    return data;
  } catch (err) {
    console.error('API Error:', err);
    return { error: 'Request could not be processed.', message: err.message };
  }
}

function setV(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

// -------------------------------------------------------
//  CAMPUSES
// -------------------------------------------------------
async function loadCampusFilter() {
  if (!_campusLoadPromise) {
    _campusLoadPromise = nhApiFetch('get_campuses')
      .then((res) => {
        _campusCache = Array.isArray(res.campuses) ? res.campuses : [];
        _populateCampusSelects();

        if (_campusCache.length === 0) {
          console.warn('Could not load campuses:', res.error || 'No campuses returned');
        }

        return _campusCache;
      })
      .finally(() => {
        _campusLoadPromise = null;
      });
  }

  return _campusLoadPromise;
}

function _populateCampusSelects() {
  ['st_campus_id', 'nh-edit-campus-id', 'assign_campus_select'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const currentVal = sel.value;
    const allOption = id === 'assign_campus_select'
      ? '<option value="">— All Campuses (no restriction) —</option>'
      : '<option value="">— All Campuses (system-wide access) —</option>';
    sel.innerHTML = allOption +
      _campusCache.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    if (currentVal) sel.value = currentVal;
  });

  document.querySelectorAll('.nh-campus-filter').forEach((select) => {
    const currentValue = select.value;
    select.replaceChildren(new Option('All Campuses', ''));
    _campusCache.forEach((campus) => {
      select.add(new Option(campus.name, String(campus.id)));
    });
    select.value = currentValue;
  });
}

// -------------------------------------------------------
//  STAFF
// -------------------------------------------------------
async function renderDoctorsTable() {
  const tbody = document.getElementById('nh-doctors-body');
  if (!tbody) return;

  const res = await nhApiFetch('get_staff');
  if (res.error) {
    console.error('Failed to load doctors and dentists:', res.error);
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">Could not load doctors and dentists. Please try again.</td></tr>';
    return;
  }

  const professionals = (res.staff || []).filter((member) =>
    ['physician', 'dentist'].includes(member.role)
  );
  if (!professionals.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">No doctors or dentists found.</td></tr>';
    return;
  }

  tbody.innerHTML = professionals.map((member) => {
    const name = [member.first_name, member.last_name].filter(Boolean).join(' ') || '—';
    const specialty = member.role === 'dentist' ? 'Dentist' : 'Physician';
    const campus = member.campus?.name || 'All Campuses';
    const isActive = member.is_active === true || member.is_active === 1;

    return `
      <tr>
        <td>${escapeHtml(name)}</td>
        <td>${specialty}</td>
        <td>${escapeHtml(campus)}</td>
        <td>${escapeHtml(member.license_no || '—')}</td>
        <td><span class="chip ${isActive ? 'chip-green' : 'chip-red'}">${isActive ? 'Active' : 'Inactive'}</span></td>
      </tr>`;
  }).join('');
}

async function renderStaffTable() {
  const tbody = document.getElementById('staffTableBody');
  if (!tbody) return;

  const res = await nhApiFetch('get_staff');
  const staff = (res.staff || []).filter((member) =>
    ['nurse', 'physician', 'dentist'].includes(member.role)
  );
  _nhManagedStaff = staff;

  if (!staff.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">No nurses, physicians, or dentists to manage yet. Click "Add Staff" to begin.</td></tr>';
    return;
  }

  tbody.innerHTML = staff.map(s => {
    const name = [s.first_name, s.last_name].filter(Boolean).join(' ') || '—';
    const campusName = s.campus?.name || s.campus_name || 'All Campuses';
    const safeCampus = (campusName || '').replace(/'/g, "\\'");
    const roleClass = `chip-${s.role === 'admin' ? 'red' : s.role === 'nurse_head' ? 'gold' : 'blue'}`;
    return `
      <tr>
        <td><strong>${name}</strong><br>
          <small style="color:var(--text-muted)">${s.employee_id}</small></td>
        <td><span class="chip ${roleClass}">${(s.role || '').replace('_', ' ')}</span></td>
        <td>${s.email || '—'}</td>
        <td>${s.license_no || '—'}</td>
        <td>
          <span class="chip ${s.campus_id ? 'chip-green' : 'chip-gold'}">
            <i class="fa fa-building" style="font-size:0.65rem;margin-right:3px"></i>
            ${campusName}
          </span>
        </td>
        <td><span class="chip chip-green">Active</span></td>
        <td style="display:flex;gap:4px;flex-wrap:wrap;">
          <button class="btn btn-secondary btn-sm" title="Edit Staff"
            data-employee-id="${encodeURIComponent(s.employee_id)}"
            onclick="nhOpenEditStaff(decodeURIComponent(this.dataset.employeeId))">
            <i class="fa fa-pen"></i> Edit
          </button>
          <button class="btn btn-secondary btn-sm" title="Assign Campus"
            onclick="openAssignCampusModal('${s.employee_id}','${safeCampus}',${s.campus_id || 'null'})">
            <i class="fa fa-building"></i> Campus
          </button>
          <button class="btn btn-danger btn-sm" title="Remove" onclick="removeStaff('${s.employee_id}')">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>`;
  }).join('');
}

async function nhOpenEditStaff(employeeId) {
  let member = _nhManagedStaff.find((staff) => staff.employee_id === employeeId);

  if (!member) {
    await renderStaffTable();
    member = _nhManagedStaff.find((staff) => staff.employee_id === employeeId);
  }

  if (!member) {
    showToast('Staff member not found or cannot be edited.', 'error');
    return;
  }

  if (_campusCache.length === 0) {
    await loadCampusFilter();
  }
  _populateCampusSelects();

  const values = {
    'nh-edit-employee-id': member.employee_id,
    'nh-edit-first-name': member.first_name || '',
    'nh-edit-middle-name': member.middle_name || '',
    'nh-edit-last-name': member.last_name || '',
    'nh-edit-email': member.email || '',
    'nh-edit-role': member.role || '',
    'nh-edit-license': member.license_no || '',
    'nh-edit-contact': member.contact || '',
    'nh-edit-specialization': member.specialization || '',
    'nh-edit-campus-id': member.campus_id || '',
  };

  Object.entries(values).forEach(([id, value]) => {
    const field = document.getElementById(id);
    if (field) field.value = value;
  });

  document.getElementById('nh-edit-password').value = '';
  document.getElementById('nh-edit-password-confirmation').value = '';
  nhOpenModal('editStaffModal');
}

async function nhSaveStaffEdit(event) {
  event.preventDefault();

  const employeeId = document.getElementById('nh-edit-employee-id')?.value || '';
  const payload = {
    employee_id: employeeId,
    first_name: document.getElementById('nh-edit-first-name')?.value?.trim() || '',
    middle_name: document.getElementById('nh-edit-middle-name')?.value?.trim() || '',
    last_name: document.getElementById('nh-edit-last-name')?.value?.trim() || '',
    email: document.getElementById('nh-edit-email')?.value?.trim() || '',
    role: document.getElementById('nh-edit-role')?.value || '',
    license_no: document.getElementById('nh-edit-license')?.value?.trim() || '',
    contact: document.getElementById('nh-edit-contact')?.value?.trim() || '',
    specialization: document.getElementById('nh-edit-specialization')?.value?.trim() || '',
    campus_id: document.getElementById('nh-edit-campus-id')?.value
      ? parseInt(document.getElementById('nh-edit-campus-id').value, 10)
      : null,
  };
  const password = document.getElementById('nh-edit-password')?.value || '';
  const passwordConfirmation = document.getElementById('nh-edit-password-confirmation')?.value || '';

  if (!employeeId || !payload.first_name || !payload.last_name || !payload.email || !payload.role) {
    showToast('Employee ID, name, email, and role are required.', 'error');
    return;
  }

  if (password || passwordConfirmation) {
    payload.password = password;
    payload.password_confirmation = passwordConfirmation;
  }

  const response = await nhApiFetch('update_staff', 'PUT', payload);
  if (response.success) {
    nhCloseModal('editStaffModal');
    document.getElementById('nh-edit-password').value = '';
    document.getElementById('nh-edit-password-confirmation').value = '';
    await renderStaffTable();
    showToast('Staff member updated successfully.', 'success');
    return;
  }

  showToast(response.error || 'Failed to update staff member.', 'error');
}

async function openAddStaffModal() {
  if (_campusCache.length === 0) {
    await loadCampusFilter();
  }

  _populateCampusSelects();
  nhOpenModal('addStaffModal');
}

async function addStaff(e) {
  e.preventDefault();

  const nameVal   = document.getElementById('st_name')?.value?.trim() || '';
  const nameParts = nameVal.split(/\s+/).filter(Boolean);
  const pw  = document.getElementById('st_password')?.value || '';
  const pw2 = document.getElementById('st_password2')?.value || '';

  if (!pw) {
    showToast('Please enter a password.', 'error');
    return;
  }
  if (pw.length < 6) {
    showToast('Password must be at least 6 characters.', 'error');
    return;
  }
  if (pw !== pw2) {
    showToast('Passwords do not match.', 'error');
    return;
  }

  const data = {
    employee_id: document.getElementById('st_employee_id')?.value?.trim() || ('STF-' + Date.now()),
    last_name:   nameParts.at(-1) || nameVal,
    first_name:  nameParts[0] || nameVal,
    middle_name: nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '',
    email:       document.getElementById('st_email')?.value?.trim() || '',
    role:        (document.getElementById('st_role')?.value || '').toLowerCase(),
    license_no:  document.getElementById('st_license')?.value?.trim() || '',
    contact:     document.getElementById('st_contact')?.value?.trim() || '',
    campus_id:   document.getElementById('st_campus_id')?.value
                   ? parseInt(document.getElementById('st_campus_id').value)
                   : null,
    password:    pw,
    password_confirmation: pw2,
  };

  if (!data.email || !data.role || !data.employee_id) {
    showToast('Employee ID, email and role are required.', 'error');
    return;
  }

  const res = await nhApiFetch('add_staff', 'POST', data);
  if (res.success) {
    nhCloseModal('addStaffModal');
    e.target.reset();
    renderStaffTable();
    showToast('Staff member added successfully!', 'success');
  } else {
    showToast(res.error || 'Failed to add staff', 'error');
  }
}

async function removeStaff(employeeId) {
  if (!await uhConfirm('This will remove the staff member from the clinic.', { title: 'Remove staff member?', confirmText: 'Remove staff', danger: true })) return;
  const res = await nhApiFetch('delete_staff', 'POST', { employee_id: employeeId });
  if (res.success) {
    renderStaffTable();
    showToast('Staff removed.', 'info');
  } else {
    showToast(res.error || 'Failed to remove staff', 'error');
  }
}

// -------------------------------------------------------
//  ASSIGN CAMPUS
// -------------------------------------------------------
let _assigningEmployeeId = null;

function openAssignCampusModal(employeeId, currentCampusName, currentCampusId) {
  _assigningEmployeeId = employeeId;
  const label = document.getElementById('assign_emp_label');
  if (label) label.textContent = employeeId;

  if (_campusCache.length === 0) {
    nhApiFetch('get_campuses').then(res => {
      _campusCache = res.campuses || [];
      _populateCampusSelects();
      _setAssignSelect(currentCampusId);
    });
  } else {
    _populateCampusSelects();
    _setAssignSelect(currentCampusId);
  }

  nhOpenModal('assignCampusModal');
}

function _setAssignSelect(campusId) {
  const sel = document.getElementById('assign_campus_select');
  if (!sel) return;
  sel.value = campusId ? String(campusId) : '';
}

async function confirmAssignCampus() {
  if (!_assigningEmployeeId) return;

  const sel = document.getElementById('assign_campus_select');
  const campusId = sel?.value ? parseInt(sel.value) : null;
  const campusName = campusId
    ? (_campusCache.find(c => c.id === campusId)?.name || 'Selected Campus')
    : 'All Campuses';

  const res = await nhApiFetch('assign_staff_campus', 'POST', {
    employee_id: _assigningEmployeeId,
    campus_id: campusId,
  });

  if (res.success) {
    showToast(`${_assigningEmployeeId} → ${campusName}`, 'success');
    nhCloseModal('assignCampusModal');
    renderStaffTable();
  } else {
    showToast(res.error || 'Failed to assign campus', 'error');
  }
}

// -------------------------------------------------------
//  APPOINTMENTS
//  Replaces the static sample rows in the nurse-head dashboard's
//  "Appointments" card with real data from /api/clinic/nurse-head/appointments.
//  The view and cancel buttons in each row work end-to-end.
// -------------------------------------------------------
async function nhRenderAppointments() {
  const dashboardBody = document.getElementById('nh-dashboard-appt-body');
  const pageBody = document.getElementById('nh-appt-body');
  if (!dashboardBody && !pageBody) { return; }
  if (dashboardBody) {
    dashboardBody.innerHTML = '<tr><td colspan="5" class="no-data">Loading today\'s appointments…</td></tr>';
  }
  if (pageBody) {
    pageBody.innerHTML = '<tr><td colspan="6" class="no-data">Loading today\'s appointments…</td></tr>';
  }

  // Limit to today for the dashboard preview
  const today = new Date().toISOString().slice(0, 10);
  try {
    const appointmentsUrl = nhJoinQuery(
      `${NURSE_HEAD_API}/appointments?_=${Date.now()}&limit=20`,
      nhCampusQuery(),
    );
    const res = await fetch(appointmentsUrl, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    const all = (data && data.appointments) || [];
    all.sort((left, right) => {
      const leftTime = `${left.appt_date || ''} ${left.appt_time || ''}`;
      const rightTime = `${right.appt_date || ''} ${right.appt_time || ''}`;
      return leftTime.localeCompare(rightTime);
    });
    // Prefer today's appointments; fall back to the most recent if no match.
    const todays = all.filter(a => (a.appt_date || '').startsWith(today));
    const list = todays.length ? todays : all.slice(0, 10);

    if (!list.length) {
      if (dashboardBody) {
        dashboardBody.innerHTML = '<tr><td colspan="5" class="no-data">No appointments today.</td></tr>';
      }
      if (pageBody) {
        pageBody.innerHTML = '<tr><td colspan="6" class="no-data">No appointments today. Book one from the Appointments page.</td></tr>';
      }
      return;
    }

    const renderRow = (a, includeActions) => {
      const student = a.student || {};
      const name = [student.first_name, student.last_name].filter(Boolean).join(' ') || student.name || '—';
      const time = uhFormatTime(a.appt_time);
      const type = a.service_type || 'Consultation';
      const provider = a.provider_name || a.staff_name || '—';
      const status = a.status || 'pending';
      const statusClass = ({pending: 'waiting', accepted: 'in-progress', confirmed: 'in-progress', completed: 'completed', cancelled: 'cancelled'})[status] || 'waiting';
      const statusLabel = status === 'accepted' ? 'Accepted' : status.charAt(0).toUpperCase() + status.slice(1);

      let actions = '';
      const appointmentEntry = escapeHtml(JSON.stringify({
        id: Number(a.id),
        status,
        student_id: student.student_id || '',
        first_name: student.first_name || '',
        middle_name: student.middle_name || '',
        last_name: student.last_name || '',
        reason: a.reason || '',
        service_type: a.service_type || '',
        campus_id: Number(a.campus_id || student.campus_id || 0),
        availability_id: Number(a.availability_id || 0),
        time: a.appt_time || '',
        consultation_id: a.consultation_request?.id || null,
      }));
      if (status === 'pending') {
        actions += `<button class="uh-btn uh-btn-primary uh-btn-xs uh-mr-4" data-queue-entry="${appointmentEntry}" onclick="nAcceptAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-check"></i> Accept</button>`;
      }
      if (status === 'accepted') {
        actions += `<button class="uh-btn uh-btn-primary uh-btn-xs uh-mr-4" data-queue-entry="${appointmentEntry}" onclick="nScheduleAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-calendar-check"></i> Schedule</button>`;
        actions += `<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" data-queue-entry="${appointmentEntry}" onclick="nReferAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-share-from-square"></i> Refer</button>`;
      }
      if (status === 'confirmed') {
        actions += `<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" data-queue-entry="${appointmentEntry}" onclick="nReferAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-share-from-square"></i> Refer</button>`;
        actions += `<button class="uh-btn uh-btn-primary uh-btn-xs uh-mr-4" data-queue-entry="${appointmentEntry}" onclick="nTreatAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-stethoscope"></i> Treat</button>`;
        const appointmentDate = String(a.appt_date || '').slice(0, 10);
        const now = new Date();
        const today = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-');
        if (appointmentDate && appointmentDate <= today) {
          actions += `<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" onclick="nhUpdateAppt(${Number(a.id)},'completed')"><i class="fa fa-check-double"></i> Done</button>`;
        }
      }
      if (status !== 'cancelled' && status !== 'completed') {
        actions += `<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhUpdateAppt(${a.id},'cancelled')">Cancel</button>`;
      }
      if (!actions) { actions = '<span class="uh-text-muted uh-text-sm">—</span>'; }

      return '<tr>' +
        `<td>${time}</td>` +
        `<td>${escapeHtml(name)}</td>` +
        `<td>${escapeHtml(type)}</td>` +
        `<td>${escapeHtml(provider)}</td>` +
        `<td><span class="uh-badge ${statusClass}">${statusLabel}</span></td>` +
        (includeActions ? `<td>${actions}</td>` : '') +
        '</tr>';
    };

    if (dashboardBody) {
      dashboardBody.innerHTML = list.slice(0, 5).map(a => renderRow(a, false)).join('');
    }
    if (pageBody) {
      pageBody.innerHTML = list.map(a => renderRow(a, true)).join('');
    }
  } catch (e) {
    console.warn('Failed to load appointments:', e);
    if (dashboardBody) {
      dashboardBody.innerHTML = '<tr><td colspan="5" class="no-data">Failed to load appointments.</td></tr>';
    }
    if (pageBody) {
      pageBody.innerHTML = '<tr><td colspan="6" class="no-data">Failed to load appointments.</td></tr>';
    }
  }
}

async function nhUpdateAppt(id, status) {
  const labels = { accepted: 'Accept', confirmed: 'Confirm', completed: 'mark as completed', cancelled: 'Cancel' };
  const verb = labels[status] || ('set to ' + status);
  if (status === 'cancelled' && !await uhConfirm(`Cancel appointment #${id}?`, { title: 'Cancel appointment?', confirmText: 'Cancel appointment', danger: true })) { return; }
  try {
    const res = await fetch(`${NURSE_HEAD_API}/appointments/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ status }),
    });
    const data = await res.json().catch(() => ({}));
    if (data && data.success) {
      showToast(status === 'accepted' ? 'Patient accepted. Refer to a physician or dentist if specialist scheduling is needed.' : 'Appointment ' + verb + ' successfully.', 'success');
      nhRenderAppointments();
      nhLoadDashboardStats();
    } else {
      showToast((data && (data.error || (data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0]))) || 'Failed to update appointment.', 'error');
    }
  } catch (e) {
    showToast('Failed to update appointment.', 'error');
  }
}

function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// -------------------------------------------------------
//  RECORDS (the All Records page was removed from the
//  nurse-head dashboard; admin still renders its own copy)
// -------------------------------------------------------

// -------------------------------------------------------
//  TOAST
// -------------------------------------------------------
function showToast(msg, type = 'success') {
  const c = document.getElementById('uh-toasts') || document.getElementById('toastContainer');
  if (!c) return;
  const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fa ${icons[type] || 'fa-bell'}"></i><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transition = 'opacity 0.3s';
    setTimeout(() => t.remove(), 320);
  }, 3200);
}

// -------------------------------------------------------
//  MODAL helpers (assumes unihealth.js exposes openModal/closeModal)
//
//  These are intentionally NOT named `openModal` / `closeModal` at the
//  top level — top-level `function` declarations attach to `window`,
//  so an `if (typeof window.openModal === 'function')` self-check would
//  recurse forever. The exported wrappers below look up the real
//  implementation on `window` if one exists, otherwise fall through to
//  the local helper. Use the wrappers (`nhOpenModal` / `nhCloseModal`)
//  from anywhere in this file.
// -------------------------------------------------------
function _openModalLocal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('hidden');
}
function _closeModalLocal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('hidden');
}
function nhOpenModal(id) {
  if (typeof window.openModal === 'function' && window.openModal !== nhOpenModal) {
    window.openModal(id); return;
  }
  _openModalLocal(id);
}
function nhCloseModal(id) {
  if (typeof window.closeModal === 'function' && window.closeModal !== nhCloseModal) {
    window.closeModal(id); return;
  }
  _closeModalLocal(id);
}
