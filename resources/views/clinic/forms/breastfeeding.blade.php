@php
    $name     = request('name', '');
    $dept     = request('dept', '');
    $jobtitle = request('jobtitle', '');
    $email    = request('email', '');
    $contact  = request('contact', '');
    $checkedAgreements = (array) request()->input('agreements', []);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Breastfeeding Room User Agreement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bf-page {
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 20mm 14mm;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        /* ── Header ── */
        .bf-hdr {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4mm;
            border-bottom: 1.5pt solid #111;
            padding-bottom: 4mm;
            margin-bottom: 6mm;
        }
        .bf-hdr img {
            width: 28mm;
            height: 28mm;
            object-fit: contain;
            flex-shrink: 0;
            transform: scale(1.9) translateX(3mm);
            transform-origin: center;
        }
        .bf-ht { line-height: 1.3; font-family: Arial, sans-serif; }
        .bf-republic { color: #003C8F; font-size: 8pt; font-weight: 600; }
        .bf-uni      { color: #F6BF00; font-weight: 900; font-size: 14pt; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap; }
        .bf-office   { color: #003C8F; font-weight: 800; font-size: 10.5pt; margin-top: 1px; white-space: nowrap; }
        .bf-addr     { color: #003C8F; font-size: 8pt; font-weight: 500; margin-top: 3px; line-height: 1.5; }

        /* ── Title ── */
        .bf-title {
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            color: #000;
            margin: 2mm 0 0.5mm;
        }
        .bf-subtitle {
            text-align: center;
            font-size: 9pt;
            font-style: italic;
            margin-bottom: 3.5mm;
        }

        /* ── User fields ── */
        .bf-fields {
            display: flex;
            flex-direction: column;
            gap: 1.8mm;
            font-size: 9pt;
            margin-bottom: 3.5mm;
        }
        .bf-field {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            gap: 2mm;
        }
        .bf-label {
            font-size: 9pt;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .bf-line {
            flex: 1;
            border-bottom: 0.8pt solid #000;
            min-height: 4.5mm;
            padding: 0 2px;
            font-size: 9pt;
        }

        /* ── Instruction ── */
        .bf-instruction {
            font-size: 9pt;
            font-weight: 700;
            margin-bottom: 3mm;
            line-height: 1.4;
        }

        /* ── Agreement items ── */
        .bf-agreements {
            display: flex;
            flex-direction: column;
            gap: 3mm;
            font-size: 8.5pt;
            flex: 1;
        }
        .bf-agree-item {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 2mm;
            line-height: 1.5;
        }
        .bf-check {
            display: inline-flex;
            flex: 0 0 5mm;
            align-items: center;
            justify-content: center;
            width: 5mm;
            height: 5mm;
            border: 0.8pt solid #000;
            color: #000;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1;
            margin-top: 1px;
        }
        .bf-agree-text {
            flex: 1;
            font-size: 8.5pt;
        }

        /* ── Signature ── */
        .bf-sig-section {
            margin-top: 6mm;
        }
        .bf-sig-line {
            border-bottom: 0.8pt solid #000;
            width: 55mm;
            height: 4.5mm;
            margin-bottom: 1mm;
        }
        .bf-sig-label {
            font-size: 8.5pt;
        }

        /* ── Footer ── */
        .bf-footer {
            margin-top: auto;
            padding-top: 4mm;
        }
        .bf-cc {
            font-size: 8pt;
            font-style: italic;
            font-weight: 600;
            color: #000;
            margin-bottom: 1mm;
        }
        .bf-tagline {
            font-size: 8.5pt;
            font-style: italic;
            font-weight: 700;
            color: #003C8F;
            text-align: center;
            margin-bottom: 2mm;
        }
        .bf-foot-bar {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 4mm;
        }
        .bf-contact {
            font-size: 7pt;
            color: #003C8F;
            font-weight: 600;
            line-height: 1.6;
            flex-shrink: 0;
        }
        .bf-contact i { display: inline-block; width: 11px; margin-right: 3px; text-align: center; color: #003C8F; }
        .bf-foot-img {
            flex: 1;
            height: 14mm;
            max-height: 14mm;
            object-fit: fill;
        }
        .bf-page-num {
            text-align: center;
            font-size: 8pt;
            margin-top: 2mm;
        }
    </style>
</head>
<body>
<div class="bf-page">

    <!-- Header -->
    <div class="bf-hdr">
        <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo"/>
        <div class="bf-ht">
            <div class="bf-republic">Republic of the Philippines</div>
            <div class="bf-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>
            <div class="bf-office">MEDICAL AND DENTAL SERVICES OFFICE</div>
            <div class="bf-addr">
                SUNN Main Campus<br/>
                Sagay City, Negros Occidental<br/>
                Philippines 6122
            </div>
        </div>
    </div>

    <!-- Title -->
    <div class="bf-title">Breastfeeding Room User Agreement</div>
    <div class="bf-subtitle">(Must be completed by user before using the room)</div>

    <!-- User fields -->
    <div class="bf-fields">
        <div class="bf-field">
            <span class="bf-label">Name:</span>
            <span class="bf-line">{{ $name }}</span>
        </div>
        <div class="bf-field">
            <span class="bf-label">Department/College:</span>
            <span class="bf-line">{{ $dept }}</span>
        </div>
        <div class="bf-field">
            <span class="bf-label">Job Title/Location (if employee):</span>
            <span class="bf-line">{{ $jobtitle }}</span>
        </div>
        <div class="bf-field">
            <span class="bf-label">E-mail Adress:</span>
            <span class="bf-line" style="max-width:45mm;">{{ $email }}</span>
            <span class="bf-label">Contact details:</span>
            <span class="bf-line">{{ $contact }}</span>
        </div>
    </div>

    <!-- Instruction -->
    <div class="bf-instruction">Please put a checkmark in every statement which will indicate you have read, understand and agree for this.</div>

    <!-- Agreement statements -->
    <div class="bf-agreements">
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a1', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I agree that it is my choice to use the Breastfeeding Room. Steps will be taken to keep the room clean, safe, and secure, but I understand that others cannot be held liable for the day-to-day condition of the room and its contents.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a2', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I agree that I am responsible for doing my part to keep the room clean and orderly. I understand that breast milk is a body fluid and careful cleanup of spills, etc., is important.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a3', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">Out of respect for the needs of other nursing mothers who need to access the space, I agree that access to the breastfeeding room is for my use only, and that sharing my access to the space with anyone else is not permitted.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a4', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I will only use the room for expressing breast milk, and I will secure the door after using it.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a5', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I understand/agree that I am responsible for transportation and proper storage of milk and bringing my own breast pump and storage bags.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a6', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I understand that in order to ensure that only authorized persons use the breastfeeding room, my name may be shared with the University employees who are in charge of providing this facility and for some compliance report that University is required.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a7', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I understand/agree to stop using the facility three years from the birth of my child.</span>
        </div>
        <div class="bf-agree-item">
            <span class="bf-check">{{ in_array('bf_a8', $checkedAgreements, true) ? '✓' : '' }}</span>
            <span class="bf-agree-text">I understand and agree that failure to comply with any of the above agreements could be cause for loss of access to the facility now and in the future.</span>
        </div>
    </div>

    <!-- Signature -->
    <div class="bf-sig-section">
        <div class="bf-sig-line">{{ $name }}</div>
        <div class="bf-sig-label">User's Name and Signature</div>
    </div>

    <!-- Footer -->
    <div class="bf-footer">
        <div class="bf-cc">cc: HRDO/GADIO/VPs/CampusDirectors/Dean/Clinic</div>
        <div class="bf-tagline">The Future Shines Brightest</div>
        <div class="bf-foot-bar">
            <div class="bf-contact">
                <i class="fa fa-phone"></i> (034) 703 8802<br/>
                <i class="fa fa-envelope"></i> info@sunn.edu.ph<br/>
                <i class="fa fa-globe"></i> www.sunn.edu.ph
            </div>
            <img class="bf-foot-img" src="{{ asset('clinic/images/footer.png') }}" alt="Accreditation badges"/>
        </div>
        <div class="bf-page-num"></div>
    </div>

</div>
</body>
</html>
