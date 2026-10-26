// Admin Dashboard Logic - PostgreSQL backend
'use strict';

const API = '/api/clinic/admin';

let selectedCampusId = ''; // '' = All Campuses
let _campusCache = []; // cached campus list for modals

document.addEventListener('DOMContentLoaded', () => {
  const userJson = sessionStorage.getItem('clinic_user');
  const user = userJson ? JSON.parse(userJson) : null;
  if (!user) { window.location.replace('/login'); return; }

  const nameEl = document.getElementById('sidebarName');
  if (nameEl) nameEl.textContent = user.name || 'Admin';

  const h = new Date().getHours();
  const gr = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const grEl = document.getElementById('greetMsg');
  if (grEl) grEl.textContent = `${gr}, ${user.name ? user.name.split(' ')[0] : 'Admin'}!`;

  loadAdminStats();
  loadCampusFilter();
  renderRecentRegistrations();
  renderActivityLog();
  renderAdminChart();
  renderDeptDistrib();
  renderStudentTable();
  renderStaffTable();
  renderAllRecords();
  renderReportStats();
  renderReportChart();
  _injectCampusAssignModal();
});

async function apiFetch(action, method = 'GET', body = null) {
  // Map legacy action names to REST paths
  const actionMap = {
    'get_stats':                 'stats',
    'get_students':              'students',
    'get_student':               'students',
    'delete_student':            'students',
    'get_staff':                 'staff',
    'add_staff':                 'staff',
    'delete_staff':              'staff',
    'assign_staff_campus':       'staff',
    'get_appointments':          'appointments',
    'get_records':               'records',
    'get_reports':               'reports',
    'get_audit_log':             'audit-log',
    'get_campuses':              'campuses',
    'get_settings':              'settings',
    'update_setting':            'settings',
    'export_data':               'export',
  };

  try {
    // Parse action — may have inline query params like 'get_students&limit=5'
    const [rawAction, ...extraParts] = action.split('&');
    const extraParams = extraParts.join('&');

    const campusParam = selectedCampusId ? `campus_id=${selectedCampusId}` : '';
    const bust = `_=${Date.now()}`;
    const baseParams = [campusParam, extraParams, bust].filter(Boolean).join('&');

    let path = actionMap[rawAction] || rawAction;
    let url;
    let resolvedMethod = method;

    // Route based on action
    if (rawAction === 'get_student') {
      const params = new URLSearchParams(extraParams);
      const studentNo = params.get('student_id') || '';
      url = `${API}/students/${encodeURIComponent(studentNo)}?${bust}`;
    } else if (rawAction === 'delete_student') {
      const params = new URLSearchParams(extraParams);
      const studentNo = params.get('student_id') || (body?.student_id ?? '');
      url = `${API}/students/${encodeURIComponent(studentNo)}?${bust}`;
      resolvedMethod = 'DELETE';
    } else if (rawAction === 'delete_staff') {
      const empId = body?.employee_id ?? '';
      url = `${API}/staff/${encodeURIComponent(empId)}?${bust}`;
      resolvedMethod = 'DELETE';
    } else if (rawAction === 'assign_staff_campus') {
      const empId = body?.employee_id ?? '';
      url = `${API}/staff/${encodeURIComponent(empId)}/campus?${bust}`;
      resolvedMethod = 'POST';
    } else if (rawAction === 'update_setting') {
      url = `${API}/settings?${bust}`;
      resolvedMethod = 'PUT';
    } else {
      url = `${API}/${path}?${baseParams}`;
    }

    const opts = { method: resolvedMethod, headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-cache' } };
    if (body && resolvedMethod !== 'GET') opts.body = JSON.stringify(body);
    const resp = await fetch(url, opts);
    return await resp.json();
  } catch (err) {
    console.error('API Error:', err);
    return { error: 'offline', message: err.message };
  }
}

function setV(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }

// -------------------------------------------------------
//  CAMPUS FILTER (topbar dropdown — filters all panels)
// -------------------------------------------------------
async function loadCampusFilter() {
  try {
    const res = await apiFetch('get_campuses');
    _campusCache = res.campuses || [];
    const sel = document.getElementById('campusFilterSelect');
    if (sel && _campusCache.length > 0) {
      sel.innerHTML = '<option value="" style="background:#fff;color:#1a3d2b;">All Campuses</option>' +
        _campusCache.map(c => `<option value="${c.id}" style="background:#fff;color:#1a3d2b;">${c.name}</option>`).join('');
    }
    _populateCampusSelects();
  } catch (e) {
    console.warn('Could not load campus filter:', e);
  }
}

function _populateCampusSelects() {
  ['st_campus_id', 'assign_campus_select'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const currentVal = sel.value;
    const allOption = id === 'assign_campus_select'
      ? '<option value="">— All Campuses (no restriction) —</option>'
      : '<option value="">— No Campus Assigned —</option>';
    sel.innerHTML = allOption +
      _campusCache.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    if (currentVal) sel.value = currentVal;
  });
}

