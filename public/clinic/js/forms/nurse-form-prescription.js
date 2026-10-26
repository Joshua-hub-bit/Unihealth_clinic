/**
 * forms/nurse-form-prescription.js
 * Physician Prescription + Dentist Prescription — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var RX_STYLE = [
    '.rx-page{width:210mm;height:297mm;padding:12mm;background:#fff;font-family:"Times New Roman","Georgia",serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;box-sizing:border-box;}',
    '.rx-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;width:100%;height:100%;gap:8mm;}',
    '.rx-card{border:0.6pt solid #1a1a1a;padding:3.5mm 4.5mm 3mm;display:flex;flex-direction:column;position:relative;overflow:hidden;background:#fff;border-radius:2px;}',
    '.rx-hdr{display:flex;flex-direction:row;align-items:center;gap:2mm;border-bottom:0.75pt solid #111;padding-bottom:1.5mm;margin-bottom:2mm;}',
    '.rx-hdr img.logo{width:14mm;height:14mm;object-fit:contain;flex-shrink:0;transform:translateX(1.75mm) scale(1.5);transform-origin:center;}',
    '.rx-ht{line-height:1.15;flex:1;font-family:Arial,sans-serif;min-width:0;}',
    '.rx-republic{color:#003C8F;font-size:4.5pt;font-weight:600;}',
    '.rx-uni{color:#F6BF00;font-weight:900;font-size:6pt;letter-spacing:0.1px;text-transform:uppercase;white-space:nowrap;}',
    '.rx-office{color:#003C8F;font-weight:800;font-size:5pt;margin-top:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.rx-addr{color:#003C8F;font-size:4.5pt;font-weight:500;margin-top:0.5px;line-height:1.15;display:flex;flex-direction:column;gap:0;}',
    '.rx-addr .rx-addr-loc,.rx-addr .rx-addr-city,.rx-addr .rx-addr-zip{display:block;}',
    '.rx-divider{border:none;border-top:0.5pt solid #111;margin:1.8mm 0 2mm;}',
    '.rx-title{text-align:center;font-size:11pt;font-weight:900;text-decoration:underline;letter-spacing:1.5px;color:#000;margin-bottom:2.5mm;font-family:"Times New Roman",serif;}',
    '.rx-patient{display:flex;flex-direction:row;align-items:baseline;gap:2.5mm;font-size:7.5pt;margin-bottom:2mm;flex-wrap:wrap;}',
    '.rx-patient .field{display:inline-flex;align-items:baseline;white-space:nowrap;}',
    '.rx-patient .label{font-weight:700;}',
    '.rx-patient .ul{display:inline-block;border-bottom:0.5pt solid #000;min-width:22mm;height:3.5mm;margin-left:1.2px;padding:0 2px;vertical-align:bottom;}',
    '.rx-patient .ul-age{min-width:10mm;}',
    '.rx-patient .ul-date{min-width:18mm;}',
    '.rx-patient .ul-name{max-width:50mm;overflow:hidden;text-overflow:ellipsis;}',
    '.rx-body{display:flex;flex-direction:row;gap:3mm;margin-top:1mm;padding:1.5mm 2mm;flex:1;min-height:0;}',
    '.rx-sym{font-family:"Times New Roman",serif;font-size:26pt;font-weight:700;line-height:1;color:#000;flex-shrink:0;padding-right:2.5mm;}',
    '.rx-notes{flex:1;min-width:0;font-size:8pt;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere;color:#000;}',
    '.rx-lines{flex:1;display:flex;flex-direction:column;justify-content:space-between;padding:1.5mm 0 1mm;}',
    '.rx-line{border-bottom:0.4pt solid #555;height:0;visibility:hidden;}',
    '.rx-foot{margin-top:2mm;display:flex;flex-direction:row;justify-content:space-between;align-items:flex-end;gap:4mm;}',
    '.rx-phys{flex:1;font-size:7pt;line-height:1.35;}',
    '.rx-phys .sig-line{display:none;}',
    '.rx-phys .name{font-weight:700;font-size:7.5pt;}',
    '.rx-phys .role{font-style:italic;font-size:6.5pt;}',
    '.rx-phys .lic{font-size:6.5pt;}',
    '.rx-page-foot{margin-top:auto;padding-top:1mm;}',
    '.rx-page-foot .rx-foot-row{display:flex;align-items:center;justify-content:flex-end;width:100%;}',
    '.rx-page-foot img.rx-foot-img{width:100%;height:14mm;max-height:14mm;object-fit:fill;}',
].join('');

injectStyle('rx-styles',  RX_STYLE);
injectStyle('rxd-styles', RX_STYLE);

/* ── shared card builder ── */
function _buildRxCardHtml(prefix) {
    var name  = escHtml(fieldVal(prefix + '_full_name'));
    var age   = escHtml(fieldVal(prefix + '_age'));
    var date  = escHtml(fmtDate(fieldVal(prefix + '_date')));
    var notes = escHtml(fieldVal(prefix + '_notes'));
    var pName = escHtml(fieldVal(prefix + '_physician_name'));
    var pRole = escHtml(prefix === 'rxd' ? 'School Dentist' : 'School Physician');
    var pLic  = escHtml(fieldVal(prefix + '_physician_lic'));

    var body = notes
        ? '<div class="rx-notes">' + notes + '</div>'
        : '<div class="rx-lines">' + '<div class="rx-line"></div>'.repeat(8) + '</div>';

    return '<div class="rx-card">' +
        '<div class="rx-hdr">' +
            '<img class="logo" src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="rx-ht">' +
                '<div class="rx-republic">Republic of the Philippines</div>' +
                '<div class="rx-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="rx-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="rx-addr">' +
                    '<span class="rx-addr-loc">SUNN Main Campus</span>' +
                    '<span class="rx-addr-city">Sagay City, Negros Occidental</span>' +
                    '<span class="rx-addr-zip">Philippines 6122</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<hr class="rx-divider"/>' +
        '<div class="rx-title">PRESCRIPTION FORM</div>' +
        '<div class="rx-patient">' +
            '<span class="field"><span class="label">Name:</span><span class="ul ul-name">' + name + '</span></span>' +
            '<span class="field"><span class="label">Age:</span><span class="ul ul-age">'   + age  + '</span></span>' +
            '<span class="field"><span class="label">Date:</span><span class="ul ul-date">' + date + '</span></span>' +
        '</div>' +
        '<div class="rx-body"><div class="rx-sym">\u211E</div>' + body + '</div>' +
        '<div class="rx-foot">' +
            '<div class="rx-phys">' +
                '<div class="sig-line"></div>' +
                '<div class="name">' + pName + '</div>' +
                '<div class="role">' + pRole + '</div>' +
                '<div class="lic">Lic. No.: ' + pLic + '</div>' +
            '</div>' +
        '</div>' +
        '<div class="rx-page-foot">' +
            '<div class="rx-foot-row">' +
                '<img class="rx-foot-img" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
            '</div>' +
        '</div>' +
    '</div>';
}

