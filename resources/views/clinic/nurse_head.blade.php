<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Head Nurse Dashboard — SUNN Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/unihealth.css') }}?v={{ filemtime(public_path('clinic/css/unihealth.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/student-dashboard-reference.css') }}?v={{ filemtime(public_path('clinic/css/student-dashboard-reference.css')) }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/nurse-forms.css') }}"/>
    <link rel="stylesheet" href="{{ asset('clinic/css/analytics.css') }}?v={{ filemtime(public_path('clinic/css/analytics.css')) }}"/>
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
    <style>
        /* ── Modal overlay & box ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.hidden { display: none; }

        .modal-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 28px 32px;
            width: 560px;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            color: #1a1a1a;
        }

        /* ── Modal header ── */
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            font-size: 1.1rem;
            padding: 4px 6px;
            border-radius: 4px;
            line-height: 1;
            transition: background 0.15s, color 0.15s;
        }
        .modal-close:hover { background: #f3f4f6; color: #111; }

        /* ── Form groups ── */
        .mfg {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 14px;
        }
        .mfg label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
        }
        .mfg input,
        .mfg select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #1a1a1a;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            width: 100%;
            box-sizing: border-box;
        }
        .mfg input:focus,
        .mfg select:focus {
            border-color: #1a6b3a;
            box-shadow: 0 0 0 3px rgba(26,107,58,0.1);
        }
        .mfg-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .mfg-row .mfg { margin-bottom: 0; }

        /* ── Required asterisk ── */
        .req { color: #ef4444; margin-left: 2px; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 0.83rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-primary {
            background: #1a6b3a;
            color: #fff;
            border-color: #1a6b3a;
        }
        .btn-primary:hover { background: #14532d; border-color: #14532d; }
        .btn-outline {
            background: transparent;
            color: #374151;
            border-color: #d1d5db;
        }
        .btn-outline:hover { background: #f9fafb; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; }
        .btn-danger { background: #ef4444; color: #fff; border-color: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; }

        /* ── Enhanced stat cards ── */
        #page-dashboard .uh-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }
        #page-appointments .nh-appt-stats {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        #page-appointments .nh-appt-stats .uh-stat-card {
            min-width: 0;
        }
        #page-appointments .nh-appt-stats .uh-stat-label {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.3;
        }
        @media (max-width: 900px) {
            #page-appointments .nh-appt-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 480px) {
            #page-appointments .nh-appt-stats { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 900px) {
            #page-dashboard .uh-stats-grid { grid-template-columns: 1fr; }
        }
        #page-dashboard .uh-stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 22px 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 0;
            min-height: 148px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
            transition: all 0.22s ease;
            position: relative;
            overflow: hidden;
        }
        #page-dashboard .uh-stat-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--uh-primary), #4ade80);
            opacity: 0;
            transition: opacity 0.22s;
        }
        #page-dashboard .uh-stat-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
            transform: translateY(-3px);
        }
        #page-dashboard .uh-stat-card:hover::after { opacity: 1; }
        #page-dashboard .uh-stat-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        #page-dashboard .uh-stat-number {
            font-size: 2.3rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 5px;
            letter-spacing: -1px;
        }
        #page-dashboard .uh-stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        #page-dashboard .uh-stat-trend {
            font-size: 0.75rem;
            font-weight: 500;
            color: #16a34a;
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        #page-dashboard .uh-stat-trend.danger { color: #dc2626; }

        /* ── Layout ── */
        #page-dashboard .uh-grid-2-1 {
            display: grid;
            grid-template-columns: 2fr 1.15fr;
            gap: 22px;
            margin-bottom: 28px;
            align-items: start;
        }
        @media (max-width: 960px) {
            #page-dashboard .uh-grid-2-1 { grid-template-columns: 1fr; }
        }
        #page-dashboard .uh-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        #page-dashboard .uh-grid-2-1 > div:first-child .uh-card:first-child { margin-bottom: 22px; }
        #page-dashboard .uh-card-header { margin-bottom: 18px; }

        /* ── Analytics wrapper ── */
        #page-dashboard .uh-analytics {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }
        #page-dashboard .an-page-header { display: none; }

        /* ── Performance metric cards ── */
        #page-dashboard .pm-metric-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 14px;
        }
        @media (max-width: 1100px) {
            #page-dashboard .pm-metric-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px)  {
            #page-dashboard .pm-metric-row { grid-template-columns: 1fr; }
        }
        #page-dashboard .pm-metric-card {
            position: relative;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 16px 18px;
            min-height: 100px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        #page-dashboard .pm-metric-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--uh-primary);
        }
        #page-dashboard .pm-metric-card:nth-child(2)::before { background: #2563eb; }
        #page-dashboard .pm-metric-card:nth-child(3)::before { background: #0d9488; }
        #page-dashboard .pm-metric-card:nth-child(4)::before { background: #ca8a04; }
        #page-dashboard .pm-metric-card:nth-child(5)::before { background: #dc2626; }
        #page-dashboard .pm-metric-left {
            min-height: 68px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        #page-dashboard .pm-metric-value {
            font-size: 1.7rem;
            margin-bottom: 4px;
        }
        #page-dashboard .pm-trend-row {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
            grid-template-areas:
                "trend activity"
                "messages goals";
            gap: 14px;
            margin-bottom: 14px;
            align-items: stretch;
        }
        #page-dashboard .nh-pm-trend-main-col,
        #page-dashboard .nh-clinic-side-col { display: contents; }
        #page-dashboard .pm-trend-chart-card { grid-area: trend; }
        #page-dashboard .nh-dashboard-messages-card { grid-area: messages; }
        #page-dashboard .nh-clinic-side-col .pm-activity-card { grid-area: activity; }
        #page-dashboard .nh-clinic-side-col .pm-goal-card { grid-area: goals; }
        @media (max-width: 1040px) {
            #page-dashboard .pm-trend-row {
                grid-template-columns: minmax(0, 1fr);
                grid-template-areas:
                    "trend"
                    "activity"
                    "messages"
                    "goals";
            }
        }
        #page-dashboard .pm-trend-chart-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 14px 16px;
        }
        #page-dashboard .nh-pm-trend-chart-wrap {
            height: 160px;
        }
        #page-dashboard .nh-dashboard-messages-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 14px 16px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        #page-dashboard .nh-dashboard-messages-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        #page-dashboard .nh-dashboard-messages-title { margin: 0; color: #20293a; font-size: .85rem; font-weight: 700; }
        #page-dashboard .nh-dashboard-messages-subtitle { margin: 3px 0 0; color: #8993a4; font-size: .68rem; }
        #page-dashboard .nh-dashboard-messages-open {
            flex: 0 0 auto;
            border: 0;
            background: transparent;
            color: #16876f;
            cursor: pointer;
            font-size: .65rem;
            font-weight: 700;
        }
        #page-dashboard .nh-dashboard-messages-list { display: grid; gap: 6px; }
        #page-dashboard .nh-dashboard-message {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            min-width: 0;
            padding: 7px 8px;
            border: 1px solid #edf0f3;
            border-radius: 9px;
            background: #fff;
            color: inherit;
            text-align: left;
            cursor: pointer;
        }
        #page-dashboard .nh-dashboard-message:hover { background: #f8fbf9; border-color: #cfe5d5; }
        #page-dashboard .nh-dashboard-message-icon {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #16876f;
        }
        #page-dashboard .nh-dashboard-message-info { display: flex; flex: 1; flex-direction: column; min-width: 0; }
        #page-dashboard .nh-dashboard-message-sender { overflow: hidden; color: #30394a; font-size: .72rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        #page-dashboard .nh-dashboard-message-preview { overflow: hidden; color: #788397; font-size: .66rem; text-overflow: ellipsis; white-space: nowrap; }
        #page-dashboard .nh-dashboard-message-new { flex: 0 0 auto; color: #16876f; font-size: .6rem; font-weight: 700; }
        #page-dashboard .nh-nurse-head-decision-card { margin-top: 16px; }
        #page-dashboard .pm-goal-card {
            min-height: 150px;
            display: flex;
            flex-direction: column;
        }
        #page-dashboard .pm-goal-list {
            flex: 1;
            justify-content: space-between;
        }
        #page-dashboard .pm-activity-card,
        #page-dashboard .pm-goal-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 14px 16px;
        }

        /* ══ THREE-COL ANALYTICS CHART CARDS — enhanced ══ */
        #page-dashboard .an-chart-grid.three-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 4px;
            align-items: stretch;
        }
        @media (max-width: 1120px) {
            #page-dashboard .an-chart-grid.three-col { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 720px) {
            #page-dashboard .an-chart-grid.three-col { grid-template-columns: 1fr; }
        }
        #page-dashboard .an-chart-grid.three-col .an-chart-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 360px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        #page-dashboard .an-chart-grid.three-col .an-chart-card:hover {
            box-shadow: 0 8px 28px rgba(0,0,0,0.10);
            transform: translateY(-2px);
        }
        /* Accent top bar per card */
        #page-dashboard .an-chart-card-accent {
            height: 4px;
            border-radius: 16px 16px 0 0;
            flex-shrink: 0;
        }
        #page-dashboard .an-chart-card-accent.teal   { background: linear-gradient(90deg,#0d9488,#2dd4bf); }
        #page-dashboard .an-chart-card-accent.indigo { background: linear-gradient(90deg,#4f46e5,#818cf8); }
        #page-dashboard .an-chart-card-accent.green  { background: linear-gradient(90deg,#16a34a,#4ade80); }
        /* Card body */
        #page-dashboard .an-chart-card-body {
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        /* Header inside card */
        #page-dashboard .an-chart-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        #page-dashboard .an-chart-card-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        #page-dashboard .an-chart-card-icon.teal   { background: #ccfbf1; color: #0d9488; }
        #page-dashboard .an-chart-card-icon.indigo { background: #ede9fe; color: #6d28d9; }
        #page-dashboard .an-chart-card-icon.green  { background: #dcfce7; color: #16a34a; }
        #page-dashboard .an-chart-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #111827;
        }
        #page-dashboard .an-chart-subtitle {
            font-size: 0.72rem;
            color: #9ca3af;
            margin-top: 2px;
        }
        #page-dashboard .an-chart-grid.three-col .an-canvas-wrap {
            flex: 1;
            position: relative;
            min-height: 230px;
            height: 230px;
        }
        #page-dashboard .an-chart-grid.three-col .an-canvas-wrap canvas {
            position: absolute; top: 0; left: 0;
            width: 100% !important; height: 100% !important;
        }
        #page-dashboard .an-progress-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        #page-dashboard .an-progress-item-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #374151;
            font-weight: 600;
        }
        #page-dashboard .an-progress-item-label span:last-child {
            font-weight: 700;
            color: #111827;
        }
        #page-dashboard .an-progress-bar-bg {
            height: 8px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
        }
        #page-dashboard .an-progress-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.8s cubic-bezier(.4,0,.2,1);
        }
    </style>
</head>
<body class="student-dashboard dashboard-card-theme" data-clinic-role="nurse_head">