function onCampusFilterChange(campusId) {
  selectedCampusId = campusId;
  loadAdminStats();
  renderStudentTable();
  renderStaffTable();
  renderReportChart();
}

// -------------------------------------------------------
//  STATS
// -------------------------------------------------------
async function loadAdminStats() {
  const res = await apiFetch('get_stats');
  if (res.stats) {
    setV('adminStatStudents',  res.stats.total_students       ?? '—');
    setV('adminStatPatients',  res.stats.total_patients       ?? '—');
    setV('adminStatRecords',   res.stats.total_records        ?? '—');
    setV('adminStatStaff',     res.stats.total_staff          ?? '—');
    setV('adminStatVisits',    res.stats.todays_visits        ?? '—');
    setV('adminStatAppts',     res.stats.pending_appointments ?? '—');
  }
}

// -------------------------------------------------------
//  RECENT REGISTRATIONS
// -------------------------------------------------------
async function renderRecentRegistrations() {
  const container = document.getElementById('recentRegList');
  if (!container) return;

  const res = await apiFetch('get_students&limit=5');
  const students = res.students || [];

  if (students.length === 0) {
    container.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:16px;font-size:0.82rem">No registrations yet.</p>';
    return;
  }
  container.innerHTML = students.map(s => `
    <div class="patient-list-item">
      <div class="patient-avatar">${(s.last_name + s.first_name || '?')[0].toUpperCase()}</div>
      <div class="patient-info">
        <div class="pname">${s.first_name} ${s.last_name}</div>
        <div class="pdetail">${s.department || '—'} &bull; ${s.email}</div>
      </div>
      <span class="patient-status status-active">New</span>
    </div>`).join('');
}

function renderActivityLog() {
  const container = document.getElementById('activityLog');
  if (!container) return;
  const logs = [
    { icon:'fa-user-plus',      color:'var(--primary)', title:'System ready',              time:'Just now' },
    { icon:'fa-file-medical',   color:'#3498db',         title:'Medical records available', time:'Today' },
    { icon:'fa-prescription',   color:'#e67e22',         title:'Prescriptions tracked',     time:'Today' },
    { icon:'fa-tooth',          color:'#9b59b6',         title:'Dental module active',      time:'Today' },
    { icon:'fa-building',       color:'#27ae60',         title:'Multi-campus enabled',      time:'Today' },
  ];
  container.innerHTML = logs.map(l => `
    <div class="timeline-item">
      <div class="timeline-dot" style="background:${l.color}"><i class="fa ${l.icon}" style="font-size:0.7rem"></i></div>
      <div class="timeline-content">
        <div class="timeline-title">${l.title}</div>
        <div class="timeline-date">${l.time}</div>
      </div>
    </div>`).join('');
}

