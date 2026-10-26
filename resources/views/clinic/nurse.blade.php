<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nurse Dashboard — SUNN Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/unihealth.css') }}?v={{ time() }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/student-dashboard-reference.css') }}?v={{ filemtime(public_path('clinic/css/student-dashboard-reference.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/nurse-forms.css') }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/analytics.css') }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/consultation-workflow.css') }}?v={{ filemtime(public_path('clinic/css/consultation-workflow.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/dashboard-card-theme.css') }}?v={{ filemtime(public_path('clinic/css/dashboard-card-theme.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/medical-record-entry.css') }}?v={{ filemtime(public_path('clinic/css/medical-record-entry.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/staff-calendar.css') }}?v={{ filemtime(public_path('clinic/css/staff-calendar.css')) }}"/>
    <script src="{{ asset('clinic/js/dashboard-card-motion.js') }}?v={{ filemtime(public_path('clinic/js/dashboard-card-motion.js')) }}" defer></script>
    <script>
        (function () {
            var raw = sessionStorage.getItem('clinic_user');
            try { window.__clinicUser = raw ? JSON.parse(raw) : null; } catch (e) { window.__clinicUser = null; }
        }());
    </script>
</head>
<body class="student-dashboard dashboard-card-theme" data-clinic-role="nurse">

<div class="uh-layout">

    <!-- ── Sidebar ─────────────────────────────────── -->
    <x-clinic.sidebar role="nurse">
        <button class="uh-nav-item active" onclick="uhShowPage('dashboard',this)" data-title="Dashboard">
            <i class="fa fa-gauge-high"></i><span>Dashboard</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('medical-records',this)" data-title="Medical and Dental Records">
            <i class="fa fa-file-medical"></i><span>Medical and Dental Records</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('vital-records',this)" data-title="Vital Signs Records">
            <i class="fa fa-heart-pulse"></i><span>Vital Signs Records</span>
        </button>
        <button class="uh-nav-item" onclick="nSetVitalsTreatmentMode(false);uhShowPage('vital-signs',this)" data-title="Record Vital Signs">
            <i class="fa fa-clipboard-list"></i><span>Record Vital Signs</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('encounter',this)" data-title="Create Encounter">
            <i class="fa fa-stethoscope"></i><span>Create Encounter</span>
        </button>
        <button class="uh-nav-item" onclick="cwOpenConsultations()" data-title="Consultation Requests">
            <i class="fa fa-comment-medical"></i><span>Consultation Requests</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('forms',this)" data-title="Generate Forms">
            <i class="fa fa-file-medical"></i><span>Generate Forms</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('queue',this);nLoadQueue();" data-title="Appointments">
            <i class="fa fa-list-ol"></i><span>Appointments</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('calendar',this);clinicCalendarLoad().catch(function(){});" data-title="Appointments Calendar">
            <i class="fa fa-calendar-days"></i><span>Appointments Calendar</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('availability-calendar',this);nOpenAvailabilityManager().catch(function(error){showToast(error.message||'Could not load provider availability.','error');});" data-title="Provider Availability">
            <i class="fa fa-calendar-plus"></i><span>Provider Availability</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('referrals-coord',this)" data-title="Referrals">
            <i class="fa fa-share-from-square"></i><span>Referrals</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('messages',this);nMessagesInit();" data-title="Messages">
            <i class="fa fa-comments"></i><span>Messages</span>
        </button>
    </x-clinic.sidebar>

    <!-- ── Main ────────────────────────────────────── -->
    <div class="uh-main">

        <x-clinic.topbar />

        <div class="uh-content">

            <!-- ════════════════════════════════════════
                 PAGE: DASHBOARD
            ════════════════════════════════════════ -->
            <div class="uh-page active" id="page-dashboard">
                <div class="student-stat-grid nurse-stat-grid">
                    <button class="student-stat-card" type="button" onclick="uhShowPage('queue',document.querySelector('[data-title=\'Appointments\']'))"><span class="student-stat-icon"><i class="fa fa-users"></i></span><span class="student-stat-label">Total Patients <i class="fa fa-chevron-right"></i></span><strong id="statPatients">—</strong><small id="todayPatientCount">0 today</small><em>View Queue <i class="fa fa-arrow-right"></i></em></button>
                    <button class="student-stat-card" type="button" onclick="nSetVitalsTreatmentMode(false);uhShowPage('vital-signs',document.querySelector('[data-title=\'Record Vital Signs\']'))"><span class="student-stat-icon"><i class="fa fa-heart-pulse"></i></span><span class="student-stat-label">Today's Visits <i class="fa fa-chevron-right"></i></span><strong id="statVisits">—</strong><small>Recorded today.</small><em>Record Vitals <i class="fa fa-arrow-right"></i></em></button>
                    <button class="student-stat-card" type="button" onclick="nSetVitalsTreatmentMode(false);uhShowPage('vital-signs',document.querySelector('[data-title=\'Record Vital Signs\']'))"><span class="student-stat-icon"><i class="fa fa-calendar"></i></span><span class="student-stat-label">Visits This Month <i class="fa fa-chevron-right"></i></span><strong id="statMonthlyVisits">—</strong><small>Current month.</small><em>View Visits <i class="fa fa-arrow-right"></i></em></button>
                    <button class="student-stat-card" type="button" onclick="uhShowPage('forms',document.querySelector('[data-title=\'Generate Forms\']'))"><span class="student-stat-icon"><i class="fa fa-file-medical"></i></span><span class="student-stat-label">Forms Issued <i class="fa fa-chevron-right"></i></span><strong id="statPrescriptions">—</strong><small>This month.</small><em>Generate Forms <i class="fa fa-arrow-right"></i></em></button>
                    <button class="student-stat-card" type="button" onclick="cwOpenConsultations()"><span class="student-stat-icon"><i class="fa fa-comment-medical"></i></span><span class="student-stat-label">Consultation Requests <i class="fa fa-chevron-right"></i></span><strong id="cw-consultation-count">—</strong><small>Student health concerns.</small><em>Review Requests <i class="fa fa-arrow-right"></i></em></button>
                </div>

                <div class="student-dashboard-grid">
                    <section class="student-panel student-appointments-panel"><div class="student-panel-heading"><h3><i class="fa fa-list-ol"></i> Patient Queue</h3><button type="button" onclick="uhShowPage('queue',document.querySelector('[data-title=\'Appointments\']'))">Manage Queue <i class="fa fa-arrow-right"></i></button></div><div id="recentVisitsList"><p class="student-empty-state">Loading queue...</p></div></section>
                    <section class="student-panel"><div class="student-panel-heading"><h3><i class="fa fa-bolt"></i> Quick Actions</h3></div><div class="student-action-grid"><button type="button" onclick="nSetVitalsTreatmentMode(false);uhShowPage('vital-signs',document.querySelector('[data-title=\'Record Vital Signs\']'))"><i class="fa fa-heart-pulse"></i><span>Record Vitals</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('encounter',document.querySelector('[data-title=\'Create Encounter\']'))"><i class="fa fa-stethoscope"></i><span>Create Encounter</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('forms',document.querySelector('[data-title=\'Generate Forms\']'))"><i class="fa fa-file-medical"></i><span>Generate Forms</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('referrals-coord',document.querySelector('[data-title=\'Coordinate Referrals\']'))"><i class="fa fa-share-from-square"></i><span>Coordinate Referrals</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('messages',document.querySelector('[data-title=\'Messages\']'))"><i class="fa fa-comments"></i><span>Messages</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="cwOpenConsultations()"><i class="fa fa-comment-medical"></i><span>Consultation Requests</span><i class="fa fa-chevron-right"></i></button></div></section>
                </div>

                <div class="student-dashboard-grid student-lower-grid"><section class="student-panel student-trends-panel"><div class="student-panel-heading"><h3><i class="fa fa-chart-line"></i> Reports &amp; Dashboard</h3></div><div class="student-chart-title">Daily Visit Volume — Last 7 Days</div><div class="student-chart" id="nurse-visit-chart"><p class="student-empty-state">No visit data yet.</p></div></section><section class="student-panel nurse-dashboard-messages-panel"><div class="student-panel-heading"><h3><i class="fa fa-comments"></i> Messages</h3><button type="button" onclick="nShowMessages()">Open Messages <i class="fa fa-arrow-right"></i></button></div><p class="student-chart-title">People who messaged you and your new messages</p><div id="nurse-dashboard-messages" class="nurse-dashboard-messages" aria-live="polite"><p class="student-empty-state">Loading messages...</p></div></section></div>
                <section class="nh-pm-decision-card" aria-labelledby="nan-decision-support-title">
                    <div class="nh-ds-header">
                        <div>
                            <div class="nh-ds-title" id="nan-decision-support-title">
                                <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                Nurse Workflow Insights
                            </div>
                            <div class="nh-ds-subtitle">Queue, visit, and documentation signals for your shift</div>
                        </div>
                        <div class="nh-ds-header-actions">
                            <button type="button" class="nh-ds-analyze-button" data-ai-analysis-button disabled>
                                <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                <span class="nh-ds-analyze-label">Analyze with AI</span>
                            </button>
                            <span class="nh-ds-badge">DATA-DRIVEN</span>
                        </div>
                    </div>
                    <div class="nh-ds-insights" id="nan-decision-support-insights" aria-live="polite">
                        <div class="nh-ds-state">Loading your dashboard information…</div>
                    </div>
                    <div class="nh-ds-disclaimer">Workflow summaries only; these do not assess or prioritize clinical urgency.</div>
                </section>
            </div>

            <x-clinic.medical-record-entry />

            <!-- ════════════════════════════════════════
                 PAGE: RECORD VITAL SIGNS
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-vital-signs">
                @include('clinic.partials.vitals-recording-form')
            </div>

            <div class="uh-page" id="page-vital-records">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div>
                            <div class="uh-card-title">Vital Signs Records</div>
                            <p class="uh-muted">Review measurements recorded at your campus in the last 30 days.</p>
                        </div>
                    </div>
                    <div class="uh-table-toolbar">
                        <input id="n-vital-records-search" type="search" class="uh-table-search" data-uh-table-search placeholder="Search by patient, student ID, or vital signs..." autocomplete="off" aria-label="Search vital-sign records">
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="nRenderVitalRecords">
                            <thead>
                            <tr><th>Patient</th><th>Student ID</th><th>BP</th><th>Temp</th><th>Pulse</th><th>RR</th><th>O₂</th><th>Health Record</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="n-vital-records-body">
                            <tr><td colspan="9" class="no-data">Select this page to load vital-sign records.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="n-vital-records-pagination" aria-label="Vital-sign record pages"></div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: CREATE ENCOUNTER
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-encounter">
                <div class="uh-card uh-card-lg encounter-workspace">
                    <div class="encounter-workspace-header">
                        <div class="encounter-title-wrap">
                            <span class="encounter-title-icon"><i class="fa fa-stethoscope"></i></span>
                            <div>
                                <span class="encounter-eyebrow">Clinical documentation</span>
                                <h2>Create Encounter</h2>
                                <p>Document the patient visit, clinical findings, treatment, and next steps in one place.</p>
                            </div>
                        </div>
                        <span class="encounter-status-badge"><i class="fa fa-file-circle-check"></i> New encounter</span>
                    </div>

                    <div class="encounter-section-heading">
                        <span>01</span>
                        <div><strong>Patient and visit details</strong><small>Identify the patient and record when the encounter took place.</small></div>
                    </div>
                    <div class="uh-form-group encounter-wide-field encounter-patient-search">
                        <label for="enc_patient_search"><i class="fa fa-magnifying-glass"></i> Search patient / student</label>
                        <div class="encounter-search-control">
                            <input
                                type="search"
                                id="enc_patient_search"
                                placeholder="Search by name or student ID..."
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="enc_patient_search_results"
                            />
                            <div id="enc_patient_search_results" class="encounter-search-results" role="listbox" hidden></div>
                        </div>
                        <small class="encounter-search-hint">Choose a result to fill in the details below, or enter a student ID manually.</small>
                    </div>
                    <div class="uh-grid-2 uh-gap-12 encounter-fields">
                        <div class="uh-form-group encounter-field">
                            <label for="enc_patient_id"><i class="fa fa-id-card"></i> Student / Patient ID</label>
                            <input type="text" id="enc_patient_id" placeholder="e.g. 2024-00001"/>
                        </div>
                        <div class="uh-form-group encounter-field">
                            <label for="enc_patient_name"><i class="fa fa-user"></i> Patient Name</label>
                            <input type="text" id="enc_patient_name" placeholder="Full name"/>
                        </div>
                        <div class="uh-form-group encounter-field encounter-datetime-field">
                            <label for="enc_datetime"><i class="fa fa-calendar-clock"></i> Date &amp; Time</label>
                            <input type="datetime-local" id="enc_datetime"/>
                        </div>
                    </div>

                    <div class="encounter-section-heading">
                        <span>02</span>
                        <div><strong>Presenting concern</strong><small>Capture the reason for the visit in the patient’s own context.</small></div>
                    </div>
                    <div class="uh-form-group encounter-wide-field">
                        <label for="enc_complaint"><i class="fa fa-comment-medical"></i> Chief Complaint</label>
                        <textarea id="enc_complaint" rows="2" placeholder="Patient's chief complaint..."></textarea>
                    </div>

                    <div class="encounter-section-heading">
                        <span>03</span>
                        <div><strong>Quick measurements</strong><small>Add the key readings taken during this encounter.</small></div>
                    </div>
                    <div class="uh-grid-2 uh-gap-12 encounter-fields encounter-measurement-grid">
                        <div class="uh-form-group encounter-field encounter-measurement">
                            <label for="enc_bp"><i class="fa fa-gauge-high"></i> Blood Pressure <span>(mmHg)</span></label>
                            <input type="text" id="enc_bp" placeholder="120/80" inputmode="numeric"/>
                        </div>
                        <div class="uh-form-group encounter-field encounter-measurement">
                            <label for="enc_temp"><i class="fa fa-temperature-half"></i> Temperature <span>(°C)</span></label>
                            <input type="number" id="enc_temp" placeholder="36.5" step="0.1" inputmode="decimal"/>
                        </div>
                        <div class="uh-form-group encounter-field encounter-measurement">
                            <label for="enc_pulse"><i class="fa fa-heart-pulse"></i> Pulse <span>(bpm)</span></label>
                            <input type="number" id="enc_pulse" placeholder="72" inputmode="numeric"/>
                        </div>
                        <div class="uh-form-group encounter-field encounter-measurement">
                            <label for="enc_rr"><i class="fa fa-lungs"></i> Respiratory Rate <span>(breaths/min)</span></label>
                            <input type="number" id="enc_rr" placeholder="18" inputmode="numeric"/>
                        </div>
                        <div class="uh-form-group encounter-field encounter-measurement">
                            <label for="enc_o2"><i class="fa fa-lungs"></i> O₂ Saturation <span>(%)</span></label>
                            <input type="number" id="enc_o2" placeholder="98" inputmode="numeric"/>
                        </div>
                    </div>

                    <div class="encounter-section-heading">
                        <span>04</span>
                        <div><strong>Assessment and care</strong><small>Record findings, treatment, and the patient’s disposition.</small></div>
                    </div>
                    <div class="uh-form-group encounter-wide-field">
                        <label for="enc_assessment"><i class="fa fa-notes-medical"></i> Assessment / Findings</label>
                        <textarea id="enc_assessment" rows="3" placeholder="Clinical assessment..."></textarea>
                    </div>
                    <div class="uh-form-group encounter-wide-field">
                        <label for="enc_treatment"><i class="fa fa-pills"></i> Treatment Given</label>
                        <textarea id="enc_treatment" rows="2" placeholder="Medications / treatment given..."></textarea>
                    </div>
                    <div class="uh-form-group encounter-wide-field" id="enc_remarks_group">
                        <label for="enc_remarks"><i class="fa fa-note-sticky"></i> Remarks / follow-up notes <span>Optional</span></label>
                        <textarea id="enc_remarks" rows="2" placeholder="Add follow-up notes for this treated encounter..."></textarea>
                    </div>
                    <div class="uh-form-group encounter-wide-field">
                        <label for="enc_disposition"><i class="fa fa-route"></i> Disposition</label>
                        <select id="enc_disposition" onchange="nToggleEncounterRemarks()">
                            <option value="">Select disposition...</option>
                            <option>Sent back to class</option>
                            <option>Sent home</option>
                            <option>Referred to hospital</option>
                            <option>Admitted for observation</option>
                            <option>For further evaluation</option>
                        </select>
                    </div>
                    <div class="encounter-form-footer">
                        <span><i class="fa fa-circle-check"></i> Review the encounter details before saving.</span>
                        <div class="uh-flex-row-gap-10">
                            <button class="uh-btn uh-btn-primary encounter-save-button" onclick="nSaveEncounter()">
                                <i class="fa fa-save"></i> Save Encounter
                            </button>
                            <button class="uh-btn uh-btn-ghost encounter-clear-button"
                                onclick="nClearEncounter()">
                                <i class="fa fa-rotate-left"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uh-page" id="page-consultations">
                <div class="cw-card">
                    <div id="cw-nurse-queue-header" class="uh-card-header cw-queue-header">
                        <div>
                            <h2>Student consultation requests</h2>
                            <p class="cw-muted">Review each request's transcript and summary first; open the full transcript only when needed. Student recordings are transcribed automatically after submission.</p>
                        </div>
                        <button id="cw-refresh-queue" class="uh-btn uh-btn-outline uh-btn-sm" type="button">Refresh</button>
                    </div>
                    <div id="cw-queue-filters" class="cw-queue-filters cw-queue-filters--nurse">
                        <label class="cw-queue-priority-field">
                            <span>Filter by priority level</span>
                            <select id="cw-queue-priority" aria-label="Filter consultation requests by priority level">
                                <option value="">All priorities</option>
                                <option value="Priority 1">High priority</option>
                                <option value="Priority 2">Medium priority</option>
                                <option value="Priority 3">Low priority</option>
                            </select>
                        </label>
                        <label class="cw-queue-search-field">
                            <span>Search student</span>
                            <span class="cw-queue-search-input">
                                <i class="fa fa-search" aria-hidden="true"></i>
                                <input id="cw-queue-student-search" type="search" placeholder="Name or student ID" autocomplete="off" aria-label="Search consultation requests by student">
                            </span>
                        </label>
                    </div>
                    <div id="cw-nurse-queue" class="cw-list" aria-live="polite"></div>
                    <nav id="cw-queue-pagination" class="cw-queue-pagination" aria-label="Consultation request pages"></nav>
                    <section id="cw-nurse-detail" class="cw-nurse-detail" aria-live="polite" hidden></section>
                </div>
            </div>

            {{-- Generate Forms — tile grid + 10 document pages (extracted to partial) --}}
            @include('clinic.partials.generate-forms')


            {{-- ═══════════════════════════════════════════════════════════
                 FORM PREVIEW SCRIPTS (extracted from inline blocks)
                 utils.js must load first — it provides escHtml, fmtDate,
                 injectStyle, openPrint, fieldVal, fieldChecked, radioVal.
            ════════════════════════════════════════════════════════════════ --}}
            <script src="{{ asset('clinic/js/forms/utils.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-medcert.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-dentalcert.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-prescription.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-referral.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-lab.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-xray.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-lactating.js') }}?v={{ time() }}"></script>
            <script src="{{ asset('clinic/js/forms/nurse-form-breastfeeding.js') }}?v={{ time() }}"></script>


            <!-- ΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉ
                 PAGE: QUEUES & WORKFLOW
            ΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉ -->
            @include('clinic.partials.staff-calendar')

            <div class="uh-page nurse-workflow-page" id="page-queue">
                <!-- Queue stats -->
                <div class="uh-grid-4 uh-mb-16 nurse-queue-stats">
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon blue"><i class="fa fa-list-ol"></i></div>
                        <div class="uh-stat-number" id="queueStatTotal">0</div>
                        <div class="uh-stat-label">In Queue</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon amber"><i class="fa fa-clock"></i></div>
                        <div class="uh-stat-number" id="queueStatWaiting">0</div>
                        <div class="uh-stat-label">Waiting</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon amber"><i class="fa fa-calendar-plus"></i></div>
                        <div class="uh-stat-number" id="queueStatAccepted">0</div>
                        <div class="uh-stat-label">Accepted — Awaiting Care</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon green"><i class="fa fa-stethoscope"></i></div>
                        <div class="uh-stat-number" id="queueStatCheckupScheduled">0</div>
                        <div class="uh-stat-label">Scheduled Check-up</div>
                    </div>
                </div>

                <div class="nurse-workflow-grid">
                    <!-- Patient queue -->
                    <div class="uh-card nurse-current-queue-card">
                        <div class="uh-card-header">
                            <div class="uh-card-title"><i class="fa fa-list-ol" aria-hidden="true"></i> Patient Queue</div>
                            <div class="uh-flex uh-gap-8">
                                <button class="uh-btn uh-btn-primary uh-btn-sm" onclick="nLoadQueue()">
                                    <i class="fa fa-rotate"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="uh-table-toolbar">
                            <input id="queueTableSearch" type="search" class="uh-table-search" placeholder="Search..." autocomplete="off" aria-label="Search patient queue">
                            <label class="uh-table-count">Priority
                                <select id="queuePriorityFilter" class="uh-table-status-filter" aria-label="Filter appointments by priority" onchange="nQueuePriorityFilterChanged(this.value)">
                                    <option value="">All Priorities</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </label>
                            <label class="uh-table-count">Status
                                <select id="queueStatusFilter" class="uh-table-status-filter" aria-label="Filter patient queue by status" onchange="nQueueStatusFilterChanged(this.value)">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Waiting</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="checkup_scheduled">Check-up scheduled</option>
                                </select>
                            </label>
                            <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nToggleQueueTimeSort()" aria-label="Sort appointments: oldest first">
                                <i class="fa fa-arrow-down-short-wide" aria-hidden="true"></i>
                                <span id="n-queue-sort-indicator">Oldest</span>
                            </button>
                            <span class="uh-table-count" data-uh-table-count></span>
                        </div>
                        <div class="nurse-current-queue-header" aria-label="Appointment queue columns">
                            <span aria-hidden="true"></span>
                            <span data-column="patient">Patient</span>
                            <span data-column="details">Appointment details</span>
                            <span data-column="priority">Priority / case confidence</span>
                            <span data-column="status">Status</span>
                            <span data-column="actions">Actions</span>
                        </div>
                        <div id="queueTableBody" class="nurse-current-queue-list" aria-live="polite">
                            <p class="student-empty-state">Loading queue...</p>
                        </div>
                        <div id="queueTablePagination"></div>
                    </div>

                </div>
            </div>

            <!-- ΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉ
                 PAGE: COORDINATE REFERRALS
            ΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉΓòÉ -->
            <div class="uh-page nurse-workflow-page" id="page-referrals-coord">
                <div class="nurse-page-intro">
                    <div><span class="nurse-page-eyebrow"><i class="fa fa-share-from-square"></i> Care coordination</span><h2>Referrals</h2><p>Manage referrals for patients registered at your assigned campus.</p></div>
                    <span class="nurse-page-badge"><i class="fa fa-route"></i> Referral desk</span>
                </div>
                <div class="uh-card nurse-referrals-card">
                    <div class="uh-card-header">
                        <div><div class="uh-card-title">Referral Coordination</div><div class="uh-card-subtitle">Review routing and follow-up status.</div></div>
                    </div>
                    <div class="uh-table-toolbar">
                        <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
                        <select id="n-referral-status-filter" class="uh-table-status-filter" aria-label="Filter referrals by status" onchange="loadReferrals()">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="waiting_to_schedule">Waiting to schedule</option>
                            <option value="accepted">Accepted</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="nRenderNurseReferrals">
                            <thead>
                                <tr>
                                    <th><button type="button" class="uh-table-sort" onclick="nToggleReferralTimeSort()" aria-label="Sort referrals by date and time">Date / time <span id="n-referrals-sort-indicator">↓</span></button></th>
                                    <th>Patient</th>
                                    <th>Referred To</th>
                                    <th>Route To</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="referralsTableBody">
                                <tr>
                                    <td colspan="7" class="no-data">
                                        No referrals found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="n-referrals-pagination" aria-label="Referral pages"></div>
                </div>
            </div>

            @include('clinic.partials.appointment-scheduler')
            @include('clinic.partials.availability-manager')

            <!-- PAGE: MESSAGES -->
            <div class="uh-page nurse-messages-page" id="page-messages">
                <div class="uh-chat-area">

                    <!-- Sidebar -->
                    <div class="uh-chat-sidebar">
                        <div class="uh-chat-header">
                            <div class="uh-chat-header-row">
                                <div class="uh-chat-title">Chats</div>
                                <button class="uh-chat-new-btn" onclick="nOpenNewMessage()" title="New Message">
                                    <i class="fa fa-pen-to-square"></i>
                                </button>
                            </div>
                            <div class="uh-chat-search-wrap">
                                <i class="fa fa-magnifying-glass"></i>
                                <input type="text" id="nConvSearch" class="uh-chat-search"
                                    placeholder="Search conversations"
                                    oninput="nFilterConversations(this.value)"/>
                            </div>
                        </div>
                        <!-- User picker -->
                        <div id="nUserPickerWrap" class="uh-chat-picker" style="display:none">
                            <select id="nUserPicker">
                                <option value="">-- Select a person --</option>
                            </select>
                            <div class="uh-chat-picker-actions">
                                <button class="uh-btn uh-btn-primary uh-btn-sm" style="flex:1" onclick="nStartDm()">Open Chat</button>
                                <button class="uh-btn uh-btn-ghost uh-btn-sm" onclick="nCloseUserPicker()">Cancel</button>
                            </div>
                        </div>
                        <div class="uh-chat-channel-list" id="conversationsList">
                            <p class="uh-chat-empty" style="padding:20px 14px">Loading...</p>
                        </div>
                    </div>

                    <!-- Chat main -->
                    <div class="uh-chat-main">
                        <div class="uh-chat-active" id="nChatHeader" style="display:none">
                            <div class="uh-chat-active-avatar" id="nChatHeaderAvatar">?</div>
                            <div class="uh-chat-active-info">
                                <div class="uh-chat-active-name" id="nChatHeaderName">--</div>
                                <div class="uh-chat-active-status">Checking status...</div>
                            </div>
                            <input type="search" id="nChatSearchInput" class="uh-chat-conversation-search"
                                placeholder="Search messages" aria-label="Search messages in conversation"
                                oninput="nRenderMessages(_activeMessages)"
                                onkeydown="if(event.key==='Escape'){event.preventDefault();uhToggleConversationSearch('nChatSearchInput');}"
                                hidden>
                            <button type="button" class="uh-chat-conversation-search-toggle"
                                aria-label="Search conversation" title="Search conversation"
                                aria-controls="nChatSearchInput" aria-expanded="false"
                                onclick="uhToggleConversationSearch('nChatSearchInput')">
                                <i class="fa fa-search" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="uh-chat-placeholder" id="nChatPlaceholder">
                            <i class="fa fa-comments"></i>
                            <p>Select a conversation or start a new one</p>
                        </div>
                        <div class="uh-chat-messages" id="messagesList" style="display:none"></div>
                        <div class="uh-chat-compose" id="nChatCompose" style="display:none">
                            <div class="uh-chat-image-preview" id="nMessageImagePreview" hidden>
                                <img id="nMessageImageThumbnail" alt="Selected image preview">
                                <span id="nMessageImageName"></span>
                                <button type="button" aria-label="Remove selected attachment"
                                    onclick="uhClearMessageImage('nMessageImageInput','nMessageImagePreview','nMessageImageThumbnail','nMessageImageName')">
                                    <i class="fa fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="uh-chat-compose-controls">
                                <input type="file" id="nMessageImageInput" accept="*/*" hidden
                                    onchange="uhPreviewMessageImage(this,'nMessageImagePreview','nMessageImageThumbnail','nMessageImageName')">
                                <button type="button" class="uh-chat-image-btn" aria-label="Attach file, maximum 50 MB" title="Attach file (maximum 50 MB)"
                                    onclick="document.getElementById('nMessageImageInput').click()">
                                    <i class="fa fa-paperclip" aria-hidden="true"></i>
                                </button>
                                <input type="text" id="msgInput" class="uh-chat-input"
                                    placeholder="Aa"
                                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();nSendMessage();}"/>
                                <button class="uh-chat-send-btn" onclick="nSendMessage()">
                                    <i class="fa fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            @include('clinic.partials.notifications-page')

        </div><!-- /.uh-content -->
    </div><!-- /.uh-main -->
</div><!-- /.uh-layout -->

<!-- ════════════════════════════════════════════════════════════
     MODAL: Edit Vital Signs Record
     ════════════════════════════════════════════════════════════ -->
<div class="uh-modal-overlay hidden" id="vitalRecordEditModal" onclick="if(event.target===this) nCloseVitalRecordEdit()">
    <div class="uh-modal uh-modal-lg" role="dialog" aria-modal="true" aria-labelledby="vitalRecordEditTitle">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="vitalRecordEditTitle"><i class="fa fa-pen"></i> Edit Vital Signs</div>
            <button type="button" class="uh-modal-close" onclick="nCloseVitalRecordEdit()" aria-label="Close">&times;</button>
        </div>
        <form id="vitalRecordEditForm">
            <div class="uh-modal-body">
                <div class="uh-grid-2 uh-gap-12">
                    <div class="uh-form-group">
                        <label for="vitalEditBp">Blood pressure</label>
                        <input id="vitalEditBp" name="bp" type="text" maxlength="20">
                    </div>
                    <div class="uh-form-group">
                        <label for="vitalEditTemp">Temperature (°C)</label>
                        <input id="vitalEditTemp" name="temp" type="number" step="0.1">
                    </div>
                    <div class="uh-form-group">
                        <label for="vitalEditPulse">Pulse (bpm)</label>
                        <input id="vitalEditPulse" name="pulse" type="number" step="1">
                    </div>
                    <div class="uh-form-group">
                        <label for="vitalEditRr">Respiratory rate</label>
                        <input id="vitalEditRr" name="rr" type="number" step="1">
                    </div>
                    <div class="uh-form-group">
                        <label for="vitalEditO2">O₂ saturation (%)</label>
                        <input id="vitalEditO2" name="o2_sat" type="number" step="1">
                    </div>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="nCloseVitalRecordEdit()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Queue Detail (View)
     ════════════════════════════════════════════════════════════ -->
<div class="uh-modal-overlay hidden" id="queueDetailModal" onclick="if(event.target===this) nCloseQueueDetail()">
    <div class="uh-modal uh-modal-lg" role="dialog" aria-modal="true" aria-labelledby="queueDetailTitle">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="queueDetailTitle">
                <i class="fa fa-eye"></i> Queue Item Details
            </div>
            <button class="uh-modal-close" onclick="nCloseQueueDetail()" aria-label="Close">&times;</button>
        </div>
        <div class="uh-modal-body" id="queueDetailBody">
            <p style="text-align:center;color:var(--uh-muted);padding:24px;">Loading…</p>
        </div>
        <div class="uh-modal-footer" style="display:flex;justify-content:space-between;gap:12px;">
            <button type="button" id="queueDetailHealthRecord" class="uh-btn uh-btn-ghost" style="display:none;">Health record</button>
            <button type="button" class="uh-btn uh-btn-ghost" onclick="nCloseQueueDetail()">Close</button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Referral Detail (View)
     ════════════════════════════════════════════════════════════ -->
<div class="uh-modal-overlay hidden" id="referralDetailModal" onclick="if(event.target===this) nCloseReferralDetail()">
    <div class="uh-modal uh-modal-lg" role="dialog" aria-modal="true" aria-labelledby="referralDetailTitle">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="referralDetailTitle">
                <i class="fa fa-file-medical"></i> Referral Details
            </div>
            <button class="uh-modal-close" onclick="nCloseReferralDetail()" aria-label="Close">&times;</button>
        </div>
        <div class="uh-modal-body" id="referralDetailBody">
            <p style="text-align:center;color:var(--uh-muted);padding:24px;">Loading…</p>
        </div>
        <div class="uh-modal-footer">
            <button type="button" class="uh-btn uh-btn-ghost" onclick="nCloseReferralDetail()">Close</button>
        </div>
    </div>
</div>

<style>
.nurse-dashboard-messages { display: flex; flex-direction: column; gap: 6px; }
.nurse-dashboard-message {
    display: flex; align-items: center; gap: 9px; width: 100%; min-width: 0;
    padding: 8px; border: 1px solid var(--student-border); border-radius: 9px;
    background: #fff; color: inherit; font: inherit; text-align: left; cursor: pointer;
}
.nurse-dashboard-message:hover { background: #f8fbf9; border-color: #cfe5d5; }
.nurse-dashboard-message-icon {
    display: grid; place-items: center; width: 32px; height: 32px; flex: 0 0 32px;
    border-radius: 50%; background: #e8f5e9; color: var(--student-green);
}
.nurse-dashboard-message-content { display: flex; flex: 1; flex-direction: column; min-width: 0; }
.nurse-dashboard-message-sender { font-size: .76rem; font-weight: 700; }
.nurse-dashboard-message-preview {
    overflow: hidden; color: var(--student-muted); font-size: .7rem;
    text-overflow: ellipsis; white-space: nowrap;
}
.nurse-dashboard-message-new {
    flex: 0 0 auto; padding: 3px 6px; border-radius: 10px;
    background: #dcfce7; color: #166534; font-size: .62rem; font-weight: 700;
}
/* Detail grid used by the referral detail modal. */
.uh-detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 8px 16px; }
.uh-detail-row { display: contents; }
.uh-detail-label {
    font-size: 0.78rem; font-weight: 600; color: var(--uh-muted);
    text-transform: uppercase; letter-spacing: .03em;
    padding: 6px 0; border-bottom: 1px solid var(--uh-border);
}
.uh-detail-value {
    font-size: 0.85rem; color: var(--uh-text);
    padding: 6px 0; border-bottom: 1px solid var(--uh-border);
    word-break: break-word;
}
</style>

<!-- Toast container -->
<div id="uh-toasts"></div>
<div id="toast" class="toast hidden"></div>

<!-- ΓöÇΓöÇ Scripts ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ -->
<script src="{{ asset('clinic/vendor/chart.umd.min.js') }}"></script>
<script src="{{ asset('clinic/js/unihealth.js') }}?v={{ filemtime(public_path('clinic/js/unihealth.js')) }}"></script>
<script src="{{ asset('clinic/js/analytics.js') }}?v={{ filemtime(public_path('clinic/js/analytics.js')) }}"></script>
<script src="{{ asset('clinic/js/sunn-logo-b64.js') }}"></script>
<script src="{{ asset('clinic/js/staff-health-record.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/nurse-forms.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/nurse-typeahead.js') }}"></script>
<script src="{{ asset('clinic/js/nurse-fill-fix.js') }}"></script>
<script src="{{ asset('clinic/js/appointment-scheduling.js') }}?v={{ filemtime(public_path('clinic/js/appointment-scheduling.js')) }}"></script>
<script src="{{ asset('clinic/js/nurse.js') }}?v={{ filemtime(public_path('clinic/js/nurse.js')) }}"></script>
<script src="{{ asset('clinic/js/forms/medical-record-form.js') }}?v={{ filemtime(public_path('clinic/js/forms/medical-record-form.js')) }}"></script>
<script src="{{ asset('clinic/js/forms/dental-record-form.js') }}?v={{ filemtime(public_path('clinic/js/forms/dental-record-form.js')) }}"></script>
<script src="{{ asset('clinic/js/medical-record-entry.js') }}?v={{ filemtime(public_path('clinic/js/medical-record-entry.js')) }}"></script>
<script src="{{ asset('clinic/js/availability-manager.js') }}?v={{ filemtime(public_path('clinic/js/availability-manager.js')) }}"></script>
<script src="{{ asset('clinic/js/consultation-workflow.js') }}?v={{ filemtime(public_path('clinic/js/consultation-workflow.js')) }}"></script>
<script src="{{ asset('clinic/js/staff-calendar.js') }}?v={{ filemtime(public_path('clinic/js/staff-calendar.js')) }}"></script>

<script>
'use strict';

/* ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
   Inline Nurse Functions
   (called by buttons in this file; nurse.js owns the API layer)
ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ */

// ΓöÇΓöÇ Vital Signs ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
function nSaveVitals() {
    var payload = {
        student_no: document.getElementById('vitals_patient_id')?.value?.trim() || '',
        bp:         document.getElementById('vitals_bp')?.value || '',
        temp:       document.getElementById('vitals_temp')?.value || '',
        pulse:      document.getElementById('vitals_pulse')?.value || '',
        rr:         document.getElementById('vitals_rr')?.value || '',
        o2_sat:     document.getElementById('vitals_o2')?.value || '',
        visit_date: document.getElementById('vitals_date')?.value || new Date().toISOString().split('T')[0],
        notes:      document.getElementById('vitals_notes')?.value || ''
    };
    if (!payload.student_no) { showToast('Please select a student first', 'error'); return; }
    if (window.nVitalsSaving) { return; }
    window.nVitalsSaving = true;
    var button = document.querySelector('#page-vital-signs .vitals-save-button');
    if (button) { button.disabled = true; }
    apiFetch('save_visit', 'POST', { type: 'vitals', ...payload }).then(function(res) {
        if (res && (res.success || res.id)) {
            showToast('Vitals saved successfully', 'success');
        } else {
            showToast((res && res.error) ? res.error : 'Failed to save vitals', 'error');
        }
    }).catch(function() {
        showToast('Could not save vitals. Please try again.', 'error');
    }).finally(function() {
        window.nVitalsSaving = false;
        if (button) { button.disabled = false; }
    });
}

// ΓöÇΓöÇ Encounter ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
var nEncounterSearchTimer = null;
var nEncounterSearchSequence = 0;

function nSetEncounterSearchResults(message, students) {
    var results = document.getElementById('enc_patient_search_results');
    var search = document.getElementById('enc_patient_search');
    if (!results || !search) { return; }

    results.replaceChildren();
    if (message) {
        var status = document.createElement('div');
        status.className = 'encounter-search-message';
        status.textContent = message;
        results.appendChild(status);
    } else {
        (students || []).forEach(function(student) {
            var option = document.createElement('button');
            var name = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ');
            var details = [student.student_id, student.department || student.course_year].filter(Boolean).join(' · ');
            option.type = 'button';
            option.className = 'encounter-search-option';
            option.setAttribute('role', 'option');

            var nameLabel = document.createElement('strong');
            nameLabel.textContent = name || 'Unnamed patient';
            var detailLabel = document.createElement('small');
            detailLabel.textContent = details;
            option.append(nameLabel, detailLabel);
            option.addEventListener('click', function() {
                document.getElementById('enc_patient_id').value = student.student_id || '';
                document.getElementById('enc_patient_name').value = name;
                search.value = name || student.student_id || '';
                results.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            });
            results.appendChild(option);
        });
    }

    results.hidden = false;
    search.setAttribute('aria-expanded', 'true');
}

function nSearchEncounterPatients(query) {
    var search = document.getElementById('enc_patient_search');
    var results = document.getElementById('enc_patient_search_results');
    if (!search || !results) { return; }

    var trimmedQuery = query.trim();
    var sequence = ++nEncounterSearchSequence;
    window.clearTimeout(nEncounterSearchTimer);

    if (trimmedQuery.length < 2) {
        results.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        return;
    }

    nSetEncounterSearchResults('Searching...', []);
    nEncounterSearchTimer = window.setTimeout(async function() {
        try {
            var response = await apiFetch('search_student&q=' + encodeURIComponent(trimmedQuery));
            if (sequence !== nEncounterSearchSequence) { return; }

            var students = Array.isArray(response.students) ? response.students : [];
            if (!students.length) {
                nSetEncounterSearchResults('No matching patients or students found.', []);
                return;
            }
            nSetEncounterSearchResults('', students);
        } catch (error) {
            if (sequence !== nEncounterSearchSequence) { return; }
            console.error('Encounter patient search failed:', error);
            nSetEncounterSearchResults('Search failed. Please try again.', []);
        }
    }, 250);
}

document.getElementById('enc_patient_search')?.addEventListener('input', function(event) {
    document.getElementById('enc_patient_id').value = '';
    document.getElementById('enc_patient_name').value = '';
    nSearchEncounterPatients(event.target.value);
});

document.getElementById('enc_patient_search')?.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        var results = document.getElementById('enc_patient_search_results');
        if (results) { results.hidden = true; }
        event.currentTarget.setAttribute('aria-expanded', 'false');
    }
});

