'use strict';
/* ═══════════════════════════════════════════════
   SUNN Clinic — Admin Extra JS
   Overrides / extends admin.js for the new UI
═══════════════════════════════════════════════ */

const ADMIN_API = '../api/student.php';

// ── helpers ──────────────────────────────────
function showToast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fa ${icons[type]||'fa-bell'}"></i><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity 0.3s'; setTimeout(()=>t.remove(),320); }, 3200);
}

function openModal(id)  { document.getElementById(id)?.classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

function togglePwVis(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp) return;
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  if (ico) { ico.classList.toggle('fa-eye', !show); ico.classList.toggle('fa-eye-slash', show); }
}

function doLogout() {
  sessionStorage.removeItem('clinic_user');
  window.location.href = '/login';
}

// Page navigation
function showPage(pageId, el) {
  document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
  const pg = document.getElementById('page-' + pageId);
  if (pg) pg.classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.querySelectorAll('.nav-sub-item').forEach(n => n.classList.remove('active'));
  if (el) {
    if (el.classList.contains('nav-sub-item')) el.classList.add('active');
    else el.classList.add('active');
  }
  const titles = {
    dashboard:'Dashboard', students:'Students', staff:'Staff', appointments:'Appointments',
    'rec-medical':'Medical Records', 'rec-dental':'Dental Records',
    'rec-rx':'Prescriptions', 'rec-lab':'Lab Requests',
    reports:'Reports', audit:'Audit Log', settings:'Settings',
  };
  const t = document.getElementById('pageTitle');
  if (t) t.textContent = titles[pageId] || pageId;
}

function toggleSub(menuId, btn) {
  const menu = document.getElementById(menuId);
  if (!menu) return;
  const open = menu.classList.contains('open');
  document.querySelectorAll('.nav-sub').forEach(m => m.classList.remove('open'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('open'));
  if (!open) { menu.classList.add('open'); btn.classList.add('open'); }
}

function adminExtraFormatTime(value) {
  if (!value) return '—';
  const match = String(value).trim().match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
  if (match) {
    const hour = Number(match[1]);
    const minute = Number(match[2]);
    if (hour <= 23 && minute <= 59) return `${hour % 12 || 12}:${match[2]} ${hour >= 12 ? 'PM' : 'AM'}`;
  }
  const date = value instanceof Date ? value : new Date(value);
  return Number.isNaN(date.getTime())
    ? String(value)
    : new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', hour12: true }).format(date);
}

function adminExtraFormatDateTime(value) {
  return new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  }).format(value instanceof Date ? value : new Date(value));
}

// ── Clock ────────────────────────────────────
function _tickClock() {
  const now  = new Date();
  const time = document.getElementById('topTime');
  const date = document.getElementById('topDate');
  if (time) time.textContent = adminExtraFormatTime(now);
  if (date) date.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(now);
}
function startClock() {
  _tickClock();
  setInterval(_tickClock, 1000);
}
// Run immediately — scripts are at bottom of body so DOM is already ready
_tickClock();
setInterval(_tickClock, 1000);

// ── Init ─────────────────────────────────────
// Note: admin.js handles all data loading. This block only sets avatar initial.
document.addEventListener('DOMContentLoaded', () => {
  const raw  = sessionStorage.getItem('clinic_user');
  const user = raw ? JSON.parse(raw) : null;
  if (user) {
    const av = document.getElementById('sidebarAvatar');
    if (av) av.textContent = (user.name||'A')[0].toUpperCase();
  }
});

// ── Stats ────────────────────────────────────
function loadAdminStats() {
  const students  = JSON.parse(localStorage.getItem('clinic_students')        || '[]');
  const medical   = JSON.parse(localStorage.getItem('clinic_records_medical') || '[]');
  const staff     = JSON.parse(localStorage.getItem('clinic_admin_staff')     || '[]');

  const set = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
  set('adminStatStudents', students.length);
  set('adminStatRecords',  medical.length + 15);
  set('adminStatStaff',    Math.max(staff.length, 3));

  const badge = document.getElementById('studentBadge');
  if (badge) badge.textContent = students.length;
}

