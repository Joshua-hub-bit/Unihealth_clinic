@php
    $role = ($role ?? 'physician') === 'dentist' ? 'dentist' : 'physician';
    if (!empty($phys)) {
        $physician = $phys;
    } elseif ($role === 'dentist') {
        $physician = 'RHIZZA FAYE T. BARCENILLA, D.M.D';
    } else {
        $physician = 'CYRILLE S. ADRANEDA, M.D';
    }
    $designation = $role === 'dentist' ? 'School Dentist' : 'School Physician';
    $physicianEsc = htmlspecialchars($physician, ENT_QUOTES, 'UTF-8');
    $licenseText = (string) ($license ?? '');
    $selectedTests = array_fill_keys(is_array($tests ?? null) ? $tests : [], true);

    $nameEsc    = htmlspecialchars((string) ($name ?? ''), ENT_QUOTES, 'UTF-8');
    $ageEsc     = htmlspecialchars((string) ($age ?? ''), ENT_QUOTES, 'UTF-8');
    $sexEsc     = htmlspecialchars((string) ($sex ?? ''), ENT_QUOTES, 'UTF-8');
    $dobEsc     = htmlspecialchars((string) ($dob ?? ''), ENT_QUOTES, 'UTF-8');
    $addrEsc    = htmlspecialchars((string) ($address ?? ''), ENT_QUOTES, 'UTF-8');
    $dateEsc    = htmlspecialchars((string) ($date ?? ''), ENT_QUOTES, 'UTF-8');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laboratory Request Form</title>
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

        .lab-card {
            border: 0.6pt solid #1a1a1a;
            padding: 3mm 4mm;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: #fff;
            border-radius: 2px;
        }

        /* ── Header ── */
        .lc-hdr {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 2mm;
            border-bottom: 0.75pt solid #111;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .lc-hdr img.logo {
            width: 14mm;
            height: 14mm;
            object-fit: contain;
            flex-shrink: 0;
            transform: translateX(1.75mm) scale(1.5);
            transform-origin: center;
        }
        .lc-ht { line-height: 1.15; flex: 1; font-family: Arial, sans-serif; min-width: 0; }
        .lc-republic { color: #003C8F; font-size: 4.5pt; font-weight: 600; }
        .lc-uni {
            color: #F6BF00;
            font-weight: 900;
            font-size: 6pt;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .lc-office {
            color: #003C8F;
            font-weight: 800;
            font-size: 5pt;
            margin-top: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .lc-addr {
            color: #003C8F;
            font-size: 4.5pt;
            font-weight: 500;
            margin-top: 0.5px;
            line-height: 1.15;
            display: flex;
            flex-direction: column;
            gap: 0.2mm;
        }
        .lc-addr .lc-addr-loc { display: block; }
        .lc-addr .lc-addr-city { display: block; }
        .lc-addr .lc-addr-zip { display: block; }

        /* ── Title ── */
        .lc-title {
            text-align: center;
            font-size: 10pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #000;
            margin-bottom: 2mm;
            font-family: 'Times New Roman', serif;
        }

        /* ── Patient bar (compact, inline) ── */
        .lc-patient {
            display: flex;
            flex-direction: column;
            gap: 1mm;
            margin-bottom: 2mm;
            font-size: 6.5pt;
        }
        .lc-prow {
            display: flex;
            align-items: baseline;
            gap: 1.5mm;
            line-height: 1.3;
        }
        .lc-plabel { font-weight: 700; white-space: nowrap; font-family: 'Times New Roman', serif; }
        .lc-pline {
            flex: 1;
            border-bottom: 0.4pt solid #000;
            min-height: 3.2mm;
            padding: 0 1.5px;
            font-size: 6.8pt;
        }
        .lc-pline-sm { max-width: 16mm; flex: 0 0 16mm; }
        .lc-pline-md { max-width: 28mm; flex: 0 0 28mm; }

        /* ── Two-column test grid ── */
        .lc-tests {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm 2.5mm;
            font-family: 'Times New Roman', serif;
        }
        .lc-col {
            display: flex;
            flex-direction: column;
            gap: 1mm;
        }
        .lc-cat {
            display: flex;
            flex-direction: column;
            gap: 0.3mm;
        }
        .lc-cat-title {
            font-size: 6.5pt;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: #000;
            margin-bottom: 0.3mm;
            border-bottom: 0.4pt solid #000;
            padding-bottom: 0.2mm;
        }
        .lc-item {
            display: flex;
            align-items: baseline;
            gap: 1.2mm;
            font-size: 6pt;
            line-height: 1.15;
        }
        .lc-box {
            width: 2.2mm;
            height: 2.2mm;
            border: 0.5pt solid #000;
            flex-shrink: 0;
            display: inline-block;
            position: relative;
            top: -0.2mm;
        }
        .lc-box-checked {
            background: #000;
            color: #fff;
            font-size: 5pt;
            line-height: 2.2mm;
            text-align: center;
        }
        .lc-item .lc-name { flex: 1; }
        .lc-item .lc-sub { color: #222; font-style: italic; font-size: 5.5pt; }

        .lc-request-meta {
            margin-top: 1mm;
            font-family: 'Times New Roman', serif;
            font-size: 6.5pt;
            line-height: 1.3;
        }
        .lc-request-meta-row {
            display: flex;
            align-items: baseline;
            gap: 1.5mm;
        }
        .lc-request-meta-label {
            font-weight: 700;
            white-space: nowrap;
        }
        .lc-request-date {
            flex: 0 0 28mm;
            border-bottom: 0.4pt solid #000;
            min-height: 3.2mm;
            padding: 0 1.5px;
        }
        .lc-request-physician { font-weight: 700; }
    </style>
</head>
<body>
<div class="grid">

    @for ($i = 0; $i < 4; $i++)
    <div class="lab-card">
        <div class="lc-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="lc-ht">
                <div class="lc-republic">Republic of the Philippines</div>
                <div class="lc-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="lc-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="lc-addr">
                    <span class="lc-addr-loc">SUNN Main Campus</span>
                    <span class="lc-addr-city">Sagay City, Negros Occidental</span>
                    <span class="lc-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <div class="lc-title">LABORATORY REQUEST FORM</div>

        <div class="lc-patient">
            <div class="lc-prow">
                <span class="lc-plabel">Name:</span>
                <span class="lc-pline">{!! $nameEsc !!}</span>
            </div>
            <div class="lc-prow">
                <span class="lc-plabel">Age:</span>
                <span class="lc-pline lc-pline-sm">{!! $ageEsc !!}</span>
                <span class="lc-plabel">Sex:</span>
                <span class="lc-pline lc-pline-sm">{!! $sexEsc !!}</span>
                <span class="lc-plabel">DOB:</span>
                <span class="lc-pline lc-pline-md">{!! $dobEsc !!}</span>
            </div>
            <div class="lc-prow">
                <span class="lc-plabel">Address:</span>
                <span class="lc-pline">{!! $addrEsc !!}</span>
            </div>
        </div>

        <div class="lc-tests">
            <div class="lc-col">
                <div class="lc-cat">
                    <div class="lc-cat-title">HEMATOLOGY</div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_cbc']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_cbc']) ? '✓' : '' }}</span><span class="lc-name">CBC</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_cbc_plt']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_cbc_plt']) ? '✓' : '' }}</span><span class="lc-name">CBC WITH PLATELET</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_platelet']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_platelet']) ? '✓' : '' }}</span><span class="lc-name">PLATELET COUNT</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_ctbt']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_ctbt']) ? '✓' : '' }}</span><span class="lc-name">CT, BT</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_esr']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_esr']) ? '✓' : '' }}</span><span class="lc-name">ESR</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_abo']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_abo']) ? '✓' : '' }}</span><span class="lc-name">ABO TYPING</span></div>
                </div>
                <div class="lc-cat">
                    <div class="lc-cat-title">MICROSCOPY</div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_urinalysis']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_urinalysis']) ? '✓' : '' }}</span><span class="lc-name">URINALYSIS</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_fecalysis']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_fecalysis']) ? '✓' : '' }}</span><span class="lc-name">FECALYSIS</span></div>
                </div>
                <div class="lc-cat">
                    <div class="lc-cat-title">SEROLOGY</div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_hbsag']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_hbsag']) ? '✓' : '' }}</span><span class="lc-name">HbsAg</span></div>
                    <div class="lc-item">
                        <span class="lc-box{{ isset($selectedTests['lab_t_preg']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_preg']) ? '✓' : '' }}</span>
                        <span class="lc-name">Pregnancy Test</span>
                        <span class="lc-sub">urine / blood</span>
                    </div>
                </div>
            </div>

            <div class="lc-col">
                <div class="lc-cat">
                    <div class="lc-cat-title">CHEMISTRY</div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_bun']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_bun']) ? '✓' : '' }}</span><span class="lc-name">BUN</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_creatinine']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_creatinine']) ? '✓' : '' }}</span><span class="lc-name">CREATININE</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_sgpt']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_sgpt']) ? '✓' : '' }}</span><span class="lc-name">SGPT / SGOT</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_rbs']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_rbs']) ? '✓' : '' }}</span><span class="lc-name">RBS</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_fbs']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_fbs']) ? '✓' : '' }}</span><span class="lc-name">FBS</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_lipid']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_lipid']) ? '✓' : '' }}</span><span class="lc-name">LIPID PROFILE</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_uric']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_uric']) ? '✓' : '' }}</span><span class="lc-name">URIC ACID</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_hba1c']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_hba1c']) ? '✓' : '' }}</span><span class="lc-name">HBA1C</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_tsh']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_tsh']) ? '✓' : '' }}</span><span class="lc-name">TSH</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_t3t4']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_t3t4']) ? '✓' : '' }}</span><span class="lc-name">T3, T4</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_sodium']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_sodium']) ? '✓' : '' }}</span><span class="lc-name">SODIUM</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_potassium']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_potassium']) ? '✓' : '' }}</span><span class="lc-name">POTASSIUM</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_chloride']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_chloride']) ? '✓' : '' }}</span><span class="lc-name">CHLORIDE</span></div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_calcium']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_calcium']) ? '✓' : '' }}</span><span class="lc-name">CALCIUM</span></div>
                </div>
                <div class="lc-cat">
                    <div class="lc-cat-title">OTHERS</div>
                    <div class="lc-item"><span class="lc-box{{ isset($selectedTests['lab_t_xray']) ? ' lc-box-checked' : '' }}">{{ isset($selectedTests['lab_t_xray']) ? '✓' : '' }}</span><span class="lc-name">CHEST XRAY-PA</span></div>
                    <div class="lc-item">
                        <span class="lc-box"></span>
                        <span class="lc-line" style="flex:1; border-bottom: 0.4pt solid #000; min-height: 3mm;"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-request-meta">
            <div class="lc-request-meta-row">
                <span class="lc-request-meta-label">Date Requested:</span>
                <span class="lc-request-date">{!! $dateEsc !!}</span>
            </div>
            <div class="lc-request-meta-row">
                <span class="lc-request-meta-label">Requesting {{ $role === 'dentist' ? 'Dentist' : 'Physician' }}:</span>
                <span class="lc-request-physician">{!! $physicianEsc !!}</span>
            </div>
            <div class="lc-request-meta-row">
                <span class="lc-request-meta-label">Designation:</span>
                <span>{{ $designation }}</span>
                <span class="lc-request-meta-label">License No.:</span>
                <span>{{ $licenseText }}</span>
            </div>
        </div>
    </div>
    @endfor

</div>
</body>
</html>
