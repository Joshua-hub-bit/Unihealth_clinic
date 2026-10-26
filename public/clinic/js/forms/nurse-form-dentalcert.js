/**
 * forms/nurse-form-dentalcert.js
 * Dental Certificate — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var DENTALCERT_STYLE = [
    '.dc-page{width:210mm;min-height:297mm;padding:19mm;display:flex;flex-direction:column;background:#fff;font-family:"Times New Roman",Arial,serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;}',
    '.dc-header{display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm;}',
    '.dc-header img{width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center;}',
    '.dc-ht{line-height:1.3;font-family:Arial,sans-serif;}',
    '.dc-republic{color:#003C8F;font-size:8pt;font-weight:600;}',
    '.dc-university{color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.dc-office{color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap;}',
    '.dc-address{color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5;}',
    '.dc-title{text-align:center;font-size:14pt;font-weight:900;text-decoration:underline;letter-spacing:1px;color:#000;margin-bottom:4.5mm;font-family:"Times New Roman",serif;line-height:1.0;}',
    '.dc-body{flex:1;font-family:"Times New Roman",serif;font-size:10pt;color:#000;line-height:1.0;}',
    '.dc-date-row{text-align:left;margin-bottom:2.5mm;font-size:10pt;line-height:1.0;}',
    '.dc-ul{display:inline-block;border-bottom:0.8pt solid #000;min-width:45mm;padding:0 2px;font-weight:600;}',
    '.dc-ul-long{min-width:100mm;}',
    '.dc-ul-name{min-width:65mm;max-width:65mm;}',
    '.dc-ul-age{min-width:10mm;max-width:14mm;}',
    '.dc-ul-tooth{min-width:35mm;}',
    '.dc-ul-mid{min-width:60mm;}',
    '.dc-ul-line{display:block;border-bottom:0.8pt solid #000;width:100%;height:6mm;margin-top:3mm;}',
    '.dc-ul-line-text{height:auto;min-height:6mm;line-height:6mm;border-bottom:0;overflow-wrap:anywhere;background:repeating-linear-gradient(to bottom,transparent 0,transparent calc(6mm - 0.8pt),#000 calc(6mm - 0.8pt),#000 6mm);}',
    '.dc-ul-line-short{display:block;border-bottom:0.8pt solid #000;width:55%;height:6mm;margin-top:3mm;}',
    '.dc-salutation{font-size:10pt;font-weight:400;line-height:1.0;margin-top:3mm;margin-bottom:2.5mm;}',
    '.dc-statement{font-size:10pt;line-height:1.0;margin-bottom:2.5mm;text-align:justify;}',
    '.dc-cert-line{display:block;font-size:10pt;line-height:1.4;}',
    '.dc-cb-row{display:flex;align-items:baseline;gap:3mm;margin:1.5mm 0 1.5mm 8mm;font-size:10pt;line-height:1.0;}',
    '.dc-cb-box{display:inline-block;width:3.5mm;height:3.5mm;border:0.8pt solid #000;flex-shrink:0;position:relative;top:0.5mm;text-align:center;line-height:3.5mm;font-weight:900;font-size:9pt;}',
    '.dc-proc-block{margin:3mm 0 3mm 0;}',
    '.dc-proc-label{font-size:10pt;font-weight:700;line-height:1.0;margin-bottom:1.5mm;}',
    '.dc-sig-block{margin-top:auto;padding-top:6mm;display:flex;justify-content:flex-end;}',
    '.dc-sig-inner{text-align:center;min-width:65mm;}',
    '.dc-sig-prep{font-size:9pt;margin-bottom:15mm;font-family:"Times New Roman",serif;line-height:1.0;}',
    '.dc-sig-prep-label{font-weight:400;}',
    '.dc-sig-line{border-bottom:1pt solid #000;height:1mm;margin-bottom:1.5mm;}',
    '.dc-sig-name{font-weight:700;font-size:9pt;font-family:"Times New Roman",serif;line-height:1.2;}',
    '.dc-sig-lic{font-size:9pt;margin-top:1pt;font-family:"Times New Roman",serif;line-height:1.2;}',
    '.dc-sig-designation{font-size:9pt;margin-top:1pt;font-family:"Times New Roman",serif;line-height:1.2;}',
    '.dc-sig-title{font-size:9pt;margin-top:1pt;font-family:"Times New Roman",serif;line-height:1.2;}',
    '.dc-footer{margin-top:3mm;padding-top:0;}',
    '.dc-tagline{text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:8pt;letter-spacing:0.3px;margin-bottom:1.5mm;line-height:1.0;}',
    '.dc-footer-row{display:flex;align-items:center;justify-content:space-between;width:100%;gap:3mm;}',
    '.dc-contact{font-size:7pt;color:#003C8F;font-weight:600;line-height:1.5;flex-shrink:0;}',
    '.dc-contact i{display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F;}',
    '.dc-logos{flex:1;height:13mm;max-height:13mm;object-fit:fill;}',
    '.dc-page-num{text-align:right;font-size:8pt;color:#003C8F;font-weight:600;margin-top:1mm;line-height:1.0;}',
].join('');

injectStyle('dc-styles', DENTALCERT_STYLE);

/* ── helpers ── */
function dcVal(id)      { return fieldVal(id); }
function dcChecked(id)  { return fieldChecked(id) ? 'X' : ''; }
function dcRadio(name)  { return radioVal(name); }

