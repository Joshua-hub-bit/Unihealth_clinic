@php
    $role = $role ?? 'physician';
    $formData = $formData ?? [];
    if ($role === 'dentist') {
        $defaultSigName = 'RHIZZA FAYE T. BARCENILLA, D.M.D';
        $sigRole = 'School Dentist';
    } else {
        $defaultSigName = 'CYRILLE S. ADRANEDA, M.D';
        $sigRole = 'School Physician';
    }
    $sigName = $formData['physician_name'] ?? '';
    $sigName = $sigName !== '' ? $sigName : $defaultSigName;
    $sigLicense = $formData['physician_lic'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Referral Form</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Times New Roman', 'Georgia', serif;
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── 2x2 grid container ── */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            width: 210mm;
            height: 297mm;
            gap: 8mm;
            padding: 12mm;
        }

        .rf-card {
            border: 0.6pt solid #1a1a1a;
            padding: 3.5mm 4.5mm 3mm;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: #fff;
            border-radius: 2px;
        }

        /* ── Header ── */
        .rf-hdr {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 2mm;
            border-bottom: 0.75pt solid #111;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .rf-hdr img.logo {
            width: 14mm;
            height: 14mm;
            object-fit: contain;
            flex-shrink: 0;
            transform: translateX(1.75mm) scale(1.5);
            transform-origin: center;
        }
        .rf-ht { line-height: 1.15; flex: 1; font-family: Arial, sans-serif; min-width: 0; }
        .rf-republic { color: #003C8F; font-size: 4.5pt; font-weight: 600; }
        .rf-uni {
            color: #F6BF00;
            font-weight: 900;
            font-size: 6pt;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .rf-office {
            color: #003C8F;
            font-weight: 800;
            font-size: 5pt;
            margin-top: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .rf-addr {
            color: #003C8F;
            font-size: 4.5pt;
            font-weight: 500;
            margin-top: 0.5px;
            line-height: 1.15;
            display: flex;
            flex-direction: column;
            gap: 0.2mm;
        }
        .rf-addr .rf-addr-loc { display: block; }
        .rf-addr .rf-addr-city { display: block; }
        .rf-addr .rf-addr-zip { display: block; }

        /* ── Title ── */
        .rf-title {
            text-align: center;
            font-size: 12pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000;
            margin-bottom: 3mm;
            font-family: 'Times New Roman', serif;
        }

        /* ── Clinical fields ── */
        .rf-fields {
            display: flex;
            flex-direction: column;
            gap: 2.4mm;
            flex: 1;
            font-size: 8pt;
            line-height: 1.3;
        }
        .rf-field {
            display: flex;
            flex-direction: row;
            align-items: baseline;
            gap: 1.5mm;
        }
        .rf-field .rf-label {
            font-weight: 700;
            font-family: 'Times New Roman', serif;
            font-size: 8.5pt;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .rf-field .rf-line {
            flex: 1;
            border-bottom: 0.5pt solid #000;
            min-height: 4mm;
            padding: 0 2px;
            font-size: 8.5pt;
            font-family: 'Times New Roman', serif;
        }
        .rf-field .rf-line-sm { max-width: 28mm; flex: 0 0 28mm; }
        .rf-field .rf-line-md { max-width: 50mm; flex: 0 0 50mm; }
        .rf-field .rf-line-hide { visibility: visible; }

        .rf-field-stack {
            display: flex;
            flex-direction: column;
            gap: 0.5mm;
        }
        .rf-field-stack .rf-line {
            width: 100%;
            border-bottom: 0.5pt solid #000;
            min-height: 4mm;
            padding: 0 2px;
        }
        .rf-field-stack .rf-line-tall {
            min-height: 6mm;
        }

        /* ── Sign-off (sits directly below the Remarks field) ── */
        .rf-phys {
            margin-top: 4mm;
            font-family: 'Times New Roman', serif;
            font-size: 8pt;
            line-height: 1.35;
        }
        .rf-phys .rf-name {
            font-weight: 700;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .rf-phys .rf-role {
            font-style: italic;
            font-size: 8pt;
        }
        .rf-phys .rf-lic {
            font-size: 7.5pt;
        }

        .view-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            width: 210mm;
            margin: 12px auto;
            font-family: Arial, sans-serif;
        }
        .view-actions button {
            padding: 8px 14px;
            border: 1px solid #1a6b3a;
            border-radius: 4px;
            background: #1a6b3a;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .view-actions button.ghost { border-color: #d1d5db; background: #fff; color: #374151; }
        .single-referral-view { width: 100%; min-height: 100vh; background: #f3f4f6; }
        .single-referral-view .grid {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: auto;
            min-height: 100vh;
            gap: 0;
            padding: 16px;
        }
        .single-referral-view .rf-card {
            width: min(100%, 89mm);
            height: 128.5mm;
            min-height: 0;
            flex: 0 0 auto;
        }
        .single-referral-view .rf-card:not(:first-child) { display: none; }
        .rf-page-foot { padding-top: 1mm; }
        .rf-page-foot .rf-foot-row { display:flex; align-items:center; justify-content:flex-end; width:100%; }
        .rf-page-foot img.rf-foot-img { width:100%; height:12mm; max-height:12mm; object-fit:fill; }
        @media print {
            .view-actions { display: none; }
            .single-referral-view { width: 210mm; min-height: 297mm; }
            .single-referral-view .grid { width: 210mm; height: 297mm; min-height: 0; padding: 12mm; }
        }
    </style>
</head>
<body class="{{ !empty($viewOnly) ? 'single-referral-view' : '' }}">
@if (!empty($viewOnly))
    <div class="view-actions">
        <button class="ghost" type="button" onclick="{{ !empty($embedded) ? 'window.parent.nCloseReferralForm()' : 'window.close()' }}">Close</button>
        <button type="button" onclick="window.print()">Print</button>
    </div>
@endif
<div class="grid">

    {{-- CARD 1 (top-left) --}}
    <div class="rf-card">
        <div class="rf-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rf-ht">
                <div class="rf-republic">Republic of the Philippines</div>
                <div class="rf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rf-addr">
                    <span class="rf-addr-loc">SUNN Main Campus</span>
                    <span class="rf-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rf-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <div class="rf-title">REFERRAL FORM</div>
        <div class="rf-fields">
            <div class="rf-field">
                <span class="rf-label">Name:</span>
                <span class="rf-line">{{ $formData['name'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Age:</span>
                <span class="rf-line rf-line-sm rf-line-hide">{{ $formData['age'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Address:</span>
                <span class="rf-line rf-line-hide">{{ $formData['address'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Urgency:</span>
                <span class="rf-line rf-line-hide">{{ $formData['urgency'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Chief Complaint/s:</span>
                <span class="rf-line rf-line-hide">{{ $formData['chief_complaint'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Vital Signs:</span>
                <span class="rf-line rf-line-hide">{{ $formData['vital_signs'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Remarks:</span>
                <span class="rf-line rf-line-hide">{{ $formData['remarks'] ?? '' }}</span>
            </div>
        </div>
        <div class="rf-phys">
            <div class="rf-name">{{ $sigName }}</div>
            <div class="rf-role">{{ $sigRole }}</div>
            <div class="rf-lic">Lic. No.: {{ $sigLicense }}</div>
        </div>
        <div class="rf-page-foot">
            <div class="rf-foot-row">
                <img class="rf-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 2 (top-right) --}}
    <div class="rf-card">
        <div class="rf-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rf-ht">
                <div class="rf-republic">Republic of the Philippines</div>
                <div class="rf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rf-addr">
                    <span class="rf-addr-loc">SUNN Main Campus</span>
                    <span class="rf-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rf-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <div class="rf-title">REFERRAL FORM</div>
        <div class="rf-fields">
            <div class="rf-field">
                <span class="rf-label">Name:</span>
                <span class="rf-line">{{ $formData['name'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Age:</span>
                <span class="rf-line rf-line-sm rf-line-hide">{{ $formData['age'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Address:</span>
                <span class="rf-line rf-line-hide">{{ $formData['address'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Urgency:</span>
                <span class="rf-line rf-line-hide">{{ $formData['urgency'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Chief Complaint/s:</span>
                <span class="rf-line rf-line-hide">{{ $formData['chief_complaint'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Vital Signs:</span>
                <span class="rf-line rf-line-hide">{{ $formData['vital_signs'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Remarks:</span>
                <span class="rf-line rf-line-hide">{{ $formData['remarks'] ?? '' }}</span>
            </div>
        </div>
        <div class="rf-phys">
            <div class="rf-name">{{ $sigName }}</div>
            <div class="rf-role">{{ $sigRole }}</div>
            <div class="rf-lic">Lic. No.: {{ $sigLicense }}</div>
        </div>
        <div class="rf-page-foot">
            <div class="rf-foot-row">
                <img class="rf-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 3 (bottom-left) --}}
    <div class="rf-card">
        <div class="rf-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rf-ht">
                <div class="rf-republic">Republic of the Philippines</div>
                <div class="rf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rf-addr">
                    <span class="rf-addr-loc">SUNN Main Campus</span>
                    <span class="rf-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rf-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <div class="rf-title">REFERRAL FORM</div>
        <div class="rf-fields">
            <div class="rf-field">
                <span class="rf-label">Name:</span>
                <span class="rf-line">{{ $formData['name'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Age:</span>
                <span class="rf-line rf-line-sm rf-line-hide">{{ $formData['age'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Address:</span>
                <span class="rf-line rf-line-hide">{{ $formData['address'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Urgency:</span>
                <span class="rf-line rf-line-hide">{{ $formData['urgency'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Chief Complaint/s:</span>
                <span class="rf-line rf-line-hide">{{ $formData['chief_complaint'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Vital Signs:</span>
                <span class="rf-line rf-line-hide">{{ $formData['vital_signs'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Remarks:</span>
                <span class="rf-line rf-line-hide">{{ $formData['remarks'] ?? '' }}</span>
            </div>
        </div>
        <div class="rf-phys">
            <div class="rf-name">{{ $sigName }}</div>
            <div class="rf-role">{{ $sigRole }}</div>
            <div class="rf-lic">Lic. No.: {{ $sigLicense }}</div>
        </div>
        <div class="rf-page-foot">
            <div class="rf-foot-row">
                <img class="rf-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 4 (bottom-right) --}}
    <div class="rf-card">
        <div class="rf-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rf-ht">
                <div class="rf-republic">Republic of the Philippines</div>
                <div class="rf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rf-addr">
                    <span class="rf-addr-loc">SUNN Main Campus</span>
                    <span class="rf-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rf-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <div class="rf-title">REFERRAL FORM</div>
        <div class="rf-fields">
            <div class="rf-field">
                <span class="rf-label">Name:</span>
                <span class="rf-line">{{ $formData['name'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Age:</span>
                <span class="rf-line rf-line-sm rf-line-hide">{{ $formData['age'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Address:</span>
                <span class="rf-line rf-line-hide">{{ $formData['address'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Urgency:</span>
                <span class="rf-line rf-line-hide">{{ $formData['urgency'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Chief Complaint/s:</span>
                <span class="rf-line rf-line-hide">{{ $formData['chief_complaint'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Vital Signs:</span>
                <span class="rf-line rf-line-hide">{{ $formData['vital_signs'] ?? '' }}</span>
            </div>
            <div class="rf-field">
                <span class="rf-label">Remarks:</span>
                <span class="rf-line rf-line-hide">{{ $formData['remarks'] ?? '' }}</span>
            </div>
        </div>
        <div class="rf-phys">
            <div class="rf-name">{{ $sigName }}</div>
            <div class="rf-role">{{ $sigRole }}</div>
            <div class="rf-lic">Lic. No.: {{ $sigLicense }}</div>
        </div>
        <div class="rf-page-foot">
            <div class="rf-foot-row">
                <img class="rf-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

</div>
@if (!empty($autoPrint))
    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () { window.print(); }, 200);
        });
    </script>
@endif
</body>
</html>