<div class="uh-layout">

    <!-- ── Sidebar ─────────────────────────────────── -->
    <x-clinic.sidebar role="nurse_head">
        <button class="uh-nav-item active" onclick="uhShowPage('dashboard',this)" data-title="Dashboard">
            <i class="fa fa-gauge-high"></i><span>Dashboard</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('patients',this)" data-title="Patient Information">
            <i class="fa fa-users"></i><span>Patient Information</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('medical-records',this)" data-title="Medical and Dental Records">
            <i class="fa fa-file-medical"></i><span>Medical and Dental Records</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('vitals',this)" data-title="Vital Signs Records">
            <i class="fa fa-heart-pulse"></i><span>Vital Signs Records</span>
        </button>
        <button class="uh-nav-item" onclick="nSetVitalsTreatmentMode(false);uhShowPage('record-vitals',this)" data-title="Record Vital Signs">
            <i class="fa fa-clipboard-list"></i><span>Record Vital Signs</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('appointments',this)" data-title="Appointments">
            <i class="fa fa-calendar-check"></i><span>Appointments</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('calendar',this);clinicCalendarLoad().catch(function(){});" data-title="Appointments Calendar">
            <i class="fa fa-calendar-days"></i><span>Appointments Calendar</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('availability-calendar',this);nOpenAvailabilityManager().catch(function(error){showToast(error.message||'Could not load provider availability.','error');});" data-title="Provider Availability">
            <i class="fa fa-calendar-plus"></i><span>Provider Availability</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('appointment-history',this);nhRenderAppointmentHistory();" data-title="Appointment History">
            <i class="fa fa-clock-rotate-left"></i><span>Appointment History</span>
        </button>
        <button class="uh-nav-item" onclick="cwOpenConsultations()" data-title="Consultation Requests">
            <i class="fa fa-comment-medical"></i><span>Consultation Requests</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('referrals',this)" data-title="Referrals">
            <i class="fa fa-share-from-square"></i><span>Referrals</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('forms',this)" data-title="Generate Forms">
            <i class="fa fa-file-medical"></i><span>Generate Forms</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('staff',this)" data-title="Manage Staff">
            <i class="fa fa-user-tie"></i><span>Manage Staff</span>
        </button>
        <button class="uh-nav-item" onclick="uhShowPage('messages',this);nMessagesInit();" data-title="Messages">
            <i class="fa fa-comments"></i><span>Messages</span>
        </button>
    </x-clinic.sidebar>

    <!-- ── Main ────────────────────────────────────── -->
    <div class="uh-main">

        <x-clinic.topbar />

        <div class="uh-content">

            <!-- ── PAGE: DASHBOARD ─────────────────── -->
            <div class="uh-page active" id="page-dashboard">

                <!-- Campus Filter Tabs -->
                <div class="uh-campus-tabs">
                    <button class="uh-campus-tab active" onclick="nhFilterCampus('all',this)">All Campuses</button>
                    <button class="uh-campus-tab" onclick="nhFilterCampus(2,this)">Main Campus</button>
                    <button class="uh-campus-tab" onclick="nhFilterCampus(1,this)">Old Sagay Campus</button>
                    <button class="uh-campus-tab" onclick="nhFilterCampus(4,this)">Escalante Campus</button>
                    <button class="uh-campus-tab" onclick="nhFilterCampus(3,this)">Laga-an Campus</button>
                </div>


                <!-- ── Analytics section (inline on dashboard) ── -->
                <div class="uh-analytics" style="margin-top:0">


                    <!-- ══ Performance Metric Cards ══ -->
                    <div class="pm-metric-row">
                        <div class="pm-metric-card">
                            <div class="pm-metric-left">
                                <div class="pm-metric-label">Total Patients</div>
                                <div class="pm-metric-value" id="nh-pm-patients">—</div>
                                <div class="pm-metric-delta up" id="nh-pm-patients-delta"><i class="fa fa-arrow-trend-up"></i> Active records</div>
                            </div>
                        </div>
                        <div class="pm-metric-card">
                            <div class="pm-metric-left">
                                <div class="pm-metric-label">Today's Visits</div>
                                <div class="pm-metric-value" id="nh-pm-visits">—</div>
                                <div class="pm-metric-delta" id="nh-pm-visits-delta"><i class="fa fa-clock"></i> Live count</div>
                            </div>
                        </div>
                        <div class="pm-metric-card">
                            <div class="pm-metric-left">
                                <div class="pm-metric-label">Visits This Month</div>
                                <div class="pm-metric-value" id="nh-pm-month-visits">—</div>
                                <div class="pm-metric-delta"><i class="fa fa-calendar"></i> Current month</div>
                            </div>
                        </div>
                        <div class="pm-metric-card">
                            <div class="pm-metric-left">
                                <div class="pm-metric-label">Appointments</div>
                                <div class="pm-metric-value" id="nh-pm-appts">—</div>
                                <div class="pm-metric-delta" id="nh-pm-appts-delta"><i class="fa fa-calendar-check"></i> Today</div>
                            </div>
                        </div>
                        <div class="pm-metric-card">
                            <div class="pm-metric-left">
                                <div class="pm-metric-label">Pending Referrals</div>
                                <div class="pm-metric-value" id="nh-pm-refs">—</div>
                                <div class="pm-metric-delta down" id="nh-pm-refs-delta"><i class="fa fa-triangle-exclamation"></i> Action needed</div>
                            </div>
                        </div>
                    </div>

                    <!-- ══ Trend Chart + Activity Feed + Goal Progress ══ -->
                    <div class="pm-trend-row">
                        <div class="nh-pm-trend-main-col">
                            <div class="pm-trend-chart-card">
                                <div class="pm-trend-header">
                                    <div>
                                        <div class="pm-trend-title">Visit Trends &amp; Forecasting</div>
                                        <div class="pm-trend-sub">Daily visits — actual vs 7-day moving average</div>
                                    </div>
                                    <div class="pm-trend-legend">
                                        <span><span class="pm-legend-dot" style="background:#2563eb"></span> Actual</span>
                                        <span><span class="pm-legend-dot" style="background:#16a34a;opacity:.6"></span> Avg</span>
                                    </div>
                                </div>
                                <div class="nh-pm-trend-chart-wrap" style="position:relative;">
                                    <canvas id="nh-pm-trend-chart"></canvas>
                                </div>
                            </div>
                            <section class="nh-dashboard-messages-card" aria-labelledby="nh-dashboard-messages-title">
                                <div class="nh-dashboard-messages-header">
                                    <div>
                                        <h3 class="nh-dashboard-messages-title" id="nh-dashboard-messages-title">Messages</h3>
                                        <p class="nh-dashboard-messages-subtitle">Who messaged you and your new messages</p>
                                    </div>
                                    <button type="button" class="nh-dashboard-messages-open" onclick="nhOpenMessages()">Open Messages <i class="fa fa-arrow-right"></i></button>
                                </div>
                                <div class="nh-dashboard-messages-list" id="nh-dashboard-messages" aria-live="polite">
                                    <p class="pm-activity-text">Loading messages...</p>
                                </div>
                            </section>
                        </div>

                        <div class="pm-side-col nh-clinic-side-col">
                            <!-- Activity Feed -->
                            <div class="pm-activity-card">
                                <div class="pm-activity-header">
                                    <span class="pm-activity-title">Recent Activity</span>
                                    <span class="pm-live-dot-wrap"><span class="pm-live-dot"></span> Live</span>
                                </div>
                                <div class="pm-activity-list" id="nh-pm-activity">
                                    <div class="pm-activity-item">
                                        <span class="pm-activity-dot blue"></span>
                                        <div><div class="pm-activity-text">Dashboard loaded</div><div class="pm-activity-time">Just now</div></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Goal Progress -->
                            <div class="pm-goal-card">
                                <div class="pm-goal-title">Clinic Performance Goals</div>
                                <div class="pm-goal-list" id="nh-pm-goals">
                                    <div class="pm-goal-item">
                                        <div class="pm-goal-label"><span>Daily Visit Target</span><span id="nh-pm-goal-daily-pct">—%</span></div>
                                        <div class="pm-goal-bar-bg"><div class="pm-goal-bar-fill blue" id="nh-pm-goal-daily-bar" style="width:0%"></div></div>
                                    </div>
                                    <div class="pm-goal-item">
                                        <div class="pm-goal-label"><span>Appointment Completion</span><span id="nh-pm-goal-appt-pct">—%</span></div>
                                        <div class="pm-goal-bar-bg"><div class="pm-goal-bar-fill green" id="nh-pm-goal-appt-bar" style="width:0%"></div></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="an-chart-grid three-col">

                        <!-- Department Distribution -->
                        <div class="an-chart-card">
                            <div class="an-chart-card-accent teal"></div>
                            <div class="an-chart-card-body">
                                <div class="an-chart-card-head">
                                    <div>
                                        <div class="an-chart-title">Department Distribution</div>
                                        <div class="an-chart-subtitle">Patients by college / dept</div>
                                    </div>
                                    <div class="an-chart-card-icon teal">
                                        <i class="fa fa-building-columns"></i>
                                    </div>
                                </div>
                                <div class="an-canvas-wrap">
                                    <canvas id="nh-chart-dept"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Chief Complaints -->
                        <div class="an-chart-card">
                            <div class="an-chart-card-accent indigo"></div>
                            <div class="an-chart-card-body">
                                <div class="an-chart-card-head">
                                    <div>
                                        <div class="an-chart-title">Chief Complaints</div>
                                        <div class="an-chart-subtitle">Top 6 student complaints by campus</div>
                                    </div>
                                    <div class="an-chart-card-icon indigo">
                                        <i class="fa fa-stethoscope"></i>
                                    </div>
                                </div>
                                <div class="an-canvas-wrap">
                                    <canvas id="nh-chart-complaints"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>

                    <section class="nh-pm-decision-card nh-nurse-head-decision-card" aria-labelledby="nh-decision-support-title">
                        <div class="nh-ds-header">
                            <div>
                                <div class="nh-ds-title" id="nh-decision-support-title">
                                    <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                    AI Decision Support
                                </div>
                                <div class="nh-ds-subtitle">Evidence-backed operational signals · select an insight for details</div>
                            </div>
                            <div class="nh-ds-header-actions">
                                <button type="button" class="nh-ds-analyze-button" data-ai-analysis-button disabled>
                                    <i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i>
                                    <span class="nh-ds-analyze-label">Analyze with AI</span>
                                </button>
                                <span class="nh-ds-badge">LIVE RECORDS</span>
                            </div>
                        </div>
                        <div class="nh-ds-insights" id="nh-decision-support-insights" aria-live="polite">
                            <div class="nh-ds-state">Loading insights from the selected campus data…</div>
                        </div>
                        <div class="nh-ds-disclaimer">Insights use aggregate clinic report data and are operational guidance only, not a diagnosis or treatment recommendation.</div>
                    </section>

                </div><!-- /.uh-analytics -->
            </div>

            <!-- ── PAGE: CONSULTATION REQUESTS ──────── -->
            <div class="uh-page" id="page-consultations">
                <div class="cw-card">
                    <div id="cw-nurse-queue-header" class="uh-card-header cw-queue-header">
                        <div>
                            <h2>Campus consultation requests</h2>
                            <p class="cw-muted">Search student requests across campuses. Consultation details are read-only; assigned nurses handle decisions and follow-up.</p>
                        </div>
                        <button id="cw-refresh-queue" class="uh-btn uh-btn-outline uh-btn-sm" type="button">Refresh</button>
                    </div>
                    <div id="cw-queue-filters" class="cw-queue-filters">
                        <label class="cw-queue-campus-field">
                            <span>Filter by campus</span>
                            <select id="cw-queue-campus" aria-label="Filter consultation requests by campus">
                                <option value="">Loading campuses...</option>
                            </select>
                        </label>
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

            @include('clinic.partials.staff-calendar')

            <!-- ── PAGE: APPOINTMENTS ─────────────── -->
            <div class="uh-page" id="page-appointments">
                <div class="uh-grid-4 uh-mb-16 nh-appt-stats">
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon blue"><i class="fa fa-calendar"></i></div>
                        <div class="uh-stat-number" id="nh-appt-stat-total">—</div>
                        <div class="uh-stat-label">Total</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon amber"><i class="fa fa-hourglass-half"></i></div>
                        <div class="uh-stat-number" id="nh-appt-stat-waiting">—</div>
                        <div class="uh-stat-label">Waiting</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon amber"><i class="fa fa-calendar-plus"></i></div>
                        <div class="uh-stat-number" id="nh-appt-stat-accepted">—</div>
                        <div class="uh-stat-label">Accepted — Awaiting Care</div>
                    </div>
                    <div class="uh-stat-card">
                        <div class="uh-stat-icon blue"><i class="fa fa-calendar-check"></i></div>
                        <div class="uh-stat-number" id="nh-appt-stat-checkup-scheduled">—</div>
                        <div class="uh-stat-label">Scheduled Check-up</div>
                    </div>
                </div>
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title"><i class="fa fa-users" aria-hidden="true"></i> Patient Queue</div>
                        <div class="nh-table-header-controls">
                            <label class="nh-campus-filter-label">Campus
                                <select id="nh-appt-campus-filter" class="nh-campus-filter"
                                    aria-label="Filter appointments by campus"
                                    onchange="nhApptCampusChanged(this.value)">
                                    <option value="">All Campuses</option>
                                </select>
                            </label>
                            <label class="nh-campus-filter-label">Status
                                <select id="nh-appt-status-filter" class="nh-appt-view-filter"
                                    aria-label="Filter appointments by status"
                                    onchange="nhApptStatusChanged(this.value)">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Waiting</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="checkup_scheduled">Check-up scheduled</option>
                                </select>
                            </label>
                            <label class="nh-campus-filter-label">Priority
                                <select id="nh-appt-priority-filter" class="nh-appt-view-filter"
                                    aria-label="Filter appointments by priority"
                                    onchange="nhApptPriorityChanged(this.value)">
                                    <option value="">All Priorities</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </label>
                            <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs"
                                onclick="nhRenderAppointments()" aria-label="Refresh patient queue">
                                <i class="fa fa-refresh" aria-hidden="true"></i> Refresh
                            </button>
                            <button type="button" class="uh-btn uh-btn-ghost uh-btn-xs" id="nh-appt-sort-btn"
                                onclick="nhToggleAppointmentTimeSort()" aria-label="Sort appointments: oldest first">
                                <i class="fa fa-arrow-down-short-wide" aria-hidden="true"></i>
                                <span id="nh-appt-sort-indicator">Oldest</span>
                            </button>
                        </div>
                    </div>
                    <div class="nh-appt-queue-toolbar">
                        <div class="uh-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="nh-appt-search"
                                placeholder="Search patient name or ID..."
                                aria-label="Search patient queue"
                                oninput="nhApptSearchDebounce(this.value)"/>
                        </div>
                        <span class="uh-pg-info" id="nh-appt-count" aria-live="polite">Loading appointments...</span>
                    </div>
                    <div class="nurse-current-queue-header" aria-label="Appointment queue columns">
                        <span aria-hidden="true"></span>
                        <span data-column="patient">Patient</span>
                        <span data-column="details">Appointment details</span>
                        <span data-column="priority">Priority / case confidence</span>
                        <span data-column="status">Status</span>
                        <span data-column="actions">Actions</span>
                    </div>
                    <div class="nurse-current-queue-list" id="nh-appt-body" aria-live="polite">
                        <p class="student-empty-state">Loading appointments...</p>
                    </div>
                    <div id="nh-appt-pagination"
                        style="display:none;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;">
                        <span class="uh-pg-info" id="nh-appt-info"></span>
                        <div style="display:flex;gap:4px;" id="nh-appt-controls"></div>
                    </div>
                </div>
            </div>

            @include('clinic.partials.appointment-scheduler')
            @include('clinic.partials.availability-manager', ['showCampusSelector' => true])

            <!-- ── PAGE: APPOINTMENT HISTORY ──────── -->
            <div class="uh-page" id="page-appointment-history">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Appointment History</div>
                        <div class="nh-table-header-controls">
                            <label class="nh-campus-filter-label">Status
                                <select id="nh-appt-history-status" class="nh-appt-view-filter"
                                    aria-label="Filter appointment history by status"
                                    onchange="nhApptHistoryStatusChanged(this.value)">
                                    <option value="">Completed, Cancelled, and Deleted</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="deleted">Deleted by student</option>
                                </select>
                            </label>
                            <label class="nh-campus-filter-label">Campus
                                <select id="nh-appt-history-campus-filter" class="nh-campus-filter"
                                    aria-label="Filter appointment history by campus"
                                    onchange="nhApptHistoryCampusChanged(this.value)">
                                    <option value="">All Campuses</option>
                                </select>
                            </label>
                            <label class="nh-campus-filter-label">Priority
                                <select id="nh-appt-history-priority-filter" class="nh-appt-view-filter"
                                    aria-label="Filter appointment history by priority"
                                    onchange="nhApptHistoryPriorityChanged(this.value)">
                                    <option value="">All Priorities</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </label>
                            <div class="uh-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="nh-appt-history-search"
                                    placeholder="Search by name or ID..."
                                    oninput="nhApptHistorySearchDebounce(this.value)"/>
                            </div>
                        </div>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table">
                            <thead>
                                <tr>
                                    <th><button type="button" class="uh-table-sort" onclick="nhToggleAppointmentHistoryTimeSort()" aria-label="Sort appointment history by date and time">Date <span id="nh-appt-history-sort-indicator">↓</span></button></th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Decline reason</th>
                                </tr>
                            </thead>
                            <tbody id="nh-appt-history-body">
                                <tr><td colspan="6" class="no-data">Loading appointment history...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="nh-appt-history-pagination"
                        style="display:none;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;">
                        <span class="uh-pg-info" id="nh-appt-history-info"></span>
                        <div style="display:flex;gap:4px;" id="nh-appt-history-controls"></div>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: PATIENTS ─────────────────── -->
            <div class="uh-page" id="page-patients">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Patient Information</div>
                        <div class="nh-table-header-controls">
                            <label class="nh-campus-filter-label">Campus
                                <select id="nh-patient-campus-filter" class="nh-campus-filter"
                                    aria-label="Filter patients by campus"
                                    onchange="nhPatientCampusChanged(this.value)">
                                    <option value="">All Campuses</option>
                                </select>
                            </label>
                            <div class="uh-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="nh-patient-search"
                                    placeholder="Search by name or ID..."
                                    oninput="nhPatientSearchDebounce(this.value)"/>
                            </div>
                        </div>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Age</th>
                                    <th>Campus</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="nh-patients-body">
                                <tr><td colspan="6" class="no-data">Loading patients...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div id="nh-patients-pagination"
                        style="display:none;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;">
                        <span class="uh-pg-info" id="nh-patients-info"></span>
                        <div style="display:flex;gap:4px;" id="nh-patients-controls"></div>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: MEDICAL RECORDS ──────────── -->
            <x-clinic.medical-record-entry />

            <!-- ── PAGE: VITAL SIGNS ───────────────── -->
            <div class="uh-page" id="page-vitals">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Vital Signs Records</div>
                        <div class="nh-table-header-controls">
                            <label class="nh-campus-filter-label">Campus
                                <select id="nh-vital-campus-filter" class="nh-campus-filter"
                                    aria-label="Filter vital-sign records by campus"
                                    onchange="nhVitalCampusChanged(this.value)">
                                    <option value="">All Campuses</option>
                                </select>
                            </label>
                            <div class="uh-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="nh-vital-search"
                                    placeholder="Search by name or ID..."
                                    oninput="nhVitalSearchDebounce(this.value)"/>
                            </div>
                        </div>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table">
                            <thead>
                                <tr><th>Date</th><th>Patient</th><th>BP</th><th>Temp</th><th>HR</th><th>SpO2</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="nh-vitals-body">
                                <tr><td colspan="7" class="no-data">Loading vital signs...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="nh-vital-pagination"
                        style="display:none;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;">
                        <span class="uh-pg-info" id="nh-vital-info"></span>
                        <div style="display:flex;gap:4px;" id="nh-vital-controls"></div>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: RECORD VITAL SIGNS ────────── -->
            <div class="uh-page" id="page-record-vitals">
                @include('clinic.partials.vitals-recording-form')
            </div>

            <!-- ── PAGE: REFERRALS ────────────────── -->
            <div class="uh-page" id="page-referrals">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Referrals</div>
                        <div class="nh-table-header-controls">
                            <label class="nh-campus-filter-label">Campus
                                <select id="nh-ref-campus-filter" class="nh-campus-filter"
                                    aria-label="Filter referrals by campus"
                                    onchange="nhRefCampusChanged(this.value)">
                                    <option value="">All Campuses</option>
                                </select>
                            </label>
                            <label class="nh-campus-filter-label">Status
                                <select id="nh-ref-status-filter" class="nh-appt-view-filter"
                                    aria-label="Filter referrals by status"
                                    onchange="nhRefStatusChanged(this.value)">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="waiting_to_schedule">Waiting to schedule</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </label>
                            <div class="uh-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="nh-ref-search"
                                    placeholder="Search by name or ID..."
                                    oninput="nhRefSearchDebounce(this.value)"/>
                            </div>
                            <button class="uh-btn uh-btn-primary uh-btn-sm"><i class="fa fa-plus"></i> New Referral</button>
                        </div>
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table">
                            <thead>
                                <tr><th>ID</th><th>Patient</th><th>From</th><th>Referred To</th><th>Reason</th><th><button type="button" class="uh-table-sort" onclick="nhToggleReferralTimeSort()" aria-label="Sort referrals by date and time">Date / time <span id="nh-referrals-sort-indicator">↓</span></button></th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="nh-referrals-body">
                                <tr><td colspan="8" class="no-data">Loading referrals...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="nh-ref-pagination"
                        style="display:none;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #e5e7eb;">
                        <span class="uh-pg-info" id="nh-ref-info"></span>
                        <div style="display:flex;gap:4px;" id="nh-ref-controls"></div>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: DOCTORS ──────────────────── -->
            <div class="uh-page" id="page-doctors">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Doctors &amp; Dentists</div>
                    </div>
                    <div class="uh-table-toolbar">
                        <input type="search" data-uh-table-search placeholder="Search doctors and dentists..." aria-label="Search doctors and dentists">
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable>
                            <thead>
                                <tr><th>Name</th><th>Specialty</th><th>Campus</th><th>License</th><th>Status</th></tr>
                            </thead>
                            <tbody id="nh-doctors-body">
                                <tr><td colspan="5" class="no-data">Loading doctors and dentists...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: MANAGE STAFF ─────────────── -->
            <div class="uh-page" id="page-staff">
                <div class="uh-card">
                    <div class="uh-card-header">
                        <div class="uh-card-title">Manage Staff</div>
                        <button class="uh-btn uh-btn-primary" onclick="openAddStaffModal()">
                            <i class="fa fa-user-plus"></i> Add Staff
                        </button>
                    </div>
                    <div class="uh-table-toolbar">
                        <input type="search" data-uh-table-search placeholder="Search staff..." aria-label="Search staff">
                    </div>
                    <div class="uh-table-wrap">
                        <table class="uh-table" data-uh-searchable>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>License No.</th>
                                    <th>Campus</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staffTableBody">
                                <tr><td colspan="7" class="no-data">Loading staff…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── PAGE: ALL RECORDS (removed) ──────────────── -->

            {{-- Generate Forms — tile grid + 10 document pages (shared with nurse view) --}}
            @include('clinic.partials.generate-forms')

            <!-- ── PAGE: MESSAGES ─────────────────── -->
            <div class="uh-page" id="page-messages">
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

