/**
 * forms/nurse-form-lab.js
 * Laboratory Request Form — live preview + print.
 * Depends on: forms/utils.js
 */
'use strict';

var LAB_STYLE = [
    '.lab-grid{width:210mm;height:297mm;display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:8mm;padding:12mm;background:#fff;font-family:"Times New Roman",Georgia,serif;color:#000;}',
    '.lab-card{border:0.6pt solid #1a1a1a;padding:3mm 4mm;display:flex;flex-direction:column;position:relative;overflow:hidden;background:#fff;border-radius:2px;}',
    '.lab-card-hdr{display:flex;flex-direction:row;align-items:center;gap:2mm;border-bottom:0.75pt solid #111;padding-bottom:1.5mm;margin-bottom:2mm;}',
    '.lab-card-hdr img{width:14mm;height:14mm;object-fit:contain;flex-shrink:0;transform:translateX(1.75mm) scale(1.5);transform-origin:center;}',
    '.lab-card-ht{line-height:1.15;flex:1;font-family:Arial,sans-serif;min-width:0;}',
    '.lab-card-republic{color:#003C8F;font-size:4.5pt;font-weight:600;}',
    '.lab-card-uni{color:#F6BF00;font-weight:900;font-size:6pt;letter-spacing:0.1px;text-transform:uppercase;white-space:nowrap;}',
    '.lab-card-office{color:#003C8F;font-weight:800;font-size:5pt;margin-top:0.3px;text-transform:uppercase;white-space:nowrap;}',
    '.lab-card-addr{color:#003C8F;font-size:4.5pt;font-weight:500;margin-top:0.5px;line-height:1.15;display:flex;flex-direction:column;gap:0;}',
    '.lab-card-addr-loc,.lab-card-addr-city,.lab-card-addr-zip{display:block;}',
    '.lab-card-title{text-align:center;font-size:10pt;font-weight:900;text-transform:uppercase;letter-spacing:1.2px;color:#000;margin-bottom:2mm;font-family:"Times New Roman",serif;}',
    '.lab-card-patient{display:flex;flex-direction:column;gap:1mm;margin-bottom:2mm;font-size:6.5pt;}',
    '.lab-cprow{display:flex;align-items:baseline;gap:1.5mm;line-height:1.3;}',
    '.lab-cplabel{font-weight:700;white-space:nowrap;font-family:"Times New Roman",serif;}',
    '.lab-cpline{flex:1;border-bottom:0.4pt solid #000;min-height:3.2mm;padding:0 1.5px;font-size:6.8pt;}',
    '.lab-cpline-sm{max-width:16mm;flex:0 0 16mm;}',
    '.lab-cpline-md{max-width:28mm;flex:0 0 28mm;}',
    '.lab-tests{display:grid;grid-template-columns:1fr 1fr;gap:1mm 2.5mm;font-family:"Times New Roman",serif;}',
    '.lab-tests-col{display:flex;flex-direction:column;gap:1mm;}',
    '.lab-tests-cat{display:flex;flex-direction:column;gap:0.3mm;}',
    '.lab-tests-cat-title{font-size:6.5pt;font-weight:900;letter-spacing:0.3px;color:#000;margin-bottom:0.3mm;border-bottom:0.4pt solid #000;padding-bottom:0.2mm;}',
    '.lab-tests-item{display:flex;align-items:baseline;gap:1.2mm;font-size:6pt;line-height:1.15;}',
    '.lab-tests-box{width:2.2mm;height:2.2mm;border:0.5pt solid #000;flex-shrink:0;display:inline-block;position:relative;top:-0.2mm;}',
    '.lab-tests-box-checked{background:#000;color:#fff;font-size:5pt;text-align:center;line-height:2.2mm;}',
    '.lab-tests-name{flex:1;}',
    '.lab-tests-sub{color:#222;font-style:italic;font-size:5.5pt;}',
    '.lab-request-meta{margin-top:1mm;font-family:"Times New Roman",serif;font-size:6.5pt;line-height:1.3;}',
    '.lab-request-meta-row{display:flex;align-items:baseline;gap:1.5mm;}',
    '.lab-request-meta-label{font-weight:700;white-space:nowrap;}',
    '.lab-request-date{flex:0 0 28mm;border-bottom:0.4pt solid #000;min-height:3.2mm;padding:0 1.5px;}',
    '.lab-request-physician{font-weight:700;}',
].join('');

