<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Medical Certificate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #fff;
            width: 210mm;
            min-height: 297mm;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 25.4mm;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4mm;
            border-bottom: 1.5pt solid #111;
            padding-bottom: 4mm;
            margin-bottom: 6mm;
        }
        .header img {
            width: 28mm;
            height: 28mm;
            object-fit: contain;
            flex-shrink: 0;
            transform: scale(1.9) translateX(3mm);
            transform-origin: center;
        }
        .header-text { line-height: 1.3; }
        .header-text .republic { color: #003C8F; font-size: 8pt; font-weight: 600; }
        .header-text .university { color: #F6BF00; font-weight: 900; font-size: 14pt; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap; }
        .header-text .office { color: #003C8F; font-weight: 800; font-size: 10.5pt; margin-top: 1px; white-space: nowrap; }
        .header-text .address { color: #003C8F; font-size: 8pt; font-weight: 500; margin-top: 3px; line-height: 1.5; }

        /* ── Title ── */
        .cert-title {
            text-align: center;
            font-size: 13pt;
            font-weight: 900;
            text-decoration: underline;
            letter-spacing: 2px;
            color: #111;
            margin: 5mm 0 6mm;
        }

        /* ── Body ── */
        .body { flex: 1; font-size: 10pt; color: #111; }

        .date-row {
            text-align: right;
            margin-bottom: 6mm;
            font-size: 10pt;
        }
        .underline {
            display: inline-block;
            border-bottom: 1pt solid #111;
            min-width: 40mm;
            padding: 0 2px;
        }
        .underline-full {
            display: block;
            border-bottom: 1pt solid #111;
            width: 100%;
            min-height: 5mm;
            padding: 0 2px;
        }

        .label-bold { font-weight: 700; }

        .name-label {
            font-weight: 700;
            font-size: 10pt;
            margin-bottom: 1mm;
        }
        .name-cols {
            display: flex;
            gap: 4mm;
            margin-bottom: 2mm;
        }
        .name-col { flex: 1; }
        .name-col .val {
            border-bottom: 1pt solid #111;
            min-height: 5mm;
            padding: 0 2px;
            font-size: 10pt;
        }
        .name-col .lbl {
            font-size: 8pt;
            color: #444;
            margin-top: 1px;
        }

        .inline-row {
            display: flex;
            align-items: baseline;
            gap: 6mm;
            margin-bottom: 2mm;
            font-size: 10pt;
        }
        .inline-field {
            display: inline-flex;
            align-items: baseline;
            gap: 2px;
        }

        .address-row {
            display: flex;
            align-items: baseline;
            gap: 2mm;
            margin-bottom: 5mm;
        }
        .address-row .address-line {
            flex: 1;
            border-bottom: 1pt solid #111;
            min-height: 5mm;
            padding: 0 2px;
        }

        .vitals { margin-bottom: 5mm; }
        .vital-row { margin-bottom: 2mm; font-size: 10pt; }

        .diagnosis-section { margin-top: 4mm; }
        .diag-label { font-weight: 700; margin-bottom: 2mm; font-size: 10pt; }
        .diag-lines {
            min-height: 30mm;
            padding: 0 2px;
            word-break: break-word;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            line-height: 7mm;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent,
                transparent calc(7mm - 1pt),
                #111 calc(7mm - 1pt),
                #111 7mm
            );
            background-size: 100% 7mm;
            background-position: 0 6mm;
        }

        /* ── Signature ── */
        .signature-block {
            margin-top: auto;
            padding-top: 10mm;
            display: flex;
            justify-content: flex-end;
        }
        .sig-inner {
            text-align: center;
            min-width: 55mm;
        }
        .sig-line {
            border-bottom: 1.5pt solid #111;
            height: 10mm;
            margin-bottom: 2mm;
        }
        .sig-name { font-weight: 700; font-size: 10pt; }
        .sig-license { font-size: 9pt; margin-top: 1px; }
        .sig-title { font-size: 9pt; margin-top: 1px; }

        /* ── Footer ── */
        .footer {
            margin-top: auto;
            padding-top: 2mm;
        }
        .footer .tagline {
            text-align: center;
            font-style: italic;
            font-weight: 700;
            color: #003C8F;
            font-size: 8pt;
            letter-spacing: 0.3px;
            margin-bottom: 2mm;
        }
        .footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 4mm;
        }
        .footer .contact {
            font-size: 7pt;
            color: #003C8F;
            font-weight: 600;
            line-height: 1.7;
            flex-shrink: 0;
        }
        .footer .contact i { display: inline-block; width: 11px; margin-right: 3px; text-align: center; color: #003C8F; }
        .footer img.logos {
            flex: 1;
            height: 14mm;
            object-fit: fill;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
        <div class="header-text">
            <div class="republic">Republic of the Philippines</div>
            <div class="university">STATE UNIVERSITY OF NORTHERN NEGROS</div>
            <div class="office">MEDICAL AND DENTAL SERVICES OFFICE</div>
            <div class="address">SUNN Main Campus<br>Sagay City, Negros Occidental<br>Philippines 6122</div>
        </div>
    </div>

    {{-- TITLE --}}
    <div class="cert-title">MEDICAL CERTIFICATE</div>

    {{-- BODY --}}
    <div class="body">

        {{-- Date --}}
        <div class="date-row">
            Date: <span class="underline">{{ $date ?? '' }}</span>
        </div>

        {{-- Name of Patient --}}
        <div class="name-label">NAME OF PATIENT:</div>

        <div class="name-cols">
            <div class="name-col">
                <div class="val">{{ $surname ?? '' }}</div>
                <div class="lbl">Surname</div>
            </div>
            <div class="name-col">
                <div class="val">{{ $firstname ?? '' }}</div>
                <div class="lbl">First Name</div>
            </div>
            <div class="name-col">
                <div class="val">{{ $middlename ?? '' }}</div>
                <div class="lbl">Middle Name</div>
            </div>
        </div>

        {{-- Age / Sex / Civil Status --}}
        <div class="inline-row">
            <div class="inline-field">
                Age: <span class="underline" style="min-width:15mm">{{ $age ?? '' }}</span>
            </div>
            <div class="inline-field">
                Sex: <span class="underline" style="min-width:18mm">{{ $sex ?? '' }}</span>
            </div>
            <div class="inline-field">
                Civil Status: <span class="underline" style="min-width:22mm">{{ $civil ?? '' }}</span>
            </div>
        </div>

        {{-- Address --}}
        <div class="address-row">
            <span class="label-bold">Address:</span>
            <span class="address-line">{{ $address ?? '' }}</span>
        </div>

        {{-- BP / Temp --}}
        <div class="vitals">
            <div class="vital-row">
                BP: <span class="underline" style="min-width:28mm">{{ $bp ?? '' }}</span>
            </div>
            <div class="vital-row">
                Temp: <span class="underline" style="min-width:25mm">{{ $temp ?? '' }}</span>
            </div>
        </div>

        {{-- Recommendation / Diagnosis --}}
        <div class="diagnosis-section">
            <div class="diag-label">Recommendation/Diagnosis:</div>
            <div class="diag-lines">{{ $assessment ?? '' }}</div>
        </div>

        {{-- Signature --}}
        <div class="signature-block">
            <div class="sig-inner">
                <div class="sig-line"></div>
                <div class="sig-name">{{ strtoupper($sig_name ?? 'DR. CYRILLE S. ADRANEDA') }}</div>
                <div class="sig-license">{{ $sig_license ?? '#85606' }}</div>
                <div class="sig-title">{{ $sig_title ?? 'School Physician' }}</div>
            </div>
        </div>

    </div>{{-- end body --}}

    {{-- FOOTER --}}
    <div class="footer">
        <div class="tagline">The Future Shines Brightest</div>
        <div class="footer-row">
            <div class="contact">
                <i class="fa fa-phone"></i> (034) 703 8802<br>
                <i class="fa fa-envelope"></i> info@sunn.edu.ph<br>
                <i class="fa fa-globe"></i> www.sunn.edu.ph
            </div>
            <img class="logos" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
        </div>
    </div>

</div>
</body>
</html>
