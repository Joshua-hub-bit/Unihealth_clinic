@php
    $student = $record->student ?? null;
    $campus  = $record->campus  ?? null;

    $studentName = $student
        ? trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '') . ' ' . ($student->middle_name ?? ''))
        : '—';

    $visitDate = $record->visit_date
        ? \Carbon\Carbon::parse($record->visit_date)->format('F d, Y')
        : '—';
    $visitTime = $record->visit_time
        ? \Carbon\Carbon::parse($record->visit_time)->format('g:i A')
        : '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Vital Signs #{{ $record->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        body {
            font-family: 'Arial', sans-serif;
            background: #fff;
            color: #111;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-page { width: 210mm; min-height: 297mm; padding: 18mm 20mm; }
        .print-header { display: flex; gap: 4mm; align-items: center; border-bottom: 1.5pt solid #111; padding-bottom: 4mm; margin-bottom: 6mm; }
        .print-header img { width: 26mm; height: 26mm; object-fit: contain; }
        .print-header .republic { color: #003C8F; font-size: 8pt; font-weight: 600; }
        .print-header .university { color: #F6BF00; font-weight: 900; font-size: 13pt; letter-spacing: .3px; text-transform: uppercase; }
        .print-header .office { color: #003C8F; font-weight: 800; font-size: 10pt; margin-top: 1px; }
        .print-header .address { color: #003C8F; font-size: 7.5pt; font-weight: 500; margin-top: 3px; line-height: 1.4; }

        .doc-title { text-align: center; font-size: 13pt; font-weight: 900; letter-spacing: 2px; text-decoration: underline; margin: 4mm 0 5mm; }
        .doc-sub { text-align: center; font-size: 9pt; color: #444; margin-bottom: 6mm; }

        .row { display: flex; gap: 4mm; margin-bottom: 2.5mm; font-size: 10pt; }
        .row .lbl { font-weight: 700; min-width: 32mm; }
        .row .val { flex: 1; border-bottom: 1pt solid #111; padding: 0 2px; min-height: 5mm; }
        .row-half { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }

        .vital-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm;
            margin: 6mm 0;
        }
        .vital-card {
            border: 1pt solid #111;
            padding: 4mm 5mm;
            border-radius: 4px;
            text-align: center;
        }
        .vital-card .lbl { font-size: 8pt; font-weight: 700; color: #003C8F; text-transform: uppercase; letter-spacing: 1px; }
        .vital-card .val { font-size: 18pt; font-weight: 800; margin-top: 2mm; }
        .vital-card .unit { font-size: 9pt; color: #444; margin-top: 1mm; }

        .footer { margin-top: 14mm; text-align: center; }
        .footer .line { border-top: 0.6pt solid #444; padding-top: 1.5mm; font-size: 8.5pt; color: #444; }

        .print-actions { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 6mm; }
        .print-actions button {
            padding: 6px 14px; font-size: 9pt; font-weight: 600;
            border: 1px solid #1a6b3a; background: #1a6b3a; color: #fff;
            border-radius: 4px; cursor: pointer;
        }
        .print-actions button.ghost { background: #fff; color: #374151; border-color: #d1d5db; }
        @media print { .print-actions { display: none; } }
    </style>
</head>
<body>
<div class="print-actions">
    <button class="ghost" onclick="window.close()">Close</button>
    <button onclick="window.print()"><i class="fa fa-print"></i> Print</button>
</div>

<div class="print-page">
    <div class="print-header">
        <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
        <div>
            <div class="republic">Republic of the Philippines</div>
            <div class="university">State University of Northern Negros</div>
            <div class="office">Medical and Dental Services Office</div>
            <div class="address">SUNN Main Campus, Sagay City, Negros Occidental, Philippines 6122</div>
        </div>
    </div>

    <div class="doc-title">VITAL SIGNS RECORD</div>
    <div class="doc-sub">Entry #{{ $record->id }} · {{ $visitDate }} at {{ $visitTime }}</div>

    <div class="row">
        <div class="lbl">Patient</div>
        <div class="val">{{ $studentName }}</div>
    </div>
    <div class="row-half">
        <div class="row" style="margin:0">
            <div class="lbl">Student ID</div>
            <div class="val">{{ $student->student_id ?? '—' }}</div>
        </div>
        <div class="row" style="margin:0">
            <div class="lbl">Age / Sex</div>
            <div class="val">{{ $student->age ?? '—' }} / {{ $student->sex ?? '—' }}</div>
        </div>
    </div>
    <div class="row">
        <div class="lbl">Campus</div>
        <div class="val">{{ $campus->name ?? '—' }}</div>
    </div>

    <div class="vital-grid">
        <div class="vital-card">
            <div class="lbl">Blood Pressure</div>
            <div class="val">{{ $record->bp ?: '—' }}</div>
            <div class="unit">mmHg</div>
        </div>
        <div class="vital-card">
            <div class="lbl">Temperature</div>
            <div class="val">{{ $record->temp !== null ? $record->temp : '—' }}</div>
            <div class="unit">°C</div>
        </div>
        <div class="vital-card">
            <div class="lbl">Pulse Rate</div>
            <div class="val">{{ $record->pulse ?? '—' }}</div>
            <div class="unit">bpm</div>
        </div>
        <div class="vital-card">
            <div class="lbl">Resp. Rate</div>
            <div class="val">{{ $record->rr ?? '—' }}</div>
            <div class="unit">/min</div>
        </div>
        <div class="vital-card">
            <div class="lbl">O₂ Saturation</div>
            <div class="val">{{ $record->o2_sat ?? '—' }}</div>
            <div class="unit">%</div>
        </div>
    </div>

    <div class="footer">
        <div class="line">SUNN Clinic — Vital Signs Record · Generated {{ now()->timezone('Asia/Manila')->format('F d, Y g:i A') }}</div>
    </div>
</div>
</body>
</html>
