@php
    $isEmployeeDashboard = request()->routeIs('clinic.faculty', 'clinic.staff', 'clinic.instructor');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isEmployeeDashboard ? 'Employee Wellness Dashboard' : 'Student Dashboard' }} — SUNN Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/unihealth.css') }}?v={{ filemtime(public_path('clinic/css/unihealth.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/student-dashboard-reference.css') }}?v={{ filemtime(public_path('clinic/css/student-dashboard-reference.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/consultation-workflow.css') }}?v={{ filemtime(public_path('clinic/css/consultation-workflow.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/analytics.css') }}?v={{ filemtime(public_path('clinic/css/analytics.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/dashboard-card-theme.css') }}?v={{ filemtime(public_path('clinic/css/dashboard-card-theme.css')) }}"/>
    <script>
        (function () {
            var raw = sessionStorage.getItem('clinic_user');
            try { window.__clinicUser = raw ? JSON.parse(raw) : null; } catch (e) { window.__clinicUser = null; }
        }());
    </script>
</head>
<body class="student-dashboard dashboard-card-theme" data-clinic-role="{{ $isEmployeeDashboard ? 'employee' : 'student' }}">

<div class="uh-layout">

    <!-- -- Sidebar ----------------------------------- -->
    <x-clinic.sidebar :role="$isEmployeeDashboard ? 'employee' : 'student'">
        <button class="uh-nav-item active" onclick="uhShowPage('dashboard',this)" data-title="Dashboard">
            <i class="fa fa-house-medical"></i><span>Dashboard</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('book-appointment',this)" data-title="Book Appointment">
            <i class="fa fa-calendar-plus"></i><span>Book Appointment</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('my-appointments',this)" data-title="My Appointments">
            <i class="fa fa-calendar-check"></i><span>My Appointments</span>
        </button>
        <button class="uh-nav-item" onclick="cwOpenConsultations()" data-title="Request Consultation">
            <i class="fa fa-comment-medical"></i><span>Request Consultation</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('health-record',this)" data-title="{{ $isEmployeeDashboard ? 'Personal Health Record' : 'Health Record' }}">
            <i class="fa fa-clipboard-list"></i><span>{{ $isEmployeeDashboard ? 'Personal Health Record' : 'Health Record' }}</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('prescriptions',this)" data-title="Prescriptions">
            <i class="fa fa-pills"></i><span>Prescriptions</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('referrals',this)" data-title="Referrals &amp; Status" id="nav-referrals">
            <i class="fa fa-share-from-square"></i><span>Referrals &amp; Status</span>
        </button>
    </x-clinic.sidebar>

    <!-- -- Main -------------------------------------- -->
    <div class="uh-main">

        <x-clinic.topbar :initial-role="$isEmployeeDashboard ? 'Employee' : 'Patient / Student'" />

        <div class="uh-content">

            <!-- -- PAGE: DASHBOARD ------------------- -->
            <div class="uh-page active" id="page-dashboard">
                @if ($isEmployeeDashboard)
                    <section class="uh-card uh-mb-20">
                        <h2 class="uh-card-title">Employee Wellness Dashboard</h2>
                        <p class="uh-card-subtitle">Manage your clinic visits, prescriptions, referrals, and personal health information.</p>
                    </section>
                @endif
                <div class="uh-stats-grid uh-mb-20">
                    <div class="uh-stat-card" onclick="uhShowPage('my-appointments',document.querySelector('[data-title=\'My Appointments\']'))">
                        <div class="uh-stat-icon blue"><i class="fa fa-calendar-check"></i></div>
                        <div class="uh-stat-number" id="s-stat-next-appt">—</div>
                        <div class="uh-stat-label">Upcoming visits</div>
                        <div class="uh-stat-trend" id="s-stat-appt-date"><i class="fa fa-clock"></i> Scheduled or awaiting review</div>
                    </div>
                    <div class="uh-stat-card" onclick="uhShowPage('prescriptions',document.querySelector('[data-title=\'Prescriptions\']'))">
                        <div class="uh-stat-icon green"><i class="fa fa-hourglass-half"></i></div>
                        <div class="uh-stat-number" id="s-stat-rx">0</div>
                        <div class="uh-stat-label">Active prescriptions</div>
                        <div class="uh-stat-trend">Currently active in your record.</div>
                    </div>
                    <div class="uh-stat-card" onclick="uhShowPage('referrals',document.getElementById('nav-referrals'))">
                        <div class="uh-stat-icon amber"><i class="fa fa-circle-check"></i></div>
                        <div class="uh-stat-number" id="s-stat-referrals">0</div>
                        <div class="uh-stat-label">Open referrals</div>
                        <div class="uh-stat-trend">Pending clinic referrals.</div>
                    </div>

                    <div class="uh-stat-card" onclick="uhShowPage('referrals',document.getElementById('nav-referrals'))">
                        <div class="uh-stat-icon teal"><i class="fa fa-calendar-arrow-up"></i></div>
                        <div class="uh-stat-number" id="s-stat-next-referral">—</div>
                        <div class="uh-stat-label">Next referral appt.</div>
                        <div class="uh-stat-trend" id="s-stat-next-referral-label"><i class="fa fa-clock"></i> Awaiting specialist</div>
                    </div>
                </div>

                <div class="student-dashboard-grid">
                    <section class="student-panel student-appointments-panel"><div class="student-panel-heading"><h3><i class="fa fa-calendar-days"></i> My Appointments</h3><button type="button" onclick="uhShowPage('my-appointments',document.querySelector('[data-title=\'My Appointments\']'))">View All <i class="fa fa-arrow-right"></i></button></div><div id="s-upcoming-appt"><p class="student-empty-state">Loading appointments...</p></div></section>
                    <section class="student-panel"><div class="student-panel-heading"><h3><i class="fa fa-bolt"></i> Quick Actions</h3></div><div class="student-action-grid"><button type="button" onclick="uhShowPage('book-appointment',document.querySelector('[data-title=\'Book Appointment\']'))"><i class="fa fa-calendar-plus"></i><span>Book a Clinic Visit</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('prescriptions',document.querySelector('[data-title=\'Prescriptions\']'))"><i class="fa fa-pills"></i><span>Request Refill</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('medical-records',document.querySelector('[data-title=\'Medical Records\']'))"><i class="fa fa-flask"></i><span>View Medical Records</span><i class="fa fa-chevron-right"></i></button><button type="button" onclick="uhShowPage('notifications',document.getElementById('nav-notifications'))"><i class="fa fa-comment-dots"></i><span>Contact the Clinic</span><i class="fa fa-chevron-right"></i></button></div></section>
                </div>
                <div class="student-dashboard-grid student-lower-grid"><section class="student-panel student-trends-panel"><div class="student-panel-heading"><h3><i class="fa fa-chart-line"></i> Appointment Trends</h3><button type="button" onclick="uhShowPage('my-appointments',document.querySelector('[data-title=\'My Appointments\']'))">View All <i class="fa fa-arrow-right"></i></button></div><div class="student-chart-title">Appointments over the last 30 days</div><div class="student-chart" id="s-appointment-chart" role="img" aria-label="Appointments over the last 30 days"></div></section><section class="student-panel"><div class="student-panel-heading"><h3><i class="fa fa-building"></i> Clinic Information</h3></div><div class="student-clinic-image"><img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH clinic logo"><span>UNIHEALTH</span></div><div class="student-clinic-meta"><strong><i class="fa fa-location-dot"></i> <span id="s-campus-badge-secondary">Loading campus...</span></strong><small>Clinic Wellness Center</small><strong><i class="fa fa-clock"></i> <span id="s-clinic-today-status" class="student-clinic-status" aria-live="polite">Loading today’s schedule...</span></strong></div><button type="button" class="student-clinic-link" onclick="uhShowPage('book-appointment',document.querySelector('[data-title=\'Book Appointment\']'))">View Clinic Details <i class="fa fa-arrow-right"></i></button></section></div>
                <section class="nh-pm-decision-card student-insights-card" aria-labelledby="sta-decision-support-title">
                    <div class="nh-ds-header">
                        <div>
                            <div class="nh-ds-title" id="sta-decision-support-title">
                                <i class="fa fa-heart-pulse" aria-hidden="true"></i>
                                My Clinic Overview
                            </div>
                            <div class="nh-ds-subtitle">Personal appointment and record reminders</div>
                        </div>
                        <span class="nh-ds-badge">PRIVATE TO YOU</span>
                    </div>
                    <div class="nh-ds-insights" id="sta-decision-support-insights" aria-live="polite">
                        <div class="nh-ds-state">Loading your clinic information…</div>
                    </div>
                    <div class="nh-ds-disclaimer">Personal record summary only; not a diagnosis or medical advice.</div>
                </section>
                <div id="s-recent-notifs" class="student-hidden-notifications" aria-hidden="true"></div>
            </div>

            <!-- -- PAGE: BOOK APPOINTMENT ----------- -->
            <div class="uh-page" id="page-book-appointment">
                <div class="student-booking-layout">
                    <section class="student-booking-card">
                        <div class="student-booking-header">
                            <div class="student-booking-heading">
                                <div class="student-booking-icon"><i class="fa fa-calendar-plus" aria-hidden="true"></i></div>
                                <div>
                                    <span class="student-booking-eyebrow">{{ $isEmployeeDashboard ? 'Employee clinic services' : 'Student services' }}</span>
                                    <h2>Book an appointment</h2>
                                    <p>Send a request so the clinic team can arrange your check-up.</p>
                                </div>
                            </div>
                            <span class="student-booking-status"><i class="fa fa-circle" aria-hidden="true"></i> Online requests</span>
                        </div>
                        <div class="student-booking-steps" aria-label="Booking progress">
                            <span class="active"><b>1</b> Visit details</span>
                            <i aria-hidden="true"></i>
                            <span><b>2</b> Request review</span>
                        </div>
                        <form id="s-book-form" onsubmit="sBookAppointment(event)">
                            <div class="student-booking-section">
                                <div class="student-booking-section-title"><span>01</span><div><strong>What do you need?</strong><small>Select the service that best matches your visit.</small></div></div>
                                <div class="student-booking-field">
                                    <label for="s-svc-type">Service type <em>*</em></label>
                                    <div class="student-booking-input">
                                        <i class="fa fa-stethoscope" aria-hidden="true"></i>
                                        <select id="s-svc-type" required>
                                            <option value="">Select a service</option>
                                            <option>Medical Check-up</option>
                                            <option>Dental Check-up</option>
                                            <option>Medical Certificate</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="student-booking-section">
                                <div class="student-booking-section-title"><span>02</span><div><strong>Clinic check-up</strong><small>The clinic team will set the time for your initial check-up.</small></div></div>
                                <div class="student-booking-field">
                                    <label for="s-campus-display">Campus</label>
                                    <div class="student-booking-input">
                                        <i class="fa fa-location-dot" aria-hidden="true"></i>
                                        <input type="text" id="s-campus-display" readonly aria-readonly="true" value="Loading campus..." />
                                    </div>
                                </div>
                                <p class="student-booking-field-hint"><i class="fa fa-clock" aria-hidden="true"></i> After the check-up, the nurse will schedule any required physician or dentist appointment.</p>
                            </div>
                            <div class="student-booking-section">
                                <div class="student-booking-section-title"><span>03</span><div><strong>Share a little more</strong><small>This helps the clinic prepare for your visit.</small></div></div>
                                <div class="student-booking-field">
                                    <label for="s-reason">Reason for visit <span>(optional)</span></label>
                                    <textarea id="s-reason" rows="4" maxlength="500" placeholder="Briefly describe your concern or reason for visiting..."></textarea>
                                    <div class="student-booking-field-hint"><i class="fa fa-lock" aria-hidden="true"></i> Your information is kept confidential.</div>
                                </div>
                            </div>
                            <div class="student-booking-footer">
                                <p><i class="fa fa-circle-info" aria-hidden="true"></i> Your request is not a confirmed appointment. The clinic will contact you with your check-up schedule.</p>
                                <div class="student-booking-actions">
                                    <button type="button" class="uh-btn uh-btn-ghost" onclick="uhShowPage('dashboard',document.querySelector('.uh-nav-item'))">Cancel</button>
                                    <button type="submit" class="uh-btn uh-btn-primary" id="s-book-btn">
                                        <i class="fa fa-calendar-check" aria-hidden="true"></i> Request appointment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>
                    <aside class="student-booking-aside">
                        <div class="student-booking-aside-card student-booking-highlight">
                            <span class="student-booking-aside-icon"><i class="fa fa-heart-pulse" aria-hidden="true"></i></span>
                            <h3>Here when you need us</h3>
                            <p>Book a visit ahead of time so our clinic team can prepare for your concern.</p>
                        </div>
                        <div class="student-booking-aside-card">
                            <h3><i class="fa fa-clipboard-check" aria-hidden="true"></i> Before you request</h3>
                            <ul>
                                <li>Choose the service you need.</li>
                                <li>The nurse will schedule your clinic check-up.</li>
                                <li>After the check-up, the clinic will schedule the required provider visit.</li>
                            </ul>
                        </div>
                        <div class="student-booking-aside-card student-booking-hours">
                            <div><i class="fa fa-clock" aria-hidden="true"></i><span><strong>Clinic hours</strong><small>Monday – Friday</small></span></div>
                            <b>8:00 AM – 5:00 PM</b>
                        </div>
                        <div class="student-booking-preview" aria-live="polite">
                            <div class="student-booking-preview-top">
                                <span class="student-booking-preview-kicker"><i class="fa fa-sparkles" aria-hidden="true"></i> Your visit plan</span>
                                <span class="student-booking-preview-dot"></span>
                            </div>
                            <strong id="s-booking-preview-service">Choose a service to begin</strong>
                            <p id="s-booking-preview-detail">The clinic will arrange your initial check-up at your campus.</p>
                            <div class="student-booking-preview-line"><i class="fa fa-shield-heart" aria-hidden="true"></i> Prepared with care</div>
                        </div>
                    </aside>
                </div>
            </div>

            <!-- -- PAGE: MY APPOINTMENTS ------------ -->
            <div class="uh-page" id="page-my-appointments">
                <div class="student-appointments-page">
                    <section class="student-appointments-panel student-appointments-upcoming">
                        <div class="student-appointments-panel-header">
                            <div>
                                <h3>Upcoming appointments</h3>
                                <p>Your clinic check-up and provider-appointment stages appear here until the visit is completed.</p>
                            </div>
                            <span class="student-appointments-count" id="s-appt-upcoming-label">— visits</span>
                        </div>
                        <div id="s-upcoming-detail">
                            <div class="student-appointment-loading"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Loading your schedule...</div>
                        </div>
                    </section>

                    <section class="student-appointments-panel student-appointments-history">
                        <div class="student-appointments-panel-header student-appointments-history-header">
                            <div>
                                <span class="student-appointments-section-kicker"><i class="fa fa-clock-rotate-left" aria-hidden="true"></i> Your records</span>
                                <h3>Appointment history</h3>
                                <p>Completed appointments and requests you deleted are kept here for reference.</p>
                            </div>
                        </div>
                        <div class="student-appointments-table-meta">
                            <span>Past appointments</span>
                            <span class="uh-table-count" data-uh-table-count></span>
                        </div>
                        <div class="uh-table-toolbar">
                            <label class="student-appointments-search" aria-label="Search appointment history">
                                <i class="fa fa-search" aria-hidden="true"></i>
                                <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search history..." autocomplete="off">
                            </label>
                        </div>
                        <div class="uh-table-wrap student-appointments-table-wrap">
                            <table class="uh-table student-appointments-table" data-uh-searchable data-uh-render="sRenderPastAppointments">
                                <thead>
                                    <tr>
                                        <th><button type="button" class="uh-table-sort" onclick="sTogglePastAppointmentTimeSort()" aria-label="Sort appointment history by date and time">Date / time <span id="s-past-appts-sort-indicator">↓</span></button></th>
                                        <th>Type</th>
                                        <th>Healthcare Professional</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="s-past-appts">
                                    <tr><td colspan="4" class="no-data">Loading appointment history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="s-past-appts-pagination"></div>
                    </section>
                </div>
            </div>

            <!-- -- PAGE: HEALTH RECORD ------------- -->
            <div class="uh-page" id="page-health-record">
                <form id="s-hr-form" class="student-health-record-form" onsubmit="sSaveHealthRecord(event)">
                    <div class="uh-card student-health-record-card" style="max-width:860px;margin:0 auto;padding:0;border:2px solid #2d6a4f;border-radius:10px;overflow:hidden;font-size:.88rem;">

                        <!-- -- FORM HEADER -- -->
                        <div class="student-health-record-header" style="background:#2d6a4f;color:#fff;padding:18px 28px 14px;text-align:center;">
                            <div class="student-health-record-kicker">{{ $isEmployeeDashboard ? 'Employee wellness profile' : 'Student wellness profile' }}</div>
                            <div class="student-health-record-title" style="font-size:1.25rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;">Personal Health Record</div>
                            <div class="student-health-record-subtitle" style="font-size:.85rem;font-style:italic;opacity:.9;margin-top:2px;">{{ $isEmployeeDashboard ? 'For employees' : 'For students' }}</div>
                            <div class="student-health-record-note" style="font-size:.78rem;margin-top:8px;opacity:.82;line-height:1.6;">
                                &#x25BA; Complete the fields below &nbsp;&#x25BA; Keep your information up to date &nbsp;&#x25BA; Your record is <strong>CONFIDENTIAL</strong>
                            </div>
                        </div>

                        <div class="student-health-record-body" style="padding:20px 28px;background:#fff;">

                            <!-- A. GENERAL INFORMATION -->
                            <div class="student-health-section-title" style="background:#e8f5e9;border-left:4px solid #2d6a4f;padding:6px 12px;margin-bottom:14px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#1b4332;">
                                <span>A</span><div><strong>General Information</strong><small>Tell us about yourself and your current contact details.</small></div>
                            </div>

                            <div class="student-health-record-date" style="display:flex;justify-content:flex-end;margin-bottom:10px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <label style="font-weight:600;white-space:nowrap;">Date:</label>
                                    <input type="date" id="hr-record-date" max="{{ today()->toDateString() }}" style="border:none;border-bottom:1px solid #888;outline:none;padding:2px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                            </div>

                            <div class="student-health-course-row" style="display:flex;align-items:center;gap:8px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #ddd;flex-wrap:wrap;">
                                <label style="font-weight:600;white-space:nowrap;min-width:100px;">{{ $isEmployeeDashboard ? 'Department / Office:' : 'Course/Year:' }}</label>
                                <input type="text" id="hr-course-year" placeholder="{{ $isEmployeeDashboard ? 'Enter your department or office' : 'e.g. BSIT 2-A' }}" style="flex:1;min-width:120px;border:none;border-bottom:1px solid #aaa;outline:none;padding:2px 6px;font-size:.88rem;background:transparent;"/>
                                <div style="display:flex;gap:16px;border:1px solid #ccc;padding:4px 12px;border-radius:4px;background:#fafafa;flex-wrap:wrap;">
                                    <label style="font-weight:600;margin-right:8px;white-space:nowrap;">Sex:</label>
                                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-sex" value="Male" id="hr-sex-m" style="accent-color:#2d6a4f;"/> Male</label>
                                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-sex" value="Female" id="hr-sex-f" style="accent-color:#2d6a4f;"/> Female</label>
                                </div>
                                <div style="display:flex;gap:16px;border:1px solid #ccc;padding:4px 12px;border-radius:4px;background:#fafafa;flex-wrap:wrap;">
                                    <label style="font-weight:600;margin-right:8px;white-space:nowrap;">Civil Status:</label>
                                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-civil" value="Single" id="hr-civil-s" style="accent-color:#2d6a4f;"/> Single</label>
                                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-civil" value="Married" id="hr-civil-m" style="accent-color:#2d6a4f;"/> Married</label>
                                </div>
                            </div>

                            <div class="student-health-name-row" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:16px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #ddd;">
                                <div>
                                    <input type="text" id="hr-last-name" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:4px 4px 2px;font-size:.88rem;background:transparent;"/>
                                    <div style="font-size:.72rem;color:#666;text-align:center;margin-top:2px;">Last Name</div>
                                </div>
                                <div>
                                    <input type="text" id="hr-first-name" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:4px 4px 2px;font-size:.88rem;background:transparent;"/>
                                    <div style="font-size:.72rem;color:#666;text-align:center;margin-top:2px;">First Name</div>
                                </div>
                                <div>
                                    <input type="text" id="hr-middle-name" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:4px 4px 2px;font-size:.88rem;background:transparent;"/>
                                    <div style="font-size:.72rem;color:#666;text-align:center;margin-top:2px;">Middle Name</div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;min-width:130px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <label style="font-weight:600;font-size:.78rem;white-space:nowrap;">AGE:</label>
                                        <input type="number" id="hr-age" min="1" max="120" style="width:50px;border:none;border-bottom:1px solid #aaa;outline:none;padding:2px;font-size:.88rem;background:transparent;"/>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <label style="font-weight:600;font-size:.78rem;white-space:nowrap;">DOB:</label>
                                        <input type="date" id="hr-dob" max="{{ today()->toDateString() }}" style="border:none;border-bottom:1px solid #aaa;outline:none;padding:2px;font-size:.78rem;background:transparent;"/>
                                    </div>
                                </div>
                            </div>

                            <div class="student-health-contact-row" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #ddd;">
                                <div>
                                    <label style="font-weight:600;font-size:.78rem;">ADDRESS:</label>
                                    <input type="text" id="hr-address" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                                <div>
                                    <label style="font-weight:600;font-size:.78rem;">CONTACT #:</label>
                                    <input type="text" id="hr-contact" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                                <div>
                                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                        <label style="font-weight:600;font-size:.78rem;white-space:nowrap;">Blood Type:</label>
                                        <select id="hr-blood-type" style="border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px;">
                                            <option value="">—</option>
                                            <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                            <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                                        </select>
                                    </div>
                                    <div style="display:flex;gap:10px;">
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <label style="font-size:.78rem;font-weight:600;">Ht(cm):</label>
                                            <input type="number" id="hr-height" min="50" max="250" step="0.1" style="width:56px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px;"/>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <label style="font-size:.78rem;font-weight:600;">Wt(kg):</label>
                                            <input type="number" id="hr-weight" min="1" max="300" step="0.1" style="width:56px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px;"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- B. CONTACT PERSON -->
                            <div class="student-health-section-title" style="background:#e8f5e9;border-left:4px solid #2d6a4f;padding:6px 12px;margin:16px 0 14px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#1b4332;">
                                <span>B</span><div><strong>Contact Person</strong><small>Someone the clinic can reach in an emergency.</small></div>
                            </div>
                            <div class="student-health-emergency-row" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px;margin-bottom:10px;">
                                <div>
                                    <label style="font-weight:600;font-size:.78rem;">NAME:</label>
                                    <input type="text" id="hr-ec-name" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                                <div>
                                    <label style="font-weight:600;font-size:.78rem;">RELATIONSHIP:</label>
                                    <input type="text" id="hr-ec-rel" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                                <div>
                                    <label style="font-weight:600;font-size:.78rem;">CONTACT #:</label>
                                    <input type="text" id="hr-ec-contact" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                                </div>
                            </div>
                            <div style="margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #ddd;">
                                <label style="font-weight:600;font-size:.78rem;">ADDRESS:</label>
                                <input type="text" id="hr-ec-address" style="width:100%;border:none;border-bottom:1px solid #aaa;outline:none;padding:3px 4px;font-size:.88rem;background:transparent;"/>
                            </div>

                            <!-- C. HEALTH RECORD & MEDICAL HISTORY -->
                            <div class="student-health-section-title" style="background:#e8f5e9;border-left:4px solid #2d6a4f;padding:6px 12px;margin:16px 0 14px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#1b4332;">
                                <span>C</span><div><strong>Health Record and Medical History</strong><small>Share any relevant history so the clinic team can support you safely.</small></div>
                            </div>
                            <div class="student-health-history-list" style="border:1px solid #ddd;border-radius:6px;overflow:hidden;margin-bottom:14px;">
                                <div style="padding:8px 12px;border-bottom:1px solid #eee;">
                                    <div style="font-size:.82rem;margin-bottom:4px;"><strong>Past Medical History</strong> (List any previous or ongoing medical conditions.)</div>
                                    <textarea id="hr-past-medical-history" rows="3" placeholder="Include any known conditions and relevant details" style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                                <div style="padding:8px 12px;border-bottom:1px solid #eee;">
                                    <div style="font-size:.82rem;margin-bottom:4px;">Do you have <strong>allergy</strong>? If yes, please list all medicines, foods and other causes of your allergy.</div>
                                    <textarea id="hr-allergy" rows="2" placeholder="Leave blank if none" style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                                <div style="padding:8px 12px;border-bottom:1px solid #eee;">
                                    <div style="font-size:.82rem;margin-bottom:4px;">Have you had an <strong>accident</strong>? If yes, please write the date/year, affected body parts, treatment, and operations.</div>
                                    <textarea id="hr-accident" rows="2" placeholder="Leave blank if none" style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                                <div style="padding:8px 12px;border-bottom:1px solid #eee;">
                                    <div style="font-size:.82rem;margin-bottom:4px;">Do you have a history of <strong>hospitalization</strong>? If yes, please write the date/year, affected body parts, treatment, and operations.</div>
                                    <textarea id="hr-hospitalization" rows="2" placeholder="Leave blank if none" style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                                <div style="padding:8px 12px;border-bottom:1px solid #eee;">
                                    <div style="font-size:.82rem;margin-bottom:4px;">Have you undergone <strong>surgical operation(s)</strong>? If yes, please write details, including the date, illness, type of surgery and complications.</div>
                                    <textarea id="hr-surgical" rows="2" placeholder="Leave blank if none" style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                                <div style="padding:8px 12px;">
                                    <div style="font-size:.82rem;margin-bottom:4px;"><strong>Family Medical History</strong> (Please state any diseases/illnesses that are present in your family.)</div>
                                    <textarea id="hr-family-history" rows="2" placeholder="e.g. Hypertension, Diabetes, Asthma..." style="width:100%;border:none;outline:none;font-size:.85rem;resize:vertical;background:transparent;padding:2px 0;"></textarea>
                                </div>
                            </div>

                            <div class="student-health-symptom-intro" style="font-size:.82rem;margin-bottom:8px;">
                                Please check the appropriate box: <strong>N = Never &nbsp;&nbsp; A = Always &nbsp;&nbsp; S = Seldom</strong> &nbsp;— Do you experience any of the following?
                            </div>
                            <div class="student-health-symptom-table-wrap" style="border:1px solid #ccc;border-radius:6px;overflow:hidden;margin-bottom:14px;">
                                <table style="width:100%;font-size:.8rem;border-collapse:collapse;" id="hr-symptoms-table">
                                    <thead>
                                        <tr style="background:#2d6a4f;color:#fff;">
                                            <th style="text-align:left;padding:6px 10px;width:26%;">Symptom</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">N</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">A</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">S</th>
                                            <th style="text-align:left;padding:6px 10px;width:26%;">Symptom</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">N</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">A</th>
                                            <th style="width:36px;text-align:center;padding:6px 0;">S</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hr-sym-body"></tbody>
                                </table>
                            </div>

                            <!-- IMMUNIZATION -->
                            <div class="student-health-section-title student-health-section-title-small" style="background:#e8f5e9;border-left:4px solid #2d6a4f;padding:6px 12px;margin:16px 0 10px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#1b4332;">
                                <span>D</span><div><strong>Immunization / Vaccination Record</strong><small>Check the vaccines you have received.</small></div>
                            </div>
                            <div class="student-health-immunization" style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;margin-bottom:14px;">
                                <div style="font-size:.82rem;margin-bottom:10px;color:#555;">Please check if you have received vaccination for:</div>
                                <div style="display:flex;flex-wrap:wrap;gap:20px;">
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-cp" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Chicken Pox / Varicella</label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-ty" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Typhoid</label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-ha" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Hepatitis A</label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-hb" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Hepatitis B</label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-tt" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Tetanus</label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;"><input type="checkbox" id="hr-imm-cv" style="accent-color:#2d6a4f;width:15px;height:15px;"/> Covid</label>
                                </div>
                            </div>

                            <!-- FOR FEMALES -->
                            <div id="hr-female-section" style="border:1px solid #ddd;border-radius:6px;padding:10px 16px;margin-bottom:14px;background:#fdfefe;">
                                <div style="font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;color:#2d6a4f;margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:6px;">For Females</div>
                                <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
                                    <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;">
                                        <span style="font-weight:600;">Menstrual Period:</span>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" id="hr-mens-reg" style="accent-color:#2d6a4f;"/> Regular</label>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" id="hr-mens-irr" style="accent-color:#2d6a4f;"/> Irregular</label>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;">
                                        <span style="font-weight:600;">Dysmenorrhea:</span>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-dysm" value="Yes" id="hr-dysm-y" style="accent-color:#2d6a4f;"/> Yes</label>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-dysm" value="No" id="hr-dysm-n" style="accent-color:#2d6a4f;"/> No</label>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;">
                                        <span style="font-weight:600;">Excessive Blood Flow:</span>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-ebf" value="Yes" id="hr-ebf-y" style="accent-color:#2d6a4f;"/> Yes</label>
                                        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="radio" name="hr-ebf" value="No" id="hr-ebf-n" style="accent-color:#2d6a4f;"/> No</label>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
                                        <span style="font-weight:600;">Avg. No. of days:</span>
                                        <input type="text" id="hr-avg-days" style="width:50px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.85rem;background:transparent;padding:2px 4px;"/>
                                    </div>
                                </div>
                            </div>

                            <!-- VITALS (staff-only) -->
                            <div class="student-health-vitals" style="border:1px solid #b0bec5;border-radius:6px;padding:10px 16px;margin-bottom:14px;background:#f9f9f9;">
                                <div style="font-weight:700;font-size:.78rem;text-transform:uppercase;color:#546e7a;margin-bottom:10px;border-bottom:1px dashed #cfd8dc;padding-bottom:6px;">
                                    Physical Exam — Vitals <span style="font-weight:400;font-style:italic;text-transform:none;">(filled out by clinic staff)</span>
                                </div>
                                <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:center;">
                                    <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
                                        <label style="font-weight:600;">BP:</label>
                                        <input type="text" id="hr-bp" placeholder="120/80" readonly style="width:90px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px 4px;"/>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
                                        <label style="font-weight:600;">PR:</label>
                                        <input type="text" id="hr-pr" placeholder="bpm" readonly style="width:70px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px 4px;"/>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
                                        <label style="font-weight:600;">Temp:</label>
                                        <input type="number" id="hr-temp" step="0.1" min="35" max="42" readonly style="width:70px;border:none;border-bottom:1px solid #aaa;outline:none;font-size:.88rem;background:transparent;padding:2px 4px;"/>
                                        <span style="font-size:.78rem;color:#666;">°C</span>
                                    </div>
                                </div>
                            </div>

                            <!-- FINDINGS (physician — hidden until data present) -->
                            <div id="hr-findings-section" class="student-health-findings" style="display:none;border:1px solid #b0bec5;border-radius:6px;padding:10px 16px;margin-bottom:14px;background:#f9f9f9;">
                                <div style="font-weight:700;font-size:.78rem;text-transform:uppercase;color:#546e7a;margin-bottom:8px;">
                                    &#10003; Physical Exam — Findings and Recommendation
                                </div>
                                <textarea id="hr-findings" rows="3" readonly style="width:100%;border:none;outline:none;background:transparent;font-size:.88rem;resize:none;"></textarea>
                            </div>

                            <!-- DECLARATION & SIGNATURE -->
                            <div class="student-health-declaration" style="margin-top:18px;padding-top:12px;border-top:1px solid #ddd;">
                                <div style="font-size:.82rem;font-style:italic;color:#444;margin-bottom:14px;text-align:center;">
                                    I hereby declare that all information are true and correct.
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <div style="text-align:center;min-width:260px;">
                                        <input type="text" id="hr-signature" placeholder="Type your full name" style="width:100%;border:none;border-bottom:2px solid #333;outline:none;font-size:.9rem;text-align:center;padding:4px;background:transparent;"/>
                                        <div style="font-size:.73rem;color:#555;margin-top:4px;">Signature over Printed Name</div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /padding wrapper -->

                        <!-- FORM FOOTER / ACTIONS -->
                        <div class="student-health-record-footer" style="background:#f5f5f5;border-top:1px solid #ddd;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                            <div id="hr-status-badge" style="font-size:.85rem;"></div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" class="uh-btn uh-btn-ghost" onclick="sLoadHealthRecord(uhGetUser())">
                                    <i class="fa fa-rotate-left"></i> Reset
                                </button>
                                <button type="submit" class="uh-btn uh-btn-primary" id="s-hr-submit-btn" style="background:#2d6a4f;border-color:#2d6a4f;">
                                    <i class="fa fa-floppy-disk"></i> Save Health Record
                                </button>
                            </div>
                        </div>

                    </div><!-- /single form card -->
                </form>
            </div>

            <!-- -- PAGE: MEDICAL RECORDS ------------ -->
            <div class="uh-page" id="page-medical-records">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div>
                            <div class="uh-card-title">Medical Records</div>
                            <div class="uh-card-subtitle" id="s-records-meta">—</div>
                        </div>
                    </div>
                    <div class="uh-timeline" id="s-records-timeline">
                        <div class="uh-empty">Loading records...</div>
                    </div>
                </div>
            </div>

            <!-- -- PAGE: PRESCRIPTIONS -------------- -->
            <div class="uh-page" id="page-prescriptions">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div>
                            <div class="uh-card-title">Prescriptions</div>
                            <div class="uh-card-subtitle">Your medication prescriptions from the clinic</div>
                        </div>
                        <button type="button" class="uh-btn uh-btn-outline uh-btn-xs" onclick="sRefreshPrescriptions(this)">
                            <i class="fa fa-rotate" aria-hidden="true"></i> Refresh
                        </button>
                    </div>
                    <div class="uh-table-toolbar">
                        <input id="s-rx-search" type="search" class="uh-table-search" data-uh-table-search placeholder="Search prescriptions..." aria-label="Search prescriptions" autocomplete="off">
                        <label class="uh-table-count">
                            Medication
                            <select id="s-rx-medication-filter" class="uh-table-status-filter" aria-label="Filter prescriptions by medication" onchange="sSetPrescriptionMedicationFilter(this.value)">
                                <option value="">All medications</option>
                            </select>
                        </label>
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table id="s-rx-table" class="uh-table" data-uh-searchable data-uh-render="sRenderPrescriptionRecords">
                            <thead>
                                <tr>
                                    <th>RX ID</th>
                                    <th>Medication</th>
                                    <th>Dosage Instructions</th>
                                    <th>Prescriber</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="s-rx-body">
                                <tr><td colspan="7" class="no-data">No prescriptions on file.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="s-rx-pagination"></div>
                </div>
            </div>

            <!-- Prescription detail modal -->
            <div class="uh-modal-overlay hidden" id="s-rx-detail-modal">
                <div class="uh-modal">
                    <div class="uh-modal-header">
                        <div class="uh-modal-title">Prescription Details</div>
                        <button class="uh-modal-close" onclick="uhCloseModal('s-rx-detail-modal')" aria-label="Close">×</button>
                    </div>
                    <div class="uh-modal-body" id="s-rx-detail-body">
                        <p class="uh-text-muted">Loading…</p>
                    </div>
                    <div class="uh-modal-footer">
                        <button type="button" class="uh-btn uh-btn-ghost" onclick="uhCloseModal('s-rx-detail-modal')">Close</button>
                    </div>
                </div>
            </div>

            <!-- -- PAGE: REFERRALS ------------------ -->
            <div class="uh-page" id="page-referrals">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div>
                            <div class="uh-card-title">Referrals &amp; Status</div>
                            <div class="uh-card-subtitle">Track your specialist referrals</div>
                        </div>
                    </div>
                    <div class="uh-table-toolbar">
                        <input id="s-referral-search" type="search" class="uh-table-search" data-uh-table-search placeholder="Search referrals..." aria-label="Search referrals" autocomplete="off">
                        <label class="uh-table-count">
                            Referred to
                            <select id="s-referral-to-filter" class="uh-table-status-filter" aria-label="Filter referrals by referred-to person or campus" onchange="sSetReferralToFilter(this.value)">
                                <option value="">All referrals</option>
                            </select>
                        </label>
                        <label class="uh-table-count">
                            Status
                            <select id="s-referral-status-filter" class="uh-table-status-filter" aria-label="Filter referrals by status" onchange="sSetReferralStatusFilter(this.value)">
                                <option value="">All statuses</option>
                            </select>
                        </label>
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap student-referrals-table-wrap">
                        <table id="s-referral-table" class="uh-table student-referrals-table" data-uh-searchable data-uh-render="sRenderReferrals">
                            <thead>
                                <tr>
                                    <th>Referral ID</th>
                                    <th>Referred To</th>
                                    <th>Reason</th>
                                    <th>Appointment schedule</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="s-referral-body">
                                <tr><td colspan="5" class="no-data">No referrals on file.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="s-referral-pagination"></div>
                </div>
            </div>

            <div class="uh-page" id="page-consultations">
                <div class="cw-page">
                    <section class="cw-card">
                        <h2>Request a clinic consultation</h2>
                        <p class="cw-muted">Enter a short description of your consultation. You may also record a voice message. A nurse will review your request and contact you about next steps.</p>
                        <form id="cw-student-form" class="cw-form-grid">
                            <div class="cw-field cw-wide">
                                <span class="cw-field-label">Voice message (optional)</span>
                                <div class="cw-recorder">
                                    <div class="cw-recorder-actions">
                                        <button id="cw-record-start" class="uh-btn uh-btn-primary" type="button">
                                            <i class="fa fa-microphone" aria-hidden="true"></i> Start recording
                                        </button>
                                        <button id="cw-record-stop" class="uh-btn uh-btn-outline" type="button" hidden>
                                            <i class="fa fa-stop" aria-hidden="true"></i> Stop recording
                                        </button>
                                        <button id="cw-record-retake" class="uh-btn uh-btn-outline" type="button" hidden>Record again</button>
                                        <span id="cw-record-indicator" class="cw-record-indicator" role="status" aria-label="Recording in progress" hidden>
                                            <i class="fa fa-heart" aria-hidden="true"></i>
                                            <span>Recording</span>
                                            <span id="cw-record-timer" class="cw-record-timer">00:00</span>
                                        </span>
                                        <span id="cw-record-status" class="cw-muted" role="status" aria-live="polite">Your recording will be stored privately and transcribed automatically for nurse review.</span>
                                    </div>
                                    <audio id="cw-record-preview" controls hidden aria-label="Preview your consultation recording"></audio>
                                    <div class="cw-recorder-actions">
                                        <button id="cw-record-clear" class="uh-btn uh-btn-outline uh-btn-sm" type="button" hidden>Clear audio</button>
                                        <button id="cw-record-transcribe" class="uh-btn uh-btn-outline uh-btn-sm" type="button" hidden>
                                            <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i> Preview transcript
                                        </button>
                                    </div>
                                </div>
                                <div id="cw-transcript-preview" class="cw-transcript-preview" hidden>
                                    <div class="cw-transcript-label">
                                        <i class="fa fa-file-lines" aria-hidden="true"></i>
                                        Transcript preview — review and edit before submitting
                                    </div>
                                    <textarea id="cw-transcript-text" class="cw-transcript-textarea" rows="4"
                                        aria-label="Transcript preview — editable"
                                        placeholder="Your transcript will appear here…"></textarea>
                                    <small class="cw-muted">This transcript is sent to the nurse along with your recording. You can edit it.</small>
                                </div>
                            </div>
                            <div class="cw-field cw-wide">
                                <label for="cw-student-concern">Short description of your consultation (required)</label>
                                <textarea id="cw-student-concern" name="concern" rows="4" maxlength="5000" placeholder="Briefly describe what you need help with" aria-describedby="cw-student-concern-hint" required></textarea>
                                <small class="cw-muted" id="cw-student-concern-hint">Please enter a short description, even if you also record a voice message.</small>
                            </div>
                            <div class="cw-field">
                                <label for="cw-student-onset">When did it start?</label>
                                <input id="cw-student-onset" name="symptom_onset" maxlength="120" placeholder="For example, yesterday morning">
                            </div>
                            <div class="cw-field">
                                <label for="cw-student-severity">Severity (1–10)</label>
                                <input id="cw-student-severity" name="severity" type="number" min="1" max="10">
                            </div>
                            <div class="cw-field">
                                <label for="cw-student-care-type">Consultation type</label>
                                <select id="cw-student-care-type" name="care_type">
                                    <option value="medical">Medical</option>
                                    <option value="dental">Dental</option>
                                </select>
                            </div>
                            <div class="cw-actions cw-wide">
                                <button class="uh-btn uh-btn-primary" type="submit">Submit health concern</button>
                                <button class="uh-btn uh-btn-outline" type="reset">Clear form</button>
                            </div>
                        </form>
                    </section>
                    <section class="cw-card">
                        <h3>Your consultation status</h3>
                        <div id="cw-student-list" class="cw-list" aria-live="polite">
                            <p class="cw-empty">Loading your requests…</p>
                        </div>
                    </section>
                </div>
            </div>

            <!-- -- PAGE: NOTIFICATIONS -------------- -->
            <div class="uh-page" id="page-notifications">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div>
                            <div class="uh-card-title">Notifications</div>
                        </div>
                        <button class="uh-btn uh-btn-ghost uh-btn-sm" onclick="sMarkAllRead()">Mark all as read</button>
                    </div>
                    <div id="s-notif-list">
                        <p class="uh-text-muted uh-text-sm uh-empty-xs">Loading notifications...</p>
                    </div>
                </div>
            </div>

        </div><!-- /.uh-content -->
    </div><!-- /.uh-main -->