document.addEventListener('click', function(event) {
    var control = document.querySelector('.encounter-search-control');
    if (control && !control.contains(event.target)) {
        var results = document.getElementById('enc_patient_search_results');
        var search = document.getElementById('enc_patient_search');
        if (results) { results.hidden = true; }
        if (search) { search.setAttribute('aria-expanded', 'false'); }
    }
});

function nSaveEncounter() {
    var dateTime = document.getElementById('enc_datetime')?.value || '';
    var complaint = document.getElementById('enc_complaint')?.value?.trim() || '';
    var disposition = document.getElementById('enc_disposition')?.value || '';
    var isReferralDisposition = ['Referred to hospital', 'For further evaluation'].includes(disposition);
    var payload = {
        student_no: document.getElementById('enc_patient_id')?.value?.trim() || '',
        visit_date: dateTime ? dateTime.slice(0, 10) : '',
        visit_time: dateTime.includes('T') ? dateTime.slice(11, 16) : '',
        reason: complaint,
        bp: document.getElementById('enc_bp')?.value || '',
        temp: document.getElementById('enc_temp')?.value || '',
        pulse: document.getElementById('enc_pulse')?.value || '',
        rr: document.getElementById('enc_rr')?.value || '',
        o2_sat: document.getElementById('enc_o2')?.value || '',
        symptoms: document.getElementById('enc_assessment')?.value || '',
        notes: isReferralDisposition ? '' : document.getElementById('enc_remarks')?.value || '',
        treatment: document.getElementById('enc_treatment')?.value || '',
        disposition: disposition
    };
    if (!payload.student_no) { showToast('Please select a patient or enter a student ID', 'error'); return; }
    if (!payload.reason) { showToast('Please enter the chief complaint', 'error'); return; }
    if (window.nEncounterSaving) { return; }
    window.nEncounterSaving = true;
    var button = document.querySelector('#page-encounter .encounter-save-button');
    if (button) { button.disabled = true; }

    apiFetch('save_visit', 'POST', { type: 'encounter', ...payload }).then(function(res) {
        if (res && (res.success || res.id)) {
            showToast('Medical record saved successfully', 'success');
        } else {
            showToast((res && res.error) ? res.error : 'Failed to save medical record', 'error');
        }
    }).catch(function() {
        showToast('Could not save the medical record. Please try again.', 'error');
    }).finally(function() {
        window.nEncounterSaving = false;
        if (button) { button.disabled = false; }
    });
}