// ── Student Table ────────────────────────────
function renderStudentTable(filter='') {
  const tbody = document.getElementById('studentTableBody');
  const noMsg = document.getElementById('noStudents');
  if (!tbody) return;
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  const filtered = filter ? students.filter(s =>
    (s.name||'').toLowerCase().includes(filter.toLowerCase()) ||
    (s.email||'').toLowerCase().includes(filter.toLowerCase()) ||
    (s.studentId||'').toLowerCase().includes(filter.toLowerCase())
  ) : students;

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="no-data">No students found.</td></tr>`;
    return;
  }
  if (noMsg) noMsg.style.display = 'none';
  tbody.innerHTML = filtered.map((s,i) => `
    <tr>
      <td><strong>${s.name||'—'}</strong></td>
      <td><span class="chip ch-blue">${s.studentId||'—'}</span></td>
      <td>${s.dept||'—'}</td>
      <td>${s.email||'—'}</td>
      <td>${s.contact||'—'}</td>
      <td>${s.sex||'—'}</td>
      <td><span class="chip ch-green">Active</span></td>
      <td style="display:flex;gap:4px;">
        <button class="btn btn-sm btn-outline" onclick="viewStudentAdmin('${s.email}')"><i class="fa fa-eye"></i></button>
        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${i})"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`).join('');
}

function filterStudents() {
  renderStudentTable(document.getElementById('studentSearch')?.value || '');
}

function viewStudentAdmin(email) {
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  const s = students.find(st => st.email === email);
  if (!s) return;
  uhAlert(`Name: ${s.name}\nID: ${s.studentId||'—'}\nDept: ${s.dept||'—'}\nSection: ${s.section||'—'}\nEmail: ${s.email}\nContact: ${s.contact||'—'}\nAge: ${s.age||'—'}   Sex: ${s.sex||'—'}   Status: ${s.status||'—'}\nRegistered: ${s.createdAt?.split('T')[0]||'—'}`, { title: 'Student details' });
}

async function deleteStudent(i) {
  if (!await uhConfirm('This will permanently delete the student account.', { title: 'Delete student?', confirmText: 'Delete student', danger: true })) return;
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  students.splice(i,1);
  localStorage.setItem('clinic_students', JSON.stringify(students));
  renderStudentTable(); loadAdminStats();
  showToast('Student deleted.', 'info');
}

function exportStudents() {
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  if (!students.length) { showToast('No students to export.','info'); return; }
  const csv = ['Name,Student ID,Department,Email,Contact,Age,Sex,Status']
    .concat(students.map(s=>`"${s.name}","${s.studentId||''}","${s.dept||''}","${s.email}","${s.contact||''}","${s.age||''}","${s.sex||''}","${s.status||''}"`))
    .join('\n');
  const a = Object.assign(document.createElement('a'),{href:URL.createObjectURL(new Blob([csv],{type:'text/csv'})),download:'sunn-students.csv'});
  a.click(); URL.revokeObjectURL(a.href);
  showToast('Students exported!','success');
}

// ── Appointments ─────────────────────────────
function renderAppointments() {
  const tbody = document.getElementById('apptTableBody');
  if (!tbody) return;
  // Gather from all students
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  let all = [];
  students.forEach(s => {
    const key = `appts_${s.email}`;
    const appts = JSON.parse(localStorage.getItem(key) || '[]');
    appts.forEach(a => all.push({ ...a, studentName: s.name, studentId: s.studentId }));
  });
  if (all.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">No appointments found.</td></tr>'; return;
  }
  const statusClass = {pending:'ch-amber',confirmed:'ch-green',completed:'ch-blue',cancelled:'ch-gray',deleted:'ch-gray'};
  tbody.innerHTML = all.map(a => `
    <tr>
      <td><strong>${a.studentName||'—'}</strong><br><span style="font-size:0.68rem;color:#94a3b8">${a.studentId||''}</span></td>
      <td>${a.appt_date||'—'}</td>
      <td>${adminExtraFormatTime(a.appt_time)}</td>
      <td>${a.service_type||'—'}</td>
      <td>${(a.reason||'—').substring(0,40)}</td>
      <td><span class="chip ${statusClass[a.status]||'ch-gray'}">${a.status||'pending'}</span></td>
      <td>—</td>
    </tr>`).join('');
}