</div><!-- /.uh-layout -->

<!-- Toast container -->
<div id="uh-toasts"></div>

<script src="{{ asset('clinic/js/unihealth.js') }}?v={{ filemtime(public_path('clinic/js/unihealth.js')) }}"></script>
<script src="{{ asset('clinic/js/analytics.js') }}?v={{ filemtime(public_path('clinic/js/analytics.js')) }}"></script>
<script>
'use strict';

var studentOriginalShowPage = window.uhShowPage;
window.uhShowPage = function (pageId, navEl) {
    studentOriginalShowPage(pageId, navEl);
    if (pageId === 'prescriptions') {
        sLoadMedications(uhGetUser());
    }
};

document.addEventListener('DOMContentLoaded', function () {
    var user = uhGetUser();
    if (!user) { return; }

    var campusDisplay = document.getElementById('s-campus-display');
    if (campusDisplay) {
        campusDisplay.value = user.campusName || user.campus_name || 'Campus not available';
    }

    sLoadAppointments(user);
    sLoadVisits(user);
    sLoadMedications(user);
    sLoadReferrals(user);
    sLoadHealthRecord(user);
    sLoadDashboardStats(user);
    sLoadClinicTodayStatus();
    sInitBookingPreview(user);
    if (sPrescriptionRefreshTimer === null) {
        sPrescriptionRefreshTimer = window.setInterval(function () {
            sRefreshPrescriptionPageIfVisible();
            if (document.visibilityState === 'visible') { sLoadClinicTodayStatus(); }
        }, 30000);
        document.addEventListener('visibilitychange', function () {
            sRefreshPrescriptionPageIfVisible();
            if (document.visibilityState === 'visible') { sLoadClinicTodayStatus(); }
        });
    }
});

