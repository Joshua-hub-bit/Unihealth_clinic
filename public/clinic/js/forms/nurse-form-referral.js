/**
 * forms/nurse-form-referral.js
 * Physician Referral + Dentist Referral — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var REFERRAL_STYLE = [
    '.rf-page{width:210mm;height:297mm;padding:12mm;background:#fff;font-family:"Times New Roman","Georgia",serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;box-sizing:border-box;}',
    '.rf-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;width:100%;height:100%;gap:8mm;}',
    '.rf-card{border:0.6pt solid #1a1a1a;padding:3.5mm 4.5mm 3mm;display:flex;flex-direction:column;position:relative;overflow:hidden;background:#fff;border-radius:2px;}',
    '.rf-hdr{display:flex;flex-direction:row;align-items:center;gap:2mm;border-bottom:0.75pt solid #111;padding-bottom:1.5mm;margin-bottom:2mm;}',
    '.rf-hdr img.logo{width:14mm;height:14mm;object-fit:contain;flex-shrink:0;transform:translateX(1.75mm) scale(1.5);transform-origin:center;}',
    '.rf-ht{line-height:1.15;flex:1;font-family:Arial,sans-serif;min-width:0;}',
    '.rf-republic{color:#003C8F;font-size:4.5pt;font-weight:600;}',
    '.rf-uni{color:#F6BF00;font-weight:900;font-size:6pt;letter-spacing:0.1px;text-transform:uppercase;white-space:nowrap;}',
    '.rf-office{color:#003C8F;font-weight:800;font-size:5pt;margin-top:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.rf-addr{color:#003C8F;font-size:4.5pt;font-weight:500;margin-top:0.5px;line-height:1.15;display:flex;flex-direction:column;gap:0;}',
    '.rf-addr .rf-addr-loc,.rf-addr .rf-addr-city,.rf-addr .rf-addr-zip{display:block;}',
    '.rf-title{text-align:center;font-size:12pt;font-weight:900;text-transform:uppercase;letter-spacing:2px;color:#000;margin-bottom:3mm;font-family:"Times New Roman",serif;}',
    '.rf-fields{display:flex;flex-direction:column;gap:2.4mm;flex:1;font-size:8pt;line-height:1.3;}',
    '.rf-field{display:flex;flex-direction:row;align-items:baseline;gap:1.5mm;}',
    '.rf-field .rf-label{font-weight:700;font-family:"Times New Roman",serif;font-size:8.5pt;white-space:nowrap;flex-shrink:0;}',
    '.rf-field .rf-line{flex:1;border-bottom:0.5pt solid #000;min-height:4mm;padding:0 2px;font-size:8.5pt;font-family:"Times New Roman",serif;word-break:break-word;}',
    '.rf-field .rf-line-sm{max-width:28mm;flex:0 0 28mm;}',
    '.rf-field .rf-line-hide{visibility:visible;}',
    '.rf-phys{margin-top:4mm;font-family:"Times New Roman",serif;font-size:8pt;line-height:1.35;}',
    '.rf-phys .rf-name{font-weight:700;font-size:9pt;text-transform:uppercase;letter-spacing:0.4px;}',
    '.rf-phys .rf-role{font-style:italic;font-size:8pt;}',
    '.rf-phys .rf-lic{font-size:7.5pt;}',
    '.rf-page-foot{margin-top:auto;padding-top:1mm;}',
    '.rf-page-foot .rf-foot-row{display:flex;align-items:center;justify-content:flex-end;width:100%;}',
    '.rf-page-foot img.rf-foot-img{width:100%;height:14mm;max-height:14mm;object-fit:fill;}',
].join('');

injectStyle('rf-styles',  REFERRAL_STYLE);
injectStyle('rfd-styles', REFERRAL_STYLE);

/* ── shared card builder ── */
function _buildRfCardHtml(prefix) {
    var p      = prefix + '_';
    var name   = escHtml(fieldVal(p + 'name'));
    var age    = escHtml(fieldVal(p + 'age'));
    var addr   = escHtml(fieldVal(p + 'address'));
    var chief  = escHtml(fieldVal(p + 'chief_complaint'));
    var vitals = escHtml(fieldVal(p + 'vital_signs'));
    var remarks= escHtml(fieldVal(p + 'remarks'));
    var pName  = escHtml(fieldVal(p + 'physician_name'));
    var pRole  = escHtml(prefix === 'rfd' ? 'School Dentist' : 'School Physician');
    var pLic   = escHtml(fieldVal(p + 'physician_lic'));
    var urgency = escHtml(fieldVal(p + 'urgency'));

    return '<div class="rf-card">' +
        '<div class="rf-hdr">' +
            '<img class="logo" src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="rf-ht">' +
                '<div class="rf-republic">Republic of the Philippines</div>' +
                '<div class="rf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="rf-addr">' +
                    '<span class="rf-addr-loc">SUNN Main Campus</span>' +
                    '<span class="rf-addr-city">Sagay City, Negros Occidental</span>' +
                    '<span class="rf-addr-zip">Philippines 6122</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="rf-title">REFERRAL FORM</div>' +
        '<div class="rf-fields">' +
            '<div class="rf-field"><span class="rf-label">Name:</span><span class="rf-line">' + name + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Age:</span><span class="rf-line rf-line-sm rf-line-hide">' + age + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Address:</span><span class="rf-line rf-line-hide">' + addr + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Urgency:</span><span class="rf-line rf-line-hide">' + urgency + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Chief Complaint/s:</span><span class="rf-line rf-line-hide">' + chief + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Vital Signs:</span><span class="rf-line rf-line-hide">' + vitals + '</span></div>' +
            '<div class="rf-field"><span class="rf-label">Remarks:</span><span class="rf-line rf-line-hide">' + remarks + '</span></div>' +
        '</div>' +
        '<div class="rf-phys">' +
            '<div class="rf-name">' + pName.toUpperCase() + '</div>' +
            '<div class="rf-role">' + pRole + '</div>' +
            '<div class="rf-lic">Lic. No.: ' + pLic + '</div>' +
        '</div>' +
        '<div class="rf-page-foot">' +
            '<div class="rf-foot-row">' +
                '<img class="rf-foot-img" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
            '</div>' +
        '</div>' +
    '</div>';
}