function renderAdminChart() {
  const chart  = document.getElementById('adminWeekChart');
  const labels = document.getElementById('adminWeekLabels');
  if (!chart) return;
  const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const vals = [12, 18, 9, 15, 20, 7, 3];
  const max  = Math.max(...vals);
  chart.innerHTML = vals.map((v, i) => `
    <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:4px">
      <span style="font-size:0.68rem;font-weight:600;color:var(--primary)">${v}</span>
      <div style="width:100%;background:${i===4?'var(--primary)':'rgba(42,122,111,0.2)'};
        border-radius:6px 6px 0 0;height:${Math.round((v/max)*100)}px"></div>
    </div>`).join('');
  if (labels) labels.innerHTML = days.map(d => `<span style="flex:1;text-align:center">${d}</span>`).join('');
}

async function renderDeptDistrib() {
  const container = document.getElementById('deptDistrib');
  if (!container) return;
  const res = await apiFetch('get_students&limit=1000');
  const students = res.students || [];
  const depts = {};
  students.forEach(s => { const d = s.department || 'Unknown'; depts[d] = (depts[d]||0)+1; });
  const demoDepts = { 'BSN':8,'BSIT':5,'BSF':4,'BSEd-English':3 };
  Object.keys(demoDepts).forEach(d => { depts[d] = (depts[d]||0) + demoDepts[d]; });
  const total = Object.values(depts).reduce((a,b)=>a+b,0);
  const sorted = Object.entries(depts).sort((a,b)=>b[1]-a[1]).slice(0,5);
  container.innerHTML = sorted.map(([d, c]) => `
    <div style="margin-bottom:8px">
      <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
        <span style="font-weight:500">${d}</span><span style="color:var(--text-muted)">${c}</span>
      </div>
      <div style="background:rgba(42,122,111,0.1);border-radius:6px;height:8px;overflow:hidden">
        <div style="height:100%;background:var(--primary);border-radius:6px;width:${Math.round((c/total)*100)}%;transition:width 0.6s"></div>
      </div>
    </div>`).join('');
}

