// Shared Dashboard Utilities
'use strict';

function showPage(pageId, el) {
  document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
  const page = document.getElementById('page-' + pageId);
  if (page) page.classList.add('active');

  // Update topbar title
  const titleMap = {
    'dashboard':'Dashboard','form-xray':'X-Ray Request Form','form-referral':'Referral Form',
    'form-prescription-physician':'Prescription Form — Physician','form-prescription-dentist':'Prescription Form — Dentist',
    'form-lab':'Laboratory Request Form',
    'lactating-form':'Lactating Mother Accommodating Form','breastfeeding-form':'Breastfeeding Room Agreement',
    'medical-form':'Medical Certificate','medical-records':'Medical Records',
    'dental-record':'Dental Record','dental-cert':'Dental Certificate',
    'health-profile':'Health Profile','visit-log':'Visit Log',
    'medications':'Medication Tracking','incidents':'Incident Reports',
    'alerts':'Alerts & Flags','comm-log':'Communication Log',
    'documents':'Document Uploads','consent':'Consent Forms','analytics':'Analytics'
  };
  const titleEl = document.getElementById('pageTitle');
  if (titleEl) titleEl.textContent = titleMap[pageId] || pageId;

  // Highlight active nav
  if (el) {
    document.querySelectorAll('.nav-item').forEach(n => {
      if (!n.querySelector('.chevron')) n.classList.remove('active');
    });
    document.querySelectorAll('.nav-sub-item').forEach(n => n.classList.remove('active'));
    if (el.classList.contains('nav-sub-item')) el.classList.add('active');
    else { el.classList.add('active'); }
  }

  if (pageId === 'medical-records') renderMedRecords();

  // Auto-render preview for form pages
  const formPageMap = {
    'form-xray':                   'xray-form-body',
    'form-referral':               'referral-form-body',
    'form-lab':                    'lab-form-body',
    'form-prescription-physician': 'rx-physician-body',
    'form-prescription-dentist':   'rx-dentist-body',
    'lactating-form':              'lactating-form-body',
    'breastfeeding-form':          'bf-form-body',
    'medical-form':                'med-form-body',
    'dental-cert':                 'dental-cert-body',
  };
  const fId = formPageMap[pageId];
  if (fId && typeof updatePreview === 'function') {
    setTimeout(() => updatePreview(fId), 60);
  }
}

function toggleSub(menuId, btn) {
  const menu = document.getElementById(menuId);
  if (!menu) return;
  const isOpen = menu.classList.contains('open');
  // Close all
  document.querySelectorAll('.nav-sub').forEach(m => m.classList.remove('open'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('open'));
  if (!isOpen) { menu.classList.add('open'); btn.classList.add('open'); }
}

function togglePass(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
  } else {
    input.type = 'password';
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
  }
}

function showToast(msg, type = 'success', duration = 3000) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = `toast ${type}`;
  setTimeout(() => { t.className = 'toast hidden'; }, duration);
}

function doLogout() {
  sessionStorage.removeItem('clinic_user');
  window.location.href = '/login';
}

function clearForm(formId) {
  const f = document.getElementById(formId);
  if (f) f.reset();
}

function printForm(formId) {
  const content = document.getElementById(formId);
  if (!content) return;
  const win = window.open('','_blank','width=800,height=600');
  win.document.write(`<!DOCTYPE html><html><head>
    <title>Print</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
      body{font-family:Arial,sans-serif;padding:30px;color:#111;font-size:13px;}
      label{font-weight:bold;display:block;margin-bottom:3px;font-size:11px;text-transform:uppercase;color:#555;}
      input,select,textarea{width:100%;border:none;border-bottom:1px solid #ccc;padding:4px 0;margin-bottom:10px;font-size:13px;}
      .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
      .form-row.three{grid-template-columns:1fr 1fr 1fr;}
      .vitals-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
      .vital-box{border:1px solid #ccc;padding:10px;border-radius:6px;}
      button{display:none!important;}
      .modal-divider{border-top:1px solid #ccc;margin:12px 0;}
      @page{margin:20mm;}
    </style></head><body>`);
  win.document.write(content.innerHTML);
  win.document.write('</body></html>');
  win.document.close();
  win.focus();
  win.print();
}