function nToggleEncounterRemarks() {
    var disposition = document.getElementById('enc_disposition')?.value || '';
    var isReferralDisposition = ['Referred to hospital', 'For further evaluation'].includes(disposition);
    var remarksGroup = document.getElementById('enc_remarks_group');
    var remarks = document.getElementById('enc_remarks');

    if (remarksGroup) { remarksGroup.hidden = isReferralDisposition; }
    if (isReferralDisposition && remarks) { remarks.value = ''; }
}

function nClearEncounter() {
    var page = document.getElementById('page-encounter');
    if (!page) { return; }

    window.clearTimeout(nEncounterSearchTimer);
    nEncounterSearchSequence += 1;
    page.querySelectorAll('input, textarea, select').forEach(function (element) {
        element.value = '';
    });
    var searchResults = document.getElementById('enc_patient_search_results');
    var patientSearch = document.getElementById('enc_patient_search');
    if (searchResults) { searchResults.hidden = true; }
    if (patientSearch) { patientSearch.setAttribute('aria-expanded', 'false'); }
    nToggleEncounterRemarks();
}

// ΓöÇΓöÇ Queue ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
function nLoadQueue() {
    // Delegated to nurse.js so queue behavior stays consistent.
    if (typeof loadQueueWithFallback === 'function') {
        loadStats();
        loadQueueWithFallback();
        return;
    }
    var today = new Date().toISOString().split('T')[0];
    var queueWindowDays = 30;
    apiFetch('get_queue&date=' + today + '&days=' + queueWindowDays).then(function(res) {
        var items = (res && res.queue) ? res.queue : [];
        var tbody = document.getElementById('queueTableBody');
        if (!tbody) { return; }
        var queueTotal = document.getElementById('queueStatTotal');
        if (queueTotal) { queueTotal.textContent = items.length; }
        // API returns: type, id, date, time, detail, status, student_id, last_name, first_name, middle_name
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No open appointments or walk-ins. No upcoming appointments in the next ' + queueWindowDays + ' days.</td></tr>';
            return;
        }

        // Format time HH:MM:SS → 8:00 AM
        var fmtT = function(raw) {
            if (!raw) { return '—'; }
            var parts = (raw + '').split(':');
            var h = parseInt(parts[0], 10); var m = parts[1] || '00';
            var ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
            return h + ':' + m + ' ' + ampm;
        };

        tbody.innerHTML = items.map(function(q, i) {
            var name = [q.first_name, q.last_name].filter(Boolean).join(' ') || '—';
            var sid  = q.student_id || '—';
            var reason = q.type === 'appointment' ? q.service_type || q.detail || '—' : q.detail || '—';
            var typeBadge = q.type === 'appointment'
                ? '<span class="uh-badge scheduled" style="font-size:.7rem;">Appt</span>'
                : '<span class="uh-badge pending" style="font-size:.7rem;">Walk-in</span>';
            var statusClass = q.status === 'completed' ? 'completed' : (q.status === 'confirmed' || q.status === 'accepted') ? 'in-progress' : 'pending';
            var statusLabel = q.status === 'accepted' ? 'Accepted' : (q.status || 'pending');
            var statusBadge = '<span class="uh-badge appointment-status-badge ' + statusClass + '">' + statusLabel + '</span>';
            var entry = {
                id: q.id,
                type: q.type || 'appointment',
                status: q.status || 'pending',
                availability_id: q.availability_id || null,
                campus_id: q.campus_id || null,
                student_id: q.student_id || '',
                first_name: q.first_name || '',
                middle_name: q.middle_name || '',
                last_name: q.last_name || '',
                created_at: q.created_at || '',
                date: q.date || '',
                time: q.time || '',
                detail: q.detail || '',
                service_type: q.service_type || '',
                reason: q.reason || '',
                preferred_time: q.preferred_time || '',
                campus_name: q.campus_name || '',
                is_overdue: !!q.is_overdue
            };
            var actions = '';
            if (q.type === 'appointment' && q.status !== 'completed' && q.status !== 'cancelled' && q.status !== 'deleted') {
                var serializedEntry = escapeHtml(JSON.stringify(entry));
                actions += '<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" data-queue-entry="' + serializedEntry + '" onclick="nViewQueueEntry(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-eye"></i> View</button>';
                if (q.status === 'pending') {
                    actions += '<button class="uh-btn uh-btn-primary uh-btn-xs uh-mr-4" data-queue-entry="' + serializedEntry + '" onclick="nConfirmAppt(' + q.id + ', JSON.parse(this.dataset.queueEntry))"><i class="fa fa-check"></i> Accept</button>';
                    actions += '<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" onclick="nDeclineAppt(' + q.id + ')"><i class="fa fa-xmark"></i> Decline</button>';
                }
                if (q.status === 'accepted') {
                    actions += '<button class="uh-btn uh-btn-primary uh-btn-xs uh-mr-4" data-queue-entry="' + serializedEntry + '" onclick="nScheduleAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-calendar-check"></i> Schedule</button>';
                }
                if (q.status === 'accepted' || q.status === 'confirmed') {
                    actions += '<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" data-queue-entry="' + serializedEntry + '" onclick="nReferAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-share-from-square"></i> Refer</button>';
                }
                if (q.status === 'accepted') {
                    actions += '<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" onclick="nCompleteAppt(' + Number(q.id) + ')"><i class="fa fa-check-double"></i> Done</button>';
                } else if (q.status === 'confirmed') {
                    var now = new Date();
                    var today = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-');
                    if (q.date && q.date <= today) {
                        actions += '<button class="uh-btn uh-btn-ghost uh-btn-xs uh-mr-4" onclick="nCompleteAppt(' + Number(q.id) + ')"><i class="fa fa-check-double"></i> Done</button>';
                    }
                }
            }
            return '<tr>' +
                '<td>' + (i + 1) + ' ' + typeBadge + '</td>' +
                '<td>' + name + '</td>' +
                '<td>' + sid + '</td>' +
                '<td>' + escapeHtml(reason) + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' + (actions || '<span class="uh-text-muted">—</span>') + '</td>' +
                '</tr>';
        }).join('');
    });
}

