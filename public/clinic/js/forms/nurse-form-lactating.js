/**
 * forms/nurse-form-lactating.js
 * Lactating Mother Request Accommodation Form — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var LM_STYLE = [
    '.lm-page{width:210mm;min-height:297mm;padding:18mm 20mm 14mm;display:flex;flex-direction:column;background:#fff;font-family:Arial,sans-serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;box-sizing:border-box;}',
    '.lm-hdr{display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm;}',
    '.lm-hdr img{width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center;}',
    '.lm-ht{line-height:1.3;font-family:Arial,sans-serif;}',
    '.lm-republic{color:#003C8F;font-size:8pt;font-weight:600;}',
    '.lm-uni{color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.lm-office{color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap;}',
    '.lm-addr{color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5;}',
    '.lm-title{text-align:center;font-size:11pt;font-weight:900;letter-spacing:0.5px;color:#000;margin:3mm 0 2mm;text-transform:uppercase;}',
    '.lm-intro{font-size:8.5pt;text-align:justify;margin-bottom:2mm;line-height:1.5;}',
    '.lm-required{font-size:8.5pt;text-align:center;font-style:italic;margin-bottom:4mm;}',
    '.lm-fields{display:flex;flex-direction:column;gap:3mm;font-size:9pt;flex:1;}',
    '.lm-field{display:flex;flex-direction:row;align-items:flex-end;gap:2mm;}',
    '.lm-label{font-size:9pt;white-space:nowrap;flex-shrink:0;line-height:1.4;}',
    '.lm-label-block{font-size:9pt;flex-shrink:0;line-height:1.4;}',
    '.lm-label-italic{font-style:italic;font-size:8pt;display:block;}',
    '.lm-line{flex:1;border-bottom:0.8pt solid #000;min-height:4.5mm;padding:0 2px;font-size:9pt;word-break:break-word;}',
    '.lm-textarea-lines{min-height:9mm;margin-top:1mm;background-image:repeating-linear-gradient(to bottom,transparent 0,transparent calc(4.5mm - 0.8pt),#000 calc(4.5mm - 0.8pt),#000 4.5mm);}',
    '.lm-long-line{width:100%;padding:0 2px;font-size:9pt;line-height:4.5mm;white-space:pre-wrap;overflow-wrap:anywhere;}',
    '.lm-sig-section{display:flex;flex-direction:column;gap:6mm;margin-top:5mm;}',
    '.lm-sig-row{display:flex;flex-direction:row;align-items:flex-end;gap:2mm;}',
    '.lm-sig-label{font-size:9pt;white-space:nowrap;flex-shrink:0;}',
    '.lm-sig-line{flex:0 0 55mm;border-bottom:0.8pt solid #000;height:4.5mm;}',
    '.lm-footer{margin-top:auto;padding-top:4mm;}',
    '.lm-cc{font-size:8pt;font-style:italic;font-weight:600;color:#000;margin-bottom:1mm;}',
    '.lm-tagline{font-size:8.5pt;font-style:italic;font-weight:700;color:#003C8F;text-align:center;margin-bottom:2mm;}',
    '.lm-foot-bar{display:flex;flex-direction:row;align-items:center;gap:4mm;}',
    '.lm-contact{font-size:7pt;color:#003C8F;font-weight:600;line-height:1.6;flex-shrink:0;}',
    '.lm-contact i{display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F;}',
    '.lm-foot-img{flex:1;height:14mm;max-height:14mm;object-fit:fill;}',
].join('');

injectStyle('lm-styles', LM_STYLE);

function buildLactatingPage() {
    var name       = escHtml(fieldVal('lm_name'));
    var dept       = escHtml(fieldVal('lm_dept'));
    var jobtitle   = escHtml(fieldVal('lm_jobtitle'));
    var email      = escHtml(fieldVal('lm_email'));
    var contact    = escHtml(fieldVal('lm_contact'));
    var dob        = escHtml(fmtDate(fieldVal('lm_dob')));
    var begin      = escHtml(fmtDate(fieldVal('lm_begin')));
    var end        = escHtml(fmtDate(fieldVal('lm_end')));
    var desc       = escHtml(fieldVal('lm_description'));
    var reqBy      = escHtml(fieldVal('lm_requested_by'));
    var recomm     = escHtml(fieldVal('lm_recommending'));
    var approvedBy = escHtml(fieldVal('lm_approved_by'));

    return '<div class="lm-page">' +
        '<div class="lm-hdr">' +
            '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="lm-ht">' +
                '<div class="lm-republic">Republic of the Philippines</div>' +
                '<div class="lm-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="lm-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="lm-addr">SUNN Main Campus<br/>Sagay City, Negros Occidental<br/>Philippines 6122</div>' +
            '</div>' +
        '</div>' +
        '<div class="lm-title">LACTATING MOTHER REQUEST ACCOMODATION FORM</div>' +
        '<div class="lm-intro">SUNN is committed to provide reasonable break time for nursing mothers to express breast milk up to 2 years following the child\'s birth. Submit this form to immediate supervisors for review and approval. The completed and approved form must be forwarded to appropriate office/s.</div>' +
        '<div class="lm-required">All information requested is required.</div>' +
        '<div class="lm-fields">' +
            '<div class="lm-field"><span class="lm-label">Employee/Student\' Complete name:</span><span class="lm-line">' + name + '</span></div>' +
            '<div class="lm-field"><span class="lm-label">Department/ College:</span><span class="lm-line">' + dept + '</span></div>' +
            '<div class="lm-field"><span class="lm-label">Job Title (if employee):</span><span class="lm-line">' + jobtitle + '</span></div>' +
            '<div class="lm-field"><span class="lm-label">Email Address:</span><span class="lm-line">' + email + '</span></div>' +
            '<div class="lm-field"><span class="lm-label">Contact Details:</span><span class="lm-line">' + contact + '</span></div>' +
            '<div class="lm-field" style="align-items:flex-start;">' +
                '<div class="lm-label-block"><span>Child\'s Date of Birth:</span><br/><span class="lm-label-italic">(pls. attach live birth)</span></div>' +
                '<span class="lm-line" style="margin-top:auto;">' + dob + '</span>' +
            '</div>' +
            '<div class="lm-field"><span class="lm-label">Requested Begin Date:</span><span class="lm-line">' + begin + '</span></div>' +
            '<div class="lm-field" style="align-items:flex-start;">' +
                '<div class="lm-label-block"><span>Requested End Date:</span><br/><span class="lm-label-italic">(2 years from child\'s birth)</span></div>' +
                '<span class="lm-line" style="margin-top:auto;">' + end + '</span>' +
            '</div>' +
            '<div style="font-size:9pt;margin-top:1mm;">Describe the accommodation being requested, e.g., approximate time(s) and number of breaks:</div>' +
            '<div class="lm-textarea-lines"><div class="lm-long-line">' + desc + '</div></div>' +
        '</div>' +
        '<div class="lm-sig-section">' +
            '<div class="lm-sig-row"><span class="lm-sig-label">Requested by:</span><span class="lm-sig-line">' + reqBy + '</span></div>' +
            '<div class="lm-sig-row"><span class="lm-sig-label">Recommending Approval:</span><span class="lm-sig-line">' + recomm + '</span></div>' +
            '<div class="lm-sig-row"><span class="lm-sig-label">Approved by:</span><span class="lm-sig-line">' + approvedBy + '</span></div>' +
        '</div>' +
        '<div class="lm-footer">' +
            '<div class="lm-cc">cc: HRDO/GADIO/VPs/CampusDirectors/DEAN/Clinic</div>' +
            '<div class="lm-tagline">The Future Shines Brightest</div>' +
            '<div class="lm-foot-bar">' +
                '<div class="lm-contact"><i class="fa fa-phone"></i> (034) 703 8802<br/><i class="fa fa-envelope"></i> info@sunn.edu.ph<br/><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
                '<img class="lm-foot-img" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
            '</div>' +
        '</div>' +
    '</div>';
}

function renderLactatingPreview() {
    var el = document.getElementById('lactating-preview');
    if (el) { el.innerHTML = buildLactatingPage(); }
}

function clearLactatingForm() {
    var form = document.getElementById('lactating-form');
    if (form) { form.reset(); }
    renderLactatingPreview();
}

function lactatingPrint() {
    var params = new URLSearchParams({
        name:         fieldVal('lm_name'),
        dept:         fieldVal('lm_dept'),
        jobtitle:     fieldVal('lm_jobtitle'),
        email:        fieldVal('lm_email'),
        contact:      fieldVal('lm_contact'),
        dob:          fieldVal('lm_dob'),
        begin:        fieldVal('lm_begin'),
        end:          fieldVal('lm_end'),
        description:  fieldVal('lm_description'),
        requested_by: fieldVal('lm_requested_by'),
        recommending: fieldVal('lm_recommending'),
        approved_by:  fieldVal('lm_approved_by'),
    });
    openPrint('/clinic/forms/lactating?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    renderLactatingPreview();
});
