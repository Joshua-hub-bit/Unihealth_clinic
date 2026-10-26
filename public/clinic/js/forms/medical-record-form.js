'use strict';

function medicalRecordHeader() {
    return '<div class="dc-header">' +
        '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
        '<div class="dc-ht">' +
            '<div class="dc-republic">Republic of the Philippines</div>' +
            '<div class="dc-university">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
            '<div class="dc-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
            '<div class="dc-address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>' +
        '</div>' +
    '</div>';
}

function medicalRecordFooter() {
    return '<div class="dc-footer">' +
        '<div class="dc-tagline">THE FUTURE SHINES BRIGHTEST</div>' +
        '<div class="dc-footer-row">' +
            '<div class="dc-contact"><i class="fa fa-phone"></i> (034) 703 8802<br><i class="fa fa-envelope"></i> info@sunn.edu.ph<br><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
            '<img class="dc-logos" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
        '</div>' +
    '</div>';
}

function renderMedicalRecord() {
    const value = id => esc(document.getElementById(id)?.value || '');
    const field = (label, id, width) => `
        <span style="display:inline-flex;align-items:baseline;gap:3px;min-width:0;${width ? `flex:${width};` : 'flex:1;'}">
            <strong style="white-space:nowrap;">${label}</strong>
            <span style="flex:1;min-width:8mm;min-height:5mm;border-bottom:1px solid #222;padding:0 2px;overflow-wrap:anywhere;">${value(id) || '&nbsp;'}</span>
        </span>`;

    const html = `
        <article class="mrf-sheet" style="box-sizing:border-box;width:210mm;height:297mm;padding:19mm;background:#fff;color:#111;font:8pt Arial,sans-serif;display:flex;flex-direction:column;">
            <style>
                .mrf-sheet .dc-header { display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm; }
                .mrf-sheet .dc-header img { width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center; }
                .mrf-sheet .dc-ht { line-height:1.3;font-family:Arial,sans-serif; }
                .mrf-sheet .dc-republic { color:#003C8F;font-size:8pt;font-weight:600; }
                .mrf-sheet .dc-university { color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:.3px;text-transform:uppercase;white-space:nowrap; }
                .mrf-sheet .dc-office { color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap; }
                .mrf-sheet .dc-address { color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5; }
                .mrf-sheet .dc-footer { margin-top:2mm;padding-top:0; }
                .mrf-sheet .dc-tagline { text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:6pt;letter-spacing:.2px;margin-bottom:1mm;line-height:1; }
                .mrf-sheet .dc-footer-row { display:flex;align-items:center;justify-content:space-between;width:100%;gap:3mm; }
                .mrf-sheet .dc-contact { font-size:5.5pt;color:#003C8F;font-weight:600;line-height:1.3;flex-shrink:0; }
                .mrf-sheet .dc-contact i { display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F; }
                .mrf-sheet .dc-logos { display:block;flex:0 0 70mm;width:70mm;height:8mm;max-height:8mm;object-fit:contain;object-position:right center; }
            </style>
            ${medicalRecordHeader()}
            <h1 style="text-align:center;font-size:11pt;margin:1mm 0 2mm;">Medical Record</h1>

            <section style="padding:1mm 0 2mm;margin-bottom:1mm;flex-shrink:0;">
                <div style="display:flex;gap:3mm;margin-bottom:2mm;">
                    ${field('Name: Last name', 'mrf_last_name', 1)}
                    ${field('First name', 'mrf_first_name', 1)}${field('Middle name', 'mrf_middle_name', 1)}
                    ${field('Birth date:', 'mrf_birth_date', 1)}${field('Age:', 'mrf_age', 0.55)}
                </div>
                <div style="display:flex;gap:3mm;">
                    ${field('Course:', 'mrf_course', 1)}
                    ${field('Gender:', 'mrf_gender', 0.75)}
                    ${field('Address:', 'mrf_address', 1.7)}
                    ${field('Contact #:', 'mrf_contact', 1)}
                </div>
            </section>

            <div style="display:grid;grid-template-columns:17% 23% 27% 33%;flex:1;min-height:0;border:1px solid #222;">
                <section style="min-width:0;border-right:1px solid #222;display:flex;flex-direction:column;">
                    <h2 style="font-size:7pt;text-align:center;margin:0;padding:2mm 1mm;border-bottom:1px solid #222;">Date / Time</h2>
                    <div style="padding:2mm 1.5mm;">
                        ${field('Date:', 'mrf_visit_date')}
                        ${field('Time:', 'mrf_visit_time')}
                    </div>
                    <h2 style="font-size:7pt;text-align:center;margin:1mm 0 0;padding:2mm 1mm;">Vital Signs</h2>
                    <div style="padding:2mm 1.5mm;display:flex;flex-direction:column;gap:2mm;">
                        ${field('BP:', 'mrf_bp')}
                        ${field('Temp:', 'mrf_temp')}
                        ${field('PR:', 'mrf_pulse')}
                    </div>
                </section>
                <section style="min-width:0;border-right:1px solid #222;display:flex;flex-direction:column;">
                    <h2 style="font-size:7pt;text-align:center;margin:0;padding:2mm 1mm;border-bottom:1px solid #222;">Chief Complaint</h2>
                    <div style="padding:2mm;white-space:pre-wrap;overflow-wrap:anywhere;flex:1;">${value('mrf_reason')}</div>
                </section>
                <section style="min-width:0;border-right:1px solid #222;display:flex;flex-direction:column;">
                    <h2 style="font-size:7pt;text-align:center;margin:0;padding:2mm 1mm;border-bottom:1px solid #222;">Treatment</h2>
                    <div style="padding:2mm;white-space:pre-wrap;overflow-wrap:anywhere;flex:1;">${value('mrf_treatment')}</div>
                </section>
                <section style="min-width:0;display:flex;flex-direction:column;">
                    <h2 style="font-size:7pt;text-align:center;margin:0;padding:2mm 1mm;border-bottom:1px solid #222;">Remarks / Nurse’s Notes</h2>
                    <div style="padding:2mm;white-space:pre-wrap;overflow-wrap:anywhere;flex:1;">${value('mrf_notes')}</div>
                </section>
            </div>
            ${medicalRecordFooter()}
        </article>`;

    const preview = document.getElementById('medical-record-preview');
    if (preview) { preview.innerHTML = html; }
}