function saveForm(e, type) {
  e.preventDefault();
  const records = JSON.parse(localStorage.getItem('clinic_records_' + type) || '[]');
  const formData = { id: Date.now(), savedAt: new Date().toISOString(), type };

  // Generic: grab all inputs in the form
  const form = e.target;
  form.querySelectorAll('input,select,textarea').forEach(el => {
    if (el.name || el.id) {
      if (el.type === 'checkbox') formData[el.id || el.name] = el.checked;
      else formData[el.id || el.name] = el.value;
    }
  });

  records.push(formData);
  localStorage.setItem('clinic_records_' + type, JSON.stringify(records));
  showToast('Record saved successfully!', 'success');
  form.reset();
  if (type === 'medical') renderMedRecords();
}

// Date/time clock
function updateClock() {
  const now = new Date();
  const dateEl = document.getElementById('todayDate');
  const timeEl = document.getElementById('todayTime');
  if (dateEl) dateEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }).format(now);
  if (timeEl) timeEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', hour12: true }).format(now);
}

setInterval(updateClock, 1000);
updateClock();

// Greeting
function setGreeting(name) {
  const h = new Date().getHours();
  let gr = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  const el = document.getElementById('greetMsg');
  if (el) el.textContent = `${gr}, ${name}!`;
}

// Med rows (prescription)
function addMedRow() {
  const container = document.getElementById('medRows');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'med-row form-row';
  div.style.cssText = 'align-items:flex-end;gap:10px;margin-bottom:10px;';
  div.innerHTML = `
    <div class="form-group" style="flex:2;margin:0"><label>Medication Name</label><input type="text" placeholder="e.g. Ibuprofen 400mg"/></div>
    <div class="form-group" style="flex:1;margin:0"><label>Dosage</label><input type="text" placeholder="e.g. 1 tab"/></div>
    <div class="form-group" style="flex:1;margin:0"><label>Frequency</label><input type="text" placeholder="e.g. 2x daily"/></div>
    <div class="form-group" style="flex:1;margin:0"><label>Duration</label><input type="text" placeholder="e.g. 3 days"/></div>
    <button type="button" class="btn btn-danger btn-sm" style="margin-bottom:1px" onclick="removeMedRow(this)"><i class="fa fa-trash"></i></button>`;
  container.appendChild(div);
}

function removeMedRow(btn) {
  const row = btn.closest('.med-row');
  if (row) row.remove();
}

// Render medical records table
function renderMedRecords(filter = '') {
  const tbody = document.getElementById('medRecordsTableBody');
  const noMsg = document.getElementById('noMedRecords');
  if (!tbody) return;
  const records = JSON.parse(localStorage.getItem('clinic_records_medical') || '[]');
  const filtered = filter ? records.filter(r =>
    (r.med_name||'').toLowerCase().includes(filter.toLowerCase()) ||
    (r.med_id||'').toLowerCase().includes(filter.toLowerCase())
  ) : records;

  if (filtered.length === 0) {
    tbody.innerHTML = '';
    if (noMsg) noMsg.style.display = 'block';
    return;
  }
  if (noMsg) noMsg.style.display = 'none';
  tbody.innerHTML = filtered.slice().reverse().map(r => `
    <tr>
      <td>${r.med_date || r.savedAt?.split('T')[0] || '—'}</td>
      <td><strong>${r.med_name || '—'}</strong></td>
      <td><span class="chip chip-blue">${r.med_id || '—'}</span></td>
      <td>${r.med_complaint || '—'}</td>
      <td><span class="chip chip-green">${r.med_disposition || '—'}</span></td>
      <td><button class="btn btn-secondary btn-sm" onclick="viewMedRecord('${r.id}')"><i class="fa fa-eye"></i></button>
          <button class="btn btn-danger btn-sm" onclick="deleteMedRecord('${r.id}')"><i class="fa fa-trash"></i></button></td>
    </tr>`).join('');
}

function filterMedRecords() {
  const q = document.getElementById('medRecordSearch')?.value || '';
  renderMedRecords(q);
}

