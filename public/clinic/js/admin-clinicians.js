'use strict';

var adClinicianRecords = [];
var adClinicianCampuses = [];

function adClinicianEscape(value) {
    return typeof uhEscapeHtml === 'function' ? uhEscapeHtml(value || '') : String(value || '');
}

async function adLoadClinicians() {
    var tbody = document.getElementById('ad-clinicians-body');
    if (!tbody) { return; }

    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading clinicians...</td></tr>';
    try {
        var response = await uhGet('/admin/clinicians');
        if (!response || !response.ok || !response.success) {
            throw new Error(response?.message || response?.error || 'Unable to load clinicians.');
        }

        adClinicianRecords = response.clinicians || [];
        adClinicianCampuses = response.campuses || [];
        var campusSelect = document.getElementById('ad-clinician-campus');
        if (campusSelect) {
            campusSelect.replaceChildren(new Option('Available to all campuses', ''));
            adClinicianCampuses.forEach(function (campus) {
                campusSelect.add(new Option(campus.name, String(campus.id)));
            });
        }
        if (!adClinicianRecords.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No clinicians yet. Add a physician or dentist to make them available on clinic forms.</td></tr>';
            return;
        }

        tbody.innerHTML = adClinicianRecords.map(function (clinician) {
            var type = clinician.provider_type === 'dentist' ? 'Dentist' : 'Physician';
            var status = clinician.is_active
                ? '<span class="uh-badge active">Available</span>'
                : '<span class="uh-badge cancelled">Inactive</span>';
            return '<tr>' +
                '<td><strong>' + adClinicianEscape(clinician.name) + '</strong>' +
                    (clinician.designation ? '<br><small>' + adClinicianEscape(clinician.designation) + '</small>' : '') +
                    (clinician.email ? '<br><small>' + adClinicianEscape(clinician.email) + '</small>' : '') + '</td>' +
                '<td>' + type + '</td>' +
                '<td>' + adClinicianEscape(clinician.campus?.name || 'Available to all campuses') + '</td>' +
                '<td>' + adClinicianEscape(clinician.license_no || '—') + '</td>' +
                '<td>' + status + '</td>' +
                '<td><div class="uh-flex uh-gap-8">' +
                    '<button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="adEditClinician(' + Number(clinician.id) + ')">Edit</button>' +
                    '<button type="button" class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeleteClinician(' + Number(clinician.id) + ')">Delete</button>' +
                '</div></td>' +
                '</tr>';
        }).join('');
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">Unable to load clinician directory.</td></tr>';
        uhToast(error.message || 'Unable to load clinician directory.', 'error');
    }
}

function adResetClinicianForm() {
    var form = document.getElementById('ad-clinician-form');
    if (!form) { return; }
    form.reset();
    document.getElementById('ad-clinician-id').value = '';
    document.getElementById('ad-clinician-active').checked = true;
    document.getElementById('ad-clinician-form-title').textContent = 'Add Physician or Dentist';
}

function adEditClinician(id) {
    var clinician = adClinicianRecords.find(function (record) {
        return Number(record.id) === Number(id);
    });
    if (!clinician) {
        uhToast('Clinician details are no longer available. Refresh and try again.', 'error');
        return;
    }
    document.getElementById('ad-clinician-id').value = clinician.id;
    document.getElementById('ad-clinician-name').value = clinician.name || '';
    document.getElementById('ad-clinician-type').value = clinician.provider_type || 'physician';
    document.getElementById('ad-clinician-campus').value = clinician.campus_id ? String(clinician.campus_id) : '';
    document.getElementById('ad-clinician-designation').value = clinician.designation || '';
    document.getElementById('ad-clinician-license').value = clinician.license_no || '';
    document.getElementById('ad-clinician-contact').value = clinician.contact || '';
    document.getElementById('ad-clinician-email').value = clinician.email || '';
    document.getElementById('ad-clinician-active').checked = Boolean(clinician.is_active);
    document.getElementById('ad-clinician-form-title').textContent = 'Edit Clinician';
    document.getElementById('ad-clinician-name').focus();
}

async function adDeleteClinician(id) {
    if (!window.confirm('Delete this clinician from the form directory?')) { return; }
    try {
        var response = await uhDelete('/admin/clinicians/' + encodeURIComponent(id));
        if (!response || !response.ok || !response.success) {
            throw new Error(response?.message || response?.error || 'Could not delete clinician.');
        }
        uhToast('Clinician removed from the form directory.', 'success');
        await adLoadClinicians();
    } catch (error) {
        uhToast(error.message || 'Could not delete clinician.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ad-clinician-form');
    if (!form) { return; }
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        var id = document.getElementById('ad-clinician-id').value;
        var payload = {
            name: document.getElementById('ad-clinician-name').value.trim(),
            campus_id: document.getElementById('ad-clinician-campus').value || null,
            provider_type: document.getElementById('ad-clinician-type').value,
            designation: document.getElementById('ad-clinician-designation').value.trim() || null,
            license_no: document.getElementById('ad-clinician-license').value.trim() || null,
            contact: document.getElementById('ad-clinician-contact').value.trim() || null,
            email: document.getElementById('ad-clinician-email').value.trim() || null,
            is_active: document.getElementById('ad-clinician-active').checked,
        };

        try {
            var response = id
                ? await uhPut('/admin/clinicians/' + encodeURIComponent(id), payload)
                : await uhPost('/admin/clinicians', payload);
            if (!response || !response.ok || !response.success) {
                var validationError = response?.errors ? Object.values(response.errors).flat()[0] : null;
                throw new Error(validationError || response?.message || response?.error || 'Could not save clinician.');
            }
            uhToast(id ? 'Clinician details updated.' : 'Clinician added to the form directory.', 'success');
            adResetClinicianForm();
            await adLoadClinicians();
        } catch (error) {
            uhToast(error.message || 'Could not save clinician.', 'error');
        }
    });
});
