'use strict';

function uhViewStudentHealthRecord(studentNo) {
    var modal = document.getElementById('uh-staff-health-record-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'uh-staff-health-record-modal';
        modal.className = 'uh-modal-overlay';
        modal.innerHTML =
            '<div class="uh-modal" role="dialog" aria-modal="true" aria-labelledby="uh-staff-health-record-title" style="max-width:900px">' +
                '<div class="uh-modal-header">' +
                    '<span class="uh-modal-title" id="uh-staff-health-record-title"><i class="fa fa-file-medical" style="color:#2a7a6f;margin-right:8px"></i>Personal Health Record</span>' +
                    '<button class="uh-modal-close" type="button" aria-label="Close" data-phr-close><i class="fa fa-times"></i></button>' +
                '</div>' +
                '<div class="uh-modal-body" data-phr-body><p style="text-align:center;color:#6b7280;padding:24px 0">Loading health record...</p></div>' +
                '<div class="uh-modal-footer">' +
                    '<button class="uh-btn uh-btn-primary" type="button" data-phr-close>Close</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-phr-close]')) {
                modal.classList.add('hidden');
            }
        });
    }

    var body = modal.querySelector('[data-phr-body]');
    modal.classList.remove('hidden');
    body.innerHTML = '<p style="text-align:center;color:#6b7280;padding:24px 0">Loading health record...</p>';

    uhGet('/staff/students/' + encodeURIComponent(studentNo) + '/health-record')
        .then(function (data) {
            if (!data || !data.success) {
                throw new Error((data && data.error) || 'Health record not found.');
            }
            body.innerHTML = uhRenderStaffHealthRecord(
                data.student || {},
                data.record || {},
                data.visits || []
            );
        })
        .catch(function (error) {
            body.innerHTML = '<p style="color:#b91c1c;text-align:center;padding:24px 0">' +
                uhStaffHealthRecordEscape(error.message || 'Failed to load health record.') + '</p>';
        });
}

function uhRenderStaffHealthRecord(student, record, visits) {
    var value = function (key, fallback) {
        var result = record[key];
        if (result === null || result === undefined || result === '') {
            result = student[key];
        }
        return result === null || result === undefined || result === '' ? (fallback || 'Not recorded') : result;
    };
    var dateValue = function (key) {
        var result = value(key);
        return result === 'Not recorded' ? result : String(result).substring(0, 10);
    };
    var yesNo = function (key) {
        var result = record[key];
        if (result === null || result === undefined || result === '') {
            return 'Not recorded';
        }

        return result === true || result === 1 || result === '1' || result === 'yes' || result === 'Y'
            ? 'Yes'
            : 'Not recorded';
    };
    var section = function (title, fields) {
        return '<section style="margin-bottom:18px">' +
            '<h4 style="margin:0 0 10px;color:#1f5f57;font-size:.95rem;border-bottom:1px solid #dbe7e4;padding-bottom:7px">' +
                uhStaffHealthRecordEscape(title) + '</h4>' +
            '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px 18px">' +
                fields.map(function (field) {
                    return '<div style="min-width:0"><small style="display:block;color:#6b7280;font-size:.72rem;margin-bottom:2px">' +
                        uhStaffHealthRecordEscape(field[0]) + '</small><strong style="font-size:.84rem;font-weight:600;color:#1f2937;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word">' +
                        uhStaffHealthRecordEscape(String(field[1] === undefined || field[1] === null || field[1] === '' ? 'Not recorded' : field[1])) + '</strong></div>';
                }).join('') +
            '</div>' +
        '</section>';
    };
    var name = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ');
    var symptoms = [
        ['Headache', 'sym_headache'], ['Dizziness', 'sym_dizziness'], ['Fainting', 'sym_fainting'],
        ['Blurred vision', 'sym_blurring_vision'], ['Hearing difficulty', 'sym_hearing_difficulty'],
        ['Back pain', 'sym_back_pain'], ['Chest pain', 'sym_chest_pain'], ['Breathing difficulty', 'sym_breathing_difficulty'],
        ['Cough', 'sym_cough'], ['Sore throat', 'sym_sore_throat'], ['Runny nose', 'sym_runny_nose'],
        ['Nose bleed', 'sym_nose_bleed'], ['Vomiting', 'sym_vomiting'], ['Nausea', 'sym_nausea'],
        ['Abdominal pain', 'sym_abdominal_pain'], ['Fever', 'sym_fever'], ['Frequent urination', 'sym_frequent_urination'],
        ['Weight loss', 'sym_weight_loss'], ['Difficulty sleeping', 'sym_difficulty_sleeping'], ['Nervousness', 'sym_nervousness'],
    ].map(function (item) { return [item[0], yesNo(item[1])]; });
    var immunizations = [
        ['Chicken pox', 'imm_chicken_pox'], ['Typhoid', 'imm_typhoid'], ['Hepatitis A', 'imm_hepatitis_a'],
        ['Hepatitis B', 'imm_hepatitis_b'], ['Tetanus', 'imm_tetanus'], ['COVID-19', 'imm_covid'],
    ].map(function (item) { return [item[0], yesNo(item[1])]; });

    return '<div style="background:#f8fbfa;border:1px solid #dbe7e4;border-radius:10px;padding:16px">' +
        '<div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:18px">' +
            '<div><h3 style="margin:0 0 4px;font-size:1.15rem;color:#173b36">' + uhStaffHealthRecordEscape(name || 'Student') + '</h3>' +
                '<p style="margin:0;color:#6b7280;font-size:.8rem">' + uhStaffHealthRecordEscape(student.student_id || 'Not recorded') + '</p></div>' +
            '<span class="uh-badge">' + uhStaffHealthRecordEscape(value('status', 'No record status')) + '</span>' +
        '</div>' +
        section('Student information', [
            ['Student ID', student.student_id], ['Campus', student.campus ? student.campus.name : ''],
            ['Department', student.department], ['Course / year', value('course_year')],
            ['Section', student.section], ['Sex', student.sex], ['Civil status', student.civil_status],
            ['Date of birth', dateValue('date_of_birth')], ['Age', value('age')], ['Contact', value('contact')],
            ['Address', student.address],
        ]) +
        section('Vitals and measurements', [
            ['Blood type', value('blood_type')], ['Height (cm)', value('height_cm')],
            ['Weight (kg)', value('weight_kg')], ['Blood pressure', value('bp')],
            ['Pulse', value('pr')], ['Temperature', value('temp')],
            ['Record date', dateValue('record_date')],
        ]) +
        section('Medical history', [
            ['Past medical history', value('past_medical_history')],
            ['Allergies', value('allergy_detail')], ['Accidents', value('accident_detail')],
            ['Hospitalizations', value('hospitalization_detail')], ['Surgeries', value('surgical_detail')],
            ['Family history', value('family_history')],
        ]) +
        section('Emergency contact', [
            ['Name', value('ec_name')], ['Relationship', value('ec_relationship')],
            ['Contact', value('ec_contact')], ['Address', value('ec_address')],
        ]) +
        section('Immunizations', immunizations) +
        section('Reported symptoms', symptoms) +
        section('Additional notes', [
            ['Findings / recommendation', value('findings_recommendation')],
            ['Recorded by', value('signature_name')],
        ]) +
        section('Imported patient-record details', [
            ['Booking appointment choice', value('imported_booking_choice')],
            ['Reason / symptoms (source record)', value('imported_reason_symptoms')],
            ['Priority level (source record)', value('imported_priority_level')],
            ['Prescriber (source record)', value('imported_prescriber')],
            ['Prescription (source record)', value('imported_prescription')],
            ['Source dataset', value('source_dataset')],
            ['Source record number', value('source_record_no')],
        ]) +
        uhRenderStudentVisitHistory(visits) +
    '</div>';
}