injectStyle('lab-styles', LAB_STYLE);

/* ── test catalogue ── */
var LAB_LEFT_TESTS = [
    { cat: 'HEMATOLOGY', items: [
        { name: 'CBC',              id: 'lab_t_cbc' },
        { name: 'CBC WITH PLATELET',id: 'lab_t_cbc_plt' },
        { name: 'PLATELET COUNT',   id: 'lab_t_platelet' },
        { name: 'CT, BT',           id: 'lab_t_ctbt' },
        { name: 'ESR',              id: 'lab_t_esr' },
        { name: 'ABO TYPING',       id: 'lab_t_abo' },
    ]},
    { cat: 'MICROSCOPY', items: [
        { name: 'URINALYSIS', id: 'lab_t_urinalysis' },
        { name: 'FECALYSIS',  id: 'lab_t_fecalysis' },
    ]},
    { cat: 'SEROLOGY', items: [
        { name: 'HbsAg',         id: 'lab_t_hbsag' },
        { name: 'Pregnancy Test', id: 'lab_t_preg', sub: 'urine / blood' },
    ]},
];

var LAB_RIGHT_TESTS = [
    { cat: 'CHEMISTRY', items: [
        { name: 'BUN',           id: 'lab_t_bun' },
        { name: 'CREATININE',    id: 'lab_t_creatinine' },
        { name: 'SGPT / SGOT',   id: 'lab_t_sgpt' },
        { name: 'RBS',           id: 'lab_t_rbs' },
        { name: 'FBS',           id: 'lab_t_fbs' },
        { name: 'LIPID PROFILE', id: 'lab_t_lipid' },
        { name: 'URIC ACID',     id: 'lab_t_uric' },
        { name: 'HBA1C',         id: 'lab_t_hba1c' },
        { name: 'TSH',           id: 'lab_t_tsh' },
        { name: 'T3, T4',        id: 'lab_t_t3t4' },
        { name: 'SODIUM',        id: 'lab_t_sodium' },
        { name: 'POTASSIUM',     id: 'lab_t_potassium' },
        { name: 'CHLORIDE',      id: 'lab_t_chloride' },
        { name: 'CALCIUM',       id: 'lab_t_calcium' },
    ]},
];

function _labTick(id) {
    return fieldChecked(id)
        ? '<span class="lab-tests-box lab-tests-box-checked">&#10003;</span>'
        : '<span class="lab-tests-box"></span>';
}

function _labItem(it) {
    var name = typeof it === 'string' ? it : it.name;
    var id   = (typeof it === 'object') ? it.id  : null;
    var sub  = (typeof it === 'object' && it.sub) ? '<span class="lab-tests-sub">' + escHtml(it.sub) + '</span>' : '';
    return '<div class="lab-tests-item">' + _labTick(id) + '<span class="lab-tests-name">' + escHtml(name) + '</span>' + sub + '</div>';
}

function _labCat(c) {
    return '<div class="lab-tests-cat"><div class="lab-tests-cat-title">' + escHtml(c.cat) + '</div>' +
        c.items.map(_labItem).join('') + '</div>';
}