/* ── physician ── */
function buildReferralCard()    { return _buildRfCardHtml('rf'); }
function renderReferralPreview() {
    var el = document.getElementById('referral-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="rf-page"><div class="rf-grid">' +
        buildReferralCard() + buildReferralCard() + buildReferralCard() + buildReferralCard() +
    '</div></div>';
}
function clearReferralForm() {
    var form = document.getElementById('referral-form');
    if (form) { form.reset(); }
    var pName = document.getElementById('rf_physician_name');
    var pRole = document.getElementById('rf_physician_role');
    var pLic  = document.getElementById('rf_physician_lic');
    if (pName) { pName.value = ''; }
    if (pRole) { pRole.value = 'School Physician'; }
    if (pLic)  { pLic.value  = ''; }
    renderReferralPreview();
}
function submitReferralForPrint(url, prefix) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken || !csrfToken.content) {
        showToast('Unable to prepare the referral for printing. Refresh the page and try again.', 'error');
        return;
    }

    var targetName = 'referral-print-' + Date.now();
    var printWindow = window.open('', targetName, 'width=900,height=1200');
    if (!printWindow) {
        showToast('Allow pop-ups to print the referral.', 'error');
        return;
    }

    var fields = {
        _token: csrfToken.content,
        name: fieldVal(prefix + '_name'),
        age: fieldVal(prefix + '_age'),
        address: fieldVal(prefix + '_address'),
        urgency: fieldVal(prefix + '_urgency'),
        chief_complaint: fieldVal(prefix + '_chief_complaint'),
        vital_signs: fieldVal(prefix + '_vital_signs'),
        remarks: fieldVal(prefix + '_remarks'),
        physician_name: fieldVal(prefix + '_physician_name'),
        physician_lic: fieldVal(prefix + '_physician_lic'),
    };
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = targetName;
    form.style.display = 'none';

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

function referralPrint() { submitReferralForPrint('/clinic/forms/referral', 'rf'); }

function _referralStudentNumber() {
    var student = typeof currentStudentData !== 'undefined' ? currentStudentData : null;
    return (student && student.student_id) || fieldVal('rf_id') || fieldVal('rfd_id');
}