function nViewQueueEntry(entry) {
    var record = entry && typeof entry === 'object' ? entry : null;
    if (!record) {
        showToast('Queue item details are unavailable.', 'error');
        return;
    }

    var modal = document.getElementById('queueDetailModal');
    var body = document.getElementById('queueDetailBody');
    if (!modal || !body) {
        if (record.student_id) {
            var studentNo = String(record.student_id).replace(/[^A-Za-z0-9\-_]/g, '');
            if (studentNo) {
                uhViewStudentHealthRecord(studentNo);
                return;
            }
        }
        showToast('Queue item details are unavailable.', 'error');
        return;
    }

    var patientName = [record.first_name, record.middle_name, record.last_name].filter(Boolean).join(' ') || 'Unknown patient';
    var submittedDate = record.created_at ? uhFormatDateTime(record.created_at) : (record.date ? uhFormatDate(record.date) : '—');
    var rows = [
        ['Patient', patientName],
        ['Student ID', record.student_id || '—'],
        ['Record type', record.type === 'visit' ? 'Walk-in' : 'Appointment'],
        ['Service type', record.service_type || (record.type === 'visit' ? 'Walk-in' : '—')],
        ['Date submitted', submittedDate],
        ['Campus', record.campus_name || '—'],
        ['Status', record.status || 'pending'],
        ['Reason for visit', record.type === 'visit' ? record.detail || '—' : record.reason || '—'],
        ['Overdue', record.is_overdue ? 'Yes' : 'No']
    ];

    body.innerHTML = '<div class="uh-detail-grid">' + rows.map(function(row) {
        return '<div class="uh-detail-label">' + escapeHtml(row[0]) + '</div><div class="uh-detail-value">' + escapeHtml(row[1] == null ? '—' : String(row[1])) + '</div>';
    }).join('') + '</div>';
    modal.classList.remove('hidden');

    var button = document.getElementById('queueDetailHealthRecord');
    if (button) {
        button.style.display = record.student_id ? 'inline-flex' : 'none';
        button.onclick = function() {
            if (record.student_id) {
                var studentNo = String(record.student_id).replace(/[^A-Za-z0-9\-_]/g, '');
                if (studentNo) { uhViewStudentHealthRecord(studentNo); }
            }
        };
    }
}