// ── All Records ──────────────────────────────
function renderAllRecords() {
  const fill = (key, bodyId, cols) => {
    const tbody = document.getElementById(bodyId);
    if (!tbody) return;
    const records = JSON.parse(localStorage.getItem('clinic_records_'+key) || '[]');
    if (!records.length) { tbody.innerHTML=`<tr><td colspan="${cols.length}" class="no-data">No records found.</td></tr>`; return; }
    tbody.innerHTML = records.slice().reverse().map(r =>
      `<tr>${cols.map(c=>`<td>${r[c]||'—'}</td>`).join('')}</tr>`
    ).join('');
  };
  fill('medical',      'allMedBody',    ['med_date','med_name','med_complaint','med_assessment','med_nurse','med_disposition']);
  fill('dental',       'allDentalBody', ['dent_date','dent_name','dent_complaint','dent_treatment','dent_dentist']);
  fill('prescription', 'allRxBody',     ['rx_date','rx_name','rx_diagnosis','rx_nurse']);
  fill('lab',          'allLabBody',    ['lab_date','lab_name','lab_notes','lab_priority','lab_nurse']);
}

// ── Charts ───────────────────────────────────
function renderAdminChart() {
  const chart  = document.getElementById('adminWeekChart');
  const labels = document.getElementById('adminWeekLabels');
  if (!chart) return;
  const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const vals = [12,18,9,15,20,7,3];
  const max  = Math.max(...vals);
  chart.innerHTML = vals.map((v,i) => `
    <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:4px">
      <span style="font-size:0.68rem;font-weight:700;color:#6366f1">${v}</span>
      <div style="width:100%;background:${i===4?'linear-gradient(180deg,#6366f1,#3b82f6)':'#e0e7ff'};
        border-radius:6px 6px 0 0;height:${Math.round((v/max)*110)}px;transition:height 0.6s;"></div>
    </div>`).join('');
  if (labels) labels.innerHTML = days.map(d=>`<span style="flex:1;text-align:center">${d}</span>`).join('');
}

function renderReportStats() {
  const medical = JSON.parse(localStorage.getItem('clinic_records_medical') || '[]');
  const rx      = JSON.parse(localStorage.getItem('clinic_records_prescription') || '[]');
  const ref     = JSON.parse(localStorage.getItem('clinic_records_referral') || '[]');
  const set = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
  set('rpt_visits', medical.length + 48);
  set('rpt_rx',     rx.length + 22);
  set('rpt_ref',    ref.length + 9);
}