<!-- Edit Medical Record modal -->
<div id="nh-med-pf-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="nh-med-pf-title">Edit Medical Record</div>
            <button type="button" class="uh-modal-close" onclick="nhCloseMedEditForm()" aria-label="Close">&times;</button>
        </div>
        <form id="nh-med-pf-form" onsubmit="event.preventDefault(); nhSaveMedRecord();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label for="nh-med-pf-visit_date">Date</label>
                    <input type="date" id="nh-med-pf-visit_date" name="visit_date" max="{{ today()->toDateString() }}"/>
                </div>
                <div class="uh-form-group">
                    <label for="nh-med-pf-visit_time">Time</label>
                    <input type="time" id="nh-med-pf-visit_time" name="visit_time"/>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label for="nh-med-pf-reason">Chief Complaint</label>
                    <input type="text" id="nh-med-pf-reason" name="reason" maxlength="255"/>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label>Vital Signs</label>
                </div>
                <div class="uh-form-group">
                    <label for="nh-med-pf-bp">BP</label>
                    <input type="text" id="nh-med-pf-bp" name="bp" maxlength="20" placeholder="120/80"/>
                </div>
                <div class="uh-form-group">
                    <label for="nh-med-pf-temp">Temp. (°C)</label>
                    <input type="number" id="nh-med-pf-temp" name="temp" step="0.1"/>
                </div>
                <div class="uh-form-group">
                    <label for="nh-med-pf-pulse">PR (bpm)</label>
                    <input type="number" id="nh-med-pf-pulse" name="pulse" min="0" step="1"/>
                </div>
                <div class="uh-form-group">
                    <label for="nh-med-pf-rr">RR (breaths/min)</label>
                    <input type="number" id="nh-med-pf-rr" name="rr" min="0" step="1"/>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label for="nh-med-pf-treatment">Treatment</label>
                    <textarea id="nh-med-pf-treatment" name="treatment" rows="3"></textarea>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label for="nh-med-pf-notes">Nurse’s Notes / Remarks</label>
                    <textarea id="nh-med-pf-notes" name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="nhCloseMedEditForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Vital Signs modal -->
<div id="nh-vital-pf-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="nh-vital-pf-title">Edit Vital Signs Record</div>
            <button type="button" class="uh-modal-close" onclick="nhCloseVitalEditForm()" aria-label="Close">&times;</button>
        </div>
        <form id="nh-vital-pf-form" onsubmit="event.preventDefault(); nhSaveVitalRecord();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Visit Date</label>
                    <input type="date" id="nh-vital-pf-visit_date" name="visit_date" max="{{ today()->toDateString() }}"/>
                </div>
                <div class="uh-form-group">
                    <label>Visit Time</label>
                    <input type="time" id="nh-vital-pf-visit_time" name="visit_time"/>
                </div>
                <div class="uh-form-group">
                    <label>BP</label>
                    <input type="text" id="nh-vital-pf-bp" name="bp" maxlength="20" placeholder="120/80"/>
                </div>
                <div class="uh-form-group">
                    <label>Temp (°C)</label>
                    <input type="number" id="nh-vital-pf-temp" name="temp" step="0.1"/>
                </div>
                <div class="uh-form-group">
                    <label>Pulse / HR (bpm)</label>
                    <input type="number" id="nh-vital-pf-pulse" name="pulse" min="0"/>
                </div>
                <div class="uh-form-group">
                    <label>RR</label>
                    <input type="number" id="nh-vital-pf-rr" name="rr" min="0"/>
                </div>
                <div class="uh-form-group">
                    <label>SpO₂ (%)</label>
                    <input type="number" id="nh-vital-pf-o2_sat" name="o2_sat" min="0" max="100"/>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="nhCloseVitalEditForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Patient modal -->
<div id="nh-pf-modal" class="uh-modal-overlay hidden">
    <div class="uh-modal uh-modal-lg">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="nh-pf-title">Edit Patient</div>
            <button type="button" class="uh-modal-close" onclick="nhCloseEditForm()" aria-label="Close">&times;</button>
        </div>
        <form id="nh-pf-form" onsubmit="event.preventDefault(); nhSaveStudent();">
            <div class="uh-grid uh-grid-2 uh-gap-12">
                <div class="uh-form-group">
                    <label>Student ID <span class="uh-text-danger">*</span></label>
                    <input type="text" id="nh-pf-student_id" name="student_id" maxlength="30" disabled/>
                </div>
                <div class="uh-form-group">
                    <label>Email <span class="uh-text-danger">*</span></label>
                    <input type="email" id="nh-pf-email" name="email" maxlength="150" required/>
                </div>
                <div class="uh-form-group">
                    <label>First Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="nh-pf-first_name" name="first_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Last Name <span class="uh-text-danger">*</span></label>
                    <input type="text" id="nh-pf-last_name" name="last_name" maxlength="80" required/>
                </div>
                <div class="uh-form-group">
                    <label>Middle Name</label>
                    <input type="text" id="nh-pf-middle_name" name="middle_name" maxlength="80"/>
                </div>
                <div class="uh-form-group">
                    <label>Password</label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="nh-pf-pw" name="password" minlength="6" maxlength="128" autocomplete="new-password" placeholder="Leave blank to keep current"/>
                        <button type="button" class="uh-pw-toggle" data-target="nh-pf-pw" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted">Leave blank to keep current password.</small>
                </div>
                <div class="uh-form-group" id="nh-pf-pw-confirm-group">
                    <label>Confirm Password</label>
                    <div class="uh-pw-wrap">
                        <input type="password" id="nh-pf-pw-confirm" name="password_confirmation" minlength="6" maxlength="128" autocomplete="new-password"/>
                        <button type="button" class="uh-pw-toggle" data-target="nh-pf-pw-confirm" aria-label="Show password" title="Show password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <small class="uh-text-muted">Only needed when setting a new password.</small>
                </div>
                <div class="uh-form-group">
                    <label>Course / Year</label>
                    <input type="text" id="nh-pf-course_year" name="course_year" maxlength="100"/>
                </div>
                <div class="uh-form-group">
                    <label>Course</label>
                    <input type="text" id="nh-pf-department" name="department" maxlength="120"/>
                </div>
                <div class="uh-form-group">
                    <label>Section</label>
                    <input type="text" id="nh-pf-section" name="section" maxlength="30"/>
                </div>
                <div class="uh-form-group">
                    <label>Contact</label>
                    <input type="text" id="nh-pf-contact" name="contact" maxlength="30"/>
                </div>
                <div class="uh-form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <input type="text" id="nh-pf-address" name="address" maxlength="255"/>
                </div>
                <div class="uh-form-group">
                    <label>Sex</label>
                    <select id="nh-pf-sex" name="sex">
                        <option value="">—</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>Civil Status</label>
                    <select id="nh-pf-civil_status" name="civil_status">
                        <option value="">—</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="uh-form-group">
                    <label>Age</label>
                    <input type="number" id="nh-pf-age" name="age" min="0" max="150"/>
                </div>
                <div class="uh-form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="nh-pf-date_of_birth" name="date_of_birth" max="{{ today()->toDateString() }}"/>
                </div>
                <div class="uh-form-group">
                    <label>Blood Type</label>
                    <select id="nh-pf-blood_type" name="blood_type">
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
                    <select id="nh-pf-campus_id" name="campus_id">
                        <option value="">— None —</option>
                    </select>
                </div>
            </div>
            <div class="uh-modal-footer">
                <button type="button" class="uh-btn uh-btn-ghost" onclick="nhCloseEditForm()">Cancel</button>
                <button type="submit" class="uh-btn uh-btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Staff modal -->
<div class="modal-overlay hidden" id="addStaffModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa fa-user-plus" style="color:#2a7a6f;margin-right:8px"></i>
                Add Staff Member
            </span>
            <button type="button" class="modal-close" onclick="nhCloseModal('addStaffModal')">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <form id="addStaffForm" onsubmit="addStaff(event)">
            <div class="mfg">
                <label>Employee ID <span class="req">*</span></label>
                <input type="text" id="st_employee_id" placeholder="e.g. STF-002" required maxlength="30"/>
            </div>
            <div class="mfg">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" id="st_name" placeholder="Last, First Middle" required/>
            </div>
            <div class="mfg">
                <label>Email <span class="req">*</span></label>
                <input type="email" id="st_email" placeholder="name@uni.edu" required/>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Password <span class="req">*</span></label>
                    <input type="password" id="st_password" minlength="6" required/>
                </div>
                <div class="mfg">
                    <label>Confirm Password <span class="req">*</span></label>
                    <input type="password" id="st_password2" minlength="6" required/>
                </div>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Role <span class="req">*</span></label>
                    <select id="st_role" required>
                        <option value="">— Select role —</option>
                        <option value="nurse">Nurse</option>
                        <option value="physician">Physician</option>
                        <option value="dentist">Dentist</option>
                    </select>
                </div>
                <div class="mfg">
                    <label>License No.</label>
                    <input type="text" id="st_license" placeholder="e.g. RN-12345"/>
                </div>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Contact</label>
                    <input type="text" id="st_contact" placeholder="09xx-xxx-xxxx"/>
                </div>
                <div class="mfg">
                    <label>Campus</label>
                    <select id="st_campus_id">
                        <option value="">— All Campuses (system-wide access) —</option>
                    </select>
                    <small class="muted" style="display:block;margin-top:4px;font-size:0.75rem;">
                        Pick a specific campus to limit this staff member's access, or leave as "All Campuses" to grant system-wide access.
                    </small>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px">
                <button type="button" class="btn btn-outline" onclick="nhCloseModal('addStaffModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Staff
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff modal -->
<div class="modal-overlay hidden" id="editStaffModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-user-pen" style="color:#2a7a6f;margin-right:8px"></i>Edit Staff Member</span>
            <button type="button" class="modal-close" onclick="nhCloseModal('editStaffModal')" aria-label="Close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <form id="editStaffForm" onsubmit="nhSaveStaffEdit(event)">
            <div class="mfg">
                <label>Employee ID</label>
                <input type="text" id="nh-edit-employee-id" readonly aria-readonly="true"/>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>First Name <span class="req">*</span></label>
                    <input type="text" id="nh-edit-first-name" required maxlength="80"/>
                </div>
                <div class="mfg">
                    <label>Last Name <span class="req">*</span></label>
                    <input type="text" id="nh-edit-last-name" required maxlength="80"/>
                </div>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Middle Name</label>
                    <input type="text" id="nh-edit-middle-name" maxlength="80"/>
                </div>
                <div class="mfg">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" id="nh-edit-email" required maxlength="150"/>
                </div>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Role <span class="req">*</span></label>
                    <select id="nh-edit-role" required>
                        <option value="nurse">Nurse</option>
                        <option value="physician">Physician</option>
                        <option value="dentist">Dentist</option>
                    </select>
                </div>
                <div class="mfg">
                    <label>License No.</label>
                    <input type="text" id="nh-edit-license" maxlength="50"/>
                </div>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label>Contact</label>
                    <input type="text" id="nh-edit-contact" maxlength="30"/>
                </div>
                <div class="mfg">
                    <label>Campus</label>
                    <select id="nh-edit-campus-id">
                        <option value="">— All Campuses (system-wide access) —</option>
                    </select>
                </div>
            </div>
            <div class="mfg">
                <label>Specialization</label>
                <input type="text" id="nh-edit-specialization" maxlength="150"/>
            </div>
            <div class="mfg-row">
                <div class="mfg">
                    <label for="nh-edit-password">New Password</label>
                    <input type="password" id="nh-edit-password" minlength="6" maxlength="128" autocomplete="new-password"/>
                    <small>Leave blank to keep the current password.</small>
                </div>
                <div class="mfg">
                    <label for="nh-edit-password-confirmation">Confirm New Password</label>
                    <input type="password" id="nh-edit-password-confirmation" minlength="6" maxlength="128" autocomplete="new-password"/>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px">
                <button type="button" class="btn btn-outline" onclick="nhCloseModal('editStaffModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Campus modal (injected by JS, but template kept here for clarity) -->
<div class="modal-overlay hidden" id="assignCampusModal">
    <div class="modal-box" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa fa-building" style="color:#6366f1;margin-right:8px"></i>
                Assign Campus
            </span>
            <button type="button" class="modal-close" onclick="nhCloseModal('assignCampusModal')">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:12px">
            Staff member: <strong id="assign_emp_label">—</strong>
        </p>
        <div class="mfg">
            <label>Campus Assignment</label>
            <select id="assign_campus_select" style="width:100%">
                <option value="">— All Campuses (no restriction) —</option>
            </select>
        </div>
        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">
            Staff assigned to a specific campus will only see patients and data from that campus.
            Choose "All Campuses" to give system-wide access.
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <button type="button" class="btn btn-outline" onclick="nhCloseModal('assignCampusModal')">Cancel</button>
            <button class="btn btn-primary" onclick="confirmAssignCampus()">
                <i class="fa fa-save"></i> Save Assignment
            </button>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="uh-toasts"></div>

<!--
  ── Record detail modal ──
  Single modal reused for all three record types. The body is filled in
  by nhShowDetail() based on the type/id pair passed from the View
  button. "View full record" opens a print-friendly standalone page.
-->
<div class="modal-overlay hidden" id="nh-detail-modal">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <span class="modal-title" id="nh-detail-title">
                <i class="fa fa-file-medical" style="color:#2a7a6f;margin-right:8px"></i>
                Record Details
            </span>
            <button class="modal-close" onclick="nhCloseDetail()" aria-label="Close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="nh-detail-body">
            <p style="text-align:center;color:#6b7280;padding:20px 0">Loading…</p>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid #e5e7eb">
            <a id="nh-detail-full-link" class="uh-btn uh-btn-ghost" target="_blank" rel="noopener" style="display:none">
                <i class="fa fa-external-link-alt"></i> View full record
            </a>
            <button type="button" class="btn btn-primary" onclick="nhCloseDetail()">Close</button>
        </div>
    </div>
</div>

<script src="{{ asset('clinic/vendor/chart.umd.min.js') }}"></script>
<script src="{{ asset('clinic/js/unihealth.js') }}?v={{ filemtime(public_path('clinic/js/unihealth.js')) }}"></script>
<script src="{{ asset('clinic/js/staff-health-record.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/medical-record-entry.js') }}?v={{ filemtime(public_path('clinic/js/medical-record-entry.js')) }}"></script>
<script src="{{ asset('clinic/js/analytics.js') }}?v={{ filemtime(public_path('clinic/js/analytics.js')) }}"></script>
<script src="{{ asset('clinic/js/consultation-workflow.js') }}?v={{ filemtime(public_path('clinic/js/consultation-workflow.js')) }}"></script>
<script src="{{ asset('clinic/js/appointment-scheduling.js') }}?v={{ filemtime(public_path('clinic/js/appointment-scheduling.js')) }}"></script>
<script src="{{ asset('clinic/js/nurse_head.js') }}?v={{ filemtime(public_path('clinic/js/nurse_head.js')) }}"></script>

{{-- Generate Forms — shared form scripts (nurse.js provides handleFormSearch / autoFillForm) --}}
<script src="{{ asset('clinic/js/sunn-logo-b64.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/utils.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-medcert.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-dentalcert.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-prescription.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-referral.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-lab.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-xray.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-lactating.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/forms/nurse-form-breastfeeding.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/nurse-forms.js') }}?v={{ time() }}"></script>
<script src="{{ asset('clinic/js/nurse-typeahead.js') }}"></script>
<script src="{{ asset('clinic/js/nurse-fill-fix.js') }}"></script>
<script src="{{ asset('clinic/js/nurse.js') }}?v={{ filemtime(public_path('clinic/js/nurse.js')) }}"></script>
<script src="{{ asset('clinic/js/forms/medical-record-form.js') }}?v={{ filemtime(public_path('clinic/js/forms/medical-record-form.js')) }}"></script>
<script src="{{ asset('clinic/js/forms/dental-record-form.js') }}?v={{ filemtime(public_path('clinic/js/forms/dental-record-form.js')) }}"></script>
<script src="{{ asset('clinic/js/availability-manager.js') }}?v={{ filemtime(public_path('clinic/js/availability-manager.js')) }}"></script>
<script src="{{ asset('clinic/js/staff-calendar.js') }}?v={{ filemtime(public_path('clinic/js/staff-calendar.js')) }}"></script>
<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {

    nhLoadPatients();
    nhRenderMedicalRecords();
    nhRenderVitals();
    nhRenderReferrals();
    nLoadConversations();
    nhSetupPageObserver();
    // Analytics
    ClinicAnalytics.applyDefaults();
    ClinicAnalytics.loadAdminAnalytics('?campus_id=all', 'nh');
    ClinicAnalytics.loadPerfMetrics('nh', '?campus_id=all');

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
        var v = input.value;
        try { input.setSelectionRange(v.length, v.length); } catch (e) {}
    });
});