function fmtTimeForDisplay(raw) {
    return uhFormatTime(raw);
}

function nUpdateAppointmentStatus(id, payload, successMessage, errorMessage) {
    apiFetch('queue/appointment/' + id, 'PATCH', payload).then(function(res) {
        if (res && res.success) {
            uhToast(successMessage, 'success');
            nLoadQueue();
        } else {
            uhToast(res && res.message ? res.message : errorMessage, 'error');
        }
    });
}

async function nDeclineAppt(id) {
    var reason = await uhPrompt('Enter the reason for declining this appointment.', {
        title: 'Decline appointment?',
        label: 'Reason for decline',
        placeholder: 'Provide a reason',
        confirmText: 'Decline appointment',
        cancelText: 'Keep appointment',
        maxLength: 1000,
        rows: 4,
        danger: true
    });

    if (reason === null) { return; }

    nUpdateAppointmentStatus(id, {
        status: 'cancelled',
        cancellation_reason: reason.trim()
    }, 'Appointment declined. Reason recorded.', 'Failed to decline appointment.');
}

function nCompleteAppt(id) {
    nUpdateAppointmentStatus(id, { status: 'completed' }, 'Marked as completed', 'Failed to update appointment.');
}

// ΓöÇΓöÇ Stats ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
function nLoadStats() {
    loadStats();
}