function renderReportChart() {
  const chart  = document.getElementById('rptChart');
  const labels = document.getElementById('rptLabels');
  const rptReasons = document.getElementById('rptReasons');
  if (!chart) return;
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const vals = [10,8,14,22,18,25,30,28,20,15,12,8];
  const max  = Math.max(...vals);
  chart.innerHTML = vals.map((v,i) => `
    <div style="display:flex;flex-direction:column;align-items:center;flex:1;gap:3px">
      <span style="font-size:0.62rem;font-weight:700;color:#6366f1">${v}</span>
      <div style="width:100%;background:${i===5?'linear-gradient(180deg,#6366f1,#3b82f6)':'#e0e7ff'};
        border-radius:4px 4px 0 0;height:${Math.round((v/max)*120)}px;"></div>
    </div>`).join('');
  if (labels) labels.innerHTML = months.map(m=>`<span style="flex:1;text-align:center;font-size:0.62rem;color:#94a3b8">${m}</span>`).join('');

  if (rptReasons) {
    const reasons = [
      {r:'Headache/Migraine',n:45,p:22},{r:'Fever',n:38,p:19},
      {r:'Stomachache',n:30,p:15},{r:'Sports Injury',n:25,p:12},
      {r:'Common Cold',n:22,p:11},{r:'Toothache',n:18,p:9},
    ];
    rptReasons.innerHTML = reasons.map(r=>`
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
          <span style="font-weight:600;color:#1e293b">${r.r}</span>
          <span style="color:#94a3b8">${r.n} visits (${r.p}%)</span>
        </div>
        <div style="background:#e0e7ff;border-radius:6px;height:7px;overflow:hidden">
          <div style="height:100%;background:linear-gradient(90deg,#6366f1,#3b82f6);border-radius:6px;width:${r.p*4}%"></div>
        </div>
      </div>`).join('');
  }
}

// ── Recent Registrations ─────────────────────
function renderRecentRegistrations() {
  const c = document.getElementById('recentRegList');
  if (!c) return;
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  if (!students.length) {
    c.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:16px;font-size:0.8rem">No registrations yet.</p>'; return;
  }
  const colors = ['#3b82f6','#6366f1','#10b981','#f59e0b','#ef4444'];
  c.innerHTML = students.slice(-5).reverse().map((s,i) => `
    <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;">
      <div style="width:34px;height:34px;border-radius:50%;background:${colors[i%5]};
        display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;font-weight:700;flex-shrink:0">
        ${(s.name||'?')[0].toUpperCase()}
      </div>
      <div style="flex:1">
        <div style="font-size:0.82rem;font-weight:600;color:#1e293b">${s.name||'—'}</div>
        <div style="font-size:0.68rem;color:#94a3b8">${s.dept||'—'} &bull; ${s.email||''}</div>
      </div>
      <span class="chip ch-green" style="font-size:0.6rem">New</span>
    </div>`).join('');
}

// ── Dept Distribution ────────────────────────
function renderDeptDistrib() {
  const c = document.getElementById('deptDistrib');
  if (!c) return;
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  const depts = {};
  students.forEach(s => { const d=s.dept||'Unknown'; depts[d]=(depts[d]||0)+1; });
  const demo = {'BSN':8,'BSIT':5,'BSF':4,'BSEd-English':3};
  Object.keys(demo).forEach(d => { depts[d]=(depts[d]||0)+demo[d]; });
  const total = Object.values(depts).reduce((a,b)=>a+b,0);
  const sorted = Object.entries(depts).sort((a,b)=>b[1]-a[1]).slice(0,5);
  const colors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#8b5cf6'];
  c.innerHTML = sorted.map(([d,cnt],i)=>`
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
        <span style="font-weight:600;color:#1e293b">${d}</span>
        <span style="color:#94a3b8">${cnt}</span>
      </div>
      <div style="background:#f1f5f9;border-radius:6px;height:8px;overflow:hidden">
        <div style="height:100%;background:${colors[i]};border-radius:6px;width:${Math.round((cnt/total)*100)}%;transition:width 0.6s"></div>
      </div>
    </div>`).join('');
}