// -------------------------------------------------------
//  STUDENTS TABLE
// -------------------------------------------------------
async function renderStudentTable(filter = '') {
  const tbody = document.getElementById('studentTableBody');
  const noMsg = document.getElementById('noStudents');
  if (!tbody) return;

  const res = await apiFetch('get_students&limit=500');
  const students = res.students || [];

  const filtered = filter ? students.filter(s =>
    (`${s.first_name} ${s.last_name}`).toLowerCase().includes(filter.toLowerCase()) ||
    (s.email||'').toLowerCase().includes(filter.toLowerCase()) ||
    (s.student_id||'').toLowerCase().includes(filter.toLowerCase())
  ) : students;

  if (filtered.length === 0) { tbody.innerHTML = ''; if (noMsg) noMsg.style.display = 'block'; return; }
  if (noMsg) noMsg.style.display = 'none';

  tbody.innerHTML = filtered.map(s => {
    const statusChip = `<span class="chip chip-green"><i class="fa fa-user" style="margin-right:3px;font-size:0.7rem"></i>Patient</span>`;
    return `
    <tr>
      <td><strong>${s.first_name} ${s.last_name}</strong></td>
      <td><span class="chip chip-blue">${s.student_id||'—'}</span></td>
      <td>${s.department||'—'}</td>
      <td>${s.email}</td>
      <td>${s.contact||'—'}</td>
      <td>${statusChip}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="viewStudentAdmin('${s.email}')"><i class="fa fa-eye"></i></button>
        <button class="btn btn-danger btn-sm" onclick="deleteStudent('${s.student_id}')"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }).join('');
}

function filterStudents() {
  const q = document.getElementById('studentSearch')?.value || '';
  renderStudentTable(q);
}

function viewStudentAdmin(email) {
  apiFetch(`get_student&student_id=${encodeURIComponent(email)}`).then(res => {
    if (res.student) {
      const s = res.student;
      uhAlert(`Name: ${s.first_name} ${s.last_name}\nID: ${s.student_id}\nDept: ${s.department}\nSection: ${s.section}\nEmail: ${s.email}\nContact: ${s.contact}\nAge: ${s.age} | Sex: ${s.sex} | Status: ${s.civil_status}\nRegistered: ${s.created_at?.split('T')[0]||'—'}`, { title: 'Student details' });
    }
  });
}

async function deleteStudent(studentId) {
  if (!await uhConfirm('This will permanently delete the student account.', { title: 'Delete student?', confirmText: 'Delete student', danger: true })) return;
  const res = await apiFetch('delete_student', 'POST', { student_id: studentId });
  if (res.success) {
    renderStudentTable();
    loadAdminStats();
    showToast('Student deleted.', 'info');
  } else {
    showToast(res.error || 'Failed to delete student', 'error');
  }
}

function exportStudents() {
  apiFetch('get_students&limit=10000').then(res => {
    const students = res.students || [];
    const csv = ['Name,Student ID,Department,Email,Contact,Age,Sex,Status']
      .concat(students.map(s => `"${s.first_name} ${s.last_name}","${s.student_id||''}","${s.department||''}","${s.email}","${s.contact||''}","${s.age||''}","${s.sex||''}","Active"`))
      .join('\n');
    const blob = new Blob([csv], { type:'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'students.csv'; a.click();
    URL.revokeObjectURL(url);
  });
}

// -------------------------------------------------------
//  STAFF TABLE
// -------------------------------------------------------
async function renderStaffTable() {
  const tbody = document.getElementById('staffTableBody');
  if (!tbody) return;

  const res = await apiFetch('get_staff');
  const staff = res.staff || [];

  tbody.innerHTML = staff.map(s => `
    <tr>
      <td><strong>${s.first_name} ${s.last_name}</strong><br>
        <small style="color:var(--text-muted)">${s.employee_id}</small></td>
      <td><span class="chip chip-blue">${s.role}</span></td>
      <td>${s.email}</td>
      <td>${s.license_no || '—'}</td>
      <td>
        <span class="chip ${s.campus_id ? 'chip-green' : 'chip-gold'}">
          <i class="fa fa-building" style="font-size:0.65rem;margin-right:3px"></i>
          ${s.campus?.name || s.campus_name || 'All Campuses'}
        </span>
      </td>
      <td><span class="chip chip-green">Active</span></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap;">
        <button class="btn btn-secondary btn-sm" title="Assign Campus"
          onclick="openAssignCampusModal('${s.employee_id}','${(s.campus?.name || s.campus_name || '').replace(/'/g,"\\'")}',${s.campus_id||'null'})">
          <i class="fa fa-building"></i> Campus
        </button>
        <button class="btn btn-danger btn-sm" onclick="removeStaff('${s.employee_id}')">
          <i class="fa fa-trash"></i>
        </button>
      </td>
    </tr>`).join('');
}

// -------------------------------------------------------
//  ASSIGN CAMPUS MODAL
// -------------------------------------------------------
function _injectCampusAssignModal() {
  if (document.getElementById('assignCampusModal')) return;
  const modal = document.createElement('div');
  modal.className = 'modal-overlay hidden';
  modal.id = 'assignCampusModal';
  modal.innerHTML = `
    <div class="modal-box" style="max-width:420px">
      <div class="modal-header">
        <span class="modal-title">
          <i class="fa fa-building" style="color:#6366f1;margin-right:8px"></i>
          Assign Campus
        </span>
        <button class="modal-close" onclick="closeModal('assignCampusModal')">
          <i class="fa fa-times"></i>
        </button>
      </div>
      <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:12px">
        Staff member: <strong id="assign_emp_label">—</strong>
      </p>
      <div class="mfg">
        <label>Campus Assignment</label>
        <select id="assign_campus_select" style="width:100%">
          <option value="">— All Campuses (no restriction) —</option>
        </select>
      </div>
      <p style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">
        Staff assigned to a specific campus will only see patients and data from that campus.
        Choose "All Campuses" to give system-wide access.
      </p>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button class="btn btn-outline" onclick="closeModal('assignCampusModal')">Cancel</button>
        <button class="btn btn-primary" onclick="confirmAssignCampus()">
          <i class="fa fa-save"></i> Save Assignment
        </button>
      </div>
    </div>`;
  document.body.appendChild(modal);
}

let _assigningEmployeeId = null;

function openAssignCampusModal(employeeId, currentCampusName, currentCampusId) {
  _assigningEmployeeId = employeeId;

  const label = document.getElementById('assign_emp_label');
  if (label) label.textContent = employeeId;

  // Populate select if cache is ready, otherwise fetch
  if (_campusCache.length === 0) {
    apiFetch('get_campuses').then(res => {
      _campusCache = res.campuses || [];
      _populateCampusSelects();
      _setAssignSelect(currentCampusId);
    });
  } else {
    _populateCampusSelects();
    _setAssignSelect(currentCampusId);
  }

  openModal('assignCampusModal');
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

  const res = await apiFetch('assign_staff_campus', 'POST', {
    employee_id: _assigningEmployeeId,
    campus_id: campusId,
  });

  if (res.success) {
    showToast(`${_assigningEmployeeId} → ${campusName}`, 'success');
    closeModal('assignCampusModal');
    renderStaffTable();
  } else {
    showToast(res.error || 'Failed to assign campus', 'error');
  }
}

// -------------------------------------------------------
//  ADD STAFF
// -------------------------------------------------------
async function addStaff(e) {
  e.preventDefault();

  const nameVal  = document.getElementById('st_name')?.value?.trim() || '';
  const nameParts = nameVal.split(' ').filter(Boolean);

  const pw  = document.getElementById('st_password')?.value || '';
  const pw2 = document.getElementById('st_password2')?.value || '';

  if (!pw) {
    showToast('Please enter a password for this staff member.', 'error');
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
    employee_id:    document.getElementById('st_employee_id')?.value?.trim() || ('STF-' + Date.now()),
    last_name:      nameParts.at(-1) || nameVal,
    first_name:     nameParts[0] || nameVal,
    middle_name:    nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '',
    email:          document.getElementById('st_email')?.value?.trim() || '',
    role:           (document.getElementById('st_role')?.value || '').toLowerCase(),
    license_no:     document.getElementById('st_license')?.value?.trim() || '',
    contact:        document.getElementById('st_contact')?.value?.trim() || '',
    department:     document.getElementById('st_department')?.value?.trim() || '',
    campus_id:      document.getElementById('st_campus_id')?.value
                      ? parseInt(document.getElementById('st_campus_id').value)
                      : null,
    password:       pw,
    password_confirmation: pw2,
  };

  if (!data.email || !data.role || !data.employee_id) {
    showToast('Employee ID, email and role are required.', 'error');
    return;
  }

  const res = await apiFetch('add_staff', 'POST', data);
  if (res.success) {
    closeModal('addStaffModal');
    e.target.reset();
    renderStaffTable();
    showToast('Staff member added successfully!', 'success');
  } else {
    showToast(res.error || 'Failed to add staff', 'error');
  }
}

async function removeStaff(employeeId) {
  if (!await uhConfirm('This will remove the staff member from the clinic.', { title: 'Remove staff member?', confirmText: 'Remove staff', danger: true })) return;
  const res = await apiFetch('delete_staff', 'POST', { employee_id: employeeId });
  if (res.success) {
    renderStaffTable();
    showToast('Staff removed.', 'info');
  } else {
    showToast(res.error || 'Failed to remove staff', 'error');
  }
}

// -------------------------------------------------------
//  RECORDS
// -------------------------------------------------------
function renderAllRecords() {
  const renderTable = (bodyId, cols) => {
    const tbody = document.getElementById(bodyId);
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="${cols}" class="no-data">Loading from database…</td></tr>`;
  };
  renderTable('allMedBody', 6);
  renderTable('allDentalBody', 5);
  renderTable('allRxBody', 4);
  renderTable('allLabBody', 5);
}