function nhFilterCampus(campus, el) {
    document.querySelectorAll('.uh-campus-tab').forEach(function (t) { t.classList.remove('active'); });
    el.classList.add('active');
    nhSetDashboardCampus(campus);
    var qs = (campus === 'all') ? '?campus_id=all' : '?campus_id=' + campus;
    ClinicAnalytics.loadAdminAnalytics(qs, 'nh');
    ClinicAnalytics.loadPerfMetrics('nh', qs);
    uhToast('Filtered to: ' + (campus === 'all' ? 'All Campuses' : el.textContent), 'info');
}

function nhAnReload(el) {
    nhSetDashboardCampus(nhSelectedCampusId || 'all');
    var qs = nhCampusQuery();
    ClinicAnalytics.loadAdminAnalytics(qs, 'nh');
    ClinicAnalytics.loadPerfMetrics('nh', qs);
}

// ── Patient Information — paginated + searchable ─────────────────────────
var _nhPatientPage   = 1;
var _nhPatientSearch = '';
var _nhPatientCampusId = '';
var _nhPatientTimer  = null;

function nhPatientCampusChanged(campusId) {
    _nhPatientCampusId = campusId;
    _nhPatientPage = 1;
    nhLoadPatients();
}

function nhPatientSearchDebounce(val) {
    clearTimeout(_nhPatientTimer);
    _nhPatientTimer = setTimeout(function () {
        _nhPatientSearch = val.trim();
        _nhPatientPage   = 1;
        nhLoadPatients();
    }, 350);
}

function nhPatientGoPage(p) {
    _nhPatientPage = p;
    nhLoadPatients();
}

function nhLoadPatients() {
    var tbody   = document.getElementById('nh-patients-body');
    var paginEl = document.getElementById('nh-patients-pagination');
    var infoEl  = document.getElementById('nh-patients-info');
    var ctrlEl  = document.getElementById('nh-patients-controls');
    if (!tbody) { return; }

    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading...</td></tr>';

    var qs = '/nurse-head/students?limit=10&page=' + _nhPatientPage;
    if (_nhPatientSearch) { qs += '&search=' + encodeURIComponent(_nhPatientSearch); }
    if (_nhPatientCampusId) { qs += '&campus_id=' + encodeURIComponent(_nhPatientCampusId); }

    uhGet(qs).then(function (data) {
        var students = Array.isArray(data) ? data : (data.students || data.data || []);
        var pg       = data.pagination || {};
        var total    = pg.total  || students.length;
        var pages    = pg.pages  || 1;
        var page     = pg.page   || _nhPatientPage;

        if (!students.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No patients found.</td></tr>';
            if (paginEl) { paginEl.style.display = 'none'; }
            return;
        }

        tbody.innerHTML = students.map(function (s) {
            var last   = s.last_name  || '';
            var first  = s.first_name || '';
            var name   = (last && first) ? (last + ', ' + first) : (s.name || last || first || '\u2014');
            var campus = (s.campus && s.campus.name) ? s.campus.name : '\u2014';
            var safeId = (s.student_id || '').replace(/[^A-Za-z0-9\-_]/g, '');
            var visit  = s.last_visit ? String(s.last_visit).substring(0, 10) : 'No visit recorded';
            return '<tr>' +
                '<td>' + nhEsc(name)   + '</td>' +
                '<td>' + nhEsc(s.student_id || s.id || '\u2014') + '</td>' +
                '<td>' + nhEsc(s.age != null ? s.age : '\u2014') + '</td>' +
                '<td>' + nhEsc(campus) + '</td>' +
                '<td>' + nhEsc(visit)  + '</td>' +
                '<td style="display:flex;gap:4px;flex-wrap:wrap;">' +
                    '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nhShowDetail(\'student\',\'' + safeId + '\')">View</button>' +
                    '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="uhViewStudentHealthRecord(\'' + safeId + '\')">Health record</button>' +
                    '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhOpenEditForm(\'' + safeId + '\')">Edit</button>' +
                    '<button class="uh-btn uh-btn-ghost uh-btn-xs" style="color:#dc2626;border-color:#dc2626;" onclick="nhDeleteStudent(\'' + safeId + '\')">Delete</button>' +
                '</td>' +
                '</tr>';
        }).join('');

        // Render pagination
        if (paginEl && infoEl && ctrlEl) {
            var from = (page - 1) * 10 + 1;
            var to   = Math.min(page * 10, total);
            infoEl.textContent = 'Showing ' + from + '\u2013' + to + ' of ' + total + ' patients';

            var b  = '';
            var s2 = Math.max(1, page - 2);
            var e2 = Math.min(pages, page + 2);

            // Prev
            b += '<button class="uh-pg-btn" onclick="nhPatientGoPage(' + (page - 1) + ')"' + (page <= 1 ? ' disabled' : '') + '>&lsaquo;</button>';
            // First page + ellipsis
            if (s2 > 1) {
                b += '<button class="uh-pg-btn" onclick="nhPatientGoPage(1)">1</button>';
                if (s2 > 2) { b += '<span class="uh-pg-ellipsis">&hellip;</span>'; }
            }
            // Page numbers
            for (var i = s2; i <= e2; i++) {
                b += '<button class="uh-pg-btn' + (i === page ? ' active' : '') + '" onclick="nhPatientGoPage(' + i + ')">' + i + '</button>';
            }
            // Last page + ellipsis
            if (e2 < pages) {
                if (e2 < pages - 1) { b += '<span class="uh-pg-ellipsis">&hellip;</span>'; }
                b += '<button class="uh-pg-btn" onclick="nhPatientGoPage(' + pages + ')">' + pages + '</button>';
            }
            // Next
            b += '<button class="uh-pg-btn" onclick="nhPatientGoPage(' + (page + 1) + ')"' + (page >= pages ? ' disabled' : '') + '>&rsaquo;</button>';

            ctrlEl.innerHTML = b;
            paginEl.style.display = 'flex';
        }
    }).catch(function () {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">Failed to load patients.</td></tr>';
        if (paginEl) { paginEl.style.display = 'none'; }
    });
}

// ── Patient create/edit form ───────────────────────────────────────────────
var _nhEditStudentId = null;
var _nhCampusCache = [];

function nhLoadPatientCampuses(callback) {
    if (_nhCampusCache.length) {
        nhFillCampusSelect(callback);
        return;
    }
    fetch(NURSE_HEAD_API + '/campuses', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            _nhCampusCache = (data && data.campuses) || [];
            nhFillCampusSelect(callback);
        })
        .catch(function () { _nhCampusCache = []; nhFillCampusSelect(callback); });
}

function nhFillCampusSelect(callback) {
    var sel = document.getElementById('nh-pf-campus_id');
    if (sel) {
        sel.innerHTML = '<option value="">— None —</option>' +
            _nhCampusCache.map(function (c) {
                return '<option value="' + c.id + '">' + nhEsc(c.name) + '</option>';
            }).join('');
    }
    if (typeof callback === 'function') { callback(); }
}

function nhOpenEditForm(studentId) {
    _nhEditStudentId = studentId;
    document.getElementById('nh-pf-title').textContent = 'Edit Patient (' + studentId + ')';
    nhLoadPatientCampuses(function () {
        fetch(NURSE_HEAD_API + '/students/' + encodeURIComponent(studentId), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var s = data && data.student;
                if (!s) { showToast('Patient not found.', 'error'); return; }
                nhFillEditForm(s);
                nhOpenModal('nh-pf-modal');
            })
            .catch(function () { showToast('Failed to load patient.', 'error'); });
    });
}

function nhFillEditForm(s) {
    var map = {
        'nh-pf-student_id':    s.student_id,
        'nh-pf-first_name':    s.first_name,
        'nh-pf-last_name':     s.last_name,
        'nh-pf-middle_name':   s.middle_name,
        'nh-pf-email':         s.email,
        'nh-pf-course_year':   s.course_year,
        'nh-pf-department':    s.department,
        'nh-pf-section':       s.section,
        'nh-pf-contact':       s.contact,
        'nh-pf-address':       s.address,
        'nh-pf-sex':           s.sex,
        'nh-pf-civil_status':  s.civil_status,
        'nh-pf-age':           s.age,
        'nh-pf-date_of_birth': s.date_of_birth ? String(s.date_of_birth).substring(0, 10) : '',
        'nh-pf-blood_type':    s.blood_type,
        'nh-pf-campus_id':     s.campus_id || '',
    };
    Object.keys(map).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.value = map[id] == null ? '' : map[id]; }
    });
    var pw = document.getElementById('nh-pf-pw');
    var pw2 = document.getElementById('nh-pf-pw-confirm');
    if (pw)  { pw.value = ''; }
    if (pw2) { pw2.value = ''; }
}

function nhCloseEditForm() {
    nhCloseModal('nh-pf-modal');
    _nhEditStudentId = null;
}

function nhSaveStudent() {
    if (!_nhEditStudentId) { return; }
    var pw = document.getElementById('nh-pf-pw').value || '';
    var pw2 = document.getElementById('nh-pf-pw-confirm').value || '';
    if (pw && pw.length < 6) { showToast('Password must be at least 6 characters.', 'error'); return; }
    if (pw !== pw2) { showToast('Passwords do not match.', 'error'); return; }

    var payload = {
        first_name:   document.getElementById('nh-pf-first_name').value.trim(),
        last_name:    document.getElementById('nh-pf-last_name').value.trim(),
        middle_name:  document.getElementById('nh-pf-middle_name').value.trim(),
        email:        document.getElementById('nh-pf-email').value.trim(),
        course_year:  document.getElementById('nh-pf-course_year').value.trim(),
        department:   document.getElementById('nh-pf-department').value.trim(),
        section:      document.getElementById('nh-pf-section').value.trim(),
        contact:      document.getElementById('nh-pf-contact').value.trim(),
        address:      document.getElementById('nh-pf-address').value.trim(),
        sex:          document.getElementById('nh-pf-sex').value,
        civil_status: document.getElementById('nh-pf-civil_status').value,
        age:          document.getElementById('nh-pf-age').value ? parseInt(document.getElementById('nh-pf-age').value, 10) : null,
        date_of_birth: document.getElementById('nh-pf-date_of_birth').value || null,
        blood_type:   document.getElementById('nh-pf-blood_type').value,
        campus_id:    document.getElementById('nh-pf-campus_id').value ? parseInt(document.getElementById('nh-pf-campus_id').value, 10) : null,
    };
    if (pw) { payload.password = pw; payload.password_confirmation = pw2; }

    fetch(NURSE_HEAD_API + '/students/' + encodeURIComponent(_nhEditStudentId), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Patient updated successfully.', 'success');
                nhCloseEditForm();
                nhLoadPatients();
            } else {
                var msg = data && data.error;
                if (!msg && data && data.errors) {
                    var first = Object.keys(data.errors)[0];
                    msg = first && data.errors[first][0];
                }
                showToast(msg || 'Failed to update patient.', 'error');
            }
        })
        .catch(function () { showToast('Failed to update patient.', 'error'); });
}

async function nhDeleteStudent(studentId) {
    if (!await uhConfirm('This will permanently delete patient ' + studentId + '.', { title: 'Delete patient?', confirmText: 'Delete patient', danger: true })) { return; }
    fetch(NURSE_HEAD_API + '/students/' + encodeURIComponent(studentId), {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Patient deleted.', 'success');
                nhLoadPatients();
            } else {
                showToast((data && data.error) || 'Failed to delete patient.', 'error');
            }
        })
        .catch(function () { showToast('Failed to delete patient.', 'error'); });
}

// ── Medical record edit / delete ───────────────────────────────────────────
var _nhMedEditId = null;
function nhOpenMedEditForm(recordId) {
    _nhMedEditId = recordId;
    document.getElementById('nh-med-pf-title').textContent = 'Edit Medical Record #' + recordId;
    fetch(NURSE_HEAD_API + '/records/' + recordId, {
        headers: { 'Accept': 'application/json' }, credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var rec = data && data.record;
            if (!rec) { showToast('Record not found.', 'error'); return; }
            nhFillMedEditForm(rec);
            nhOpenModal('nh-med-pf-modal');
        })
        .catch(function () { showToast('Failed to load record.', 'error'); });
}

function nhFillMedEditForm(rec) {
    var map = {
        'nh-med-pf-visit_date':   rec.visit_date ? String(rec.visit_date).substring(0, 10) : '',
        'nh-med-pf-visit_time':   rec.visit_time ? String(rec.visit_time).substring(0, 5) : '',
        'nh-med-pf-reason':       rec.reason || '',
        'nh-med-pf-treatment':    rec.treatment || '',
        'nh-med-pf-bp':           rec.bp || '',
        'nh-med-pf-temp':         rec.temp != null ? rec.temp : '',
        'nh-med-pf-pulse':        rec.pulse != null ? rec.pulse : '',
        'nh-med-pf-rr':           rec.rr != null ? rec.rr : '',
        'nh-med-pf-notes':        rec.notes || '',
    };
    Object.keys(map).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.value = map[id] == null ? '' : String(map[id]); }
    });
}

function nhCloseMedEditForm() {
    nhCloseModal('nh-med-pf-modal');
    _nhMedEditId = null;
}

function nhSaveMedRecord() {
    if (!_nhMedEditId) { return; }
    var numVal = function (id) {
        var el = document.getElementById(id);
        return el && el.value !== '' ? parseFloat(el.value) : null;
    };
    var intVal = function (id) {
        var value = numVal(id);
        return value === null ? null : Math.round(value);
    };
    var payload = {
        visit_date:   document.getElementById('nh-med-pf-visit_date').value || null,
        visit_time:   document.getElementById('nh-med-pf-visit_time').value || null,
        reason:       document.getElementById('nh-med-pf-reason').value.trim(),
        treatment:    document.getElementById('nh-med-pf-treatment').value.trim(),
        bp:           document.getElementById('nh-med-pf-bp').value.trim(),
        temp:         numVal('nh-med-pf-temp'),
        pulse:        intVal('nh-med-pf-pulse'),
        rr:           intVal('nh-med-pf-rr'),
        notes:        document.getElementById('nh-med-pf-notes').value.trim(),
    };
    fetch(NURSE_HEAD_API + '/records/' + _nhMedEditId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Medical record updated.', 'success');
                nhCloseMedEditForm();
                nhRenderMedicalRecords();
                window.dispatchEvent(new CustomEvent('clinic:medical-records-changed'));
            } else {
                var msg = data && data.error;
                if (!msg && data && data.errors) {
                    var first = Object.keys(data.errors)[0];
                    msg = first && data.errors[first][0];
                }
                showToast(msg || 'Failed to update record.', 'error');
            }
        })
        .catch(function () { showToast('Failed to update record.', 'error'); });
}

async function nhDeleteMedicalRecord(recordId) {
    if (!await uhConfirm('This will permanently delete medical record #' + recordId + '.', { title: 'Delete medical record?', confirmText: 'Delete record', danger: true })) { return; }
    fetch(NURSE_HEAD_API + '/records/' + recordId, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Medical record deleted.', 'success');
                nhRenderMedicalRecords();
                window.dispatchEvent(new CustomEvent('clinic:medical-records-changed'));
            } else {
                showToast((data && data.error) || 'Failed to delete record.', 'error');
            }
        })
        .catch(function () { showToast('Failed to delete record.', 'error'); });
}

// ── Vital signs edit / delete ──────────────────────────────────────────────
var _nhVitalEditId = null;

function nhOpenVitalEditForm(recordId) {
    _nhVitalEditId = recordId;
    document.getElementById('nh-vital-pf-title').textContent = 'Edit Vital Signs #' + recordId;
    fetch(NURSE_HEAD_API + '/records-vital/' + recordId, {
        headers: { 'Accept': 'application/json' }, credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var rec = data && data.record;
            if (!rec) { showToast('Record not found.', 'error'); return; }
            nhFillVitalEditForm(rec);
            nhOpenModal('nh-vital-pf-modal');
        })
        .catch(function () { showToast('Failed to load record.', 'error'); });
}

