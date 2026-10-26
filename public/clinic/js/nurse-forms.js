'use strict';
/* ════════════════════════════════════════════════════════
   SUNN Clinic — Nurse Forms JS
   Live-preview renders + print for all document templates
════════════════════════════════════════════════════════ */

// ── helpers ──────────────────────────────────────────────
const gv  = id => (document.getElementById(id)?.value  || '');
const gc  = id => (document.getElementById(id)?.checked || false);
const esc = s  => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

// Shortcut: field row  "Label: ___________"
function fl(label, value) {
  return `<div class="pf-row"><label>${label}</label>
    <span class="pf-line">${esc(value)}</span></div>`;
}
// Signature block
function sig(label, value='') {
  return `<div style="margin-bottom:50px">
    <div class="pf-sig">${esc(value)}</div>
    <div style="font-size:12px;margin-top:4px">${label}</div>
  </div>`;
}
// Spacer flex
const spacer = `<div style="flex:1"></div>`;

// ── SUNN header (uses sunn-logo.png relative to pages/) ──
function sunnHeader() {
  const logoSrc = typeof SUNN_LOGO !== 'undefined' && SUNN_LOGO
    ? SUNN_LOGO
    : '/clinic/images/sunn-logo.png';

  return `
  <div class="sunn-header">
    <img src="${logoSrc}" alt="SUNN Logo" style="width:260px;height:260px;object-fit:contain;flex-shrink:0;"/>
    <div class="sunn-header-text">
      <div class="republic">Republic of the Philippines</div>
      <div class="university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
      <div class="office">MEDICAL AND DENTAL SERVICES OFFICE</div>
      <div class="address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>
    </div>
  </div>`;
}

function sunnFooter() {
  const footerSrc = '/clinic/images/footer.png';
  return `
  <div class="sunn-footer">
    <div class="tagline">The Future Shines Brightest</div>
    <div class="footer-row">
      <div class="contact-col">
        <span><i class="fa fa-phone"></i> (034) 703 8802</span><br>
        <span><i class="fa fa-envelope"></i> info@sunn.edu.ph</span><br>
        <span><i class="fa fa-globe"></i> www.sunn.edu.ph</span>
      </div>
      <img class="footer-img" src="${footerSrc}" alt="accreditation badges"/>
    </div>
  </div>`;
}

// Wraps body in full A4 doc structure
// A4 bond paper: 210mm × 297mm = 794px × 1123px @ 96 DPI
// Normal margins: left/right/bottom = 96px (2.54cm), top = 48px (~0.5in) so header sits near top
function doc(body) {
  return `<div style="display:flex;flex-direction:column;width:210mm;min-height:297mm;padding:48px 96px 96px 96px;box-sizing:border-box;background:#fff;">${sunnHeader()}<div style="flex:1">${body}</div>${sunnFooter()}</div>`;
}

// Render to target element
function render(elId, html) {
  const el = document.getElementById(elId);
  if (el) el.innerHTML = html;
}