function sLoadClinicTodayStatus() {
    var statusEl = document.getElementById('s-clinic-today-status');
    if (!statusEl) { return; }

    uhGet('/student/availability')
        .then(function (data) {
            var status = data.clinic_status || {};
            var label;
            if (status.status === 'not_available') {
                label = 'Not available today';
                statusEl.dataset.status = 'not-available';
            } else if (status.status === 'open' && status.opens_at && status.closes_at) {
                label = 'Open today: ' + uhFormatTime(status.opens_at) + ' – ' + uhFormatTime(status.closes_at);
                statusEl.dataset.status = 'open';
            } else if (status.status === 'no_schedule') {
                label = 'No schedule published today';
                statusEl.dataset.status = 'no-schedule';
            } else {
                throw new Error('The clinic did not return a valid schedule status.');
            }
            statusEl.textContent = label;
        })
        .catch(function (error) {
            console.error('Could not load today’s clinic schedule:', error);
            statusEl.textContent = 'Clinic schedule unavailable';
            statusEl.dataset.status = 'error';
        });
}

function sInitBookingPreview(user) {
    var serviceInput = document.getElementById('s-svc-type');
    var campusInput = document.getElementById('s-campus-display');
    if (!serviceInput || !campusInput) { return; }

    function updatePreview() {
        var service = serviceInput.value || 'Choose a service to begin';
        var campus = campusInput.value || user.campusName || user.campus_name || 'Campus not available';
        var serviceEl = document.getElementById('s-booking-preview-service');
        var detailEl = document.getElementById('s-booking-preview-detail');

        if (serviceEl) { serviceEl.textContent = service; }
        if (detailEl) { detailEl.textContent = campus + ' · The clinic team will schedule your check-up'; }
    }

    serviceInput.addEventListener('input', updatePreview);
    serviceInput.addEventListener('change', updatePreview);
    updatePreview();
}