function nhFillVitalEditForm(rec) {
    var map = {
        'nh-vital-pf-visit_date': rec.visit_date ? String(rec.visit_date).substring(0, 10) : '',
        'nh-vital-pf-visit_time': rec.visit_time ? String(rec.visit_time).substring(0, 5) : '',
        'nh-vital-pf-bp':         rec.bp || '',
        'nh-vital-pf-temp':       rec.temp != null ? rec.temp : '',
        'nh-vital-pf-pulse':      rec.pulse != null ? rec.pulse : '',
        'nh-vital-pf-rr':         rec.rr != null ? rec.rr : '',
        'nh-vital-pf-o2_sat':     rec.o2_sat != null ? rec.o2_sat : '',
    };
    Object.keys(map).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.value = map[id] == null ? '' : String(map[id]); }
    });
}

function nhCloseVitalEditForm() {
    nhCloseModal('nh-vital-pf-modal');
    _nhVitalEditId = null;
}

function nhSaveVitalRecord() {
    if (!_nhVitalEditId) { return; }
    var numVal = function (id) {
        var el = document.getElementById(id);
        return el && el.value !== '' ? parseFloat(el.value) : null;
    };
    var payload = {
        visit_date: document.getElementById('nh-vital-pf-visit_date').value || null,
        visit_time: document.getElementById('nh-vital-pf-visit_time').value || null,
        bp:         document.getElementById('nh-vital-pf-bp').value.trim(),
        temp:       numVal('nh-vital-pf-temp'),
        pulse:      numVal('nh-vital-pf-pulse') ? Math.round(numVal('nh-vital-pf-pulse')) : null,
        rr:         numVal('nh-vital-pf-rr') ? Math.round(numVal('nh-vital-pf-rr')) : null,
        o2_sat:     numVal('nh-vital-pf-o2_sat') ? Math.round(numVal('nh-vital-pf-o2_sat')) : null,
    };
    fetch(NURSE_HEAD_API + '/records/' + _nhVitalEditId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Vital signs updated.', 'success');
                nhCloseVitalEditForm();
                nhRenderVitals();
            } else {
                var msg = data && data.error;
                if (!msg && data && data.errors) {
                    var first = Object.keys(data.errors)[0];
                    msg = first && data.errors[first][0];
                }
                showToast(msg || 'Failed to update record.', 'error');
            }
        })
        .catch(function () { showToast('Failed to update record.', 'error'); });
}

async function nhDeleteVitalRecord(recordId) {
    if (!await uhConfirm('This will permanently delete vital signs record #' + recordId + '.', { title: 'Delete vital signs record?', confirmText: 'Delete record', danger: true })) { return; }
    fetch(NURSE_HEAD_API + '/records/' + recordId, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.success) {
                showToast('Vital signs record deleted.', 'success');
                nhRenderVitals();
            } else {
                showToast((data && data.error) || 'Failed to delete record.', 'error');
            }
        })
        .catch(function () { showToast('Failed to delete record.', 'error'); });
}
function nhBuildPagination(page, pages, total, limit, goFn, labelSuffix) {
    var from = (page - 1) * limit + 1;
    var to   = Math.min(page * limit, total);
    var info = 'Showing ' + from + '\u2013' + to + ' of ' + total + ' ' + (labelSuffix || 'records');
    var b = '', s = Math.max(1, page - 2), e = Math.min(pages, page + 2);
    b += '<button class="uh-pg-btn" onclick="' + goFn + '(' + (page-1) + ')"' + (page<=1?' disabled':'') + '>&lsaquo;</button>';
    if (s > 1) { b += '<button class="uh-pg-btn" onclick="' + goFn + '(1)">1</button>'; if (s>2) b += '<span class="uh-pg-ellipsis">&hellip;</span>'; }
    for (var i = s; i <= e; i++) { b += '<button class="uh-pg-btn' + (i===page?' active':'') + '" onclick="' + goFn + '(' + i + ')">' + i + '</button>'; }
    if (e < pages) { if (e<pages-1) b += '<span class="uh-pg-ellipsis">&hellip;</span>'; b += '<button class="uh-pg-btn" onclick="' + goFn + '(' + pages + ')">' + pages + '</button>'; }
    b += '<button class="uh-pg-btn" onclick="' + goFn + '(' + (page+1) + ')"' + (page>=pages?' disabled':'') + '>&rsaquo;</button>';
    return { info: info, controls: b };
}

function nhShowPagination(paginId, infoId, ctrlId, page, pages, total, limit, goFn, label) {
    var paginEl = document.getElementById(paginId);
    var infoEl  = document.getElementById(infoId);
    var ctrlEl  = document.getElementById(ctrlId);
    if (!paginEl || !infoEl || !ctrlEl) { return; }
    if (!total) { paginEl.style.display = 'none'; return; }
    var p = nhBuildPagination(page, pages, total, limit, goFn, label);
    infoEl.textContent = p.info;
    ctrlEl.innerHTML   = p.controls;
    paginEl.style.display = 'flex';
}

// ── Medical Records ───────────────────────────────────────────────────────
var _nhMedPage = 1, _nhMedSearch = '', _nhMedTimer = null;

function nhMedSearchDebounce(val) {
    clearTimeout(_nhMedTimer);
    _nhMedTimer = setTimeout(function () { _nhMedSearch = val.trim(); _nhMedPage = 1; nhRenderMedicalRecords(); }, 350);
}
function nhMedGoPage(p) { _nhMedPage = p; nhRenderMedicalRecords(); }

function nhRenderMedicalRecords() {
    var tbody   = document.getElementById('nh-med-records-body');
    var paginEl = document.getElementById('nh-med-pagination');
    if (!tbody) { return; }
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading...</td></tr>';

    var qs = NURSE_HEAD_API + '/records?type=medical&limit=10&page=' + _nhMedPage;
    if (_nhMedSearch) { qs += '&search=' + encodeURIComponent(_nhMedSearch); }

    fetch(qs, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var rows = (data && data.records) || [];
            var pg   = data.pagination || {};
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No medical records found.</td></tr>';
                if (paginEl) { paginEl.style.display = 'none'; }
                return;
            }
            tbody.innerHTML = rows.map(function (v) {
                var stu = v.student || {};
                var patientName = (stu.last_name && stu.first_name)
                    ? (stu.last_name + ', ' + stu.first_name) : (stu.student_id || '\u2014');
                var nurse = v.nurse || {};
                var nurseName = nurse.last_name
                    ? (nurse.first_name + ' ' + nurse.last_name).trim() : '\u2014';
                var date = v.visit_date ? String(v.visit_date).substring(0, 10) : '\u2014';
                var status = v.disposition || (v.physician_id ? 'reviewed' : 'pending');
                var statusClass = ({ pending:'waiting', reviewed:'in-progress', 'sent home':'completed', admitted:'in-progress', referred:'cancelled' })[String(status).toLowerCase()] || 'waiting';
                return '<tr>' +
                    '<td>' + nhEsc(date) + '</td>' +
                    '<td>' + nhEsc(patientName) + '</td>' +
                    '<td>' + nhEsc(v.reason || 'Consultation') + '</td>' +
                    '<td>' + nhEsc(nurseName) + '</td>' +
                    '<td><span class="uh-badge ' + statusClass + '">' + nhEsc(status) + '</span></td>' +
                    '<td style="display:flex;gap:4px;flex-wrap:wrap;">' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nhShowDetail(\'medical-records\',' + v.id + ')">View</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhOpenMedEditForm(' + v.id + ')">Edit</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" style="color:#dc2626;border-color:#dc2626;" onclick="nhDeleteMedicalRecord(' + v.id + ')">Delete</button>' +
                    '</td>' +
                    '</tr>';
            }).join('');
            nhShowPagination('nh-med-pagination','nh-med-info','nh-med-controls', pg.page||_nhMedPage, pg.pages||1, pg.total||rows.length, 10, 'nhMedGoPage', 'records');
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">Failed to load records.</td></tr>';
            if (paginEl) { paginEl.style.display = 'none'; }
        });
}

// ── Vital Signs ───────────────────────────────────────────────────────────
var _nhVitalPage = 1, _nhVitalSearch = '', _nhVitalCampusId = '', _nhVitalTimer = null;

function nhVitalCampusChanged(campusId) {
    _nhVitalCampusId = campusId;
    _nhVitalPage = 1;
    nhRenderVitals();
}

function nhVitalSearchDebounce(val) {
    clearTimeout(_nhVitalTimer);
    _nhVitalTimer = setTimeout(function () { _nhVitalSearch = val.trim(); _nhVitalPage = 1; nhRenderVitals(); }, 350);
}
function nhVitalGoPage(p) { _nhVitalPage = p; nhRenderVitals(); }

function nhRenderVitals() {
    var tbody   = document.getElementById('nh-vitals-body');
    var paginEl = document.getElementById('nh-vital-pagination');
    if (!tbody) { return; }
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">Loading...</td></tr>';

    var qs = NURSE_HEAD_API + '/records-vital?limit=10&page=' + _nhVitalPage;
    if (_nhVitalSearch) { qs += '&search=' + encodeURIComponent(_nhVitalSearch); }
    if (_nhVitalCampusId) { qs += '&student_campus_id=' + encodeURIComponent(_nhVitalCampusId); }

    fetch(qs, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var rows = (data && data.records) || [];
            var pg   = data.pagination || {};
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No vital signs found.</td></tr>';
                if (paginEl) { paginEl.style.display = 'none'; }
                return;
            }
            tbody.innerHTML = rows.map(function (v) {
                var stu = v.student || {};
                var patientName = (stu.last_name && stu.first_name)
                    ? (stu.last_name + ', ' + stu.first_name) : (stu.student_id || '\u2014');
                var date = v.visit_date ? String(v.visit_date).substring(0, 10) : '\u2014';
                return '<tr>' +
                    '<td>' + nhEsc(date) + '</td>' +
                    '<td>' + nhEsc(patientName) + '</td>' +
                    '<td>' + nhEsc(v.bp || '\u2014') + '</td>' +
                    '<td>' + nhEsc(v.temp   != null ? v.temp   : '\u2014') + '</td>' +
                    '<td>' + nhEsc(v.pulse  != null ? v.pulse  : '\u2014') + '</td>' +
                    '<td>' + nhEsc(v.o2_sat != null ? v.o2_sat : '\u2014') + '</td>' +
                    '<td style="display:flex;gap:4px;flex-wrap:wrap;">' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nhShowDetail(\'vital\',' + v.id + ')">View</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhOpenVitalEditForm(' + v.id + ')">Edit</button>' +
                        '<button class="uh-btn uh-btn-ghost uh-btn-xs" style="color:#dc2626;border-color:#dc2626;" onclick="nhDeleteVitalRecord(' + v.id + ')">Delete</button>' +
                    '</td>' +
                    '</tr>';
            }).join('');
            nhShowPagination('nh-vital-pagination','nh-vital-info','nh-vital-controls', pg.page||_nhVitalPage, pg.pages||1, pg.total||rows.length, 10, 'nhVitalGoPage', 'records');
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="7" class="no-data">Failed to load vital signs.</td></tr>';
            if (paginEl) { paginEl.style.display = 'none'; }
        });
}

// ── Referrals ─────────────────────────────────────────────────────────────
var _nhRefPage = 1, _nhRefSearch = '', _nhRefCampusId = '', _nhRefStatus = '', _nhRefTimer = null;

function nhRefCampusChanged(campusId) {
    _nhRefCampusId = campusId;
    _nhRefPage = 1;
    nhRenderReferrals();
}

function nhRefStatusChanged(status) {
    _nhRefStatus = status;
    _nhRefPage = 1;
    nhRenderReferrals();
}

function nhRefSearchDebounce(val) {
    clearTimeout(_nhRefTimer);
    _nhRefTimer = setTimeout(function () { _nhRefSearch = val.trim(); _nhRefPage = 1; nhRenderReferrals(); }, 350);
}
function nhRefGoPage(p) { _nhRefPage = p; nhRenderReferrals(); }

function nhRenderReferrals() {
    var tbody   = document.getElementById('nh-referrals-body');
    var paginEl = document.getElementById('nh-ref-pagination');
    if (!tbody) { return; }
    tbody.innerHTML = '<tr><td colspan="8" class="no-data">Loading...</td></tr>';

    if (_nhRefStatus === 'waiting_to_schedule') {
        var params = new URLSearchParams({ limit: '10', page: String(_nhRefPage) });
        if (_nhRefCampusId) { params.set('campus_id', _nhRefCampusId); }
        if (_nhRefSearch) { params.set('q', _nhRefSearch); }
        fetch('/api/clinic/referrals/awaiting-schedule?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json().then(function (data) { return { response: r, data: data }; }); })
            .then(function (result) {
                var data = result.data || {};
                if (!result.response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Unable to load referrals waiting to be scheduled.');
                }
                var rows = data.referrals || [];
                var pg = data.pagination || {};
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="no-data">No physician or dentist referrals are waiting to be scheduled.</td></tr>';
                    if (paginEl) { paginEl.style.display = 'none'; }
                    return;
                }
                tbody.innerHTML = rows.map(function (r) {
                    var stu = r.student || {};
                    var patientName = (stu.last_name && stu.first_name)
                        ? (stu.last_name + ', ' + stu.first_name) : (stu.student_id || '\u2014');
                    var route = r.route_to === 'dentist' ? 'Dentist' : 'Physician';
                    var date = r.created_at ? uhFormatDateTime(r.created_at) : '\u2014';
                    return '<tr>' +
                        '<td>' + nhEsc(r.referral_no || r.id) + '</td>' +
                        '<td>' + nhEsc(patientName) + '</td>' +
                        '<td>' + nhEsc(r.from_campus ? r.from_campus.name : '\u2014') + '</td>' +
                        '<td>' + nhEsc(r.referred_to_name || route) + '</td>' +
                        '<td>' + nhEsc(r.reason || '\u2014') + '</td>' +
                        '<td>' + nhEsc(date) + '</td>' +
                        '<td><span class="uh-badge waiting">Waiting to schedule</span></td>' +
                        '<td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhShowDetail(&#39;referral&#39;,' + Number(r.id) + ')">Details</button> ' +
                        '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nViewReferralForm(' + Number(r.id) + ')">View Form</button></td>' +
                        '</tr>';
                }).join('');
                nhShowPagination('nh-ref-pagination', 'nh-ref-info', 'nh-ref-controls', pg.page || _nhRefPage, pg.pages || 1, pg.total || rows.length, 10, 'nhRefGoPage', 'referrals');
            })
            .catch(function (error) {
                tbody.innerHTML = '<tr><td colspan="8" class="no-data">Unable to load referrals waiting to be scheduled.</td></tr>';
                if (paginEl) { paginEl.style.display = 'none'; }
                if (typeof uhToast === 'function') { uhToast(error.message || 'Unable to load referrals waiting to be scheduled.', 'error'); }
            });
        return;
    }

    var qs = NURSE_HEAD_API + '/records-referral?limit=10&page=' + _nhRefPage + '&sort_order=' + _nhRefSortOrder;
    if (_nhRefSearch) { qs += '&search=' + encodeURIComponent(_nhRefSearch); }
    if (_nhRefCampusId) { qs += '&student_campus_id=' + encodeURIComponent(_nhRefCampusId); }
    if (_nhRefStatus) { qs += '&status=' + encodeURIComponent(_nhRefStatus); }

    fetch(qs, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var rows = (data && data.records) || [];
            var pg   = data.pagination || {};
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="no-data">No referrals found.</td></tr>';
                if (paginEl) { paginEl.style.display = 'none'; }
                return;
            }
            tbody.innerHTML = rows.map(function (r) {
                var stu = r.student || {};
                var patientName = (stu.last_name && stu.first_name)
                    ? (stu.last_name + ', ' + stu.first_name) : (stu.student_id || '\u2014');
                var toCampus  = r.referred_to_name || (r.to_campus && r.to_campus.name) || '\u2014';
                var refBy     = r.referred_by;
                var fromStaff = (refBy && (refBy.first_name || refBy.last_name))
                    ? ((refBy.first_name || '') + ' ' + (refBy.last_name || '')).trim() : '\u2014';
                var date = uhFormatDateTime(r.created_at);
                var status = r.status || 'pending';
                var statusClass = ({ pending:'waiting', accepted:'in-progress', completed:'completed', cancelled:'cancelled', rejected:'cancelled' })[status] || 'waiting';
                return '<tr>' +
                    '<td>' + nhEsc(r.referral_no || r.id) + '</td>' +
                    '<td>' + nhEsc(patientName) + '</td>' +
                    '<td>' + nhEsc(fromStaff) + '</td>' +
                    '<td>' + nhEsc(toCampus) + '</td>' +
                    '<td>' + nhEsc(r.reason || r.specialty || '\u2014') + '</td>' +
                    '<td>' + nhEsc(date) + '</td>' +
                    '<td><span class="uh-badge ' + statusClass + '">' + nhEsc(status) + '</span></td>' +
                    '<td><button class="uh-btn uh-btn-ghost uh-btn-xs" onclick="nhShowDetail(\'referral\',' + r.id + ')">Details</button> ' +
                    '<button class="uh-btn uh-btn-primary uh-btn-xs" onclick="nViewReferralForm(' + Number(r.id) + ')">View Form</button></td>' +
                    '</tr>';
            }).join('');
            nhShowPagination('nh-ref-pagination','nh-ref-info','nh-ref-controls', pg.page||_nhRefPage, pg.pages||1, pg.total||rows.length, 10, 'nhRefGoPage', 'referrals');
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="8" class="no-data">Failed to load referrals.</td></tr>';
            if (paginEl) { paginEl.style.display = 'none'; }
        });
}