/* ── physician ── */
function buildRxCard()              { return _buildRxCardHtml('rx'); }
function renderPrescriptionPreview() {
    var el = document.getElementById('prescription-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="rx-page"><div class="rx-grid">' +
        buildRxCard() + buildRxCard() + buildRxCard() + buildRxCard() +
    '</div></div>';
}
function clearPrescriptionForm() {
    var form = document.getElementById('prescription-form');
    if (form) { form.reset(); }
    var d = document.getElementById('rx_date');
    if (d) { d.value = new Date().toISOString().split('T')[0]; }
    var pName = document.getElementById('rx_physician_name');
    var pRole = document.getElementById('rx_physician_role');
    var pLic  = document.getElementById('rx_physician_lic');
    if (pName) { pName.value = ''; }
    if (pRole) { pRole.value = 'School Physician'; }
    if (pLic)  { pLic.value  = ''; }
    renderPrescriptionPreview();
}
function submitPrescriptionForPrint(url, prefix) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken || !csrfToken.content) {
        if (typeof uhToast === 'function') { uhToast('Unable to prepare the prescription for printing. Refresh the page and try again.', 'error'); }
        return;
    }

    var targetName = 'prescription-print-' + Date.now();
    var printWindow = window.open('', targetName, 'width=900,height=1200');
    if (!printWindow) {
        if (typeof uhToast === 'function') { uhToast('Allow pop-ups to print the prescription.', 'error'); }
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = targetName;
    form.style.display = 'none';

    var fields = {
        _token: csrfToken.content,
        full_name: fieldVal(prefix + '_full_name'),
        age: fieldVal(prefix + '_age'),
        date: fmtDate(fieldVal(prefix + '_date')),
        notes: fieldVal(prefix + '_notes'),
        physician_name: fieldVal(prefix + '_physician_name'),
        physician_role: prefix === 'rxd' ? 'School Dentist' : 'School Physician',
        physician_lic: fieldVal(prefix + '_physician_lic'),
    };
    Object.keys(fields).forEach(function (name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = fields[name];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
}
function prescriptionPrint() { submitPrescriptionForPrint('/clinic/forms/prescription', 'rx'); }

/* ── dentist ── */
function buildRxdCard()                    { return _buildRxCardHtml('rxd'); }
function renderPrescriptionDentistPreview() {
    var el = document.getElementById('prescription-dentist-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="rx-page"><div class="rx-grid">' +
        buildRxdCard() + buildRxdCard() + buildRxdCard() + buildRxdCard() +
    '</div></div>';
}
function clearPrescriptionDentistForm() {
    var form = document.getElementById('prescription-dentist-form');
    if (form) { form.reset(); }
    var d = document.getElementById('rxd_date');
    if (d) { d.value = new Date().toISOString().split('T')[0]; }
    var pName = document.getElementById('rxd_physician_name');
    var pRole = document.getElementById('rxd_physician_role');
    if (pName) { pName.value = ''; }
    if (pRole) { pRole.value = 'School Dentist'; }
    renderPrescriptionDentistPreview();
}
function prescriptionDentistPrint() { submitPrescriptionForPrint('/clinic/forms/prescription/dentist', 'rxd'); }

document.addEventListener('DOMContentLoaded', function () {
    var d1 = document.getElementById('rx_date');
    if (d1 && !d1.value) { d1.value = new Date().toISOString().split('T')[0]; }
    var d2 = document.getElementById('rxd_date');
    if (d2 && !d2.value) { d2.value = new Date().toISOString().split('T')[0]; }
    renderPrescriptionPreview();
    renderPrescriptionDentistPreview();
});