// ΓöÇΓöÇ Bar Chart ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
function nRenderBarChart() {
    var chart  = document.getElementById('nRptBarChart');
    var labels = document.getElementById('nRptBarLabels');
    if (!chart) return;
    var days   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    var values = [5, 8, 12, 7, 10, 3, 1];
    var max    = Math.max.apply(null, values);
    chart.innerHTML = values.map(function(v, i) {
        return '<div class="uh-rpt-bar-group">' +
            '<span class="uh-rpt-bar-value">' + v + '</span>' +
            '<div class="uh-rpt-bar' + (i === 4 ? ' highlight' : '') + '" title="' + days[i] + ': ' + v + ' visits" style="height:' + Math.round((v / max) * 100) + 'px"></div>' +
            '</div>';
    }).join('');
    if (labels) labels.innerHTML = days.map(function(d) {
        return '<span class="uh-bar-label-day">' + d + '</span>';
    }).join('');
}

// -- Messages (DM) -------------------------------------------------------
var _activeChannelId = null;
var _staffList = [];
var _staffListLoaded = false;
var _allConversations = [];
var _activeMessages = [];
var _messageCache = new Map();
var _messagePollTimer = null;
var _messagePollBusy = false;
var _messageActionBusy = false;
var _channelLoadBusy = false;
var _messageLastSync = null;
var _channelRequestToken = 0;
var _activeRecipientId = null;
var _activeRecipientName = '';
var _presencePollTimer = null;

// Avatar initials + colour
function uhChatInitials(name) {
    var parts = (name || '?').trim().split(' ');
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}
var _avatarColors = ['color-1','color-2','color-3','color-4','color-5','color-6'];
function uhChatColor(id) { return _avatarColors[(id || 0) % _avatarColors.length]; }
function nChatPictureUrl(id) {
    var staff = _staffList.find(function (person) { return person.id === id; });
    var conversation = _allConversations.find(function (item) { return item.recipient_id === id; });
    return (staff && staff.profile_picture_url) || (conversation && conversation.recipient_picture_url) || null;
}

function nChatPresenceFor(id) {
    return _allConversations.find(function (conversation) {
        return Number(conversation.recipient_id) === Number(id);
    }) || _staffList.find(function (staff) {
        return Number(staff.id) === Number(id);
    }) || null;
}

function nUpdateActivePresence() {
    var activeConversation = _allConversations.find(function (conversation) {
        return Number(conversation.id) === Number(_activeChannelId);
    });
    if (activeConversation && activeConversation.recipient_name !== _activeRecipientName) {
        _activeRecipientName = activeConversation.recipient_name;
        var nameEl = document.getElementById('nChatHeaderName');
        var avatar = document.getElementById('nChatHeaderAvatar');
        if (nameEl) nameEl.textContent = _activeRecipientName;
        if (avatar) {
            avatar.innerHTML = uhChatAvatarContent(nChatPictureUrl(_activeRecipientId), uhChatInitials(_activeRecipientName));
        }
    }
    if (_activeRecipientId !== null && _activeRecipientId !== undefined) {
        uhSetChatPresence(nChatPresenceFor(_activeRecipientId));
    }
}

function nMessagesInit() {
    nLoadConversations();
    if (!_staffListLoaded) {
        uhGet('/messages/staff-list').then(function(res) {
            if (!res.ok) { showToast(res.error || 'Could not load staff list.', 'error'); return; }
            _staffList = (res && res.staff) ? res.staff : [];
            _staffListLoaded = true;
            nUpdateActivePresence();
        }).catch(function() { showToast('Could not load staff list. Check your connection and try again.', 'error'); });
    }
    nBindConversationClicks();
    nBindMessageActions();
    if (!_messagePollTimer) {
        _messagePollTimer = window.setInterval(nSyncMessages, 4000);
    }
    if (!_presencePollTimer) {
        _presencePollTimer = window.setInterval(function () {
            var page = document.getElementById('page-messages');
            if (document.visibilityState === 'visible' && page && page.classList.contains('active')) {
                nLoadConversations();
            }
        }, 30000);
    }
}

function nOpenNewMessage() {
    var wrap = document.getElementById('nUserPickerWrap');
    var picker = document.getElementById('nUserPicker');
    if (!wrap || !picker) return;
    if (!_staffListLoaded) { showToast('Staff list is still loading. Please try again in a moment.', 'info'); return; }
    var user = window.__clinicUser || {};
    picker.innerHTML = '<option value="">-- Select a person --</option>' +
        _staffList.filter(function(s){ return s.id !== (user.id||0); })
            .map(function(s){
                return '<option value="'+Number(s.id)+'">'+uhEscapeHtml(s.name)+' ('+uhEscapeHtml(s.role_label)+')</option>';
            }).join('');
    wrap.style.display = 'block';
}
function nCloseUserPicker() {
    var w = document.getElementById('nUserPickerWrap');
    if (w) w.style.display = 'none';
}
function nStartDm() {
    var picker = document.getElementById('nUserPicker');
    var recipientId = picker ? parseInt(picker.value, 10) : 0;
    if (!recipientId) { showToast('Please select a person', 'error'); return; }
    var user = window.__clinicUser || {};
    uhPost('/messages/direct', { sender_id: user.id||0, recipient_id: recipientId })
        .then(function(res) {
            if (!res || !res.success) { showToast('Could not open conversation', 'error'); return; }
            nCloseUserPicker();
            _channelRequestToken += 1;
            nOpenChannel(res.channel.id, res.channel.recipient_name, res.channel.recipient_id, res.messages, res.channel.recipient_picture_url);
            nLoadConversations();
        }).catch(function(){ showToast('Could not open conversation', 'error'); });
}

