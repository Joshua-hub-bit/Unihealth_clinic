@php
    $isDentistPrescription = ($role ?? 'physician') === 'dentist';
    $prescriptionPrefix = $isDentistPrescription ? 'rxd' : 'rx';
    $prescriptionFormId = $isDentistPrescription ? 'prescription-dentist-form' : 'prescription-form';
    $prescriptionPageId = $isDentistPrescription ? 'page-form-prescription-dentist' : 'page-form-prescription';
    $prescriptionSearchId = $isDentistPrescription ? 'prescription_dentist_search' : 'prescription_search';
    $prescriptionSearchClearId = $isDentistPrescription ? 'prescription_dentist_search_clear' : 'prescription_search_clear';
    $prescriptionPreviewId = $isDentistPrescription ? 'prescription-dentist-preview' : 'prescription-preview';
    $prescriptionIframeId = $isDentistPrescription ? 'prescription-dentist-iframe' : 'prescription-iframe';
    $prescriptionSearchRole = $isDentistPrescription ? 'dentist' : 'physician';
@endphp

<div class="uh-page" id="{{ $prescriptionPageId }}">
    <div class="print-toolbar">
        <div class="search-toolbar">
            <button class="ph-prescription-search-trigger" type="button"
                aria-label="Search students"
                title="Search students"
                onclick="phHandlePrescriptionFormSearch('{{ $prescriptionSearchRole }}', document.getElementById('{{ $prescriptionSearchId }}').value)">
                <i class="fa fa-search" id="{{ $isDentistPrescription ? 'prescription_dentist_search_icon' : 'prescription_search_icon' }}" aria-hidden="true"></i>
            </button>
            <input type="text" id="{{ $prescriptionSearchId }}"
                placeholder="Search student by ID or name…"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="{{ $isDentistPrescription ? 'prescription_dentist_matches' : 'prescription_matches' }}"
                onkeydown="if(event.key==='Enter'){event.preventDefault();phHandlePrescriptionFormSearch('{{ $prescriptionSearchRole }}', this.value);}"
                oninput="phPrescriptionSearchInput('{{ $prescriptionSearchRole }}', this)"/>
            <button type="button" id="{{ $prescriptionSearchClearId }}"
                onclick="phClearPrescriptionStudent('{{ $prescriptionSearchRole }}')"
                style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                title="Clear search">&times;</button>
            <div class="ph-prescription-search-results" id="{{ $isDentistPrescription ? 'prescription_dentist_matches' : 'prescription_matches' }}" role="listbox" aria-label="Matching students" hidden></div>
        </div>
        <button class="btn-action" type="button" onclick="clearPrescription{{ $isDentistPrescription ? 'Dentist' : '' }}Form();phClearPrescriptionStudent('{{ $prescriptionSearchRole }}');">
            <i class="fa fa-eraser"></i> Clear
        </button>
        <button class="btn-action btn-action-print" type="button" onclick="prescription{{ $isDentistPrescription ? 'Dentist' : '' }}Print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="doc-layout">
        <form id="{{ $prescriptionFormId }}" class="doc-form-panel"
            oninput="renderPrescription{{ $isDentistPrescription ? 'Dentist' : '' }}Preview()"
            onchange="renderPrescription{{ $isDentistPrescription ? 'Dentist' : '' }}Preview()">
            <h3><i class="fa {{ $isDentistPrescription ? 'fa-tooth' : 'fa-prescription' }}"></i> Prescription Form {{ $isDentistPrescription ? 'Dentist' : 'Physician' }}</h3>

            <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

            <div class="fg">
                <label>Full Name</label>
                <input type="text" id="{{ $prescriptionPrefix }}_full_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
            </div>
            <div class="fg-row">
                <div class="fg">
                    <label>Age</label>
                    <input type="text" id="{{ $prescriptionPrefix }}_age" placeholder="21"/>
                </div>
                <div class="fg">
                    <label>Date</label>
                    <input type="date" id="{{ $prescriptionPrefix }}_date"/>
                </div>
            </div>
            <div class="fg">
                <label for="prescription_referral_id">Link to accepted referral (optional)</label>
                <select id="prescription_referral_id" disabled>
                    <option value="">Search for a student first</option>
                </select>
            </div>

            <div class="form-section-title">Prescription Notes</div>
            <div class="fg">
                <label>Medications / Instructions (printed on all 4 cards)</label>
                <textarea id="{{ $prescriptionPrefix }}_notes" rows="8" placeholder="@if($isDentistPrescription)1. Mefenamic Acid 500mg — 1 tab every 8 hours for pain&#10;2. Amoxicillin 500mg — 1 cap every 8 hours for 7 days&#10;3. Warm saline gargle 3x daily @else 1. Paracetamol 500mg — 1 tab every 6 hours for fever&#10;2. Amoxicillin 500mg — 1 cap every 8 hours for 7 days&#10;3. Continue hydration and rest @endif"></textarea>
            </div>

            <div class="form-section-title">Issuing {{ $isDentistPrescription ? 'Dentist' : 'Physician' }}</div>

            <div class="fg">
                <label>{{ $isDentistPrescription ? 'Dentist' : 'Physician' }} Name</label>
                <select id="{{ $prescriptionPrefix }}_physician_name" data-signing-role="{{ $prescriptionSearchRole }}" onchange="phUpdatePrescriptionSigner(this, '{{ $prescriptionPrefix }}')">
                    <option value="">Select {{ $prescriptionSearchRole }}</option>
                </select>
            </div>
            <div class="fg">
                <label>Designation</label>
                <input type="text" id="{{ $prescriptionPrefix }}_physician_role" value="{{ $isDentistPrescription ? 'School Dentist' : 'School Physician' }}" placeholder="{{ $isDentistPrescription ? 'School Dentist' : 'School Physician' }}" readonly/>
            </div>
            <div class="fg">
                <label>License No.</label>
                <input type="text" id="{{ $prescriptionPrefix }}_physician_lic" placeholder="{{ $isDentistPrescription ? 'e.g. 0056828' : 'e.g. 85606' }}"/>
            </div>
            <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                <i class="fa fa-info-circle"></i>
                The same details are duplicated across all 4 cards in the printed page. Use one printed page for up to 4 separate prescriptions.
            </p>
            <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="phSavePrescriptionForm('{{ $prescriptionSearchRole }}')">
                Send to Student
            </button>
        </form>

        <div class="doc-preview-wrap">
            <div id="{{ $prescriptionPreviewId }}" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
        </div>
        <iframe id="{{ $prescriptionIframeId }}" src="" style="display:none;" title="Prescription Form {{ $isDentistPrescription ? 'Dentist' : 'Physician' }} Print"></iframe>
    </div>
</div>
