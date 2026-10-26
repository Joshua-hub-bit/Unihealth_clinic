/**
 * forms/nurse-form-medcert.js
 * Medical Certificate — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var MEDCERT_STYLE = [
    '.mc-page{width:210mm;min-height:297mm;padding:25.4mm;display:flex;flex-direction:column;background:#fff;font-family:Arial,sans-serif;}',
    '.mc-header{display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm;}',
    '.mc-header img{width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center;}',
    '.mc-ht{line-height:1.3;}',
    '.mc-republic{color:#003C8F;font-size:8pt;font-weight:600;}',
    '.mc-university{color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.mc-office{color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap;}',
    '.mc-address{color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5;}',
    '.mc-title{text-align:center;font-size:13pt;font-weight:900;text-decoration:underline;letter-spacing:2px;color:#111;margin:5mm 0 6mm;}',
    '.mc-body{flex:1;font-size:10pt;color:#111;}',
    '.mc-date-row{text-align:right;margin-bottom:6mm;font-size:10pt;}',
    '.mc-ul{display:inline-block;border-bottom:1pt solid #111;min-width:40mm;padding:0 2px;}',
    '.mc-name-label{font-weight:700;font-size:10pt;margin-bottom:1mm;}',
    '.mc-name-cols{display:flex;gap:4mm;margin-bottom:2mm;}',
    '.mc-name-col{flex:1;}',
    '.mc-name-val{border-bottom:1pt solid #111;min-height:5mm;padding:0 2px;font-size:10pt;}',
    '.mc-name-lbl{font-size:8pt;color:#444;margin-top:1px;}',
    '.mc-inline-row{display:flex;align-items:baseline;gap:6mm;margin-bottom:2mm;font-size:10pt;}',
    '.mc-inline-field{display:inline-flex;align-items:baseline;gap:2px;}',
    '.mc-addr-row{display:flex;align-items:baseline;gap:2mm;margin-bottom:5mm;}',
    '.mc-addr-line{flex:1;border-bottom:1pt solid #111;min-height:5mm;padding:0 2px;}',
    '.mc-vitals{margin-bottom:5mm;}',
    '.mc-vital-row{margin-bottom:2mm;font-size:10pt;}',
    '.mc-diag{margin-top:4mm;}',
    '.mc-diag-label{font-weight:700;margin-bottom:2mm;font-size:10pt;}',
    '.mc-diag-lines{min-height:30mm;padding:0 2px;word-break:break-word;white-space:pre-wrap;overflow-wrap:break-word;line-height:7mm;background-image:repeating-linear-gradient(to bottom,transparent,transparent calc(7mm - 1pt),#111 calc(7mm - 1pt),#111 7mm);background-size:100% 7mm;background-position:0 6mm;}',
    '.mc-sig-block{margin-top:auto;padding-top:10mm;display:flex;justify-content:flex-end;}',
    '.mc-sig-inner{text-align:center;min-width:55mm;}',
    '.mc-sig-line{border-bottom:1.5pt solid #111;height:10mm;margin-bottom:2mm;}',
    '.mc-sig-name{font-weight:700;font-size:10pt;}',
    '.mc-sig-lic{font-size:9pt;margin-top:1px;}',
    '.mc-sig-title{font-size:9pt;margin-top:1px;}',
    '.mc-footer{margin-top:auto;padding-top:2mm;}',
    '.mc-tagline{text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:8pt;letter-spacing:0.3px;margin-bottom:2mm;}',
    '.mc-footer-row{display:flex;align-items:center;justify-content:space-between;width:100%;gap:4mm;}',
    '.mc-contact{font-size:7pt;color:#003C8F;font-weight:600;line-height:1.7;flex-shrink:0;}',
    '.mc-contact i{display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F;}',
    '.mc-logos{flex:1;height:14mm;max-height:14mm;object-fit:fill;}',
].join('');

injectStyle('mc-styles', MEDCERT_STYLE);

function renderMedCertPreview() {
    var v = {
        date:    fieldVal('mc_date'),
        surname: fieldVal('mc_surname'),
        first:   fieldVal('mc_firstname'),
        middle:  fieldVal('mc_middlename'),
        age:     fieldVal('mc_age'),
        sex:     fieldVal('mc_sex'),
        civil:   fieldVal('mc_civil'),
        address: fieldVal('mc_address'),
        bp:      fieldVal('mc_bp'),
        temp:    fieldVal('mc_temp'),
        assess:  fieldVal('mc_assessment'),
        sig:     fieldVal('mc_sig_name'),
        lic:     fieldVal('mc_sig_license'),
        title:   'School Physician',
    };

    var html =
        '<div class="mc-page">' +
            '<div class="mc-header">' +
                '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
                '<div class="mc-ht">' +
                    '<div class="mc-republic">Republic of the Philippines</div>' +
                    '<div class="mc-university">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                    '<div class="mc-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                    '<div class="mc-address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>' +
                '</div>' +
            '</div>' +
            '<div class="mc-title">MEDICAL CERTIFICATE</div>' +
            '<div class="mc-body">' +
                '<div class="mc-date-row">Date: <span class="mc-ul">' + escHtml(fmtDate(v.date)) + '</span></div>' +
                '<div class="mc-name-label">NAME OF PATIENT:</div>' +
                '<div class="mc-name-cols">' +
                    '<div class="mc-name-col"><div class="mc-name-val">' + escHtml(v.surname) + '</div><div class="mc-name-lbl">Surname</div></div>' +
                    '<div class="mc-name-col"><div class="mc-name-val">' + escHtml(v.first)   + '</div><div class="mc-name-lbl">First Name</div></div>' +
                    '<div class="mc-name-col"><div class="mc-name-val">' + escHtml(v.middle)  + '</div><div class="mc-name-lbl">Middle Name</div></div>' +
                '</div>' +
                '<div class="mc-inline-row">' +
                    '<div class="mc-inline-field">Age: <span class="mc-ul" style="min-width:15mm">'  + escHtml(v.age)   + '</span></div>' +
                    '<div class="mc-inline-field">Sex: <span class="mc-ul" style="min-width:18mm">'  + escHtml(v.sex)   + '</span></div>' +
                    '<div class="mc-inline-field">Civil Status: <span class="mc-ul" style="min-width:22mm">' + escHtml(v.civil) + '</span></div>' +
                '</div>' +
                '<div class="mc-addr-row"><span style="font-weight:700">Address:</span><span class="mc-addr-line">' + escHtml(v.address) + '</span></div>' +
                '<div class="mc-vitals">' +
                    '<div class="mc-vital-row">BP: <span class="mc-ul" style="min-width:28mm">'   + escHtml(v.bp)   + '</span></div>' +
                    '<div class="mc-vital-row">Temp: <span class="mc-ul" style="min-width:25mm">' + escHtml(v.temp) + '</span></div>' +
                '</div>' +
                '<div class="mc-diag">' +
                    '<div class="mc-diag-label">Recommendation/Diagnosis:</div>' +
                    '<div class="mc-diag-lines">' + escHtml(v.assess) + '</div>' +
                '</div>' +
                '<div class="mc-sig-block">' +
                    '<div class="mc-sig-inner">' +
                        '<div class="mc-sig-line"></div>' +
                        '<div class="mc-sig-name">'  + escHtml(v.sig.toUpperCase()) + '</div>' +
                        '<div class="mc-sig-lic">'   + escHtml(v.lic)               + '</div>' +
                        '<div class="mc-sig-title">' + escHtml(v.title)             + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="mc-footer">' +
                '<div class="mc-tagline">The Future Shines Brightest</div>' +
                '<div class="mc-footer-row">' +
                    '<div class="mc-contact"><i class="fa fa-phone"></i> (034) 703 8802<br><i class="fa fa-envelope"></i> info@sunn.edu.ph<br><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
                    '<img class="mc-logos" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
                '</div>' +
            '</div>' +
        '</div>';

    var el = document.getElementById('medcert-preview');
    if (el) { el.innerHTML = html; }
}

/* alias kept for backward-compat with oninput handler */
function updateMedCertIframe() { renderMedCertPreview(); }