async function _saveReferralForm(prefix, routeTo) {
    var studentNo = _referralStudentNumber();
    var reason = fieldVal(prefix + '_chief_complaint').trim();
    // referred_to is optional — form type already determines the destination.
    // Fall back to the issuing physician/dentist name when left blank.
    var referredTo = fieldVal(prefix + '_referred_to').trim()
        || fieldVal(prefix + '_physician_name').trim()
        || (routeTo === 'dentist' ? 'School Dentist' : 'School Physician');
    var specialty = fieldVal(prefix + '_specialty').trim();
    var remarks = fieldVal(prefix + '_remarks').trim();
    var vitalSigns = fieldVal(prefix + '_vital_signs').trim();
    var signatureName = fieldVal(prefix + '_physician_name').trim();
    var signatureRole = routeTo === 'dentist' ? 'School Dentist' : 'School Physician';
    var signatureLicenseNo = fieldVal(prefix + '_physician_lic').trim();

    if (!studentNo) {
        showToast('Search for a student before saving the referral.', 'error');
        return;
    }
    if (!reason) {
        showToast('Chief complaint or reason is required.', 'error');
        return;
    }

    try {
        var sourceAppointment = window.__pendingReferralSourceAppointment;
        var sourceAppointmentId = sourceAppointment
            && sourceAppointment.studentId === studentNo
            ? sourceAppointment.appointmentId
            : null;
        if (sourceAppointment && !sourceAppointmentId) {
            window.__pendingReferralSourceAppointment = null;
        }
        var referralPayload = {
            student_no: studentNo,
            route_to: routeTo,
            referred_to_name: referredTo,
            specialty: specialty || null,
            reason: reason,
            management_given: remarks || null,
            vital_signs: vitalSigns || null,
            signature_name: signatureName || null,
            signature_role: signatureRole || null,
            signature_license_no: signatureLicenseNo || null,
            urgency: fieldVal(prefix + '_urgency') || 'routine',
        };
        if (sourceAppointmentId) {
            referralPayload.source_appointment_id = sourceAppointmentId;
            if (sourceAppointment.availabilityId && sourceAppointment.apptTime) {
                referralPayload.availability_id = sourceAppointment.availabilityId;
                referralPayload.appt_time = sourceAppointment.apptTime;
            }
        }
        var response = await uhPost('/referrals', referralPayload);

        if (response && response.ok && response.success) {
            window.__pendingReferralSourceAppointment = null;
            var role = (window.__clinicUser && window.__clinicUser.role)
                || (document.body && document.body.dataset.clinicRole);
            var referralsNav = document.querySelector('[data-title="Referrals"]');
            if (role === 'nurse_head') {
                if (typeof uhShowPage === 'function') {
                    uhShowPage('referrals', referralsNav);
                }
                if (typeof nhRenderReferrals === 'function') {
                    nhRenderReferrals();
                }
                if (typeof nhRenderAppointments === 'function') {
                    nhRenderAppointments();
                }
            } else if (role === 'nurse') {
                if (typeof uhShowPage === 'function') {
                    uhShowPage('referrals-coord', referralsNav);
                }
                if (typeof loadQueueWithFallback === 'function') {
                    loadQueueWithFallback();
                }
                if (typeof loadReferrals === 'function') {
                    loadReferrals();
                }
            } else if (typeof loadReferrals === 'function') {
                loadReferrals();
            }
            showToast(response.scheduled_at
                ? 'Referral saved and appointment scheduled. Opening the Referrals table.'
                : 'Referral saved. Opening the Referrals table.', 'success');
            return;
        }

        var message = response && response.errors
            ? Object.values(response.errors).flat()[0]
            : (response && response.error);

        if (!message && response && response.status >= 500) {
            message = 'The server could not save the referral. Please try again; contact clinic support if the problem continues.';
        } else if (!message && response && response.status >= 400) {
            message = 'The referral was rejected by the server (HTTP ' + response.status + '). Check the referral details and try again.';
        }

        showToast(message || 'The server returned an unexpected response while saving the referral.', 'error');
    } catch (error) {
        console.error('Referral save request failed.', error);
        showToast('Could not reach the clinic server. Check your connection and try again.', 'error');
    }
}

function saveReferralForm() {
    return _saveReferralForm('rf', 'physician');
}

/* ── dentist ── */
function buildReferralDentistCard()    { return _buildRfCardHtml('rfd'); }
function renderReferralDentistPreview() {
    var el = document.getElementById('referral-dentist-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="rf-page"><div class="rf-grid">' +
        buildReferralDentistCard() + buildReferralDentistCard() +
        buildReferralDentistCard() + buildReferralDentistCard() +
    '</div></div>';
}
function clearReferralDentistForm() {
    var form = document.getElementById('referral-dentist-form');
    if (form) { form.reset(); }
    var pName = document.getElementById('rfd_physician_name');
    var pRole = document.getElementById('rfd_physician_role');
    var pLic  = document.getElementById('rfd_physician_lic');
    if (pName) { pName.value = ''; }
    if (pRole) { pRole.value = 'School Dentist'; }
    if (pLic)  { pLic.value  = ''; }
    renderReferralDentistPreview();
}
function referralDentistPrint() { submitReferralForPrint('/clinic/forms/referral/dentist', 'rfd'); }

function saveReferralDentistForm() {
    return _saveReferralForm('rfd', 'dentist');
}

document.addEventListener('DOMContentLoaded', function () {
    renderReferralPreview();
    renderReferralDentistPreview();
});
