'use strict';
/* ========================================================
   SUNN Clinic — Student Dashboard JS
   Connects to /api/clinic/student for database operations,
   with localStorage fallback for sample/offline use.
========================================================= */

const API = '/api/clinic/student';
let currentUser = null;

// -------------------------------------------------------
//  INIT
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', async () => {
  // auth-guard.js already verified the session — use the validated user
  const raw = sessionStorage.getItem('clinic_user');
  currentUser = raw ? JSON.parse(raw) : null;
  if (!currentUser) { window.location.replace('/login'); return; }

  // Sidebar info
  const fn = (currentUser.name || 'Student').split(' ')[0];
  setEl('sidebarName', currentUser.name || 'Student');
  setEl('sidebarRole', currentUser.dept || 'Student');
  // Show campus badge in header if available
  if (currentUser.campusName) {
    const campusBadgeEl = document.getElementById('studentCampusBadge');
    if (campusBadgeEl) {
      campusBadgeEl.textContent = currentUser.campusName;
      campusBadgeEl.style.display = 'inline-flex';
    }
    // Also inject a campus pill into the sidebar if element doesn't exist in HTML
    const sidebarRole = document.getElementById('sidebarRole');
    if (sidebarRole && !document.getElementById('sidebarCampus')) {
      const campusPill = document.createElement('div');
      campusPill.id = 'sidebarCampus';
      campusPill.style.cssText = 'font-size:0.65rem;color:var(--primary,#2a7a6f);font-weight:600;margin-top:2px;opacity:0.8;';
      campusPill.innerHTML = `<i class="fa fa-building" style="margin-right:3px;font-size:0.6rem"></i>${currentUser.campusName}`;
      sidebarRole.parentNode.insertBefore(campusPill, sidebarRole.nextSibling);
    }
  }
  setEl('sidebarAvatar', (currentUser.name || 'S')[0].toUpperCase());

  setGreeting(fn);
  startClock();
  buildSymptomsTable();
  renderSexVisibility();

  // Load profile FIRST so profileData is available for PHR pre-fill
  await loadProfile();
  // Then load all other data (health record, visits, appointments, meds)
  await loadAllData();
  // Finally, populate the main dashboard stat cards with real DB values
  loadStudentStats();
});

// -------------------------------------------------------
//  HELPERS
// -------------------------------------------------------
function setEl(id, text) { const e = document.getElementById(id); if (e) e.textContent = text; }
function getStudentNo()  { return currentUser?.studentId || currentUser?.identifier || 'STUDENT'; }