function clearMedCertForm() {
    var form = document.getElementById('medcert-form');
    if (form) { form.reset(); }
    var d = document.getElementById('mc_date');
    if (d) { d.value = new Date().toISOString().split('T')[0]; }
    var sigName  = document.getElementById('mc_sig_name');
    var sigTitle = document.getElementById('mc_sig_title');
    var sigLic   = document.getElementById('mc_sig_license');
    if (sigName)  { sigName.value  = ''; }
    if (sigTitle) { sigTitle.value = 'School Physician'; }
    if (sigLic)   { sigLic.value   = ''; }
    var s = document.getElementById('medcert_search');
    if (s) {
        s.value = '';
        var clr = document.getElementById('medcert_search_clear');
        if (clr) { clr.style.display = 'none'; }
    }
    var tb = document.querySelector('#page-form-medcert .search-toolbar');
    if (tb) { tb.classList.remove('is-loaded', 'is-searching'); }
    renderMedCertPreview();
}

function medcertPrint() {
    var params = new URLSearchParams({
        date:        fieldVal('mc_date'),
        surname:     fieldVal('mc_surname'),
        firstname:   fieldVal('mc_firstname'),
        middlename:  fieldVal('mc_middlename'),
        age:         fieldVal('mc_age'),
        sex:         fieldVal('mc_sex'),
        civil:       fieldVal('mc_civil'),
        address:     fieldVal('mc_address'),
        bp:          fieldVal('mc_bp'),
        temp:        fieldVal('mc_temp'),
        assessment:  fieldVal('mc_assessment'),
        sig_name:    fieldVal('mc_sig_name'),
        sig_license: fieldVal('mc_sig_license'),
        sig_title:   'School Physician',
    });
    openPrint('/clinic/forms/medcert?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    var d = document.getElementById('mc_date');
    if (d && !d.value) { d.value = new Date().toISOString().split('T')[0]; }
    renderMedCertPreview();
});