async function deleteMedRecord(id) {
  if (!await uhConfirm('This will permanently delete this medical record.', { title: 'Delete medical record?', confirmText: 'Delete record', danger: true })) return;
  let records = JSON.parse(localStorage.getItem('clinic_records_medical') || '[]');
  records = records.filter(r => String(r.id) !== String(id));
  localStorage.setItem('clinic_records_medical', JSON.stringify(records));
  renderMedRecords();
  showToast('Record deleted.', 'info');
}

function viewMedRecord(id) {
  const records = JSON.parse(localStorage.getItem('clinic_records_medical') || '[]');
  const r = records.find(rec => String(rec.id) === String(id));
  if (!r) return;
  uhAlert(`Patient: ${r.med_name}\nID: ${r.med_id}\nDate: ${r.med_date}\nComplaint: ${r.med_complaint}\nAssessment: ${r.med_assessment}\nTreatment: ${r.med_treatment}\nDisposition: ${r.med_disposition}`, { title: 'Medical record details' });
}

// Section tabs
function switchTab(tabGroupId, tabId) {
  const group = document.getElementById(tabGroupId);
  if (!group) return;
  group.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
  group.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  const tab = group.querySelector(`[data-tab="${tabId}"]`);
  const panel = document.getElementById(tabId);
  if (tab) tab.classList.add('active');
  if (panel) panel.classList.add('active');
}

// Print the official SUNN form — triggers browser print on just that form area
function printOfficialForm(formId) {
  window.print();
}

// ---- PHYSICIAN PRESCRIPTION MED ROWS ----
function addPhysicianMedRow() {
  const container = document.getElementById('physicianMedRows');
  if (!container) return;
  const num = container.querySelectorAll('.rx-med-row').length + 1;
  const div = document.createElement('div');
  div.className = 'rx-med-row';
  div.innerHTML = `
    <div class="rx-med-num">${num}.</div>
    <div class="rx-med-fields">
      <div class="form-row" style="margin-bottom:6px;">
        <div class="form-group" style="flex:3;margin:0"><label>Medication / Generic Name &amp; Strength</label><input type="text" placeholder="e.g. Paracetamol 500mg tablet"/></div>
        <div class="form-group" style="flex:1;margin:0"><label>Quantity</label><input type="text" placeholder="e.g. #10"/></div>
      </div>
      <div class="form-row three" style="margin-bottom:0;">
        <div class="form-group" style="margin:0"><label>Sig. (Instructions)</label><input type="text" placeholder="e.g. 1 tab q8h"/></div>
        <div class="form-group" style="margin:0"><label>Route</label>
          <select><option>Oral</option><option>Topical</option><option>IV</option><option>IM</option><option>Sublingual</option><option>Inhalation</option></select>
        </div>
        <div class="form-group" style="margin:0"><label>Duration</label><input type="text" placeholder="e.g. 5 days"/></div>
      </div>
    </div>
    <button type="button" class="btn btn-danger btn-sm" style="align-self:center;margin-top:16px;" onclick="removeRxRow(this)"><i class="fa fa-trash"></i></button>`;
  container.appendChild(div);
}

// ---- DENTIST PRESCRIPTION MED ROWS ----
function addDentistMedRow() {
  const container = document.getElementById('dentistMedRows');
  if (!container) return;
  const num = container.querySelectorAll('.rx-med-row').length + 1;
  const div = document.createElement('div');
  div.className = 'rx-med-row';
  div.innerHTML = `
    <div class="rx-med-num">${num}.</div>
    <div class="rx-med-fields">
      <div class="form-row" style="margin-bottom:6px;">
        <div class="form-group" style="flex:3;margin:0"><label>Medication / Generic Name &amp; Strength</label><input type="text" placeholder="e.g. Amoxicillin 500mg capsule"/></div>
        <div class="form-group" style="flex:1;margin:0"><label>Quantity</label><input type="text" placeholder="e.g. #21"/></div>
      </div>
      <div class="form-row three" style="margin-bottom:0;">
        <div class="form-group" style="margin:0"><label>Sig. (Instructions)</label><input type="text" placeholder="e.g. 1 cap 3x daily pc"/></div>
        <div class="form-group" style="margin:0"><label>Route</label>
          <select><option>Oral</option><option>Topical</option><option>Rinse/Gargle</option><option>Sublingual</option></select>
        </div>
        <div class="form-group" style="margin:0"><label>Duration</label><input type="text" placeholder="e.g. 7 days"/></div>
      </div>
    </div>
    <button type="button" class="btn btn-danger btn-sm" style="align-self:center;margin-top:16px;" onclick="removeRxRow(this)"><i class="fa fa-trash"></i></button>`;
  container.appendChild(div);
}