// -------------------------------------------------------
//  DASHBOARD STATS
//  Pulls per-student counts (visits, prescriptions, referrals,
//  next appointment date, and appointment trends) from
//  /api/clinic/student/stats.
// -------------------------------------------------------
async function loadStudentStats() {
  const studentNo = currentUser?.identifier || currentUser?.studentId;
  if (!studentNo) {
    renderAppointmentTrend([]);
    return;
  }

  try {
    const resp = await fetch(`${API}/stats?student_no=${encodeURIComponent(studentNo)}&_=${Date.now()}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    const data = await resp.json().catch(() => ({}));
    const s = (data && data.stats) || data || {};
    renderAppointmentTrend(Array.isArray(s.appointment_trends) ? s.appointment_trends : []);

    // Active Prescriptions
    const rx = s.active_prescriptions != null ? s.active_prescriptions : 0;
    const rxEl = document.getElementById('s-stat-rx');
    if (rxEl) rxEl.innerHTML = Number(rx).toLocaleString();

    // Open Referrals
    const refs = s.open_referrals != null ? s.open_referrals : 0;
    const refsEl = document.getElementById('s-stat-referrals');
    if (refsEl) refsEl.innerHTML = Number(refs).toLocaleString();

    const wellnessEl = document.getElementById('s-stat-wellness');
    const wellnessLabelEl = document.getElementById('s-stat-wellness-label');
    if (wellnessEl) wellnessEl.textContent = s.health_record_status || 'Not available';
    if (wellnessLabelEl) wellnessLabelEl.textContent = 'Based on your health record';

    // Next Appointment
    const next = s.next_appointment;
    const statEl = document.getElementById('s-stat-next-appt');
    const trendEl = document.getElementById('s-stat-appt-date');
    if (next) {
        const d = new Date(next);
        if (!isNaN(d.getTime())) {
            if (statEl) statEl.innerHTML = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            if (trendEl) trendEl.innerHTML = '<i class="fa fa-clock"></i> ' + d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
        } else {
            if (statEl) statEl.innerHTML = '—';
            if (trendEl) trendEl.innerHTML = 'No upcoming appointment';
        }
    } else {
        if (statEl) statEl.innerHTML = 'None';
        if (trendEl) trendEl.innerHTML = 'Choose a future date';
    }
  } catch (e) {
    renderAppointmentTrend([]);
  }
}

function renderAppointmentTrend(trends) {
  const chart = document.getElementById('s-appointment-chart');
  if (!chart) { return; }

  chart.replaceChildren();

  const width = 700;
  const height = 180;
  const padding = { top: 16, right: 12, bottom: 28, left: 24 };
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;
  const points = trends.map((trend) => ({
    label: String(trend.label || ''),
    count: Math.max(0, Number(trend.count) || 0),
  }));
  const maxCount = Math.max(...points.map((point) => point.count), 1);
  const xStep = points.length > 1 ? plotWidth / (points.length - 1) : plotWidth;
  const toPoint = (point, index) => ({
    x: padding.left + (points.length > 1 ? index * xStep : plotWidth / 2),
    y: padding.top + plotHeight - (point.count / maxCount) * plotHeight,
  });
  const coordinates = points.map(toPoint);
  const linePath = coordinates.map((point, index) => `${index ? 'L' : 'M'} ${point.x} ${point.y}`).join(' ');
  const hasAppointments = points.some((point) => point.count > 0);
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

  svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
  svg.setAttribute('role', 'img');
  svg.setAttribute('aria-label', 'Appointments over the last 30 days');

  const addSvgElement = (tag, attributes, text = '') => {
    const element = document.createElementNS('http://www.w3.org/2000/svg', tag);
    Object.entries(attributes).forEach(([name, value]) => element.setAttribute(name, value));
    if (text) { element.textContent = text; }
    svg.appendChild(element);
    return element;
  };

  [0, 0.5, 1].forEach((ratio) => {
    const y = padding.top + plotHeight * ratio;
    addSvgElement('line', {
      x1: padding.left,
      y1: y,
      x2: width - padding.right,
      y2: y,
      class: 'student-chart-gridline',
    });
  });

  if (hasAppointments && coordinates.length) {
    addSvgElement('path', { d: linePath, class: 'student-chart-line' });
  } else {
    addSvgElement('text', {
      x: width / 2,
      y: padding.top + plotHeight / 2,
      class: 'student-chart-empty',
      'text-anchor': 'middle',
    }, 'No appointment data yet');
  }

  points.forEach((point, index) => {
    const coordinate = coordinates[index];
    if (index % 5 === 0 || index === points.length - 1) {
      addSvgElement('text', {
        x: coordinate.x,
        y: height - 8,
        class: 'student-chart-label',
        'text-anchor': 'middle',
      }, point.label);
    }
  });

  chart.appendChild(svg);
}

async function apiFetch(action, method = 'GET', body = null) {
  // Map legacy action?param style to REST paths
  const actionMap = {
    'save_health_record': 'health-record',
    'get_health_record':  'health-record',
    'get_profile':        'profile',
    'update_profile':     'profile',
    'upload_picture':     'profile/picture',
    'get_visits':         'visits',
    'save_visit':         'visits',
    'get_appointments':   'appointments',
    'save_appointment':   'appointments',
    'cancel_appointment': 'appointments',
    'get_medications':    'medications',
    'save_medication':    'medications',
    'delete_medication':  'medications',
    'search_student':     'search',
  };

  try {
    const [rawAction, ...extraParts] = action.split('&');
    const extraParams = new URLSearchParams(extraParts.join('&'));
    let path = actionMap[rawAction] || rawAction;
    let url;
    let resolvedMethod = method;

    if (rawAction === 'cancel_appointment') {
      const id = body?.id ?? 0;
      url = `${API}/appointments/${id}`;
      resolvedMethod = 'DELETE';
    } else if (rawAction === 'delete_medication') {
      const id = body?.id ?? 0;
      url = `${API}/medications/${id}`;
      resolvedMethod = 'DELETE';
    } else if (rawAction === 'save_health_record' || rawAction === 'update_profile' || rawAction === 'save_appointment' || rawAction === 'save_medication' || rawAction === 'upload_picture') {
      url = `${API}/${path}`;
      resolvedMethod = 'POST';
    } else if (rawAction === 'update_profile') {
      url = `${API}/profile`;
      resolvedMethod = 'PUT';
    } else {
      // GET with query params
      const qStr = [extraParams.toString()].filter(Boolean).join('&');
      url = `${API}/${path}${qStr ? '?' + qStr : ''}`;
    }

    const opts = { method: resolvedMethod, headers: { 'Content-Type': 'application/json' } };
    if (body && resolvedMethod !== 'GET') opts.body = JSON.stringify(body);
    const resp = await fetch(url, opts);
    return await resp.json();
  } catch {
    return { error: 'offline' };
  }
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

function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }
function openModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }

// -------------------------------------------------------
//  CLOCK & GREETING
// -------------------------------------------------------
function startClock() {
  function tick() {
    const now  = new Date();
    const time = uhFormatTime(now);
    const date = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(now);
    setEl('topClock', time);
    setEl('topDate', date);
    setEl('greetDate', date);
    setEl('greetDay', '');
  }
  tick();
  setInterval(tick, 1000);
}

function setGreeting(name) {
  const h  = new Date().getHours();
  const gr = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  setEl('greetMsg', `${gr}, ${name}!`);
}

// -------------------------------------------------------
//  PAGE NAVIGATION
// -------------------------------------------------------
function showPage(pageId, el) {
  document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
  const pg = document.getElementById('page-' + pageId);
  if (pg) pg.classList.add('active');

  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');

  const titles = {
    dashboard: 'Dashboard', 'health-record': 'Personal Health Record',
    appointments: 'Appointments', 'visit-log': 'Visit Log',
    medications: 'Medications Tracking', alerts: 'Health Alerts',
    profile: 'My Profile'
  };
  setEl('pageTitle', titles[pageId] || pageId);

  // When the student opens the health record page, pre-fill any
  // empty fields from their registration / profile data.
  if (pageId === 'health-record') {
    prefillPHRFromProfile();
  }
}

function doLogout() {
  sessionStorage.removeItem('clinic_user');
  window.location.href = '/login';
}

function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
}

// -------------------------------------------------------
//  LOAD ALL DATA
// -------------------------------------------------------
async function loadAllData() {
  const sno = getStudentNo();

  // Visits
  const visitsRes = await apiFetch(`get_visits&student_no=${encodeURIComponent(sno)}`);
  const visits = Array.isArray(visitsRes.visits) ? visitsRes.visits : [];
  renderVisits(visits);
  setEl('sStatVisits', visits.length);

  // Appointments
  const apptRes = await apiFetch(`get_appointments&student_no=${encodeURIComponent(sno)}`);
  const appts   = Array.isArray(apptRes.appointments) ? apptRes.appointments : [];
  renderAppointments(appts);
  setEl('sStatAppts', appts.filter(a => a.status === 'pending' || a.status === 'confirmed').length);

  // Medications
  const medRes = await apiFetch(`get_medications&student_no=${encodeURIComponent(sno)}`);
  const meds = Array.isArray(medRes.medications) ? medRes.medications : [];
  renderMedications(meds);
  setEl('sStatMeds', meds.filter(m => m.active != 0).length);

  // Health record
  const hrRes = await apiFetch(`get_health_record&student_no=${encodeURIComponent(sno)}`);
  if (hrRes.record) {
    populatePHR(hrRes.record);
    renderProfileWidget(hrRes.record);
    generateAlerts(hrRes.record);
    markAsPatient();
  } else {
  }
  // Pre-fill any remaining empty fields from registration/profile data
  prefillPHRFromProfile();
}

// -------------------------------------------------------
//  SYMPTOMS TABLE (NAS grid)
// -------------------------------------------------------
const SYMPTOMS = [
  ['sym_headache',              'Headache'],
  ['sym_dizziness',             'Dizziness'],
  ['sym_fainting',              'Fainting'],
  ['sym_blurring_vision',       'Blurring of vision'],
  ['sym_hearing_difficulty',    'Difficulty of hearing'],
  ['sym_back_pain',             'Back pain'],
  ['sym_chest_pain',            'Chest pain'],
  ['sym_breathing_difficulty',  'Difficulty of breathing'],
  ['sym_cough',                 'Cough'],
  ['sym_sore_throat',           'Sore throat'],
  ['sym_runny_nose',            'Runny nose'],
  ['sym_nose_bleed',            'Nose bleed'],
  ['sym_vomiting',              'Vomiting'],
  ['sym_nausea',                'Nausea'],
  ['sym_abdominal_pain',        'Abdominal pain'],
  ['sym_fever',                 'Fever'],
  ['sym_frequent_urination',    'Frequent urination'],
  ['sym_weight_loss',           'Loss of weight'],
  ['sym_difficulty_sleeping',   'Difficult of sleeping'],
  ['sym_nervousness',           'Nervousness'],
];

function buildSymptomsTable() {
  const tbody = document.getElementById('symptomsBody');
  if (!tbody) return;

  const col1 = SYMPTOMS.slice(0, 5);
  const col2 = SYMPTOMS.slice(5, 10);
  const col3 = SYMPTOMS.slice(10, 15);
  const col4 = SYMPTOMS.slice(15, 20);

  let html = '';
  for (let r = 0; r < 5; r++) {
    html += '<tr>';
    [col1, col2, col3, col4].forEach(col => {
      const [fid, label] = col[r] || ['', ''];
      if (fid) {
        html += `<td style="text-align:left">${label}</td>`;
        ['N','A','S'].forEach(v => {
          html += `<td><input type="radio" name="${fid}" id="${fid}_${v}" value="${v}" ${v==='N'?'checked':''}></td>`;
        });
      } else {
        html += '<td></td><td></td><td></td><td></td>';
      }
    });
    html += '</tr>';
  }
  tbody.innerHTML = html;
}

// -------------------------------------------------------
//  HEALTH RECORD — PHR
// -------------------------------------------------------
function renderSexVisibility() {
  const sel = document.getElementById('phr_sex');
  if (!sel) return;
  const toggle = () => {
    const sec = document.getElementById('femaleSection');
    if (sec) sec.style.display = sel.value === 'Female' ? 'block' : 'none';
  };
  sel.addEventListener('change', toggle);
  toggle();
}

function gv(id) { return (document.getElementById(id) || {}).value || ''; }
function sv(id, val) { const e = document.getElementById(id); if (e) e.value = val || ''; }

function collectPHR() {
  const data = {
    student_no: getStudentNo(),
    course_year: gv('phr_course_year'),
    age:          gv('phr_age') || null,
    date_of_birth:gv('phr_date_of_birth') || null,
    blood_type:   gv('phr_blood_type'),
    height_cm:    gv('phr_height_cm') || null,
    weight_kg:    gv('phr_weight_kg') || null,
    sex:          gv('phr_sex'),
    civil_status: gv('phr_civil_status'),
    last_name:    gv('phr_last_name'),
    first_name:   gv('phr_first_name'),
    middle_name:  gv('phr_middle_name'),
    address:      gv('phr_address'),
    contact:      gv('phr_contact'),
    ec_name:       gv('phr_ec_name'),
    ec_relationship: gv('phr_ec_relationship'),
    ec_address:    gv('phr_ec_address'),
    ec_contact:    gv('phr_ec_contact'),
    allergy_detail:         gv('phr_allergy_detail'),
    accident_detail:        gv('phr_accident_detail'),
    hospitalization_detail: gv('phr_hospitalization_detail'),
    surgical_detail:        gv('phr_surgical_detail'),
    family_history:         gv('phr_family_history'),
    bp:   gv('phr_bp'),
    pr:   gv('phr_pr'),
    temp: gv('phr_temp') || null,
    record_date:  gv('phr_record_date') || null,
    signature_name: gv('phr_signature_name'),
  };

  // Symptoms (NAS radios)
  SYMPTOMS.forEach(([fid]) => {
    const checked = document.querySelector(`input[name="${fid}"]:checked`);
    data[fid] = checked ? checked.value : 'N';
  });

  // Immunizations
  ['imm_chicken_pox','imm_typhoid','imm_hepatitis_a','imm_hepatitis_b','imm_tetanus','imm_covid'].forEach(id => {
    data[id] = document.getElementById('phr_' + id)?.checked ? 1 : 0;
  });

  // Menstrual
  const menReg = document.getElementById('phr_menstrual_regular');
  const menIrr = document.getElementById('phr_menstrual_irregular');
  data.menstrual_regular   = menReg?.checked ? 1 : 0;
  data.menstrual_irregular = menIrr?.checked ? 1 : 0;

  const dysmen = document.querySelector('input[name="phr_dysmenorrhea"]:checked');
  data.dysmenorrhea = dysmen ? dysmen.value : '';

  const ebl = document.querySelector('input[name="phr_ebl"]:checked');
  data.excessive_blood_flow = ebl ? ebl.value : '';
  data.avg_days = gv('phr_avg_days');

  return data;
}

function populatePHR(rec) {
  if (!rec) return;
  const map = {
    phr_course_year:    rec.course_year,
    phr_age:            rec.age,
    phr_date_of_birth:  rec.date_of_birth,
    phr_blood_type:     rec.blood_type,
    phr_height_cm:      rec.height_cm,
    phr_weight_kg:      rec.weight_kg,
    phr_ec_name:        rec.ec_name,
    phr_ec_relationship:rec.ec_relationship,
    phr_ec_address:     rec.ec_address,
    phr_ec_contact:     rec.ec_contact,
    phr_allergy_detail: rec.allergy_detail,
    phr_accident_detail:rec.accident_detail,
    phr_hospitalization_detail: rec.hospitalization_detail,
    phr_surgical_detail:rec.surgical_detail,
    phr_family_history: rec.family_history,
    phr_bp:   rec.bp, phr_pr: rec.pr, phr_temp: rec.temp,
    phr_record_date:    rec.record_date,
    phr_signature_name: rec.signature_name,
    phr_avg_days:       rec.avg_days,
  };

  // Pre-fill name from user if not in DB
  if (!rec.last_name && currentUser.name) {
    const parts = currentUser.name.split(' ');
    map.phr_last_name   = parts[0] || '';
    map.phr_first_name  = parts[1] || '';
    map.phr_middle_name = parts[2] || '';
  } else {
    map.phr_last_name   = rec.last_name;
    map.phr_first_name  = rec.first_name;
    map.phr_middle_name = rec.middle_name;
  }

  // Sex & civil status from user if not in rec
  const sexEl = document.getElementById('phr_sex');
  if (sexEl) sexEl.value = rec.sex || currentUser.sex || '';
  renderSexVisibility();

  Object.entries(map).forEach(([id, val]) => sv(id, val));

  // Symptoms radios
  SYMPTOMS.forEach(([fid]) => {
    const val = rec[fid] || 'N';
    const radio = document.getElementById(`${fid}_${val}`);
    if (radio) radio.checked = true;
  });

  // Immunization checkboxes
  ['imm_chicken_pox','imm_typhoid','imm_hepatitis_a','imm_hepatitis_b','imm_tetanus','imm_covid'].forEach(id => {
    const el = document.getElementById('phr_' + id);
    if (el) el.checked = !!parseInt(rec[id]);
  });

  // Menstrual
  if (rec.menstrual_regular && document.getElementById('phr_menstrual_regular'))
    document.getElementById('phr_menstrual_regular').checked = !!parseInt(rec.menstrual_regular);
  if (rec.menstrual_irregular && document.getElementById('phr_menstrual_irregular'))
    document.getElementById('phr_menstrual_irregular').checked = !!parseInt(rec.menstrual_irregular);

  if (rec.dysmenorrhea) {
    const el = document.querySelector(`input[name="phr_dysmenorrhea"][value="${rec.dysmenorrhea}"]`);
    if (el) el.checked = true;
  }
  if (rec.excessive_blood_flow) {
    const el = document.querySelector(`input[name="phr_ebl"][value="${rec.excessive_blood_flow}"]`);
    if (el) el.checked = true;
  }
}

// -------------------------------------------------------
//  PRE-FILL PHR FROM REGISTRATION / PROFILE DATA
//  Called when the student opens the Health Record page.
//  Only fills fields that are currently empty — never
//  overwrites data the student has already entered or
//  that was loaded from a saved record.
// -------------------------------------------------------
function prefillPHRFromProfile() {
  const p = profileData;
  if (!p || !Object.keys(p).length) return;

  // Helper: set a text/number/date input only if currently empty
  const fill = (id, val) => {
    if (!val) return;
    const el = document.getElementById(id);
    if (el && !el.value) el.value = val;
  };

  // Helper: set a select only if currently unset (empty / default)
  const fillSelect = (id, val) => {
    if (!val) return;
    const el = document.getElementById(id);
    if (el && !el.value) el.value = val;
  };

  // Fields that come directly from registration / profile
  fill('phr_last_name',   p.last_name);
  fill('phr_first_name',  p.first_name);
  fill('phr_middle_name', p.middle_name);
  fill('phr_contact',     p.contact);
  fill('phr_address',     p.address);
  fill('phr_course_year', p.course_year);
  fill('phr_age',         p.age);
  fill('phr_date_of_birth', p.date_of_birth);
  fill('phr_blood_type',  p.blood_type);

  fillSelect('phr_sex',          p.sex);
  fillSelect('phr_civil_status', p.civil_status);
  fillSelect('phr_blood_type',   p.blood_type);  // also a select in some themes

  // Re-trigger sex visibility (shows/hides female section)
  renderSexVisibility();
}

async function submitHealthRecord() {
  const data = collectPHR();

  if (!data.signature_name) {
    showToast('Please add your signature (printed name) before saving.', 'error');
    return;
  }

  // Save to localStorage as backup
  localStorage.setItem(`phr_${getStudentNo()}`, JSON.stringify(data));

  // Try API
  const res = await apiFetch('save_health_record', 'POST', data);
  if (res.error && res.error !== 'offline') {
    showToast('Error: ' + res.error, 'error');
    return;
  }
  if (res.error === 'offline') {
    showToast('Saved locally (offline mode). Will sync when connected.', 'info');
  } else {
    showToast('Health record saved successfully!', 'success');
  }

  renderProfileWidget(data);
  generateAlerts(data);
  markAsPatient();
}

// -------------------------------------------------------
//  PATIENT STATUS — shown once a PHR has been saved
// -------------------------------------------------------
function markAsPatient() {
  // Greeting banner badge
  const badge = document.getElementById('patientStatusBadge');
  if (badge) badge.style.display = 'inline-flex';

  // Sidebar role label
  const roleEl = document.getElementById('sidebarRole');
  if (roleEl && !roleEl.dataset.patient) {
    roleEl.dataset.patient = '1';
    roleEl.innerHTML = `<span style="display:inline-flex;align-items:center;gap:4px;">
      <i class="fa fa-heart-pulse" style="font-size:0.65rem;color:var(--primary,#2a7a6f)"></i> Patient
    </span>`;
  }
}

async function clearPHR() {
  if (!await uhConfirm('This will clear all unsaved form fields.', { title: 'Clear form?', confirmText: 'Clear fields', danger: true })) return;
  document.getElementById('phrForm')?.reset();
  buildSymptomsTable();
}

function renderProfileWidget(rec) {
  const c = document.getElementById('profileSummaryWidget');
  if (!c || !rec) return;
  const rows = [
    { label: 'Blood Type', val: rec.blood_type || '—' },
    { label: 'Age',        val: rec.age        || '—' },
    { label: 'Height',     val: rec.height_cm ? rec.height_cm + ' cm' : '—' },
    { label: 'Weight',     val: rec.weight_kg  ? rec.weight_kg + ' kg' : '—' },
    { label: 'Allergy',    val: rec.allergy_detail ? (rec.allergy_detail.substring(0, 40) + (rec.allergy_detail.length > 40 ? '…' : '')) : 'None on file' },
  ];
  c.innerHTML = rows.map(r => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--border)">
      <span style="font-size:0.7rem;color:var(--text-muted)">${r.label}</span>
      <span style="font-size:0.78rem;font-weight:600">${r.val}</span>
    </div>`).join('');
}

function generateAlerts(rec) {
  const alerts = [];
  if (rec.allergy_detail && rec.allergy_detail.trim() && rec.allergy_detail.toLowerCase() !== 'none') {
    alerts.push({ level: 'red', icon: 'fa-circle-exclamation', label: 'Known Allergy', text: rec.allergy_detail.substring(0, 100) });
  }
  if (rec.surgical_detail && rec.surgical_detail.trim() && rec.surgical_detail.toLowerCase() !== 'none') {
    alerts.push({ level: 'gold', icon: 'fa-scalpel', label: 'Surgical History', text: rec.surgical_detail.substring(0, 100) });
  }
  if (rec.hospitalization_detail && rec.hospitalization_detail.trim() && rec.hospitalization_detail.toLowerCase() !== 'none') {
    alerts.push({ level: 'blue', icon: 'fa-hospital', label: 'Hospitalization History', text: rec.hospitalization_detail.substring(0, 100) });
  }

  const cnt = alerts.length;
  const badge = document.getElementById('alertBadge');
  const dot   = document.getElementById('notifDot');
  if (badge) { badge.textContent = cnt; badge.style.display = cnt > 0 ? 'inline' : 'none'; }
  if (dot)   dot.style.display   = cnt > 0 ? 'block' : 'none';
  setEl('sStatAlerts', cnt);

  const banner = document.getElementById('criticalBanner');
  const ctext  = document.getElementById('criticalText');
  if (alerts.filter(a => a.level === 'red').length > 0) {
    if (banner) banner.style.display = 'flex';
    if (ctext)  ctext.textContent = alerts.filter(a => a.level === 'red').map(a => a.text).join(' | ');
  }

  const list = document.getElementById('alertsList');
  if (!list) return;
  if (alerts.length === 0) {
    list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:24px;font-size:0.8rem">No active alerts. Great health record!</p>';
    return;
  }
  const colMap = { red: 'chip-red', gold: 'chip-gold', blue: 'chip-blue' };
  list.innerHTML = alerts.map(a => `
    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:rgba(255,255,255,0.04);border-radius:var(--radius-sm);border:1px solid rgba(255,255,255,0.07);margin-bottom:10px;">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(231,76,60,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fa ${a.icon}" style="color:${a.level==='red'?'#e74c3c':a.level==='gold'?'var(--gold)':'#3498db'};font-size:0.9rem"></i>
      </div>
      <div>
        <div style="font-size:0.82rem;font-weight:700">${a.label}</div>
        <div style="font-size:0.74rem;color:var(--text-muted);margin-top:3px">${a.text}</div>
      </div>
    </div>`).join('');
}

// -------------------------------------------------------
//  VISITS
// -------------------------------------------------------
function getDemoVisits() {
  return [
    { visit_date:'2026-06-20', reason:'Headache and fever', symptoms:'Headache, mild fever (37.8°C)', treatment:'Paracetamol 500mg given, rest advised', bp:'110/70', temp:'37.8', pulse:'82', nurse:'Nurse M. Santos', disposition:'Sent back to class' },
    { visit_date:'2026-05-15', reason:'Stomachache', symptoms:'Nausea, abdominal pain', treatment:'Antacid given', bp:'115/75', temp:'36.6', pulse:'78', nurse:'Nurse M. Santos', disposition:'Sent back to class' },
    { visit_date:'2026-04-03', reason:'Sports injury', symptoms:'Sprained ankle during PE', treatment:'Ice pack, compression bandage', temp:'36.5', nurse:'Nurse R. Cruz', disposition:'Sent home' },
  ];
}

function getLocalVisits() { return JSON.parse(localStorage.getItem(`visits_${getStudentNo()}`) || '[]'); }
function getLocalAppts()  { return JSON.parse(localStorage.getItem(`appts_${getStudentNo()}`) || '[]');  }
function getLocalMeds()   { return JSON.parse(localStorage.getItem(`meds_${getStudentNo()}`) || '[]');   }

function renderVisits(dbVisits) {
  const all = [...getDemoVisits(), ...dbVisits];

  // Recent (dashboard widget)
  const recentEl = document.getElementById('recentVisitsList');
  if (recentEl) {
    if (all.length === 0) {
      recentEl.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;font-size:0.82rem">No visits recorded.</p>';
    } else {
      recentEl.innerHTML = all.slice(0, 4).map(v => `
        <div class="visit-item">
          <div class="visit-dot"><i class="fa fa-stethoscope"></i></div>
          <div class="visit-info">
            <div class="vtitle">${v.reason}</div>
            <div class="vdetail">${v.visit_date} &bull; ${v.nurse || 'Clinic Staff'}</div>
          </div>
          <span class="chip chip-green">Done</span>
        </div>`).join('');
    }
  }

  // Timeline (visit log page)
  const tl = document.getElementById('visitTimeline');
  if (!tl) return;
  if (all.length === 0) {
    tl.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:24px;font-size:0.82rem">No visits recorded.</p>';
    return;
  }
  tl.innerHTML = all.map((v, i) => `
    <div class="tl-item">
      <div>
        <div class="tl-dot"><i class="fa fa-stethoscope" style="font-size:0.65rem"></i></div>
        ${i < all.length - 1 ? '<div class="tl-line" style="margin-top:4px;margin-bottom:4px;"></div>' : ''}
      </div>
      <div style="flex:1;padding-bottom:16px;">
        <div class="tl-date">${v.visit_date}</div>
        <div class="tl-title">${v.reason}</div>
        <div class="tl-desc">
          ${v.symptoms ? `<strong>Symptoms:</strong> ${v.symptoms}<br>` : ''}
          ${v.treatment ? `<strong>Treatment:</strong> ${v.treatment}<br>` : ''}
          ${v.bp || v.temp ? `<strong>Vitals:</strong>${v.temp ? ' Temp: ' + v.temp + '°C' : ''}${v.bp ? ' | BP: ' + v.bp : ''}${v.pulse ? ' | Pulse: ' + v.pulse + ' bpm' : ''}<br>` : ''}
          <strong>Attended by:</strong> ${v.nurse || '—'} &bull; <strong>Disposition:</strong> ${v.disposition || '—'}
        </div>
      </div>
    </div>`).join('');
}

// -------------------------------------------------------
//  APPOINTMENTS
// -------------------------------------------------------
function openApptModal() {
  openModal('apptModal');
}

async function saveAppointment(e) {
  e.preventDefault();
  const appt = {
    student_no:   getStudentNo(),
    service_type: gv('appt_service'),
    reason:       gv('appt_reason'),
    status:       'pending',
    id:           Date.now(),
  };

  // Try API
  const res = await apiFetch('save_appointment', 'POST', appt);
  if (res.error && res.error !== 'offline') {
    showToast('Error: ' + res.error, 'error');
    return;
  }

  // Save locally as well
  const local = getLocalAppts();
  local.push(appt);
  localStorage.setItem(`appts_${getStudentNo()}`, JSON.stringify(local));

  closeModal('apptModal');
  document.getElementById('apptForm')?.reset();
  const allAppts = res.error === 'offline' ? local : [...local];
  renderAppointments(allAppts);
  setEl('sStatAppts', allAppts.filter(a => a.status === 'pending' || a.status === 'confirmed').length);
  showToast('Appointment booked!', 'success');
}

function renderAppointments(appts) {
  const tbody = document.getElementById('appointmentsBody');
  if (!tbody) return;
  if (appts.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">No appointments yet. Book one!</td></tr>';
    return;
  }
  const statusChip = { pending: 'chip-gold', confirmed: 'chip-green', completed: 'chip-blue', cancelled: 'chip-gray', deleted: 'chip-gray' };
  tbody.innerHTML = appts.map(a => `
    <tr>
      <td>${a.appt_date || 'Awaiting clinic schedule'}</td>
      <td>${a.appt_time ? uhFormatTime(a.appt_time) : 'TBD'}</td>
      <td>${a.service_type || '—'}</td>
      <td>${a.reason ? a.reason.substring(0, 40) + (a.reason.length > 40 ? '…' : '') : '—'}</td>
      <td><span class="chip ${statusChip[a.status] || 'chip-gray'}">${a.awaiting_schedule ? 'Accepted — awaiting schedule' : (a.status || 'pending')}</span></td>
      <td>
        ${(a.status === 'pending' || !a.status) ? `<button class="btn btn-danger btn-sm" onclick="cancelAppt('${a.id}')"><i class="fa fa-trash"></i> Delete</button>` : '—'}
      </td>
    </tr>`).join('');
}

async function cancelAppt(id) {
  if (!await uhConfirm('This will permanently delete the selected appointment request.', { title: 'Delete appointment?', confirmText: 'Delete appointment', danger: true })) return;
  await apiFetch('cancel_appointment', 'POST', { id });

  // Also update local
  const local = getLocalAppts().map(a => String(a.id) === String(id) ? { ...a, status: 'deleted' } : a);
  localStorage.setItem(`appts_${getStudentNo()}`, JSON.stringify(local));
  renderAppointments(local);
  showToast('Appointment deleted.', 'success');
}

// -------------------------------------------------------
//  MEDICATIONS
// -------------------------------------------------------
function openMedModal() { openModal('medModal'); }

async function saveMedication(e) {
  e.preventDefault();
  const med = {
    student_no:      getStudentNo(),
    name:            gv('med_name'),
    dosage:          gv('med_dosage'),
    schedule:        gv('med_schedule'),
    purpose:         gv('med_purpose'),
    school_kept:     document.getElementById('med_school_kept')?.checked ? 1 : 0,
    consent_on_file: document.getElementById('med_consent')?.checked ? 1 : 0,
    active:          1,
    id:              Date.now(),
  };

  const res = await apiFetch('save_medication', 'POST', med);
  if (res.error && res.error !== 'offline') { showToast('Error: ' + res.error, 'error'); return; }

  const local = getLocalMeds();
  local.push(med);
  localStorage.setItem(`meds_${getStudentNo()}`, JSON.stringify(local));

  closeModal('medModal');
  document.getElementById('medForm')?.reset();
  renderMedications(local);
  setEl('sStatMeds', local.filter(m => m.active != 0).length);
  showToast('Medication saved!', 'success');
}

function renderMedications(meds) {
  const tbody = document.getElementById('medsBody');
  if (!tbody) return;
  if (meds.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">No medications logged.</td></tr>';
    return;
  }
  tbody.innerHTML = meds.map((m, i) => `
    <tr>
      <td><strong>${m.name}</strong></td>
      <td>${m.dosage}</td>
      <td>${m.schedule || '—'}</td>
      <td>${m.purpose || '—'}</td>
      <td><span class="chip ${parseInt(m.school_kept) ? 'chip-green' : 'chip-gray'}">${parseInt(m.school_kept) ? 'Yes' : 'No'}</span></td>
      <td><span class="chip ${parseInt(m.consent_on_file) ? 'chip-green' : 'chip-gold'}">${parseInt(m.consent_on_file) ? 'On File' : 'Needed'}</span></td>
      <td>
        <button class="btn btn-danger btn-sm" onclick="removeMed(${i}, '${m.id}')"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`).join('');
}

async function removeMed(idx, id) {
  if (!await uhConfirm('This will remove the selected medication from your list.', { title: 'Remove medication?', confirmText: 'Remove medication', danger: true })) return;
  await apiFetch('delete_medication', 'POST', { id });
  const local = getLocalMeds();
  // Remove by id or index
  const updated = local.filter((m, i) => String(m.id) !== String(id) && i !== idx);
  localStorage.setItem(`meds_${getStudentNo()}`, JSON.stringify(updated));
  renderMedications(updated);
  setEl('sStatMeds', updated.filter(m => m.active != 0).length);
  showToast('Medication removed.', 'info');
}

// -------------------------------------------------------
//  PROFILE
// -------------------------------------------------------
let profileData = {};

async function loadProfile() {
  const res = await apiFetch(`get_profile&student_no=${encodeURIComponent(getStudentNo())}`);
  if (res.profile) {
    profileData = res.profile;
    renderProfile(profileData);
  }
}

function renderProfile(p) {
  const fullName = `${p.first_name} ${p.middle_name ? p.middle_name + ' ' : ''}${p.last_name}`;
  setEl('profileFullName', fullName);
  setEl('profileStudentId', 'ID: ' + p.student_id);
  setEl('profileEmail', p.email);
  setEl('pf_dept',    p.department   || '—');
  setEl('pf_course',  p.course_year  || '—');
  setEl('pf_section', p.section      || '—');
  setEl('pf_contact', p.contact      || '—');
  setEl('pf_sex',     p.sex          || '—');
  setEl('pf_civil',   p.civil_status || '—');
  setEl('pf_address', p.address      || '—');
  // Home Campus — read-only
  // NOTE: campus_id is intentionally NOT editable by students.
  // It is set at registration and can only be changed by an admin.
  setEl('pf_campus', p.campus_name || currentUser?.campusName || '—');

  // Avatar — show picture if available, else initial letter
  const initial = (p.first_name || 'S')[0].toUpperCase();
  const avatarEl    = document.getElementById('profileAvatar');
  const avatarImg   = document.getElementById('profileAvatarImg');
  const sidebarAv   = document.getElementById('sidebarAvatar');
  const sidebarImg  = document.getElementById('sidebarAvatarImg');

  if (p.profile_picture) {
    if (avatarEl)  avatarEl.style.display  = 'none';
    if (avatarImg) { avatarImg.src = p.profile_picture; avatarImg.style.display = 'block'; }
    // Sidebar picture
    if (sidebarImg) { sidebarImg.src = p.profile_picture; sidebarImg.style.display = 'block'; }
    if (sidebarAv)  sidebarAv.style.display = 'none';
  } else {
    if (avatarEl)  { avatarEl.textContent = initial; avatarEl.style.display = 'flex'; }
    if (avatarImg) avatarImg.style.display = 'none';
    if (sidebarAv) { sidebarAv.textContent = initial; sidebarAv.style.display = 'flex'; }
    if (sidebarImg) sidebarImg.style.display = 'none';
  }

  setEl('sidebarName', fullName);
  setEl('sidebarRole', p.department || 'Student');
}

async function handleAvatarUpload(event) {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { showToast('Image must be under 2MB.', 'error'); return; }

  const reader = new FileReader();
  reader.onload = async (e) => {
    const base64 = e.target.result;

    // Preview immediately
    const avatarEl  = document.getElementById('profileAvatar');
    const avatarImg = document.getElementById('profileAvatarImg');
    const sidebarAv  = document.getElementById('sidebarAvatar');
    const sidebarImg = document.getElementById('sidebarAvatarImg');
    if (avatarEl)  avatarEl.style.display  = 'none';
    if (avatarImg) { avatarImg.src = base64; avatarImg.style.display = 'block'; }
    if (sidebarAv)  sidebarAv.style.display = 'none';
    if (sidebarImg) { sidebarImg.src = base64; sidebarImg.style.display = 'block'; }

    // Upload to server
    const res = await apiFetch('upload_picture', 'POST', { student_no: getStudentNo(), picture: base64 });
    if (res.error) {
      showToast(res.error, 'error');
    } else {
      profileData.profile_picture = base64;
      showToast('Profile photo updated!', 'success');
    }
  };
  reader.readAsDataURL(file);
  // Reset input so same file can be re-selected
  event.target.value = '';
}

function openEditProfile() {
  const p = profileData;
  document.getElementById('ep_first_name').value   = p.first_name   || '';
  document.getElementById('ep_last_name').value    = p.last_name    || '';
  document.getElementById('ep_middle_name').value  = p.middle_name  || '';
  document.getElementById('ep_contact').value      = p.contact      || '';
  document.getElementById('ep_address').value      = p.address      || '';
  document.getElementById('ep_department').value   = p.department   || '';
  document.getElementById('ep_course_year').value  = p.course_year  || '';
  document.getElementById('ep_section').value      = p.section      || '';
  document.getElementById('ep_sex').value          = p.sex          || '';
  document.getElementById('ep_civil_status').value = p.civil_status || '';
  document.getElementById('ep_current_password').value = '';
  document.getElementById('ep_new_password').value     = '';
  openModal('editProfileModal');
}

async function saveProfile(e) {
  e.preventDefault();
  const newPass = document.getElementById('ep_new_password').value;
  const curPass = document.getElementById('ep_current_password').value;

  if (newPass && !curPass) {
    showToast('Please enter your current password to change it.', 'error');
    return;
  }

  const payload = {
    student_no:   getStudentNo(),
    first_name:   document.getElementById('ep_first_name').value.trim(),
    last_name:    document.getElementById('ep_last_name').value.trim(),
    middle_name:  document.getElementById('ep_middle_name').value.trim(),
    contact:      document.getElementById('ep_contact').value.trim(),
    address:      document.getElementById('ep_address').value.trim(),
    department:   document.getElementById('ep_department').value,
    course_year:  document.getElementById('ep_course_year').value.trim(),
    section:      document.getElementById('ep_section').value.trim(),
    sex:          document.getElementById('ep_sex').value,
    civil_status: document.getElementById('ep_civil_status').value,
  };

  if (newPass) {
    payload.current_password = curPass;
    payload.new_password     = newPass;
  }

  const btn = document.getElementById('saveProfileBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

  const res = await apiFetch('update_profile', 'POST', payload);

  btn.disabled = false;
  btn.innerHTML = '<i class="fa fa-save"></i> Save Changes';

  if (res.error) {
    showToast(res.error, 'error');
    return;
  }

  // Update session + local state
  profileData = res.profile;
  const updatedUser = {
    ...currentUser,
    name:       `${res.profile.first_name} ${res.profile.last_name}`,
    firstName:  res.profile.first_name,
    lastName:   res.profile.last_name,
    dept:       res.profile.department,
    section:    res.profile.section,
    contact:    res.profile.contact,
    sex:        res.profile.sex,
    civilStatus:res.profile.civil_status,
    courseYear: res.profile.course_year,
  };
  currentUser = updatedUser;
  sessionStorage.setItem('clinic_user', JSON.stringify(updatedUser));

  renderProfile(res.profile);
  closeModal('editProfileModal');
  showToast('Profile updated successfully!', 'success');
}

// -------------------------------------------------------
//  PRINT PHR
// -------------------------------------------------------
function printPHR() {
  const data = collectPHR();
  const user = currentUser;

  const nasRow = (label, fid) => {
    const val = data[fid] || 'N';
    return `<tr>
      <td style="text-align:left;padding:3px 6px;border:1px solid #ccc">${label}</td>
      <td style="border:1px solid #ccc;text-align:center">${val==='N'?'✓':''}</td>
      <td style="border:1px solid #ccc;text-align:center">${val==='A'?'✓':''}</td>
      <td style="border:1px solid #ccc;text-align:center">${val==='S'?'✓':''}</td>
    </tr>`;
  };

  const win = window.open('', '_blank', 'width=900,height=700');
  win.document.write(`<!DOCTYPE html><html><head><title>Personal Health Record — SUNN Clinic</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#111;padding:24px 40px;max-width:800px;margin:auto}
    h2{text-align:center;font-size:16px;margin-bottom:2px}
    h3{text-align:center;font-size:12px;font-style:italic;margin-bottom:12px}
    .section{border:1px solid #333;margin-bottom:8px;}
    .section-title{background:#f0f0f0;font-weight:bold;padding:4px 8px;font-size:12px;border-bottom:1px solid #333}
    .info-box{padding:6px 10px;}
    .row{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:4px}
    .field{display:flex;align-items:baseline;gap:4px;flex:1;min-width:150px}
    .field label{font-weight:bold;white-space:nowrap;font-size:11px}
    .field span{border-bottom:1px solid #555;flex:1;min-width:60px;min-height:14px;font-size:11px;display:inline-block}
    table.nas{border-collapse:collapse;font-size:11px;width:100%}
    table.nas th{background:#eee;border:1px solid #ccc;padding:3px 5px}
    table.nas td{border:1px solid #ccc;padding:2px 5px}
    .imm{display:flex;flex-wrap:wrap;gap:12px;padding:4px 10px;font-size:11px}
    .imm span{display:flex;align-items:center;gap:4px}
    .cb{width:12px;height:12px;border:1px solid #333;display:inline-block;text-align:center;font-size:10px;line-height:12px}
    .sig-area{border-top:1px solid #555;margin-top:30px;padding-top:4px;font-size:11px}
    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
    @page{size:letter;margin:0.6in}
    @media print{body{padding:0}}
    .note-box{border:1px solid #999;padding:4px 6px;min-height:28px;font-size:11px;margin-top:3px}
  </style>
  </head><body>
  <h2>PERSONAL HEALTH RECORD</h2>
  <h3>(For students) — All information treated as CONFIDENTIAL</h3>

  <div class="section">
    <div class="section-title">A. General Information &nbsp;&nbsp;&nbsp; Date: ${data.record_date || new Date().toLocaleDateString()}</div>
    <div class="info-box">
      <div class="field" style="margin-bottom:6px"><label>Course/Year:</label><span>${data.course_year||''}</span></div>
      <div class="grid2" style="margin-bottom:6px">
        <div><div style="font-weight:bold;font-size:11px">NAME (Last, First, Middle):</div>
          <div class="row" style="margin-top:3px">
            <div class="field"><label>Last:</label><span>${data.last_name||''}</span></div>
            <div class="field"><label>First:</label><span>${data.first_name||''}</span></div>
            <div class="field"><label>M.I.:</label><span>${data.middle_name||''}</span></div>
          </div>
          <div class="row" style="margin-top:6px">
            <div class="field"><label>Address:</label><span>${data.address||''}</span></div>
            <div class="field"><label>Contact #:</label><span>${data.contact||''}</span></div>
          </div>
        </div>
        <div>
          <div class="row"><div class="field"><label>Sex:</label><span>${gv('phr_sex')||''}</span></div>
            <div class="field"><label>Civil Status:</label><span>${gv('phr_civil_status')||''}</span></div></div>
          <div class="row" style="margin-top:4px">
            <div class="field"><label>Age:</label><span>${data.age||''}</span></div>
            <div class="field"><label>DOB:</label><span>${data.date_of_birth||''}</span></div>
          </div>
          <div class="row" style="margin-top:4px">
            <div class="field"><label>Blood Type:</label><span>${data.blood_type||''}</span></div>
            <div class="field"><label>Height:</label><span>${data.height_cm ? data.height_cm+'cm' : ''}</span></div>
            <div class="field"><label>Weight:</label><span>${data.weight_kg ? data.weight_kg+'kg' : ''}</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">B. Contact Person (In Case of Emergency)</div>
    <div class="info-box">
      <div class="row">
        <div class="field"><label>Name:</label><span>${data.ec_name||''}</span></div>
        <div class="field"><label>Relationship:</label><span>${data.ec_relationship||''}</span></div>
      </div>
      <div class="row" style="margin-top:4px">
        <div class="field"><label>Address:</label><span>${data.ec_address||''}</span></div>
        <div class="field"><label>Contact #:</label><span>${data.ec_contact||''}</span></div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">C. Health Record and Medical History</div>
    <div class="info-box">
      <p style="font-size:11px;margin-bottom:4px">Do you have allergy? If yes please list all medicines, foods and other causes.</p>
      <div class="note-box">${data.allergy_detail||''}</div>
      <p style="font-size:11px;margin:6px 0 4px">Do you had an accident(s)? Date/year, parts of body, treatment and operations.</p>
      <div class="note-box">${data.accident_detail||''}</div>
      <p style="font-size:11px;margin:6px 0 4px">Hospitalization history? Date/year, parts of body, treatment and operations.</p>
      <div class="note-box">${data.hospitalization_detail||''}</div>
      <p style="font-size:11px;margin:6px 0 4px">Have you undergone surgical operation(s)? Date, illness, type of surgery and complications.</p>
      <div class="note-box">${data.surgical_detail||''}</div>
      <p style="font-size:11px;margin:6px 0 4px"><strong>Family Medical History:</strong></p>
      <div class="note-box">${data.family_history||''}</div>

      <p style="font-size:11px;margin:10px 0 4px"><strong>Please check the appropriate box: N = Never &nbsp; A = Always &nbsp; S = Seldom</strong> — Do you experience any of the following?</p>
      <table class="nas">
        <thead><tr>
          <th style="text-align:left">Symptom</th><th>N</th><th>A</th><th>S</th>
          <th style="text-align:left">Symptom</th><th>N</th><th>A</th><th>S</th>
          <th style="text-align:left">Symptom</th><th>N</th><th>A</th><th>S</th>
          <th style="text-align:left">Symptom</th><th>N</th><th>A</th><th>S</th>
        </tr></thead>
        <tbody>`);

  const col1 = SYMPTOMS.slice(0,5), col2 = SYMPTOMS.slice(5,10), col3 = SYMPTOMS.slice(10,15), col4 = SYMPTOMS.slice(15,20);
  for (let r=0;r<5;r++) {
    win.document.write('<tr>');
    [col1,col2,col3,col4].forEach(col=>{
      const [fid,label]=col[r]||['',''];
      if(fid){
        const v=data[fid]||'N';
        win.document.write(`<td style="text-align:left;border:1px solid #ccc;padding:2px 5px">${label}</td>`);
        ['N','A','S'].forEach(x=>win.document.write(`<td style="border:1px solid #ccc;text-align:center">${v===x?'✓':''}</td>`));
      } else { win.document.write('<td></td><td></td><td></td><td></td>'); }
    });
    win.document.write('</tr>');
  }

  const chk = id => data[id] ? '✓' : '☐';
  win.document.write(`</tbody></table>
    <p style="font-size:11px;margin:10px 0 4px"><strong>IMMUNIZATION/VACCINATION RECORD:</strong> Please Check If You Have Received Vaccination For:</p>
    <div class="imm">
      <span><span class="cb">${chk('imm_chicken_pox')}</span> Chicken Pox/Varicella</span>
      <span><span class="cb">${chk('imm_typhoid')}</span> Typhoid</span>
      <span><span class="cb">${chk('imm_hepatitis_a')}</span> Hepatitis A</span>
      <span><span class="cb">${chk('imm_hepatitis_b')}</span> Hepatitis B</span>
      <span><span class="cb">${chk('imm_tetanus')}</span> Tetanus</span>
      <span><span class="cb">${chk('imm_covid')}</span> Covid</span>
    </div>
    <div style="border:1px solid #ccc;padding:6px 10px;margin-top:8px;">
      <strong style="font-size:11px">FOR FEMALES</strong>
      <div class="row" style="margin-top:6px;align-items:center;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:8px;font-size:11px">
          Menstrual Period:
          <span class="cb">${data.menstrual_regular?'✓':''}</span> Regular
          <span class="cb">${data.menstrual_irregular?'✓':''}</span> Irregular
          &nbsp;&nbsp; Dysmenorrhea:
          <span class="cb">${data.dysmenorrhea==='Yes'?'✓':''}</span> Yes
          <span class="cb">${data.dysmenorrhea==='No'?'✓':''}</span> No
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:11px">
          Excessive Blood Flow:
          <span class="cb">${data.excessive_blood_flow==='Yes'?'✓':''}</span> Yes
          <span class="cb">${data.excessive_blood_flow==='No'?'✓':''}</span> No
          &nbsp; Avg No. of days: <span style="border-bottom:1px solid #555;min-width:30px;display:inline-block">${data.avg_days||''}</span>
        </div>
      </div>
    </div>
    <div class="grid3" style="margin-top:10px">
      <div class="field"><label>BP:</label><span>${data.bp||''}</span></div>
      <div class="field"><label>PR:</label><span>${data.pr||''}</span></div>
      <div class="field"><label>Temp:</label><span>${data.temp||''}</span></div>
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:24px;gap:40px">
      <div style="flex:1"><em style="font-size:11px">I hereby declare that all information are true and correct.</em></div>
      <div style="flex:1"><div class="sig-area">${data.signature_name||''}</div><div style="font-size:10px">Signature over Printed Name</div></div>
    </div>
    <div style="margin-top:18px;padding-top:10px;border-top:2px solid #333">
      <p style="font-size:11px">✓ PHYSICAL EXAM (to be filled out by school nurse and physician)</p>
      <p style="font-size:11px;margin-top:8px"><strong>FINDINGS AND RECOMMENDATION:</strong></p>
      <div style="border-bottom:1px solid #555;min-height:36px;margin-top:8px"></div>
      <p style="font-size:10px;margin-top:12px;font-style:italic">We certify that the information given are true to the best of our knowledge and ability.</p>
      <div style="display:flex;justify-content:space-between;margin-top:24px">
        <div style="text-align:center"><div style="border-top:1px solid #333;padding-top:4px;font-size:11px">School Nurse</div></div>
        <div style="text-align:center"><div style="border-top:1px solid #333;padding-top:4px;font-size:11px"><strong>CYRILLE S. ADRANEDA, M.D</strong><br/>School Physician</div></div>
      </div>
    </div>
    </div></div>
  </body></html>`);
  win.document.close();
  win.focus();
  setTimeout(() => win.print(), 600);
}