function nLoadConversations() {
    var user = window.__clinicUser || {};
    if (!user.id) return;
    uhGet('/messages/conversations?staff_id=' + user.id).then(function(res) {
        if (!res.ok) { showToast(res.error || 'Could not load conversations. Please try again.', 'error'); return; }
        _allConversations = (res && res.conversations) ? res.conversations : [];
        nRenderDashboardMessages(_allConversations);
        var search = (document.getElementById('nConvSearch')?.value || '').trim().toLowerCase();
        var visibleConversations = search
            ? _allConversations.filter(function (conversation) {
                return conversation.recipient_name.toLowerCase().indexOf(search) >= 0;
            })
            : _allConversations;
        nRenderConversations(visibleConversations);
        nUpdateActivePresence();
    }).catch(function() { showToast('Could not load conversations. Check your connection and try again.', 'error'); });
}

function nRenderDashboardMessages(conversations) {
    var container = document.getElementById('nurse-dashboard-messages');
    if (!container) return;

    var recent = conversations.filter(function(conversation) {
        return conversation.last_message;
    }).slice(0, 4);
    if (!recent.length) {
        container.innerHTML = '<p class="student-empty-state">No messages yet.</p>';
        return;
    }

    var userId = Number((window.__clinicUser || {}).id);
    container.innerHTML = recent.map(function(conversation) {
        var isNew = Number(conversation.last_message_sender_id) === Number(conversation.recipient_id)
            && !conversation.last_message_seen_at;
        var sender = Number(conversation.last_message_sender_id) === userId ? 'You' : conversation.recipient_name;
        var preview = conversation.last_message.length > 54
            ? conversation.last_message.slice(0, 51) + '...'
            : conversation.last_message;
        var newLabel = isNew ? '<span class="nurse-dashboard-message-new">New message</span>' : '';
        return '<button type="button" class="nurse-dashboard-message" onclick="nOpenDashboardConversation(' +
            Number(conversation.id) + ', ' + Number(conversation.recipient_id) + ')">' +
            '<span class="nurse-dashboard-message-icon"><i class="fa fa-user"></i></span>' +
            '<span class="nurse-dashboard-message-content"><span class="nurse-dashboard-message-sender">' +
            uhEscapeHtml(sender) + '</span><span class="nurse-dashboard-message-preview">' +
            uhEscapeHtml(preview) + '</span></span>' + newLabel + '</button>';
    }).join('');
}

function nOpenDashboardConversation(channelId, recipientId) {
    var conversation = _allConversations.find(function(item) {
        return Number(item.id) === Number(channelId);
    });
    nShowMessages();
    nSelectConversation(channelId, conversation ? conversation.recipient_name : 'Conversation', recipientId);
}

function nShowMessages() {
    uhShowPage('messages', document.querySelector('[data-title="Messages"]'));
    nMessagesInit();
}

function nFilterConversations(q) {
    var filtered = !q ? _allConversations :
        _allConversations.filter(function(c){ return c.recipient_name.toLowerCase().indexOf(q.toLowerCase()) >= 0; });
    nRenderConversations(filtered);
}
function nRenderConversations(convs) {
    var list = document.getElementById('conversationsList');
    if (!list) return;
    if (!convs.length) {
        list.innerHTML = '<p class="uh-chat-empty" style="padding:20px 14px">No conversations yet.<br>Click <b>New Message</b> to start one.</p>';
        return;
    }
    list.innerHTML = convs.map(function(c) {
        var initials = uhChatInitials(c.recipient_name);
        var colorCls = uhChatColor(c.recipient_id);
        var activeCls = _activeChannelId === c.id ? ' active' : '';
        var preview = c.last_message ? c.last_message.substring(0,40)+(c.last_message.length>40?'...':'') : 'No messages yet';
        var timeStr = c.last_at ? uhFormatTime(c.last_at) : '';
        var presenceText = uhPresenceText(c);
        var presenceClass = c.is_online ? ' is-online' : ' is-offline';
        var openAttributes = ' data-message-conversation data-recipient-id="'+Number(c.recipient_id)+'"';
        var menuActions = '<button type="button" data-conversation-action="conversation">Delete conversation</button><button type="button" data-conversation-action="person">Delete person</button>';
        return '<div class="uh-conv-item'+activeCls+'" data-channel-id="'+Number(c.id)+'">' +
            '<button type="button" class="uh-conv-open"'+openAttributes+' data-channel-id="'+Number(c.id)+'" data-recipient-name="'+uhEscapeHtml(c.recipient_name)+'" aria-label="Open conversation with '+uhEscapeHtml(c.recipient_name)+'">' +
            '<div class="uh-conv-avatar ' + colorCls + '">' + uhChatAvatarContent(c.recipient_picture_url, initials) + '</div>' +
            '<div class="uh-conv-info">' +
              '<div class="uh-conv-name">'+uhEscapeHtml(c.recipient_name)+'</div>' +
              '<span class="uh-conv-preview">'+uhEscapeHtml(preview)+'</span>' +
              '<span class="uh-conv-presence'+presenceClass+'">'+uhEscapeHtml(presenceText)+'</span>' +
            '</div>' +
            '<div class="uh-conv-meta"><span class="uh-conv-time">'+timeStr+'</span></div>' +
            '</button><div class="uh-conv-actions"><button type="button" class="uh-conv-menu-toggle" aria-label="Conversation options" aria-expanded="false"><i class="fa fa-ellipsis" aria-hidden="true"></i></button>' +
            '<div class="uh-conv-menu" hidden>'+menuActions+'</div></div>' +
            '</div>';
    }).join('');
}

function nSelectConversation(channelId, recipientName, recipientId) {
    var requestToken = ++_channelRequestToken;
    var cachedMessages = _messageCache.get(Number(channelId));
    _channelLoadBusy = true;
    nShowConversationLoading(channelId, recipientName, recipientId);
    if (cachedMessages) nRenderMessages(cachedMessages);
    uhGet('/messages/' + channelId).then(function(res) {
        if (!res.ok) {
            if (requestToken === _channelRequestToken && !cachedMessages) {
                nConversationLoadFailed();
            }
            if (requestToken === _channelRequestToken) {
                showToast(res.error || 'Could not open this conversation.', 'error');
            }
            return;
        }
        if (requestToken !== _channelRequestToken) return;
        var currentName = typeof res.channel_name === 'string' && res.channel_name.trim()
            ? res.channel_name
            : recipientName;
        nOpenChannel(channelId, currentName, recipientId, res.messages || [], nChatPictureUrl(recipientId));
    }).catch(function() {
        if (requestToken === _channelRequestToken) {
            if (!cachedMessages) nConversationLoadFailed();
            showToast('Could not open this conversation. Check your connection and try again.', 'error');
        }
    }).finally(function() {
        if (requestToken === _channelRequestToken || requestToken + 1 === _channelRequestToken) {
            _channelLoadBusy = false;
        }
    });
}
function nShowConversationLoading(channelId, name, recipientId) {
    _activeChannelId = channelId;
    _activeRecipientId = recipientId;
    _activeRecipientName = name;
    _activeMessages = [];
    _messageLastSync = null;
    uhResetConversationSearch('nChatSearchInput');
    uhClearMessageImage('nMessageImageInput', 'nMessageImagePreview', 'nMessageImageThumbnail', 'nMessageImageName');
    var header = document.getElementById('nChatHeader');
    var avatar = document.getElementById('nChatHeaderAvatar');
    var nameEl = document.getElementById('nChatHeaderName');
    var placeholder = document.getElementById('nChatPlaceholder');
    var list = document.getElementById('messagesList');
    var compose = document.getElementById('nChatCompose');
    if (header) header.style.display = 'flex';
    if (avatar) {
        avatar.innerHTML = uhChatAvatarContent(nChatPictureUrl(recipientId), uhChatInitials(name));
        avatar.className = 'uh-chat-active-avatar ' + uhChatColor(recipientId);
    }
    if (nameEl) nameEl.textContent = name;
    if (placeholder) placeholder.style.display = 'none';
    if (list) {
        list.style.display = 'flex';
        list.innerHTML = '<p class="uh-chat-empty uh-chat-loading" role="status">Loading messages…</p>';
    }
    document.querySelectorAll('#conversationsList [data-message-conversation]').forEach(function(item) {
        item.closest('.uh-conv-item')?.classList.toggle('active', Number(item.dataset.channelId) === Number(channelId));
    });
    if (compose) compose.style.display = 'flex';
}
function nConversationLoadFailed() {
    var list = document.getElementById('messagesList');
    if (list) list.innerHTML = '<div class="uh-chat-load-error" role="alert">Could not load this conversation.<button type="button" class="uh-btn uh-btn-outline uh-btn-sm" onclick="nRetryConversation()">Try again</button></div>';
}
function nRetryConversation() {
    if (_activeChannelId) nSelectConversation(_activeChannelId, _activeRecipientName, _activeRecipientId);
}
function nBindConversationClicks() {
    var list = document.getElementById('conversationsList');
    if (!list || list.dataset.conversationClicksBound) return;
    list.dataset.conversationClicksBound = 'true';
    list.addEventListener('click', function(event) {
        var action = event.target.closest('[data-conversation-action]');
        if (action) {
            var actionRow = action.closest('.uh-conv-item');
            action.closest('.uh-conv-menu').hidden = true;
            actionRow.querySelector('.uh-conv-menu-toggle')?.setAttribute('aria-expanded', 'false');
            nDeleteConversationAction(
                Number(actionRow.dataset.channelId),
                action.dataset.conversationAction,
                actionRow.querySelector('.uh-conv-name')?.textContent || 'this person',
            );
            return;
        }
        var menuToggle = event.target.closest('.uh-conv-menu-toggle');
        if (menuToggle) {
            var menu = menuToggle.nextElementSibling;
            var opening = menu.hidden;
            list.querySelectorAll('.uh-conv-menu').forEach(function(item) { item.hidden = true; });
            list.querySelectorAll('.uh-conv-menu-toggle').forEach(function(item) {
                item.setAttribute('aria-expanded', 'false');
            });
            menu.hidden = !opening;
            menuToggle.setAttribute('aria-expanded', String(opening));
            return;
        }
        var button = event.target.closest('[data-message-conversation]');
        if (!button) return;
        nSelectConversation(Number(button.dataset.channelId), button.dataset.recipientName, Number(button.dataset.recipientId));
    });
}
async function nDeleteConversationAction(channelId, action, recipientName) {
    if (!await uhDeleteConversationItem(channelId, action, recipientName)) return;
    _messageCache.delete(channelId);
    if (Number(_activeChannelId) === channelId) {
        _channelRequestToken += 1;
        _activeChannelId = null;
        _activeRecipientId = null;
        _activeRecipientName = '';
        _activeMessages = [];
        document.getElementById('nChatHeader').style.display = 'none';
        document.getElementById('nChatPlaceholder').style.display = '';
        document.getElementById('messagesList').style.display = 'none';
        document.getElementById('nChatCompose').style.display = 'none';
    }
    nLoadConversations();
}