function sLoadDashboardStats(user) {
    if (!user || !user.identifier) {
        ClinicAnalytics.showDashboardInsightsUnavailable('sta', 'Your clinic information is unavailable. Refresh the dashboard to try again.');
        return;
    }

    uhGet('/student/stats?student_no=' + encodeURIComponent(user.identifier))
        .then(function (data) {
            var stats = data.stats || {};
            ClinicAnalytics.renderStudentInsights('sta', stats);
            var campus = user.campusName || 'Campus to be confirmed';
            var campusEl = document.getElementById('s-campus-badge-secondary');
            if (campusEl) { campusEl.textContent = campus; }

            var wellnessEl = document.getElementById('s-stat-wellness');
            var wellnessLabelEl = document.getElementById('s-stat-wellness-label');
            if (wellnessEl) { wellnessEl.textContent = stats.health_record_status || 'Not available'; }
            if (wellnessLabelEl) { wellnessLabelEl.textContent = 'Based on your health record'; }

            sRenderAppointmentTrend(Array.isArray(stats.appointment_trends) ? stats.appointment_trends : []);
        })
        .catch(function () {
            ClinicAnalytics.showDashboardInsightsUnavailable('sta', 'Your clinic information is unavailable. Refresh the dashboard to try again.');
            sRenderAppointmentTrend([]);
        });
}

function sRenderAppointmentTrend(trends) {
    var chart = document.getElementById('s-appointment-chart');
    if (!chart) { return; }

    chart.replaceChildren();

    var width = 700;
    var height = 180;
    var padding = { top: 16, right: 12, bottom: 28, left: 24 };
    var plotWidth = width - padding.left - padding.right;
    var plotHeight = height - padding.top - padding.bottom;
    var points = trends.map(function (trend) {
        return {
            label: String(trend.label || ''),
            count: Math.max(0, Number(trend.count) || 0),
        };
    });
    var maxCount = Math.max.apply(null, points.map(function (point) { return point.count; }).concat([1]));
    var xStep = points.length > 1 ? plotWidth / (points.length - 1) : plotWidth;
    var coordinates = points.map(function (point, index) {
        return {
            x: padding.left + (points.length > 1 ? index * xStep : plotWidth / 2),
            y: padding.top + plotHeight - (point.count / maxCount) * plotHeight,
        };
    });
    var linePath = coordinates.map(function (point, index) {
        return (index ? 'L' : 'M') + ' ' + point.x + ' ' + point.y;
    }).join(' ');
    var hasAppointments = points.some(function (point) { return point.count > 0; });
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Appointments over the last 30 days');

    function addSvgElement(tag, attributes, text) {
        var element = document.createElementNS('http://www.w3.org/2000/svg', tag);
        Object.keys(attributes).forEach(function (name) { element.setAttribute(name, attributes[name]); });
        if (text) { element.textContent = text; }
        svg.appendChild(element);
    }

    [0, 0.5, 1].forEach(function (ratio) {
        var y = padding.top + plotHeight * ratio;
        addSvgElement('line', { x1: padding.left, y1: y, x2: width - padding.right, y2: y, class: 'student-chart-gridline' });
    });

    if (hasAppointments && coordinates.length) {
        addSvgElement('path', { d: linePath, class: 'student-chart-line' });
    } else {
        addSvgElement('text', { x: width / 2, y: padding.top + plotHeight / 2, class: 'student-chart-empty', 'text-anchor': 'middle' }, 'No appointment data yet');
    }

    points.forEach(function (point, index) {
        var coordinate = coordinates[index];
        if (index % 5 === 0 || index === points.length - 1) {
            addSvgElement('text', { x: coordinate.x, y: height - 8, class: 'student-chart-label', 'text-anchor': 'middle' }, point.label);
        }
    });

    chart.appendChild(svg);
}

var sPastAppointmentRows = [];
var sPastAppointmentPage = 1;
var sPastAppointmentQuery = '';
var sPastAppointmentPageSize = 10;
var sPastAppointmentSortOrder = 'desc';
var sReferralSortOrder = 'desc';

function sAppointmentTimestamp(appointment) {
    var date = String(sAppointmentDisplayDate(appointment) || '').slice(0, 10);
    var time = String(sAppointmentDisplayTime(appointment) || '00:00:00').split('T').pop().split('.')[0];
    return Date.parse(date + 'T' + time) || 0;
}

function sAppointmentDisplayDate(appointment) {
    return ['checkup_scheduled', 'checkup_completed'].includes(appointment.status)
        ? appointment.checkup_date
        : appointment.appt_date;
}

function sAppointmentDisplayTime(appointment) {
    return ['checkup_scheduled', 'checkup_completed'].includes(appointment.status)
        ? appointment.checkup_time
        : appointment.appt_time;
}

function sScheduledAppointmentTimestamp(appointment) {
    var isCheckup = appointment.status === 'checkup_scheduled';
    var isProviderVisit = appointment.status === 'confirmed';
    if (!isCheckup && !isProviderVisit) { return null; }

    var date = isCheckup ? appointment.checkup_date : appointment.appt_date;
    var time = isCheckup ? appointment.checkup_time : appointment.appt_time;
    if (!date || !time) { return null; }

    var timestamp = Date.parse(String(date).slice(0, 10) + 'T' + String(time).slice(0, 8) + '+08:00');
    return Number.isFinite(timestamp) ? timestamp : null;
}

function sAppointmentStatusLabel(status) {
    return ({
        pending: 'Waiting for review',
        accepted: 'Accepted · awaiting check-up schedule',
        checkup_scheduled: 'Clinic check-up scheduled',
        checkup_completed: 'Check-up complete · awaiting provider schedule',
        confirmed: 'Provider appointment confirmed',
        completed: 'Completed',
        cancelled: 'Cancelled',
        deleted: 'Deleted',
    })[status] || status || 'Waiting';
}

function sFormatAppointmentDate(raw) {
    if (!raw) { return '—'; }
    return uhFormatDate(raw);
}

