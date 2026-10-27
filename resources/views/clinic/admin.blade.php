<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — SUNN Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/unihealth.css') }}?v={{ filemtime(public_path('clinic/css/unihealth.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/analytics.css') }}?v={{ filemtime(public_path('clinic/css/analytics.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/medical-record-entry.css') }}?v={{ filemtime(public_path('clinic/css/medical-record-entry.css')) }}"/>
    <style>
    /* ── Admin Dashboard ─────────────────────────────── */

    /* Campus tabs row */
    .ad-campus-tabs {
        display:flex; align-items:center; gap:4px;
        flex-wrap:wrap; margin-bottom:14px;
    }
    .ad-refresh-btn {
        margin-left:auto; width:30px; height:30px; border-radius:50%;
        border:1px solid #e5e7eb; background:#fff; cursor:pointer;
        display:flex; align-items:center; justify-content:center;
        color:#6b7280; font-size:13px;
        transition:background .14s, transform .6s;
    }
    .ad-refresh-btn:hover { background:#f3f4f6; color:#1a6b3a; }

    /* ── KPI strip ── */
    .ad-kpi-strip {
        display:grid;
        grid-template-columns:repeat(6,1fr);
        gap:10px;
        margin-bottom:12px;
    }
    @media(max-width:1280px){ .ad-kpi-strip { grid-template-columns:repeat(3,1fr); } }
    @media(max-width:720px)  { .ad-kpi-strip { grid-template-columns:1fr 1fr; } }

    .ad-kpi-card {
        background:#fff;
        border:1px solid #f0f0f0;
        border-radius:12px;
        padding:13px 14px 11px;
        display:flex; flex-direction:column;
        box-shadow:0 1px 3px rgba(0,0,0,.04);
        cursor:pointer;
        transition:box-shadow .18s, transform .18s;
        position:relative; overflow:hidden;
    }
    .ad-kpi-card::after {
        content:''; position:absolute; bottom:0; left:0; right:0; height:3px;
        opacity:0; transition:opacity .18s;
    }
    .ad-kpi-card:hover { box-shadow:0 5px 16px rgba(0,0,0,.08); transform:translateY(-2px); }
    .ad-kpi-card:hover::after { opacity:1; }
    .ad-kpi-card--green::after  { background:linear-gradient(90deg,#16a34a,#4ade80); }
    .ad-kpi-card--blue::after   { background:linear-gradient(90deg,#2563eb,#60a5fa); }
    .ad-kpi-card--teal::after   { background:linear-gradient(90deg,#0d9488,#2dd4bf); }
    .ad-kpi-card--indigo::after { background:linear-gradient(90deg,#4f46e5,#818cf8); }
    .ad-kpi-card--amber::after  { background:linear-gradient(90deg,#d97706,#fbbf24); }
    .ad-kpi-card--purple::after { background:linear-gradient(90deg,#7c3aed,#a78bfa); }

    .ad-kpi-icon {
        width:34px; height:34px; border-radius:9px;
        display:flex; align-items:center; justify-content:center;
        font-size:14px; margin-bottom:9px; flex-shrink:0;
    }
    .ad-kpi-card--green  .ad-kpi-icon { background:#dcfce7; color:#16a34a; }
    .ad-kpi-card--blue   .ad-kpi-icon { background:#dbeafe; color:#2563eb; }
    .ad-kpi-card--teal   .ad-kpi-icon { background:#ccfbf1; color:#0d9488; }
    .ad-kpi-card--indigo .ad-kpi-icon { background:#ede9fe; color:#4f46e5; }
    .ad-kpi-card--amber  .ad-kpi-icon { background:#fef3c7; color:#d97706; }
    .ad-kpi-card--purple .ad-kpi-icon { background:#f3e8ff; color:#7c3aed; }
    .ad-kpi-label {
        font-size:.64rem; font-weight:600; color:#9ca3af;
        text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .ad-kpi-value {
        font-size:1.65rem; font-weight:800; color:#111827;
        line-height:1; margin-bottom:4px; letter-spacing:-0.5px;
    }
    .ad-kpi-sub  { font-size:.67rem; color:#16a34a; font-weight:500; min-height:14px; }
    .ad-kpi-sub--danger { color:#dc2626; }

    /* ── Main 2-col grid ── */
    .ad-main-grid {
        display:grid;
        grid-template-columns:1fr 300px;
        gap:10px;
        margin-bottom:10px;
        align-items:stretch;
    }
    @media(max-width:1100px){ .ad-main-grid { grid-template-columns:1fr; } }
    .ad-right-col { display:flex; flex-direction:column; gap:10px; }
    .ad-main-grid > .ad-card:first-child { display:flex; flex-direction:column; }
    .ad-main-grid > .ad-card:first-child .an-canvas-wrap { flex:1; min-height:320px; }
    .ad-settings-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:16px;
        align-items:stretch;
    }
    .ad-settings-grid > .uh-card { margin:0; min-width:0; }
    .ad-settings-data-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .ad-model-registry-table { width:100%; table-layout:fixed; }
    .ad-model-registry-table th:first-child,
    .ad-model-registry-table td:first-child { width:24%; }
    .ad-model-registry-table th:last-child { width:190px; text-align:center; }
    .ad-model-registry-table td:nth-child(2) { overflow-wrap:anywhere; }
    .ad-model-registry-actions {
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        white-space:nowrap;
    }
    .ad-model-registry-actions .uh-btn { margin:0; min-width:48px; }
    @media(max-width:900px) {
        .ad-settings-grid { grid-template-columns:minmax(0, 1fr); }
    }

    /* ── Card ── */
    .ad-card {
        background:#fff;
        border:1px solid #f0f0f0;
        border-radius:12px;
        padding:14px 16px;
        box-shadow:0 1px 3px rgba(0,0,0,.04);
    }
    .ad-mb-12 { margin-bottom:10px; }
    .ad-card-header {
        display:flex; align-items:center; justify-content:space-between;
        margin-bottom:9px;
    }
    .ad-card-title {
        font-size:.85rem; font-weight:700; color:#111827;
        display:flex; align-items:center; gap:6px;
    }
    .ad-card-sub { font-size:.68rem; color:#9ca3af; margin:-2px 0 8px; line-height:1.4; }
    .ad-patient-actions {
        display: grid;
        grid-template-columns: max-content max-content;
        justify-content: start;
        align-items: center;
        gap: 4px 5px;
        min-width: 112px;
    }
    .ad-patient-actions .uh-btn { margin: 0; white-space: nowrap; }
    .ad-live-badge {
        display:inline-flex; align-items:center; gap:4px;
        background:#dcfce7; color:#16a34a;
        font-size:.64rem; font-weight:700;
        padding:2px 7px; border-radius:20px; text-transform:uppercase;
    }
    .ad-live-dot {
        width:5px; height:5px; border-radius:50%; background:#16a34a;
        animation:ad-pulse 1.4s ease-in-out infinite;
    }
    @keyframes ad-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
    .ad-period-badge {
        font-size:.65rem; font-weight:600;
        background:#f0f9ff; color:#0284c7;
        border:1px solid #bae6fd; border-radius:20px; padding:2px 7px;
    }
    .ad-link-btn {
        font-size:.7rem; font-weight:600; color:#6b7280;
        background:none; border:none; cursor:pointer;
        display:flex; align-items:center; gap:4px; transition:color .14s;
        white-space:nowrap;
    }
    .ad-link-btn:hover { color:#1a6b3a; }

    /* Icon accent colours */
    .ad-icon-blue   { color:#2563eb; }
    .ad-icon-indigo { color:#4f46e5; }
    .ad-icon-teal   { color:#0d9488; }
    .ad-icon-green  { color:#16a34a; }
    .ad-icon-amber  { color:#d97706; }

    /* Appointment doughnut + legend */
    .ad-appt-wrap { display:flex; align-items:center; gap:12px; }
    .ad-appt-legend {
        flex:1; list-style:none; padding:0; margin:0;
        display:flex; flex-direction:column; gap:6px; font-size:.73rem;
    }
    .ad-appt-legend li { display:flex; align-items:center; gap:6px; }
    .ad-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .ad-legend-name { flex:1; color:#374151; font-weight:500; }
    .ad-legend-val  { font-weight:700; color:#111827; }

    /* Quick stats */
    .ad-quick-stats { display:flex; flex-direction:column; gap:0; }
    .ad-qs-item {
        display:flex; align-items:center; justify-content:space-between;
        padding:5px 0; border-bottom:1px solid #f9fafb; font-size:.75rem;
    }
    .ad-qs-item:last-child { border-bottom:none; }
    .ad-qs-label { color:#6b7280; font-weight:500; }
    .ad-qs-val   { font-weight:700; color:#111827; }
    .ad-qs-val--danger  { color:#dc2626; }
    .ad-qs-val--success { color:#16a34a; }

    /* ── 2-col chart row ── */
    .ad-chart-3col {
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:10px;
        margin-bottom:10px;
    }
    @media(max-width:1024px){ .ad-chart-3col { grid-template-columns:1fr; } }

    .ad-chart-card {
        background:#fff;
        border:1px solid #f0f0f0;
        border-radius:12px;
        overflow:hidden;
        display:flex; flex-direction:column;
        min-height:300px;
        box-shadow:0 1px 4px rgba(0,0,0,.05);
        transition:box-shadow .18s, transform .18s;
    }
    .ad-chart-card:hover { box-shadow:0 5px 18px rgba(0,0,0,.08); transform:translateY(-2px); }
    .ad-chart-card-accent { height:3px; flex-shrink:0; }
    .ad-accent-indigo { background:linear-gradient(90deg,#4f46e5,#818cf8); }
    .ad-accent-teal   { background:linear-gradient(90deg,#0d9488,#2dd4bf); }
    .ad-accent-green  { background:linear-gradient(90deg,#16a34a,#4ade80); }
    .ad-chart-card-body { padding:14px 16px; display:flex; flex-direction:column; flex:1; }
    .ad-chart-card-head {
        display:flex; align-items:flex-start; justify-content:space-between;
        margin-bottom:10px;
    }
    .ad-chart-title { font-size:.85rem; font-weight:700; color:#111827; }
    .ad-chart-sub   { font-size:.67rem; color:#9ca3af; margin-top:2px; }
    .ad-chart-icon {
        width:32px; height:32px; border-radius:9px;
        display:flex; align-items:center; justify-content:center;
        font-size:13px; flex-shrink:0;
    }
    .ad-chart-icon--indigo { background:#ede9fe; color:#4f46e5; }
    .ad-chart-icon--teal   { background:#ccfbf1; color:#0d9488; }
    .ad-chart-icon--green  { background:#dcfce7; color:#16a34a; }

    /* ── Bottom analytics grid ── */
    .ad-bottom-grid {
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:10px;
        margin-bottom:10px;
    }
    @media(max-width:960px){ .ad-bottom-grid { grid-template-columns:1fr; } }
    </style>
    <link rel="stylesheet" href="{{ asset('clinic/css/dashboard-card-theme.css') }}?v={{ filemtime(public_path('clinic/css/dashboard-card-theme.css')) }}"/>
    <script src="{{ asset('clinic/js/dashboard-card-motion.js') }}?v={{ filemtime(public_path('clinic/js/dashboard-card-motion.js')) }}" defer></script>
    <script>
        // Auth is handled server-side by ClinicSessionAuth middleware.
        // We only read sessionStorage here to populate window.__clinicUser
        // for JS helpers that need the user object (name, role, campus, etc.).
        (function () {
            var raw = sessionStorage.getItem('clinic_user');
            try { window.__clinicUser = raw ? JSON.parse(raw) : null; } catch (e) { window.__clinicUser = null; }
        }());
    </script>
</head>
<body class="dashboard-card-theme">

<div class="uh-layout">

    <!-- -- Sidebar ----------------------------------- -->
    <x-clinic.sidebar role="admin">
        <button class="uh-nav-item active" onclick="uhShowPage('dashboard',this)" data-title="Dashboard">
            <i class="fa fa-gauge-high"></i><span>Dashboard</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('patients',this)" data-title="Patient Information">
            <i class="fa fa-users"></i><span>Patient Information</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('patient-accounts',this);adLoadPatientAccounts(1)" data-title="Instructor / Faculty &amp; Staff">
            <i class="fa fa-user-group"></i><span>Instructor / Faculty &amp; Staff</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('medical-records',this)" data-title="Medical Records">
            <i class="fa fa-file-medical"></i><span>Medical Records</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('appointments',this)" data-title="Appointments &amp; Queues">
            <i class="fa fa-calendar-check"></i><span>Appointments &amp; Queues</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('referrals',this)" data-title="Referrals">
            <i class="fa fa-share-from-square"></i><span>Referrals</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('clinicians',this);adLoadClinicians()" data-title="Physicians &amp; Dentists">
            <i class="fa fa-user-doctor"></i><span>Physicians &amp; Dentists</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('users',this)" data-title="Users &amp; Permissions">
            <i class="fa fa-user-shield"></i><span>Users &amp; Permissions</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('settings',this);adLoadAiModelSettings()" data-title="System Settings">
            <i class="fa fa-gear"></i><span>System Settings</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('audit',this)" data-title="Audit Logs">
            <i class="fa fa-shield-halved"></i><span>Audit Logs</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('backup',this)" data-title="Backup &amp; Restore">
            <i class="fa fa-database"></i><span>Backup &amp; Restore</span>
        </button>
    </x-clinic.sidebar>

    <!-- -- Main -------------------------------------- -->
    <div class="uh-main">

        <x-clinic.topbar />

        <div class="uh-content">

            <!-- -- PAGE: DASHBOARD ------------------- -->
            <div class="uh-page active" id="page-dashboard">

                <!-- Campus Filter Tabs -->
                <div class="uh-campus-tabs ad-campus-tabs">
                    <button class="uh-campus-tab active" onclick="adFilterCampus('all',this)">All Campuses</button>
                    <button class="uh-campus-tab" onclick="adFilterCampus(2,this)">Main Campus</button>
                    <button class="uh-campus-tab" onclick="adFilterCampus(1,this)">Old Sagay Campus</button>
                    <button class="uh-campus-tab" onclick="adFilterCampus(4,this)">Escalante Campus</button>
                    <button class="uh-campus-tab" onclick="adFilterCampus(3,this)">Laga-an Campus</button>
                    <button class="ad-refresh-btn" onclick="adReloadDashboard(this)" title="Refresh">
                        <i class="fa fa-rotate-right"></i>
                    </button>
                </div>

                <!-- ====== KPI STRIP (6 cards) ====== -->
                <div class="ad-kpi-strip">

                    <div class="ad-kpi-card ad-kpi-card--green" onclick="uhShowPage('patients',document.querySelector('[data-title=\'Patient Information\']'))">
                        <div class="ad-kpi-icon"><i class="fa fa-users"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Total Patients</div>
                            <div class="ad-kpi-value" id="ad-stat-patients">—</div>
                            <div class="ad-kpi-sub" id="ad-patients-sub">Active roster</div>
                        </div>
                    </div>

                    <div class="ad-kpi-card ad-kpi-card--blue">
                        <div class="ad-kpi-icon"><i class="fa fa-stethoscope"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Total Visits</div>
                            <div class="ad-kpi-value" id="ad-stat-visits">—</div>
                            <div class="ad-kpi-sub" id="ad-visits-sub">All time</div>
                        </div>
                    </div>

                    <div class="ad-kpi-card ad-kpi-card--teal">
                        <div class="ad-kpi-icon"><i class="fa fa-heart-pulse"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Today's Visits</div>
                            <div class="ad-kpi-value" id="ad-stat-today">—</div>
                            <div class="ad-kpi-sub" id="ad-today-sub">Live count</div>
                        </div>
                    </div>

                    <div class="ad-kpi-card ad-kpi-card--indigo" onclick="uhShowPage('appointments',document.querySelector('[data-title=\'Appointments &amp; Queues\']'))">
                        <div class="ad-kpi-icon"><i class="fa fa-calendar-check"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Today's Appointments</div>
                            <div class="ad-kpi-value" id="ad-stat-appts">—</div>
                            <div class="ad-kpi-sub" id="ad-appts-sub">Scheduled today</div>
                        </div>
                    </div>

                    <div class="ad-kpi-card ad-kpi-card--amber" onclick="uhShowPage('referrals',document.querySelector('[data-title=\'Referrals\']'))">
                        <div class="ad-kpi-icon"><i class="fa fa-share-from-square"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Pending Referrals</div>
                            <div class="ad-kpi-value" id="ad-stat-refs">—</div>
                            <div class="ad-kpi-sub ad-kpi-sub--danger" id="ad-refs-sub">Needs attention</div>
                        </div>
                    </div>

                    <div class="ad-kpi-card ad-kpi-card--purple" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users &amp; Permissions\']'))">
                        <div class="ad-kpi-icon"><i class="fa fa-user-tie"></i></div>
                        <div class="ad-kpi-body">
                            <div class="ad-kpi-label">Active Staff</div>
                            <div class="ad-kpi-value" id="ad-stat-staff">—</div>
                            <div class="ad-kpi-sub">Healthcare providers</div>
                        </div>
                    </div>

                </div><!-- /.ad-kpi-strip -->

                <!-- ====== MAIN 2-COL ROW ====== -->
                <div class="ad-main-grid">

                    <!-- Left: Visit Trend Line Chart -->
                    <div class="ad-card">
                        <div class="ad-card-header">
                            <div class="ad-card-title">
                                <i class="fa fa-chart-line ad-icon-blue"></i>
                                Visit Trends — Last 13 Days
                            </div>
                            <span class="ad-live-badge"><span class="ad-live-dot"></span>Live</span>
                        </div>
                        <p class="ad-card-sub">Daily clinic visit volume for days 1–13 (oldest to most recent)</p>
                        <div class="an-canvas-wrap">
                            <canvas id="an-chart-daily"></canvas>
                        </div>
                    </div>

                    <!-- Right: Appointment status + quick stats -->
                    <div class="ad-right-col">

                        <!-- Appointment status donut -->
                        <div class="ad-card ad-mb-12">
                            <div class="ad-card-header" style="margin-bottom:10px">
                                <div class="ad-card-title">
                                    <i class="fa fa-calendar-check ad-icon-indigo"></i>
                                    Appointment Breakdown
                                </div>
                            </div>
                            <div class="ad-appt-wrap">
                                <div class="an-canvas-wrap" style="height:150px;width:150px;flex-shrink:0">
                                    <canvas id="ad-appt-donut"></canvas>
                                </div>
                                <ul class="ad-appt-legend" id="ad-appt-legend">
                                    <li><span class="ad-dot" style="background:#16a34a"></span><span class="ad-legend-name">Completed</span><span class="ad-legend-val" id="ad-appt-completed">—</span></li>
                                    <li><span class="ad-dot" style="background:#2563eb"></span><span class="ad-legend-name">Confirmed</span><span class="ad-legend-val" id="ad-appt-confirmed">—</span></li>
                                    <li><span class="ad-dot" style="background:#d97706"></span><span class="ad-legend-name">Pending</span><span class="ad-legend-val" id="ad-appt-pending">—</span></li>
                                    <li><span class="ad-dot" style="background:#dc2626"></span><span class="ad-legend-name">Cancelled</span><span class="ad-legend-val" id="ad-appt-cancelled">—</span></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Quick stats -->
                        <div class="ad-card">
                            <div class="ad-card-title" style="margin-bottom:12px">
                                <i class="fa fa-bolt ad-icon-amber"></i>
                                Quick Stats
                            </div>
                            <div class="ad-quick-stats">
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">Visits (7 days)</span>
                                    <span class="ad-qs-val" id="ad-qs-7d">—</span>
                                </div>
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">Visits (30 days)</span>
                                    <span class="ad-qs-val" id="ad-qs-30d">—</span>
                                </div>
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">Urgent Referrals</span>
                                    <span class="ad-qs-val ad-qs-val--danger" id="ad-qs-urgent">—</span>
                                </div>
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">New Patients (week)</span>
                                    <span class="ad-qs-val" id="ad-qs-new-week">—</span>
                                </div>
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">Referrals Resolved</span>
                                    <span class="ad-qs-val ad-qs-val--success" id="ad-qs-refs-done">—</span>
                                </div>
                                <div class="ad-qs-item">
                                    <span class="ad-qs-label">Predicted Traffic</span>
                                    <span class="ad-qs-val" id="ad-qs-predicted">—</span>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.ad-right-col -->

                </div><!-- /.ad-main-grid -->

                <div class="ad-card ad-mb-12">
                    <div class="ad-card-header">
                        <div class="ad-card-title">
                            <i class="fa fa-file-medical ad-icon-blue"></i>
                            Clinic Records
                        </div>
                        <button class="ad-link-btn" onclick="uhShowPage('medical-records',document.querySelector('[data-title=\'Medical Records\']'))">
                            Open Medical Records <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                    <div class="uh-table-toolbar">
                        <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search clinic records..." autocomplete="off" aria-label="Search clinic records">
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderClinicRecordsSearch">
                            <thead>
                                <tr><th>Date</th><th>Patient</th><th>Student ID</th><th>Reason</th><th>Vitals</th><th>Recorded By</th><th>Status</th><th>Record</th></tr>
                            </thead>
                            <tbody id="ad-records-body" aria-live="polite">
                                <tr><td colspan="8" class="no-data">Loading clinic records…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="ad-records-pagination" class="uh-flex uh-items-center uh-justify-between uh-mt-12">
                        <span id="ad-records-page-label" class="uh-text-sm uh-text-muted">Loading records…</span>
                        <div class="uh-flex uh-gap-8">
                            <button type="button" id="ad-records-prev" class="uh-btn uh-btn-ghost uh-btn-sm" onclick="adLoadClinicRecords(adClinicRecordsPage - 1)" disabled>Previous</button>
                            <button type="button" id="ad-records-next" class="uh-btn uh-btn-ghost uh-btn-sm" onclick="adLoadClinicRecords(adClinicRecordsPage + 1)" disabled>Next</button>
                        </div>
                    </div>
                </div>

                <!-- ====== 3-COL CHART ROW ====== -->
                <div class="ad-chart-3col">

                    <!-- Chief Complaints -->
                    <div class="ad-chart-card">
                        <div class="ad-chart-card-accent ad-accent-indigo"></div>
                        <div class="ad-chart-card-body">
                            <div class="ad-chart-card-head">
                                <div>
                                    <div class="ad-chart-title">Chief Complaints</div>
                                    <div class="ad-chart-sub">Top 6 student complaints by campus (last 12 months)</div>
                                </div>
                                <div class="ad-chart-icon ad-chart-icon--indigo"><i class="fa fa-stethoscope"></i></div>
                            </div>
                            <div class="an-canvas-wrap" style="flex:1;position:relative;min-height:210px">
                                <canvas id="an-chart-complaints"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Department Distribution -->
                    <div class="ad-chart-card">
                        <div class="ad-chart-card-accent ad-accent-teal"></div>
                        <div class="ad-chart-card-body">
                            <div class="ad-chart-card-head">
                                <div>
                                    <div class="ad-chart-title">Department Distribution</div>
                                    <div class="ad-chart-sub">Patients by college / dept</div>
                                </div>
                                <div class="ad-chart-icon ad-chart-icon--teal"><i class="fa fa-building-columns"></i></div>
                            </div>
                            <div class="an-canvas-wrap tall" style="flex:1;position:relative;min-height:210px">
                                <canvas id="an-chart-dept"></canvas>
                            </div>
                        </div>
                    </div>

                </div><!-- /.ad-chart-3col -->

                <!-- ====== VISIT, COMPLAINT, DEPARTMENT, AND CAMPUS ANALYTICS ====== -->
                <div class="ad-bottom-grid">

                    <!-- Monthly visits bar -->
                    <div class="ad-card">
                        <div class="ad-card-header">
                            <div class="ad-card-title">
                                <i class="fa fa-chart-bar ad-icon-indigo"></i>
                                Monthly Visit Volume
                            </div>
                            <span class="ad-period-badge">Current Year</span>
                        </div>
                        <p class="ad-card-sub">Visits per month</p>
                        <div class="an-canvas-wrap" style="height:200px">
                            <canvas id="an-chart-monthly"></canvas>
                        </div>
                    </div>

                    <!-- Complaints by student department -->
                    <div class="ad-card">
                        <div class="ad-card-header">
                            <div class="ad-card-title">
                                <i class="fa fa-building ad-icon-teal"></i>
                                Complaints by Department
                            </div>
                            <span class="ad-period-badge">Last 12 Months</span>
                        </div>
                        <p class="ad-card-sub">Documented complaints grouped by student department</p>
                        <div class="an-canvas-wrap" style="height:200px">
                            <canvas id="an-chart-complaint-dept"></canvas>
                        </div>
                    </div>

                    <!-- Monthly complaint trend -->
                    <div class="ad-card">
                        <div class="ad-card-header">
                            <div class="ad-card-title">
                                <i class="fa fa-comment-medical ad-icon-indigo"></i>
                                Monthly Complaint Trend
                            </div>
                            <span class="ad-period-badge">Last 12 Months</span>
                        </div>
                        <p class="ad-card-sub">Documented student complaints by submission month</p>
                        <div class="an-canvas-wrap" style="height:200px">
                            <canvas id="an-chart-complaint-trend"></canvas>
                        </div>
                    </div>

                    <!-- Campus comparison grouped bar -->
                    <div class="ad-card">
                        <div class="ad-card-header">
                            <div class="ad-card-title">
                                <i class="fa fa-map-location-dot ad-icon-teal"></i>
                                Campus Comparison
                            </div>
                            <div style="display:flex;gap:8px;align-items:center">
                                <span class="ad-period-badge">Last 6 Months</span>
                                <button class="ad-link-btn" onclick="adExport()"><i class="fa fa-download"></i> Export</button>
                            </div>
                        </div>
                        <p class="ad-card-sub">Monthly visits across all campuses</p>
                        <div class="an-canvas-wrap" style="height:200px">
                            <canvas id="an-chart-campus"></canvas>
                        </div>
                    </div>

                </div><!-- /.ad-bottom-grid -->

                <section class="nh-pm-decision-card ad-mb-12" aria-labelledby="an-decision-support-title">
                    <div class="nh-ds-header">
                        <div>
                            <div class="nh-ds-title" id="an-decision-support-title">
                                <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                AI Decision Support
                            </div>
                            <div class="nh-ds-subtitle">Evidence-backed campus and clinic operations insights</div>
                        </div>
                        <div class="nh-ds-header-actions">
                            <button type="button" class="nh-ds-analyze-button" data-ai-analysis-button disabled>
                                <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                <span class="nh-ds-analyze-label">Analyze with AI</span>
                            </button>
                            <span class="nh-ds-badge">LIVE RECORDS</span>
                        </div>
                    </div>
                    <div class="nh-ds-insights" id="an-decision-support-insights" aria-live="polite">
                        <div class="nh-ds-state">Loading insights from clinic reports…</div>
                    </div>
                    <div class="nh-ds-disclaimer">Insights use aggregate clinic report data and are operational guidance only, not a diagnosis or treatment recommendation.</div>
                </section>

                <!-- Hidden legacy IDs needed by loadAdminAnalytics / loadPerfMetrics -->
                <span id="an-kpi-patients"       style="display:none"></span>
                <span id="an-kpi-visits"          style="display:none"></span>
                <span id="an-kpi-today"           style="display:none"></span>
                <span id="an-kpi-appts"           style="display:none"></span>
                <span id="an-kpi-pending-appts"   style="display:none"></span>
                <span id="an-kpi-referrals"       style="display:none"></span>
                <span id="ad-pm-patients"         style="display:none"></span>
                <span id="ad-pm-visits"           style="display:none"></span>
                <span id="ad-pm-appts"            style="display:none"></span>
                <span id="ad-pm-refs"             style="display:none"></span>
                <span id="ad-pm-donut-patients"   style="display:none"></span>
                <span id="ad-pm-donut-visits"     style="display:none"></span>
                <span id="ad-pm-donut-appts"      style="display:none"></span>
                <span id="ad-pm-donut-refs"       style="display:none"></span>
                <span id="ad-pm-donut-patients-pct" style="display:none"></span>
                <span id="ad-pm-donut-visits-pct"   style="display:none"></span>
                <span id="ad-pm-donut-appts-pct"    style="display:none"></span>
                <span id="ad-pm-donut-refs-pct"     style="display:none"></span>
                <span id="ad-pm-trend-chart"      style="display:none"></span>
                <span id="ad-pm-activity"         style="display:none"></span>
                <span id="ad-pm-goal-daily-bar"   style="display:none"></span>
                <span id="ad-pm-goal-appt-bar"    style="display:none"></span>
                <span id="ad-pm-goal-ref-bar"     style="display:none"></span>
                <span id="ad-pm-goal-daily-pct"   style="display:none"></span>
                <span id="ad-pm-goal-appt-pct"    style="display:none"></span>
                <span id="ad-pm-goal-ref-pct"     style="display:none"></span>

            </div><!-- /#page-dashboard -->

            <!-- -- PAGE: USERS ----------------------- -->
            <div class="uh-page" id="page-users">
                <!-- Tabs -->
                <div class="uh-campus-tabs uh-mb-16">
                    <button class="uh-campus-tab active" onclick="adUsersTab('users',this)">Users</button>
                    <button class="uh-campus-tab" onclick="adUsersTab('roles',this)">Roles &amp; Permissions</button>
                </div>

                <!-- Users Panel -->
                <div id="ad-panel-users">
                    <div class="uh-card">
                        <div class="uh-card-header">
                            <div class="uh-card-title">User Accounts</div>
                            <div class="uh-flex uh-gap-8">
                                <button class="uh-btn uh-btn-primary uh-btn-sm" onclick="uhOpenModal('ad-add-user-modal')">
                                    <i class="fa fa-plus"></i> Add User
                                </button>
                            </div>
                        </div>
                        <div class="uh-table-toolbar">
    <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
    <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                            <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="users">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Campus</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ad-users-body">
                                    <tr><td colspan="6" class="no-data">Loading users...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="pg-ad-users"></div>
                    </div>
                </div>

                <!-- Roles Panel -->
                <div id="ad-panel-roles" class="uh-hidden">
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-16">Roles &amp; Permissions</div>
                        <div class="uh-table-toolbar">
    <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
    <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                            <table class="uh-table" data-uh-searchable>
                                <thead><tr><th>Role</th><th>Users</th><th>Permissions</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><span class="uh-badge role-admin">Admin</span></td>
                                        <td>2</td>
                                        <td>Full system access</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                    <tr>
                                        <td><span class="uh-badge role-nurse">Nurse Head</span></td>
                                        <td>1</td>
                                        <td>Manage staff, view all records, reports</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                    <tr>
                                        <td><span class="uh-badge role-nurse">Nurse</span></td>
                                        <td>5</td>
                                        <td>Vitals, encounters, forms, queue</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                    <tr>
                                        <td><span class="uh-badge role-physician">Physician</span></td>
                                        <td>3</td>
                                        <td>Diagnose, prescribe, refer</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                    <tr>
                                        <td><span class="uh-badge role-dentist">Dentist</span></td>
                                        <td>2</td>
                                        <td>Dental records, prescriptions</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                    <tr>
                                        <td><span class="uh-badge role-student">Student</span></td>
                                        <td>1,284</td>
                                        <td>Self-service: appointments, records, prescriptions</td>
                                        <td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhShowPage('users',document.querySelector('[data-title=\'Users\']'))">Manage</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add User Modal -->
            <div class="uh-modal-overlay hidden" id="ad-add-user-modal">
                <div class="uh-modal">
                    <div class="uh-modal-header">
                        <div class="uh-modal-title"><i class="fa fa-user-plus uh-icon-primary uh-mr-8"></i> Add User</div>
                        <button class="uh-modal-close" onclick="uhCloseModal('ad-add-user-modal')">?</button>
                    </div>
                    <div class="uh-form-row">
                        <div class="uh-form-group"><label>First Name *</label><input type="text" id="ad-u-first"/></div>
                        <div class="uh-form-group"><label>Last Name *</label><input type="text" id="ad-u-last"/></div>
                    </div>
                    <div class="uh-form-group"><label>Email *</label><input type="email" id="ad-u-email" placeholder="user@sunn.edu.ph"/></div>
                    <div class="uh-form-row">
                        <div class="uh-form-group">
                            <label>Role *</label>
                            <select id="ad-u-role">
                                <option value="">— Select —</option>
                                <option value="nurse">Nurse</option>
                                <option value="nurse_head">Head Nurse</option>
                                <option value="physician">Physician</option>
                                <option value="dentist">Dentist</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="uh-form-group">
                            <label>Campus</label>
                            <select id="ad-u-campus">
                                <option value="">— All Campuses (system-wide access) —</option>
                                <option value="2">Main Campus</option>
                                <option value="1">Old Sagay Campus</option>
                                <option value="4">Escalante Campus</option>
                                <option value="3">Laga-an Campus</option>
                            </select>
                            <small class="uh-text-muted" style="display:block;margin-top:4px;font-size:0.75rem;">
                                Pick a specific campus to limit this user's access, or leave as "All Campuses" to grant system-wide access.
                            </small>
                        </div>
                    </div>
                    <div class="uh-form-group"><label>Password *</label><input type="password" id="ad-u-pw" placeholder="Min. 6 characters" autocomplete="new-password"/></div>
                    <div class="uh-form-group"><label>Confirm Password *</label><input type="password" id="ad-u-pw-confirm" placeholder="Re-type the password" autocomplete="new-password"/></div>
                    <div class="uh-modal-footer">
                        <button class="uh-btn uh-btn-ghost" onclick="uhCloseModal('ad-add-user-modal')">Cancel</button>
                        <button class="uh-btn uh-btn-primary" onclick="adAddUser()"><i class="fa fa-save"></i> Save</button>
                    </div>
                </div>
            </div>

            <!-- -- PAGE: PATIENTS ------------------- -->
            <div class="uh-page" id="page-patients">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Patient Information</div>
                        <div class="uh-flex uh-gap-8">
                            <button class="uh-btn uh-btn-primary uh-btn-sm" onclick="adOpenPatientForm()">
                                <i class="fa fa-plus"></i> Add Patient
                            </button>
                        </div>
                    </div>
                    <div class="uh-table-toolbar">
    <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
    <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="patients">
                            <thead>
                                <tr><th>Name</th><th>ID</th><th>Dept</th><th>Email</th><th>Sex</th><th>Campus</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="ad-patients-body">
                                <tr><td colspan="7" class="no-data">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                <div id="pg-ad-patients"></div>
                </div>
            </div>

            <!-- -- PAGE: INSTRUCTOR / FACULTY & STAFF -->
            <div class="uh-page" id="page-patient-accounts">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Instructor / Faculty &amp; Staff Patient Accounts</div>
                        <button type="button" class="uh-btn uh-btn-primary uh-btn-sm" onclick="adOpenPatientAccountForm()">
                            <i class="fa fa-plus" aria-hidden="true"></i> Add Account
                        </button>
                    </div>
                    <div class="uh-table-toolbar">
                        <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search name, ID, department, or email..." autocomplete="off" aria-label="Search instructor, faculty, and staff accounts">
                        <select id="ad-patient-accounts-status" class="uh-table-status-filter" aria-label="Filter patient account status" onchange="adPatientAccountsStatusChanged(this.value)">
                            <option value="active">Active accounts</option>
                            <option value="inactive">Inactive accounts</option>
                            <option value="all">All accounts</option>
                        </select>
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="patientAccounts">
                            <thead>
                                <tr><th>Name</th><th>Account ID</th><th>Category</th><th>Department / Office</th><th>Email</th><th>Campus</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="ad-patient-accounts-body">
                                <tr><td colspan="8" class="no-data">Loading accounts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pg-ad-patient-accounts"></div>
                </div>
            </div>

            <x-clinic.medical-record-entry />

            <!-- -- PAGE: APPOINTMENTS --------------- -->
            <div class="uh-page" id="page-appointments">
                <div class="uh-grid-5 uh-mb-16">
                    <div class="uh-stat-card"><div class="uh-stat-icon blue"><i class="fa fa-calendar"></i></div><div class="uh-stat-number" id="ad-appt-stat-total">—</div><div class="uh-stat-label">Total</div></div>
                    <div class="uh-stat-card"><div class="uh-stat-icon amber"><i class="fa fa-hourglass-half"></i></div><div class="uh-stat-number" id="ad-appt-stat-pending">—</div><div class="uh-stat-label">Pending</div></div>
                    <div class="uh-stat-card"><div class="uh-stat-icon blue"><i class="fa fa-calendar-check"></i></div><div class="uh-stat-number" id="ad-appt-stat-confirmed">—</div><div class="uh-stat-label">Confirmed</div></div>
                    <div class="uh-stat-card"><div class="uh-stat-icon green"><i class="fa fa-check"></i></div><div class="uh-stat-number" id="ad-appt-stat-completed">—</div><div class="uh-stat-label">Completed</div></div>
                    <div class="uh-stat-card"><div class="uh-stat-icon red"><i class="fa fa-ban"></i></div><div class="uh-stat-number" id="ad-appt-stat-cancelled">—</div><div class="uh-stat-label">Cancelled</div></div>
                </div>
                <div class="uh-card">
                    <div class="uh-card-header"><div class="uh-card-title">Appointments</div></div>
                    <div class="uh-table-toolbar">
                        <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off" aria-label="Search appointments">
                        <select id="ad-appointment-status-filter" class="uh-table-status-filter" aria-label="Filter appointments by status" onchange="adApplyAppointmentFilters()">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted — awaiting schedule</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="appointments">
                            <thead><tr><th><button type="button" class="uh-table-sort" onclick="adToggleTimeSort('appointments')" aria-label="Sort appointments by date and time">Date / time <span id="ad-appointments-sort-indicator">↓</span></button></th><th>Patient</th><th>Type</th><th>Healthcare Professional</th><th>Priority / confidence</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="ad-appts-body">
                                <tr><td colspan="7" class="no-data">Loading appointments...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pg-ad-appointments"></div>
                </div>
            </div>

            <!-- -- PAGE: REFERRALS ------------------ -->
            <div class="uh-page" id="page-referrals">
                <div class="uh-card">
                    <div class="uh-card-header"><div class="uh-card-title">Referrals</div></div>
                    <div class="uh-table-toolbar" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search referrals..." autocomplete="off" aria-label="Search referrals">
                        <select id="ad-referral-campus-filter" class="uh-table-status-filter" aria-label="Filter referrals by campus" onchange="adApplyReferralFilters()">
                            <option value="">All campuses</option>
                        </select>
                        <input type="search" id="ad-referral-to-filter" class="uh-table-search" placeholder="Filter by referred to..." autocomplete="off" aria-label="Filter referrals by referred-to destination" oninput="adScheduleReferralFilter()">
                        <span class="uh-table-count" data-uh-table-count></span>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="referrals">
                            <thead><tr><th>ID</th><th>Patient</th><th>From</th><th>Referred To</th><th>Reason</th><th><button type="button" class="uh-table-sort" onclick="adToggleTimeSort('referrals')" aria-label="Sort referrals by date and time">Date / time <span id="ad-referrals-sort-indicator">↓</span></button></th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="ad-referrals-body"><tr><td colspan="8" class="no-data">Loading referrals...</td></tr></tbody>
                        </table>
                    </div>
                    <div id="pg-ad-referrals"></div>
                </div>
            </div>

            <!-- -- PAGE: CLINICIANS ----------------- -->
            <div class="uh-page" id="page-clinicians">
                <div class="uh-grid uh-grid-2 uh-gap-16">
                    <section class="uh-card">
                        <div class="uh-card-header">
                            <div>
                                <div class="uh-card-title" id="ad-clinician-form-title">Add Physician or Dentist</div>
                                <p class="uh-text-muted uh-text-sm">Directory entries are for form signatories only and do not create user accounts.</p>
                            </div>
                        </div>
                        <form id="ad-clinician-form">
                            <input type="hidden" id="ad-clinician-id"/>
                            <div class="uh-grid uh-grid-2 uh-gap-12">
                                <div class="uh-form-group">
                                    <label for="ad-clinician-name">Full name <span class="uh-text-danger">*</span></label>
                                    <input type="text" id="ad-clinician-name" maxlength="150" required/>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-type">Type <span class="uh-text-danger">*</span></label>
                                    <select id="ad-clinician-type" required>
                                        <option value="physician">Physician</option>
                                        <option value="dentist">Dentist</option>
                                    </select>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-campus">Campus availability</label>
                                    <select id="ad-clinician-campus">
                                        <option value="">Available to all campuses</option>
                                    </select>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-designation">Title / designation</label>
                                    <input type="text" id="ad-clinician-designation" maxlength="100" placeholder="e.g. School Physician"/>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-license">License number</label>
                                    <input type="text" id="ad-clinician-license" maxlength="50"/>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-contact">Contact number</label>
                                    <input type="text" id="ad-clinician-contact" maxlength="30"/>
                                </div>
                                <div class="uh-form-group">
                                    <label for="ad-clinician-email">Email</label>
                                    <input type="email" id="ad-clinician-email" maxlength="150"/>
                                </div>
                                <label class="uh-flex uh-gap-8" style="align-items:center">
                                    <input type="checkbox" id="ad-clinician-active" checked/>
                                    <span>Available for form selection</span>
                                </label>
                            </div>
                            <div class="uh-flex uh-gap-8 uh-mt-16">
                                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save Clinician</button>
                                <button type="button" class="uh-btn uh-btn-ghost" onclick="adResetClinicianForm()">Clear</button>
                            </div>
                        </form>
                    </section>
                    <section class="uh-card">
                        <div class="uh-card-header">
                            <div class="uh-card-title">Clinician Directory</div>
                            <button type="button" class="uh-btn uh-btn-ghost uh-btn-sm" onclick="adLoadClinicians()"><i class="fa fa-rotate"></i> Refresh</button>
                        </div>
                        <div class="uh-table-wrap">
                            <table class="uh-table">
                                <thead><tr><th>Name</th><th>Type</th><th>Campus</th><th>License</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody id="ad-clinicians-body"><tr><td colspan="6" class="no-data">Loading clinicians...</td></tr></tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <!-- -- PAGE: DOCTORS -------------------- -->
            <div class="uh-page" id="page-doctors">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Doctors &amp; Dentists</div>
                        <button class="uh-btn uh-btn-primary uh-btn-sm" onclick="adOpenStaffForm()"><i class="fa fa-plus"></i> Add Staff</button>
                    </div>
                    <div class="uh-table-toolbar">
    <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
    <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="doctors">
                            <thead><tr><th>Name</th><th>Specialty</th><th>Campus</th><th>License</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="ad-doctors-body"><tr><td colspan="6" class="no-data">Loading...</td></tr></tbody>
                        </table>
                    </div>
                    <div id="pg-ad-doctors"></div>
                </div>
            </div>

            <!-- -- PAGE: SERVICES ------------------- -->
            <!-- -- PAGE: SETTINGS ------------------- -->
            <div class="uh-page" id="page-settings">
                <div class="ad-settings-grid">
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-16"><i class="fa fa-lock uh-icon-primary uh-mr-6"></i>Change Admin Password</div>
                        <div class="uh-form-group"><label>Current Password</label><input type="password" id="ad-cur-pw" placeholder="••••••••"/></div>
                        <div class="uh-form-group"><label>New Password</label><input type="password" id="ad-new-pw" placeholder="Min. 6 characters"/></div>
                        <div class="uh-form-group"><label>Confirm New Password</label><input type="password" id="ad-conf-pw" placeholder="Repeat new password"/></div>
                        <button class="uh-btn uh-btn-primary" onclick="adChangePw()"><i class="fa fa-key"></i> Update Password</button>
                    </div>
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-16"><i class="fa fa-clinic-medical uh-icon-primary uh-mr-6"></i>Clinic Info</div>
                        <div class="uh-form-group"><label>Clinic Name</label><input type="text" id="ad-settings-clinic_name" value="SUNN Medical &amp; Dental Services Office"/></div>
                        <div class="uh-form-group"><label>Address</label><input type="text" id="ad-settings-clinic_address" value="SUNN Main Campus, Sagay City, Negros Occidental"/></div>
                        <div class="uh-form-group"><label>Contact</label><input type="text" id="ad-settings-clinic_contact" value="(034) 703 8802"/></div>
                        <button class="uh-btn uh-btn-primary" onclick="adSaveClinicSettings()"><i class="fa fa-save"></i> Save Settings</button>
                    </div>
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-8"><i class="fa fa-key uh-icon-primary uh-mr-6"></i>Analysis Model API IDs</div>
                        <p class="uh-text-sm uh-text-muted uh-mb-16">Add named analysis model IDs. Saved entries remain in this table and are available in the Models card.</p>
                        <div class="uh-form-group">
                            <label for="ad-settings-analysis-model-name">Analysis model name</label>
                            <input type="text" id="ad-settings-analysis-model-name" maxlength="100" placeholder="e.g. Clinic Analysis Model" autocomplete="off">
                        </div>
                        <div class="uh-form-group">
                            <label for="ad-settings-analysis-model-id">Analysis model API ID</label>
                            <div class="uh-flex uh-gap-8">
                                <input class="uh-flex-1" type="text" id="ad-settings-analysis-model-id" maxlength="120" placeholder="e.g. provider/model-id" autocomplete="off">
                                <button type="button" class="uh-btn uh-btn-outline" onclick="adAddAiModel('analysis')">Add</button>
                            </div>
                        </div>
                        <div class="uh-table-wrap uh-mb-16">
                            <table class="uh-table ad-model-registry-table">
                                <thead><tr><th>Name</th><th>API ID</th><th>Action</th></tr></thead>
                                <tbody id="ad-settings-analysis-custom-list"><tr><td colspan="3" class="no-data">No custom analysis models added.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-8"><i class="fa fa-key uh-icon-primary uh-mr-6"></i>Transcription Model API IDs</div>
                        <p class="uh-text-sm uh-text-muted uh-mb-16">Add named transcription model IDs. Saved entries remain in this table and are available in the Models card.</p>
                        <div class="uh-form-group">
                            <label for="ad-settings-transcription-model-name">Transcription model name</label>
                            <input type="text" id="ad-settings-transcription-model-name" maxlength="100" placeholder="e.g. Clinic Transcription Model" autocomplete="off">
                        </div>
                        <div class="uh-form-group">
                            <label for="ad-settings-transcription-model-id">Transcription model API ID</label>
                            <div class="uh-flex uh-gap-8">
                                <input class="uh-flex-1" type="text" id="ad-settings-transcription-model-id" maxlength="120" placeholder="e.g. whisper-model-id" autocomplete="off">
                                <button type="button" class="uh-btn uh-btn-outline" onclick="adAddAiModel('transcription')">Add</button>
                            </div>
                        </div>
                        <div class="uh-table-wrap">
                            <table class="uh-table ad-model-registry-table">
                                <thead><tr><th>Name</th><th>API ID</th><th>Action</th></tr></thead>
                                <tbody id="ad-settings-transcription-custom-list"><tr><td colspan="3" class="no-data">No custom transcription models added.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-8"><i class="fa fa-wand-magic-sparkles uh-icon-primary uh-mr-6"></i>Models</div>
                        <p class="uh-text-sm uh-text-muted uh-mb-16">Choose which built-in or custom model API ID each feature uses. Changes apply to new requests.</p>
                        <div class="uh-form-group">
                            <label for="ad-settings-analysis-model">Analysis model</label>
                            <select id="ad-settings-analysis-model" disabled>
                                <option>Loading available models...</option>
                            </select>
                            <small class="uh-text-muted">Test custom models with the clinic analysis response format.</small>
                        </div>
                        <div class="uh-form-group">
                            <label for="ad-settings-transcription-model">Transcription model</label>
                            <select id="ad-settings-transcription-model" disabled>
                                <option>Loading available models...</option>
                            </select>
                            <small class="uh-text-muted">Consultation audio is sent to Groq for transcription.</small>
                        </div>
                        <button class="uh-btn uh-btn-primary" id="ad-settings-ai-models-save" onclick="adSaveAiModelSettings()" disabled><i class="fa fa-save"></i> Save API IDs and Models</button>
                        <p class="uh-text-sm uh-text-muted" id="ad-settings-ai-models-status" role="status" aria-live="polite"></p>
                    </div>
                    <div class="uh-card">
                        <div class="uh-card-title uh-mb-16"><i class="fa fa-database uh-text-danger uh-mr-6"></i>Data Management</div>
                        <div class="ad-settings-data-actions">
                            <button class="uh-btn uh-btn-outline" onclick="adExport()"><i class="fa fa-file-export"></i> Export All Data (JSON)</button>
                            <button class="uh-btn uh-btn-danger" onclick="adClearDemo()"><i class="fa fa-trash"></i> Clear Sample Records</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- -- PAGE: AUDIT ---------------------- -->
            <div class="uh-page" id="page-audit">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Audit Logs</div>
                        <button class="uh-btn uh-btn-ghost uh-btn-sm" onclick="adExport()"><i class="fa fa-download"></i> Export</button>
                    </div>
                    <div class="uh-table-toolbar">
    <input type="search" class="uh-table-search" data-uh-table-search placeholder="Search..." autocomplete="off">
    <span class="uh-table-count" data-uh-table-count></span>
</div>
<div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable data-uh-render="adRenderAdminTableSearch" data-ad-table="audit">
                            <thead><tr><th>Timestamp</th><th>User</th><th>Role</th><th>Action</th><th>Details</th></tr></thead>
                            <tbody id="ad-audit-body"><tr><td colspan="5" class="no-data">Loading audit logs...</td></tr></tbody>
                        </table>
                    </div>
                    <div id="pg-ad-audit"></div>
                </div>
            </div>

            <!-- -- PAGE: BACKUP --------------------- -->
            <div class="uh-page" id="page-backup">
                <div class="uh-card uh-card-sm">
                    <div class="uh-card-title uh-mb-16">Backup &amp; Restore</div>
                    <div class="uh-card uh-mb-16">
                        <div class="uh-section-heading">Create Backup</div>
                        <p class="uh-text-sm uh-text-muted uh-mb-8">Download a full export of all clinic data in JSON format.</p>
                        <button class="uh-btn uh-btn-primary" onclick="adExport()"><i class="fa fa-download"></i> Export Backup</button>
                    </div>
                    <div class="uh-card">
                        <div class="uh-section-heading">Restore from Backup</div>
                        <p class="uh-text-sm uh-text-muted uh-mb-8">Upload a previously exported JSON backup file to restore clinic data.</p>
                        <div class="uh-form-group"><label>Backup File</label><input type="file" id="ad-restore-file" accept=".json"/></div>
                        <button class="uh-btn uh-btn-danger" onclick="adRestoreBackup()"><i class="fa fa-upload"></i> Restore Data</button>
                    </div>
                </div>
            </div>

            @include('clinic.partials.notifications-page')

        </div><!-- /.uh-content -->
    </div><!-- /.uh-main -->
</div><!-- /.uh-layout -->

<!-- Toast container -->
<div id="uh-toasts"></div>

<!-- -- Patient create / edit modal ------------------- -->
<div id="ad-pf-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="ad-pf-title">Add Patient</div>
            <button type="button" class="uh-modal-close" onclick="adClosePatientForm()" aria-label="Close">&times;</button>
        </div>
        <form id="ad-pf-form" onsubmit="event.preventDefault(); adSavePatient();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Student ID <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pf-student_id" name="student_id" maxlength="30" required/>
                </div>
                <div class="uh-form-group">
                    <label>Email <span class="uh-text-danger">*</span></label>
                    <input type="email" id="ad-pf-email" name="email" maxlength="150" required/>
                </div>
                <div class="uh-form-group">
                    <label>First Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pf-first_name" name="first_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Last Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pf-last_name" name="last_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Middle Name</label>
                    <input type="text" id="ad-pf-middle_name" name="middle_name" maxlength="80"/>
                </div>
                <div class="uh-form-group">
                    <label>Password <span class="uh-text-danger" id="ad-pf-pw-required">*</span></label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="ad-pf-pw" name="password" minlength="6" maxlength="128" autocomplete="new-password"/>
                        <button type="button" class="uh-pw-toggle" data-target="ad-pf-pw" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted" id="ad-pf-pw-hint" style="display:none;">Leave blank to keep current password.</small>
                </div>
                <div class="uh-form-group" id="ad-pf-pw-confirm-group">
                    <label>Confirm Password <span class="uh-text-danger" id="ad-pf-pw-confirm-required">*</span></label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="ad-pf-pw-confirm" name="password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"/>
                        <button type="button" class="uh-pw-toggle" data-target="ad-pf-pw-confirm" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted" id="ad-pf-pw-confirm-hint" style="display:none;">Required only when setting a new password.</small>
                </div>
                <div class="uh-form-group">
                    <label>Course / Year</label>
                    <input type="text" id="ad-pf-course_year" name="course_year" maxlength="100"/>
                </div>
                <div class="uh-form-group">
                    <label>Course</label>
                    <input type="text" id="ad-pf-department" name="department" maxlength="120"/>
                </div>
                <div class="uh-form-group">
                    <label>Section</label>
                    <input type="text" id="ad-pf-section" name="section" maxlength="30"/>
                </div>
                <div class="uh-form-group">
                    <label>Contact</label>
                    <input type="text" id="ad-pf-contact" name="contact" maxlength="30"/>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <input type="text" id="ad-pf-address" name="address" maxlength="255"/>
                </div>
                <div class="uh-form-group">
                    <label>Sex</label>
                    <select id="ad-pf-sex" name="sex">
                        <option value="">—</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>Civil Status</label>
                    <select id="ad-pf-civil_status" name="civil_status">
                        <option value="">—</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>Age</label>
                    <input type="number" id="ad-pf-age" name="age" min="0" max="150"/>
                </div>
                <div class="uh-form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="ad-pf-date_of_birth" name="date_of_birth" max="{{ today()->toDateString() }}"/>
                </div>
                <div class="uh-form-group">
                    <label>Blood Type</label>
                    <select id="ad-pf-blood_type" name="blood_type">
                        <option value="">—</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>Campus</label>
                    <select id="ad-pf-campus_id" name="campus_id">
                        <option value="">— None —</option>
                    </select>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="adClosePatientForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- -- Instructor / faculty and staff patient account modal -->
<div id="ad-pa-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="ad-pa-title">Add Patient Account</div>
            <button type="button" class="uh-modal-close" onclick="adClosePatientAccountForm()" aria-label="Close">&times;</button>
        </div>
        <form id="ad-pa-form" onsubmit="event.preventDefault(); adSavePatientAccount();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label for="ad-pa-patient_type">Account type <span class="uh-text-danger">*</span></label>
                    <select id="ad-pa-patient_type" required>
                        <option value="" selected disabled>Select a category</option>
                        <option value="instructor_faculty">Instructor / Faculty</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-student_id">Institution ID <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pa-student_id" maxlength="30" required/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-first_name">First name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pa-first_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-last_name">Last name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-pa-last_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-middle_name">Middle name</label>
                    <input type="text" id="ad-pa-middle_name" maxlength="80"/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-email">Email <span class="uh-text-danger">*</span></label>
                    <input type="email" id="ad-pa-email" maxlength="150" required/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-department">Department / Office</label>
                    <input type="text" id="ad-pa-department" maxlength="120"/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-contact">Contact</label>
                    <input type="text" id="ad-pa-contact" maxlength="30"/>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-campus_id">Campus</label>
                    <select id="ad-pa-campus_id">
                        <option value="">— None —</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-password">Password <span class="uh-text-danger" id="ad-pa-password-required">*</span></label>
                    <input type="password" id="ad-pa-password" minlength="6" maxlength="128" autocomplete="new-password"/>
                    <small class="uh-text-muted" id="ad-pa-password-hint" style="display:none">Leave blank to keep the current password.</small>
                </div>
                <div class="uh-form-group">
                    <label for="ad-pa-password-confirmation">Confirm password <span class="uh-text-danger" id="ad-pa-confirm-required">*</span></label>
                    <input type="password" id="ad-pa-password-confirmation" minlength="6" maxlength="128" autocomplete="new-password"/>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="adClosePatientAccountForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save" aria-hidden="true"></i> Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- -- Patient view modal ------------------------------ -->
<div id="ad-pv-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="ad-pv-title">Patient Details</div>
            <button type="button" class="uh-modal-close" onclick="adClosePatientView()" aria-label="Close">&times;</button>
        </div>
        <div>
            <div class="uh-flex uh-gap-16 uh-mb-16" style="align-items:center;">
                <div id="ad-pv-picture" style="width:72px;height:72px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--uh-bg,#f3f4f6);"></div>
                <div>
                    <div class="uh-text-muted uh-text-sm">
                        <span id="ad-pv-role">—</span> — <span id="ad-pv-active">—</span>
                    </div>
                </div>
            </div>
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Student ID</label>
                    <div id="ad-pv-student_id" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Email</label>
                    <div id="ad-pv-email" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>First Name</label>
                    <div id="ad-pv-first_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Last Name</label>
                    <div id="ad-pv-last_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Middle Name</label>
                    <div id="ad-pv-middle_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Course / Year</label>
                    <div id="ad-pv-course_year" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Course</label>
                    <div id="ad-pv-department" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Section</label>
                    <div id="ad-pv-section" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Contact</label>
                    <div id="ad-pv-contact" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Date of Birth</label>
                    <div id="ad-pv-date_of_birth" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Age</label>
                    <div id="ad-pv-age" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Sex</label>
                    <div id="ad-pv-sex" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Civil Status</label>
                    <div id="ad-pv-civil_status" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Blood Type</label>
                    <div id="ad-pv-blood_type" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Campus</label>
                    <div id="ad-pv-campus" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <div id="ad-pv-address" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Member Since</label>
                    <div id="ad-pv-created_at" class="uh-text-muted">—</div>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="adClosePatientView()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- -- Staff create / edit modal ---------------------- -->
<div id="ad-sf-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="ad-sf-title">Add Staff</div>
            <button type="button" class="uh-modal-close" onclick="adCloseStaffForm()" aria-label="Close">&times;</button>
        </div>
        <form id="ad-sf-form" onsubmit="event.preventDefault(); adSaveStaff();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Employee ID <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-sf-employee_id" name="employee_id" maxlength="30" required/>
                </div>
                <div class="uh-form-group">
                    <label>Email <span class="uh-text-danger">*</span></label>
                    <input type="email" id="ad-sf-email" name="email" maxlength="150" required/>
                </div>
                <div class="uh-form-group">
                    <label>First Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-sf-first_name" name="first_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Last Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="ad-sf-last_name" name="last_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Middle Name</label>
                    <input type="text" id="ad-sf-middle_name" name="middle_name" maxlength="80"/>
                </div>
                <div class="uh-form-group">
                    <label>Password <span class="uh-text-danger" id="ad-sf-pw-required">*</span></label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="ad-sf-pw" name="password" minlength="6" maxlength="128" autocomplete="new-password"/>
                        <button type="button" class="uh-pw-toggle" data-target="ad-sf-pw" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted" id="ad-sf-pw-hint" style="display:none;">Leave blank to keep current password.</small>
                </div>
                <div class="uh-form-group" id="ad-sf-pw-confirm-group">
                    <label>Confirm Password <span class="uh-text-danger" id="ad-sf-pw-confirm-required">*</span></label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="ad-sf-pw-confirm" name="password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"/>
                        <button type="button" class="uh-pw-toggle" data-target="ad-sf-pw-confirm" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted" id="ad-sf-pw-confirm-hint" style="display:none;">Required only when setting a new password.</small>
                </div>
                <div class="uh-form-group">
                    <label>Role <span class="uh-text-danger">*</span></label>
                    <select id="ad-sf-role" name="role" required>
                        <option value="nurse">Nurse</option>
                        <option value="nurse_head">Head Nurse</option>
                        <option value="physician">Physician</option>
                        <option value="dentist">Dentist</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>License No.</label>
                    <input type="text" id="ad-sf-license_no" name="license_no" maxlength="50"/>
                </div>
                <div class="uh-form-group">
                    <label>Contact</label>
                    <input type="text" id="ad-sf-contact" name="contact" maxlength="30"/>
                </div>
                <div class="uh-form-group">
                    <label>Specialization</label>
                    <input type="text" id="ad-sf-specialization" name="specialization" maxlength="150"/>
                </div>
                <div class="uh-form-group">
                    <label>Campus</label>
                    <select id="ad-sf-campus_id" name="campus_id">
                        <option value="">— All Campuses (system-wide access) —</option>
                    </select>
                    <small class="uh-text-muted" style="display:block;margin-top:4px;font-size:0.75rem;">
                        Pick a specific campus to limit this staff member's access, or leave as "All Campuses" to grant system-wide access.
                    </small>
                </div>
                <div class="uh-form-group">
                    <label class="uh-flex uh-gap-8" style="align-items:center;cursor:pointer;">
                        <input type="checkbox" id="ad-sf-is_active" name="is_active" checked/>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="adCloseStaffForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- -- Staff view modal -------------------------------- -->
<div id="ad-sv-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="ad-sv-title">Staff Details</div>
            <button type="button" class="uh-modal-close" onclick="adCloseStaffView()" aria-label="Close">&times;</button>
        </div>
        <div>
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Employee ID</label>
                    <div id="ad-sv-employee_id" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Email</label>
                    <div id="ad-sv-email" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>First Name</label>
                    <div id="ad-sv-first_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Last Name</label>
                    <div id="ad-sv-last_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Middle Name</label>
                    <div id="ad-sv-middle_name" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Role</label>
                    <div id="ad-sv-role" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>License No.</label>
                    <div id="ad-sv-license_no" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Contact</label>
                    <div id="ad-sv-contact" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Specialization</label>
                    <div id="ad-sv-specialization" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Campus</label>
                    <div id="ad-sv-campus" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Status</label>
                    <div id="ad-sv-active" class="uh-text-muted">—</div>
                </div>
                <div class="uh-form-group">
                    <label>Member Since</label>
                    <div id="ad-sv-created_at" class="uh-text-muted">—</div>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="adCloseStaffView()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('clinic/vendor/chart.umd.min.js') }}"></script>
<script src="{{ asset('clinic/js/unihealth.js') }}?v={{ filemtime(public_path('clinic/js/unihealth.js')) }}"></script>
<script src="{{ asset('clinic/js/staff-health-record.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/medical-record-entry.js') }}?v={{ filemtime(public_path('clinic/js/medical-record-entry.js')) }}"></script>
<script src="{{ asset('clinic/js/analytics.js') }}?v={{ filemtime(public_path('clinic/js/analytics.js')) }}"></script>
<script src="{{ asset('clinic/js/admin-clinicians.js') }}?v={{ filemtime(public_path('clinic/js/admin-clinicians.js')) }}"></script>
<script>
'use strict';

// -- current campus filter ------------------------------
var adCurrentCampus = '';
var adClinicRecordsPage = 1;
var adClinicRecordsSearch = '';
var adClinicRecordsRequest = 0;
var adClinicRecordsSearchTimer = null;
var adReferralFilters = { campusId: '', referredTo: '' };
var adReferralFilterTimer = null;
var adAppointmentRows = Object.create(null);
var adTableState = {
    patients: { page: 1, search: '', requestId: 0, searchTimer: null },
    patientAccounts: { page: 1, search: '', status: 'active', requestId: 0, searchTimer: null },
    users: { page: 1, search: '', requestId: 0, searchTimer: null },
    doctors: { page: 1, search: '', requestId: 0, searchTimer: null },
    appointments: { page: 1, search: '', status: '', sortOrder: 'desc', requestId: 0, searchTimer: null },
    referrals: { page: 1, search: '', sortOrder: 'desc', requestId: 0, searchTimer: null },
    audit: { page: 1, search: '', requestId: 0, searchTimer: null }
};

document.addEventListener('DOMContentLoaded', function () {
    adLoadDashboard();
    adRenderChart('ad-rpt-chart', 'ad-rpt-labels');
    adLoadData();
    adLoadReferralFilters();
    // Analytics dashboard
    ClinicAnalytics.applyDefaults();
    ClinicAnalytics.loadAdminAnalytics('', 'an');
    ClinicAnalytics.loadPerfMetrics('ad', '');

    // Delegated handler for password show/hide toggles
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.uh-pw-toggle');
        if (!btn) { return; }
        var targetId = btn.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) { return; }
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye',       !isHidden);
            icon.classList.toggle('fa-eye-slash',  isHidden);
        }
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        btn.setAttribute('title',     isHidden ? 'Hide password' : 'Show password');
        input.focus();
        // Place caret at the end after type change
        var v = input.value;
        try { input.setSelectionRange(v.length, v.length); } catch (e) {}
    });
});

// -- Charts ---------------------------------------------
function adRenderChart(chartId, labelsId, counts, days) {
    var chart  = document.getElementById(chartId);
    var labels = document.getElementById(labelsId);
    if (!chart) { return; }
    var d = days   || ['Mon','Tue','Wed','Thu','Fri','Mon','Tue','Wed','Thu','Fri','Mon','Tue','Wed'];
    var c = counts || [32, 45, 28, 52, 38, 42, 50, 35, 29, 48, 40, 33, 47];
    var max = Math.max.apply(null, c);
    chart.innerHTML = c.map(function (v) {
        return '<div class="uh-bar" style="height:' + Math.round((v / max) * 100) + '%" title="' + v + ' patients"></div>'; // height must remain inline — dynamic per bar
    }).join('');
    if (labels) {
        labels.innerHTML = d.map(function (day) {
            return '<span class="uh-bar-label-day">' + day + '</span>';
        }).join('');
    }
}

// -- Campus filter --------------------------------------
function adFilterCampus(campusId, el) {
    document.querySelectorAll('.uh-campus-tab').forEach(function (t) { t.classList.remove('active'); });
    el.classList.add('active');
    adCurrentCampus = campusId === 'all' ? '' : campusId;
    adLoadDashboard();
    adLoadData();
    // Reload analytics for selected campus
    var qs = adCurrentCampus ? '?campus_id=' + adCurrentCampus : '';
    ClinicAnalytics.loadAdminAnalytics(qs, 'an');
    ClinicAnalytics.loadPerfMetrics('ad', qs);
}

// -- Analytics period toggle ----------------------------
function adAnPeriod(period, el) {
    document.querySelectorAll('.an-period-tab').forEach(function (t) { t.classList.remove('active'); });
    el.classList.add('active');
    var qs = adCurrentCampus ? '?campus_id=' + adCurrentCampus : '';
    ClinicAnalytics.loadAdminAnalytics(qs, 'an');
    ClinicAnalytics.loadPerfMetrics('ad', qs);
}

// -- Users tab ------------------------------------------
function adUsersTab(tab, el) {
    document.querySelectorAll('.uh-campus-tab').forEach(function (t) { t.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('ad-panel-users').classList.toggle('uh-hidden', tab !== 'users');
    document.getElementById('ad-panel-roles').classList.toggle('uh-hidden', tab !== 'roles');
}

// -- Load all dashboard data in one request -------------
function adLoadDashboard() {
    var qs = adCurrentCampus ? '?campus_id=' + adCurrentCampus : '';
    uhGet('/admin/dashboard' + qs).then(function (data) {
        var s = data.stats || {};

        function setEl(id, val) {
            var el = document.getElementById(id);
            if (el && val !== undefined) { el.textContent = Number(val).toLocaleString(); }
        }
        function setText(id, val) {
            var el = document.getElementById(id);
            if (el) { el.textContent = val; }
        }

        // ── KPI Strip ──
        setEl('ad-stat-patients', s.total_patients || s.total_students || 0);
        setEl('ad-stat-visits',   s.total_visits   || s.total_records  || 0);
        setEl('ad-stat-today',    s.todays_visits   || 0);
        setEl('ad-stat-appts',    s.todays_appointments || 0);
        setEl('ad-stat-refs',     s.pending_referrals   || 0);
        setText('ad-appts-sub',   (s.pending_appointments || 0) + ' pending');
        setText('ad-refs-sub',    (s.urgent_referrals || 0) > 0 ? (s.urgent_referrals) + ' urgent' : 'No urgent');
        setText('ad-today-sub',   (s.recent_visits_7d || 0) + ' this week');

        // Staff count — fetch separately from /admin/stats
        uhGet('/admin/stats' + qs).then(function (sd) {
            setEl('ad-stat-staff', (sd.stats && sd.stats.total_staff) || 0);
        }).catch(function () {});

        // ── Appointments page stat tiles ──
        var ap = s.appointments_by_status || {};
        setEl('ad-appt-stat-total',     (ap.pending||0)+(ap.confirmed||0)+(ap.completed||0)+(ap.cancelled||0));
        setEl('ad-appt-stat-pending',   ap.pending   || 0);
        setEl('ad-appt-stat-confirmed', ap.confirmed || 0);
        setEl('ad-appt-stat-completed', ap.completed || 0);
        setEl('ad-appt-stat-cancelled', ap.cancelled || 0);

        // ── Appointment doughnut ──
        ClinicAnalytics.makeChart('ad-appt-donut', {
            type: 'doughnut',
            data: {
                labels: ['Completed','Confirmed','Pending','Cancelled'],
                datasets: [{
                    data: [ap.completed||0, ap.confirmed||0, ap.pending||0, ap.cancelled||0],
                    backgroundColor: ['#16a34a','#2563eb','#d97706','#dc2626'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false }, tooltip: { mode: 'index' } },
            },
        });
        setEl('ad-appt-completed', ap.completed || 0);
        setEl('ad-appt-confirmed', ap.confirmed || 0);
        setEl('ad-appt-pending',   ap.pending   || 0);
        setEl('ad-appt-cancelled', ap.cancelled || 0);

        // ── Quick stats ──
        setEl('ad-qs-7d',       s.recent_visits_7d  || 0);
        setEl('ad-qs-30d',      s.recent_visits_30d || 0);
        setEl('ad-qs-urgent',   s.urgent_referrals  || 0);
        setEl('ad-qs-new-week', s.new_students_this_week || 0);
        setEl('ad-qs-refs-done', (s.referrals_by_status && s.referrals_by_status.completed) || 0);
        setEl('ad-qs-predicted', data.predicted_traffic ? data.predicted_traffic.count : 0);

        // ── Old stat IDs still referenced elsewhere ──
        setEl('ad-rpt-patients', s.total_patients || s.total_students || 0);
        setEl('ad-rpt-consults', s.total_visits || s.total_records || 0);
        setEl('ad-rpt-refs',     s.pending_referrals || 0);

        // ── 13-day visit trend (Chart.js line chart on the new canvas) ──
        var daily = data.daily_visits || [];
        if (daily.length) {
            var lbl    = daily.map(function (_, i) { return String(i + 1); });
            var actual = daily.map(function (d) { return d.count || 0; });
            var avg    = actual.map(function (_, i) {
                var sl = actual.slice(Math.max(0, i-2), i+1);
                return Math.round(sl.reduce(function (a,b) { return a+b; }, 0) / sl.length);
            });
            ClinicAnalytics.makeChart('an-chart-daily', {
                type: 'line',
                data: {
                    labels: lbl,
                    datasets: [
                        { label: 'Actual', data: actual, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', fill: true, tension: 0.4, pointRadius: 3, pointHoverRadius: 6, borderWidth: 2.5 },
                        { label: '3-Day Avg', data: avg, borderColor: '#16a34a', backgroundColor: 'transparent', fill: false, tension: 0.5, pointRadius: 0, borderWidth: 2, borderDash: [4,3] },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, padding: 12 } },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function (items) {
                                    var day = daily[items[0].dataIndex];
                                    return 'Day ' + (items[0].dataIndex + 1) + (day && day.date_val ? ' · ' + day.date_val : '');
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            title: { display: true, text: 'Day (oldest to most recent)' },
                        },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                    },
                },
            });
            // Also keep bar chart for reports page
            adRenderChart('ad-bar-chart', 'ad-bar-labels', actual, lbl);
        }

    }).catch(function () {
        adRenderChart('ad-bar-chart', 'ad-bar-labels');
    });
}

function adReloadDashboard(btn) {
    if (btn) { btn.style.transform = 'rotate(360deg)'; setTimeout(function(){ btn.style.transform=''; }, 600); }
    adLoadDashboard();
    var qs = adCurrentCampus ? '?campus_id=' + adCurrentCampus : '';
    ClinicAnalytics.loadAdminAnalytics(qs, 'an');
}

// -- Load patients & staff tables -----------------------
function adLoadData() {
    Object.keys(adTableState).forEach(function (tableKey) {
        adLoadAdminTable(tableKey, adTableState[tableKey].page);
    });
    adLoadClinicRecords(1);
}

function adBuildQuery(params) {
    var values = Object.keys(params).filter(function (key) {
        return params[key] !== null && params[key] !== undefined && params[key] !== '';
    }).map(function (key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(String(params[key]));
    });

    return values.length ? '?' + values.join('&') : '';
}

function adRenderAdminPagination(tableKey, pagination, loadPage) {
    var containerIds = {
        patients: 'pg-ad-patients',
        patientAccounts: 'pg-ad-patient-accounts',
        users: 'pg-ad-users',
        doctors: 'pg-ad-doctors',
        appointments: 'pg-ad-appointments',
        referrals: 'pg-ad-referrals',
        audit: 'pg-ad-audit'
    };
    var container = document.getElementById(containerIds[tableKey]);
    if (!container) { return; }

    var page = Math.max(1, Number(pagination.page) || 1);
    var pages = Math.max(1, Number(pagination.pages) || 1);
    var limit = Math.max(1, Number(pagination.limit) || 10);
    var total = Math.max(0, Number(pagination.total) || 0);
    var table = document.querySelector('table[data-ad-table="' + tableKey + '"]');
    var card = table ? table.closest('.uh-card') : null;
    var count = card ? card.querySelector('[data-uh-table-count]') : null;
    var start = total ? ((page - 1) * limit) + 1 : 0;
    var end = Math.min(page * limit, total);

    if (page > pages) {
        loadPage(pages);
        return;
    }

    if (count) {
        count.textContent = total ? 'Showing ' + start + '–' + end + ' of ' + total : '0 results';
    }

    container.replaceChildren();
    if (pages <= 1) { return; }

    var paginationWrap = document.createElement('div');
    paginationWrap.className = 'uh-pagination';
    function addButton(label, targetPage, disabled, active) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'uh-pg-btn' + (active ? ' active' : '');
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', function () {
            loadPage(targetPage);
        });
        paginationWrap.appendChild(button);
    }

    addButton('‹', page - 1, page <= 1, false);
    var visiblePages = Array.from(new Set([1, pages, page - 1, page, page + 1]))
        .filter(function (number) { return number >= 1 && number <= pages; })
        .sort(function (left, right) { return left - right; });
    var previousPage = 0;
    visiblePages.forEach(function (number) {
        if (previousPage && number - previousPage > 1) {
            var ellipsis = document.createElement('span');
            ellipsis.className = 'uh-pg-ellipsis';
            ellipsis.textContent = '…';
            paginationWrap.appendChild(ellipsis);
        }
        addButton(String(number), number, false, number === page);
        previousPage = number;
    });
    addButton('›', page + 1, page >= pages, false);

    var info = document.createElement('span');
    info.className = 'uh-pg-info';
    info.textContent = 'Page ' + page + ' of ' + pages;
    paginationWrap.appendChild(info);
    container.appendChild(paginationWrap);
}

window.adRenderAdminTableSearch = function (table, query) {
    var tableKey = table.getAttribute('data-ad-table');
    var state = adTableState[tableKey];
    if (!state) { return; }

    state.search = query;
    clearTimeout(state.searchTimer);
    state.searchTimer = setTimeout(function () {
        adLoadAdminTable(tableKey, 1);
    }, 250);
};

function adLoadAdminTable(tableKey, page) {
    var loaders = {
        patients: adLoadPatients,
        patientAccounts: adLoadPatientAccounts,
        users: adLoadStaffTable,
        doctors: adLoadStaffTable,
        appointments: adLoadAppointments,
        referrals: adLoadReferrals,
        audit: adLoadAuditLog
    };
    if (loaders[tableKey]) {
        loaders[tableKey](page, tableKey);
    }
}

var adPatientAccountRows = Object.create(null);
var adPatientAccountFormState = { mode: 'create', id: null };

function adPatientAccountsStatusChanged(status) {
    var state = adTableState.patientAccounts;
    state.status = status;
    adLoadPatientAccounts(1);
}

function adLoadPatientAccounts(page) {
    var state = adTableState.patientAccounts;
    state.page = page;
    var requestId = ++state.requestId;
    var params = {
        page: page,
        limit: 10,
        search: state.search,
        status: state.status,
        campus_id: adCurrentCampus
    };

    uhGet('/admin/patient-accounts' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var accounts = Array.isArray(data.patient_accounts) ? data.patient_accounts : [];
        var tbody = document.getElementById('ad-patient-accounts-body');
        if (!tbody) { return; }
        adPatientAccountRows = Object.create(null);
        accounts.forEach(function (account) {
            adPatientAccountRows[String(account.id)] = account;
        });

        if (data.ok === false || data.success === false) {
            tbody.innerHTML = uhNoData(8, 'Unable to load patient accounts.');
            uhToast(data.message || data.error || 'Could not load patient accounts.', 'error');
            return;
        }

        if (!accounts.length) {
            tbody.innerHTML = uhNoData(8, 'No matching instructor, faculty, or staff accounts found.');
        } else {
            tbody.innerHTML = accounts.map(function (account) {
                var name = [account.first_name, account.middle_name, account.last_name].filter(Boolean).join(' ') || '—';
                var campusName = account.campus && account.campus.name ? account.campus.name : '—';
                var active = !!account.is_active;
                return '<tr>' +
                    '<td>' + uhEscapeHtml(name) + '</td>' +
                    '<td>' + uhEscapeHtml(account.student_id || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(uhPatientTypeLabel(account.patient_type)) + '</td>' +
                    '<td>' + uhEscapeHtml(account.department || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(account.email || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(campusName) + '</td>' +
                    '<td>' + (active ? uhBadge('active') : '<span class="uh-badge cancelled">inactive</span>') + '</td>' +
                    '<td><div class="ad-patient-actions">' +
                        '<button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" onclick="adOpenPatientAccountForm(' + Number(account.id) + ')">Edit</button>' +
                        '<button type="button" class="uh-btn ' + (active ? 'uh-btn-danger' : 'uh-btn-primary') + ' uh-btn-xs" onclick="adSetPatientAccountActive(' + Number(account.id) + ',' + (active ? 'false' : 'true') + ')">' + (active ? 'Deactivate' : 'Reactivate') + '</button>' +
                    '</div></td>' +
                '</tr>';
            }).join('');
        }

        adRenderAdminPagination('patientAccounts', data.pagination || { page: page, limit: 10, total: accounts.length, pages: 1 }, function (nextPage) {
            adLoadPatientAccounts(nextPage);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-patient-accounts-body');
        if (tbody) { tbody.innerHTML = uhNoData(8, 'Unable to load patient accounts.'); }
        uhToast('Could not load patient accounts.', 'error');
    });
}

function adOpenPatientAccountForm(id) {
    var form = document.getElementById('ad-pa-form');
    if (form) { form.reset(); }
    adPatientAccountFormState = id
        ? { mode: 'edit', id: Number(id) }
        : { mode: 'create', id: null };

    var account = id ? adPatientAccountRows[String(id)] : null;
    if (id && !account) {
        uhToast('This account is no longer available. Refresh the list and try again.', 'error');
        return;
    }
    document.getElementById('ad-pa-title').textContent = id ? 'Edit Patient Account' : 'Add Patient Account';
    document.getElementById('ad-pa-password-required').style.display = id ? 'none' : 'inline';
    document.getElementById('ad-pa-password').required = !id;
    document.getElementById('ad-pa-password-hint').style.display = id ? 'block' : 'none';
    document.getElementById('ad-pa-confirm-required').style.display = id ? 'none' : 'inline';
    document.getElementById('ad-pa-password-confirmation').required = !id;

    if (account) {
        var categoryByType = {
            instructor_faculty: 'instructor_faculty',
            faculty: 'instructor_faculty',
            instructor: 'instructor_faculty',
            staff: 'staff'
        };
        document.getElementById('ad-pa-patient_type').value = categoryByType[account.patient_type] || '';
        document.getElementById('ad-pa-student_id').value = account.student_id || '';
        document.getElementById('ad-pa-student_id').disabled = true;
        document.getElementById('ad-pa-first_name').value = account.first_name || '';
        document.getElementById('ad-pa-last_name').value = account.last_name || '';
        document.getElementById('ad-pa-middle_name').value = account.middle_name || '';
        document.getElementById('ad-pa-email').value = account.email || '';
        document.getElementById('ad-pa-department').value = account.department || '';
        document.getElementById('ad-pa-contact').value = account.contact || '';
    } else {
        document.getElementById('ad-pa-student_id').disabled = false;
    }

    adLoadPatientCampuses(function () {
        if (account) { document.getElementById('ad-pa-campus_id').value = account.campus_id || ''; }
    });
    document.getElementById('ad-pa-modal').classList.remove('hidden');
}

function adClosePatientAccountForm() {
    document.getElementById('ad-pa-modal').classList.add('hidden');
}

function adSavePatientAccount() {
    var state = adPatientAccountFormState;
    var password = document.getElementById('ad-pa-password').value;
    var passwordConfirmation = document.getElementById('ad-pa-password-confirmation').value;
    if ((state.mode === 'create' || password || passwordConfirmation) && password !== passwordConfirmation) {
        uhToast('Passwords do not match.', 'error');
        document.getElementById('ad-pa-password-confirmation').focus();
        return;
    }

    var payload = {
        patient_type: document.getElementById('ad-pa-patient_type').value,
        first_name: document.getElementById('ad-pa-first_name').value.trim(),
        last_name: document.getElementById('ad-pa-last_name').value.trim(),
        middle_name: document.getElementById('ad-pa-middle_name').value.trim(),
        email: document.getElementById('ad-pa-email').value.trim(),
        department: document.getElementById('ad-pa-department').value.trim(),
        contact: document.getElementById('ad-pa-contact').value.trim(),
        campus_id: document.getElementById('ad-pa-campus_id').value || null
    };
    var accountId = document.getElementById('ad-pa-student_id').value.trim();
    if (state.mode === 'create') {
        payload.student_id = accountId;
        payload.password = password;
        payload.password_confirmation = passwordConfirmation;
    } else if (password) {
        payload.password = password;
        payload.password_confirmation = passwordConfirmation;
    }

    var request = state.mode === 'edit'
        ? uhPut('/admin/patient-accounts/' + state.id, payload)
        : uhPost('/admin/patient-accounts', payload);
    request.then(function (data) {
        if (!data || data.ok === false || data.success === false) {
            var firstError = data && data.errors ? Object.values(data.errors)[0] : null;
            uhToast(Array.isArray(firstError) ? firstError[0] : (data.message || data.error || 'Could not save patient account.'), 'error');
            return;
        }
        uhToast(state.mode === 'edit' ? 'Patient account updated.' : 'Patient account created.', 'success');
        adClosePatientAccountForm();
        adLoadPatientAccounts(state.page);
        adLoadDashboard();
    }).catch(function () {
        uhToast('Could not save patient account.', 'error');
    });
}

async function adSetPatientAccountActive(id, active) {
    var action = active ? 'reactivate' : 'deactivate';
    if (!await uhConfirm('Are you sure you want to ' + action + ' this patient account?', {
        title: (active ? 'Reactivate' : 'Deactivate') + ' account?',
        confirmText: active ? 'Reactivate account' : 'Deactivate account',
        danger: !active
    })) { return; }

    uhPatch('/admin/patient-accounts/' + Number(id) + '/status', { is_active: active }).then(function (data) {
        if (!data || data.ok === false || data.success === false) {
            uhToast(data.message || data.error || 'Could not update patient account status.', 'error');
            return;
        }
        uhToast(active ? 'Patient account reactivated.' : 'Patient account deactivated.', 'success');
        adLoadPatientAccounts(adTableState.patientAccounts.page);
        adLoadDashboard();
    }).catch(function () {
        uhToast('Could not update patient account status.', 'error');
    });
}

function adLoadPatients(page) {
    var state = adTableState.patients;
    state.page = page;
    var requestId = ++state.requestId;
    var params = {
        page: page,
        limit: 10,
        search: state.search,
        campus_id: adCurrentCampus
    };

    uhGet('/admin/students' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var patients = data.students || [];
        var tbody = document.getElementById('ad-patients-body');
        if (!tbody) { return; }
        if (!patients.length) {
            tbody.innerHTML = uhNoData(7, 'No patients found.');
        } else {
            tbody.innerHTML = patients.map(function (student) {
                var name = [student.first_name, student.last_name].filter(Boolean).join(' ') || '—';
                var campusName = student.campus && student.campus.name ? student.campus.name : '—';
                var safeId = String(student.student_id || '').replace(/[^A-Za-z0-9\-_]/g, '');
                var studentId = uhEscapeHtml(student.student_id || '—');
                return '<tr>' +
                    '<td>' + uhEscapeHtml(name) + '<br>' + uhPatientTypeBadge(student.patient_type) + '</td>' +
                    '<td>' + studentId + '</td>' +
                    '<td>' + uhEscapeHtml(student.department || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(student.email || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(student.sex || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(campusName) + '</td>' +
                    '<td><div class="ad-patient-actions">' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="adViewPatient(\'' + safeId + '\')">View</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhViewStudentHealthRecord(\'' + safeId + '\')">Health record</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="adEditPatient(\'' + safeId + '\')">Edit</button>' +
                        '<button class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeletePatient(\'' + safeId + '\')">Delete</button>' +
                    '</div></td></tr>';
            }).join('');
        }

        adRenderAdminPagination('patients', data.pagination || { page: page, limit: 10, total: patients.length, pages: 1 }, function (nextPage) {
            adLoadPatients(nextPage);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-patients-body');
        if (tbody) { tbody.innerHTML = uhNoData(7, 'Unable to load patients.'); }
        uhToast('Could not load patients.', 'error');
    });
}

function adLoadStaffTable(page, tableKey) {
    var state = adTableState[tableKey];
    state.page = page;
    var requestId = ++state.requestId;
    var bodyId = tableKey === 'doctors' ? 'ad-doctors-body' : 'ad-users-body';
    var params = { page: page, limit: 10, search: state.search };

    uhGet('/admin/staff' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var members = data.staff || [];
        var tbody = document.getElementById(bodyId);
        if (!tbody) { return; }
        if (!members.length) {
            tbody.innerHTML = uhNoData(6, tableKey === 'doctors' ? 'No staff found.' : 'No accounts found.');
        } else {
            tbody.innerHTML = members.map(function (member) {
                var name = [member.first_name, member.last_name].filter(Boolean).join(' ') || '—';
                var employeeId = String(member.employee_id || '').replace(/[^A-Za-z0-9\-_]/g, '');
                var campusName = member.campus && member.campus.name ? uhEscapeHtml(member.campus.name) : '<span class="uh-badge uh-badge-gold">All Campuses</span>';
                if (tableKey === 'doctors') {
                    return '<tr>' +
                        '<td>' + uhEscapeHtml(name) + '</td>' +
                        '<td>' + uhEscapeHtml(member.role || '—') + '</td>' +
                        '<td>' + campusName + '</td>' +
                        '<td>' + uhEscapeHtml(member.license_no || '—') + '</td>' +
                        '<td>' + uhBadge('active') + '</td>' +
                        '<td>' +
                            '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="adViewStaff(\'' + employeeId + '\')">View</button> ' +
                            '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="adEditStaff(\'' + employeeId + '\')">Edit</button> ' +
                            '<button class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeleteStaff(\'' + employeeId + '\')">Delete</button>' +
                        '</td></tr>';
                }

                return '<tr>' +
                    '<td>' + uhEscapeHtml(name) + '</td>' +
                    '<td>' + uhEscapeHtml(member.email || '—') + '</td>' +
                    '<td><span class="uh-badge role-' + uhEscapeHtml(member.role || '') + '">' + uhEscapeHtml(member.role || '—') + '</span></td>' +
                    '<td>' + campusName + '</td>' +
                    '<td>' + uhBadge('active') + '</td>' +
                    '<td>' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="adEditStaff(\'' + employeeId + '\')">Edit</button> ' +
                        '<button class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeleteStaff(\'' + employeeId + '\')">Delete</button>' +
                    '</td></tr>';
            }).join('');
        }

        adRenderAdminPagination(tableKey, data.pagination || { page: page, limit: 10, total: members.length, pages: 1 }, function (nextPage) {
            adLoadStaffTable(nextPage, tableKey);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById(bodyId);
        if (tbody) { tbody.innerHTML = uhNoData(6, 'Unable to load staff.'); }
        uhToast('Could not load staff records.', 'error');
    });
}

function adLoadAppointments(page) {
    var state = adTableState.appointments;
    state.page = page;
    var requestId = ++state.requestId;
    var params = {
        page: page,
        limit: 10,
        search: state.search,
        status: state.status,
        campus_id: adCurrentCampus,
        sort_order: state.sortOrder
    };

    uhGet('/admin/appointments' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var appointments = data.appointments || [];
        var tbody = document.getElementById('ad-appts-body');
        if (!tbody) { return; }
        adAppointmentRows = Object.create(null);
        appointments.forEach(function (appointment) {
            adAppointmentRows[String(appointment.id)] = appointment;
        });
        if (!appointments.length) {
            tbody.innerHTML = uhNoData(7, 'No appointments found.');
        } else {
            tbody.innerHTML = appointments.map(function (appointment) {
                var student = appointment.student || {};
                var name = [student.first_name, student.last_name].filter(Boolean).join(' ') || '—';
                var id = Number(appointment.id);
                var confidence = Number(appointment.confidence);
                var confidenceLabel = appointment.confidence !== null && appointment.confidence !== undefined && Number.isFinite(confidence)
                    ? Math.round(confidence * 100) + '% confidence'
                    : '';
                var priorityLabel = appointment.priority
                    ? appointment.priority.charAt(0).toUpperCase() + appointment.priority.slice(1)
                    : '';
                var priorityDetails = [priorityLabel, confidenceLabel].filter(Boolean).join(' · ') || '—';
                return '<tr>' +
                    '<td>' + uhEscapeHtml([uhFormatDate(appointment.appt_date), appointment.appt_time ? uhFormatTime(appointment.appt_time) : ''].filter(Boolean).join(' ') || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(name) + '<br>' + uhPatientTypeBadge(student.patient_type) + '</td>' +
                    '<td>' + uhEscapeHtml(appointment.service_type || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(appointment.healthcare_professional || 'Not assigned') + '</td>' +
                    '<td>' + uhEscapeHtml(priorityDetails) + '</td>' +
                    '<td>' + uhBadge(appointment.status || 'pending') + '</td>' +
                    '<td>' +
                        '<button type="button" class="uh-btn uh-btn-primary uh-btn-xs" onclick="adViewAppointment(' + id + ')">View</button>' +
                    '</td></tr>';
            }).join('');
        }

        var statEl = document.getElementById('ad-stat-appts');
        if (statEl && data.pagination) { statEl.textContent = Number(data.pagination.total).toLocaleString(); }
        adRenderAdminPagination('appointments', data.pagination || { page: page, limit: 10, total: appointments.length, pages: 1 }, function (nextPage) {
            adLoadAppointments(nextPage);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-appts-body');
        if (tbody) { tbody.innerHTML = uhNoData(7, 'Unable to load appointments.'); }
        uhToast('Could not load appointments.', 'error');
    });
}

function adToggleTimeSort(tableKey) {
    var state = adTableState[tableKey];
    if (!state || !['appointments', 'referrals'].includes(tableKey)) { return; }
    state.sortOrder = state.sortOrder === 'asc' ? 'desc' : 'asc';
    state.page = 1;
    var indicator = document.getElementById('ad-' + tableKey + '-sort-indicator');
    if (indicator) { indicator.textContent = state.sortOrder === 'asc' ? '↑' : '↓'; }
    adLoadAdminTable(tableKey, 1);
}

function adApplyAppointmentFilters() {
    var statusFilter = document.getElementById('ad-appointment-status-filter');
    adTableState.appointments.status = statusFilter ? statusFilter.value : '';
    adLoadAppointments(1);
}

function adViewAppointment(id) {
    var appointment = adAppointmentRows[String(Number(id))];
    if (!appointment) {
        uhToast('Appointment details are no longer available. Refresh the table and try again.', 'error');
        return;
    }

    var student = appointment.student || {};
    var details = [
        'Patient: ' + ([student.first_name, student.last_name].filter(Boolean).join(' ') || '—'),
        'Patient type: ' + uhPatientTypeLabel(student.patient_type),
        'Patient ID: ' + (student.student_id || '—'),
        'Date: ' + uhFormatDate(appointment.appt_date),
        'Time: ' + uhFormatTime(appointment.appt_time),
        'Type: ' + (appointment.service_type || '—'),
        'Healthcare Professional: ' + (appointment.healthcare_professional || 'Not assigned'),
        'Status: ' + (appointment.status || 'pending'),
        'Reason: ' + (appointment.reason || '—')
    ];

    uhAlert(details.join('\n'), { title: 'Appointment details' });
}

function adLoadReferrals(page) {
    var state = adTableState.referrals;
    state.page = page;
    var requestId = ++state.requestId;
    var params = {
        page: page,
        limit: 10,
        q: state.search,
        campus_id: adReferralFilters.campusId || adCurrentCampus,
        referred_to: adReferralFilters.referredTo,
        sort_order: state.sortOrder
    };

    uhGet('/referrals' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var referrals = data.referrals || [];
        var tbody = document.getElementById('ad-referrals-body');
        if (!tbody) { return; }
        if (!referrals.length) {
            tbody.innerHTML = uhNoData(8, 'No referrals found.');
        } else {
            tbody.innerHTML = referrals.map(function (referral) {
                var student = referral.student || {};
                var name = [student.first_name, student.last_name].filter(Boolean).join(' ') || '—';
                var destination = referral.to_campus
                    ? referral.to_campus.name
                    : (referral.referred_to_name || referral.route_to || '—');
                var reason = String(referral.reason || '—');
                var referralId = Number(referral.id);
                return '<tr>' +
                    '<td>' + uhEscapeHtml(referral.referral_no || ('REF-' + referralId)) + '</td>' +
                    '<td>' + uhEscapeHtml(name) + '</td>' +
                    '<td>' + uhEscapeHtml(referral.from_campus ? referral.from_campus.name : '—') + '</td>' +
                    '<td>' + uhEscapeHtml(destination) + '</td>' +
                    '<td>' + uhEscapeHtml(reason.length > 40 ? reason.substring(0, 40) + '…' : reason) + '</td>' +
                    '<td>' + uhEscapeHtml(uhFormatDateTime(referral.created_at)) + '</td>' +
                    '<td>' + uhBadge(referral.status || 'pending') + '</td>' +
                    '<td>' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="adViewReferralPrescription(' + referralId + ')">View prescription</button> ' +
                        '<button class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeleteReferral(' + referralId + ')">Delete</button>' +
                    '</td></tr>';
            }).join('');
        }

        adRenderAdminPagination('referrals', data.pagination || { page: page, limit: 10, total: referrals.length, pages: 1 }, function (nextPage) {
            adLoadReferrals(nextPage);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-referrals-body');
        if (tbody) { tbody.innerHTML = uhNoData(8, 'Unable to load referrals.'); }
        uhToast('Could not load referrals.', 'error');
    });
}

function adLoadReferralFilters() {
    var select = document.getElementById('ad-referral-campus-filter');
    if (!select) { return; }

    uhGet('/admin/campuses').then(function (data) {
        var campuses = data.campuses || [];
        select.replaceChildren();
        var allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = 'All campuses';
        select.appendChild(allOption);
        campuses.forEach(function (campus) {
            var option = document.createElement('option');
            option.value = String(campus.id);
            option.textContent = campus.name || ('Campus #' + campus.id);
            select.appendChild(option);
        });
        select.value = adReferralFilters.campusId;
    }).catch(function () {
        uhToast('Could not load referral campus filters.', 'error');
    });
}

function adApplyReferralFilters() {
    var campusSelect = document.getElementById('ad-referral-campus-filter');
    var referredToInput = document.getElementById('ad-referral-to-filter');
    adReferralFilters.campusId = campusSelect ? campusSelect.value : '';
    adReferralFilters.referredTo = referredToInput ? referredToInput.value.trim() : '';
    adLoadReferrals(1);
}

function adScheduleReferralFilter() {
    clearTimeout(adReferralFilterTimer);
    adReferralFilterTimer = setTimeout(function () {
        adApplyReferralFilters();
    }, 250);
}

async function adDeleteReferral(id) {
    if (!await uhConfirm('This will permanently delete referral #' + id + '.', {
        title: 'Delete referral?',
        confirmText: 'Delete referral',
        danger: true
    })) {
        return;
    }

    uhDelete('/admin/referrals/' + Number(id)).then(function (data) {
        if (data.error) { uhToast(data.error, 'error'); return; }
        uhToast('Referral deleted.', 'success');
        adLoadReferrals(adTableState.referrals.page);
        adLoadAuditLog(adTableState.audit.page);
    }).catch(function () {
        uhToast('Could not delete referral.', 'error');
    });
}

function adViewReferralPrescription(id) {
    var prescriptionWindow = window.open('about:blank', '_blank');
    if (!prescriptionWindow) {
        uhToast('Allow pop-ups to view the prescription.', 'error');
        return;
    }
    prescriptionWindow.opener = null;

    uhGet('/admin/referrals/' + Number(id) + '/prescription').then(function (data) {
        var prescription = data.prescription;
        if (!prescription) {
            prescriptionWindow.close();
            uhToast('No prescription is linked to this referral.', 'info');
            return;
        }
        if (!prescription.has_pdf) {
            prescriptionWindow.close();
            uhToast('The linked prescription does not have a PDF available.', 'error');
            return;
        }

        prescriptionWindow.location.href = '/api/clinic/admin/referrals/' + Number(id) + '/prescription/view';
    }).catch(function () {
        prescriptionWindow.close();
        uhToast('Could not load the linked prescription.', 'error');
    });
}

// -- Add user -------------------------------------------
function adAddUser() {
    var pw  = document.getElementById('ad-u-pw').value;
    var pwc = document.getElementById('ad-u-pw-confirm').value;
    if (pw !== pwc) {
        uhToast('Passwords do not match.', 'error');
        return;
    }
    var payload = {
        first_name:  document.getElementById('ad-u-first').value.trim(),
        last_name:   document.getElementById('ad-u-last').value.trim(),
        email:       document.getElementById('ad-u-email').value.trim(),
        role:        document.getElementById('ad-u-role').value,
        campus_id:   parseInt(document.getElementById('ad-u-campus').value) || null,
        password:    pw,
        password_confirmation: pwc,
        employee_id: 'EMP-' + Date.now(),
    };
    if (!payload.first_name || !payload.last_name || !payload.email || !payload.role || !payload.password) {
        uhToast('All required fields must be filled.', 'error'); return;
    }
    uhPost('/admin/staff', payload).then(function (data) {
        if (!data.ok) {
            var msg = data.message
                || (data.errors && Object.values(data.errors).flat()[0])
                || data.error
                || 'Could not create user.';
            uhToast(msg, 'error');
            return;
        }
        uhToast('User created successfully.', 'success');
        uhCloseModal('ad-add-user-modal');
        // Clear form so the next add starts clean
        document.getElementById('ad-u-first').value = '';
        document.getElementById('ad-u-last').value  = '';
        document.getElementById('ad-u-email').value = '';
        document.getElementById('ad-u-role').value  = '';
        document.getElementById('ad-u-campus').value = '';
        document.getElementById('ad-u-pw').value       = '';
        document.getElementById('ad-u-pw-confirm').value = '';
        adLoadData();
    }).catch(function () { uhToast('Could not create user.', 'error'); });
}

function adLoadClinicRecords(page) {
    var tbody = document.getElementById('ad-records-body');
    if (!tbody) { return; }

    adClinicRecordsPage = Math.max(1, page);
    var requestId = ++adClinicRecordsRequest;
    var params = new URLSearchParams({
        page: String(adClinicRecordsPage),
        limit: '10',
    });
    if (adCurrentCampus) { params.set('campus_id', adCurrentCampus); }
    if (adClinicRecordsSearch) { params.set('search', adClinicRecordsSearch); }

    tbody.innerHTML = '<tr><td colspan="8" class="no-data">Loading clinic records…</td></tr>';
    uhGet('/admin/records?' + params.toString()).then(function (data) {
        if (requestId !== adClinicRecordsRequest) { return; }

        var records = Array.isArray(data.records) ? data.records : [];
        var pagination = data.pagination || {};
        var total = Number(pagination.total || 0);
        var pages = Math.max(1, Number(pagination.pages || 1));
        var firstRecord = total ? ((adClinicRecordsPage - 1) * 10) + 1 : 0;
        var lastRecord = Math.min(adClinicRecordsPage * 10, total);

        if (!records.length) {
            tbody.innerHTML = uhNoData(8, adClinicRecordsSearch ? 'No matching clinic records found.' : 'No clinic records found.');
        } else {
            tbody.innerHTML = records.map(function (record) {
                var student = record.student || {};
                var nurse = record.nurse || {};
                var name = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(' ') || '—';
                var nurseName = [nurse.first_name, nurse.last_name].filter(Boolean).join(' ') || '—';
                var vitals = [
                    record.bp ? 'BP ' + uhEscapeHtml(record.bp) : '',
                    record.temp ? 'Temp ' + uhEscapeHtml(record.temp) + '°' : '',
                    record.pulse ? 'PR ' + uhEscapeHtml(record.pulse) : '',
                    record.o2_sat ? 'O₂ ' + uhEscapeHtml(record.o2_sat) + '%' : '',
                ].filter(Boolean).join(' · ') || '—';

                return '<tr>' +
                    '<td>' + uhEscapeHtml(record.visit_date || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(name) + '</td>' +
                    '<td>' + uhEscapeHtml(student.student_id || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(record.reason || '—') + '</td>' +
                    '<td>' + vitals + '</td>' +
                    '<td>' + uhEscapeHtml(nurseName) + '</td>' +
                    '<td>' + uhBadge(record.disposition ? 'completed' : 'pending') + '</td>' +
                    '<td>' + (student.student_id
                        ? '<button type="button" class="uh-btn uh-btn-primary uh-btn-xs" onclick="uhViewStudentHealthRecord(this.dataset.studentId)" data-student-id="' + uhEscapeHtml(student.student_id) + '">View</button>'
                        : '—') + '</td>' +
                    '</tr>';
            }).join('');
        }

        var count = document.querySelector('#ad-records-body')
            ?.closest('.ad-card')
            ?.querySelector('[data-uh-table-count]');
        if (count) {
            count.textContent = total.toLocaleString() + ' record' + (total === 1 ? '' : 's');
        }
        var pageLabel = document.getElementById('ad-records-page-label');
        var previous = document.getElementById('ad-records-prev');
        var next = document.getElementById('ad-records-next');
        if (pageLabel) {
            pageLabel.textContent = total
                ? 'Showing ' + firstRecord.toLocaleString() + '–' + lastRecord.toLocaleString() + ' of ' + total.toLocaleString()
                : 'No records';
        }
        if (previous) { previous.disabled = adClinicRecordsPage <= 1; }
        if (next) { next.disabled = adClinicRecordsPage >= pages; }
    }).catch(function () {
        if (requestId !== adClinicRecordsRequest) { return; }
        tbody.innerHTML = uhNoData(8, 'Clinic records could not be loaded. Please refresh and try again.');
        var pageLabel = document.getElementById('ad-records-page-label');
        if (pageLabel) { pageLabel.textContent = 'Records unavailable'; }
    });
}

window.adRenderClinicRecordsSearch = function (table, query) {
    clearTimeout(adClinicRecordsSearchTimer);
    adClinicRecordsSearchTimer = setTimeout(function () {
        adClinicRecordsSearch = query;
        adLoadClinicRecords(1);
    }, 250);
};

// -- Delete patient -------------------------------------
function adViewPatient(studentId) {
    uhGet('/admin/students/' + encodeURIComponent(studentId)).then(function (data) {
        if (!data || !data.student) { uhToast('Patient not found.', 'error'); return; }
        adRenderPatientView(data.student);
        document.getElementById('ad-pv-modal').classList.remove('hidden');
    }).catch(function () { uhToast('Failed to load patient.', 'error'); });
}

function adClosePatientView() {
    document.getElementById('ad-pv-modal').classList.add('hidden');
}

function adRenderPatientView(s) {
    document.getElementById('ad-pv-title').textContent = (s.first_name || '') + ' ' + (s.last_name || '') + ' (' + (s.student_id || '—') + ')';
    var fields = {
        'ad-pv-student_id': s.student_id,
        'ad-pv-first_name': s.first_name,
        'ad-pv-last_name': s.last_name,
        'ad-pv-middle_name': s.middle_name,
        'ad-pv-email': s.email,
        'ad-pv-course_year': s.course_year,
        'ad-pv-department': s.department,
        'ad-pv-section': s.section,
        'ad-pv-contact': s.contact,
        'ad-pv-address': s.address,
        'ad-pv-sex': s.sex,
        'ad-pv-civil_status': s.civil_status,
        'ad-pv-age': s.age,
        'ad-pv-date_of_birth': s.date_of_birth ? String(s.date_of_birth).substring(0, 10) : '',
        'ad-pv-blood_type': s.blood_type,
        'ad-pv-campus': s.campus ? s.campus.name : '—',
        'ad-pv-role': s.role,
        'ad-pv-active': s.is_active ? 'Yes' : 'No',
        'ad-pv-created_at': uhFormatDate(s.created_at),
    };
    Object.keys(fields).forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) { return; }
        var v = fields[id];
        el.textContent = (v === null || v === undefined || v === '') ? '—' : String(v);
    });
    // Profile picture: if a URL was returned, show it; otherwise show initials avatar
    var pic = document.getElementById('ad-pv-picture');
    if (pic) {
        if (s.profile_picture) {
            pic.innerHTML = '<img src="' + s.profile_picture + '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"/>';
        } else {
            var initials = ((s.first_name || '').charAt(0) + (s.last_name || '').charAt(0)).toUpperCase() || '?';
            pic.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--uh-gold-light,#fef3c7);color:#78350f;font-size:28px;font-weight:700;border-radius:50%;">' + initials + '</div>';
        }
    }
}

// -- Patient create / edit form -------------------------
var adPatientFormState = { mode: 'create', id: null };

function adOpenPatientForm() {
    adPatientFormState = { mode: 'create', id: null };
    document.getElementById('ad-pf-title').textContent = 'Add Patient';
    document.getElementById('ad-pf-pw-hint').style.display = 'none';
    document.getElementById('ad-pf-pw-required').style.display = 'inline';
    document.getElementById('ad-pf-pw').required = true;
    adSetPatientConfirmRequired(true);
    var form = document.getElementById('ad-pf-form');
    if (form) { form.reset(); }
    adClearPwValidation('ad-pf-pw', 'ad-pf-pw-confirm');
    var idField = document.getElementById('ad-pf-student_id');
    if (idField) { idField.disabled = false; }
    adLoadPatientCampuses();
    document.getElementById('ad-pf-modal').classList.remove('hidden');
}

function adEditPatient(studentId) {
    adPatientFormState = { mode: 'edit', id: studentId };
    document.getElementById('ad-pf-title').textContent = 'Edit Patient (' + studentId + ')';
    document.getElementById('ad-pf-pw-hint').style.display = 'block';
    document.getElementById('ad-pf-pw-confirm-hint').style.display = 'block';
    document.getElementById('ad-pf-pw-required').style.display = 'none';
    document.getElementById('ad-pf-pw').required = false;
    adSetPatientConfirmRequired(false);
    adLoadPatientCampuses(function () {
        uhGet('/admin/students/' + encodeURIComponent(studentId)).then(function (data) {
            if (!data || data.ok === false) { uhToast('Patient not found.', 'error'); return; }
            if (!data.student) { uhToast('Patient not found.', 'error'); return; }
            var s = data.student;
            adFillPatientForm(s);
            document.getElementById('ad-pf-modal').classList.remove('hidden');
        }).catch(function () { uhToast('Failed to load patient.', 'error'); });
    });
}

function adSetPatientConfirmRequired(required) {
    var lbl   = document.getElementById('ad-pf-pw-confirm-required');
    var input = document.getElementById('ad-pf-pw-confirm');
    if (lbl)   { lbl.style.display   = required ? 'inline' : 'none'; }
    if (input) { input.required      = !!required; }
}

function adFillPatientForm(s) {
    var map = {
        'ad-pf-student_id': s.student_id,
        'ad-pf-first_name': s.first_name,
        'ad-pf-last_name': s.last_name,
        'ad-pf-middle_name': s.middle_name,
        'ad-pf-email': s.email,
        'ad-pf-course_year': s.course_year,
        'ad-pf-department': s.department,
        'ad-pf-section': s.section,
        'ad-pf-contact': s.contact,
        'ad-pf-address': s.address,
        'ad-pf-sex': s.sex,
        'ad-pf-civil_status': s.civil_status,
        'ad-pf-age': s.age,
        'ad-pf-date_of_birth': s.date_of_birth ? String(s.date_of_birth).substring(0, 10) : '',
        'ad-pf-blood_type': s.blood_type,
        'ad-pf-campus_id': s.campus_id || '',
    };
    Object.keys(map).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.value = map[id] == null ? '' : map[id]; }
    });
    // student_id is immutable on edit (the URL key) — keep it visible but disabled
    var idField = document.getElementById('ad-pf-student_id');
    if (idField) { idField.disabled = true; }
    var pw  = document.getElementById('ad-pf-pw');
    var cf  = document.getElementById('ad-pf-pw-confirm');
    if (pw) { pw.value  = ''; }
    if (cf) { cf.value  = ''; }
    adClearPwValidation('ad-pf-pw', 'ad-pf-pw-confirm');
}

function adLoadPatientCampuses(after) {
    var sel = document.getElementById('ad-pf-campus_id');
    var accountCampusSelect = document.getElementById('ad-pa-campus_id');
    if (!sel && !accountCampusSelect) { if (after) { after(); } return; }
    uhGet('/admin/campuses').then(function (data) {
        var rows = (data && data.campuses) || [];
        [sel, accountCampusSelect].forEach(function (select) {
            if (!select) { return; }
            var current = select.value;
            select.innerHTML = '<option value="">— None —</option>' + rows.map(function (c) {
                return '<option value="' + Number(c.id) + '">' + uhEscapeHtml(c.name || ('Campus #' + c.id)) + '</option>';
            }).join('');
            if (current) { select.value = current; }
        });
        if (after) { after(); }
    }).catch(function () {
        uhToast('Could not load campuses for the patient account form.', 'error');
        if (after) { after(); }
    });
}

function adClosePatientForm() {
    document.getElementById('ad-pf-modal').classList.add('hidden');
}

function adSavePatient() {
    var fields = [
        'student_id', 'first_name', 'last_name', 'middle_name', 'email',
        'course_year', 'department', 'section', 'contact', 'address', 'sex',
        'civil_status', 'age', 'date_of_birth', 'blood_type', 'campus_id',
    ];
    var payload = {};
    fields.forEach(function (f) {
        var el = document.getElementById('ad-pf-' + f);
        if (!el) { return; }
        var v = el.value;
        if (f === 'age' || f === 'campus_id') {
            v = v === '' ? null : parseInt(v, 10);
        } else if (v === '' && f !== 'student_id' && f !== 'first_name' && f !== 'last_name' && f !== 'email') {
            // Allow empty strings for optional fields — backend treats as nullable
            v = v === '' ? '' : v;
        }
        payload[f] = v;
    });
    // Password field uses non-standard ID — read explicitly
    var pfPwEl = document.getElementById('ad-pf-pw');
    if (pfPwEl && pfPwEl.value) { payload.password = pfPwEl.value; }

    // -- Password confirmation check --
    var state = adPatientFormState;
    var pw  = document.getElementById('ad-pf-pw');
    var cf  = document.getElementById('ad-pf-pw-confirm');
    if (pw && cf) {
        adClearPwValidation('ad-pf-pw', 'ad-pf-pw-confirm');
        var pwVal = pw.value || '';
        var cfVal = cf.value || '';
        if (state.mode === 'create') {
            if (!pwVal) { uhToast('Password is required.', 'error'); pw.focus(); return; }
            if (pwVal !== cfVal) {
                pw.classList.add('uh-pw-mismatch');
                cf.classList.add('uh-pw-mismatch');
                uhToast('Passwords do not match.', 'error');
                cf.focus();
                return;
            }
            // Send the confirmation so the server's `confirmed` rule passes
            payload.password_confirmation = cfVal;
        } else {
            // edit mode — only validate if the user started typing a new password
            if (pwVal || cfVal) {
                if (pwVal.length < 6) { uhToast('Password must be at least 6 characters.', 'error'); pw.focus(); return; }
                if (pwVal !== cfVal) {
                    pw.classList.add('uh-pw-mismatch');
                    cf.classList.add('uh-pw-mismatch');
                    uhToast('Passwords do not match.', 'error');
                    cf.focus();
                    return;
                }
                payload.password_confirmation = cfVal;
            }
        }
    }

    var promise;
    if (state.mode === 'edit' && state.id) {
        // Drop student_id from payload (immutable key, and the field is disabled anyway)
        delete payload.student_id;
        // Don't send an empty password on edit (backend keeps current)
        if (!payload.password) { delete payload.password; }
        promise = uhPut('/admin/students/' + encodeURIComponent(state.id), payload);
    } else {
        promise = uhPost('/admin/students', payload);
    }

    promise.then(function (data) {
        if (data && data.ok === false) {
            if (data.errors) {
                var first = Object.values(data.errors)[0];
                uhToast(Array.isArray(first) ? first[0] : 'Validation failed.', 'error');
            } else if (data.message) {
                uhToast(data.message, 'error');
            } else if (data.error) {
                uhToast(data.error, 'error');
            } else {
                uhToast('Save failed (HTTP ' + (data.status || '?') + ').', 'error');
            }
            return;
        }
        if (data && data.error) { uhToast(data.error, 'error'); return; }
        if (data && data.errors) {
            var first = Object.values(data.errors)[0];
            uhToast(Array.isArray(first) ? first[0] : 'Validation failed.', 'error');
            return;
        }
        uhToast(state.mode === 'edit' ? 'Patient updated.' : 'Patient created.', 'success');
        adClosePatientForm();
        adLoadData();
    }).catch(function (xhr) {
        var msg = 'Save failed.';
        try {
            var body = JSON.parse(xhr.responseText || '{}');
            if (body.errors) {
                msg = Object.values(body.errors)[0][0] || msg;
            } else if (body.message) {
                msg = body.message;
            }
        } catch (e) {}
        uhToast(msg, 'error');
    });
}

async function adDeletePatient(studentId) {
    if (!await uhConfirm('This will permanently delete student ' + studentId + '.', { title: 'Delete student?', confirmText: 'Delete student', danger: true })) { return; }
    uhDelete('/admin/students/' + studentId).then(function (data) {
        if (data.error) { uhToast(data.error, 'error'); return; }
        uhToast('Patient deleted.', 'success');
        adLoadData();
    }).catch(function () { uhToast('Delete failed.', 'error'); });
}

// -- Staff actions --------------------------------------
// -- Staff create / edit form --------------------------
var adStaffFormState = { mode: 'create', id: null };

function adOpenStaffForm() {
    adStaffFormState = { mode: 'create', id: null };
    document.getElementById('ad-sf-title').textContent = 'Add Staff';
    document.getElementById('ad-sf-pw-hint').style.display = 'none';
    document.getElementById('ad-sf-pw-required').style.display = 'inline';
    document.getElementById('ad-sf-pw').required = true;
    adSetStaffConfirmRequired(true);
    var form = document.getElementById('ad-sf-form');
    if (form) { form.reset(); }
    adClearPwValidation('ad-sf-pw', 'ad-sf-pw-confirm');
    var idField = document.getElementById('ad-sf-employee_id');
    if (idField) { idField.disabled = false; }
    adLoadStaffCampuses();
    document.getElementById('ad-sf-modal').classList.remove('hidden');
}

function adEditStaff(employeeId) {
    adStaffFormState = { mode: 'edit', id: employeeId };
    document.getElementById('ad-sf-title').textContent = 'Edit Staff (' + employeeId + ')';
    document.getElementById('ad-sf-pw-hint').style.display = 'block';
    document.getElementById('ad-sf-pw-confirm-hint').style.display = 'block';
    document.getElementById('ad-sf-pw-required').style.display = 'none';
    document.getElementById('ad-sf-pw').required = false;
    adSetStaffConfirmRequired(false);
    adLoadStaffCampuses(function () {
        uhGet('/admin/staff/' + encodeURIComponent(employeeId)).then(function (data) {
            if (!data || data.ok === false) { uhToast('Staff not found.', 'error'); return; }
            if (!data.staff) { uhToast('Staff not found.', 'error'); return; }
            adFillStaffForm(data.staff);
            document.getElementById('ad-sf-modal').classList.remove('hidden');
        }).catch(function () { uhToast('Failed to load staff.', 'error'); });
    });
}

function adSetStaffConfirmRequired(required) {
    var lbl   = document.getElementById('ad-sf-pw-confirm-required');
    var input = document.getElementById('ad-sf-pw-confirm');
    if (lbl)   { lbl.style.display   = required ? 'inline' : 'none'; }
    if (input) { input.required      = !!required; }
}

function adClearPwValidation(pwId, cfId) {
    var pw = document.getElementById(pwId);
    var cf = document.getElementById(cfId);
    if (pw) { pw.classList.remove('uh-pw-mismatch', 'uh-pw-match'); }
    if (cf) { cf.classList.remove('uh-pw-mismatch', 'uh-pw-match'); }
}

function adFillStaffForm(s) {
    var map = {
        'ad-sf-employee_id': s.employee_id,
        'ad-sf-first_name': s.first_name,
        'ad-sf-last_name': s.last_name,
        'ad-sf-middle_name': s.middle_name,
        'ad-sf-email': s.email,
        'ad-sf-role': s.role,
        'ad-sf-license_no': s.license_no,
        'ad-sf-contact': s.contact,
        'ad-sf-specialization': s.specialization,
        'ad-sf-campus_id': s.campus_id || '',
    };
    Object.keys(map).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.value = map[id] == null ? '' : map[id]; }
    });
    var activeEl = document.getElementById('ad-sf-is_active');
    if (activeEl) { activeEl.checked = s.is_active !== false; }
    var idField = document.getElementById('ad-sf-employee_id');
    if (idField) { idField.disabled = true; }
    var pw  = document.getElementById('ad-sf-pw');
    var cf  = document.getElementById('ad-sf-pw-confirm');
    if (pw) { pw.value  = ''; }
    if (cf) { cf.value  = ''; }
    adClearPwValidation('ad-sf-pw', 'ad-sf-pw-confirm');
}

function adLoadStaffCampuses(after) {
    var sel = document.getElementById('ad-sf-campus_id');
    if (!sel) { if (after) { after(); } return; }
    uhGet('/admin/campuses').then(function (data) {
        var current = sel.value;
        var rows = (data && data.campuses) || [];
        sel.innerHTML = '<option value="">— All Campuses (system-wide access) —</option>' + rows.map(function (c) {
            return '<option value="' + c.id + '">' + (c.name || ('Campus #' + c.id)) + '</option>';
        }).join('');
        if (current) { sel.value = current; }
        if (after) { after(); }
    }).catch(function () { if (after) { after(); } });
}

function adCloseStaffForm() {
    document.getElementById('ad-sf-modal').classList.add('hidden');
}

function adSaveStaff() {
    var fields = [
        'employee_id', 'first_name', 'last_name', 'middle_name', 'email',
        'role', 'license_no', 'contact', 'specialization', 'campus_id',
    ];
    var payload = {};
    fields.forEach(function (f) {
        var el = document.getElementById('ad-sf-' + f);
        if (!el) { return; }
        var v = el.value;
        if (f === 'campus_id') { v = v === '' ? null : parseInt(v, 10); }
        payload[f] = v;
    });
    // Password fields use non-standard IDs — read them explicitly
    var pwEl = document.getElementById('ad-sf-pw');
    if (pwEl && pwEl.value) { payload.password = pwEl.value; }
    var activeEl = document.getElementById('ad-sf-is_active');
    if (activeEl) { payload.is_active = activeEl.checked; }

    // -- Password confirmation check --
    var state = adStaffFormState;
    var pw  = document.getElementById('ad-sf-pw');
    var cf  = document.getElementById('ad-sf-pw-confirm');
    if (pw && cf) {
        adClearPwValidation('ad-sf-pw', 'ad-sf-pw-confirm');
        var pwVal = pw.value || '';
        var cfVal = cf.value || '';
        if (state.mode === 'create') {
            if (!pwVal) { uhToast('Password is required.', 'error'); pw.focus(); return; }
            if (pwVal !== cfVal) {
                pw.classList.add('uh-pw-mismatch');
                cf.classList.add('uh-pw-mismatch');
                uhToast('Passwords do not match.', 'error');
                cf.focus();
                return;
            }
            // Send the confirmation so the server's `confirmed` rule passes
            payload.password_confirmation = cfVal;
        } else {
            // edit mode — only validate if the user started typing a new password
            if (pwVal || cfVal) {
                if (pwVal.length < 6) { uhToast('Password must be at least 6 characters.', 'error'); pw.focus(); return; }
                if (pwVal !== cfVal) {
                    pw.classList.add('uh-pw-mismatch');
                    cf.classList.add('uh-pw-mismatch');
                    uhToast('Passwords do not match.', 'error');
                    cf.focus();
                    return;
                }
                payload.password_confirmation = cfVal;
            }
        }
    }

    var promise;
    if (state.mode === 'edit' && state.id) {
        delete payload.employee_id;
        if (!payload.password) { delete payload.password; }
        promise = uhPut('/admin/staff/' + encodeURIComponent(state.id), payload);
    } else {
        promise = uhPost('/admin/staff', payload);
    }

    promise.then(function (data) {
        if (data && data.ok === false) {
            // Backend returned a non-2xx with a JSON body
            if (data.errors) {
                var first = Object.values(data.errors)[0];
                uhToast(Array.isArray(first) ? first[0] : 'Validation failed.', 'error');
            } else if (data.message) {
                uhToast(data.message, 'error');
            } else if (data.error) {
                uhToast(data.error, 'error');
            } else {
                uhToast('Save failed (HTTP ' + (data.status || '?') + ').', 'error');
            }
            return;
        }
        if (data && data.error) { uhToast(data.error, 'error'); return; }
        if (data && data.errors) {
            var first = Object.values(data.errors)[0];
            uhToast(Array.isArray(first) ? first[0] : 'Validation failed.', 'error');
            return;
        }
        uhToast(state.mode === 'edit' ? 'Staff updated.' : 'Staff created.', 'success');
        adCloseStaffForm();
        adLoadData();
    }).catch(function (xhr) {
        var msg = 'Save failed.';
        try {
            var body = JSON.parse(xhr.responseText || '{}');
            if (body.errors) {
                msg = Object.values(body.errors)[0][0] || msg;
            } else if (body.message) {
                msg = body.message;
            }
        } catch (e) {}
        uhToast(msg, 'error');
    });
}

// -- Staff view modal -----------------------------------
function adViewStaff(employeeId) {
    uhGet('/admin/staff/' + encodeURIComponent(employeeId)).then(function (data) {
        if (!data || data.ok === false) { uhToast('Staff not found.', 'error'); return; }
        if (!data.staff) { uhToast('Staff not found.', 'error'); return; }
        adRenderStaffView(data.staff);
        document.getElementById('ad-sv-modal').classList.remove('hidden');
    }).catch(function () { uhToast('Failed to load staff.', 'error'); });
}

function adCloseStaffView() {
    document.getElementById('ad-sv-modal').classList.add('hidden');
}

function adRenderStaffView(s) {
    document.getElementById('ad-sv-title').textContent =
        (s.first_name || '') + ' ' + (s.last_name || '') + ' (' + (s.employee_id || '—') + ')';
    var fields = {
        'ad-sv-employee_id': s.employee_id,
        'ad-sv-first_name': s.first_name,
        'ad-sv-last_name': s.last_name,
        'ad-sv-middle_name': s.middle_name,
        'ad-sv-email': s.email,
        'ad-sv-role': s.role,
        'ad-sv-license_no': s.license_no,
        'ad-sv-contact': s.contact,
        'ad-sv-specialization': s.specialization,
        'ad-sv-campus': s.campus ? s.campus.name : 'All Campuses',
        'ad-sv-active': s.is_active ? 'Yes' : 'No',
        'ad-sv-created_at': uhFormatDate(s.created_at),
    };
    Object.keys(fields).forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) { return; }
        var v = fields[id];
        el.textContent = (v === null || v === undefined || v === '') ? '—' : String(v);
    });
    // Capitalize role for display
    var roleEl = document.getElementById('ad-sv-role');
    if (roleEl && s.role) { roleEl.textContent = s.role.charAt(0).toUpperCase() + s.role.slice(1); }
}

async function adDeleteStaff(employeeId) {
    if (!await uhConfirm('This will permanently delete staff member ' + employeeId + '.', { title: 'Delete staff member?', confirmText: 'Delete staff', danger: true })) { return; }
    uhDelete('/admin/staff/' + employeeId).then(function (data) {
        if (data.error) { uhToast(data.error, 'error'); return; }
        uhToast('Staff deleted.', 'success');
        adLoadData();
    }).catch(function () { uhToast('Delete failed.', 'error'); });
}

// -- Audit log ------------------------------------------
function adLoadAuditLog(page) {
    var state = adTableState.audit;
    state.page = page;
    var requestId = ++state.requestId;
    var params = { page: page, limit: 10, search: state.search };

    uhGet('/admin/audit-log' + adBuildQuery(params)).then(function (data) {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-audit-body');
        var logs = data.logs || [];
        if (!tbody) { return; }
        if (!logs.length) {
            tbody.innerHTML = uhNoData(5, 'No audit entries.');
        } else {
            tbody.innerHTML = logs.map(function (log) {
                return '<tr>' +
                    '<td>' + uhEscapeHtml(uhFormatDateTime(log.created_at)) + '</td>' +
                    '<td>' + uhEscapeHtml(log.user_id || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(log.user_type || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(log.action || '—') + '</td>' +
                    '<td>' + uhEscapeHtml(log.entity_type || '—') + (log.entity_id ? ' #' + uhEscapeHtml(log.entity_id) : '') + '</td>' +
                    '</tr>';
            }).join('');
        }

        adRenderAdminPagination('audit', data.pagination || { page: page, limit: 10, total: logs.length, pages: 1 }, function (nextPage) {
            adLoadAuditLog(nextPage);
        });
    }).catch(function () {
        if (requestId !== state.requestId) { return; }
        var tbody = document.getElementById('ad-audit-body');
        if (tbody) { tbody.innerHTML = uhNoData(5, 'Unable to load audit logs.'); }
        uhToast('Could not load audit logs.', 'error');
    });
}

// -- Change password ------------------------------------
function adChangePw() {
    var curPw  = document.getElementById('ad-cur-pw').value;
    var newPw  = document.getElementById('ad-new-pw').value;
    var confPw = document.getElementById('ad-conf-pw').value;
    if (!curPw || !newPw || !confPw) { uhToast('All password fields are required.', 'error'); return; }
    if (newPw !== confPw)            { uhToast('New passwords do not match.', 'error'); return; }
    if (newPw.length < 6)            { uhToast('Password must be at least 6 characters.', 'error'); return; }
    var user = uhGetUser();
    if (!user) { uhToast('Session expired.', 'error'); return; }
    uhPut('/student/profile', {
        student_no:       user.identifier,
        current_password: curPw,
        new_password:     newPw,
    }).then(function (data) {
        if (data.error) { uhToast(data.error, 'error'); return; }
        uhToast('Password updated.', 'success');
        document.getElementById('ad-cur-pw').value  = '';
        document.getElementById('ad-new-pw').value  = '';
        document.getElementById('ad-conf-pw').value = '';
    }).catch(function () { uhToast('Password update failed.', 'error'); });
}

// -- Export ---------------------------------------------
function adExport() {
    window.location.href = '/api/clinic/admin/export';
}

function adClearDemo() {
    uhToast('Sample records are preserved in the live database.', 'info');
}

// -----------------------------------------------------------------------
// Services CRUD
// -----------------------------------------------------------------------
async function adRenderServices() {
    const tbody = document.getElementById('ad-services-body');
    if (!tbody) { return; }
    try {
        const res = await uhGet('/admin/services');
        const list = (res && res.services) || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-data">No services yet. Click "Add Service" to create one.</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(function (s) {
            const active = s.is_active ? '<span class="uh-badge active">Active</span>' : '<span class="uh-badge cancelled">Inactive</span>';
            const dur = s.duration_minutes || '—';
            return '<tr>' +
                '<td>' + (s.name || '—') + '</td>' +
                '<td><span class="uh-badge role-nurse">' + (s.category || '—') + '</span></td>' +
                '<td>' + dur + ' min</td>' +
                '<td>' + active + '</td>' +
                '<td><button class="uh-btn uh-btn-danger uh-btn-xs" onclick="adDeleteService(' + s.id + ')"><i class="fa fa-trash"></i> Delete</button></td>' +
                '</tr>';
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data">Failed to load services.</td></tr>';
    }
}

async function adSaveService() {
    const name = (document.getElementById('ad-svc-name').value || '').trim();
    const category = (document.getElementById('ad-svc-category').value || '').trim();
    if (!name || !category) { uhToast('Name and category are required.', 'error'); return; }
    const body = {
        name: name,
        category: category,
        description: (document.getElementById('ad-svc-description').value || '').trim(),
        duration_minutes: (document.getElementById('ad-svc-duration').value || '15').trim(),
        requires_appointment: document.getElementById('ad-svc-requires-appt').checked,
        is_active: document.getElementById('ad-svc-active').checked,
    };
    try {
        const res = await uhPost('/admin/services', body);
        if (res && res.success) {
            uhToast('Service added.', 'success');
            uhCloseModal('ad-add-service-modal');
            document.getElementById('ad-svc-form').reset();
            document.getElementById('ad-svc-duration').value = '15';
            adRenderServices();
        } else {
            uhToast((res && res.error) || 'Failed to add service.', 'error');
        }
    } catch (e) {
        uhToast('Failed to add service.', 'error');
    }
}

async function adDeleteService(id) {
    if (!await uhConfirm('This will permanently delete this clinic service.', { title: 'Delete service?', confirmText: 'Delete service', danger: true })) { return; }
    try {
        const res = await fetch('/api/clinic/admin/services/' + id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(function () { return {}; });
        if (data && data.success) {
            uhToast('Service deleted.', 'success');
            adRenderServices();
        } else {
            uhToast((data && data.error) || 'Failed to delete service.', 'error');
        }
    } catch (e) {
        uhToast('Failed to delete service.', 'error');
    }
}

// -----------------------------------------------------------------------
// Save clinic settings (4 fields at once)
// -----------------------------------------------------------------------
let adAiAnalysisCustomModels = [];
let adAiTranscriptionCustomModels = [];

function adRenderCustomAiModels(type) {
    const models = type === 'analysis' ? adAiAnalysisCustomModels : adAiTranscriptionCustomModels;
    const container = document.getElementById('ad-settings-' + type + '-custom-list');
    if (!container) { return; }
    container.replaceChildren();
    if (!models.length) {
        const emptyRow = document.createElement('tr');
        const emptyCell = document.createElement('td');
        emptyCell.colSpan = 3;
        emptyCell.className = 'no-data';
        emptyCell.textContent = 'No custom ' + type + ' models added.';
        emptyRow.appendChild(emptyCell);
        container.appendChild(emptyRow);
        return;
    }

    models.forEach(function (model) {
        const row = document.createElement('tr');
        if (model.editing) {
            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.maxLength = 100;
            nameInput.value = model.name;
            nameInput.setAttribute('aria-label', 'Model name');
            const idInput = document.createElement('input');
            idInput.type = 'text';
            idInput.maxLength = 120;
            idInput.value = model.id;
            idInput.setAttribute('aria-label', 'Model API ID');
            const nameCell = document.createElement('td');
            nameCell.appendChild(nameInput);
            const idCell = document.createElement('td');
            idCell.appendChild(idInput);
            const actionCell = document.createElement('td');
            const save = document.createElement('button');
            save.type = 'button';
            save.className = 'uh-btn uh-btn-primary uh-btn-xs';
            save.textContent = 'Save';
            save.addEventListener('click', function () {
                adSaveCustomAiModelEdit(type, model.id, nameInput.value, idInput.value);
            });
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'uh-btn uh-btn-ghost uh-btn-xs';
            cancel.textContent = 'Cancel';
            cancel.addEventListener('click', function () {
                model.editing = false;
                adRenderCustomAiModels(type);
            });
            const actionButtons = document.createElement('div');
            actionButtons.className = 'ad-model-registry-actions';
            actionButtons.append(save, cancel);
            actionCell.appendChild(actionButtons);
            row.append(nameCell, idCell, actionCell);
        } else {
            const nameCell = document.createElement('td');
            nameCell.textContent = model.name;
            const idCell = document.createElement('td');
            idCell.textContent = model.id;
            const actionCell = document.createElement('td');
            const actionButtons = document.createElement('div');
            actionButtons.className = 'ad-model-registry-actions';
            const actions = [
                ['Use', 'uh-btn uh-btn-primary uh-btn-xs', function () {
                    adUseAiModel(type, model.id);
                }],
                ['Edit', 'uh-btn uh-btn-outline uh-btn-xs', function () {
                    model.editing = true;
                    adRenderCustomAiModels(type);
                }],
                ['Delete', 'uh-btn uh-btn-danger uh-btn-xs', function () {
                    adRemoveAiModel(type, model.id);
                }],
            ];
            actions.forEach(function ([label, className, onClick]) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = className;
                button.textContent = label;
                button.addEventListener('click', onClick);
                actionButtons.appendChild(button);
            });
            actionCell.appendChild(actionButtons);
            row.append(nameCell, idCell, actionCell);
        }
        container.appendChild(row);
    });
}

function adAddAiModel(type) {
    if (!['analysis', 'transcription'].includes(type)) { return; }
    const nameInput = document.getElementById('ad-settings-' + type + '-model-name');
    const input = document.getElementById('ad-settings-' + type + '-model-id');
    const select = document.getElementById('ad-settings-' + type + '-model');
    const models = type === 'analysis' ? adAiAnalysisCustomModels : adAiTranscriptionCustomModels;
    const name = nameInput ? nameInput.value.trim() : '';
    const model = input ? input.value.trim() : '';
    if (!name || name.length > 100) {
        uhToast('Enter a model name of up to 100 characters.', 'error');
        return;
    }
    if (!/^[A-Za-z0-9][A-Za-z0-9._:/-]{0,119}$/.test(model)) {
        uhToast('Enter a valid model API ID using letters, numbers, ., _, :, /, or -.', 'error');
        return;
    }
    if (Array.from(select.options).some(function (option) { return option.value === model; })) {
        uhToast('That model API ID is already available.', 'error');
        return;
    }
    models.push({ name: name, id: model });
    select.add(new Option(name, model));
    nameInput.value = '';
    input.value = '';
    adRenderCustomAiModels(type);
}

async function adUseAiModel(type, modelId) {
    if (!['analysis', 'transcription'].includes(type)) { return; }
    const select = document.getElementById('ad-settings-' + type + '-model');
    select.value = modelId;
    if (!await adSaveAiModelSettings()) {
        await adLoadAiModelSettings();
    }
}

async function adSaveCustomAiModelEdit(type, previousId, name, id) {
    if (!['analysis', 'transcription'].includes(type)) { return; }
    const models = type === 'analysis' ? adAiAnalysisCustomModels : adAiTranscriptionCustomModels;
    const model = models.find(function (item) { return item.id === previousId; });
    const normalizedName = name.trim();
    const normalizedId = id.trim();
    const select = document.getElementById('ad-settings-' + type + '-model');
    if (!model) { return; }
    if (!normalizedName || normalizedName.length > 100) {
        uhToast('Enter a model name of up to 100 characters.', 'error');
        return;
    }
    if (!/^[A-Za-z0-9][A-Za-z0-9._:/-]{0,119}$/.test(normalizedId)) {
        uhToast('Enter a valid model API ID using letters, numbers, ., _, :, /, or -.', 'error');
        return;
    }
    if (normalizedId !== previousId && Array.from(select.options).some(function (option) {
        return option.value === normalizedId;
    })) {
        uhToast('That model API ID is already available.', 'error');
        return;
    }

    const wasSelected = select.value === previousId;
    model.name = normalizedName;
    model.id = normalizedId;
    model.editing = false;
    const option = Array.from(select.options).find(function (item) { return item.value === previousId; });
    if (option) {
        option.value = normalizedId;
        option.textContent = normalizedName;
    }
    if (wasSelected) { select.value = normalizedId; }
    adRenderCustomAiModels(type);
    if (!await adSaveAiModelSettings()) {
        await adLoadAiModelSettings();
    }
}

async function adRemoveAiModel(type, modelId) {
    if (!['analysis', 'transcription'].includes(type)) { return; }
    const select = document.getElementById('ad-settings-' + type + '-model');
    const models = type === 'analysis' ? adAiAnalysisCustomModels : adAiTranscriptionCustomModels;
    if (!await uhConfirm('Delete this saved model API ID?', {
        title: 'Delete model?',
        confirmText: 'Delete',
        danger: true,
    })) { return; }
    const index = models.findIndex(function (item) { return item.id === modelId; });
    if (index < 0) { return; }
    const [removedModel] = models.splice(index, 1);
    const selectedModel = select.value;
    const option = Array.from(select.options).find(function (item) { return item.value === modelId; });
    if (option) { option.remove(); }
    if (selectedModel === modelId) { select.selectedIndex = 0; }
    adRenderCustomAiModels(type);
    if (!await adSaveAiModelSettings()) {
        if (type === 'analysis') { adAiAnalysisCustomModels.splice(index, 0, removedModel); }
        else { adAiTranscriptionCustomModels.splice(index, 0, removedModel); }
        await adLoadAiModelSettings();
    }
}

async function adLoadAiModelSettings() {
    const analysisSelect = document.getElementById('ad-settings-analysis-model');
    const transcriptionSelect = document.getElementById('ad-settings-transcription-model');
    const saveButton = document.getElementById('ad-settings-ai-models-save');
    const status = document.getElementById('ad-settings-ai-models-status');
    if (!analysisSelect || !transcriptionSelect || !saveButton || !status) { return; }

    saveButton.disabled = true;
    status.textContent = 'Loading available models...';
    try {
        const response = await uhGet('/admin/settings');
        const models = response && response.ai_models;
        if (!response || !response.success || !models) {
            throw new Error('Could not load AI model settings.');
        }

        function populate(select, choices, selected) {
            select.replaceChildren();
            Object.entries(choices || {}).forEach(function ([value, label]) {
                select.add(new Option(label, value));
            });
            select.value = selected;
            select.disabled = select.options.length === 0;
        }

        populate(analysisSelect, models.analysis.options, models.analysis.current);
        populate(transcriptionSelect, models.transcription.options, models.transcription.current);
        adAiAnalysisCustomModels = Array.isArray(models.analysis.custom_models)
            ? models.analysis.custom_models
            : Object.entries(models.analysis.custom_options || {}).map(function ([id, name]) {
                return { id: id, name: name };
            });
        adAiTranscriptionCustomModels = Array.isArray(models.transcription.custom_models)
            ? models.transcription.custom_models
            : Object.entries(models.transcription.custom_options || {}).map(function ([id, name]) {
                return { id: id, name: name };
            });
        adRenderCustomAiModels('analysis');
        adRenderCustomAiModels('transcription');
        const providerAvailable = models.provider === 'groq';
        analysisSelect.disabled = !providerAvailable || analysisSelect.options.length === 0;
        transcriptionSelect.disabled = !providerAvailable
            || models.transcription.configured !== true
            || transcriptionSelect.options.length === 0;
        saveButton.disabled = !providerAvailable || analysisSelect.disabled;
        status.textContent = providerAvailable
            ? (models.transcription.configured === true
                ? 'Settings loaded. Save changes here after editing API IDs or model selections.'
                : 'Configure GROQ_API_KEY on the server to enable transcription model selection. Analysis and API ID changes can still be saved.')
            : 'Model selection is available only when the clinic AI provider is Groq.';
    } catch (error) {
        const errorMessage = error.message || 'Could not load AI model settings.';
        status.textContent = errorMessage;
        uhToast(errorMessage, 'error');
    }
}

async function adSaveAiModelSettings() {
    const analysisSelect = document.getElementById('ad-settings-analysis-model');
    const transcriptionSelect = document.getElementById('ad-settings-transcription-model');
    const saveButton = document.getElementById('ad-settings-ai-models-save');
    const status = document.getElementById('ad-settings-ai-models-status');
    if (!analysisSelect || !transcriptionSelect || !saveButton || !status) { return; }

    saveButton.disabled = true;
    status.textContent = 'Saving model API IDs and selections...';
    try {
        const response = await uhPost('/admin/settings/models', {
            analysis_model: analysisSelect.value,
            transcription_model: transcriptionSelect.value,
            analysis_models: adAiAnalysisCustomModels.map(function (model) {
                return { name: model.name, id: model.id };
            }),
            transcription_models: adAiTranscriptionCustomModels.map(function (model) {
                return { name: model.name, id: model.id };
            }),
        });
        if (!response || !response.success) {
            const validationError = response && response.errors
                ? Object.values(response.errors).flat()[0]
                : null;
            throw new Error(validationError || (response && response.message) || 'Could not save AI model settings.');
        }

        status.textContent = 'Model API IDs and model selections saved.';
        uhToast(status.textContent, 'success');
        await adLoadAiModelSettings();
        return true;
    } catch (error) {
        const errorMessage = error.message || 'Could not save model API IDs.';
        status.textContent = errorMessage;
        uhToast(errorMessage, 'error');
        return false;
    } finally {
        saveButton.disabled = analysisSelect.disabled;
    }
}

async function adSaveClinicSettings() {
    const body = {
        clinic_name: (document.getElementById('ad-settings-clinic_name').value || '').trim(),
        clinic_address: (document.getElementById('ad-settings-clinic_address').value || '').trim(),
        clinic_contact: (document.getElementById('ad-settings-clinic_contact').value || '').trim(),
    };
    const emailEl = document.getElementById('ad-settings-clinic_email');
    if (emailEl) { body.clinic_email = (emailEl.value || '').trim(); }
    try {
        const res = await uhPost('/admin/settings/save', body);
        if (res && res.success) {
            uhToast('Clinic settings saved (' + (res.updated || 0) + ' field' + (res.updated === 1 ? '' : 's') + ').', 'success');
        } else {
            uhToast((res && (res.error || (res.errors && Object.values(res.errors)[0] && Object.values(res.errors)[0][0]))) || 'Failed to save settings.', 'error');
        }
    } catch (e) {
        uhToast('Failed to save settings.', 'error');
    }
}

// -----------------------------------------------------------------------
// Restore backup (file upload)
// -----------------------------------------------------------------------
async function adRestoreBackup() {
    const input = document.getElementById('ad-restore-file');
    if (!input || !input.files || !input.files[0]) {
        uhToast('Please choose a backup file first.', 'error');
        return;
    }
    const file = input.files[0];
    const fd = new FormData();
    fd.append('file', file);
    try {
        const res = await fetch('/api/clinic/admin/backup/restore', {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(function () { return {}; });
        if (data && data.success) {
            uhToast(data.message || 'Backup uploaded successfully.', 'success');
            input.value = '';
        } else {
            uhToast((data && data.error) || 'Restore failed.', 'error');
        }
    } catch (e) {
        uhToast('Restore failed.', 'error');
    }
}

// Boot: render services table when the page loads
document.addEventListener('DOMContentLoaded', function () {
    adRenderServices();
});

// Boot: render services table when the page loads
document.addEventListener('DOMContentLoaded', function () {
    adRenderServices();
});
</script>
</body>
</html>