// Print the contents of the given preview element
function printDoc(elId) {
  const el = document.getElementById(elId);
  if (!el) { console.warn('[printDoc] element not found:', elId); return; }
  const win = window.open('', '_blank', 'width=900,height=1200');
  if (!win) {
    uhAlert('Please allow pop-ups for this site to print the form.', { title: 'Printing unavailable' });
    return;
  }
  win.document.write(`
    <!doctype html>
    <html>
    <head>
      <title>Print</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
      <style>
        @page { size: A4; margin: 0; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #fff; }
        /* A4 bond paper: 210mm × 297mm = 794px × 1123px @ 96 DPI */
        .print-root { width: 210mm; min-height: 297mm; padding: 0; margin: 0 auto; box-sizing: border-box; }
        .print-root > div { width: 210mm; min-height: 297mm; box-sizing: border-box; }
        .sunn-header { display:flex; flex-direction:row; align-items:center; gap:2px; border-bottom:2px solid #111; padding-bottom:14px; margin-bottom:20px; margin-left:-96px; margin-right:-96px; padding-left:0; }
        .sunn-header > img { width:260px; height:260px; object-fit:contain; flex-shrink:0; }
        .sunn-header-text { text-align:left; line-height:1.3; flex:1; min-width:0; margin-left:-55px; }
        .sunn-header-text .republic { color:#003C8F; font-size:11px; font-weight:600; }
        .sunn-header-text .university { color:#F6BF00; font-weight:900; font-size:19px; letter-spacing:0.2px; text-transform:uppercase; white-space:nowrap; }
        .sunn-header-text .office { color:#003C8F; font-weight:800; font-size:14px; margin-top:2px; white-space:nowrap; }
        .sunn-header-text .address { color:#003C8F; font-size:11px; font-weight:500; margin-top:4px; line-height:1.5; }
        .sunn-footer { border-top:2px solid #111; padding-top:8px; margin-top:10px; }
        .sunn-footer .tagline { font-style:italic; font-weight:700; color:#003C8F; margin-bottom:6px; text-align:center; text-transform:uppercase; font-size:12px; letter-spacing:0.5px; }
        .sunn-footer .footer-row { display:flex; align-items:center; justify-content:space-between; width:100%; gap:0; }
        .sunn-footer .contact-col { text-align:left; line-height:1.8; font-size:10px; color:#003C8F; font-weight:600; flex-shrink:0; min-width:110px; }
        .sunn-footer .contact-col i { display:inline-block; width:14px; margin-right:4px; text-align:center; color:#003C8F; }
        .sunn-footer .footer-img { flex:1; height:52px; max-height:52px; width:100%; object-fit:contain; object-position:right center; }
        .pf-row { display:flex; gap:6px; margin-bottom:6px; align-items:baseline; }
        .pf-row label { font-weight:700; min-width:120px; font-size:12px; }
        .pf-line { flex:1; border-bottom:1px solid #444; min-height:16px; font-size:12px; padding:0 4px 1px; }
        .pf-sig { border-top:1.5px solid #111; padding-top:5px; min-width:200px; font-size:12px; font-weight:700; display:inline-block; }
      </style>
    </head>
    <body>
      <div class="print-root">${el.innerHTML}</div>
      <script>
        window.addEventListener('load', function() {
          setTimeout(function() {
            window.focus();
            window.print();
          }, 200);
        });
      <\/script>
    </body>
    </html>
  `);
  win.document.close();
}