function buildLabCard(v) {
    var leftHtml  = LAB_LEFT_TESTS.map(_labCat).join('');
    var rightHtml = LAB_RIGHT_TESTS.map(_labCat).join('') +
        '<div class="lab-tests-cat">' +
            '<div class="lab-tests-cat-title">OTHERS</div>' +
            '<div class="lab-tests-item">' + _labTick('lab_t_xray') + '<span class="lab-tests-name">CHEST XRAY-PA</span></div>' +
            '<div class="lab-tests-item"><span class="lab-tests-box"></span><span style="flex:1;border-bottom:0.4pt solid #000;min-height:3mm;"></span></div>' +
        '</div>';

    return '<div class="lab-card">' +
        '<div class="lab-card-hdr">' +
            '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
            '<div class="lab-card-ht">' +
                '<div class="lab-card-republic">Republic of the Philippines</div>' +
                '<div class="lab-card-uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
                '<div class="lab-card-office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
                '<div class="lab-card-addr">' +
                    '<span class="lab-card-addr-loc">SUNN Main Campus</span>' +
                    '<span class="lab-card-addr-city">Sagay City, Negros Occidental</span>' +
                    '<span class="lab-card-addr-zip">Philippines 6122</span>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="lab-card-title">LABORATORY REQUEST FORM</div>' +
        '<div class="lab-card-patient">' +
            '<div class="lab-cprow"><span class="lab-cplabel">Name:</span><span class="lab-cpline">' + escHtml(v.name) + '</span></div>' +
            '<div class="lab-cprow"><span class="lab-cplabel">Age:</span><span class="lab-cpline lab-cpline-sm">' + escHtml(v.age) + '</span><span class="lab-cplabel">Sex:</span><span class="lab-cpline lab-cpline-sm">' + escHtml(v.sex) + '</span><span class="lab-cplabel">DOB:</span><span class="lab-cpline lab-cpline-md">' + escHtml(v.dob) + '</span></div>' +
            '<div class="lab-cprow"><span class="lab-cplabel">Address:</span><span class="lab-cpline">' + escHtml(v.address) + '</span></div>' +
            '</div>' +
            '<div class="lab-tests">' +
                '<div class="lab-tests-col">' + leftHtml  + '</div>' +
                '<div class="lab-tests-col">' + rightHtml + '</div>' +
            '</div>' +
            '<div class="lab-request-meta">' +
                '<div class="lab-request-meta-row"><span class="lab-request-meta-label">Date Requested:</span><span class="lab-request-date">' + escHtml(v.dateReq) + '</span></div>' +
                '<div class="lab-request-meta-row"><span class="lab-request-meta-label">Requesting Physician:</span><span class="lab-request-physician">' + escHtml(v.phys) + '</span></div>' +
            '</div>' +
    '</div>';
}

function renderLabPreview() {
    var v = {
        name:    fieldVal('lab_name'),
        age:     fieldVal('lab_age'),
        sex:     fieldVal('lab_sex'),
        dob:     fieldVal('lab_dob'),
        address: fieldVal('lab_address'),
        dateReq: fieldVal('lab_date_requested'),
        phys:    fieldVal('lab_physician_name') || 'CYRILLE S. ADRANEDA, M.D',
    };
    var el = document.getElementById('lab-preview');
    if (!el) { return; }
    el.innerHTML = '<div class="lab-grid">' +
        buildLabCard(v) + buildLabCard(v) + buildLabCard(v) + buildLabCard(v) +
    '</div>';
}

function clearLabForm() {
    var form = document.getElementById('lab-form');
    if (form) { form.reset(); }
    var d = document.getElementById('lab_date_requested');
    if (d) { d.value = new Date().toISOString().split('T')[0]; }
    var pName = document.getElementById('lab_physician_name');
    var pLic  = document.getElementById('lab_physician_lic');
    if (pName) { pName.value = ''; }
    if (pLic)  { pLic.value  = ''; }
    var s = document.getElementById('lab_search');
    if (s) {
        s.value = '';
        var clr = document.getElementById('lab_search_clear');
        if (clr) { clr.style.display = 'none'; }
    }
    var tb = document.querySelector('#page-form-lab .search-toolbar');
    if (tb) { tb.classList.remove('is-loaded', 'is-searching'); }
    renderLabPreview();
}

function labPrint() {
    var params = new URLSearchParams({
        name:      fieldVal('lab_name'),
        age:       fieldVal('lab_age'),
        sex:       fieldVal('lab_sex'),
        dob:       fieldVal('lab_dob'),
        address:   fieldVal('lab_address'),
        date:      fieldVal('lab_date_requested'),
        phys:      fieldVal('lab_physician_name'),
    });

    var selectedTests = LAB_LEFT_TESTS.concat(LAB_RIGHT_TESTS)
        .reduce(function (ids, category) {
            return ids.concat(category.items.map(function (item) {
                return item.id;
            }));
        }, ['lab_t_xray'])
        .filter(function (id) {
            return fieldChecked(id);
        });

    selectedTests.forEach(function (id) {
        params.append('tests[]', id);
    });

    openPrint('/clinic/forms/lab?' + params.toString());
}

document.addEventListener('DOMContentLoaded', function () {
    var d = document.getElementById('lab_date_requested');
    if (d && !d.value) { d.value = new Date().toISOString().split('T')[0]; }
    renderLabPreview();
});