function uhRenderStudentVisitHistory(visits) {
    if (!visits.length) {
        return '<section style="margin-bottom:18px"><h4 style="margin:0 0 10px;color:#1f5f57;font-size:.95rem;border-bottom:1px solid #dbe7e4;padding-bottom:7px">Clinic visit history</h4><p style="margin:0;color:#6b7280">No clinic visits recorded.</p></section>';
    }

    return '<section style="margin-bottom:18px">' +
        '<h4 style="margin:0 0 10px;color:#1f5f57;font-size:.95rem;border-bottom:1px solid #dbe7e4;padding-bottom:7px">Clinic visit history</h4>' +
        visits.map(function (visit) {
            var recordedBy = visit.physician || visit.nurse || {};
            var recordedName = [recordedBy.first_name, recordedBy.last_name].filter(Boolean).join(' ') || 'Not recorded';
            var date = visit.visit_date ? String(visit.visit_date).substring(0, 10) : 'Not recorded';
            var vitals = [
                visit.bp ? 'BP ' + visit.bp : '',
                visit.temp ? 'Temp ' + visit.temp + '°C' : '',
                visit.pulse ? 'Pulse ' + visit.pulse + ' bpm' : '',
                visit.rr ? 'RR ' + visit.rr : '',
                visit.o2_sat ? 'O₂ ' + visit.o2_sat + '%' : '',
            ].filter(Boolean).join(' · ') || 'Not recorded';

            return '<article style="padding:12px 0;border-bottom:1px solid #e5e7eb">' +
                '<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">' +
                    '<strong style="color:#1f2937">' + uhStaffHealthRecordEscape(visit.reason || 'Clinic visit reason not recorded') + '</strong>' +
                    '<small style="color:#6b7280">' + uhStaffHealthRecordEscape(date + (visit.visit_time ? ' · ' + uhFormatTime(visit.visit_time) : '')) + '</small>' +
                '</div>' +
                '<p style="margin:7px 0;color:#374151;font-size:.82rem"><strong>Vitals:</strong> ' + uhStaffHealthRecordEscape(vitals) + '</p>' +
                '<p style="margin:5px 0;color:#374151;font-size:.82rem"><strong>Assessment / findings:</strong> ' + uhStaffHealthRecordEscape(visit.symptoms || 'Not recorded') + '</p>' +
                '<p style="margin:5px 0;color:#374151;font-size:.82rem"><strong>Remarks:</strong> ' + uhStaffHealthRecordEscape(visit.notes || 'Not recorded') + '</p>' +
                '<p style="margin:5px 0;color:#374151;font-size:.82rem"><strong>Treatment:</strong> ' + uhStaffHealthRecordEscape(visit.treatment || 'Not recorded') + '</p>' +
                '<small style="color:#6b7280">Recorded by ' + uhStaffHealthRecordEscape(recordedName) + '</small>' +
            '</article>';
        }).join('') +
    '</section>';
}

function uhStaffHealthRecordEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('keydown', function (event) {
    var modal = document.getElementById('uh-staff-health-record-modal');
    if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        modal.classList.add('hidden');
    }
});
