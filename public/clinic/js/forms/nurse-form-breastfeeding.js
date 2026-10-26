/**
 * forms/nurse-form-breastfeeding.js
 * Breastfeeding Room User Agreement — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var BF_STYLE = [
    '.bf-page{width:210mm;min-height:297mm;padding:18mm 20mm 14mm;display:flex;flex-direction:column;background:#fff;font-family:Arial,sans-serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;box-sizing:border-box;}',
    '.bf-hdr{display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm;}',
    '.bf-hdr img{width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center;}',
    '.bf-ht{line-height:1.3;font-family:Arial,sans-serif;}',
    '.bf-republic{color:#003C8F;font-size:8pt;font-weight:600;}',
    '.bf-uni{color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.bf-office{color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap;}',
    '.bf-addr{color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5;}',
    '.bf-title{text-align:center;font-size:11pt;font-weight:700;color:#000;margin:2mm 0 0.5mm;}',
    '.bf-subtitle{text-align:center;font-size:9pt;font-style:italic;margin-bottom:3.5mm;}',
    '.bf-fields{display:flex;flex-direction:column;gap:1.8mm;font-size:9pt;margin-bottom:3.5mm;}',
    '.bf-field{display:flex;flex-direction:row;align-items:flex-end;gap:2mm;}',
    '.bf-label{font-size:9pt;white-space:nowrap;flex-shrink:0;font-weight:400;}',
    '.bf-line{flex:1;border-bottom:0.8pt solid #000;min-height:4.5mm;padding:0 2px;font-size:9pt;}',
    '.bf-instruction{font-size:9pt;font-weight:700;margin-bottom:3mm;line-height:1.4;}',
    '.bf-agreements{display:flex;flex-direction:column;gap:3mm;font-size:8.5pt;flex:1;}',
    '.bf-agree-item{display:flex;flex-direction:row;align-items:flex-start;gap:2mm;line-height:1.5;}',
    '.bf-check{flex-shrink:0;min-width:6mm;border-bottom:0.8pt solid #000;height:4mm;margin-top:2px;}',
    '.bf-agree-text{flex:1;font-size:8.5pt;}',
    '.bf-sig-section{margin-top:6mm;}',
    '.bf-sig-line{border-bottom:0.8pt solid #000;width:55mm;height:4.5mm;margin-bottom:1mm;}',
    '.bf-sig-label{font-size:8.5pt;}',
    '.bf-footer{margin-top:auto;padding-top:4mm;}',
    '.bf-cc{font-size:8pt;font-style:italic;font-weight:600;color:#000;margin-bottom:1mm;}',
    '.bf-tagline{font-size:8.5pt;font-style:italic;font-weight:700;color:#003C8F;text-align:center;margin-bottom:2mm;}',
    '.bf-foot-bar{display:flex;flex-direction:row;align-items:center;gap:4mm;}',
    '.bf-contact{font-size:7pt;color:#003C8F;font-weight:600;line-height:1.6;flex-shrink:0;}',
    '.bf-contact i{display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F;}',
    '.bf-foot-img{flex:1;height:14mm;max-height:14mm;object-fit:fill;}',
    '.bf-page-num{text-align:center;font-size:8pt;margin-top:2mm;}',
].join('');

injectStyle('bf-styles', BF_STYLE);

var BF_AGREEMENTS = [
    { id: 'bf_a1', text: 'I agree that it is my choice to use the Breastfeeding Room. Steps will be taken to keep the room clean, safe, and secure, but I understand that others cannot be held liable for the day-to-day condition of the room and its contents.' },
    { id: 'bf_a2', text: 'I agree that I am responsible for doing my part to keep the room clean and orderly. I understand that breast milk is a body fluid and careful cleanup of spills, etc., is important.' },
    { id: 'bf_a3', text: 'Out of respect for the needs of other nursing mothers who need to access the space, I agree that access to the breastfeeding room is for my use only, and that sharing my access to the space with anyone else is not permitted.' },
    { id: 'bf_a4', text: 'I will only use the room for expressing breast milk, and I will secure the door after using it.' },
    { id: 'bf_a5', text: 'I understand/agree that I am responsible for transportation and proper storage of milk and bringing my own breast pump and storage bags.' },
    { id: 'bf_a6', text: 'I understand that in order to ensure that only authorized persons use the breastfeeding room, my name may be shared with the University employees who are in charge of providing this facility and for some compliance report that University is required.' },
    { id: 'bf_a7', text: 'I understand/agree to stop using the facility three years from the birth of my child.' },
    { id: 'bf_a8', text: 'I understand and agree that failure to comply with any of the above agreements could be cause for loss of access to the facility now and in the future.' },
];

function buildBreastfeedingPage() {
    var name     = escHtml(fieldVal('bf_name'));
    var dept     = escHtml(fieldVal('bf_dept'));
    var jobtitle = escHtml(fieldVal('bf_jobtitle'));
    var email    = escHtml(fieldVal('bf_email'));
    var contact  = escHtml(fieldVal('bf_contact'));

    var agreeHtml = BF_AGREEMENTS.map(function (a) {
        var checked = fieldChecked(a.id);
        var mark = checked
            ? '<span class="bf-check" style="border-bottom:none;font-size:9pt;font-weight:900;width:6mm;text-align:center;">&#10003;</span>'
            : '<span class="bf-check"></span>';
        return '<div class="bf-agree-item">' + mark + '<span class="bf-agree-text">' + a.text + '</span></div>';
    }).join('');

    return '<div class="bf-page">' +
        '<div class="bf-hdr">' +
            '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="bf-ht">' +
                '<div class="bf-republic">Republic of the Philippines</div>' +
                '<div class="bf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="bf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="bf-addr">SUNN Main Campus<br/>Sagay City, Negros Occidental<br/>Philippines 6122</div>' +
            '</div>' +
        '</div>' +
        '<div class="bf-title">Breastfeeding Room User Agreement</div>' +
        '<div class="bf-subtitle">(Must be completed by user before using the room)</div>' +
        '<div class="bf-fields">' +
            '<div class="bf-field"><span class="bf-label">Name:</span><span class="bf-line">' + name + '</span></div>' +
            '<div class="bf-field"><span class="bf-label">Department/College:</span><span class="bf-line">' + dept + '</span></div>' +
            '<div class="bf-field"><span class="bf-label">Job Title/Location (if employee):</span><span class="bf-line">' + jobtitle + '</span></div>' +
            '<div class="bf-field">' +
                '<span class="bf-label">E-mail Adress:</span>' +
                '<span class="bf-line" style="max-width:45mm;">' + email + '</span>' +
                '<span class="bf-label">Contact details:</span>' +
                '<span class="bf-line">' + contact + '</span>' +
            '</div>' +
        '</div>' +
        '<div class="bf-instruction">Please put a checkmark in every statement which will indicate you have read, understand and agree for this.</div>' +
        '<div class="bf-agreements">' + agreeHtml + '</div>' +
        '<div class="bf-sig-section"><div class="bf-sig-line">' + name + '</div><div class="bf-sig-label">User\'s Name and Signature</div></div>' +
        '<div class="bf-footer">' +
            '<div class="bf-cc">cc: HRDO/GADIO/VPs/CampusDirectors/Dean/Clinic</div>' +
            '<div class="bf-tagline">The Future Shines Brightest</div>' +
            '<div class="bf-foot-bar">' +
                '<div class="bf-contact"><i class="fa fa-phone"></i> (034) 703 8802<br/><i class="fa fa-envelope"></i> info@sunn.edu.ph<br/><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
                '<img class="bf-foot-img" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
            '</div>' +
            '<div class="bf-page-num"></div>' +
        '</div>' +
    '</div>';
}

function renderBreastfeedingPreview() {
    var el = document.getElementById('breastfeeding-preview');
    if (el) { el.innerHTML = buildBreastfeedingPage(); }
}

function clearBreastfeedingForm() {
    var form = document.getElementById('breastfeeding-form');
    if (form) { form.reset(); }
    renderBreastfeedingPreview();
}

function breastfeedingPrint() {
    var params = new URLSearchParams({
        name:     fieldVal('bf_name'),
        dept:     fieldVal('bf_dept'),
        jobtitle: fieldVal('bf_jobtitle'),
        email:    fieldVal('bf_email'),
        contact:  fieldVal('bf_contact'),
    });
    Array.prototype.forEach.call(
        document.querySelectorAll('#breastfeeding-form input[type="checkbox"][id^="bf_a"]:checked'),
        function (checkbox) {
            params.append('agreements[]', checkbox.id);
        }
    );
    openPrint('/clinic/forms/breastfeeding?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    renderBreastfeedingPreview();
});