// ── Medical Certificate ─────────────────────────────────
// Reads mc_* field IDs (populated by autoFillForm in nurse.js)
// and renders the certificate into #medcert-preview-content.
function renderMedCert() {
  const p = 'mc_';
  const surname  = gv(p + 'surname')    || '';
  const firstNm  = gv(p + 'firstname')  || '';
  const midNm    = gv(p + 'middlename') || '';
  const age      = gv(p + 'age')        || '';
  const sex      = gv(p + 'sex')        || '';
  const civil    = gv(p + 'civil')      || '';
  const address  = gv(p + 'address')    || '';
  const date     = gv(p + 'date')       || '';
  const bp       = gv(p + 'bp')         || '';
  const temp     = gv(p + 'temp')       || '';
  const assess   = gv(p + 'assessment') || '';
  const sig      = gv(p + 'sig_name')   || '';
  const lic      = gv(p + 'sig_license')|| '';
  const title    = gv(p + 'sig_title')  || '';

  // Underline cell: shows value or blank underline. Width in em so it scales with font.
  const U = (v, w) => `<span style="border-bottom:1px solid #111;display:inline-block;min-width:${w}em;padding:0 3px;font-size:inherit">${v ? esc(v) : '&nbsp;'}</span>`;
  // Full-width underline row (block-level, 100% wide)
  const URow = (v) => `<div style="border-bottom:1px solid #111;min-height:18px;padding:2px 4px;font-size:12px;color:#111">${v ? esc(v) : '&nbsp;'}</div>`;

  // Format date as "Month DD, YYYY" or blank underline
  let dateDisplay = '';
  if (date) {
    try {
      dateDisplay = new Date(date + 'T00:00').toLocaleDateString('en-PH', {year:'numeric', month:'long', day:'numeric'});
    } catch(e) {
      dateDisplay = date;
    }
  }

  const sigStr   = sig   ? esc(sig).toUpperCase() : 'DR. CYRILLE S. ADRANEDA';
  const licStr   = lic   ? esc(lic)               : '#85606';
  const titleStr = title ? esc(title)             : 'School Physician';

  const body = `
    <div style="text-align:center;margin:16px 0 20px">
      <h2 style="margin:0;font-size:15px;letter-spacing:2px;font-weight:900;text-decoration:underline;color:#111">MEDICAL CERTIFICATE</h2>
    </div>

    <div style="display:flex;justify-content:flex-end;font-size:12px;color:#111;margin-bottom:18px">
      <span>Date:&nbsp;</span>${U(dateDisplay, 14)}
    </div>

    <div style="font-size:12.5px;color:#111;font-weight:700;margin-bottom:4px">NAME OF PATIENT:</div>
    <div style="border-bottom:1px solid #111;margin-bottom:8px"></div>

    <div style="display:flex;gap:8px;font-size:12px;color:#111;margin-bottom:4px">
      <div style="flex:1.3">
        ${U(surname, 10)}
        <div style="font-size:11px;color:#555;margin-top:2px">Surname</div>
      </div>
      <div style="flex:1.2">
        ${U(firstNm, 10)}
        <div style="font-size:11px;color:#555;margin-top:2px">First Name</div>
      </div>
      <div style="flex:1">
        ${U(midNm, 8)}
        <div style="font-size:11px;color:#555;margin-top:2px">Middle Name</div>
      </div>
    </div>

    <div style="font-size:12px;color:#111;margin:8px 0 4px">
      Age:&nbsp;${U(age, 5)}&nbsp;&nbsp;
      Sex:&nbsp;${U(sex, 6)}&nbsp;&nbsp;
      Civil Status:&nbsp;${U(civil, 8)}
    </div>

    <div style="font-size:12px;color:#111;margin-bottom:18px;display:flex;align-items:baseline;gap:4px">
      <span style="white-space:nowrap">Address:</span>
      <span style="flex:1;border-bottom:1px solid #111;display:block;min-height:18px;padding:0 4px">${address ? esc(address) : '&nbsp;'}</span>
    </div>

    <div style="font-size:12px;color:#111;margin-bottom:6px">BP:&nbsp;${U(bp, 8)}</div>
    <div style="font-size:12px;color:#111;margin-bottom:20px">Temp:${U(temp, 7)}</div>

    <div style="font-size:12px;color:#111;margin-top:10px">
      <div style="display:flex;align-items:baseline;gap:4px;margin-bottom:8px">
        <span style="white-space:nowrap;font-weight:700">Recommendation/Diagnosis:</span>
        <span style="flex:1;border-bottom:1px solid #111;display:block;min-height:18px;padding:0 4px">${assess ? esc(assess) : '&nbsp;'}</span>
      </div>
      ${URow('')}
      <div style="margin-top:8px">${URow('')}</div>
      <div style="margin-top:8px;width:55%">${URow('')}</div>
    </div>

    <div style="margin-top:80px;display:flex;justify-content:flex-end">
      <div style="text-align:center;min-width:36%">
        <div style="border-bottom:1.5px solid #111;height:34px"></div>
        <div style="font-weight:700;font-size:12.5px;margin-top:4px">${sigStr}</div>
        <div style="font-size:12px;margin-top:2px">${licStr}</div>
        <div style="font-size:12px;margin-top:2px">${titleStr}</div>
      </div>
    </div>
  `;
  // Delegate to the inline renderer in nurse.blade.php (instant, no server round-trip)
  if (typeof window.renderMedCertPreview === 'function') {
    window.renderMedCertPreview();
  }
}
