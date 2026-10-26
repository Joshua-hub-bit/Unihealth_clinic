'use strict';

(function () {
    const adultUpper = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
    const adultLower = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];
    const primaryUpper = [55, 54, 53, 52, 51, 61, 62, 63, 64, 65];
    const primaryLower = [85, 84, 83, 82, 81, 71, 72, 73, 74, 75];
    const oralConditions = [
        'Gingivitis',
        'Malocclusion',
        'Buccal ulcer',
        'Calculus',
        'Plaque',
        'Root Fragments',
        'Pharyngitis',
    ];
    function val(id) {
        return document.getElementById(id)?.value || '';
    }

    function dentalRecordHeader() {
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

    function dentalRecordFooter() {
        return '<div class="dc-footer">' +
            '<div class="dc-tagline">THE FUTURE SHINES BRIGHTEST</div>' +
            '<div class="dc-footer-row">' +
                '<div class="dc-contact"><i class="fa fa-phone"></i> (034) 703 8802<br><i class="fa fa-envelope"></i> info@sunn.edu.ph<br><i class="fa fa-globe"></i> www.sunn.edu.ph</div>' +
                '<img class="dc-logos" src="/clinic/images/footer.png" alt="Accreditation badges"/>' +
            '</div>' +
        '</div>';
    }

    function safe(value) {
        return esc(value);
    }

    function editable(label, id, value, options = {}) {
        return `<span class="dr-field ${options.className || ''}" style="${options.style || ''}">
            ${label ? `<strong>${label}</strong>` : ''}
            <span class="dr-editable"${options.readOnly ? '' : ` contenteditable="true" role="textbox" aria-label="${label || options.ariaLabel || 'Form field'}" data-placeholder="${options.placeholder || ''}"`}>${safe(value)}</span>
        </span>`;
    }

    function toothCell(number, kind, rowTypes, columnSpan = 1) {
        return `<div class="dr-tooth" style="grid-column:span ${columnSpan}">
            ${rowTypes.map((rowType, index) => {
                if (rowType === 'number') {
                    return `<span class="dr-tooth-number">${number}</span>`;
                }
                if (rowType === 'mark') {
                    const markIndex = rowTypes.slice(0, index).filter(type => type === 'mark').length + 1;
                    return `<button type="button" class="dr-tooth-mark" data-tooth="${kind}-${number}-${markIndex}" aria-label="Mark tooth ${number}, condition row ${markIndex}" aria-pressed="false"><span class="dr-tooth-mark-symbol" aria-hidden="true"></span></button>`;
                }
                const conditionIndex = rowTypes.slice(0, index).filter(type => type === 'condition').length + 1;
                return `<button type="button" class="dr-condition-cell" data-tooth="${kind}-${number}-condition-${conditionIndex}" aria-label="Mark tooth ${number}, condition box ${conditionIndex}" aria-pressed="false"></button>`;
            }).join('')}
        </div>`;
    }

    function toothChartRow(numbers, kind, rowTypes, options = {}) {
        const start = options.startColumn ?? (options.centered ? 4 : 0);
        const teeth = [];
        let toothIndex = 0;
        let column = 0;
        while (column < 18) {
            if (options.centered && column < start) {
                teeth.push('<span class="dr-tooth-spacer" aria-hidden="true"></span>');
                column += 1;
                continue;
            }
            const number = numbers[toothIndex];
            if (number) {
                const wideLastTooth = options.wideLastTooth && toothIndex === numbers.length - 1;
                const span = wideLastTooth ? 3 : 1;
                teeth.push(toothCell(number, kind, rowTypes, span));
                toothIndex += 1;
                column += span;
                continue;
            }
            if (options.centered) {
                teeth.push('<span class="dr-tooth-spacer" aria-hidden="true"></span>');
            } else {
                teeth.push(`<span class="dr-tooth">${rowTypes.map((rowType, index) => rowType === 'condition'
                    ? `<button type="button" class="dr-condition-cell" data-tooth="${kind}-blank-${column}-condition-${index + 1}" aria-label="Mark empty condition box, column ${column + 1}, row ${index + 1}" aria-pressed="false"></button>`
                    : '<span class="dr-condition-cell dr-condition-cell-empty" aria-hidden="true"></span>'
                ).join('')}</span>`);
            }
            column += 1;
        }
        const toothMarkup = teeth.join('');
        const labelGroupCount = options.leftLabelGroupCount || 0;
        const labelGroupStart = options.leftLabelGroupStart || 0;
        const labelGroupLabelStart = options.leftLabelGroupLabelStart || 0;
        const labelGroupLeft = options.leftLabelGroupLeft || 'calc(8.3333% + 5.0833mm)';
        const rightLabelGroupCount = options.rightLabelGroupCount || 0;
        const rightLabelGroupStart = options.rightLabelGroupStart || 0;
        const sideLabels = (labels, className = '') => {
            return `<span class="dr-side-label ${className}" style="--dr-label-rows:${rowTypes.length}" aria-hidden="true">
                ${rowTypes.map((_, index) => {
                    const grouped = !className && index >= labelGroupLabelStart && index < labelGroupLabelStart + labelGroupCount;
                    const rightGrouped = className && index >= rightLabelGroupStart && index < rightLabelGroupStart + rightLabelGroupCount;
                    return `<span class="dr-side-label-line">${grouped || rightGrouped ? '' : labels[index] || ''}</span>`;
                }).join('')}
            </span>`;
        };
        const labelGroup = labelGroupCount
            ? `<span class="dr-side-label-group" style="--dr-label-group-start:${labelGroupStart};--dr-label-group-left:${labelGroupLeft}" aria-hidden="true">
                ${options.leftLabels.slice(labelGroupLabelStart, labelGroupLabelStart + labelGroupCount).map(label =>
                    `<span class="dr-side-label-line">${label || ''}</span>`
                ).join('')}
            </span>`
            : '';
        const rightLabelGroup = rightLabelGroupCount
            ? `<span class="dr-side-label-group dr-side-label-group-right ${options.rightLabelGroupBox ? 'dr-side-label-group-right-boxed' : ''}" style="--dr-label-group-start:${rightLabelGroupStart};--dr-label-group-count:${rightLabelGroupCount};--dr-right-label-group-left:${options.rightLabelGroupLeft || 'auto'};--dr-right-label-group-right:${options.rightLabelGroupRight || '0'}" aria-hidden="true">
                ${options.rightLabels.slice(rightLabelGroupStart, rightLabelGroupStart + rightLabelGroupCount).map((label, index) => {
                    const inlineEndLabel = options.rightLabelGroupInlineEnd && index === rightLabelGroupCount - 1;
                    return inlineEndLabel
                        ? `<span class="dr-side-label-line dr-side-label-line-inline"><span>${label || ''}</span><span>${options.rightLabelGroupInlineEnd}</span></span>`
                        : `<span class="dr-side-label-line">${label || ''}</span>`;
                }).join('')}
            </span>`
            : '';
        return `<div class="dr-tooth-chart-row">
            ${labelGroup}
            ${rightLabelGroup}
            ${sideLabels(options.leftLabels || [])}
            <div class="dr-tooth-cells">${toothMarkup}</div>
            ${sideLabels(options.rightLabels || [], 'dr-side-label-right')}
        </div>`;
    }

    function conditionTreatmentChart() {
        return `<div class="dr-tooth-chart" role="group" aria-label="Condition and treatment needs tooth chart">
            ${toothChartRow(primaryUpper, 'primary-upper', ['condition', 'number', 'mark'], { centered: true, startColumn: 3, leftLabels: ['condition', 'right'], leftLabelGroupCount: 2, rightLabels: ['left', 'temporary', 'teeth'], rightLabelGroupCount: 3, rightLabelGroupStart: 0, rightLabelGroupLeft: 'calc(75% - 0.25mm)' })}
            ${toothChartRow(adultUpper, 'permanent-upper', ['condition', 'condition', 'number', 'mark', 'mark'], { wideLastTooth: true, rightLabels: ['', '', ''] })}
            ${toothChartRow(adultLower, 'permanent-lower', ['number', 'condition', 'condition'], { wideLastTooth: true, rightLabels: [''] })}
            ${toothChartRow(primaryLower, 'primary-lower', ['mark', 'number', 'condition'], { centered: true, startColumn: 3, leftLabels: ['right', 'condition'], leftLabelGroupStart: 1, leftLabelGroupCount: 2, leftLabelGroupLeft: 'calc(8.3333% + 5.0833mm)', rightLabels: ['temporary', 'teeth', 'left'], rightLabelGroupCount: 3, rightLabelGroupBox: true, rightLabelGroupLeft: 'calc(75% - 0.25mm)' })}
        </div>`;
    }

    function oralHealthTable() {
        return `<table class="dr-oral-table">
            <thead><tr><th>Oral Health Condition</th>${[1, 2, 3, 4].map(number => `<th>${number}</th>`).join('')}</tr></thead>
            <tbody>${oralConditions.map(condition => `<tr>
                <th>${condition}</th>
                ${[1, 2, 3, 4].map(number => `<td><button type="button" class="dr-oral-mark" data-condition="${condition}" data-quadrant="${number}" aria-label="Mark ${condition}, section ${number}" aria-pressed="false"></button></td>`).join('')}
            </tr>`).join('')}</tbody>
        </table>`;
    }

    function styles() {
        return `<style>
            .dr-sheet { box-sizing:border-box;width:210mm;height:297mm;padding:19mm;background:#fff;color:#111;font:9pt Arial,sans-serif;display:flex;flex-direction:column; }
            .dr-sheet .dc-header { display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm; }
            .dr-sheet .dc-header img { width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center; }
            .dr-sheet .dc-ht { line-height:1.3;font-family:Arial,sans-serif; }
            .dr-sheet .dc-republic { color:#003C8F;font-size:8pt;font-weight:600; }
            .dr-sheet .dc-university { color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:.3px;text-transform:uppercase;white-space:nowrap; }
            .dr-sheet .dc-office { color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap; }
            .dr-sheet .dc-address { color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5; }
            .dr-title { text-align:center;font-size:13pt;margin:4px 0 7px;letter-spacing:.4px; }
            .dr-student { padding:0 0 2mm;margin-bottom:4mm; }
            .dr-student-row { display:flex;gap:4mm;margin:1.5mm 0; }
            .dr-student-label { flex:0 0 auto;font-size:8pt;align-self:baseline; }
            .dr-field { display:inline-flex;align-items:baseline;gap:4px;min-width:0;flex:1; }
            .dr-field strong { white-space:nowrap;font-size:8pt; }
            .dr-editable { display:inline-block;min-width:20px;min-height:5mm;flex:1;border-bottom:0.7pt solid #333;padding:0 2px;overflow-wrap:anywhere; }
            .dr-editable:empty::before { content:attr(data-placeholder);color:#888; }
            .dr-editable:focus { outline:1px solid #4b86b4;outline-offset:1px;background:#f5faff; }
            .dr-exam-heading { display:flex;align-items:center;justify-content:space-between;margin-bottom:2mm; }
            .dr-exam-heading .dr-section-title { flex:1;margin:0;text-align:center; }
            .dr-date-line { display:flex;flex:0 0 58mm;justify-content:flex-end;border:0.6pt solid #555;padding:1mm 1.5mm; }
            .dr-date-line .dr-field { width:100%;flex-direction:column;align-items:stretch;gap:0.5mm; }
            .dr-date-line .dr-editable { max-width:none;min-height:4mm; }
            .dr-exam { display:grid;grid-template-columns:minmax(0,3.2fr) minmax(0,1fr);gap:3mm;align-items:start; }
            .dr-chart,.dr-oral { min-width:0; }
            .dr-section-title { font-weight:700;text-align:center;text-transform:uppercase;font-size:7pt;margin:0 0 1.5mm; }
            .dr-subsection-title { font-size:6.5pt;text-align:center;font-weight:700;margin:1.5mm 0; }
            .dr-tooth-chart { display:grid;gap:0; }
            .dr-tooth-chart-row { position:relative;display:grid;grid-template-columns:14mm minmax(0,1fr) 9mm;align-items:stretch; }
            .dr-side-label { display:grid;grid-template-rows:repeat(var(--dr-label-rows),4.5mm);min-width:0;padding:0;text-align:center;font-size:5.5pt;line-height:1.05;color:#333; }
            .dr-side-label-line { display:flex;min-width:0;min-height:0;align-items:center;justify-content:flex-end;overflow:visible;padding-right:1mm;text-align:right; }
            .dr-side-label-right .dr-side-label-line { justify-content:flex-start;padding:0 0 0 1mm;text-align:left; }
            .dr-side-label-group { position:absolute;top:calc(var(--dr-label-group-start,0) * 4.5mm);left:var(--dr-label-group-left,calc(8.3333% + 5.0833mm));box-sizing:border-box;display:grid;grid-template-rows:repeat(2,4.5mm);width:14mm;min-width:0;text-align:left; }
            .dr-side-label-group .dr-side-label-line { justify-content:flex-start;padding:0 0.7mm;text-align:left; }
            .dr-side-label-group-right { top:calc(var(--dr-label-group-start,0) * 4.5mm);right:var(--dr-right-label-group-right,0);left:var(--dr-right-label-group-left,auto);width:14mm;grid-template-rows:repeat(var(--dr-label-group-count),4.5mm);border:0;text-align:left; }
            .dr-side-label-group-right-boxed { box-sizing:border-box;border:0; }
            .dr-side-label-group-right .dr-side-label-line { justify-content:flex-start;padding:0 0.7mm;text-align:left; }
            .dr-side-label-group-right .dr-side-label-line-inline { justify-content:space-between;gap:1mm; }
            .dr-tooth-cells { display:grid;grid-template-columns:repeat(18,minmax(0,1fr));min-width:0; }
            .dr-tooth-spacer { min-width:0; }
            .dr-tooth { display:flex;min-width:0;flex-direction:column;align-items:stretch;margin:0 -0.25pt -0.25pt 0; }
            .dr-condition-cell,.dr-tooth-number,.dr-tooth-mark { display:flex;box-sizing:border-box;width:100%;height:4.5mm;min-height:4.5mm;align-items:center;justify-content:center;border:0.5pt solid #555;margin:0; }
            button.dr-condition-cell { padding:0;background:#fff;color:#111;font:inherit;cursor:pointer; }
            button.dr-condition-cell[aria-pressed="true"]::after { content:"✓";font-size:7pt;line-height:1; }
            button.dr-condition-cell:focus-visible { outline:1px solid #4b86b4;outline-offset:1px;z-index:1; }
            .dr-condition-cell-empty { background:transparent; }
            .dr-tooth-number { font-size:5.5pt;line-height:1.1; }
            .dr-tooth-mark { padding:0;border-radius:0;background:#fff;color:#111;cursor:pointer; }
            .dr-tooth-mark-symbol { position:relative;width:3.2mm;height:3.2mm;border:0.45pt solid #333;border-radius:50%; }
            .dr-tooth-mark-symbol::after { position:absolute;inset:0.65mm;border:0.4pt solid #333;border-radius:50%;content:""; }
            .dr-tooth-mark[aria-pressed="true"] .dr-tooth-mark-symbol::after { background:#111; }
            .dr-print-check { color:#000;font-family:Arial,sans-serif;font-size:9pt;font-weight:900;line-height:1;print-color-adjust:exact;-webkit-print-color-adjust:exact; }
            .dr-oral-table { width:100%;border-collapse:collapse;font-size:6pt; }
            .dr-oral-table th,.dr-oral-table td { border:0.6pt solid #555;padding:1mm 1.2mm; }
            .dr-oral-table thead th { text-align:center; }
            .dr-oral-table th:first-child { text-align:left; }
            .dr-oral-mark { display:block;width:5mm;height:4mm;margin:auto;border:0;background:#fff;cursor:pointer; }
            .dr-oral-mark[aria-pressed="true"]::after { content:"✓";font-size:8pt;line-height:4mm; }
            .dr-findings { margin-top:7mm;flex:1;min-height:25mm;display:flex;flex-direction:column; }
            .dr-findings-title { font-size:8pt;font-weight:700;margin-bottom:1mm; }
            .dr-findings .dr-editable { display:block;box-sizing:border-box;flex:0 0 5mm;height:5mm;min-height:5mm;margin:0;padding:0 2px;font-size:8pt;line-height:4.5mm;white-space:nowrap;overflow:hidden;border:0;background-image:linear-gradient(to bottom,transparent calc(100% - 0.6pt),#777 0);background-size:100% 100%;background-position:0 0;background-repeat:no-repeat; }
            .dr-findings .dr-editable:focus { outline:none;background-color:transparent; }
            .dr-signature { display:flex;justify-content:flex-end;margin:4mm 5mm 2mm; }
            .dr-signature .dr-field { position:relative;flex:0 0 55mm;flex-direction:column;text-align:center; }
            .dr-signature .dr-editable { flex:none;width:100%;min-height:7mm;border-bottom:0; }
            .dr-signature .dr-dentist-select { flex:none;width:100%;height:8mm;padding:0 1mm 1mm;border:0;border-bottom:1pt solid #333;border-radius:0;background:transparent;text-align:center;text-align-last:center;font:8pt Arial,sans-serif; }
            .dr-signature small { display:block;width:100%;font-size:7pt;text-align:left; }
            .dr-license { display:flex;align-items:baseline;gap:2mm;width:100%;font-size:7pt;text-align:left; }
            .dr-license .dr-editable { flex:1;width:auto;min-width:0;min-height:4mm;border-bottom:0.7pt solid #333; }
            .dr-sheet .dc-footer { margin-top:3mm;padding-top:0; }
            .dr-sheet .dc-tagline { text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:8pt;letter-spacing:.3px;margin-bottom:1.5mm;line-height:1; }
            .dr-sheet .dc-footer-row { display:flex;align-items:center;justify-content:space-between;width:100%;gap:3mm; }
            .dr-sheet .dc-contact { font-size:7pt;color:#003C8F;font-weight:600;line-height:1.5;flex-shrink:0; }
            .dr-sheet .dc-contact i { display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F; }
            .dr-sheet .dc-logos { flex:1;height:13mm;max-height:13mm;object-fit:fill; }
            @media print {
                .dr-editable:focus { outline:none;background:transparent; }
                .dr-tooth-mark,.dr-condition-cell,.dr-oral-mark { cursor:default; }
            }
        </style>`;
    }

    function dentalRecordMarkup(data, readOnly) {
        const dentistSignature = readOnly
            ? `<span class="dr-dentist-print-name">${safe(data.dentistName || '')}</span>`
            : `<select id="dr_dentist_name" class="dr-dentist-select" data-signing-role="dentist" aria-label="Choose dentist" onchange="updateSigningPersonnelPreview(this)" disabled>
                <option value="">Loading dentists...</option>
            </select>`;
        const dentistLicense = readOnly
            ? `<span class="dr-editable">${safe(data.dentistLicense || '')}</span>`
            : `<input id="dr_dentist_license" class="dr-editable" type="text" aria-label="Dentist license number" readonly value="${safe(data.dentistLicense || '')}">`;

        return `<article class="dr-sheet">
                ${dentalRecordHeader()}
                <h1 class="dr-title">DENTAL RECORD</h1>
                <section class="dr-student">
                    <div class="dr-student-row">
                        <strong class="dr-student-label">NAME:</strong>
                        ${editable('LAST:', 'dr-last-name', data.lastName, { readOnly })}
                        ${editable('FIRST:', 'dr-first-name', data.firstName, { readOnly })}
                        ${editable('MIDDLE:', 'dr-middle-name', data.middleName, { readOnly })}
                        ${editable('AGE:', 'dr-age', data.age, { style: 'flex:0.35', readOnly })}
                    </div>
                    <div class="dr-student-row">
                        ${editable('ADDRESS:', 'dr-address', data.address, { readOnly })}
                        ${editable('CONTACT NO:', 'dr-contact', data.contact, { style: 'flex:0.65', readOnly })}
                    </div>
                    <div class="dr-student-row">
                        ${editable('Course & Year (for students):', 'dr-course', data.course, { readOnly })}
                    </div>
                    <div class="dr-student-row">
                        ${editable('Department & Designation (for employees):', 'dr-designation', data.department, { readOnly })}
                    </div>
                </section>

                <div class="dr-exam-heading">
                    <h2 class="dr-section-title">Oral Health Examination</h2>
                    <div class="dr-date-line">${editable('Date and Time:', 'dr-date-time', data.recordDate, { ariaLabel: 'Examination date and time', style: 'flex:0 1 280px', readOnly })}</div>
                </div>
                <div class="dr-exam">
                    <section class="dr-chart">
                        <div class="dr-section-title">Condition and Treatment Needs</div>
                        ${conditionTreatmentChart()}
                    </section>
                    <section class="dr-oral">
                        <h2 class="dr-section-title">Oral Health Condition</h2>
                        ${oralHealthTable()}
                    </section>
                </div>

                <section class="dr-findings">
                    <div class="dr-findings-title">FINDINGS AND RECOMMENDATIONS:</div>
                    <div class="dr-editable"${readOnly ? '' : ' contenteditable="true" role="textbox" aria-label="Findings and recommendations" data-placeholder="Click here to enter findings and recommendations"'}>${safe(data.findings || '')}</div>
                    <div class="dr-editable"${readOnly ? '' : ' contenteditable="true" role="textbox" aria-label="Additional findings and recommendations"'}>${safe(data.additionalFindings || '')}</div>
                    <div class="dr-editable"${readOnly ? '' : ' contenteditable="true" role="textbox" aria-label="Further findings and recommendations"'}>${safe(data.furtherFindings || '')}</div>
                </section>
                <div class="dr-signature">
                    <span class="dr-field">
                        ${dentistSignature}
                        <small>SIGNATURE OF DENTIST</small>
                        <span class="dr-license">LIC.NO. ${dentistLicense}</span>
                    </span>
                </div>
                ${dentalRecordFooter()}
            </article>`;
    }

    function renderDentalRecordForm() {
        const preview = document.getElementById('dental-record-preview');
        if (!preview) { return; }
        const saveButton = document.getElementById('dr-save-record');
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa fa-floppy-disk"></i> Save';
        }
        const selectedDentist = preview.querySelector('#dr_dentist_name')?.value || '';
        const dentistLicense = preview.querySelector('#dr_dentist_license')?.value || '';
        const examinationDateTime = new Intl.DateTimeFormat('en-PH', {
            timeZone: 'Asia/Manila',
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date());
        preview.innerHTML = `${styles()}${dentalRecordMarkup({
            lastName: val('dr_last_name'),
            firstName: val('dr_first_name'),
            middleName: val('dr_middle_name'),
            age: val('dr_age'),
            address: val('dr_address'),
            contact: val('dr_contact'),
            course: val('dr_course'),
            department: val('dr_department'),
            recordDate: examinationDateTime,
            dentistLicense,
        }, false)}`;

        if (typeof loadSigningPersonnel === 'function') {
            loadSigningPersonnel().then(() => {
                const dentistSelect = preview.querySelector('#dr_dentist_name');
                if (!dentistSelect) { return; }
                dentistSelect.value = selectedDentist;
                if (dentistSelect.value && typeof updateSigningPersonnelPreview === 'function') {
                    updateSigningPersonnelPreview(dentistSelect);
                }
            }).catch(error => {
                console.error('Unable to load dentists for the Dental Record form.', error);
                showToast('Unable to load dentists for the Dental Record form.', 'error');
            });
        }

        if (!preview.dataset.markHandlersBound) {
            preview.addEventListener('click', function (event) {
                const button = event.target.closest('.dr-tooth-mark, .dr-condition-cell:not(.dr-condition-cell-empty), .dr-oral-mark');
                if (!button) { return; }
                const isMarked = button.getAttribute('aria-pressed') === 'true';
                button.setAttribute('aria-pressed', String(!isMarked));
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                }
            });
            preview.addEventListener('input', function () {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                }
            });
            preview.addEventListener('change', function () {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                }
            });
            preview.dataset.markHandlersBound = 'true';
        }
    }

    function renderDentalRecordReadOnly(container, record) {
        if (!container || !record) { return; }
        const student = record.student || {};
        const dentist = record.dentist_provider?.name
            || [record.dentist?.first_name, record.dentist?.last_name].filter(Boolean).join(' ');
        const chart = record.tooth_chart && typeof record.tooth_chart === 'object'
            ? record.tooth_chart
            : {};
        const findings = String(record.oral_exam_findings || '').split('\n');

        container.replaceChildren();
        const sheet = document.createElement('div');
        sheet.className = 'dre-form-paper';
        sheet.innerHTML = `${styles()}${dentalRecordMarkup({
            lastName: student.last_name,
            firstName: student.first_name,
            middleName: student.middle_name,
            age: student.age,
            address: student.address,
            contact: student.contact,
            course: [student.department, student.course_year].filter(Boolean).join(' / '),
            department: student.designation || '',
            recordDate: record.record_date ? String(record.record_date).slice(0, 10) : '',
            findings: findings[0] || '',
            additionalFindings: findings[1] || '',
            furtherFindings: findings.slice(2).join('\n'),
            dentistName: dentist,
            dentistLicense: record.dentist_provider?.license_no || record.dentist?.license_no || '',
        }, true)}`;
        container.append(sheet);

        const toothMarks = new Set(Array.isArray(chart.tooth_markings) ? chart.tooth_markings : []);
        const oralMarks = new Set(Array.isArray(chart.oral_condition_markings) ? chart.oral_condition_markings : []);
        const legacyToothMarks = chart.tooth_markings ? {} : chart;
        sheet.querySelectorAll('.dr-tooth-mark, .dr-condition-cell:not(.dr-condition-cell-empty)').forEach(button => {
            const isMarked = toothMarks.has(button.dataset.tooth)
                || (button.classList.contains('dr-tooth-mark')
                    && Object.prototype.hasOwnProperty.call(legacyToothMarks, button.dataset.tooth.split('-')[1]));
            button.setAttribute('aria-pressed', String(isMarked));
            button.setAttribute('aria-disabled', 'true');
            button.tabIndex = -1;
        });
        sheet.querySelectorAll('.dr-oral-mark').forEach(button => {
            button.setAttribute('aria-pressed', String(oralMarks.has(`${button.dataset.condition}:${button.dataset.quadrant}`)));
            button.setAttribute('aria-disabled', 'true');
            button.tabIndex = -1;
        });
    }

    function printForm() {
        const preview = document.getElementById('dental-record-preview');
        if (!preview) { return; }
        const dentistSelect = preview.querySelector('#dr_dentist_name');
        const selectedDentist = dentistSelect?.value
            ? dentistSelect.selectedOptions[0]?.textContent || ''
            : '';
        const dentistLicense = preview.querySelector('#dr_dentist_license')?.value || '';
        const clone = preview.cloneNode(true);
        clone.querySelectorAll('#dr_dentist_name').forEach(select => {
            const name = document.createElement('span');
            name.className = 'dr-dentist-select dr-dentist-print-name';
            name.textContent = selectedDentist;
            select.replaceWith(name);
        });
        clone.querySelectorAll('#dr_dentist_license').forEach(input => {
            const license = document.createElement('span');
            license.className = 'dr-editable';
            license.textContent = dentistLicense;
            input.replaceWith(license);
        });
        clone.querySelectorAll('[contenteditable]').forEach(element => {
            element.removeAttribute('contenteditable');
            element.removeAttribute('role');
            element.removeAttribute('aria-label');
            element.removeAttribute('data-placeholder');
        });
        clone.querySelectorAll('.dr-tooth-mark, .dr-condition-cell:not(.dr-condition-cell-empty), .dr-oral-mark').forEach(button => {
            const isMarked = button.getAttribute('aria-pressed') === 'true';
            const mark = document.createElement('span');
            mark.className = button.className;
            if (button.classList.contains('dr-tooth-mark')) {
                mark.setAttribute('aria-pressed', String(isMarked));
                mark.innerHTML = button.innerHTML;
            } else {
                mark.classList.add('dr-print-check');
                mark.textContent = isMarked ? '✓' : '';
                if (button.classList.contains('dr-oral-mark')) {
                    mark.style.display = 'flex';
                    mark.style.alignItems = 'center';
                    mark.style.justifyContent = 'center';
                    mark.style.width = '5mm';
                    mark.style.height = '4mm';
                    mark.style.margin = 'auto';
                }
            }
            button.replaceWith(mark);
        });
        const win = window.open('', '_blank', 'width=1200,height=900');
        if (!win) {
            uhAlert('Please allow pop-ups for this site to print the form.', { title: 'Printing unavailable' });
            return;
        }
        win.document.write(`<!doctype html><html><head><title>Dental Record</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <style>
                    @page { size:A4 portrait;margin:0; }
                html,body { margin:0;padding:0;background:#fff;font-family:Arial,sans-serif; }
                ${clone.querySelector('style')?.textContent || ''}
                    .dr-sheet { width:210mm;height:297mm;padding:19mm;page-break-after:avoid; }
                    .dr-sheet .dc-header { display:flex;flex-direction:row;align-items:center;gap:4mm;border-bottom:1.5pt solid #111;padding-bottom:4mm;margin-bottom:6mm; }
                    .dr-sheet .dc-header img { width:28mm;height:28mm;object-fit:contain;flex-shrink:0;transform:scale(1.9) translateX(3mm);transform-origin:center; }
                    .dr-sheet .dc-ht { line-height:1.3;font-family:Arial,sans-serif; }
                    .dr-sheet .dc-republic { color:#003C8F;font-size:8pt;font-weight:600; }
                    .dr-sheet .dc-university { color:#F6BF00;font-weight:900;font-size:14pt;letter-spacing:.3px;text-transform:uppercase;white-space:nowrap; }
                    .dr-sheet .dc-office { color:#003C8F;font-weight:800;font-size:10.5pt;margin-top:1px;white-space:nowrap; }
                    .dr-sheet .dc-address { color:#003C8F;font-size:8pt;font-weight:500;margin-top:3px;line-height:1.5; }
                    .dr-sheet .dc-footer { margin-top:3mm;padding-top:0; }
                    .dr-sheet .dc-tagline { text-align:center;font-style:italic;font-weight:700;color:#003C8F;font-size:8pt;letter-spacing:.3px;margin-bottom:1.5mm;line-height:1; }
                    .dr-sheet .dc-footer-row { display:flex;align-items:center;justify-content:space-between;width:100%;gap:3mm; }
                    .dr-sheet .dc-contact { font-size:7pt;color:#003C8F;font-weight:600;line-height:1.5;flex-shrink:0; }
                    .dr-sheet .dc-contact i { display:inline-block;width:11px;margin-right:3px;text-align:center;color:#003C8F; }
                    .dr-sheet .dc-logos { flex:1;height:13mm;max-height:13mm;object-fit:fill; }
                .dr-tooth-mark,.dr-oral-mark { print-color-adjust:exact;-webkit-print-color-adjust:exact; }
                * { box-sizing:border-box; }
            </style>
            </head><body>${clone.innerHTML}<script>window.addEventListener('load',function(){setTimeout(function(){window.focus();window.print();},250);});<\/script></body></html>`);
        win.document.close();
    }

    async function saveDentalRecord() {
        const button = document.getElementById('dr-save-record');
        const studentId = val('dr_student_id').trim();
        const preview = document.getElementById('dental-record-preview');
        if (!studentId) {
            showToast('Search for and select a student before saving the Dental Record.', 'error');
            return;
        }
        if (!preview || !button) { return; }

        const role = window.__clinicUser?.role || document.body.dataset.clinicRole;
        const endpoint = role === 'nurse_head'
            ? '/nurse-head/dental-records'
            : role === 'nurse'
                ? '/nurse/dental-records'
                : '/admin/dental-records';
        const selectedDentist = preview.querySelector('#dr_dentist_name')?.value || '';
        const findings = Array.from(preview.querySelectorAll('.dr-findings .dr-editable'))
            .map(element => element.textContent.trim())
            .filter(Boolean)
            .join('\n');
        const toothMarkings = Array.from(preview.querySelectorAll('.dr-tooth-mark[aria-pressed="true"], .dr-condition-cell[aria-pressed="true"]'))
            .map(button => button.dataset.tooth)
            .filter(Boolean);
        const oralConditionMarkings = Array.from(preview.querySelectorAll('.dr-oral-mark[aria-pressed="true"]'))
            .map(button => `${button.dataset.condition}:${button.dataset.quadrant}`)
            .filter(value => !value.endsWith(':'));
        const dateParts = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).formatToParts(new Date()).reduce((parts, part) => {
            parts[part.type] = part.value;
            return parts;
        }, {});
        const payload = {
            student_id: studentId,
            dentist_provider_id: selectedDentist || null,
            record_date: `${dateParts.year}-${dateParts.month}-${dateParts.day}`,
            oral_exam_findings: findings || null,
            tooth_chart: {
                tooth_markings: toothMarkings,
                oral_condition_markings: oralConditionMarkings,
            },
        };

        button.disabled = true;
        button.innerHTML = 'Saving…';
        try {
            const response = await uhPost(endpoint, payload);
            if (!response.ok || !response.success) {
                const validationError = response.errors ? Object.values(response.errors).flat()[0] : null;
                throw new Error(validationError || response.message || response.error || 'The Dental Record could not be saved.');
            }
            button.innerHTML = '<i class="fa fa-check"></i> Saved';
            showToast(response.message || 'Dental record saved.', 'success');
            window.dispatchEvent(new CustomEvent('clinic:dental-record-saved', {
                detail: { record: response.record },
            }));
        } catch (error) {
            button.disabled = false;
            button.innerHTML = '<i class="fa fa-floppy-disk"></i> Save';
            showToast(error.message || 'The Dental Record could not be saved.', 'error');
            console.error('Dental record save failed:', error);
        }
    }

    function clearForm() {
        const search = document.getElementById('dental-record_search');
        if (search) { search.value = ''; }
        const saveButton = document.getElementById('dr-save-record');
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa fa-floppy-disk"></i> Save';
        }
        ['dr_student_id', 'dr_last_name', 'dr_first_name', 'dr_middle_name', 'dr_age', 'dr_course', 'dr_department', 'dr_contact', 'dr_address', 'dr_designation']
            .forEach(id => {
                const input = document.getElementById(id);
                if (input) { input.value = ''; }
            });
        renderDentalRecordForm();
    }

    window.renderDentalRecordForm = renderDentalRecordForm;
    window.renderDentalRecordReadOnly = renderDentalRecordReadOnly;
    window.dentalRecordPrint = printForm;
    window.clearDentalRecordForm = clearForm;
    window.saveDentalRecord = saveDentalRecord;
    document.addEventListener('DOMContentLoaded', renderDentalRecordForm);
}());