// ── Appointments ──────────────────────────────────────────────────────────
var _nhApptPage = 1, _nhApptSearch = '', _nhApptCampusId = '', _nhApptStatus = '', _nhApptPriority = '', _nhApptTimer = null, _nhApptSortOrder = 'asc';
var _nhAutoTriageAttemptedAppointments = new Set();
var _nhAutoTriageInFlightAppointments = new Set();
var _nhApptHistoryPage = 1, _nhApptHistorySearch = '', _nhApptHistoryCampusId = '', _nhApptHistoryPriority = '', _nhApptHistorySortOrder = 'desc';
var _nhApptHistoryStatus = '', _nhApptHistoryTimer = null;
var _nhRefSortOrder = 'desc';
function nhApptCampusChanged(campusId) {
    _nhApptCampusId = campusId;
    _nhApptPage = 1;
    nhRenderAppointments();
}

function nhApptStatusChanged(status) {
    _nhApptStatus = status;
    _nhApptPage = 1;
    nhRenderAppointments();
}

function nhApptPriorityChanged(priority) {
    _nhApptPriority = priority;
    _nhApptPage = 1;
    nhRenderAppointments();
}

function nhApptSearchDebounce(val) {
    clearTimeout(_nhApptTimer);
    _nhApptTimer = setTimeout(function () {
        _nhApptSearch = val.trim();
        _nhApptPage = 1;
        nhRenderAppointments();
    }, 350);
}

function nhApptGoPage(page) {
    _nhApptPage = page;
    nhRenderAppointments();
}

function nhApptHistoryCampusChanged(campusId) {
    _nhApptHistoryCampusId = campusId;
    _nhApptHistoryPage = 1;
    nhRenderAppointmentHistory();
}

function nhApptHistoryPriorityChanged(priority) {
    _nhApptHistoryPriority = priority;
    _nhApptHistoryPage = 1;
    nhRenderAppointmentHistory();
}

function nhApptHistoryStatusChanged(status) {
    _nhApptHistoryStatus = status;
    _nhApptHistoryPage = 1;
    nhRenderAppointmentHistory();
}

function nhApptHistorySearchDebounce(val) {
    clearTimeout(_nhApptHistoryTimer);
    _nhApptHistoryTimer = setTimeout(function () {
        _nhApptHistorySearch = val.trim();
        _nhApptHistoryPage = 1;
        nhRenderAppointmentHistory();
    }, 350);
}

function nhApptHistoryGoPage(page) {
    _nhApptHistoryPage = page;
    nhRenderAppointmentHistory();
}

function nhToggleAppointmentHistoryTimeSort() {
    _nhApptHistorySortOrder = _nhApptHistorySortOrder === 'asc' ? 'desc' : 'asc';
    var indicator = document.getElementById('nh-appt-history-sort-indicator');
    if (indicator) { indicator.textContent = _nhApptHistorySortOrder === 'asc' ? '↑' : '↓'; }
    _nhApptHistoryPage = 1;
    nhRenderAppointmentHistory();
}

function nhToggleAppointmentTimeSort() {
    _nhApptSortOrder = _nhApptSortOrder === 'asc' ? 'desc' : 'asc';
    var indicator = document.getElementById('nh-appt-sort-indicator');
    if (indicator) { indicator.textContent = _nhApptSortOrder === 'asc' ? 'Oldest' : 'Newest'; }
    var sortButton = document.getElementById('nh-appt-sort-btn');
    if (sortButton) {
        sortButton.setAttribute('aria-label', 'Sort appointments: ' + (_nhApptSortOrder === 'asc' ? 'oldest' : 'newest') + ' first');
    }
    _nhApptPage = 1;
    nhRenderAppointments();
}

function nhToggleReferralTimeSort() {
    _nhRefSortOrder = _nhRefSortOrder === 'asc' ? 'desc' : 'asc';
    var indicator = document.getElementById('nh-referrals-sort-indicator');
    if (indicator) { indicator.textContent = _nhRefSortOrder === 'asc' ? '↑' : '↓'; }
    _nhRefPage = 1;
    nhRenderReferrals();
}

function nhAppointmentRow(appointment, withActions) {
    var student = appointment.student || {};
    var patientName = [student.first_name, student.middle_name, student.last_name]
        .filter(Boolean).join(' ') || 'Unknown patient';
    var isCheckupStage = ['checkup_scheduled', 'checkup_completed'].includes(appointment.status);
    var date = uhFormatDate(isCheckupStage ? appointment.checkup_date : appointment.appt_date);
    var time = uhFormatTime(isCheckupStage ? appointment.checkup_time : appointment.appt_time);
    var status = appointment.status || 'pending';
    var priorityValue = String(appointment.priority || '').trim().toLowerCase();
    var priorityLevels = {
        high: 'High', 'priority 1': 'High', '1': 'High',
        medium: 'Medium', 'priority 2': 'Medium', '2': 'Medium',
        low: 'Low', 'priority 3': 'Low', '3': 'Low',
    };
    var priorityLabel = priorityLevels[priorityValue] || '';
    var priorityClass = priorityLabel ? priorityLabel.toLowerCase() : 'unassigned';
    var confidence = Number(appointment.confidence);
    var modelPrediction = appointment.model_prediction && typeof appointment.model_prediction === 'object'
        ? appointment.model_prediction
        : null;
    var modelConfidence = Number(modelPrediction && modelPrediction.confidence);
    var confidenceLabel = modelPrediction && Number.isFinite(modelConfidence) && modelConfidence >= 0 && modelConfidence <= 1
        ? 'Model: ' + (modelPrediction.priority || 'Priority unavailable') + ' · ' + Math.round(modelConfidence * 100) + '% confidence'
        : appointment.confidence !== null && appointment.confidence !== undefined && appointment.confidence !== '' && Number.isFinite(confidence) && confidence >= 0 && confidence <= 1
            ? 'Triage confidence: ' + Math.round(confidence * 100) + '%'
            : '';
    var priorityDetails = [priorityLabel, confidenceLabel].filter(Boolean).join(' · ');
    var caseConfidenceLabel = confidenceLabel || 'Confidence unavailable';
    var statusClasses = { pending:'waiting', accepted:'in-progress', checkup_scheduled:'in-progress', checkup_completed:'in-progress', confirmed:'in-progress', completed:'completed', cancelled:'cancelled', deleted:'cancelled' };
    var actions = '\u2014';

    if (withActions) {
        actions = '';
        var viewData = {
            name: patientName,
            studentId: student.student_id || '',
            patientType: student.patient_type || '',
            date: date,
            time: time,
            submittedAt: appointment.created_at || '',
            service: appointment.service_type || appointment.appt_type || '\u2014',
            status: status,
        };
        actions += '<button type="button" class="nurse-queue-workflow-action" data-appointment="' + nhEsc(JSON.stringify(viewData)) + '" onclick="nhViewAppointment(JSON.parse(this.dataset.appointment))" aria-label="View ' + nhEsc(patientName) + ' appointment" title="View appointment"><i class="fa fa-eye" aria-hidden="true"></i><span>View</span></button>';
        var acceptData = nhEsc(JSON.stringify({
            id: Number(appointment.id),
            campus_id: Number(appointment.campus_id || student.campus_id || 0),
            service_type: appointment.service_type || appointment.appt_type || '',
            availability_id: Number(appointment.availability_id || 0),
            time: appointment.appt_time || '',
            checkup_date: appointment.checkup_date || '',
            checkup_time: appointment.checkup_time || '',
        }));
        if (!appointment.priority) {
            actions += '<button type="button" class="nurse-queue-workflow-action" onclick="nhTriageAppointment(' + Number(appointment.id) + ')" aria-label="Run triage for ' + nhEsc(patientName) + ' appointment" title="Run triage"><i class="fa fa-heart-pulse" aria-hidden="true"></i><span>Triage</span></button>';
        }
        if (status === 'pending') {
            actions += '<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" data-accept-appointment="' + acceptData + '" onclick="nAcceptAppointment(JSON.parse(this.dataset.acceptAppointment))"><i class="fa fa-check" aria-hidden="true"></i><span>Accept</span></button>';
            actions += '<button type="button" class="nurse-queue-workflow-action" onclick="nhDeclineAppointment(' + Number(appointment.id) + ')"><i class="fa fa-xmark" aria-hidden="true"></i><span>Decline</span></button>';
        }
        var actionData = nhEsc(JSON.stringify({
            id: Number(appointment.id),
            status: status,
            student_id: student.student_id || '',
            first_name: student.first_name || '',
            middle_name: student.middle_name || '',
            last_name: student.last_name || '',
            reason: appointment.reason || '',
            campus_id: Number(appointment.campus_id || student.campus_id || 0),
            availability_id: Number(appointment.availability_id || 0),
            time: isCheckupStage ? appointment.checkup_time || '' : appointment.appt_time || '',
            checkup_date: appointment.checkup_date || '',
            checkup_time: appointment.checkup_time || '',
            service_type: appointment.service_type || appointment.appt_type || '',
        }));
        if (status === 'accepted' || status === 'checkup_completed') {
            var scheduleLabel = status === 'accepted' ? 'Schedule check-up' : 'Schedule provider';
            actions += '<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" data-queue-entry="' + actionData + '" onclick="nScheduleAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-calendar-check" aria-hidden="true"></i><span>' + scheduleLabel + '</span></button>';
        }
        if (['accepted', 'checkup_scheduled', 'checkup_completed', 'confirmed'].includes(status)) {
            actions += '<button type="button" class="nurse-queue-workflow-action" data-queue-entry="' + actionData + '" onclick="nReferAppointment(JSON.parse(this.dataset.queueEntry))"><i class="fa fa-share-from-square" aria-hidden="true"></i><span>Refer</span></button>';
        }
        if (status === 'checkup_scheduled') {
            if (nurseCheckupTimeHasArrived(appointment.checkup_date, appointment.checkup_time)) {
                actions += '<button type="button" class="nurse-queue-workflow-action nurse-queue-confirm-action" onclick="nCompleteClinicCheckup(' + Number(appointment.id) + ')" aria-label="Mark ' + nhEsc(patientName) + ' clinic check-up as complete"><i class="fa fa-check-double" aria-hidden="true"></i><span>Check-up done</span></button>';
            }
        }
        if (status === 'confirmed') {
            var appointmentDate = String(appointment.appt_date || '').slice(0, 10);
            var today = nurseLocalDateString();
            if (appointmentDate && appointmentDate <= today) {
                actions += '<button type="button" class="nurse-queue-workflow-action" onclick="nhUpdateAppointment(' + Number(appointment.id) + ',\'completed\')" aria-label="Mark ' + nhEsc(patientName) + ' appointment as done"><i class="fa fa-check-double" aria-hidden="true"></i><span>Done</span></button>';
            }
        }
        var initials = [student.first_name, student.last_name]
            .filter(Boolean).map(function (name) { return String(name).charAt(0); }).join('').toUpperCase() || 'U';
        var statusLabel = ({
            pending: 'Waiting',
            accepted: 'Accepted · schedule check-up',
            checkup_scheduled: 'Clinic check-up scheduled',
            checkup_completed: 'Check-up complete · schedule provider',
            confirmed: 'Provider visit confirmed',
        })[status] || status.charAt(0).toUpperCase() + status.slice(1);
        var statusClass = ['confirmed', 'accepted', 'checkup_scheduled', 'checkup_completed'].includes(status) ? 'status-active'
            : (status === 'completed' ? 'status-done' : 'status-waiting');

        return '<article class="nurse-recent-visit nurse-current-queue-item">' +
            '<div class="nurse-recent-visit-avatar" aria-hidden="true">' + nhEsc(initials) + '</div>' +
            '<div class="nurse-recent-visit-main">' +
                '<strong class="nurse-recent-visit-name">' + nhEsc(patientName) + '</strong>' +
                uhPatientTypeBadge(student.patient_type) +
                '<span class="nurse-recent-visit-id">' + (student.student_id ? 'ID ' + nhEsc(student.student_id) : 'Appointment') + '</span>' +
            '</div>' +
            '<div class="nurse-recent-visit-detail">' +
                '<span class="nurse-recent-visit-reason">' + nhEsc(appointment.service_type || appointment.appt_type || '\u2014') + '</span>' +
                '<span class="nurse-current-queue-meta">Appointment · ' + nhEsc(date) + (time !== '\u2014' ? ' · ' + nhEsc(time) : '') + '</span>' +
            '</div>' +
            '<div class="nurse-current-queue-priority">' +
                '<span class="cw-priority-indicator cw-priority-indicator--' + priorityClass + '">' + (priorityLabel || 'Not assessed') + '</span>' +
                '<small>' + nhEsc(caseConfidenceLabel) + '</small>' +
            '</div>' +
            '<span class="nurse-recent-visit-status ' + statusClass + '">' + nhEsc(statusLabel) + '</span>' +
            '<div class="nurse-current-queue-actions">' + actions + '</div>' +
        '</article>';
    }

    return '<tr>' +
        '<td>' + nhEsc(date) + '</td>' +
        '<td>' + nhEsc(time) + '</td>' +
        '<td>' + nhEsc(patientName) + '<br>' + uhPatientTypeBadge(student.patient_type) + '</td>' +
        '<td>' + nhEsc(appointment.service_type || appointment.appt_type || '\u2014') +
            (priorityLabel
                ? '<br><span class="cw-priority-indicator cw-priority-indicator--' + priorityClass + '">' + nhEsc(priorityLabel) + '</span>'
                    + (confidenceLabel ? '<br><small>' + nhEsc(confidenceLabel) + '</small>' : '')
                : '') + '</td>' +
        '<td><span class="uh-badge appointment-status-badge ' + (statusClasses[status] || 'waiting') + '">' + nhEsc(status === 'accepted' ? 'Accepted' : status) + '</span></td>' +
        (withActions ? '<td>' + actions + '</td>' : '<td>' + (status === 'cancelled' && appointment.cancellation_reason ? nhEsc(appointment.cancellation_reason) : '\u2014') + '</td>') +
        '</tr>';
}

function nhRenderAppointments() {
    var tbody = document.getElementById('nh-appt-body');
    var pagination = document.getElementById('nh-appt-pagination');
    var count = document.getElementById('nh-appt-count');
    if (!tbody) { return; }
    tbody.innerHTML = '<p class="student-empty-state">Loading appointments...</p>';

    var query = NURSE_HEAD_API + '/appointments?limit=10&page=' + _nhApptPage + '&appointment_group=upcoming&sort_order=' + _nhApptSortOrder;
    if (_nhApptSearch) { query += '&search=' + encodeURIComponent(_nhApptSearch); }
    if (_nhApptCampusId) { query += '&student_campus_id=' + encodeURIComponent(_nhApptCampusId); }
    if (_nhApptStatus) { query += '&status=' + encodeURIComponent(_nhApptStatus); }
    if (_nhApptPriority) { query += '&priority=' + encodeURIComponent(_nhApptPriority); }

    fetch(query, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data || data.success === false) {
                    throw new Error((data && (data.message || data.error)) || 'Failed to load appointments.');
                }
                return data;
            });
        })
        .then(function (data) {
            var appointments = (data && data.appointments) || [];
            var page = data.pagination || {};
            if (!appointments.length && Number(page.total) > 0 && _nhApptPage > Number(page.pages || 1)) {
                _nhApptPage = Number(page.pages || 1);
                nhRenderAppointments();
                return;
            }

            if (!appointments.length) {
                tbody.innerHTML = '<p class="student-empty-state">No upcoming appointments found.</p>';
                if (count) { count.textContent = Number(page.total || 0) ? 'No appointments on this page.' : 'Showing 0 of 0'; }
                if (pagination) { pagination.style.display = 'none'; }
                return;
            }

            tbody.innerHTML = appointments.map(function (appointment) {
                return nhAppointmentRow(appointment, true);
            }).join('');
            var currentPage = Number(page.page || _nhApptPage);
            var totalPages = Number(page.pages || 1);
            var total = Number(page.total || appointments.length);
            var from = (currentPage - 1) * 10 + 1;
            var to = Math.min(currentPage * 10, total);
            if (count) { count.textContent = 'Showing ' + from + '\u2013' + to + ' of ' + total; }

            if (pagination && totalPages > 1) {
                var pageControls = nhBuildPagination(currentPage, totalPages, total, 10, 'nhApptGoPage', 'appointments');
                document.getElementById('nh-appt-info').textContent = 'Page ' + currentPage + ' of ' + totalPages;
                document.getElementById('nh-appt-controls').innerHTML = pageControls.controls;
                pagination.style.display = 'flex';
            } else if (pagination) {
                pagination.style.display = 'none';
            }

            nhAutoTriageAppointments(appointments);
        })
        .catch(function (error) {
            console.error('Failed to load appointments:', error);
            tbody.innerHTML = '<p class="student-empty-state">Failed to load appointments. Please refresh and try again.</p>';
            if (count) { count.textContent = 'Unable to load appointments.'; }
            if (pagination) { pagination.style.display = 'none'; }
        });
}