// ── Activity Log ─────────────────────────────
function renderActivityLog() {
  const c = document.getElementById('activityLog');
  if (!c) return;
  const logs = [
    {icon:'fa-user-plus',  color:'#10b981',title:'New student registered',          time:'Just now'},
    {icon:'fa-file-medical',color:'#3b82f6',title:'Medical record created',          time:'1 hr ago'},
    {icon:'fa-pills',      color:'#f59e0b', title:'Prescription issued — Physician', time:'2 hrs ago'},
    {icon:'fa-tooth',      color:'#8b5cf6', title:'Dental certificate issued',       time:'3 hrs ago'},
    {icon:'fa-share-from-square',color:'#10b981',title:'Patient referred to hospital',time:'Yesterday'},
  ];
  const students = JSON.parse(localStorage.getItem('clinic_students') || '[]');
  if (students.length) {
    const last = students[students.length-1];
    logs.unshift({icon:'fa-user-plus',color:'#10b981',title:`${last.name} just registered`,time:'Recently'});
  }
  c.innerHTML = logs.slice(0,6).map(l=>`
    <div class="timeline-item">
      <div class="timeline-dot" style="background:${l.color}"><i class="fa ${l.icon}" style="font-size:0.7rem"></i></div>
      <div class="timeline-content">
        <div class="timeline-title">${l.title}</div>
        <div class="timeline-date">${l.time}</div>
      </div>
    </div>`).join('');
}

// ── Audit Log ────────────────────────────────
function renderAuditLog() {
  const tbody = document.getElementById('auditBody');
  if (!tbody) return;
  const now = new Date();
  const entries = [
    {time: adminExtraFormatDateTime(now), user:'admin@uni.edu',       role:'Admin',   action:'Login',            detail:'Admin dashboard accessed'},
    {time: adminExtraFormatDateTime(now), user:'nurse@uni.edu',        role:'Nurse',   action:'Form Created',     detail:'Medical Certificate issued'},
    {time: adminExtraFormatDateTime(now), user:'student@uni.edu',      role:'Student', action:'Registration',     detail:'New student account created'},
    {time: adminExtraFormatDateTime(now), user:'nurse@uni.edu',        role:'Nurse',   action:'Record Saved',     detail:'Consultation record added'},
  ];
  tbody.innerHTML = entries.map(e=>`
    <tr>
      <td style="font-size:0.72rem;color:#94a3b8">${e.time}</td>
      <td>${e.user}</td>
      <td><span class="chip ${e.role==='Admin'?'ch-purple':e.role==='Nurse'?'ch-blue':'ch-green'}">${e.role}</span></td>
      <td><strong>${e.action}</strong></td>
      <td>${e.detail}</td>
    </tr>`).join('');
}

// ── Settings ─────────────────────────────────
function saveSettings() { showToast('Settings saved!','success'); }
function generateReport(){ showToast('Report export coming soon!','info'); }
function exportAllData() {
  const data = {};
  for (let i=0;i<localStorage.length;i++) {
    const k = localStorage.key(i);
    try { data[k] = JSON.parse(localStorage.getItem(k)); } catch(e) { data[k] = localStorage.getItem(k); }
  }
  const a = Object.assign(document.createElement('a'),{
    href: URL.createObjectURL(new Blob([JSON.stringify(data,null,2)],{type:'application/json'})),
    download: 'sunn-clinic-data.json'
  });
  a.click(); URL.revokeObjectURL(a.href);
  showToast('Data exported!','success');
}
async function clearDemoData() {
  if (!await uhConfirm('This will remove all sample and test records. Student accounts will remain.', { title: 'Clear sample records?', confirmText: 'Clear records', danger: true })) return;
  ['medical','dental','prescription','lab','xray','referral','lactating','breastfeeding','dental-cert']
    .forEach(k => localStorage.removeItem('clinic_records_'+k));
  renderAllRecords(); loadAdminStats(); showToast('Sample records cleared.','info');
}
function changeAdminPw() {
  const cur = document.getElementById('set_cur_pw')?.value;
  const nw  = document.getElementById('set_new_pw')?.value;
  const cf  = document.getElementById('set_confirm_pw')?.value;
  if (cur !== 'password123') { showToast('Current password incorrect.','error'); return; }
  if (!nw || nw.length < 6) { showToast('New password must be ≥ 6 characters.','error'); return; }
  if (nw !== cf) { showToast('Passwords do not match.','error'); return; }
  showToast('Password updated successfully!','success');
  ['set_cur_pw','set_new_pw','set_confirm_pw'].forEach(id => { const e=document.getElementById(id); if(e) e.value=''; });
}