function dcRow(cbId, label, after) {
    return '<div class="dc-cb-row">' +
        '<span class="dc-cb-box">' + dcChecked(cbId) + '</span>' +
        '<span>' + label + '</span>' +
        (after || '') +
    '</div>';
}

function renderDentalCertPreview() {
    var nameParts = [dcVal('dc_firstname'), dcVal('dc_middlename'), dcVal('dc_surname')]
        .map(function (part) { return part.trim(); })
        .filter(Boolean);
    var fullName = nameParts.length ? nameParts.join(' ') : dcVal('dc_full_name');

    var html =
        '<div class="dc-page">' +
            '<div class="dc-header">' +
                '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
                '<div class="dc-ht">' +
                    '<div class="dc-republic">Republic of the Philippines</div>' +
                    '<div class="dc-university">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                    '<div class="dc-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                    '<div class="dc-address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>' +
                '</div>' +
            '</div>' +
            '<div class="dc-title">DENTAL CERTIFICATE</div>' +
            '<div class="dc-body">' +
                '<div class="dc-date-row">Date: <span class="dc-ul">' + escHtml(fmtDate(dcVal('dc_date'))) + '</span></div>' +
                '<div class="dc-salutation">To Whom It May Concern:</div>' +
                '<div class="dc-statement">' +
                    '<span class="dc-cert-line">This is to certify that</span>' +
                    '<span class="dc-cert-line">Mr./Mrs./Ms.<span class="dc-ul dc-ul-name">' + escHtml(fullName) + '</span>, <span class="dc-ul dc-ul-age">' + escHtml(dcVal('dc_age')) + '</span> years of</span>' +
                    '<span class="dc-cert-line">age, residing at <span class="dc-ul dc-ul-long">' + escHtml(dcVal('dc_address')) + '</span> have</span>' +
                    '<span class="dc-cert-line">visited this clinic and had his/her dental check \u2013 up and</span>' +
                '</div>' +
                '<div class="dc-cb-row"><span class="dc-cb-box">' + (dcRadio('dc_outcome') === 'diagnosed' ? 'X' : '') + '</span><span>was diagnosed that he/she need</span></div>' +
                '<div class="dc-cb-row"><span class="dc-cb-box">' + (dcRadio('dc_outcome') === 'treated'   ? 'X' : '') + '</span><span>was treated for</span></div>' +
                '<div class="dc-proc-block">' +
                    '<div class="dc-proc-label">The following dental procedures:</div>' +
                    dcRow('dc_proc_oral_prophylaxis', 'Oral prophylaxis') +
                    dcRow('dc_proc_temp_filling',     'Temporary filling of tooth/teeth no./nos. <span class="dc-ul dc-ul-tooth">' + escHtml(dcVal('dc_tooth_temp'))    + '</span>') +
                    dcRow('dc_proc_perm_restoration', 'Permanent Restoration of the tooth/teeth no./nos. <span class="dc-ul dc-ul-tooth">' + escHtml(dcVal('dc_tooth_perm'))    + '</span>') +
                    dcRow('dc_proc_extraction',       'Extraction of the tooth no./nos. <span class="dc-ul dc-ul-tooth">' + escHtml(dcVal('dc_tooth_extract')) + '</span>') +
                    dcRow('dc_proc_orthodontic',      'Orthodontic treatment') +
                    dcRow('dc_proc_subgingival',      'Subgingival scaling') +
                    dcRow('dc_proc_impaction',        'Impaction') +
                    dcRow('dc_proc_root_canal',       'Root Canal Treatment') +
                    '<div class="dc-cb-row">' +
                        '<span class="dc-cb-box">' + dcChecked('dc_proc_prosthodontic') + '</span>' +
                        '<span>Prosthodontic Treatment</span>' +
                        '<span style="margin-left:8mm;">Other:</span>' +
                        '<span class="dc-cb-box">' + dcChecked('dc_proc_other') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="dc-ul-line dc-ul-line-text">' + escHtml(dcVal('dc_proc_other_text')) + '</div>' +
                '<div class="dc-statement" style="margin-top:4mm;">This certification is being issued upon the request of the patient for</div>' +
                '<div class="dc-ul-line dc-ul-line-text">' + escHtml(dcVal('dc_purpose')) + '</div>' +
            '</div>' +
            '<div class="dc-sig-block">' +
                '<div class="dc-sig-inner">' +
                    '<div class="dc-sig-prep"><span class="dc-sig-prep-label">Prepared by:</span></div>' +
                    '<div class="dc-sig-name">'  + escHtml(dcVal('dc_sig_name').toUpperCase()) + '</div>' +
                    '<div class="dc-sig-line"></div>' +
                    '<div class="dc-sig-lic">Lic No.: '  + escHtml(dcVal('dc_sig_license')) + '</div>' +
                    '<div class="dc-sig-designation">School Dentist</div>' +
                    '<div class="dc-sig-title">PTR No.: ' + escHtml(dcVal('dc_sig_ptr')     || '_____')   + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="dc-footer">' +
                '<div class="dc-tagline">THE FUTURE SHINES BRIGHTEST</div>' +
                '<div class="dc-footer-row">' +
                    '<div class="dc-contact"><i class="fa fa-phone"></i> (034) 703 8802<br><i class="fa fa-envelope"></i> info@sunn.edu.ph<br><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
                    '<img class="dc-logos" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
                '</div>' +
            '</div>' +
        '</div>';

    var el = document.getElementById('dentalcert-preview');
    if (el) { el.innerHTML = html; }
}