function removeRxRow(btn) {
  const row = btn.closest('.rx-med-row');
  if (row) row.remove();
  // Renumber
  const container = row?.parentElement;
  if (container) {
    container.querySelectorAll('.rx-med-num').forEach((n, i) => { n.textContent = (i+1) + '.'; });
  }
}

// ---- QUICK-ADD DENTAL SHORTCUTS ----
function quickAddDental(name, sig, route, duration, qty) {
  const container = document.getElementById('dentistMedRows');
  if (!container) return;
  // Fill last empty row first, otherwise add new
  const rows = container.querySelectorAll('.rx-med-row');
  let targetInputs = null;
  for (const row of rows) {
    const nameInput = row.querySelector('input[type="text"]');
    if (nameInput && !nameInput.value.trim()) { targetInputs = row; break; }
  }
  if (!targetInputs) {
    addDentistMedRow();
    targetInputs = container.querySelectorAll('.rx-med-row')[container.querySelectorAll('.rx-med-row').length - 1];
  }
  const inputs  = targetInputs.querySelectorAll('input[type="text"]');
  const selects = targetInputs.querySelectorAll('select');
  if (inputs[0]) inputs[0].value = name;
  if (inputs[1]) inputs[1].value = qty;
  if (inputs[2]) inputs[2].value = sig;
  if (selects[0]) selects[0].value = route;
  if (inputs[3]) inputs[3].value = duration;
}


// ── Campus Badge ─────────────────────────────
/**
 * Renders a campus badge in the dashboard header.
 * Call after session is loaded on any staff/nurse dashboard.
 * @param {string|null} campusName - The campus name from session. Null shows "All Campuses".
 */
function renderCampusBadge(campusName) {
  const name = campusName && campusName.trim() ? campusName.trim() : 'All Campuses';
  const isAll = name === 'All Campuses';

  // Remove existing badge if any
  const existing = document.getElementById('campusBadge');
  if (existing) existing.remove();

  const badge = document.createElement('div');
  badge.id = 'campusBadge';
  badge.style.cssText = `
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    background: ${isAll ? 'rgba(99,102,241,0.12)' : 'rgba(42,122,111,0.12)'};
    color: ${isAll ? '#6366f1' : 'var(--primary, #2a7a6f)'};
    border: 1px solid ${isAll ? 'rgba(99,102,241,0.25)' : 'rgba(42,122,111,0.25)'};
    white-space: nowrap;
    cursor: default;
    user-select: none;
  `;
  badge.innerHTML = `
    <i class="fa ${isAll ? 'fa-globe' : 'fa-building'}" style="font-size:0.7rem"></i>
    <span>${name}</span>
  `;
  badge.title = isAll ? 'You have access to all campuses' : `You are assigned to ${name}`;

  // Try common header locations: topbar actions, header right, or before page title
  const targets = [
    document.getElementById('topbarActions'),
    document.getElementById('headerRight'),
    document.querySelector('.topbar-right'),
    document.querySelector('.topbar .right'),
    document.querySelector('.dashboard-header'),
    document.querySelector('.topbar'),
    document.querySelector('header'),
  ];

  const container = targets.find(t => t !== null);
  if (container) {
    // Prepend so it appears first in the actions area
    container.insertBefore(badge, container.firstChild);
  } else {
    // Last resort: append to body as a fixed pill
    badge.style.cssText += 'position:fixed;top:14px;right:80px;z-index:9999;';
    document.body.appendChild(badge);
  }
}