function sRenderPastAppointments(table, query) {
    var tbody = table.tBodies[0];
    if (!tbody) { return; }

    var normalizedQuery = (query || '').trim().toLowerCase();
    if (normalizedQuery !== sPastAppointmentQuery) {
        sPastAppointmentPage = 1;
        sPastAppointmentQuery = normalizedQuery;
    }

    var filtered = sPastAppointmentRows.filter(function (appointment) {
        var searchText = [
            sFormatAppointmentDate(appointment.appt_date),
            appointment.service_type || '—',
            '—',
            appointment.status || '—',
        ].join(' ').toLowerCase();
        return !normalizedQuery || searchText.indexOf(normalizedQuery) !== -1;
    }).sort(function (left, right) {
        var difference = sAppointmentTimestamp(left) - sAppointmentTimestamp(right);
        return sPastAppointmentSortOrder === 'asc' ? difference : -difference;
    });
    var totalPages = Math.max(1, Math.ceil(filtered.length / sPastAppointmentPageSize));
    sPastAppointmentPage = Math.min(Math.max(sPastAppointmentPage, 1), totalPages);

    if (filtered.length) {
        var start = (sPastAppointmentPage - 1) * sPastAppointmentPageSize;
        tbody.innerHTML = filtered.slice(start, start + sPastAppointmentPageSize).map(function (appointment) {
            return '<tr>' +
                '<td>' + (appointment.appt_date ? sFormatAppointmentDate(appointment.appt_date) + ' ' + (appointment.appt_time ? uhFormatTime(appointment.appt_time) : '') : 'Awaiting clinic schedule') + '</td>' +
                '<td>' + (appointment.service_type || '—') + '</td>' +
                '<td>—</td>' +
                '<td>' + uhBadge(appointment.status) + '</td>' +
                '</tr>';
        }).join('');
    } else {
        tbody.innerHTML = uhNoData(4, normalizedQuery ? 'No appointments match your search.' : 'No past appointments.');
    }

    var section = table.closest('section');
    var countEl = section && section.querySelector('[data-uh-table-count]');
    if (countEl) {
        countEl.textContent = normalizedQuery
            ? filtered.length + ' match' + (filtered.length === 1 ? '' : 'es')
            : '';
    }

    var paginationEl = document.getElementById('s-past-appts-pagination');
    if (!paginationEl || totalPages <= 1) {
        if (paginationEl) { paginationEl.innerHTML = ''; }
        return;
    }

    var firstVisible = (sPastAppointmentPage - 1) * sPastAppointmentPageSize + 1;
    var lastVisible = Math.min(sPastAppointmentPage * sPastAppointmentPageSize, filtered.length);
    paginationEl.innerHTML = '<nav class="uh-pagination" aria-label="Appointment history pages">' +
        '<button type="button" class="uh-pg-btn" aria-label="Previous page" ' +
            (sPastAppointmentPage === 1 ? 'disabled ' : '') +
            'onclick="sSetPastAppointmentPage(' + (sPastAppointmentPage - 1) + ')">Previous</button>' +
        '<span class="uh-pg-info" aria-live="polite">Page ' + sPastAppointmentPage + ' of ' + totalPages +
            ' · ' + firstVisible + '–' + lastVisible + ' of ' + filtered.length + '</span>' +
        '<button type="button" class="uh-pg-btn" aria-label="Next page" ' +
            (sPastAppointmentPage === totalPages ? 'disabled ' : '') +
            'onclick="sSetPastAppointmentPage(' + (sPastAppointmentPage + 1) + ')">Next</button>' +
        '</nav>';
}

window.sRenderPastAppointments = sRenderPastAppointments;

function sTogglePastAppointmentTimeSort() {
    sPastAppointmentSortOrder = sPastAppointmentSortOrder === 'asc' ? 'desc' : 'asc';
    var indicator = document.getElementById('s-past-appts-sort-indicator');
    if (indicator) { indicator.textContent = sPastAppointmentSortOrder === 'asc' ? '↑' : '↓'; }
    sPastAppointmentPage = 1;
    var table = document.querySelector('#s-past-appts')?.closest('table');
    var searchInput = document.querySelector('#page-my-appointments [data-uh-table-search]');
    if (table) { sRenderPastAppointments(table, searchInput ? searchInput.value : ''); }
}

function sSetPastAppointmentPage(page) {
    sPastAppointmentPage = page;
    var table = document.querySelector('#s-past-appts')?.closest('table');
    var searchInput = document.querySelector('#page-my-appointments [data-uh-table-search]');
    if (table) {
        sRenderPastAppointments(table, searchInput ? searchInput.value : '');
    }
}

// -- Load appointments -------------------------------------
function sLoadAppointments(user) {
    if (!user || !user.identifier) { return; }
    uhGet('/student/appointments?student_no=' + encodeURIComponent(user.identifier))
        .then(function (data) {
            var appts    = data.appointments || [];
            var upcoming = appts.filter(function (a) {
                return a.status !== 'completed' && a.status !== 'cancelled' && a.status !== 'deleted';
            });
            var past = appts.filter(function (a) {
                return a.status === 'completed' || a.status === 'deleted';
            });

            upcoming.sort(function (a, b) {
                var aTimestamp = sScheduledAppointmentTimestamp(a);
                var bTimestamp = sScheduledAppointmentTimestamp(b);
                if (aTimestamp !== null && bTimestamp !== null) { return aTimestamp - bTimestamp; }
                if (aTimestamp !== null) { return -1; }
                if (bTimestamp !== null) { return 1; }
                return sAppointmentTimestamp(a) - sAppointmentTimestamp(b);
            });
            var scheduledAppointments = upcoming.filter(function (appointment) {
                return ['checkup_scheduled', 'confirmed'].includes(appointment.status);
            });
            past.sort(function (a, b) {
                return sAppointmentTimestamp(b) - sAppointmentTimestamp(a);
            });

            // Helper: format time "HH:MM:SS" to "8:00 AM"
            var fmtTime = function (raw) {
                return raw ? uhFormatTime(raw) : 'TBD';
            };
            var fmtDateParts = function (raw) {
                if (!raw) { return { month: 'TBD', day: '—', year: '' }; }
                var d = uhParseDateTime(raw);
                if (!d) { return { month: 'TBD', day: '—', year: '' }; }
                return {
                    month: uhFormatDate(raw, { year: undefined, day: undefined }).toUpperCase(),
                    day: new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', day: '2-digit' }).format(d),
                    year: new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', year: 'numeric' }).format(d),
                };
            };

            var pendingCount = appts.filter(function (a) { return (a.status || 'pending') === 'pending'; }).length;
            var completedCount = appts.filter(function (a) { return a.status === 'completed'; }).length;
            var upcomingCountEl = document.getElementById('s-appt-upcoming-count');
            var pendingCountEl = document.getElementById('s-appt-pending-count');
            var completedCountEl = document.getElementById('s-appt-completed-count');
            var upcomingLabelEl = document.getElementById('s-appt-upcoming-label');
            if (upcomingCountEl) { upcomingCountEl.textContent = upcoming.length; }
            if (pendingCountEl) { pendingCountEl.textContent = pendingCount; }
            if (completedCountEl) { completedCountEl.textContent = completedCount; }
            if (upcomingLabelEl) { upcomingLabelEl.textContent = upcoming.length + (upcoming.length === 1 ? ' visit' : ' visits'); }

            // Stat card (dashboard)
            var statEl = document.getElementById('s-stat-next-appt');
            var trendEl = document.getElementById('s-stat-appt-date');
            if (upcoming.length) {
                var nextAppointment = scheduledAppointments[0] || upcoming[0];
                if (statEl) { statEl.textContent = String(upcoming.length); }
                if (trendEl) {
                    var nextDate = sAppointmentDisplayDate(nextAppointment);
                    var nextTime = sAppointmentDisplayTime(nextAppointment);
                    trendEl.innerHTML = '<i class="fa fa-clock"></i> ' +
                        (nextDate && nextTime
                            ? sFormatAppointmentDate(nextDate) + ' · ' + fmtTime(nextTime)
                            : sEscapeHtml(sAppointmentStatusLabel(nextAppointment.status)));
                }
            } else {
                if (statEl) { statEl.textContent = '—'; }
                if (trendEl) { trendEl.innerHTML = '<i class="fa fa-clock"></i> No upcoming appointments'; }
            }

            // Show every active appointment stage on the dashboard.
            var upcomingEl = document.getElementById('s-upcoming-appt');
            if (upcomingEl) {
                if (upcoming.length) {
                    upcomingEl.innerHTML = upcoming.map(function (appointment) {
                        var displayDate = sAppointmentDisplayDate(appointment);
                        var displayTime = sAppointmentDisplayTime(appointment);
                        return '<div class="uh-appt-card">' +
                            '<div class="uh-appt-type">' + sEscapeHtml(appointment.service_type || 'Appointment') + '</div>' +
                            '<div class="uh-appt-meta">' +
                                '<span><i class="fa fa-calendar uh-text-primary"></i> ' + sFormatAppointmentDate(displayDate) + '</span>' +
                                '<span><i class="fa fa-clock uh-text-primary"></i> ' + fmtTime(displayTime) + '</span>' +
                            '</div>' +
                            '<div class="uh-mt-8">' + uhBadge(sAppointmentStatusLabel(appointment.status)) + '</div>' +
                        '</div>';
                    }).join('');
                } else {
                    upcomingEl.innerHTML = '<p class="uh-text-muted uh-text-sm">No upcoming appointments.</p>';
                }
            }

            // My Appointments page — full upcoming list (replaces Loading...)
            var detailEl = document.getElementById('s-upcoming-detail');
            if (detailEl) {
                if (upcoming.length) {
                    detailEl.innerHTML = upcoming.map(function (a) {
                        var displayDate = sAppointmentDisplayDate(a);
                        var displayTime = sAppointmentDisplayTime(a);
                        var dateParts = fmtDateParts(displayDate);
                        var status = a.status || 'pending';
                        var reason = a.reason || 'No reason provided';
                        return '<article class="student-upcoming-card">' +
                            '<div class="student-upcoming-date" aria-label="' + sFormatAppointmentDate(displayDate) + '">' +
                                '<span>' + dateParts.month + '</span><strong>' + dateParts.day + '</strong><small>' + dateParts.year + '</small>' +
                            '</div>' +
                            '<div class="student-upcoming-main">' +
                                '<div class="student-upcoming-heading">' +
                                    '<div><span class="student-upcoming-kicker">Clinic visit</span><h4>' + (a.service_type || 'Appointment') + '</h4><p>' + reason + '</p></div>' +
                                '</div>' +
                                '<div class="student-upcoming-meta">' +
                                    '<span><i class="fa fa-clock" aria-hidden="true"></i> ' + fmtTime(displayTime) + '</span>' +
                                    (a.appt_date && a.checkup_date ? '<span><i class="fa fa-user-doctor" aria-hidden="true"></i> Provider visit: ' + sFormatAppointmentDate(a.appt_date) + ' · ' + fmtTime(a.appt_time) + '</span>' : '') +
                                    '<span><i class="fa fa-building" aria-hidden="true"></i> ' + (user.campusName || 'Campus to be confirmed') + '</span>' +
                                    '<span><i class="fa fa-shield-heart" aria-hidden="true"></i> Private request</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="student-upcoming-actions">' +
                                '<span class="student-upcoming-status">' + uhBadge(sAppointmentStatusLabel(status)) + '</span>' +
                                '<button class="uh-btn uh-btn-danger uh-btn-xs" onclick="sCancelAppointment(' + a.id + ',this)"><i class=\'fa fa-trash\'></i> Delete request</button>' +
                            '</div>' +
                        '</article>';
                    }).join('');
                } else {
                    detailEl.innerHTML = '<div class="student-appointment-empty">' +
                        '<span class="student-appointment-empty-icon"><i class="fa fa-calendar-plus" aria-hidden="true"></i></span>' +
                        '<h4>No upcoming appointments</h4>' +
                        '<p>When you request a clinic visit, your schedule and confirmation status will appear here.</p>' +
                        '<button type="button" class="uh-btn uh-btn-primary uh-btn-xs" onclick="uhShowPage(\'book-appointment\',document.querySelector(\'[data-title=\\\'Book Appointment\\\']\'))"><i class="fa fa-calendar-plus" aria-hidden="true"></i> Book one now</button>' +
                    '</div>';
                }
            }

            // Past appointments table
            var pastTbody = document.getElementById('s-past-appts');
            if (pastTbody) {
                sPastAppointmentRows = past;
                sPastAppointmentPage = 1;
                var pastTable = pastTbody.closest('table');
                if (pastTable) {
                    var searchInput = document.querySelector('#page-my-appointments [data-uh-table-search]');
                    sRenderPastAppointments(pastTable, searchInput ? searchInput.value : '');
                }
            }
        }).catch(function () {});
}

