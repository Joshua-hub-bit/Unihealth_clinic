<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dental Certificate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Times New Roman', 'Arial', serif;
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Page: 0.75" (19mm) uniform margins ── */
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 19mm;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        /* ── Header: single spacing, 10–11pt ── */
        .header {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4mm;
            border-bottom: 1.5pt solid #111;
            padding-bottom: 4mm;
            /* Header → Title gap: ~4mm (≈ 12–14pt) */
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
        .header-text { line-height: 1.3; font-family: Arial, sans-serif; }
        .header-text .republic  { color: #003C8F; font-size: 8pt;   font-weight: 600; }
        .header-text .university{ color: #F6BF00; font-weight: 900;  font-size: 14pt; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap; }
        .header-text .office    { color: #003C8F; font-weight: 800;  font-size: 10.5pt; margin-top: 1px; white-space: nowrap; }
        .header-text .address   { color: #003C8F; font-size: 8pt;   font-weight: 500; margin-top: 3px; line-height: 1.5; }

        /* ── Title: 14–16pt bold, single spacing ── */
        /* Title → Body gap: ~4.5mm (≈ 14–16pt) */
        .cert-title {
            text-align: center;
            font-size: 14pt;
            font-weight: 900;
            text-decoration: underline;
            letter-spacing: 1px;
            color: #000;
            margin-bottom: 4.5mm;
            font-family: 'Times New Roman', serif;
            line-height: 1.0;
        }

        /* ── Body: 10pt, single (1.0) spacing ── */
        .body {
            flex: 1;
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.0;
        }

        /* ── Date row ── */
        /* Between paragraphs: 6–8pt ≈ 2–2.8mm */
        .date-row {
            text-align: left;
            font-size: 10pt;
            line-height: 1.0;
            margin-bottom: 2.5mm;
        }

        /* ── Underlined blanks ── */
        .ul {
            display: inline-block;
            border-bottom: 0.8pt solid #000;
            min-width: 45mm;
            padding: 0 2px;
            font-weight: 600;
        }
        .ul-long  { min-width: 100mm; }
        .ul-name  { min-width: 65mm; max-width: 65mm; }
        .ul-age   { min-width: 10mm; max-width: 14mm; }
        .ul-tooth { min-width: 35mm; }
        .ul-mid   { min-width: 60mm; }
        .ul-line {
            display: block;
            border-bottom: 0.8pt solid #000;
            width: 100%;
            height: 6mm;
            /* Between sections: 8–10pt ≈ 2.8–3.5mm */
            margin-top: 3mm;
        }
        .ul-line-text {
            height: auto;
            min-height: 6mm;
            line-height: 6mm;
            border-bottom: 0;
            overflow-wrap: anywhere;
            background: repeating-linear-gradient(to bottom, transparent 0, transparent calc(6mm - 0.8pt), #000 calc(6mm - 0.8pt), #000 6mm);
        }
        .ul-line-short {
            display: block;
            border-bottom: 0.8pt solid #000;
            width: 55%;
            height: 6mm;
            margin-top: 3mm;
        }

        /* ── Salutation: between paragraphs 6–8pt ── */
        .salutation {
            font-size: 10pt;
            font-weight: 400;
            line-height: 1.0;
            /* Between sections: ~3mm */
            margin-top: 3mm;
            margin-bottom: 2.5mm;
        }

        /* ── Cert statement: 10pt single ── */
        .cert-statement {
            font-size: 10pt;
            line-height: 1.0;
            /* Between paragraphs: 2–2.8mm */
            margin-bottom: 2.5mm;
            text-align: justify;
        }
        .cert-line {
            display: block;
            font-size: 10pt;
            line-height: 1.4;
        }

        /* ── Checklist rows: 10pt, 4–6pt spacing ≈ 1.5–2mm ── */
        .cb-row {
            display: flex;
            align-items: baseline;
            gap: 3mm;
            /* Between checklist items: 4–6pt ≈ 1.5–2mm */
            margin: 1.5mm 0 1.5mm 8mm;
            font-size: 10pt;
            line-height: 1.0;
        }
        .cb-box {
            display: inline-block;
            width: 3.5mm;
            height: 3.5mm;
            border: 0.8pt solid #000;
            flex-shrink: 0;
            position: relative;
            top: 0.5mm;
            text-align: center;
            line-height: 3.5mm;
            font-weight: 900;
            font-size: 9pt;
        }

        /* ── Procedures block ── */
        /* Between sections: 8–10pt ≈ 3mm */
        .proc-block {
            margin: 3mm 0 3mm 0;
        }
        .proc-block .label {
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.0;
            margin-bottom: 1.5mm;
        }

        /* ── Signature: 9pt ── */
        .signature-block {
            margin-top: auto;
            padding-top: 6mm;
            display: flex;
            justify-content: flex-end;
        }
        .sig-inner {
            text-align: center;
            min-width: 65mm;
        }
        .sig-prep       { font-size: 9pt; margin-bottom: 15mm; font-family: 'Times New Roman', serif; line-height: 1.0; }
        .sig-prep-label { font-weight: 400; }
        .sig-line {
            border-bottom: 1pt solid #000;
            height: 1mm;
            margin-bottom: 1.5mm;
        }
        .sig-name    { font-weight: 700; font-size: 9pt;  font-family: 'Times New Roman', serif; line-height: 1.2; }
        .sig-license { font-size: 9pt;  margin-top: 1pt; font-family: 'Times New Roman', serif; line-height: 1.2; }
        .sig-designation { font-size: 9pt; margin-top: 1pt; font-family: 'Times New Roman', serif; line-height: 1.2; }
        .sig-title   { font-size: 9pt;  margin-top: 1pt; font-family: 'Times New Roman', serif; line-height: 1.2; }

        /* ── Footer: 8–9pt, single spacing ── */
        .footer {
            margin-top: 3mm;
            padding-top: 0;
        }
        .footer .tagline {
            text-align: center;
            font-style: italic;
            font-weight: 700;
            color: #003C8F;
            font-size: 8pt;
            letter-spacing: 0.3px;
            margin-bottom: 1.5mm;
            line-height: 1.0;
        }
        .footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 3mm;
        }
        .footer .contact {
            font-size: 7pt;
            color: #003C8F;
            font-weight: 600;
            line-height: 1.5;
            flex-shrink: 0;
        }
        .footer .contact i { display: inline-block; width: 11px; margin-right: 3px; text-align: center; color: #003C8F; }
        .footer img.logos {
            flex: 1;
            height: 13mm;
            object-fit: fill;
        }
        .page-num {
            text-align: right;
            font-size: 8pt;
            color: #003C8F;
            font-weight: 600;
            margin-top: 1mm;
            line-height: 1.0;
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
    <div class="cert-title">DENTAL CERTIFICATE</div>

    {{-- BODY --}}
    <div class="body">

        {{-- Date --}}
        <div class="date-row">
            Date: <span class="ul">{{ $date ?? '' }}</span>
        </div>

        {{-- Recipient --}}
        <div class="salutation">To Whom It May Concern:</div>

        {{-- Certification Statement --}}
        <div class="cert-statement">
            <span class="cert-line">This is to certify that</span>
            <span class="cert-line">
                Mr./Mrs./Ms.<span class="ul ul-name">{{ trim(($firstname ?? '').' '.($middlename ?? '').' '.($surname ?? '')) ?: ($full_name ?? '') }}</span>,
                <span class="ul ul-age">{{ $age ?? '' }}</span> years of
            </span>
            <span class="cert-line">
                age, residing at <span class="ul ul-long">{{ $address ?? '' }}</span> have
            </span>
            <span class="cert-line">visited this clinic and had his/her dental check – up and</span>
        </div>

        {{-- Diagnosis / Treatment --}}
        <div class="cb-row">
            <span class="cb-box">{{ !empty($diagnosed) ? 'X' : '' }}</span>
            <span>was diagnosed that he/she need</span>
        </div>
        <div class="cb-row">
            <span class="cb-box">{{ !empty($diagnosed) ? '' : (!empty($treated) ? 'X' : '') }}</span>
            <span>was treated for</span>
        </div>

        {{-- Procedures --}}
        <div class="proc-block">
            <div class="label">The following dental procedures:</div>

            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_oral_prophylaxis) ? 'X' : '' }}</span>
                <span>Oral prophylaxis</span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_temp_filling) ? 'X' : '' }}</span>
                <span>Temporary filling of tooth/teeth no./nos. <span class="ul ul-tooth">{{ $tooth_temp ?? '' }}</span></span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_perm_restoration) ? 'X' : '' }}</span>
                <span>Permanent Restoration of the tooth/teeth no./nos. <span class="ul ul-tooth">{{ $tooth_perm ?? '' }}</span></span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_extraction) ? 'X' : '' }}</span>
                <span>Extraction of the tooth no./nos. <span class="ul ul-tooth">{{ $tooth_extract ?? '' }}</span></span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_orthodontic) ? 'X' : '' }}</span>
                <span>Orthodontic treatment</span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_subgingival) ? 'X' : '' }}</span>
                <span>Subgingival scaling</span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_impaction) ? 'X' : '' }}</span>
                <span>Impaction</span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_root_canal) ? 'X' : '' }}</span>
                <span>Root Canal Treatment</span>
            </div>
            <div class="cb-row">
                <span class="cb-box">{{ !empty($proc_prosthodontic) ? 'X' : '' }}</span>
                <span>Prosthodontic Treatment</span>
                <span style="margin-left:6mm;">Other:</span>
                <span class="cb-box">{{ !empty($proc_other) ? 'X' : '' }}</span>
            </div>
        </div>

        {{-- Other procedure details wrap and add ruled lines only as needed. --}}
        <div class="ul-line ul-line-text">{{ $proc_other_text ?? '' }}</div>

        {{-- Purpose of issuance --}}
        <div class="cert-statement" style="margin-top:3mm;">
            This certification is being issued upon the request of the patient for
        </div>
        <div class="ul-line ul-line-text">{{ $purpose ?? '' }}</div>

    </div>{{-- end body --}}

    {{-- Signature Block --}}
    <div class="signature-block">
        <div class="sig-inner">
            <div class="sig-prep"><span class="sig-prep-label">Prepared by:</span></div>
            <div class="sig-name">{{ strtoupper($sig_name ?? 'RHIZZA FAYE T. BARCENILLA D.M. D') }}</div>
            <div class="sig-line"></div>
            <div class="sig-license">Lic No.: {{ $sig_license ?? '0056828' }}</div>
            <div class="sig-designation">{{ $sig_title ?? 'School Dentist' }}</div>
            <div class="sig-title">PTR No.: {{ $sig_ptr ?? '_____' }}</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <div class="tagline">THE FUTURE SHINES BRIGHTEST</div>
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