function nOpenChannel(channelId, name, recipientId, messages, pictureUrl) {
    _channelRequestToken += 1;
    _activeChannelId = channelId;
    _channelLoadBusy = false;
    _activeRecipientId = recipientId;
    _activeRecipientName = name;
    _messageLastSync = new Date(Date.now() - 5000).toISOString();
    // Show header
    var header = document.getElementById('nChatHeader');
    var avatar = document.getElementById('nChatHeaderAvatar');
    var nameEl = document.getElementById('nChatHeaderName');
    if (header) header.style.display = 'flex';
    if (avatar)      { avatar.innerHTML = uhChatAvatarContent(pictureUrl || nChatPictureUrl(recipientId), uhChatInitials(name)); avatar.className = 'uh-chat-active-avatar ' + uhChatColor(recipientId); }
    if (nameEl) nameEl.textContent = name;
    uhSetChatPresence(nChatPresenceFor(recipientId));
    // Show chat area, hide placeholder
    var placeholder = document.getElementById('nChatPlaceholder');
    var msgList = document.getElementById('messagesList');
    var compose = document.getElementById('nChatCompose');
    if (placeholder) placeholder.style.display = 'none';
    if (msgList)     msgList.style.display = 'flex';
    if (compose)     compose.style.display = 'flex';
    nRenderMessages(messages || []);
}
function nRenderMessages(msgs) {
    var list = document.getElementById('messagesList');
    if (!list) return;
    var user = window.__clinicUser || {};
    _activeMessages = Array.isArray(msgs) ? msgs : [];
    if (_activeChannelId) uhRememberChatMessages(_messageCache, _activeChannelId, _activeMessages);
    var searchInput = document.getElementById('nChatSearchInput');
    var searchTerm = searchInput ? searchInput.value.trim() : '';
    var visibleMessages = uhFilterConversationMessages(_activeMessages, searchTerm);
    if (!visibleMessages.length) {
        list.innerHTML = _activeMessages.length && searchTerm
            ? '<p class="uh-chat-empty">No messages match your search.</p>'
            : '<p class="uh-chat-empty">No messages yet. Say hello!</p>';
        return;
    }
    list.innerHTML = uhMessageMarkup(visibleMessages, user.id, uhChatInitials, uhChatColor);
    list.scrollTop = searchTerm ? 0 : list.scrollHeight;
}
function nLoadMessages() {
    if (!_activeChannelId) return Promise.resolve();
    var channelId = _activeChannelId;
    return uhGet('/messages/' + channelId).then(function(res) {
        if (!res.ok) {
            showToast(res.error || 'Could not load messages. Please try again.', 'error');
            return;
        }
        if (_activeChannelId === channelId) nRenderMessages(res.messages || []);
    }).catch(function() {
        showToast('Could not load messages. Check your connection and try again.', 'error');
    });
}
function nSyncMessages() {
    var page = document.getElementById('page-messages');
    if (!_activeChannelId || !page || !page.classList.contains('active') || _messagePollBusy || _messageActionBusy || _channelLoadBusy) return;
    _messagePollBusy = true;
    var channelId = _activeChannelId;
    var list = document.getElementById('messagesList');
    var previousTop = list ? list.scrollTop : 0;
    var wasNearBottom = list ? list.scrollHeight - list.scrollTop - list.clientHeight < 80 : true;
    var changedAfter = _messageLastSync || new Date(Date.now() - 5000).toISOString();
    uhGet('/messages/' + channelId + '?changed_after=' + encodeURIComponent(changedAfter)).then(function(res) {
        if (!res.ok || _activeChannelId !== channelId) return;
        var messagesById = new Map(_activeMessages.map(function(message) { return [Number(message.id), message]; }));
        var changed = false;
        (res.messages || []).forEach(function(message) {
            var current = messagesById.get(Number(message.id));
            if (!current || current.updated_at !== message.updated_at || current.body !== message.body
                || current.is_pinned !== message.is_pinned || current.is_unsent !== message.is_unsent) {
                messagesById.set(Number(message.id), message);
                changed = true;
            }
        });
        if (changed) {
            var combined = Array.from(messagesById.values()).sort(function(left, right) { return Number(left.id) - Number(right.id); });
            nRenderMessages(combined);
            if (!wasNearBottom && list) list.scrollTop = previousTop;
            nLoadConversations();
        }
        _messageLastSync = new Date().toISOString();
    }).catch(function() {
        // Keep the last rendered messages available; the next poll retries.
    }).finally(function() {
        _messagePollBusy = false;
    });
}
function nBindMessageActions() {
    var list = document.getElementById('messagesList');
    if (!list || list.dataset.actionsBound) return;
    list.dataset.actionsBound = 'true';
    list.addEventListener('click', async function(event) {
        var button = event.target.closest('button[data-message-action]');
        if (!button) return;
        var action = button.dataset.messageAction;
        if (action === 'toggle') {
            var wasOpen = button.getAttribute('aria-expanded') === 'true';
            list.querySelectorAll('.uh-msg-menu').forEach(function(menu) { menu.hidden = true; });
            list.querySelectorAll('.uh-msg-more').forEach(function(toggle) { toggle.setAttribute('aria-expanded', 'false'); });
            if (!wasOpen) {
                var menu = button.parentElement.querySelector('.uh-msg-menu');
                if (menu) { menu.hidden = false; button.setAttribute('aria-expanded', 'true'); }
            }
            return;
        }
        if (_messageActionBusy || _channelLoadBusy) return;
        var message = _activeMessages.find(function(item) { return Number(item.id) === Number(button.dataset.messageId); });
        if (!message) { showToast('This message is no longer available.', 'error'); return; }
        _messageActionBusy = true;
        try {
            await uhPerformMessageAction(action, message, {
                channelId: _activeChannelId,
                userId: (window.__clinicUser || {}).id,
                staffList: function() { return _staffList; },
                refresh: nLoadMessages,
                loadConversations: nLoadConversations,
            });
        } catch (error) {
            showToast('Could not complete that message action. Please try again.', 'error');
        } finally {
            _messageActionBusy = false;
        }
    });
}
function nSendMessage() {
    if (_channelLoadBusy) { showToast('Please wait for the conversation to finish loading.', 'info'); return; }
    if (!_activeChannelId) { showToast('Select a conversation first', 'error'); return; }
    var channelId = _activeChannelId;
    var input = document.getElementById('msgInput');
    var text = input ? input.value.trim() : '';
    var attachmentInput = document.getElementById('nMessageImageInput');
    var attachment = attachmentInput && attachmentInput.files ? attachmentInput.files[0] : null;
    if (!text && !attachment) return;
    uhPostMessage('/messages/' + channelId, text, attachment)
        .then(function(res) {
            if (res && res.ok && res.message) {
                input.value = '';
                uhClearMessageImage('nMessageImageInput', 'nMessageImagePreview', 'nMessageImageThumbnail', 'nMessageImageName');
                if (_activeChannelId === channelId) {
                    _activeMessages.push(res.message);
                    nRenderMessages(_activeMessages);
                }
                nLoadConversations();
            } else {
                var validationMessage = res.errors && Object.values(res.errors).flat()[0];
                showToast(res.error || validationMessage || res.message || 'Failed to send. Please try again.', 'error');
            }
        }).catch(function(error){ showToast(error.message || 'Failed to send. Check your connection and try again.', 'error'); });
}


// ── Init ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Set today on encounter datetime
    var encDt = document.getElementById('enc_datetime');
    if (encDt) {
        var now = new Date();
        encDt.value = now.toISOString().slice(0, 16);
    }
    var vitDate = document.getElementById('vitals_date');
    if (vitDate && !vitDate.value) {
        vitDate.value = new Date().toISOString().split('T')[0];
    }

    nRenderBarChart();
    nLoadQueue();
    nMessagesInit();
    // Analytics
    ClinicAnalytics.applyDefaults();
    ClinicAnalytics.loadNurseAnalytics('nan');
});
</script>

</body>
</html>