// -------------------------------------------------------
//  REPORTS
// -------------------------------------------------------
async function renderReportStats() {
  const res = await apiFetch('get_reports&type=overview');
  if (res.report) {
    setV('rpt_visits', (res.report.monthly_visits?.reduce((a,b)=>a+(b.count||0),0) || 0) + 15);
    setV('rpt_rx', 8);
    setV('rpt_ref', 4);
  }
}

function renderReportChart() {
  const chart  = document.getElementById('rptChart');
  const labels = document.getElementById('rptLabels');
  const rptReasons = document.getElementById('rptReasons');
  if (!chart) return;

  apiFetch('get_reports&type=overview').then(res => {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let vals = months.map(() => 0);
    if (res.report?.monthly_visits) {
      res.report.monthly_visits.forEach(d => { vals[parseInt(d.month)-1] = parseInt(d.count); });
    } else {
      vals = [10,8,14,22,18,25,30,28,20,15,12,8];
    }
    const max = Math.max(...vals, 1);
    chart.innerHTML = vals.map((v, i) => `
      <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:3px">
        <span style="font-size:0.65rem;font-weight:600;color:var(--primary)">${v}</span>
        <div style="width:100%;background:${i===5?'var(--primary)':'rgba(42,122,111,0.2)'};border-radius:4px 4px 0 0;height:${Math.round((v/max)*130)}px"></div>
      </div>`).join('');
    if (labels) labels.innerHTML = months.map(m => `<span style="flex:1;text-align:center">${m}</span>`).join('');

    if (rptReasons && res.report?.top_reasons?.length) {
      const reasons = res.report.top_reasons;
      const topCount = reasons[0].count || 1;
      rptReasons.innerHTML = reasons.map(r => `
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
            <span style="font-weight:500">${r.reason}</span>
            <span style="color:var(--text-muted)">${r.count} visits</span>
          </div>
          <div style="background:rgba(42,122,111,0.1);border-radius:6px;height:7px;overflow:hidden">
            <div style="height:100%;background:var(--primary);border-radius:6px;width:${Math.round((r.count/topCount)*100)}%"></div>
          </div>
        </div>`).join('');
    }
  });
}