function nhViewAppointment(appointment) {
    var submittedAt = appointment.submittedAt ? uhFormatDateTime(appointment.submittedAt) : (appointment.date || '\u2014');
    var details = [
        'Patient: '        + (appointment.name      || 'Unknown patient'),
        'Patient type: '   + uhPatientTypeLabel(appointment.patientType),
        'Patient ID: '     + (appointment.studentId || '\u2014'),
        'Date submitted: ' + submittedAt,
        'Service: '        + (appointment.service   || '\u2014'),
        'Status: '         + (appointment.status    || 'pending'),
    ].join('\n');
    uhAlert(details, { title: 'Appointment details' });
}

function nhAutoTriageAppointments(appointments) {
    var eligible = appointments.filter(function (appointment) {
        var id = String(appointment.id);
        return !appointment.priority
            && String(appointment.reason || '').trim() !== ''
            && !_nhAutoTriageAttemptedAppointments.has(id);
    });

    if (!eligible.length) { return; }
    eligible.forEach(function (appointment) {
        _nhAutoTriageAttemptedAppointments.add(String(appointment.id));
        _nhAutoTriageInFlightAppointments.add(String(appointment.id));
    });

    Promise.all(eligible.map(function (appointment) {
        return fetch(NURSE_HEAD_API + '/appointments/' + appointment.id + '/triage', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Could not automatically triage this appointment.');
                }
                return { id: appointment.id, error: null };
            });
        }).catch(function (error) {
            console.error('Failed to automatically triage appointment ' + appointment.id + ':', error);
            return { id: appointment.id, error: error };
        });
    })).then(function (results) {
        results.forEach(function (result) {
            _nhAutoTriageInFlightAppointments.delete(String(result.id));
        });
        var assessed = results.filter(function (result) { return !result.error; });
        var failed = results.filter(function (result) { return result.error; });
        if (assessed.length) {
            uhToast('Triage completed for ' + assessed.length + ' appointment' + (assessed.length === 1 ? '' : 's') + '. A clinician should review the assessments.', 'success');
            nhRenderAppointments();
        }
        if (failed.length) {
            uhToast('Could not automatically triage ' + failed.length + ' appointment' + (failed.length === 1 ? '' : 's') + '. Review the appointment reason and try again.', 'error');
        }
    });
}

async function nhTriageAppointment(id) {
    if (_nhAutoTriageInFlightAppointments.has(String(id))) {
        uhToast('Triage is already running for this appointment.', 'info');
        return;
    }

    var response;
    try {
        response = await fetch(NURSE_HEAD_API + '/appointments/' + id + '/triage', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
    } catch (error) {
        uhToast('Could not run triage for this appointment. Check your connection and try again.', 'error');
        return;
    }

    var data = await response.json().catch(function () { return {}; });
    if (!response.ok || !data.success) {
        uhToast(data.message || data.error || 'Could not run triage for this appointment.', 'error');
        return;
    }

    uhToast('Triage result: ' + data.priority + '. A clinician should review this assessment.', 'success');
    nhRenderAppointments();
    nhRenderAppointmentHistory();
}

function nhRenderAppointmentHistory() {
    var tbody = document.getElementById('nh-appt-history-body');
    var pagination = document.getElementById('nh-appt-history-pagination');
    if (!tbody) { return; }
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading appointment history...</td></tr>';

    var query = NURSE_HEAD_API + '/appointments?limit=10&page=' + _nhApptHistoryPage + '&appointment_group=history&sort_order=' + _nhApptHistorySortOrder;
    if (_nhApptHistoryStatus) { query += '&history_status=' + encodeURIComponent(_nhApptHistoryStatus); }
    if (_nhApptHistorySearch) { query += '&search=' + encodeURIComponent(_nhApptHistorySearch); }
    if (_nhApptHistoryCampusId) { query += '&student_campus_id=' + encodeURIComponent(_nhApptHistoryCampusId); }
    if (_nhApptHistoryPriority) { query += '&priority=' + encodeURIComponent(_nhApptHistoryPriority); }

    fetch(query, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            var appointments = (data && data.appointments) || [];
            var page = data.pagination || {};

            if (!appointments.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No appointments in this history view.</td></tr>';
                if (pagination) { pagination.style.display = 'none'; }
                return;
            }

            tbody.innerHTML = appointments.map(function (appointment) {
                return nhAppointmentRow(appointment, false);
            }).join('');
            nhShowPagination('nh-appt-history-pagination', 'nh-appt-history-info', 'nh-appt-history-controls', page.page || _nhApptHistoryPage, page.pages || 1, page.total || appointments.length, 10, 'nhApptHistoryGoPage', 'appointment history');
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">Failed to load appointment history.</td></tr>';
            if (pagination) { pagination.style.display = 'none'; }
        });
}

async function nhUpdateAppointment(id, status, cancellationReason) {
    var response;
    try {
        response = await fetch(NURSE_HEAD_API + '/appointments/' + id, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                status: status,
                cancellation_reason: cancellationReason,
            }),
        });
    } catch (error) {
        uhToast('Could not update the appointment. Check your connection and try again.', 'error');
        return;
    }

    var responseText = await response.text();
    var data;
    try {
        data = responseText ? JSON.parse(responseText) : {};
    } catch (error) {
        uhToast('The server returned an unexpected response (HTTP ' + response.status + '). Please try again or contact the clinic administrator.', 'error');
        return;
    }
    if (!response.ok || !data.success) {
        uhToast(data.message || data.error || 'Could not update the appointment.', 'error');
        return;
    }

    var successMessage = status === 'accepted'
        ? 'Patient accepted. Refer to a physician or dentist if specialist scheduling is needed.'
        : (status === 'confirmed'
            ? 'Appointment confirmed.'
            : (status === 'cancelled' ? 'Appointment declined. Reason recorded.' : 'Appointment marked as done.'));
    uhToast(successMessage, 'success');
    nhRenderAppointments();
    nhRenderAppointmentHistory();
    nhLoadDashboardStats();
}

async function nhDeclineAppointment(id) {
    var reason = await uhPrompt('Enter the reason for declining this appointment.', {
        title: 'Decline appointment?',
        label: 'Reason for decline',
        placeholder: 'Provide a reason',
        confirmText: 'Decline appointment',
        danger: true,
    });
    if (reason === null) { return; }
    if (!reason.trim()) {
        uhToast('A decline reason is required.', 'error');
        return;
    }
    await nhUpdateAppointment(id, 'cancelled', reason.trim());
}

// Local escapeHtml for the inline script — matches the helper in
// nurse_head.js but kept inline so the page works even if the external
// script hasn't loaded yet.
function nhEsc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// -------------------------------------------------------
//  RECORD DETAIL MODAL
//  Fetches a single record by type/id and renders a compact summary
//  inside the shared #nh-detail-modal. The "View full record" link
//  opens the print-friendly standalone page in a new tab.
//  type ∈ { 'medical-records', 'vital', 'referral' }
// -------------------------------------------------------
function nhShowDetail(type, id) {
    var titleEl = document.getElementById('nh-detail-title');
    var bodyEl  = document.getElementById('nh-detail-body');
    var linkEl  = document.getElementById('nh-detail-full-link');
    if (!bodyEl || !titleEl) { return; }

    var endpoint;
    var title;
    var hasPrint = true;
    if (type === 'medical-records') {
        endpoint = NURSE_HEAD_API + '/records/' + id;
        title    = 'Medical Record #' + id;
    } else if (type === 'vital') {
        endpoint = NURSE_HEAD_API + '/records-vital/' + id;
        title    = 'Vital Signs #' + id;
    } else if (type === 'referral') {
        endpoint = NURSE_HEAD_API + '/records-referral/' + id;
        title    = 'Referral #' + id;
    } else if (type === 'student') {
        endpoint = NURSE_HEAD_API + '/students/' + encodeURIComponent(id);
        title    = 'Patient ' + id;
        hasPrint = false; // no standalone print page for students
    } else {
        showToast('Unknown record type: ' + type, 'error');
        return;
    }

    titleEl.innerHTML = '<i class="fa fa-file-medical" style="color:#2a7a6f;margin-right:8px"></i>' + nhEsc(title);
    bodyEl.innerHTML  = '<p style="text-align:center;color:#6b7280;padding:20px 0">Loading…</p>';
    if (linkEl) {
        if (hasPrint) {
            linkEl.href = '/clinic/records/print/' + encodeURIComponent(type) + '/' + id;
            linkEl.style.display = 'inline-flex';
        } else {
            linkEl.style.display = 'none';
        }
    }

    nhOpenModal('nh-detail-modal');

    fetch(endpoint, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data || !data.success) {
                bodyEl.innerHTML = '<p style="color:#b91c1c;text-align:center;padding:14px 0">' +
                    nhEsc((data && data.error) || 'Record not found.') + '</p>';
                return;
            }
            // The student endpoint nests the record under `student` instead
            // of `record`. Normalize so nhRenderDetailBody can be type-driven.
            var record = data.record || data.student;
            if (!record) {
                bodyEl.innerHTML = '<p style="color:#b91c1c;text-align:center;padding:14px 0">Record not found.</p>';
                return;
            }
            bodyEl.innerHTML = nhRenderDetailBody(type, record);
        })
        .catch(function () {
            bodyEl.innerHTML = '<p style="color:#b91c1c;text-align:center;padding:14px 0">Failed to load record.</p>';
        });
}

function nhCloseDetail() {
    nhCloseModal('nh-detail-modal');
}

// Builds the inner HTML for the detail modal body. Three shapes share
// the same shell so the modal styling stays consistent.
function nhRenderDetailBody(type, r) {
    if (type === 'medical-records') {
        var stu = r.student || {};
        var studentName = (stu.last_name || stu.first_name)
            ? (stu.last_name || '') + ', ' + (stu.first_name || '')
            : (stu.student_id || '—');
        var nurse = r.nurse || {};
        var phys  = r.physician || {};
        var nurseName = (nurse.first_name || nurse.last_name)
            ? ((nurse.first_name || '') + ' ' + (nurse.last_name || '')).trim() : '—';
        var physName  = (phys.first_name || phys.last_name)
            ? ((phys.first_name || '') + ' ' + (phys.last_name || '')).trim() : '—';
        var campusName = (r.campus && r.campus.name) || '—';
        var date = r.visit_date ? String(r.visit_date).substring(0, 10) : '—';

        return nhDetailGrid([
            ['Patient',       studentName],
            ['Student ID',    stu.student_id || '—'],
            ['Date',          date],
            ['Reason',        r.reason || '—'],
            ['Disposition',   r.disposition || '—'],
            ['BP',            r.bp || '—'],
            ['Temp',          r.temp != null ? r.temp + ' °C' : '—'],
            ['Pulse',         r.pulse != null ? r.pulse : '—'],
            ['Attending Nurse', nurseName],
            ['Physician',     physName],
            ['Campus',        campusName],
        ]);
    }

    if (type === 'vital') {
        var stu = r.student || {};
        var studentName = (stu.last_name || stu.first_name)
            ? (stu.last_name || '') + ', ' + (stu.first_name || '')
            : (stu.student_id || '—');
        var date = r.visit_date ? String(r.visit_date).substring(0, 10) : '—';

        return nhDetailGrid([
            ['Patient',    studentName],
            ['Student ID', stu.student_id || '—'],
            ['Date',       date],
            ['BP',         r.bp || '—'],
            ['Temp',       r.temp != null ? r.temp + ' °C' : '—'],
            ['Pulse',      r.pulse != null ? r.pulse : '—'],
            ['RR',         r.rr != null ? r.rr : '—'],
            ['O₂ Sat',     r.o2_sat != null ? r.o2_sat + ' %' : '—'],
        ]);
    }

    if (type === 'referral') {
        var stu = r.student || {};
        var studentName = (stu.last_name || stu.first_name)
            ? (stu.last_name || '') + ', ' + (stu.first_name || '')
            : (stu.student_id || '—');
        var fromCampus = (r.from_campus && r.from_campus.name) || '—';
        var toCampus   = r.referred_to_name
            || (r.to_campus && r.to_campus.name) || '—';
        var refBy = r.referred_by || {};
        var fromStaff = (refBy.first_name || refBy.last_name)
            ? ((refBy.first_name || '') + ' ' + (refBy.last_name || '')).trim() : '—';
        var date = uhFormatDate(r.created_at);

        return nhDetailGrid([
            ['Referral No.', r.referral_no || ('#' + r.id)],
            ['Patient',      studentName],
            ['From Campus',  fromCampus],
            ['Referred By',  fromStaff],
            ['Referred To',  toCampus],
            ['Specialty',    r.specialty || '—'],
            ['Urgency',      r.urgency ? r.urgency.charAt(0).toUpperCase() + r.urgency.slice(1) : 'Routine'],
            ['Status',       r.status ? r.status.charAt(0).toUpperCase() + r.status.slice(1) : 'Pending'],
            ['Date',         date],
            ['Reason',       r.reason || '—'],
        ]);
    }

    if (type === 'student') {
        // Student shape from /api/clinic/admin/students/{studentNo}:
        // { id, student_id, first_name, last_name, middle_name, email,
        //   course_year, department, section, contact, address, sex,
        //   civil_status, age, blood_type, campus: { id, name },
        //   allergy_detail, hr_blood_type, last_visit (only on list, not here) }
        var fullName = ((r.last_name || '') + (r.first_name ? ', ' + r.first_name : '')
            + (r.middle_name ? ' ' + r.middle_name : '')).trim() || '—';
        var campusName = (r.campus && r.campus.name) || '—';
        var dob = r.date_of_birth ? String(r.date_of_birth).substring(0, 10) : '—';

        return nhDetailGrid([
            ['Name',         fullName],
            ['Student ID',   r.student_id || '—'],
            ['Email',        r.email || '—'],
            ['Contact',      r.contact || '—'],
            ['Age / Sex',    (r.age != null ? r.age : '—') + ' / ' + (r.sex || '—')],
            ['Civil Status', r.civil_status || '—'],
            ['Date of Birth', dob],
            ['Blood Type',   r.blood_type || r.hr_blood_type || '—'],
            ['Course / Year', r.course_year || '—'],
            ['Department',   r.department || '—'],
            ['Section',      r.section || '—'],
            ['Address',      r.address || '—'],
            ['Campus',       campusName],
            ['Allergies',    r.allergy_detail || 'None reported'],
        ]);
    }

    return '<p>Unsupported record type.</p>';
}

// Shared 2-column key/value grid used by all three detail types.
function nhDetailGrid(pairs) {
    return '<dl class="nh-detail-grid">' + pairs.map(function (p) {
        return '<dt>' + nhEsc(p[0]) + '</dt><dd>' + nhEsc(p[1]) + '</dd>';
    }).join('') + '</dl>' +
    '<style>' +
    '.nh-detail-grid{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:0.85rem;margin:4px 0}' +
    '.nh-detail-grid dt{font-weight:600;color:#374151}' +
    '.nh-detail-grid dd{margin:0;color:#1a1a1a;word-break:break-word}' +
    '</style>';
}

// -------------------------------------------------------
//  PAGE-ENTER OBSERVER
//  Re-fires the right loader whenever the user navigates to one of the
//  data-backed pages. Without this the rows would be stale (loaded once
//  on initial DOMContentLoaded) and the user would never see fresh data
//  after creating a record.
// -------------------------------------------------------
function nhSetupPageObserver() {
    var map = {
        patients:          nhLoadPatients,
        'medical-records': nhRenderMedicalRecords,
        vitals:            nhRenderVitals,
        referrals:         nhRenderReferrals,
        appointments:      nhRenderAppointments,
    };
    document.querySelectorAll('.uh-page').forEach(function (page) {
        var observer = new MutationObserver(function () {
            if (page.classList.contains('active') && page.id && map[page.id.replace(/^page-/, '')]) {
                map[page.id.replace(/^page-/, '')]();
            }
        });
        observer.observe(page, { attributes: true, attributeFilter: ['class'] });
    });
    // Initial fire for whatever page is active on load.
    var active = document.querySelector('.uh-page.active');
    if (active && active.id) {
        var key = active.id.replace(/^page-/, '');
        if (map[key]) { map[key](); }
    }
}