function updateDentalCertIframe() { renderDentalCertPreview(); }

function clearDentalCertForm() {
    var form = document.getElementById('dentalcert-form');
    if (form) { form.reset(); }
    var d = document.getElementById('dc_date');
    if (d) { d.value = new Date().toISOString().split('T')[0]; }
    var sigName  = document.getElementById('dc_sig_name');
    var sigTitle = document.getElementById('dc_sig_title');
    var sigLic   = document.getElementById('dc_sig_license');
    if (sigName)  { sigName.value  = ''; }
    if (sigTitle) { sigTitle.value = 'School Dentist'; }
    if (sigLic)   { sigLic.value   = ''; }
    var s = document.getElementById('dentalcert_search');
    if (s) {
        s.value = '';
        var clr = document.getElementById('dentalcert_search_clear');
        if (clr) { clr.style.display = 'none'; }
    }
    var tb = document.querySelector('#page-form-dentalcert .search-toolbar');
    if (tb) { tb.classList.remove('is-loaded', 'is-searching'); }
    renderDentalCertPreview();
}

function dentalcertPrint() {
    var params = new URLSearchParams({
        date:                  dcVal('dc_date'),
        surname:               dcVal('dc_surname'),
        firstname:             dcVal('dc_firstname'),
        middlename:            dcVal('dc_middlename'),
        full_name:             dcVal('dc_full_name'),
        age:                   dcVal('dc_age'),
        sex:                   dcVal('dc_sex'),
        civil:                 dcVal('dc_civil'),
        address:               dcVal('dc_address'),
        diagnosed:             dcRadio('dc_outcome') === 'diagnosed' ? '1' : '',
        treated:               dcRadio('dc_outcome') === 'treated'   ? '1' : '',
        proc_oral_prophylaxis: dcChecked('dc_proc_oral_prophylaxis'),
        proc_temp_filling:     dcChecked('dc_proc_temp_filling'),
        tooth_temp:            dcVal('dc_tooth_temp'),
        proc_perm_restoration: dcChecked('dc_proc_perm_restoration'),
        tooth_perm:            dcVal('dc_tooth_perm'),
        proc_extraction:       dcChecked('dc_proc_extraction'),
        tooth_extract:         dcVal('dc_tooth_extract'),
        proc_orthodontic:      dcChecked('dc_proc_orthodontic'),
        proc_subgingival:      dcChecked('dc_proc_subgingival'),
        proc_impaction:        dcChecked('dc_proc_impaction'),
        proc_root_canal:       dcChecked('dc_proc_root_canal'),
        proc_prosthodontic:    dcChecked('dc_proc_prosthodontic'),
        proc_other:            dcChecked('dc_proc_other'),
        proc_other_text:       dcVal('dc_proc_other_text'),
        purpose:               dcVal('dc_purpose'),
        sig_name:              dcVal('dc_sig_name'),
        sig_license:           dcVal('dc_sig_license'),
        sig_title:             'School Dentist',
        sig_ptr:               dcVal('dc_sig_ptr'),
    });
    openPrint('/clinic/forms/dentalcert?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    var d = document.getElementById('dc_date');
    if (d && !d.value) { d.value = new Date().toISOString().split('T')[0]; }
    renderDentalCertPreview();
});