// -- Load clinic visits ------------------------------------
function sLoadVisits(user) {
    if (!user || !user.identifier) { return; }
    uhGet('/student/visits?student_no=' + encodeURIComponent(user.identifier))
        .then(function (data) {
            var visits   = data.visits || [];
            var timeline = document.getElementById('s-records-timeline');
            var metaEl   = document.getElementById('s-records-meta');

            if (metaEl) { metaEl.textContent = visits.length + ' record(s) on file'; }
            if (!timeline) { return; }
            if (!visits.length) { timeline.innerHTML = '<div class="uh-empty">No medical records on file.</div>'; return; }

            timeline.innerHTML = visits.map(function (v) {
                return '<div class="uh-timeline-item">' +
                    '<div class="uh-timeline-dot primary"></div>' +
                    '<div class="uh-timeline-body">' +
                        '<div class="uh-flex-center uh-gap-8">' +
                            '<span class="uh-timeline-title">' + (v.reason || 'Clinic Visit') + '</span>' +
                            uhBadge(v.disposition ? 'completed' : 'pending') +
                        '</div>' +
                        '<div class="uh-timeline-meta">' + (v.visit_date || '—') +
                            (v.treatment ? ' · ' + v.treatment.substring(0, 60) : '') +
                        '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }).catch(function () {});
}

var sPrescriptionRecords = [];
var sPrescriptionPage = 1;
var sPrescriptionPageSize = 5;
var sPrescriptionQuery = '';
var sPrescriptionMedicationFilter = '';
var sPrescriptionLoadPromise = null;
var sPrescriptionRefreshTimer = null;

function sPrescriptionMedicationLabel(record) {
    if (record.recordType === 'Prescription Form') {
        return record.form_type === 'dentist_prescription' ? 'Dentist prescription' : 'Physician prescription';
    }

    return record.name || '—';
}

function sPrescriptionSearchText(record) {
    var searchFields = [
        record.id,
        sPrescriptionMedicationLabel(record),
        record.created_at,
        record.recordType
    ];

    if (record.recordType === 'Prescription Form') {
        var prefix = record.form_type === 'dentist_prescription' ? 'rxd' : 'rx';
        var formData = record.form_data || {};
        searchFields.push(
            formData[prefix + '_physician_name'],
            formData[prefix + '_notes'],
            record.has_pdf ? 'PDF available' : 'PDF unavailable'
        );
    } else {
        var prescriber = record.prescribed_by
            ? [record.prescribed_by.first_name, record.prescribed_by.last_name].filter(Boolean).join(' ')
            : '';
        searchFields.push(record.dosage, record.schedule, prescriber, record.active ? 'active' : 'cancelled');
    }

    return searchFields.filter(Boolean).join(' ').toLowerCase();
}

function sPopulatePrescriptionMedicationFilter(records) {
    var filter = document.getElementById('s-rx-medication-filter');
    if (!filter) { return; }

    var selectedValue = filter.value;
    var medicationsByKey = {};
    records.forEach(function (record) {
        var medication = sPrescriptionMedicationLabel(record);
        var key = medication.toLowerCase();
        if (medication && !medicationsByKey[key]) {
            medicationsByKey[key] = medication;
        }
    });

    var medicationKeys = Object.keys(medicationsByKey).sort(function (left, right) {
        return medicationsByKey[left].localeCompare(medicationsByKey[right]);
    });
    filter.innerHTML = '<option value="">All medications</option>' + medicationKeys.map(function (key) {
        var medication = medicationsByKey[key];
        return '<option value="' + sEscapeHtml(medication) + '">' + sEscapeHtml(medication) + '</option>';
    }).join('');
    filter.value = medicationKeys.indexOf(selectedValue.toLowerCase()) !== -1
        ? medicationsByKey[selectedValue.toLowerCase()]
        : '';
    sPrescriptionMedicationFilter = filter.value.toLowerCase();
}

function sRenderPrescriptionRecords(table, query) {
    var tbody = table && table.tBodies[0];
    if (!tbody) { return; }

    var normalizedQuery = String(query || '').trim().toLowerCase();
    if (normalizedQuery !== sPrescriptionQuery) {
        sPrescriptionQuery = normalizedQuery;
        sPrescriptionPage = 1;
    }

    var filtered = sPrescriptionRecords.filter(function (record) {
        var medicationMatches = !sPrescriptionMedicationFilter
            || sPrescriptionMedicationLabel(record).toLowerCase() === sPrescriptionMedicationFilter;
        var searchMatches = !normalizedQuery || sPrescriptionSearchText(record).indexOf(normalizedQuery) !== -1;
        return medicationMatches && searchMatches;
    });
    var totalPages = Math.max(1, Math.ceil(filtered.length / sPrescriptionPageSize));
    sPrescriptionPage = Math.min(Math.max(sPrescriptionPage, 1), totalPages);

    if (filtered.length) {
        var start = (sPrescriptionPage - 1) * sPrescriptionPageSize;
        tbody.innerHTML = filtered.slice(start, start + sPrescriptionPageSize).map(function (record) {
            if (record.recordType === 'Prescription Form') {
                var prefix = record.form_type === 'dentist_prescription' ? 'rxd' : 'rx';
                var formData = record.form_data || {};
                var prescriber = formData[prefix + '_physician_name'] || (record.form_type === 'dentist_prescription' ? 'Dentist' : 'Physician');

                return '<tr>' +
                    '<td>FORM-' + Number(record.id) + '</td>' +
                    '<td>' + sEscapeHtml(sPrescriptionMedicationLabel(record)) + '</td>' +
                    '<td class="ph-record-details">' + sEscapeHtml(formData[prefix + '_notes'] || '—').replace(/\r?\n/g, '<br>') + '</td>' +
                    '<td>' + sEscapeHtml(prescriber) + '</td>' +
                    '<td>' + sEscapeHtml(uhFormatDate(record.created_at)) + '</td>' +
                    '<td>' + (record.has_pdf ? '<span class="uh-badge completed">PDF available</span>' : '<span class="uh-badge waiting">PDF unavailable</span>') + '</td>' +
                    '<td>' + (record.has_pdf
                        ? '<a class="uh-btn uh-btn-outline uh-btn-xs" href="/api/clinic/student/prescription-forms/' + Number(record.id) + '/pdf">Download PDF</a>'
                        : '—') + '</td>' +
                    '</tr>';
            }

            var medication = record;
            var prescriber = medication.prescribed_by
                ? [medication.prescribed_by.first_name, medication.prescribed_by.last_name].filter(Boolean).join(' ')
                : '—';
            return '<tr>' +
                '<td>RX-' + Number(medication.id) + '</td>' +
                '<td>' + sEscapeHtml(sPrescriptionMedicationLabel(medication)) + '</td>' +
                '<td>' + sEscapeHtml((medication.dosage || '—') + ' ' + (medication.schedule || '')) + '</td>' +
                '<td>' + sEscapeHtml(prescriber) + '</td>' +
                '<td>' + sEscapeHtml(uhFormatDate(medication.created_at)) + '</td>' +
                '<td>' + uhBadge(medication.active ? 'active' : 'cancelled') + '</td>' +
                '<td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="sShowPrescription(' + Number(medication.id) + ')">Details</button></td>' +
                '</tr>';
        }).join('');
    } else {
        var hasActiveFilter = normalizedQuery || sPrescriptionMedicationFilter;
        tbody.innerHTML = uhNoData(7, hasActiveFilter
            ? 'No prescriptions match your search or medication filter.'
            : 'No prescriptions on file.');
    }

    var countEl = document.querySelector('#page-prescriptions [data-uh-table-count]');
    if (countEl) {
        countEl.textContent = normalizedQuery || sPrescriptionMedicationFilter
            ? filtered.length + ' matching prescription' + (filtered.length === 1 ? '' : 's')
            : '';
    }

    var paginationEl = document.getElementById('s-rx-pagination');
    if (!paginationEl || totalPages <= 1) {
        if (paginationEl) { paginationEl.innerHTML = ''; }
        return;
    }

    var firstVisible = (sPrescriptionPage - 1) * sPrescriptionPageSize + 1;
    var lastVisible = Math.min(sPrescriptionPage * sPrescriptionPageSize, filtered.length);
    paginationEl.innerHTML = '<nav class="uh-pagination" aria-label="Prescription pages">' +
        '<button type="button" class="uh-pg-btn" aria-label="Previous page" ' +
            (sPrescriptionPage === 1 ? 'disabled ' : '') +
            'onclick="sSetPrescriptionPage(' + (sPrescriptionPage - 1) + ')">Previous</button>' +
        '<span class="uh-pg-info" aria-live="polite">Page ' + sPrescriptionPage + ' of ' + totalPages +
            ' · ' + firstVisible + '–' + lastVisible + ' of ' + filtered.length + '</span>' +
        '<button type="button" class="uh-pg-btn" aria-label="Next page" ' +
            (sPrescriptionPage === totalPages ? 'disabled ' : '') +
            'onclick="sSetPrescriptionPage(' + (sPrescriptionPage + 1) + ')">Next</button>' +
        '</nav>';
}

window.sRenderPrescriptionRecords = sRenderPrescriptionRecords;

function sSetPrescriptionMedicationFilter(medication) {
    sPrescriptionMedicationFilter = String(medication || '').trim().toLowerCase();
    sPrescriptionPage = 1;
    var table = document.getElementById('s-rx-table');
    var searchInput = document.getElementById('s-rx-search');
    if (table) {
        sRenderPrescriptionRecords(table, searchInput ? searchInput.value : '');
    }
}

function sSetPrescriptionPage(page) {
    sPrescriptionPage = page;
    var table = document.getElementById('s-rx-table');
    var searchInput = document.getElementById('s-rx-search');
    if (table) {
        sRenderPrescriptionRecords(table, searchInput ? searchInput.value : '');
    }
}

function sRefreshPrescriptions(button) {
    if (button) { button.disabled = true; }
    sLoadMedications(uhGetUser())
        .then(function (loaded) {
            if (loaded) { uhToast('Prescription list updated.', 'success'); }
        })
        .finally(function () {
            if (button) { button.disabled = false; }
        });
}

function sRefreshPrescriptionPageIfVisible() {
    var page = document.getElementById('page-prescriptions');
    if (document.visibilityState !== 'visible' || !page || !page.classList.contains('active')) {
        return;
    }

    sLoadMedications(uhGetUser(), { showError: false });
}

// -- Load medications --------------------------------------
function sLoadMedications(user, options) {
    if (!user || !user.identifier) { return Promise.resolve(false); }
    if (sPrescriptionLoadPromise) { return sPrescriptionLoadPromise; }

    var showError = !options || options.showError !== false;
    var request = Promise.all([
        uhGet('/student/medications?student_no=' + encodeURIComponent(user.identifier)),
        uhGet('/student/prescription-forms')
    ]).then(function (responses) {
            var medicationResponse = responses[0];
            var formResponse = responses[1];
            var tbody  = document.getElementById('s-rx-body');
            var statEl = document.getElementById('s-stat-rx');
            if (!medicationResponse.ok || !formResponse.ok) {
                throw new Error('Unable to load prescription records.');
            }

            var meds = medicationResponse.medications || [];
            var forms = formResponse.prescription_forms || [];
            var activeCount = meds.filter(function (m) { return m.active; }).length;
            sPrescriptionRecords = meds.map(function (medication) {
                return Object.assign({ recordType: 'Medication' }, medication);
            }).concat(forms.map(function (form) {
                return Object.assign({ recordType: 'Prescription Form' }, form);
            })).sort(function (left, right) {
                return String(right.created_at || '').localeCompare(String(left.created_at || ''));
            });
            sPopulatePrescriptionMedicationFilter(sPrescriptionRecords);

            if (statEl) { statEl.innerHTML = Number(activeCount + forms.length).toLocaleString(); }
            if (!tbody) { return; }
            var table = document.getElementById('s-rx-table');
            var searchInput = document.getElementById('s-rx-search');
            if (table) {
                sRenderPrescriptionRecords(table, searchInput ? searchInput.value : '');
            }
            return true;
        }).catch(function (error) {
            console.error('Could not load patient prescription records:', error);
            var tbody = document.getElementById('s-rx-body');
            if (tbody) { tbody.innerHTML = uhNoData(7, 'Unable to load prescriptions. Refresh the page or try again later.'); }
            var paginationEl = document.getElementById('s-rx-pagination');
            if (paginationEl) { paginationEl.innerHTML = ''; }
            if (showError) { uhToast('Could not load your prescription records.', 'error'); }
            return false;
        });

    sPrescriptionLoadPromise = request.finally(function () {
        sPrescriptionLoadPromise = null;
    });
    return sPrescriptionLoadPromise;
}

function sEscapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[character];
    });
}

// -- Prescription detail modal -----------------------------
function sShowPrescription(id) {
    var body = document.getElementById('s-rx-detail-body');
    if (body) { body.innerHTML = '<p class="uh-text-muted">Loading…</p>'; }
    uhOpenModal('s-rx-detail-modal');
    uhGet('/student/medications?student_no=' + encodeURIComponent(uhGetUser().identifier || ''))
        .then(function (data) {
            var meds = (data && data.medications) || [];
            var m = meds.find(function (x) { return String(x.id) === String(id); });
            if (!m) {
                if (body) { body.innerHTML = '<p class="uh-text-muted">Prescription not found.</p>'; }
                return;
            }
            var by = m.prescribed_by
                ? [m.prescribed_by.first_name, m.prescribed_by.last_name].filter(Boolean).join(' ')
                : '—';
            var status = m.active
                ? '<span class="uh-badge active">Active</span>'
                : '<span class="uh-badge cancelled">Cancelled</span>';
            if (body) {
                body.innerHTML =
                    '<div class="uh-form-group"><label>RX ID</label><div>RX-' + m.id + '</div></div>' +
                    '<div class="uh-form-group"><label>Medication</label><div>' + (m.name || '—') + '</div></div>' +
                    '<div class="uh-form-group"><label>Dosage</label><div>' + (m.dosage || '—') + '</div></div>' +
                    '<div class="uh-form-group"><label>Schedule</label><div>' + (m.schedule || '—') + '</div></div>' +
                    '<div class="uh-form-group"><label>Prescriber</label><div>' + by + '</div></div>' +
                    '<div class="uh-form-group"><label>Date prescribed</label><div>' + uhFormatDate(m.created_at) + '</div></div>' +
                    '<div class="uh-form-group"><label>Status</label><div>' + status + '</div></div>' +
                    (m.notes ? '<div class="uh-form-group"><label>Notes</label><div>' + m.notes + '</div></div>' : '');
            }
        })
        .catch(function () {
            if (body) { body.innerHTML = '<p class="uh-text-muted">Failed to load prescription details.</p>'; }
        });
}

var sReferralRows = [];
var sReferralToFilter = '';
var sReferralStatusFilter = '';
var sReferralPage = 1;
var sReferralPageSize = 5;
var sReferralQuery = '';

function sReferralReferredTo(referral) {
    return referral.to_campus ? referral.to_campus.name : (referral.referred_to_name || '—');
}

function sPopulateReferralToFilter(referrals) {
    var filter = document.getElementById('s-referral-to-filter');
    if (!filter) { return; }

    var selectedValue = filter.value.toLowerCase();
    var referredToNames = {};
    referrals.forEach(function (referral) {
        var referredTo = sReferralReferredTo(referral);
        var key = String(referredTo || '').trim().toLowerCase();
        if (key && key !== '—' && !referredToNames[key]) {
            referredToNames[key] = referredTo;
        }
    });

    var referredToKeys = Object.keys(referredToNames).sort(function (left, right) {
        return referredToNames[left].localeCompare(referredToNames[right]);
    });
    filter.innerHTML = '<option value="">All referrals</option>' + referredToKeys.map(function (key) {
        var referredTo = referredToNames[key];
        return '<option value="' + sEscapeHtml(referredTo) + '">' + sEscapeHtml(referredTo) + '</option>';
    }).join('');
    filter.value = referredToKeys.indexOf(selectedValue) !== -1
        ? referredToNames[selectedValue]
        : '';
    sReferralToFilter = filter.value.toLowerCase();
}

function sPopulateReferralStatusFilter(referrals) {
    var filter = document.getElementById('s-referral-status-filter');
    if (!filter) { return; }

    var selectedValue = filter.value.toLowerCase();
    var statuses = {};
    referrals.forEach(function (referral) {
        var status = String(referral.status || '').trim();
        var key = status.toLowerCase();
        if (key && !statuses[key]) {
            statuses[key] = status;
        }
    });

    var statusKeys = Object.keys(statuses).sort(function (left, right) {
        return statuses[left].localeCompare(statuses[right]);
    });
    filter.innerHTML = '<option value="">All statuses</option>' + statusKeys.map(function (key) {
        var status = statuses[key];
        var label = status.charAt(0).toUpperCase() + status.slice(1);
        return '<option value="' + sEscapeHtml(status) + '">' + sEscapeHtml(label) + '</option>';
    }).join('');
    filter.value = statusKeys.indexOf(selectedValue) !== -1
        ? statuses[selectedValue]
        : '';
    sReferralStatusFilter = filter.value.toLowerCase();
}

function sRenderReferrals(table, query) {
    var tbody = table && table.tBodies[0];
    if (!tbody) { return; }

    var normalizedQuery = String(query || '').trim().toLowerCase();
    if (normalizedQuery !== sReferralQuery) {
        sReferralQuery = normalizedQuery;
        sReferralPage = 1;
    }

    var filtered = sReferralRows.filter(function (referral) {
        var referredTo = sReferralReferredTo(referral);
        var matchesReferredTo = !sReferralToFilter || String(referredTo || '').toLowerCase() === sReferralToFilter;
        var matchesStatus = !sReferralStatusFilter || String(referral.status || '').toLowerCase() === sReferralStatusFilter;
        var searchText = [
            'REF-' + referral.id,
            referredTo,
            referral.reason || '—',
            (referral.scheduled_at || referral.created_at || '—').substring(0, 10),
            referral.status || '—'
        ].join(' ').toLowerCase();

        return matchesReferredTo && matchesStatus && (!normalizedQuery || searchText.indexOf(normalizedQuery) !== -1);
    }).sort(function (left, right) {
        var difference = String(left.scheduled_at || left.created_at || '').localeCompare(String(right.scheduled_at || right.created_at || ''));
        return sReferralSortOrder === 'asc' ? difference : -difference;
    });
    var totalPages = Math.max(1, Math.ceil(filtered.length / sReferralPageSize));
    sReferralPage = Math.min(Math.max(sReferralPage, 1), totalPages);

    if (filtered.length) {
        var start = (sReferralPage - 1) * sReferralPageSize;
        tbody.innerHTML = filtered.slice(start, start + sReferralPageSize).map(function (referral) {
            return '<tr class="student-referral-row">' +
                '<td data-label="Referral ID"><strong class="student-referral-id">REF-' + Number(referral.id) + '</strong></td>' +
                '<td data-label="Referred to">' + sEscapeHtml(sReferralReferredTo(referral)) + '</td>' +
                '<td data-label="Reason">' + sEscapeHtml((referral.reason || '—').substring(0, 50)) + '</td>' +
                '<td data-label="Appointment schedule">' + (referral.scheduled_at
                    ? sEscapeHtml(uhFormatDateTime(referral.scheduled_at))
                    : '<span class="uh-text-muted">Awaiting specialist schedule</span>') + '</td>' +
                '<td data-label="Status">' + uhBadge(referral.status) + '</td>' +
                '</tr>';
        }).join('');
    } else {
        var hasActiveFilter = normalizedQuery || sReferralToFilter || sReferralStatusFilter;
        tbody.innerHTML = uhNoData(5, hasActiveFilter
            ? 'No referrals match your search or filters.'
            : 'No referrals on file.');
    }

    var countEl = document.querySelector('#page-referrals [data-uh-table-count]');
    if (countEl) {
        countEl.textContent = normalizedQuery || sReferralToFilter || sReferralStatusFilter
            ? filtered.length + ' matching referral' + (filtered.length === 1 ? '' : 's')
            : '';
    }

    var paginationEl = document.getElementById('s-referral-pagination');
    if (!paginationEl || totalPages <= 1) {
        if (paginationEl) { paginationEl.innerHTML = ''; }
        return;
    }

    var firstVisible = (sReferralPage - 1) * sReferralPageSize + 1;
    var lastVisible = Math.min(sReferralPage * sReferralPageSize, filtered.length);
    paginationEl.innerHTML = '<nav class="uh-pagination" aria-label="Referral pages">' +
        '<button type="button" class="uh-pg-btn" aria-label="Previous page" ' +
            (sReferralPage === 1 ? 'disabled ' : '') +
            'onclick="sSetReferralPage(' + (sReferralPage - 1) + ')">Previous</button>' +
        '<span class="uh-pg-info" aria-live="polite">Page ' + sReferralPage + ' of ' + totalPages +
            ' · ' + firstVisible + '–' + lastVisible + ' of ' + filtered.length + '</span>' +
        '<button type="button" class="uh-pg-btn" aria-label="Next page" ' +
            (sReferralPage === totalPages ? 'disabled ' : '') +
            'onclick="sSetReferralPage(' + (sReferralPage + 1) + ')">Next</button>' +
        '</nav>';
}

window.sRenderReferrals = sRenderReferrals;

function sToggleReferralTimeSort() {
    sReferralSortOrder = sReferralSortOrder === 'asc' ? 'desc' : 'asc';
    var indicator = document.getElementById('s-referrals-sort-indicator');
    if (indicator) { indicator.textContent = sReferralSortOrder === 'asc' ? '↑' : '↓'; }
    sReferralPage = 1;
    sRenderReferralTable();
}

function sSetReferralToFilter(referredTo) {
    sReferralToFilter = String(referredTo || '').trim().toLowerCase();
    sReferralPage = 1;
    sRenderReferralTable();
}

function sSetReferralStatusFilter(status) {
    sReferralStatusFilter = String(status || '').trim().toLowerCase();
    sReferralPage = 1;
    sRenderReferralTable();
}

function sSetReferralPage(page) {
    sReferralPage = page;
    sRenderReferralTable();
}

function sRenderReferralTable() {
    var table = document.getElementById('s-referral-table');
    var searchInput = document.getElementById('s-referral-search');
    if (table) {
        sRenderReferrals(table, searchInput ? searchInput.value : '');
    }
}

// -- Load referrals ----------------------------------------
function sLoadReferrals(user) {
    if (!user || !user.identifier) { return; }
    uhGet('/referrals?student_no=' + encodeURIComponent(user.identifier))
        .then(function (data) {
            var refs   = data.referrals || [];
            var table  = document.getElementById('s-referral-table');
            var statEl = document.getElementById('s-stat-referrals');
            var open   = refs.filter(function (r) { return r.status === 'pending' || r.status === 'accepted'; });

            if (statEl) { statEl.innerHTML = Number(open.length).toLocaleString(); }

            // ── Next referral appointment ───────────────────────────
            var nextRefEl    = document.getElementById('s-stat-next-referral');
            var nextRefLabel = document.getElementById('s-stat-next-referral-label');
            if (nextRefEl && nextRefLabel) {
                var now = new Date();
                // Find the closest future scheduled_at among open referrals
                var upcoming = open
                    .filter(function (r) { return r.scheduled_at; })
                    .map(function (r) { return { date: new Date(r.scheduled_at), raw: r.scheduled_at }; })
                    .filter(function (r) { return !isNaN(r.date.getTime()) && r.date >= now; })
                    .sort(function (a, b) { return a.date - b.date; });

                if (upcoming.length) {
                    var closest = upcoming[0].date;
                    nextRefEl.textContent = closest.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                    nextRefLabel.innerHTML = '<i class="fa fa-clock"></i> ' +
                        closest.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                } else if (open.length) {
                    nextRefEl.textContent = open.length;
                    nextRefLabel.innerHTML = '<i class="fa fa-clock"></i> Awaiting specialist schedule';
                } else {
                    nextRefEl.textContent = 'None';
                    nextRefLabel.textContent = 'No open referrals';
                }
            }

            sReferralRows = refs;
            sPopulateReferralToFilter(sReferralRows);
            sPopulateReferralStatusFilter(sReferralRows);
            sReferralPage = 1;
            if (table) { sRenderReferralTable(); }
        }).catch(function () {});
}



// -- Load notifications ---------------------------------------------------
function sRelTime(iso) {
    if (!iso) { return '-'; }
    var d = uhParseDateTime(iso);
    if (!d) { return iso; }
    var now  = new Date();
    var diff = Math.floor((now - d) / 1000);
    if (diff < 60)   { return 'Just now'; }
    if (diff < 3600) { return Math.floor(diff / 60) + ' min ago'; }
    var todayKey = uhManilaDateKey(now);
    var recordKey = uhManilaDateKey(d);
    var days = Math.floor((Date.parse(todayKey + 'T00:00:00Z') - Date.parse(recordKey + 'T00:00:00Z')) / 86400000);
    var timeStr = uhFormatTime(d);
    if (days === 0) { return 'Today, ' + timeStr; }
    if (days === 1) { return 'Yesterday, ' + timeStr; }
    if (days < 7)  { return days + ' days ago'; }
    if (days < 30) { return Math.floor(days / 7) + ' week' + (days >= 14 ? 's' : '') + ' ago'; }
    return uhFormatDate(d);
}

function sLoadNotifications(user) {
    if (!user || !user.id) { return; }
    uhGet('/notifications?recipient_id=' + user.id + '&recipient_type=student&limit=10')
        .then(function (data) {
            var notifs   = data.notifications || [];
            var unread   = Number(data.unread_count) || 0;
            var listEl   = document.getElementById('s-notif-list');
            var recentEl = document.getElementById('s-recent-notifs');

            uhSetNotificationBadge(unread);

            var renderItem = function (n, clickable) {
                var readCls  = n.is_read ? ' read' : '';
                var onclick  = clickable ? ' onclick="sMarkRead(' + n.id + ',this)"' : '';
                var cls      = clickable ? 'uh-notif-item uh-clickable' : 'uh-notif-item';
                return '<div class="' + cls + '"' + onclick + '>' +
                    '<div class="uh-notif-indicator' + readCls + '"></div>' +
                    '<div style="flex:1;">' +
                        '<div class="uh-notif-text">' + (n.title || '') + '</div>' +
                        (n.body ? '<div class="uh-notif-sub">' + n.body + '</div>' : '') +
                        '<div class="uh-notif-sub" style="color:#999;font-size:.75rem;">' + sRelTime(n.created_at) + '</div>' +
                    '</div>' +
                    '</div>';
            };

            if (listEl) {
                listEl.innerHTML = notifs.length
                    ? notifs.map(function (n) { return renderItem(n, true); }).join('')
                    : '<p class="uh-text-muted uh-text-sm uh-empty-xs">No notifications.</p>';
            }

            if (recentEl) {
                recentEl.innerHTML = notifs.length
                    ? notifs.slice(0, 3).map(function (n) { return renderItem(n, false); }).join('')
                    : '<p class="uh-text-muted uh-text-sm uh-empty-xs">No new notifications.</p>';
            }
        }).catch(function () {
            var listEl = document.getElementById('s-notif-list');
            if (listEl) { listEl.innerHTML = '<p class="uh-text-muted uh-text-sm uh-empty-xs">Could not load notifications.</p>'; }
        });
}



function sMarkRead(id, itemEl) {
    uhPatch('/notifications/' + id + '/read', {}).then(function () {
        var dot = itemEl ? itemEl.querySelector('.uh-notif-indicator') : null;
        if (dot) { dot.classList.add('read'); }
        var user = uhGetUser();
        if (user) { sLoadNotifications(user); }
    }).catch(function () {});
}

function sMarkAllRead() {
    var user = uhGetUser();
    if (!user) { return; }
    uhPost('/notifications/mark-all-read?recipient_id=' + user.id + '&recipient_type=student', {})
        .then(function () {
            document.querySelectorAll('.uh-notif-indicator').forEach(function (el) { el.classList.add('read'); });
            uhSetNotificationBadge(0);
            uhToast('All notifications marked as read.', 'info');
        }).catch(function () {});
}

// -- Cancel / delete appointment -----------------------------------
async function sCancelAppointment(id, btnEl) {
    if (!await uhConfirm('This will permanently delete the appointment.', { title: 'Delete appointment?', confirmText: 'Delete appointment', danger: true })) { return; }
    if (btnEl) { btnEl.disabled = true; btnEl.textContent = '...'; }
    var user = uhGetUser();
    try {
        var resp = await fetch(UH_BASE + '/student/appointments/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Content-Type': 'application/json',
            },
        });
        var data = await resp.json();
        if (data.success) {
            uhToast('Appointment deleted.', 'success');
            sLoadAppointments(user);
        } else {
            uhToast(data.error || 'Could not delete appointment.', 'error');
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fa fa-trash"></i> Delete'; }
        }
    } catch {
        uhToast('Could not reach the server.', 'error');
        if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fa fa-trash"></i> Delete'; }
    }
}

