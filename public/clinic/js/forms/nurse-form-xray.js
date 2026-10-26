/**
 * forms/nurse-form-xray.js
 * X-Ray Form (2×2 grid) — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var XRAY_STYLE = [
    '.xr-page{width:210mm;height:297mm;padding:12mm;background:#fff;font-family:"Times New Roman","Georgia",serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;box-sizing:border-box;}',
    '.xr-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;width:100%;height:100%;gap:8mm;}',
    '.xr-card{border:0.6pt solid #1a1a1a;padding:3.5mm 4.5mm 3mm;display:flex;flex-direction:column;position:relative;overflow:hidden;background:#fff;border-radius:2px;}',
    '.xr-hdr{display:flex;flex-direction:row;align-items:center;gap:2mm;border-bottom:0.75pt solid #111;padding-bottom:1.5mm;margin-bottom:2mm;}',
    '.xr-hdr img.logo{width:14mm;height:14mm;object-fit:contain;flex-shrink:0;transform:translateX(1.75mm) scale(1.5);transform-origin:center;}',
    '.xr-ht{line-height:1.15;flex:1;font-family:Arial,sans-serif;min-width:0;}',
    '.xr-republic{color:#003C8F;font-size:4.5pt;font-weight:600;}',
    '.xr-uni{color:#F6BF00;font-weight:900;font-size:6pt;letter-spacing:0.1px;text-transform:uppercase;white-space:nowrap;}',
    '.xr-office{color:#003C8F;font-weight:800;font-size:5pt;margin-top:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.xr-addr{color:#003C8F;font-size:4.5pt;font-weight:500;margin-top:0.5px;line-height:1.15;display:flex;flex-direction:column;gap:0;}',
    '.xr-addr .xr-addr-loc,.xr-addr .xr-addr-city,.xr-addr .xr-addr-zip{display:block;}',
    '.xr-spacer{flex:0 0 10mm;}',
    '.xr-fields{display:flex;flex-direction:column;gap:3mm;font-size:8.5pt;}',
    '.xr-field{display:flex;flex-direction:row;align-items:baseline;gap:1.5mm;}',
    '.xr-field .xr-label{font-weight:400;white-space:nowrap;flex-shrink:0;font-size:8.5pt;}',
    '.xr-field .xr-line{flex:1;border-bottom:0.5pt solid #000;min-height:4mm;padding:0 2px;font-size:8.5pt;word-break:break-word;}',
    '.xr-check-row{display:flex;flex-direction:row;align-items:center;gap:3mm;margin-top:5mm;}',
    '.xr-check-box{width:6.5mm;height:6.5mm;border:0.8pt solid #333;flex-shrink:0;display:inline-block;}',
    '.xr-check-box-checked{background:#000;color:#fff;font-size:8pt;text-align:center;line-height:6.5mm;}',
    '.xr-check-label{font-size:8.5pt;font-weight:400;}',
    '.xr-mid-spacer{flex:1;}',
    '.xr-bottom{display:flex;flex-direction:column;align-items:flex-start;gap:5mm;padding-bottom:2mm;}',
    '.xr-proceed{font-size:8.5pt;text-align:left;color:#111;}',
    '.xr-staff-row{display:flex;flex-direction:row;align-items:baseline;gap:1.5mm;font-size:8.5pt;}',
    '.xr-staff-label{white-space:nowrap;font-size:8.5pt;}',
    '.xr-staff-line{display:inline-block;border-bottom:0.5pt solid #000;min-width:28mm;height:4mm;}',
    '.xr-page-foot{margin-top:2mm;}',
    '.xr-page-foot .xr-foot-row{display:flex;align-items:center;justify-content:flex-end;width:100%;}',
    '.xr-page-foot img.xr-foot-img{width:100%;height:14mm;max-height:14mm;object-fit:fill;}',
].join('');

injectStyle('xr-styles', XRAY_STYLE);

function buildXrayCard() {
    var name    = escHtml(fieldVal('xr_name'));
    var course  = escHtml(fieldVal('xr_course'));
    var staff   = escHtml(fieldVal('xr_staff'));
    var checked = fieldChecked('xr_checked');

    var checkBox = checked
        ? '<span class="xr-check-box xr-check-box-checked">&#10003;</span>'
        : '<span class="xr-check-box"></span>';

    return '<div class="xr-card">' +
        '<div class="xr-hdr">' +
            '<img class="logo" src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="xr-ht">' +
                '<div class="xr-republic">Republic of the Philippines</div>' +
                '<div class="xr-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="xr-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="xr-addr">' +
                    '<span class="xr-addr-loc">SUNN Main Campus</span>' +
                    '<span class="xr-addr-city">Sagay City, Negros Occidental</span>' +
                    '<span class="xr-addr-zip">Philippines 6122</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="xr-spacer"></div>' +
        '<div class="xr-fields">' +
            '<div class="xr-field"><span class="xr-label">Name:</span><span class="xr-line">' + name + '</span></div>' +
            '<div class="xr-field"><span class="xr-label">Course &amp; Year:</span><span class="xr-line">' + course + '</span></div>' +
        '</div>' +
        '<div class="xr-check-row">' + checkBox + '<span class="xr-check-label">Chest X-ray Result</span></div>' +
        '<div class="xr-mid-spacer"></div>' +
        '<div class="xr-bottom">' +
            '<div class="xr-proceed">May proceed to registrar.</div>' +
            '<div class="xr-staff-row">' +
                '<span class="xr-staff-label">Clinic Nurse/Staff:</span>' +
                '<span class="xr-staff-line">' + staff + '</span>' +
            '</div>' +
        '</div>' +
        '<div class="xr-page-foot"><div class="xr-foot-row">' +
            '<img class="xr-foot-img" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
        '</div></div>' +
    '</div>';
}

function renderXrayPreview() {
    var el = document.getElementById('xray-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="xr-page"><div class="xr-grid">' +
        buildXrayCard() + buildXrayCard() + buildXrayCard() + buildXrayCard() +
    '</div></div>';
}

function clearXrayForm() {
    var form = document.getElementById('xray-form');
    if (form) { form.reset(); }
    renderXrayPreview();
}

function xrayPrint() {
    var params = new URLSearchParams({
        name: fieldVal('xr_name'),
        course: fieldVal('xr_course'),
        staff: fieldVal('xr_staff'),
        checked: fieldChecked('xr_checked') ? '1' : '0',
    });

    openPrint('/clinic/forms/xray?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    renderXrayPreview();
});