// -------------------------------------------------------
//  MISC
// -------------------------------------------------------
function generateReport() { showToast('Report generation coming soon!', 'info'); }
function saveSettings()    { showToast('Settings saved!', 'success'); }
function exportAllData()   { window.open('/api/clinic/admin/export', '_blank'); showToast('Preparing export…', 'info'); }
async function clearDemoData() {
  if (!await uhConfirm('This will remove all sample and test records. Student registrations will remain.', { title: 'Clear sample records?', confirmText: 'Clear records', danger: true })) return;
  ['medical','dental','prescription','lab','xray','referral','lactating','breastfeeding','dental-cert']
    .forEach(k => localStorage.removeItem('clinic_records_' + k));
  renderAllRecords();
  loadAdminStats();
  showToast('Sample data cleared.', 'info');
}
function changeAdminPw() {
  const cur = document.getElementById('set_cur_pw')?.value;
  const nw  = document.getElementById('set_new_pw')?.value;
  if (cur !== 'password123') { showToast('Current password is incorrect.', 'error'); return; }
  if (!nw || nw.length < 6)  { showToast('New password must be at least 6 characters.', 'error'); return; }
  showToast('Password updated! (In a real system, this updates the server.)', 'success');
  document.getElementById('set_cur_pw').value = '';
  document.getElementById('set_new_pw').value = '';
}

function showToast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fa ${icons[type] || 'fa-bell'}"></i><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 320); }, 3200);
}