// -- Book appointment --------------------------------------
async function sBookAppointment(e) {
    e.preventDefault();
    var btn = document.getElementById('s-book-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';

    var user = uhGetUser();
    if (!user || !user.identifier) {
        uhToast('Session expired. Please log in again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-calendar-check"></i> Request appointment';
        return;
    }

    try {
        var data = await uhPost('/student/appointments', {
            service_type: document.getElementById('s-svc-type').value,
            reason:       document.getElementById('s-reason').value,
        });
        if (!data.ok || data.success === false) {
            var validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
            uhToast(data.message || data.error || validationMessage || 'Could not submit appointment request.', 'error');
        } else {
            uhToast('Appointment request submitted!', 'success');
            document.getElementById('s-book-form').reset();
            sLoadAppointments(user);
            uhShowPage('my-appointments', document.querySelector('[data-title="My Appointments"]'));
        }
    } catch {
        uhToast('Could not reach the server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-calendar-check"></i> Request appointment';
    }
}

// -- Health Record -----------------------------------------
var HR_SYMPTOMS = [
    ['Headache',              'sym_headache'],
    ['Dizziness',             'sym_dizziness'],
    ['Fainting',              'sym_fainting'],
    ['Blurring of vision',    'sym_blurring_vision'],
    ['Difficulty of hearing', 'sym_hearing_difficulty'],
    ['Back pain',             'sym_back_pain'],
    ['Chest pain',            'sym_chest_pain'],
    ['Difficulty of breathing','sym_breathing_difficulty'],
    ['Cough',                 'sym_cough'],
    ['Sore throat',           'sym_sore_throat'],
    ['Runny nose',            'sym_runny_nose'],
    ['Nose bleed',            'sym_nose_bleed'],
    ['Vomiting',              'sym_vomiting'],
    ['Nausea',                'sym_nausea'],
    ['Abdominal pain',        'sym_abdominal_pain'],
    ['Fever',                 'sym_fever'],
    ['Frequent urination',    'sym_frequent_urination'],
    ['Loss of weight',        'sym_weight_loss'],
    ['Difficult of sleeping', 'sym_difficulty_sleeping'],
    ['Nervousness',           'sym_nervousness'],
];

function sBuildSymptomTable() {
    var tbody = document.getElementById('hr-sym-body');
    if (!tbody) { return; }
    var rows = '';
    var half = Math.ceil(HR_SYMPTOMS.length / 2);
    for (var i = 0; i < half; i++) {
        var left  = HR_SYMPTOMS[i];
        var right = HR_SYMPTOMS[i + half];
        rows += '<tr style="background:' + (i % 2 === 0 ? '#fff' : '#f9fffe') + ';">';
        rows += '<td style="padding:5px 10px;border-bottom:1px solid #eee;">' + left[0] + '</td>';
        ['N','A','S'].forEach(function (v) {
            rows += '<td style="text-align:center;border-bottom:1px solid #eee;border-left:1px solid #eee;">' +
                '<input type="radio" name="sym-' + left[1] + '" value="' + v + '" id="sym-' + left[1] + '-' + v + '" style="accent-color:#2d6a4f;"/></td>';
        });
        if (right) {
            rows += '<td style="padding:5px 10px;border-bottom:1px solid #eee;border-left:2px solid #ccc;">' + right[0] + '</td>';
            ['N','A','S'].forEach(function (v) {
                rows += '<td style="text-align:center;border-bottom:1px solid #eee;border-left:1px solid #eee;">' +
                    '<input type="radio" name="sym-' + right[1] + '" value="' + v + '" id="sym-' + right[1] + '-' + v + '" style="accent-color:#2d6a4f;"/></td>';
            });
        } else {
            rows += '<td colspan="4" style="border-bottom:1px solid #eee;"></td>';
        }
        rows += '</tr>';
    }
    tbody.innerHTML = rows;
}

function sLoadHealthRecord(user) {
    if (!user || !user.identifier) { return; }
    sBuildSymptomTable();
    uhGet('/student/health-record?student_no=' + encodeURIComponent(user.identifier))
        .then(function (data) {
            var r = data.record || {};
            var setVal = function (id, val) {
                var el = document.getElementById(id);
                if (!el || val === null || val === undefined) { return; }
                el.value = val;
            };

            setVal('hr-record-date', r.record_date   ? r.record_date.substring(0,10)   : new Date().toISOString().split('T')[0]);
            setVal('hr-course-year', r.course_year   || user.dept        || '');
            setVal('hr-last-name',   r.last_name     || user.lastName    || '');
            setVal('hr-first-name',  r.first_name    || user.firstName   || '');
            setVal('hr-middle-name', r.middle_name   || user.middleName  || '');
            setVal('hr-address',     r.address       || user.address     || '');
            setVal('hr-contact',     r.contact       || user.contact     || '');
            setVal('hr-age',         r.age           || user.age         || '');
            setVal('hr-dob',         r.date_of_birth ? r.date_of_birth.substring(0,10) : '');
            setVal('hr-blood-type',  r.blood_type    || user.bloodType   || '');
            setVal('hr-height',      r.height_cm     || '');
            setVal('hr-weight',      r.weight_kg     || '');

            var sexVal   = r.sex          || user.sex         || '';
            var civilVal = r.civil_status || user.civilStatus || '';
            if (sexVal) {
                var sexEl = document.querySelector('input[name="hr-sex"][value="' + sexVal + '"]');
                if (sexEl) { sexEl.checked = true; }
            }
            if (civilVal) {
                var civEl = document.querySelector('input[name="hr-civil"][value="' + civilVal + '"]');
                if (civEl) { civEl.checked = true; }
            }

            setVal('hr-ec-name',    r.ec_name         || '');
            setVal('hr-ec-rel',     r.ec_relationship || '');
            setVal('hr-ec-contact', r.ec_contact      || '');
            setVal('hr-ec-address', r.ec_address      || '');
            setVal('hr-allergy',         r.allergy_detail         || '');
            setVal('hr-past-medical-history', r.past_medical_history || r.imported_medical_history || '');
            setVal('hr-accident',        r.accident_detail        || '');
            setVal('hr-hospitalization', r.hospitalization_detail || '');
            setVal('hr-surgical',        r.surgical_detail        || '');
            setVal('hr-family-history',  r.family_history         || '');

            HR_SYMPTOMS.forEach(function (sym) {
                var val = r[sym[1]] || 'N';
                var el  = document.getElementById('sym-' + sym[1] + '-' + val);
                if (el) { el.checked = true; }
            });

            var immMap = {
                'hr-imm-cp': 'imm_chicken_pox', 'hr-imm-ty': 'imm_typhoid',
                'hr-imm-ha': 'imm_hepatitis_a', 'hr-imm-hb': 'imm_hepatitis_b',
                'hr-imm-tt': 'imm_tetanus',     'hr-imm-cv': 'imm_covid',
            };
            Object.keys(immMap).forEach(function (elId) {
                var el = document.getElementById(elId);
                if (el) { el.checked = !!r[immMap[elId]]; }
            });

            var mensReg = document.getElementById('hr-mens-reg');
            var mensIrr = document.getElementById('hr-mens-irr');
            if (mensReg) { mensReg.checked = !!r.menstrual_regular; }
            if (mensIrr) { mensIrr.checked = !!r.menstrual_irregular; }
            if (r.dysmenorrhea) {
                var dysmEl = document.querySelector('input[name="hr-dysm"][value="' + r.dysmenorrhea + '"]');
                if (dysmEl) { dysmEl.checked = true; }
            }
            if (r.excessive_blood_flow) {
                var ebfEl = document.querySelector('input[name="hr-ebf"][value="' + r.excessive_blood_flow + '"]');
                if (ebfEl) { ebfEl.checked = true; }
            }
            setVal('hr-avg-days',  r.avg_days || '');
            setVal('hr-bp',        r.bp       || '');
            setVal('hr-pr',        r.pr       || '');
            setVal('hr-temp',      r.temp     || '');
            setVal('hr-signature', r.signature_name || '');

            if (r.findings_recommendation) {
                setVal('hr-findings', r.findings_recommendation);
                var sec = document.getElementById('hr-findings-section');
                if (sec) { sec.style.display = ''; }
            }

            var badge = document.getElementById('hr-status-badge');
            if (badge && r.status) { badge.innerHTML = uhBadge(r.status); }
        }).catch(function () {});
}

async function sSaveHealthRecord(e) {
    e.preventDefault();
    var btn = document.getElementById('s-hr-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

    var user = uhGetUser();
    if (!user || !user.identifier) {
        uhToast('Session expired. Please log in again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Health Record';
        return;
    }

    var symPayload = {};
    HR_SYMPTOMS.forEach(function (sym) {
        var checked = document.querySelector('input[name="sym-' + sym[1] + '"]:checked');
        symPayload[sym[1]] = checked ? checked.value : 'N';
    });

    var sexEl  = document.querySelector('input[name="hr-sex"]:checked');
    var civEl  = document.querySelector('input[name="hr-civil"]:checked');
    var dysmEl = document.querySelector('input[name="hr-dysm"]:checked');
    var ebfEl  = document.querySelector('input[name="hr-ebf"]:checked');

    var payload = Object.assign({
        student_no:             user.identifier,
        record_date:            document.getElementById('hr-record-date').value  || null,
        course_year:            document.getElementById('hr-course-year').value,
        last_name:              document.getElementById('hr-last-name').value,
        first_name:             document.getElementById('hr-first-name').value,
        middle_name:            document.getElementById('hr-middle-name').value,
        address:                document.getElementById('hr-address').value,
        contact:                document.getElementById('hr-contact').value,
        sex:                    sexEl  ? sexEl.value  : null,
        civil_status:           civEl  ? civEl.value  : null,
        age:                    document.getElementById('hr-age').value          || null,
        date_of_birth:          document.getElementById('hr-dob').value          || null,
        blood_type:             document.getElementById('hr-blood-type').value   || null,
        height_cm:              document.getElementById('hr-height').value       || null,
        weight_kg:              document.getElementById('hr-weight').value       || null,
        ec_name:                document.getElementById('hr-ec-name').value,
        ec_relationship:        document.getElementById('hr-ec-rel').value,
        ec_contact:             document.getElementById('hr-ec-contact').value,
        ec_address:             document.getElementById('hr-ec-address').value,
        allergy_detail:         document.getElementById('hr-allergy').value,
        past_medical_history:   document.getElementById('hr-past-medical-history').value,
        accident_detail:        document.getElementById('hr-accident').value,
        hospitalization_detail: document.getElementById('hr-hospitalization').value,
        surgical_detail:        document.getElementById('hr-surgical').value,
        family_history:         document.getElementById('hr-family-history').value,
        imm_chicken_pox:        document.getElementById('hr-imm-cp').checked,
        imm_typhoid:            document.getElementById('hr-imm-ty').checked,
        imm_hepatitis_a:        document.getElementById('hr-imm-ha').checked,
        imm_hepatitis_b:        document.getElementById('hr-imm-hb').checked,
        imm_tetanus:            document.getElementById('hr-imm-tt').checked,
        imm_covid:              document.getElementById('hr-imm-cv').checked,
        menstrual_regular:      document.getElementById('hr-mens-reg').checked,
        menstrual_irregular:    document.getElementById('hr-mens-irr').checked,
        dysmenorrhea:           dysmEl ? dysmEl.value : null,
        excessive_blood_flow:   ebfEl  ? ebfEl.value  : null,
        avg_days:               document.getElementById('hr-avg-days').value || null,
        signature_name:         document.getElementById('hr-signature').value,
    }, symPayload);

    try {
        var data = await uhPost('/student/health-record', payload);
        if (!data.ok || data.error) {
            uhToast(data.error || data.message || 'Health record could not be saved.', 'error');
        } else {
            uhToast('Health record saved successfully!', 'success');
            sLoadHealthRecord(user);
        }
    } catch {
        uhToast('Could not reach the server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Health Record';
    }
}
</script>
<script src="{{ asset('clinic/js/consultation-workflow.js') }}?v={{ filemtime(public_path('clinic/js/consultation-workflow.js')) }}"></script>
</body>
</html>
