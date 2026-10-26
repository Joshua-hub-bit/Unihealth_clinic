<div class="uh-card uh-card-md vitals-workspace">
    <div class="vitals-workspace-header">
        <div class="vitals-title-wrap">
            <span class="vitals-title-icon"><i class="fa fa-heart-pulse"></i></span>
            <div>
                <span class="vitals-eyebrow">Clinical observation</span>
                <h2>Record Vital Signs</h2>
                <p>Capture the patient’s current measurements and add any observations for the care team.</p>
            </div>
        </div>
        <span class="vitals-secure-badge"><i class="fa fa-lock"></i> Secure record</span>
    </div>

    <div class="vitals-section-heading">
        <span>01</span>
        <div><strong>Choose patient</strong><small>Search by student ID or patient name.</small></div>
    </div>
    <div class="uh-form-group vitals-patient-group">
        <label for="vitals_patient_id">Student / Patient</label>
        <div class="search-toolbar vitals-student-search">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="text" id="vitals_patient_id" placeholder="Enter student ID or patient name" autocomplete="off" aria-describedby="vitals-search-hint"/>
        </div>
        <small id="vitals-search-hint" class="vitals-field-hint"><i class="fa fa-circle-info"></i> Select the matching patient before saving.</small>
        <div id="vitals-selected-student" class="vitals-selected-student" aria-live="polite"></div>
    </div>

    <div class="vitals-section-heading">
        <span>02</span>
        <div><strong>Measurements</strong><small>Use the patient’s latest observed values.</small></div>
    </div>
    <div class="uh-grid-2 uh-gap-12 vitals-measurement-grid">
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_bp"><i class="fa fa-gauge-high"></i> Blood Pressure <span>(mmHg)</span></label>
            <input type="text" id="vitals_bp" placeholder="e.g. 120/80" inputmode="numeric"/>
        </div>
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_temp"><i class="fa fa-temperature-half"></i> Temperature <span>(°C)</span></label>
            <input type="number" id="vitals_temp" placeholder="36.5" step="0.1" inputmode="decimal"/>
        </div>
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_pulse"><i class="fa fa-heart-pulse"></i> Pulse Rate <span>(bpm)</span></label>
            <input type="number" id="vitals_pulse" placeholder="72" inputmode="numeric"/>
        </div>
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_rr"><i class="fa fa-lungs"></i> Respiratory Rate <span>(breaths/min)</span></label>
            <input type="number" id="vitals_rr" placeholder="18" inputmode="numeric"/>
        </div>
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_o2"><i class="fa fa-lungs"></i> O₂ Saturation <span>(%)</span></label>
            <input type="number" id="vitals_o2" placeholder="98" inputmode="numeric"/>
        </div>
        <div class="uh-form-group vitals-measurement">
            <label for="vitals_date"><i class="fa fa-calendar-day"></i> Observation Date</label>
            <input type="date" id="vitals_date" max="{{ today()->toDateString() }}"/>
        </div>
    </div>

    <div class="vitals-section-heading">
        <span>03</span>
        <div><strong id="vitals-notes-heading">Clinical notes</strong><small id="vitals-notes-description">Document context or observations for follow-up.</small></div>
    </div>
    <div class="uh-form-group vitals-notes-group">
        <label for="vitals_notes"><span id="vitals-notes-label">Notes</span> <span>Optional</span></label>
        <textarea id="vitals_notes" rows="3" placeholder="Add symptoms, observations, or follow-up notes..."></textarea>
    </div>

    <div class="vitals-form-footer">
        <span><i class="fa fa-circle-check"></i> Review the values before saving.</span>
        <div class="uh-flex-row-gap-10">
            <button class="uh-btn uh-btn-primary vitals-save-button" onclick="nSaveVitals()">
                <i class="fa fa-save"></i> <span id="vitals-save-label">Save Vitals</span>
            </button>
            <button class="uh-btn uh-btn-ghost vitals-clear-button" onclick="nClearVitals()">
                <i class="fa fa-rotate-left"></i> Clear
            </button>
        </div>
    </div>
</div>
