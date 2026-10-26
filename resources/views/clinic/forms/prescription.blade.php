@php
    $role = $role ?? 'physician';
    if ($role === 'dentist') {
        $defaultSigName = 'Rhizza Faye T. Barcenilla, D.M.D';
        $defaultSigRole = 'School Dentist';
        $defaultSigLic = '0056828';
    } else {
        $defaultSigName = 'Cyrille S. Adraneda, M.D';
        $defaultSigRole = 'School Physician';
        $defaultSigLic = '85606';
    }
    $sigName = $physician_name ?? $defaultSigName;
    $sigRole = $defaultSigRole;
    $sigLic = $physician_lic ?? $defaultSigLic;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Prescription Form</title>
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

        .rx-card {
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
        .rx-hdr {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 2mm;
            border-bottom: 0.75pt solid #111;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .rx-hdr img.logo {
            width: 14mm;
            height: 14mm;
            object-fit: contain;
            flex-shrink: 0;
            transform: translateX(1.75mm) scale(1.5);
            transform-origin: center;
        }
        .rx-ht { line-height: 1.15; flex: 1; font-family: Arial, sans-serif; min-width: 0; }
        .rx-republic {
            color: #003C8F;
            font-size: 4.5pt;
            font-weight: 600;
        }
        .rx-uni {
            color: #F6BF00;
            font-weight: 900;
            font-size: 6pt;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
            
        }
        .rx-office {
            color: #003C8F;
            font-weight: 800;
            font-size: 5pt;
            margin-top: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
            
        }
        .rx-addr {
            color: #003C8F;
            font-size: 4.5pt;
            font-weight: 500;
            margin-top: 0.5px;
            line-height: 1.15;
            display: flex;
            flex-direction: column;
            gap: 0.2mm;
        }
        .rx-addr .rx-addr-loc { display: block; }
        .rx-addr .rx-addr-city { display: block; }
        .rx-addr .rx-addr-zip  { display: block; }

        .rx-divider {
            border: none;
            border-top: 0.5pt solid #111;
            margin: 1.8mm 0 2mm;
        }

        /* ── Title ── */
        .rx-title {
            text-align: center;
            font-size: 11pt;
            font-weight: 900;
            text-decoration: underline;
            letter-spacing: 1.5px;
            color: #000;
            margin-bottom: 2.5mm;
            font-family: 'Times New Roman', serif;
        }

        /* ── Patient Bar ── */
        .rx-patient {
            display: flex;
            flex-direction: row;
            align-items: baseline;
            gap: 2.5mm;
            font-size: 7.5pt;
            margin-bottom: 2mm;
            flex-wrap: wrap;
        }
        .rx-patient .field {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
        }
        .rx-patient .label { font-weight: 700; }
        .rx-patient .ul {
            display: inline-block;
            border-bottom: 0.5pt solid #000;
            min-width: 22mm;
            height: 3.5mm;
            margin-left: 1.2px;
        }
        .rx-patient .ul-age { min-width: 10mm; }
        .rx-patient .ul-date { min-width: 18mm; }
        .rx-patient .ul-name { max-width: 50mm; }

        /* ── Rx Body ── */
        .rx-body {
            display: flex;
            flex-direction: row;
            gap: 3mm;
            margin-top: 1mm;
            padding: 1.5mm 2mm;
            flex: 1;
            min-height: 0;
        }
        .rx-sym {
            font-family: 'Times New Roman', serif;
            font-size: 26pt;
            font-weight: 700;
            line-height: 1;
            color: #000;
            flex-shrink: 0;
            padding-right: 2.5mm;
        }
        .rx-lines {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5mm 0 1mm;
        }
        .rx-line {
            border-bottom: 0.4pt solid #555;
            height: 0;
            visibility: hidden;
        }
        .rx-notes {
            flex: 1;
            min-width: 0;
            font-size: 8pt;
            line-height: 1.5;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            color: #000;
        }

        /* ── Signature / Footer ── */
        .rx-foot {
            margin-top: 2mm;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-end;
            gap: 4mm;
        }
        .rx-phys {
            flex: 1;
            font-size: 7pt;
            line-height: 1.35;
        }
        .rx-phys .sig-line {
            display: none;
        }
        .rx-phys .name { font-weight: 700; font-size: 7.5pt; }
        .rx-phys .role { font-style: italic; font-size: 6.5pt; }
        .rx-phys .lic  { font-size: 6.5pt; }
        .rx-phys .ptr  { font-size: 6.5pt; }
        .rx-phys .date { font-size: 6.5pt; }

        .rx-badges {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            gap: 1.2mm;
            max-width: 48%;
            flex-shrink: 0;
        }
        .rx-badge {
            font-size: 4.6pt;
            font-weight: 800;
            color: #003C8F;
            text-align: center;
            line-height: 1.1;
            letter-spacing: 0.2px;
            flex: 1;
        }
        .rx-badge .acc { color: #F5A623; }
        .rx-badge small { display: block; font-size: 3.8pt; font-weight: 500; color: #555; }

        /* ── Bottom Footer (matches medcert) ── */
        .rx-page-foot {
            margin-top: auto;
            padding-top: 1mm;
        }
        .rx-page-foot .rx-tagline {
            text-align: center;
            font-style: italic;
            font-weight: 700;
            color: #003C8F;
            font-size: 7pt;
            letter-spacing: 0.4px;
            margin-bottom: 1mm;
        }
        .rx-page-foot .rx-foot-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
        }
        .rx-page-foot img.rx-foot-img {
            width: 100%;
            height: 13mm;
            max-height: 13mm;
            object-fit: fill;
        }
    </style>
</head>
<body>
<div class="grid">

    {{-- CARD 1 (top-left) --}}
    <div class="rx-card">
        <div class="rx-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rx-ht">
                <div class="rx-republic">Republic of the Philippines</div>
                <div class="rx-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rx-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rx-addr">
                    <span class="rx-addr-loc">SUNN Main Campus</span>
                    <span class="rx-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rx-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <hr class="rx-divider"/>
        <div class="rx-title">PRESCRIPTION FORM</div>
        <div class="rx-patient">
            <span class="field"><span class="label">Name:</span><span class="ul ul-name">{{ $full_name ?? '' }}</span></span>
            <span class="field"><span class="label">Age:</span><span class="ul ul-age">{{ $age ?? '' }}</span></span>
            <span class="field"><span class="label">Date:</span><span class="ul ul-date">{{ $date ?? '' }}</span></span>
        </div>
        <div class="rx-body">
            <div class="rx-sym">℞</div>
            @if (!empty($notes))
                <div class="rx-notes">{{ $notes }}</div>
            @else
            <div class="rx-lines">
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
            </div>
            @endif
        </div>
        <div class="rx-foot">
            <div class="rx-phys">
                <div class="sig-line"></div>
                <div class="name">{{ $sigName }}</div>
                <div class="role">{{ $sigRole }}</div>
                <div class="lic">Lic. No.: {{ $sigLic }}</div>
            </div>
        </div>
        <div class="rx-page-foot">
            <div class="rx-foot-row">
                <img class="rx-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 2 (top-right) --}}
    <div class="rx-card">
        <div class="rx-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rx-ht">
                <div class="rx-republic">Republic of the Philippines</div>
                <div class="rx-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rx-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rx-addr">
                    <span class="rx-addr-loc">SUNN Main Campus</span>
                    <span class="rx-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rx-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <hr class="rx-divider"/>
        <div class="rx-title">PRESCRIPTION FORM</div>
        <div class="rx-patient">
            <span class="field"><span class="label">Name:</span><span class="ul ul-name">{{ $full_name ?? '' }}</span></span>
            <span class="field"><span class="label">Age:</span><span class="ul ul-age">{{ $age ?? '' }}</span></span>
            <span class="field"><span class="label">Date:</span><span class="ul ul-date">{{ $date ?? '' }}</span></span>
        </div>
        <div class="rx-body">
            <div class="rx-sym">℞</div>
            @if (!empty($notes))
                <div class="rx-notes">{{ $notes }}</div>
            @else
            <div class="rx-lines">
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
            </div>
            @endif
        </div>
        <div class="rx-foot">
            <div class="rx-phys">
                <div class="sig-line"></div>
                <div class="name">{{ $sigName }}</div>
                <div class="role">{{ $sigRole }}</div>
                <div class="lic">Lic. No.: {{ $sigLic }}</div>
            </div>
        </div>
        <div class="rx-page-foot">
            <div class="rx-foot-row">
                <img class="rx-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 3 (bottom-left) --}}
    <div class="rx-card">
        <div class="rx-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rx-ht">
                <div class="rx-republic">Republic of the Philippines</div>
                <div class="rx-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rx-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rx-addr">
                    <span class="rx-addr-loc">SUNN Main Campus</span>
                    <span class="rx-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rx-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <hr class="rx-divider"/>
        <div class="rx-title">PRESCRIPTION FORM</div>
        <div class="rx-patient">
            <span class="field"><span class="label">Name:</span><span class="ul ul-name">{{ $full_name ?? '' }}</span></span>
            <span class="field"><span class="label">Age:</span><span class="ul ul-age">{{ $age ?? '' }}</span></span>
            <span class="field"><span class="label">Date:</span><span class="ul ul-date">{{ $date ?? '' }}</span></span>
        </div>
        <div class="rx-body">
            <div class="rx-sym">℞</div>
            @if (!empty($notes))
                <div class="rx-notes">{{ $notes }}</div>
            @else
            <div class="rx-lines">
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
            </div>
            @endif
        </div>
        <div class="rx-foot">
            <div class="rx-phys">
                <div class="sig-line"></div>
                <div class="name">{{ $sigName }}</div>
                <div class="role">{{ $sigRole }}</div>
                <div class="lic">Lic. No.: {{ $sigLic }}</div>
            </div>
        </div>
        <div class="rx-page-foot">
            <div class="rx-foot-row">
                <img class="rx-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
            </div>
        </div>
    </div>

    {{-- CARD 4 (bottom-right) --}}
    <div class="rx-card">
        <div class="rx-hdr">
            <img class="logo" src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
            <div class="rx-ht">
                <div class="rx-republic">Republic of the Philippines</div>
                <div class="rx-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
                <div class="rx-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
                <div class="rx-addr">
                    <span class="rx-addr-loc">SUNN Main Campus</span>
                    <span class="rx-addr-city">Sagay City, Negros Occidental</span>
                    <span class="rx-addr-zip">Philippines 6122</span>
                </div>
            </div>
        </div>
        <hr class="rx-divider"/>
        <div class="rx-title">PRESCRIPTION FORM</div>
        <div class="rx-patient">
            <span class="field"><span class="label">Name:</span><span class="ul ul-name">{{ $full_name ?? '' }}</span></span>
            <span class="field"><span class="label">Age:</span><span class="ul ul-age">{{ $age ?? '' }}</span></span>
            <span class="field"><span class="label">Date:</span><span class="ul ul-date">{{ $date ?? '' }}</span></span>
        </div>
        <div class="rx-body">
            <div class="rx-sym">℞</div>
            @if (!empty($notes))
                <div class="rx-notes">{{ $notes }}</div>
            @else
            <div class="rx-lines">
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
                <div class="rx-line"></div>
            </div>
            @endif
        </div>
        <div class="rx-foot">
            <div class="rx-phys">
                <div class="sig-line"></div>
                <div class="name">{{ $sigName }}</div>
                <div class="role">{{ $sigRole }}</div>
                <div class="lic">Lic. No.: {{ $sigLic }}</div>
            </div>
        </div>
        <div class="rx-page-foot">
            <div class="rx-foot-row">
                <img class="rx-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
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