// ── Messages (DM) ──────────────────────────────────────
var _activeChannelId = null;
var _staffList       = [];
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
        if (nameEl) { nameEl.textContent = _activeRecipientName; }
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
        uhGet('/messages/staff-list').then(function (res) {
            if (!res.ok) { uhToast(res.error || 'Could not load staff list.', 'error'); return; }
            _staffList = (res && res.staff) ? res.staff : [];
            _staffListLoaded = true;
            nUpdateActivePresence();
        }).catch(function () { uhToast('Could not load staff list. Check your connection and try again.', 'error'); });
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
    var wrap   = document.getElementById('nUserPickerWrap');
    var picker = document.getElementById('nUserPicker');
    if (!wrap || !picker) { return; }
    if (!_staffListLoaded) { uhToast('Staff list is still loading. Please try again in a moment.', 'info'); return; }
    var user = window.__clinicUser || {};
    picker.innerHTML = '<option value="">-- Select a person --</option>' +
        _staffList.filter(function (s) { return s.id !== (user.id || 0); })
            .map(function (s) {
                return '<option value="' + Number(s.id) + '">' + uhEscapeHtml(s.name) + ' (' + uhEscapeHtml(s.role_label) + ')</option>';
            }).join('');
    wrap.style.display = 'block';
}

function nCloseUserPicker() {
    var w = document.getElementById('nUserPickerWrap');
    if (w) { w.style.display = 'none'; }
}

function nStartDm() {
    var picker = document.getElementById('nUserPicker');
    var recipientId = picker ? parseInt(picker.value, 10) : 0;
    if (!recipientId) { uhToast('Please select a person', 'error'); return; }
    var user = window.__clinicUser || {};
    uhPost('/messages/direct', { sender_id: user.id || 0, recipient_id: recipientId })
        .then(function (res) {
            if (!res || !res.success) { uhToast('Could not open conversation', 'error'); return; }
            nCloseUserPicker();
            _channelRequestToken += 1;
            nOpenChannel(res.channel.id, res.channel.recipient_name, res.channel.recipient_id, res.messages, res.channel.recipient_picture_url);
            nLoadConversations();
        }).catch(function () { uhToast('Could not open conversation', 'error'); });
}

function nLoadConversations() {
    var user = window.__clinicUser || {};
    if (!user.id) { return; }
    uhGet('/messages/conversations?staff_id=' + user.id).then(function (res) {
        if (!res.ok) {
            nhRenderDashboardMessageState('Could not load messages.');
            uhToast(res.error || 'Could not load conversations. Please try again.', 'error');
            return;
        }
        _allConversations = (res && res.conversations) ? res.conversations : [];
        nhRenderDashboardMessages(_allConversations);
        var search = (document.getElementById('nConvSearch')?.value || '').trim().toLowerCase();
        var visibleConversations = search
            ? _allConversations.filter(function (conversation) {
                return conversation.recipient_name.toLowerCase().indexOf(search) >= 0;
            })
            : _allConversations;
        nRenderConversations(visibleConversations);
        nUpdateActivePresence();
    }).catch(function () {
        nhRenderDashboardMessageState('Could not load messages. Check your connection.');
        uhToast('Could not load conversations. Check your connection and try again.', 'error');
    });
}

function nhRenderDashboardMessageState(message) {
    var container = document.getElementById('nh-dashboard-messages');
    if (container) { container.innerHTML = '<p class="pm-activity-text">' + uhEscapeHtml(message) + '</p>'; }
}

function nhRenderDashboardMessages(conversations) {
    var container = document.getElementById('nh-dashboard-messages');
    if (!container) { return; }

    var recent = conversations.filter(function (conversation) {
        return conversation.last_message;
    }).slice(0, 3);
    if (!recent.length) {
        nhRenderDashboardMessageState('No messages yet.');
        return;
    }

    var userId = Number((window.__clinicUser || {}).id);
    container.innerHTML = recent.map(function (conversation) {
        var senderId = Number(conversation.last_message_sender_id);
        var hasNewMessage = senderId !== userId && !conversation.last_message_seen_at;
        var sender = senderId === userId ? 'You' : conversation.recipient_name;
        var preview = String(conversation.last_message);
        if (preview.length > 48) { preview = preview.slice(0, 45) + '...'; }
        return '<button type="button" class="nh-dashboard-message" onclick="nhOpenDashboardConversation(' +
            Number(conversation.id) + ', ' + Number(conversation.recipient_id) + ')">' +
            '<span class="nh-dashboard-message-icon"><i class="fa fa-user"></i></span>' +
            '<span class="nh-dashboard-message-info"><span class="nh-dashboard-message-sender">' +
            uhEscapeHtml(sender) + '</span><span class="nh-dashboard-message-preview">' +
            uhEscapeHtml(preview) + '</span></span>' +
            (hasNewMessage ? '<span class="nh-dashboard-message-new">New</span>' : '') +
            '</button>';
    }).join('');
}

function nhOpenMessages() {
    uhShowPage('messages', document.querySelector('[data-title="Messages"]'));
    nMessagesInit();
}

function nhOpenDashboardConversation(channelId, recipientId) {
    var conversation = _allConversations.find(function (item) {
        return Number(item.id) === Number(channelId);
    });
    nhOpenMessages();
    nSelectConversation(channelId, conversation ? conversation.recipient_name : 'Conversation', recipientId);
}

function nFilterConversations(q) {
    var filtered = !q ? _allConversations :
        _allConversations.filter(function (c) {
            return c.recipient_name.toLowerCase().indexOf(q.toLowerCase()) >= 0;
        });
    nRenderConversations(filtered);
}

function nRenderConversations(convs) {
    var list = document.getElementById('conversationsList');
    if (!list) { return; }
    if (!convs.length) {
        list.innerHTML = '<p class="uh-chat-empty" style="padding:20px 14px">No conversations yet.<br>Click <b>New Message</b> to start one.</p>';
        return;
    }
    list.innerHTML = convs.map(function (c) {
        var initials  = uhChatInitials(c.recipient_name);
        var colorCls  = uhChatColor(c.recipient_id);
        var activeCls = _activeChannelId === c.id ? ' active' : '';
        var preview   = c.last_message ? c.last_message.substring(0, 40) + (c.last_message.length > 40 ? '...' : '') : 'No messages yet';
        var timeStr   = c.last_at ? uhFormatTime(c.last_at) : '';
        var presenceText = uhPresenceText(c);
        var presenceClass = c.is_online ? ' is-online' : ' is-offline';
        var openAttributes = ' data-message-conversation data-recipient-id="' + Number(c.recipient_id) + '"';
        var menuActions = '<button type="button" data-conversation-action="conversation">Delete conversation</button><button type="button" data-conversation-action="person">Delete person</button>';
        return '<div class="uh-conv-item' + activeCls + '" data-channel-id="' + Number(c.id) + '">' +
            '<button type="button" class="uh-conv-open"' + openAttributes + ' data-channel-id="' + Number(c.id) + '" data-recipient-name="' + uhEscapeHtml(c.recipient_name) + '" aria-label="Open conversation with ' + uhEscapeHtml(c.recipient_name) + '">' +
            '<div class="uh-conv-avatar ' + colorCls + '">' + uhChatAvatarContent(c.recipient_picture_url, initials) + '</div>' +
            '<div class="uh-conv-info">' +
                '<div class="uh-conv-name">' + uhEscapeHtml(c.recipient_name) + '</div>' +
                '<span class="uh-conv-preview">' + uhEscapeHtml(preview) + '</span>' +
                '<span class="uh-conv-presence' + presenceClass + '">' + uhEscapeHtml(presenceText) + '</span>' +
            '</div>' +
            '<div class="uh-conv-meta"><span class="uh-conv-time">' + timeStr + '</span></div>' +
            '</button><div class="uh-conv-actions"><button type="button" class="uh-conv-menu-toggle" aria-label="Conversation options" aria-expanded="false"><i class="fa fa-ellipsis" aria-hidden="true"></i></button>' +
            '<div class="uh-conv-menu" hidden>' + menuActions + '</div></div>' +
            '</div>';
    }).join('');
}

function nSelectConversation(channelId, recipientName, recipientId) {
    var requestToken = ++_channelRequestToken;
    var cachedMessages = _messageCache.get(Number(channelId));
    _channelLoadBusy = true;
    nShowConversationLoading(channelId, recipientName, recipientId);
    if (cachedMessages) { nRenderMessages(cachedMessages); }
    uhGet('/messages/' + channelId).then(function (res) {
        if (!res.ok) {
            if (requestToken === _channelRequestToken && !cachedMessages) {
                nConversationLoadFailed();
            }
            if (requestToken === _channelRequestToken) {
                uhToast(res.error || 'Could not open this conversation.', 'error');
            }
            return;
        }
        if (requestToken !== _channelRequestToken) { return; }
        var currentName = typeof res.channel_name === 'string' && res.channel_name.trim()
            ? res.channel_name
            : recipientName;
        nOpenChannel(channelId, currentName, recipientId, res.messages || [], nChatPictureUrl(recipientId));
    }).catch(function () {
        if (requestToken === _channelRequestToken) {
            if (!cachedMessages) { nConversationLoadFailed(); }
            uhToast('Could not open this conversation. Check your connection and try again.', 'error');
        }
    }).finally(function () {
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
    if (header) { header.style.display = 'flex'; }
    if (avatar) {
        avatar.innerHTML = uhChatAvatarContent(nChatPictureUrl(recipientId), uhChatInitials(name));
        avatar.className = 'uh-chat-active-avatar ' + uhChatColor(recipientId);
    }
    if (nameEl) { nameEl.textContent = name; }
    if (placeholder) { placeholder.style.display = 'none'; }
    if (list) {
        list.style.display = 'flex';
        list.innerHTML = '<p class="uh-chat-empty uh-chat-loading" role="status">Loading messages…</p>';
    }
    document.querySelectorAll('#conversationsList [data-message-conversation]').forEach(function (item) {
        item.closest('.uh-conv-item')?.classList.toggle('active', Number(item.dataset.channelId) === Number(channelId));
    });
    if (compose) { compose.style.display = 'flex'; }
}

function nConversationLoadFailed() {
    var list = document.getElementById('messagesList');
    if (list) {
        list.innerHTML = '<div class="uh-chat-load-error" role="alert">Could not load this conversation.<button type="button" class="uh-btn uh-btn-outline uh-btn-sm" onclick="nRetryConversation()">Try again</button></div>';
    }
}

function nRetryConversation() {
    if (_activeChannelId) {
        nSelectConversation(_activeChannelId, _activeRecipientName, _activeRecipientId);
    }
}

function nBindConversationClicks() {
    var list = document.getElementById('conversationsList');
    if (!list || list.dataset.conversationClicksBound) { return; }
    list.dataset.conversationClicksBound = 'true';
    list.addEventListener('click', function (event) {
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
            list.querySelectorAll('.uh-conv-menu').forEach(function (item) { item.hidden = true; });
            list.querySelectorAll('.uh-conv-menu-toggle').forEach(function (item) {
                item.setAttribute('aria-expanded', 'false');
            });
            menu.hidden = !opening;
            menuToggle.setAttribute('aria-expanded', String(opening));
            return;
        }
        var button = event.target.closest('[data-message-conversation]');
        if (!button) { return; }
        nSelectConversation(
            Number(button.dataset.channelId),
            button.dataset.recipientName,
            Number(button.dataset.recipientId),
        );
    });
}

async function nDeleteConversationAction(channelId, action, recipientName) {
    if (!await uhDeleteConversationItem(channelId, action, recipientName)) { return; }
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
    var header      = document.getElementById('nChatHeader');
    var avatar      = document.getElementById('nChatHeaderAvatar');
    var nameEl      = document.getElementById('nChatHeaderName');
    var placeholder = document.getElementById('nChatPlaceholder');
    var msgList     = document.getElementById('messagesList');
    var compose     = document.getElementById('nChatCompose');
    if (header)      { header.style.display = 'flex'; }
    if (avatar)      { avatar.innerHTML = uhChatAvatarContent(pictureUrl || nChatPictureUrl(recipientId), uhChatInitials(name)); avatar.className = 'uh-chat-active-avatar ' + uhChatColor(recipientId); }
    if (nameEl)      { nameEl.textContent = name; }
    uhSetChatPresence(nChatPresenceFor(recipientId));
    if (placeholder) { placeholder.style.display = 'none'; }
    if (msgList)     { msgList.style.display = 'flex'; }
    if (compose)     { compose.style.display = 'flex'; }
    nRenderMessages(messages || []);
}

function nRenderMessages(msgs) {
    var list = document.getElementById('messagesList');
    if (!list) { return; }
    var user = window.__clinicUser || {};
    _activeMessages = Array.isArray(msgs) ? msgs : [];
    if (_activeChannelId) { uhRememberChatMessages(_messageCache, _activeChannelId, _activeMessages); }
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
    if (!_activeChannelId) { return Promise.resolve(); }
    var channelId = _activeChannelId;
    return uhGet('/messages/' + channelId).then(function (res) {
        if (!res.ok) {
            uhToast(res.error || 'Could not load messages. Please try again.', 'error');
            return;
        }
        if (_activeChannelId === channelId) { nRenderMessages(res.messages || []); }
    }).catch(function () {
        uhToast('Could not load messages. Check your connection and try again.', 'error');
    });
}

function nSyncMessages() {
    var page = document.getElementById('page-messages');
    if (!_activeChannelId || !page || !page.classList.contains('active') || _messagePollBusy || _messageActionBusy || _channelLoadBusy) { return; }
    _messagePollBusy = true;
    var channelId = _activeChannelId;
    var list = document.getElementById('messagesList');
    var previousTop = list ? list.scrollTop : 0;
    var wasNearBottom = list ? list.scrollHeight - list.scrollTop - list.clientHeight < 80 : true;
    var changedAfter = _messageLastSync || new Date(Date.now() - 5000).toISOString();

    uhGet('/messages/' + channelId + '?changed_after=' + encodeURIComponent(changedAfter)).then(function (res) {
        if (!res.ok || _activeChannelId !== channelId) { return; }
        var messagesById = new Map(_activeMessages.map(function (message) { return [Number(message.id), message]; }));
        var changed = false;
        (res.messages || []).forEach(function (message) {
            var current = messagesById.get(Number(message.id));
            if (!current || current.updated_at !== message.updated_at
                || current.body !== message.body
                || current.is_pinned !== message.is_pinned
                || current.is_unsent !== message.is_unsent) {
                messagesById.set(Number(message.id), message);
                changed = true;
            }
        });
        if (changed) {
            var combined = Array.from(messagesById.values()).sort(function (left, right) {
                return Number(left.id) - Number(right.id);
            });
            nRenderMessages(combined);
            if (!wasNearBottom && list) { list.scrollTop = previousTop; }
            nLoadConversations();
        }
        _messageLastSync = new Date().toISOString();
    }).catch(function () {
        // Keep the last rendered messages available; the next poll retries.
    }).finally(function () {
        _messagePollBusy = false;
    });
}

function nBindMessageActions() {
    var list = document.getElementById('messagesList');
    if (!list || list.dataset.actionsBound) { return; }
    list.dataset.actionsBound = 'true';
    list.addEventListener('click', async function (event) {
        var button = event.target.closest('button[data-message-action]');
        if (!button) { return; }
        var action = button.dataset.messageAction;
        if (action === 'toggle') {
            var wasOpen = button.getAttribute('aria-expanded') === 'true';
            list.querySelectorAll('.uh-msg-menu').forEach(function (menu) { menu.hidden = true; });
            list.querySelectorAll('.uh-msg-more').forEach(function (toggle) { toggle.setAttribute('aria-expanded', 'false'); });
            if (!wasOpen) {
                var menu = button.parentElement.querySelector('.uh-msg-menu');
                if (menu) { menu.hidden = false; button.setAttribute('aria-expanded', 'true'); }
            }
            return;
        }
        if (_messageActionBusy || _channelLoadBusy) { return; }
        var message = _activeMessages.find(function (item) { return Number(item.id) === Number(button.dataset.messageId); });
        if (!message) { uhToast('This message is no longer available.', 'error'); return; }
        var channelId = _activeChannelId;
        _messageActionBusy = true;
        try {
            await uhPerformMessageAction(action, message, {
                channelId: channelId,
                userId: (window.__clinicUser || {}).id,
                staffList: function () { return _staffList; },
                refresh: nLoadMessages,
                loadConversations: nLoadConversations,
            });
        } catch (error) {
            uhToast('Could not complete that message action. Please try again.', 'error');
        } finally {
            _messageActionBusy = false;
        }
    });
}

function nSendMessage() {
    if (_channelLoadBusy) { uhToast('Please wait for the conversation to finish loading.', 'info'); return; }
    if (!_activeChannelId) { uhToast('Select a conversation first', 'error'); return; }
    var channelId = _activeChannelId;
    var input = document.getElementById('msgInput');
    var text  = input ? input.value.trim() : '';
    var attachmentInput = document.getElementById('nMessageImageInput');
    var attachment = attachmentInput && attachmentInput.files ? attachmentInput.files[0] : null;
    if (!text && !attachment) { return; }
    uhPostMessage('/messages/' + channelId, text, attachment)
        .then(function (res) {
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
                uhToast(res.error || validationMessage || res.message || 'Failed to send. Please try again.', 'error');
            }
        }).catch(function (error) { uhToast(error.message || 'Failed to send. Check your connection and try again.', 'error'); });
}
</script>
</body>
</html>