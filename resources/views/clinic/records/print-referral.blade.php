@php
    $embedded = $embedded ?? false;
    $student = $record->student;
    $clinicVisit = $student?->latestClinicVisit;
    $healthRecord = $student?->latestHealthRecord;

    $studentName = $student
        ? trim(implode(' ', array_filter([
            $student->first_name,
            $student->middle_name,
            $student->last_name,
        ])))
        : '—';
    $referredBy = $record->referredBy;
    $referredByName = $record->signature_name ?: ($referredBy
        ? trim(implode(' ', array_filter([$referredBy->first_name, $referredBy->last_name])))
        : '—');
    $age = $student?->age ?? $healthRecord?->age;

    $bloodPressure = $clinicVisit?->bp ?: $healthRecord?->bp;
    $temperature = $clinicVisit?->temp ?: $healthRecord?->temp;
    $pulse = $clinicVisit?->pulse ?: $healthRecord?->pr;
    $vitalSigns = array_filter([
        $bloodPressure ? "BP: {$bloodPressure}" : null,
        $temperature ? "Temp: {$temperature} °C" : null,
        $pulse ? "PR: {$pulse} bpm" : null,
        $clinicVisit?->rr ? "RR: {$clinicVisit->rr}/min" : null,
        $clinicVisit?->o2_sat ? "SpO2: {$clinicVisit->o2_sat}%" : null,
    ]);
    $vitalSigns = $record->vital_signs ?: implode(' · ', $vitalSigns);

    $remarks = collect([
        $record->management_given ? 'Management given: '.$record->management_given : null,
        $record->diagnosis ? 'Diagnosis: '.$record->diagnosis : null,
        $record->history ? 'History: '.$record->history : null,
    ])->filter()->implode("\n");
    $signatureRole = $record->signature_role ?: ($referredBy?->role
        ? ucfirst(str_replace('_', ' ', $referredBy->role))
        : 'Clinic Personnel');
    $signatureLicenseNo = $record->signature_license_no ?: $referredBy?->license_no;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Referral Form</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            background: #f3f4f6;
            color: #111;
            font-family: "Times New Roman", Georgia, serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            max-width: 760px;
            margin: 16px auto 0;
            padding: 0 16px;
        }
        .print-actions button {
            padding: 7px 14px;
            border: 1px solid #1a6b3a;
            border-radius: 4px;
            background: #1a6b3a;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .print-actions button.ghost { border-color: #d1d5db; background: #fff; color: #374151; }
        .print-page {
            display: flex;
            min-height: 100vh;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }
        .referral-card {
            display: flex;
            flex-direction: column;
            width: min(100%, 340px);
            min-height: 486px;
            padding: 13px 16px 11px;
            border: 0.6pt solid #1a1a1a;
            border-radius: 2px;
            background: #fff;
            box-shadow: 0 3px 18px rgb(0 0 0 / 12%);
        }
        .rf-header { display: flex; align-items: center; gap: 7px; }
        .rf-logo { width: 53px; height: 53px; flex: 0 0 53px; object-fit: fill; }
        .rf-header-text { flex: 1; line-height: 1.22; }
        .rf-republic { color: #003c8f; font-size: 8px; }
        .rf-university { color: #f5a623; font-size: 9.3px; font-weight: 900; letter-spacing: .2px; }
        .rf-office { margin-top: 1px; color: #003c8f; font-size: 9.3px; font-weight: 800; }
        .rf-address { margin-top: 2px; color: #003c8f; font-size: 7.3px; line-height: 1.35; }
        .rf-divider { width: 100%; margin: 7px 0 8px; border: 0; border-top: 0.5pt solid #111; }
        .rf-title { margin: 0 0 9px; text-align: center; font-size: 16px; font-weight: 900; letter-spacing: 1.5px; }
        .rf-fields { display: flex; flex: 1; flex-direction: column; gap: 7px; font-size: 10px; line-height: 1.3; }
        .rf-field { display: flex; align-items: baseline; gap: 5px; min-width: 0; }
        .rf-label { flex: 0 0 auto; font-weight: 700; white-space: nowrap; }
        .rf-value {
            flex: 1;
            min-width: 0;
            min-height: 16px;
            padding: 0 2px 1px;
            border-bottom: 0.5pt solid #000;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }
        .rf-signature { margin-top: auto; padding-top: 24px; font-size: 9px; line-height: 1.35; }
        .rf-signature-name { font-weight: 700; text-transform: uppercase; }
        .rf-signature-role, .rf-license { font-size: 8px; }
        .rf-footer { padding-top: 5px; }
        .rf-footer img { display: block; width: 100%; height: 45px; object-fit: fill; }
        @media (max-width: 380px) {
            .print-page { padding: 8px; }
            .referral-card { min-height: 0; }
        }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .print-page { width: 210mm; height: 297mm; min-height: 0; padding: 0; }
            .referral-card {
                width: 89mm;
                height: 128.5mm;
                min-height: 0;
                padding: 3.5mm 4.5mm 3mm;
                box-shadow: none;
            }
            .rf-logo { width: 14mm; height: 14mm; flex-basis: 14mm; }
            .rf-republic { font-size: 6pt; }
            .rf-university, .rf-office { font-size: 7pt; }
            .rf-address { font-size: 5.5pt; }
            .rf-title { font-size: 12pt; margin-bottom: 3mm; }
            .rf-fields { gap: 2.4mm; font-size: 8pt; }
            .rf-label, .rf-value { font-size: 8.5pt; }
            .rf-value { min-height: 4mm; }
            .rf-signature { padding-top: 4mm; font-size: 8pt; }
            .rf-signature-role { font-size: 8pt; }
            .rf-license { font-size: 7.5pt; }
            .rf-footer { padding-top: 1mm; }
            .rf-footer img { height: 12mm; }
        }
    </style>
</head>
<body @if ($embedded) class="embedded" @endif>
@unless ($embedded)
    <div class="print-actions">
        <button class="ghost" type="button" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print</button>
    </div>
@endunless

<main class="print-page">
    <article class="referral-card">
        <header class="rf-header">
            <img class="rf-logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN logo"/>
            <div class="rf-header-text">
                <div class="rf-republic">Republic of the Philippines</div>
                <div class="rf-university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rf-address">SUNN Main Campus<br/>Sagay City, Negros Occidental<br/>Philippines 6122</div>
            </div>
        </header>

        <hr class="rf-divider"/>
        <h1 class="rf-title">REFERRAL FORM</h1>

        <section class="rf-fields" aria-label="Referral details">
            <div class="rf-field"><span class="rf-label">Name:</span><span class="rf-value">{{ $studentName }}</span></div>
            <div class="rf-field"><span class="rf-label">Age:</span><span class="rf-value">{{ $age ?: '—' }}</span></div>
            <div class="rf-field"><span class="rf-label">Address:</span><span class="rf-value">{{ $student?->address ?: '—' }}</span></div>
            <div class="rf-field"><span class="rf-label">Chief Complaint/s:</span><span class="rf-value">{{ $record->reason ?: '—' }}</span></div>
            <div class="rf-field"><span class="rf-label">Vital Signs:</span><span class="rf-value">{{ $vitalSigns ?: 'Not recorded' }}</span></div>
            <div class="rf-field"><span class="rf-label">Remarks:</span><span class="rf-value">{{ $remarks ?: '—' }}</span></div>
        </section>

        <section class="rf-signature" aria-label="Referrer signature">
            <div class="rf-signature-name">{{ $referredByName }}</div>
            <div class="rf-signature-role">{{ $signatureRole }}</div>
            <div class="rf-license">Lic. No. {{ $signatureLicenseNo ?: '—' }}</div>
        </section>

        <footer class="rf-footer">
            <img src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
        </footer>
    </article>
</main>
</body>
</html>
