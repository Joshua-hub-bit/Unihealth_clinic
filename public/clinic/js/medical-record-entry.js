'use strict';

(function () {
    const page = document.getElementById('page-medical-records');
    if (!page) { return; }

    const role = window.__clinicUser?.role;
    if (!['admin', 'nurse_head', 'nurse'].includes(role)) { return; }

    const apiRoot = role === 'nurse'
        ? '/nurse'
        : role === 'nurse_head'
            ? '/nurse-head'
            : '/admin';
    if (role === 'nurse') {
        page.classList.add('mre-nurse-records');
        const medicalSubtitle = page.querySelector('#mre-campus-section .mre-subtitle');
        const dentalSubtitle = page.querySelector('#dre-campus-section .mre-subtitle');
        if (medicalSubtitle) {
            medicalSubtitle.textContent = 'Open your assigned campus to review student medical histories.';
        }
        if (dentalSubtitle) {
            dentalSubtitle.textContent = 'Open your assigned campus to review saved dental examinations.';
        }
    }
    const endpoint = `${apiRoot}/medical-records`;
    const campusGrid = document.getElementById('mre-campus-grid');
    const dentalCampusGrid = document.getElementById('dre-campus-grid');
    const dentalCampusSection = document.getElementById('dre-campus-section');
    const dentalRecordsSection = document.getElementById('dre-record-section');
    const dentalCampusTitle = document.getElementById('dre-campus-title');
    const dentalRecordSearch = document.getElementById('dre-record-search');
    const dentalCourseFilter = document.getElementById('dre-course-filter');
    const dentalRecordBody = document.getElementById('dre-record-body');
    const dentalRecordPagination = document.getElementById('dre-record-pagination');
    const dentalStudentRecordsSection = document.getElementById('dre-student-records');
    const dentalStudentRecordsTitle = document.getElementById('dre-student-records-title');
    const dentalStudentRecordsBody = document.getElementById('dre-student-records-body');
    const dentalStudentRecordsPagination = document.getElementById('dre-student-records-pagination');
    const dentalRecordDetail = document.getElementById('dre-record-detail');
    const dentalRecordDetailContent = document.getElementById('dre-record-detail-content');
    const studentSection = document.getElementById('mre-student-section');
    const studentTitle = document.getElementById('mre-campus-title');
    const studentBody = document.getElementById('mre-student-body');
    const studentSearch = document.getElementById('mre-student-search');
    const medicalCourseFilter = document.getElementById('mre-course-filter');
    const studentPagination = document.getElementById('mre-student-pagination');
    const recordDetailPanel = document.getElementById('mre-record-detail-panel');
    const recordStudentProfile = document.getElementById('mre-record-student-profile');
    const visitList = document.getElementById('mre-visit-list');
    const visitPagination = document.getElementById('mre-visit-pagination');
    const formPanel = document.getElementById('mre-form-panel');
    const form = document.getElementById('mre-form');
    const studentId = document.getElementById('mre-student-id');
    const studentPreview = document.getElementById('mre-student-preview');
    const message = document.getElementById('mre-form-message');
    const submitButton = document.getElementById('mre-submit');
    let selectedCampus = null;
    let selectedStudent = null;
    let selectedCampusCard = null;
    let searchTimer = null;
    let currentPage = 1;
    let studentRequest = 0;
    let visitPage = 1;
    let visitRequest = 0;
    let selectedDentalCampus = null;
    let selectedDentalCampusCard = null;
    let selectedDentalStudent = null;
    let dentalRecordPage = 1;
    let dentalRecordRequest = 0;
    let dentalSearchTimer = null;
    let dentalStudentRecordPage = 1;
    let dentalStudentRecordRequest = 0;

    function displayValue(value) {
        return value === null || value === undefined || value === '' ? '—' : String(value);
    }

    function fullName(student) {
        return [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ');
    }

    function updateCourseOptions(select, courses) {
        if (!select) { return; }
        const currentValue = select.value;
        const options = Array.from(new Set((courses || [])
            .map(course => String(course || '').trim())
            .filter(Boolean)));
        select.replaceChildren(new Option('All courses', ''));
        options.forEach(course => select.add(new Option(course, course)));
        select.value = options.includes(currentValue) ? currentValue : '';
    }

    function showState(container, text, className) {
        container.replaceChildren();
        const state = document.createElement('div');
        state.className = className || 'mre-state';
        state.textContent = text;
        container.append(state);
    }

    function addText(parent, tag, className, value) {
        const element = document.createElement(tag);
        if (className) { element.className = className; }
        element.textContent = displayValue(value);
        parent.append(element);
        return element;
    }

    function buildCampusCard(campus, countLabel, onSelect) {
        const card = document.createElement('article');
        card.className = 'mre-campus-card';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'mre-campus-trigger';
        trigger.dataset.campusId = String(campus.id);
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', `${campus.name}, ${countLabel}`);
        const icon = document.createElement('span');
        icon.className = 'mre-campus-icon';
        icon.innerHTML = '<i class="fa fa-building-columns" aria-hidden="true"></i>';
        addText(trigger, 'strong', 'mre-card-title', campus.name);
        addText(trigger, 'span', 'mre-card-detail', countLabel);
        const arrow = document.createElement('span');
        arrow.className = 'mre-card-arrow';
        arrow.innerHTML = '<i class="fa fa-arrow-right" aria-hidden="true"></i>';
        trigger.prepend(icon);
        trigger.append(arrow);
        trigger.addEventListener('click', function () {
            (onSelect || openCampus)(campus);
        });
        card.append(trigger);
        return card;
    }

    function renderCampuses(campuses) {
        campusGrid.replaceChildren();
        if (!campuses.length) {
            showState(campusGrid, 'No campuses are available for your account.', 'mre-state');
            return;
        }

        campuses.forEach(function (campus) {
            const card = buildCampusCard(campus, `${campus.students_count ?? 0} registered students`);
            campusGrid.append(card);
        });
    }

    function renderDentalCampusCards(campuses) {
        if (!dentalCampusGrid) { return; }
        dentalCampusGrid.replaceChildren();
        if (!campuses.length) {
            showState(dentalCampusGrid, 'No campuses are available for your account.', 'mre-state');
            return;
        }
        campuses.forEach(function (campus) {
            const card = buildCampusCard(
                campus,
                `${campus.dental_records_count ?? 0} dental records`,
                openDentalCampus,
            );
            dentalCampusGrid.append(card);
        });
    }

    function openDentalCampus(campus) {
        if (selectedDentalCampusCard) {
            selectedDentalCampusCard.classList.remove('mre-campus-card--expanded');
            selectedDentalCampusCard.querySelector('.mre-campus-trigger')?.setAttribute('aria-expanded', 'false');
        }
        selectedDentalCampus = campus;
        selectedDentalCampusCard = Array.from(dentalCampusGrid.children).find(card =>
            card.querySelector('.mre-campus-trigger')?.dataset.campusId === String(campus.id),
        );
        if (!selectedDentalCampusCard) { return; }
        Array.from(dentalCampusGrid.children).forEach(card => {
            card.hidden = card !== selectedDentalCampusCard;
        });
        selectedDentalCampusCard.classList.add('mre-campus-card--expanded');
        selectedDentalCampusCard.querySelector('.mre-campus-trigger')?.setAttribute('aria-expanded', 'true');
        dentalCampusTitle.textContent = campus.name;
        dentalRecordSearch.value = '';
        dentalCourseFilter.value = '';
        dentalRecordPage = 1;
        dentalRecordDetail.hidden = true;
        dentalStudentRecordsSection.hidden = true;
        dentalRecordsSection.hidden = false;
        selectedDentalCampusCard.append(dentalRecordsSection, dentalStudentRecordsSection, dentalRecordDetail);
        loadDentalRecords();
    }

    function renderDentalRecords(records, pagination) {
        dentalRecordBody.replaceChildren();
        if (!records.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'no-data';
            cell.textContent = 'No dental records found for this campus.';
            row.append(cell);
            dentalRecordBody.append(row);
            dentalRecordPagination.replaceChildren();
            return;
        }

        records.forEach(record => {
            const row = document.createElement('tr');
            const student = record.student || {};
            const dentist = record.dentist_provider?.name || (record.dentist
                ? [record.dentist.first_name, record.dentist.last_name].filter(Boolean).join(' ')
                : '—');
            [
                record.record_date ? String(record.record_date).slice(0, 10) : '—',
                fullName(student) || '—',
                student.student_id || '—',
                student.department || '—',
                dentist,
            ].forEach(value => addTableCell(row, value));
            const actionCell = document.createElement('td');
            const viewButton = document.createElement('button');
            viewButton.type = 'button';
            viewButton.className = 'uh-btn uh-btn-primary uh-btn-sm';
            viewButton.textContent = 'View';
            viewButton.addEventListener('click', () => openDentalStudentRecords(student));
            actionCell.append(viewButton);
            row.append(actionCell);
            dentalRecordBody.append(row);
        });

        renderPagination(dentalRecordPagination, pagination, page => {
            dentalRecordPage = page;
            loadDentalRecords();
        });
    }

    function openDentalStudentRecords(student) {
        selectedDentalStudent = student;
        dentalStudentRecordPage = 1;
        dentalStudentRecordsTitle.textContent = `${fullName(student)} — Dental Records`;
        dentalRecordsSection.hidden = true;
        dentalStudentRecordsSection.hidden = false;
        dentalRecordDetail.hidden = true;
        selectedDentalCampusCard.append(dentalStudentRecordsSection, dentalRecordDetail);
        loadDentalStudentRecords();
    }

    async function loadDentalStudentRecords() {
        if (!selectedDentalCampus || !selectedDentalStudent?.id) { return; }
        const requestNumber = ++dentalStudentRecordRequest;
        dentalStudentRecordsBody.innerHTML = '<tr><td colspan="4" class="no-data">Loading student dental records…</td></tr>';
        dentalStudentRecordsPagination.replaceChildren();
        const params = new URLSearchParams({
            campus_id: String(selectedDentalCampus.id),
            page: String(dentalStudentRecordPage),
        });

        try {
            const response = await uhGet(
                `${apiRoot}/dental-records/students/${encodeURIComponent(selectedDentalStudent.id)}/records?${params.toString()}`,
            );
            if (requestNumber !== dentalStudentRecordRequest) { return; }
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load this student’s dental records.');
            }
            dentalStudentRecordsTitle.textContent = `${fullName(response.student || selectedDentalStudent)} — Dental Records`;
            renderDentalStudentRecords(response.records || [], response.pagination || {});
        } catch (error) {
            if (requestNumber !== dentalStudentRecordRequest) { return; }
            dentalStudentRecordsBody.innerHTML = '<tr><td colspan="4" class="no-data">The student’s dental records could not be loaded.</td></tr>';
            console.error('Student dental records failed to load:', error);
        }
    }

    function renderDentalStudentRecords(records, pagination) {
        dentalStudentRecordsBody.replaceChildren();
        if (!records.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'no-data';
            cell.textContent = 'No dental records found for this student.';
            row.append(cell);
            dentalStudentRecordsBody.append(row);
            dentalStudentRecordsPagination.replaceChildren();
            return;
        }

        records.forEach(record => {
            const row = document.createElement('tr');
            const dentist = record.dentist_provider?.name || (record.dentist
                ? [record.dentist.first_name, record.dentist.last_name].filter(Boolean).join(' ')
                : '—');
            [
                record.record_date ? String(record.record_date).slice(0, 10) : '—',
                dentist,
                record.oral_exam_findings || '—',
            ].forEach(value => addTableCell(row, value));
            const actions = document.createElement('td');
            const viewButton = document.createElement('button');
            viewButton.type = 'button';
            viewButton.className = 'uh-btn uh-btn-primary uh-btn-sm';
            viewButton.textContent = 'View';
            viewButton.addEventListener('click', () => openDentalRecord(record.id));
            actions.append(viewButton);
            row.append(actions);
            dentalStudentRecordsBody.append(row);
        });

        renderPagination(dentalStudentRecordsPagination, pagination, page => {
            dentalStudentRecordPage = page;
            loadDentalStudentRecords();
        });
    }

    async function loadDentalRecords() {
        if (!selectedDentalCampus) { return; }
        const requestNumber = ++dentalRecordRequest;
        dentalRecordBody.innerHTML = '<tr><td colspan="6" class="no-data">Loading dental records…</td></tr>';
        dentalRecordPagination.replaceChildren();
        const params = new URLSearchParams({
            campus_id: String(selectedDentalCampus.id),
            page: String(dentalRecordPage),
        });
        const search = dentalRecordSearch.value.trim();
        if (search) { params.set('search', search); }
        if (dentalCourseFilter.value) { params.set('course', dentalCourseFilter.value); }

        try {
            const response = await uhGet(`${apiRoot}/dental-records?${params.toString()}`);
            if (requestNumber !== dentalRecordRequest) { return; }
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load campus dental records.');
            }
            updateCourseOptions(dentalCourseFilter, response.course_options);
            renderDentalRecords(response.records || [], response.pagination || {});
        } catch (error) {
            if (requestNumber !== dentalRecordRequest) { return; }
            dentalRecordBody.innerHTML = '<tr><td colspan="6" class="no-data">Dental records could not be loaded. Try again.</td></tr>';
            console.error('Dental records for campus failed:', error);
        }
    }

    function renderDentalRecordDetail(record) {
        if (typeof window.renderDentalRecordReadOnly !== 'function') {
            showState(dentalRecordDetailContent, 'Dental Record form is unavailable. Refresh the page and try again.', 'mre-state mre-state-error');
            return;
        }
        window.renderDentalRecordReadOnly(dentalRecordDetailContent, record);
        dentalRecordDetail.hidden = false;
    }

    async function openDentalRecord(recordId) {
        dentalRecordDetail.hidden = false;
        showState(dentalRecordDetailContent, 'Loading dental record…', 'mre-state');
        try {
            const response = await uhGet(`${apiRoot}/dental-records/${encodeURIComponent(recordId)}`);
            if (!response.ok || !response.success || !response.record) {
                throw new Error(response.message || response.error || 'Could not load this dental record.');
            }
            renderDentalRecordDetail(response.record);
        } catch (error) {
            showState(dentalRecordDetailContent, error.message || 'Dental record could not be loaded.', 'mre-state mre-state-error');
            console.error('Dental record detail failed:', error);
        }
    }

    function backToDentalCampuses() {
        dentalRecordRequest += 1;
        dentalStudentRecordRequest += 1;
        dentalRecordsSection.hidden = true;
        dentalStudentRecordsSection.hidden = true;
        dentalRecordDetail.hidden = true;
        dentalCampusSection.append(dentalRecordsSection, dentalStudentRecordsSection, dentalRecordDetail);
        if (selectedDentalCampusCard) {
            selectedDentalCampusCard.classList.remove('mre-campus-card--expanded');
            selectedDentalCampusCard.querySelector('.mre-campus-trigger')?.setAttribute('aria-expanded', 'false');
            selectedDentalCampusCard = null;
        }
        Array.from(dentalCampusGrid.children).forEach(card => {
            card.hidden = false;
        });
        selectedDentalCampus = null;
        selectedDentalStudent = null;
    }

    async function loadDentalCampuses() {
        try {
            const response = await uhGet(`${apiRoot}/dental-records/campuses`);
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load dental record campuses.');
            }
            renderDentalCampusCards(response.campuses || []);
        } catch (error) {
            showState(dentalCampusGrid, 'Dental record campuses could not be loaded. Refresh the page and try again.', 'mre-state mre-state-error');
            console.error('Dental record campus list failed:', error);
        }
    }

    async function refreshDentalCampusCounts() {
        const response = await uhGet(`${apiRoot}/dental-records/campuses`);
        if (!response.ok || !response.success) {
            throw new Error(response.message || response.error || 'Could not refresh dental record campuses.');
        }
        const campuses = response.campuses || [];
        if (!selectedDentalCampus) {
            renderDentalCampusCards(campuses);
            return;
        }
        campuses.forEach(campus => {
            const card = Array.from(dentalCampusGrid.children).find(item =>
                item.querySelector('.mre-campus-trigger')?.dataset.campusId === String(campus.id),
            );
            if (!card) { return; }
            const countLabel = `${campus.dental_records_count ?? 0} dental records`;
            const trigger = card.querySelector('.mre-campus-trigger');
            const detail = card.querySelector('.mre-card-detail');
            if (detail) { detail.textContent = countLabel; }
            trigger?.setAttribute('aria-label', `${campus.name}, ${countLabel}`);
            if (String(campus.id) === String(selectedDentalCampus.id)) {
                selectedDentalCampus = campus;
            }
        });
    }

    async function loadCampuses() {
        showState(campusGrid, 'Loading campuses…', 'mre-state');
        try {
            const response = await uhGet(`${endpoint}/campuses`);
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load campuses.');
            }
            const campuses = response.campuses || [];
            renderCampuses(campuses);
        } catch (error) {
            showState(campusGrid, 'Campus cards could not be loaded. Refresh the page and try again.', 'mre-state mre-state-error');
            console.error('Medical record campus list failed:', error);
        }
        await loadDentalCampuses();
    }

    function addTableCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = displayValue(value);
        row.append(cell);
    }

    function renderStudents(students, pagination) {
        studentBody.replaceChildren();
        if (!students.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'no-data';
            cell.textContent = 'No registered students found in this campus.';
            row.append(cell);
            studentBody.append(row);
            studentPagination.replaceChildren();
            return;
        }

        students.forEach(function (student) {
            const row = document.createElement('tr');
            addTableCell(row, fullName(student));
            addTableCell(row, student.student_id);
            addTableCell(row, student.age);
            addTableCell(row, [student.department, student.course_year].filter(Boolean).join(' · '));
            addTableCell(row, student.sex);
            addTableCell(row, student.contact);

            const actions = document.createElement('td');
            const viewButton = document.createElement('button');
            viewButton.type = 'button';
            viewButton.className = 'uh-btn uh-btn-primary uh-btn-sm';
            viewButton.textContent = 'View';
            viewButton.addEventListener('click', function () {
                openStudent(student);
            });
            actions.append(viewButton);
            row.append(actions);
            studentBody.append(row);
        });

        renderPagination(studentPagination, pagination, function (page) {
            currentPage = page;
            loadStudents();
        });
    }

    function renderPagination(container, pagination, onPageChange) {
        container.replaceChildren();
        const pages = Number(pagination.pages || 1);
        const pageNumber = Number(pagination.page || currentPage);
        if (pages < 2) { return; }

        addText(container, 'span', 'mre-page-summary', `Page ${pageNumber} of ${pages}`);
        const controls = document.createElement('div');
        controls.className = 'mre-page-controls';
        [
            { label: 'Previous', page: pageNumber - 1, disabled: pageNumber <= 1 },
            { label: 'Next', page: pageNumber + 1, disabled: pageNumber >= pages },
        ].forEach(function (control) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mre-page-button';
            button.textContent = control.label;
            button.disabled = control.disabled;
            button.addEventListener('click', function () {
                onPageChange(control.page);
            });
            controls.append(button);
        });
        container.append(controls);
    }

    async function loadStudents() {
        if (!selectedCampus) { return; }
        const requestNumber = ++studentRequest;
        studentBody.innerHTML = '<tr><td colspan="7" class="no-data">Loading registered students…</td></tr>';
        studentPagination.replaceChildren();
        const params = new URLSearchParams({
            campus_id: String(selectedCampus.id),
            page: String(currentPage),
        });
        const search = studentSearch.value.trim();
        if (search) { params.set('search', search); }
        if (medicalCourseFilter.value) { params.set('course', medicalCourseFilter.value); }

        try {
            const response = await uhGet(`${endpoint}/students?${params.toString()}`);
            if (requestNumber !== studentRequest) { return; }
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load campus students.');
            }
            updateCourseOptions(medicalCourseFilter, response.course_options);
            renderStudents(response.students || [], response.pagination || {});
        } catch (error) {
            if (requestNumber !== studentRequest) { return; }
            studentBody.innerHTML = '<tr><td colspan="7" class="no-data">Students could not be loaded. Try again.</td></tr>';
            console.error('Medical record campus students failed:', error);
        }
    }

    function openCampus(campus) {
        if (selectedCampusCard) {
            selectedCampusCard.classList.remove('mre-campus-card--expanded');
        }
        selectedCampus = campus;
        selectedCampusCard = Array.from(campusGrid.children)
            .find(function (card) {
                return card.querySelector('.mre-campus-trigger')?.dataset.campusId === String(campus.id);
            });
        if (!selectedCampusCard) { return; }
        Array.from(campusGrid.children).forEach(function (card) {
            card.hidden = card !== selectedCampusCard;
        });
        selectedCampusCard.querySelector('.mre-campus-trigger')?.setAttribute('aria-expanded', 'true');
        currentPage = 1;
        studentSearch.value = '';
        medicalCourseFilter.value = '';
        studentTitle.textContent = campus.name;
        selectedCampusCard.classList.add('mre-campus-card--expanded');
        selectedCampusCard.append(studentSection, recordDetailPanel, formPanel);
        studentSection.hidden = false;
        recordDetailPanel.hidden = true;
        formPanel.hidden = true;
        loadStudents();
    }

    function renderStudentProfile(container, student) {
        container.replaceChildren();
        addText(container, 'h3', 'mre-student-name', fullName(student));
        const details = document.createElement('div');
        details.className = 'mre-student-grid';
        let age = student.age;
        if ((age === null || age === undefined || age === '') && student.date_of_birth) {
            const birthDate = new Date(`${String(student.date_of_birth).slice(0, 10)}T00:00:00`);
            const today = new Date();
            age = today.getFullYear() - birthDate.getFullYear();
            if (today.getMonth() < birthDate.getMonth()
                || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
                age -= 1;
            }
        }
        [
            ['Campus', student.campus?.name || selectedCampus?.name],
            ['Student ID', student.student_id],
            ['Date of Birth', student.date_of_birth ? String(student.date_of_birth).slice(0, 10) : null],
            ['Age', age],
            ['Course / Year', [student.department, student.course_year].filter(Boolean).join(' · ')],
            ['Gender', student.sex],
            ['Address', student.address],
            ['Contact Number', student.contact],
        ].forEach(function (field) {
            const item = document.createElement('div');
            item.className = 'mre-profile-field';
            addText(item, 'span', '', field[0]);
            addText(item, 'strong', '', field[1]);
            details.append(item);
        });
        container.append(details);
    }

    function openStudent(student) {
        selectedStudent = student;
        visitPage = 1;
        renderStudentProfile(recordStudentProfile, student);
        studentSection.hidden = true;
        recordDetailPanel.hidden = false;
        formPanel.hidden = true;
        loadStudentRecords();
    }

    function openCreateRecord() {
        if (!selectedStudent) { return; }
        studentId.value = selectedStudent.id;
        setVisitDateAndTime();
        renderStudentProfile(studentPreview, selectedStudent);
        recordDetailPanel.hidden = true;
        formPanel.hidden = false;
        message.textContent = '';
        document.getElementById('mre-reason').focus();
    }

    function addPaperRow(parent, label, value) {
        const row = document.createElement('div');
        row.className = 'mre-paper-row';
        addText(row, 'span', 'mre-paper-label', label);
        const valueElement = addText(row, 'span', 'mre-paper-value', '');
        valueElement.textContent = value === null || value === undefined ? '' : String(value);
        parent.append(row);
    }

    function addPaperNote(parent, className, value) {
        const note = document.createElement('p');
        note.className = className;
        note.textContent = value === null || value === undefined || value === '' ? '' : String(value);
        parent.append(note);
    }

    function staffDisplay(staff) {
        if (!staff) { return null; }
        const roles = {
            nurse: 'Nurse',
            nurse_head: 'Nurse Head',
            physician: 'Physician',
            dentist: 'Dentist',
        };
        const roleLabel = roles[staff.role];
        if (!roleLabel) { return null; }
        const name = [staff.first_name, staff.last_name].filter(Boolean).join(' ');
        return [roleLabel, name].filter(Boolean).join(': ');
    }

    function renderVisitForm(records) {
        const form = document.createElement('article');
        form.className = 'mre-paper-form';

        const formHeader = document.createElement('header');
        formHeader.className = 'mre-paper-heading';
        const identity = document.createElement('div');
        identity.className = 'mre-paper-identity';
        const logo = document.createElement('img');
        logo.src = page.dataset.clinicLogo;
        logo.alt = 'SUNN logo';
        identity.append(logo);
        const institution = document.createElement('div');
        addText(institution, 'p', 'mre-paper-republic', 'Republic of the Philippines');
        addText(institution, 'p', 'mre-paper-university', 'State University of Northern Negros');
        addText(institution, 'p', 'mre-paper-office', 'OFFICE OF THE MEDICAL AND DENTAL SERVICES');
        addText(institution, 'p', 'mre-paper-address', 'Sagay City, Negros Occidental, Philippines 6122');
        identity.append(institution);
        formHeader.append(identity);
        form.append(formHeader);

        addText(form, 'h2', 'mre-paper-title', 'Medical Record');

        const studentInfo = document.createElement('section');
        studentInfo.className = 'mre-paper-student-info';
        const nameRow = document.createElement('div');
        nameRow.className = 'mre-paper-info-row mre-paper-name-row';
        addPaperRow(nameRow, 'Last name', selectedStudent.last_name);
        addPaperRow(nameRow, 'First name', selectedStudent.first_name);
        addPaperRow(nameRow, 'Middle name', selectedStudent.middle_name);
        addPaperRow(nameRow, 'Birth Date:', selectedStudent.date_of_birth ? String(selectedStudent.date_of_birth).slice(0, 10) : null);
        let age = selectedStudent.age;
        if ((age === null || age === undefined || age === '') && selectedStudent.date_of_birth) {
            const birthDate = new Date(`${String(selectedStudent.date_of_birth).slice(0, 10)}T00:00:00`);
            const today = new Date();
            age = today.getFullYear() - birthDate.getFullYear();
            if (today.getMonth() < birthDate.getMonth()
                || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
                age -= 1;
            }
        }
        addPaperRow(nameRow, 'Age:', age);
        studentInfo.append(nameRow);

        const courseRow = document.createElement('div');
        courseRow.className = 'mre-paper-info-row';
        addPaperRow(courseRow, 'Course:', [selectedStudent.department, selectedStudent.course_year].filter(Boolean).join(' / '));
        addPaperRow(courseRow, 'Gender:', selectedStudent.sex);
        addPaperRow(courseRow, 'Address:', selectedStudent.address);
        addPaperRow(courseRow, 'Contact #:', selectedStudent.contact);
        addPaperRow(courseRow, 'Campus:', selectedStudent.campus?.name);
        studentInfo.append(courseRow);
        form.append(studentInfo);

        const visitColumns = document.createElement('section');
        visitColumns.className = 'mre-paper-visit-columns';

        [
            'Date / Time',
            'Vital Signs',
            'Chief Complaint',
            'Treatment',
            'Nurse’s Notes / Remarks',
        ].forEach(function (title) {
            addText(visitColumns, 'h3', 'mre-paper-column-title', title);
        });

        if (!records.length) {
            const emptyDate = document.createElement('div');
            emptyDate.className = 'mre-paper-visit-cell';
            addPaperNote(emptyDate, 'mre-paper-note', null);

            const emptyVitals = document.createElement('div');
            emptyVitals.className = 'mre-paper-visit-cell';
            [
                ['BP:', null],
                ['Temp.:', null],
                ['PR:', null],
                ['RR:', null],
            ].forEach(function (vital) {
                addPaperRow(emptyVitals, vital[0], vital[1]);
            });
            addText(emptyVitals, 'p', 'mre-paper-empty-note', 'No clinic visits or consultation requests recorded.');

            [emptyDate, emptyVitals].forEach(function (cell) {
                visitColumns.append(cell);
            });
            for (let column = 0; column < 3; column += 1) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'mre-paper-visit-cell';
                addPaperNote(emptyCell, 'mre-paper-note', null);
                visitColumns.append(emptyCell);
            }
        } else {
            records.forEach(function (record) {
                const dentalRecord = record.dental_record || null;
                const dateColumn = document.createElement('div');
                dateColumn.className = 'mre-paper-visit-cell';
                addText(
                    dateColumn,
                    'strong',
                    'mre-paper-record-type',
                    dentalRecord
                        ? 'Dental visit'
                        : record.record_type === 'consultation'
                        ? `Consultation · ${String(record.notes || '').split('\n')[0].replace(/^Consultation status:\s*/, '')}`
                        : record.consultation_status
                            ? `Clinic visit · Consultation ${record.consultation_status}`
                            : 'Clinic visit'
                );
                const recordedTime = record.visit_time
                    ? uhFormatTime(record.visit_time)
                    : (record.created_at ? uhFormatTime(record.created_at) : '');
                const dateTime = [
                    record.visit_date ? String(record.visit_date).slice(0, 10) : 'Date not recorded',
                    recordedTime || 'Time not recorded',
                ].join('\n');
                addPaperNote(dateColumn, 'mre-paper-note', dateTime);

                const vitalsColumn = document.createElement('div');
                vitalsColumn.className = 'mre-paper-visit-cell mre-paper-vitals-column';
                [
                    ['BP:', record.bp],
                    ['Temp.:', record.temp === null || record.temp === undefined || record.temp === '' ? null : `${record.temp} °C`],
                    ['PR:', record.pulse],
                    ['RR:', record.rr],
                ].forEach(function (vital) {
                    addPaperRow(vitalsColumn, vital[0], vital[1]);
                });

                const complaintColumn = document.createElement('div');
                complaintColumn.className = 'mre-paper-visit-cell';
                addPaperNote(complaintColumn, 'mre-paper-note', record.reason);

                const treatmentColumn = document.createElement('div');
                treatmentColumn.className = 'mre-paper-visit-cell';
                addPaperNote(treatmentColumn, 'mre-paper-note', record.treatment);

                const remarksColumn = document.createElement('div');
                remarksColumn.className = 'mre-paper-visit-cell';
                const toothChart = dentalRecord?.tooth_chart && typeof dentalRecord.tooth_chart === 'object'
                    ? Object.entries(dentalRecord.tooth_chart)
                        .map(function ([tooth, condition]) { return `${tooth}: ${condition}`; })
                        .join(', ')
                    : '';
                const dentalDetails = dentalRecord
                    ? [
                        dentalRecord.dental_history && `Dental history: ${dentalRecord.dental_history}`,
                        dentalRecord.oral_exam_findings && `Oral examination: ${dentalRecord.oral_exam_findings}`,
                        dentalRecord.treatment_plan && `Treatment plan: ${dentalRecord.treatment_plan}`,
                        dentalRecord.next_appointment && `Next appointment: ${dentalRecord.next_appointment}`,
                        toothChart && `Tooth chart: ${toothChart}`,
                    ].filter(Boolean).join('\n')
                    : '';
                const remarks = [record.notes, dentalDetails].filter(Boolean).join('\n');
                addPaperNote(remarksColumn, 'mre-paper-note', remarks);
                if (String(remarks || '').trim()) {
                    const noteAuthors = [
                        staffDisplay(record.nurse),
                        staffDisplay(record.physician),
                    ].filter(Boolean).join(' · ');
                    if (noteAuthors) {
                        addPaperRow(remarksColumn, 'By:', noteAuthors);
                    }
                }

                if (role === 'nurse_head' && record.record_type === 'visit') {
                    const actions = document.createElement('div');
                    actions.className = 'mre-paper-actions';
                    actions.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;';

                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'uh-btn uh-btn-ghost uh-btn-xs';
                    editButton.textContent = 'Edit';
                    editButton.addEventListener('click', function () {
                        if (typeof window.nhOpenMedEditForm === 'function') {
                            window.nhOpenMedEditForm(record.id);
                        } else {
                            window.uhToast('Medical record editing is unavailable.', 'error');
                        }
                    });

                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'uh-btn uh-btn-ghost uh-btn-xs';
                    deleteButton.textContent = 'Delete';
                    deleteButton.style.cssText = 'color:#dc2626;border-color:#dc2626;';
                    deleteButton.addEventListener('click', function () {
                        if (typeof window.nhDeleteMedicalRecord === 'function') {
                            window.nhDeleteMedicalRecord(record.id);
                        } else {
                            window.uhToast('Medical record deletion is unavailable.', 'error');
                        }
                    });

                    actions.append(editButton, deleteButton);
                    dateColumn.append(actions);
                }

                visitColumns.append(dateColumn, vitalsColumn, complaintColumn, treatmentColumn, remarksColumn);
            });
        }

        form.append(visitColumns);
        return form;
    }

    function renderVisits(records, pagination) {
        visitList.replaceChildren();
        visitList.append(renderVisitForm(records));

        renderPagination(visitPagination, pagination, function (page) {
            visitPage = page;
            loadStudentRecords();
        });
    }

    async function loadStudentRecords() {
        if (!selectedStudent) { return; }
        const requestNumber = ++visitRequest;
        showState(visitList, 'Loading visit history…', 'mre-state');
        visitPagination.replaceChildren();

        try {
            const response = await uhGet(`${endpoint}/students/${encodeURIComponent(selectedStudent.id)}/records?page=${visitPage}`);
            if (requestNumber !== visitRequest) { return; }
            if (!response.ok || !response.success) {
                throw new Error(response.message || response.error || 'Could not load this student’s medical record.');
            }
            selectedStudent = response.student || selectedStudent;
            renderStudentProfile(recordStudentProfile, selectedStudent);
            renderVisits(response.records || [], response.pagination || {});
        } catch (error) {
            if (requestNumber !== visitRequest) { return; }
            showState(visitList, 'This student’s medical record could not be loaded. Refresh the page and try again.', 'mre-state mre-state-error');
            console.error('Medical record student history failed:', error);
        }
    }

    function backToCampusCards() {
        studentRequest += 1;
        visitRequest += 1;
        selectedCampus = null;
        selectedStudent = null;
        studentSection.hidden = true;
        recordDetailPanel.hidden = true;
        formPanel.hidden = true;
        if (selectedCampusCard) {
            selectedCampusCard.querySelector('.mre-campus-trigger')?.setAttribute('aria-expanded', 'false');
            selectedCampusCard.classList.remove('mre-campus-card--expanded');
            selectedCampusCard = null;
        }
        Array.from(campusGrid.children).forEach(function (card) {
            card.hidden = false;
        });
        campusGrid.append(studentSection, recordDetailPanel, formPanel);
    }

    function backToStudentList() {
        visitRequest += 1;
        recordDetailPanel.hidden = true;
        formPanel.hidden = true;
        studentSection.hidden = false;
    }

    function backToStudentRecord() {
        formPanel.hidden = true;
        recordDetailPanel.hidden = false;
        message.textContent = '';
    }

    studentSearch.addEventListener('input', function () {
        window.clearTimeout(searchTimer);
        currentPage = 1;
        searchTimer = window.setTimeout(loadStudents, 250);
    });
    medicalCourseFilter.addEventListener('change', function () {
        currentPage = 1;
        loadStudents();
    });

    document.getElementById('mre-back-campuses').addEventListener('click', backToCampusCards);
    document.getElementById('mre-detail-back-students').addEventListener('click', backToStudentList);
    document.getElementById('mre-back-record').addEventListener('click', backToStudentRecord);
    document.getElementById('mre-add-record').addEventListener('click', openCreateRecord);
    document.getElementById('dre-back-campuses').addEventListener('click', backToDentalCampuses);
    document.getElementById('dre-detail-back').addEventListener('click', function () {
        dentalRecordDetail.hidden = true;
        dentalStudentRecordsSection.hidden = !selectedDentalStudent;
        dentalRecordsSection.hidden = Boolean(selectedDentalStudent);
    });
    document.getElementById('dre-student-records-back').addEventListener('click', function () {
        dentalStudentRecordRequest += 1;
        selectedDentalStudent = null;
        dentalStudentRecordsSection.hidden = true;
        dentalRecordDetail.hidden = true;
        dentalRecordsSection.hidden = false;
    });
    dentalRecordSearch.addEventListener('input', function () {
        window.clearTimeout(dentalSearchTimer);
        dentalRecordPage = 1;
        dentalSearchTimer = window.setTimeout(loadDentalRecords, 250);
    });
    dentalCourseFilter.addEventListener('change', function () {
        dentalRecordPage = 1;
        loadDentalRecords();
    });
    window.addEventListener('clinic:dental-record-saved', function (event) {
        refreshDentalCampusCounts().catch(error => {
            console.error('Dental record campus counts could not be refreshed:', error);
        });
        if (selectedDentalCampus && Number(event.detail?.record?.campus_id) === Number(selectedDentalCampus.id)) {
            dentalRecordPage = 1;
            loadDentalRecords();
        }
    });
    window.addEventListener('clinic:medical-records-changed', function () {
        if (selectedStudent && !recordDetailPanel.hidden) {
            loadStudentRecords();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        message.classList.remove('is-success');
        message.textContent = '';
        if (!selectedStudent) {
            message.textContent = 'Select a student card before saving this record.';
            return;
        }

        const payload = Object.fromEntries(new FormData(form).entries());
        ['temp', 'pulse', 'rr'].forEach(function (field) {
            if (payload[field] === '') { payload[field] = null; }
        });
        ['treatment', 'notes', 'bp'].forEach(function (field) {
            if (payload[field] === '') { payload[field] = null; }
        });

        submitButton.disabled = true;
        try {
            const response = await uhPost(endpoint, payload);
            if (!response.ok || !response.success) {
                const firstValidationError = response.errors ? Object.values(response.errors).flat()[0] : null;
                throw new Error(firstValidationError || response.message || response.error || 'The medical record could not be saved.');
            }
            message.classList.add('is-success');
            message.textContent = response.message || 'Medical record saved.';
            uhToast(response.message || 'Medical record saved.', 'success');
            form.reset();
            studentId.value = selectedStudent.id;
            setVisitDateAndTime();
            visitPage = 1;
            recordDetailPanel.hidden = false;
            formPanel.hidden = true;
            loadStudentRecords();
        } catch (error) {
            message.textContent = error.message || 'The medical record could not be saved.';
            uhToast(message.textContent, 'error');
            console.error('Medical record save failed:', error);
        } finally {
            submitButton.disabled = false;
        }
    });

    form.addEventListener('reset', function () {
        message.classList.remove('is-success');
        message.textContent = '';
        window.setTimeout(function () {
            studentId.value = selectedStudent?.id || '';
            setVisitDateAndTime();
        }, 0);
    });

    function setVisitDateAndTime() {
        const now = new Date();
        document.getElementById('mre-visit-date').value = [
            now.getFullYear(),
            String(now.getMonth() + 1).padStart(2, '0'),
            String(now.getDate()).padStart(2, '0'),
        ].join('-');
        document.getElementById('mre-visit-time').value = [
            String(now.getHours()).padStart(2, '0'),
            String(now.getMinutes()).padStart(2, '0'),
        ].join(':');
    }

    setVisitDateAndTime();
    loadCampuses();
}());