function medicalRecordPrint() {
    renderMedicalRecord();
    const preview = document.getElementById('medical-record-preview');
    if (!preview) { return; }
    const win = window.open('', '_blank', 'width=1200,height=900');
    if (!win) {
        uhAlert('Please allow pop-ups for this site to print the form.', { title: 'Printing unavailable' });
        return;
    }
    win.document.write(`<!doctype html><html><head><title>Medical Record</title>
        <style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0; padding: 0; background: #fff; font-family: Arial, sans-serif; }
            article { width: 210mm; height: 297mm; page-break-after: avoid; padding:19mm;display:flex;flex-direction:column; }
            * { box-sizing: border-box; }
            .dc-header { display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm; }
            .dc-header img { width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center; }
            .dc-ht { line-height:1.3;font-family:Arial,sans-serif; }
            .dc-republic { color:#003C8F;font-size:8pt;font-weight:600; }
            .dc-university { color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:.3px;text-transform:uppercase;white-space:nowrap; }
            .dc-office { color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap; }
            .dc-address { color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5; }
            .dc-footer { margin-top:2mm;padding-top:0; }
            .dc-tagline { text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:6pt;letter-spacing:.2px;margin-bottom:1mm;line-height:1; }
            .dc-footer-row { display:flex;align-items:center;justify-content:space-between;width:100%;gap:3mm; }
            .dc-contact { font-size:5.5pt;color:#003C8F;font-weight:600;line-height:1.3;flex-shrink:0; }
            .dc-contact i { display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F; }
            .dc-logos { display:block;flex:0 0 70mm;width:70mm;height:8mm;max-height:8mm;object-fit:contain;object-position:right center; }
        </style>
        </head><body>${preview.innerHTML}<script>window.addEventListener('load',function(){setTimeout(function(){window.focus();window.print();},200);});<\/script></body></html>`);
    win.document.close();
}

function clearMedicalRecordForm() {
    const form = document.getElementById('medical-record-form');
    if (form) { form.reset(); }
    const search = document.getElementById('medical-record_search');
    if (search) { search.value = ''; }
    const today = uhManilaDateKey(new Date());
    const date = document.getElementById('mrf_visit_date');
    const time = document.getElementById('mrf_visit_time');
    if (date) { date.value = today; date.max = today; }
    if (time) {
        time.value = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Manila',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).format(new Date());
    }
    renderMedicalRecord();
}

async function saveMedicalRecordForm() {
    const form = document.getElementById('medical-record-form');
    if (!form || !form.reportValidity()) { return; }

    const studentNo = document.getElementById('mrf_student_id')?.value || '';
    if (!studentNo) {
        showToast('Search for a student before saving this medical record.', 'error');
        return;
    }

    const button = document.getElementById('mrf-save');
    if (button?.disabled) { return; }
    if (button) { button.disabled = true; }

    const field = id => document.getElementById(id)?.value || '';
    try {
        const response = await apiFetch('save_visit', 'POST', {
            student_no: studentNo,
            visit_date: field('mrf_visit_date'),
            visit_time: field('mrf_visit_time'),
            reason: field('mrf_reason').trim(),
            treatment: field('mrf_treatment').trim(),
            bp: field('mrf_bp').trim(),
            temp: field('mrf_temp'),
            pulse: field('mrf_pulse'),
            rr: field('mrf_rr'),
            notes: field('mrf_notes').trim(),
        });

        if (!response?.success) {
            const validationMessage = response?.errors
                ? Object.values(response.errors).flat()[0]
                : null;
            throw new Error(validationMessage || response?.message || response?.error || 'Could not save the medical record.');
        }
        showToast('Medical record saved successfully.', 'success');
    } catch (error) {
        showToast(error.message || 'Could not save the medical record. Please try again.', 'error');
    } finally {
        if (button) { button.disabled = false; }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const today = uhManilaDateKey(new Date());
    const date = document.getElementById('mrf_visit_date');
    const time = document.getElementById('mrf_visit_time');
    if (date) { date.value = today; date.max = today; }
    if (time) {
        time.value = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Manila',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).format(new Date());
    }
    const visitDate = document.getElementById('mrf_visit_date');
    if (visitDate) { visitDate.max = today; }
    renderMedicalRecord();
});
