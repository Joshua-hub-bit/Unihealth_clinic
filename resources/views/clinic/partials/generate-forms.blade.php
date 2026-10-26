{{--
    Generate Forms — tile grid + 11 document pages
    (Medical Record, Medical Certificate, Dental Certificate, Prescription Physician,
    Prescription Dentist, Physician Referral, Dentist Referral, Lab Request,
    X-Ray, Lactating, Breastfeeding)

    Included by both clinic.nurse (Generate Forms nav) and clinic.nurse_head
    (so the head nurse can issue the same set of clinical forms).

    Requires these scripts to be loaded by the parent view:
        clinic/js/forms/utils.js
        clinic/js/forms/nurse-form-medcert.js
        clinic/js/forms/nurse-form-dentalcert.js
        clinic/js/forms/nurse-form-prescription.js
        clinic/js/forms/nurse-form-referral.js
        clinic/js/forms/nurse-form-lab.js
        clinic/js/forms/nurse-form-xray.js
        clinic/js/forms/nurse-form-lactating.js
        clinic/js/forms/nurse-form-breastfeeding.js
        clinic/js/forms/medical-record-form.js
        clinic/js/nurse.js                 (provides handleFormSearch, autoFillForm)
        clinic/js/nurse-typeahead.js       (typeahead for student search)
        clinic/js/nurse-forms.js           (shared preview helpers)
        clinic/js/sunn-logo-b64.js         (logo data URI for the letterhead)
        clinic/js/nurse-fill-fix.js        (workaround for search-pool clearing)
--}}
            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — tile grid
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-forms">
                <div class="uh-card-title uh-mb-16">
                    <i class="fa fa-file-medical uh-icon-primary uh-mr-6"></i>
                    Generate Forms
                </div>
                <div class="uh-card uh-mb-16">
                    <div class="uh-card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-medical-record', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-notes-medical"></i>
                            <span class="uh-form-tile-title">Medical Record Form</span>
                            <span class="uh-form-tile-desc">Record a student visit and vital signs</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-dental-record', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-tooth"></i>
                            <span class="uh-form-tile-title">Dental Record Form</span>
                            <span class="uh-form-tile-desc">Mark teeth and oral-health findings directly on the form</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-medcert', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-certificate"></i>
                            <span class="uh-form-tile-title">Medical Certificate</span>
                            <span class="uh-form-tile-desc">Issue a medical certificate</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-dentalcert', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-tooth"></i>
                            <span class="uh-form-tile-title">Dental Certificate</span>
                            <span class="uh-form-tile-desc">Issue a dental certificate</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-prescription', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-prescription"></i>
                            <span class="uh-form-tile-title">Prescription Form Physician</span>
                            <span class="uh-form-tile-desc">2×2 grid prescription pad</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-prescription-dentist', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-tooth"></i>
                            <span class="uh-form-tile-title">Prescription Form Dentist</span>
                            <span class="uh-form-tile-desc">2×2 grid dental prescription pad</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-referral', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-share-from-square"></i>
                            <span class="uh-form-tile-title">Physician Referral Form</span>
                            <span class="uh-form-tile-desc">2×2 grid physician referral pad</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-referral-dentist', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-tooth"></i>
                            <span class="uh-form-tile-title">Dentist Referral Form</span>
                            <span class="uh-form-tile-desc">2×2 grid dental referral pad</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-lab', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-vial-circle-check"></i>
                            <span class="uh-form-tile-title">Laboratory Request Form</span>
                            <span class="uh-form-tile-desc">Single-page A4 lab request pad</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-xray', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-x-ray"></i>
                            <span class="uh-form-tile-title">X-Ray Form</span>
                            <span class="uh-form-tile-desc">2×2 grid chest x-ray slip</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-lactating', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-baby"></i>
                            <span class="uh-form-tile-title">Lactating Mother Accommodation</span>
                            <span class="uh-form-tile-desc">Request accommodation form</span>
                        </button>
                        <button class="uh-form-tile" type="button"
                            onclick="uhShowPage('form-breastfeeding', document.querySelector('[data-title=\'Generate Forms\']'))">
                            <i class="fa fa-baby-carriage"></i>
                            <span class="uh-form-tile-title">Breastfeeding Room Agreement</span>
                            <span class="uh-form-tile-desc">Room user agreement form</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — DENTAL RECORD
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-dental-record">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="dental-record_search_icon"></i>
                        <input type="text" id="dental-record_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('dental-record', this.value);}"
                            oninput="document.getElementById('dental-record_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="dental-record_search_clear"
                            onclick="var i=document.getElementById('dental-record_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearDentalRecordForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button id="dr-save-record" class="btn-action btn-action-save-pending" type="button"
                        onclick="saveDentalRecord()">
                        <i class="fa fa-floppy-disk"></i> Save
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="dentalRecordPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <span style="color:#cbd5e1;font-size:0.78rem;">Click the tooth circles and oral-health boxes directly on the form to mark them.</span>
                </div>
                <input type="hidden" id="dr_student_id">
                <input type="hidden" id="dr_last_name">
                <input type="hidden" id="dr_first_name">
                <input type="hidden" id="dr_middle_name">
                <input type="hidden" id="dr_age">
                <input type="hidden" id="dr_course">
                <input type="hidden" id="dr_department">
                <input type="hidden" id="dr_contact">
                <input type="hidden" id="dr_address">
                <input type="hidden" id="dr_designation">
                <div class="doc-layout">
                    <div class="doc-preview-wrap">
                        <div id="dental-record-preview" style="width:794px;height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:Arial,sans-serif;font-size:13px;color:#111;"></div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — MEDICAL RECORD
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-medical-record">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="medical-record_search_icon"></i>
                        <input type="text" id="medical-record_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('medical-record', this.value);}"
                            oninput="document.getElementById('medical-record_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="medical-record_search_clear"
                            onclick="var i=document.getElementById('medical-record_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearMedicalRecordForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="medicalRecordPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <form id="medical-record-form" class="doc-form-panel" oninput="renderMedicalRecord()">
                        <h3><i class="fa fa-user"></i> Patient Information</h3>
                        <input type="hidden" id="mrf_student_id">
                        <div class="form-section-title">Student Details</div>
                        <div class="fg-row">
                            <div class="fg">
                                <label for="mrf_last_name">Last Name</label>
                                <input type="text" id="mrf_last_name" readonly placeholder="Search for a student above"/>
                            </div>
                            <div class="fg">
                                <label for="mrf_first_name">First Name</label>
                                <input type="text" id="mrf_first_name" readonly/>
                            </div>
                            <div class="fg">
                                <label for="mrf_middle_name">Middle Name</label>
                                <input type="text" id="mrf_middle_name" readonly/>
                            </div>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label for="mrf_birth_date">Birth Date</label>
                                <input type="date" id="mrf_birth_date" readonly/>
                            </div>
                            <div class="fg">
                                <label for="mrf_age">Age</label>
                                <input type="text" id="mrf_age" readonly/>
                            </div>
                            <div class="fg">
                                <label for="mrf_course">Course / Year</label>
                                <input type="text" id="mrf_course" readonly/>
                            </div>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label for="mrf_contact">Contact No.</label>
                                <input type="text" id="mrf_contact" readonly/>
                            </div>
                            <div class="fg">
                                <label for="mrf_gender">Gender</label>
                                <input type="text" id="mrf_gender" readonly/>
                            </div>
                            <div class="fg">
                                <label for="mrf_address">Address</label>
                                <input type="text" id="mrf_address" readonly/>
                            </div>
                        </div>

                        <div class="form-section-title">Visit Information</div>
                        <div class="fg-row">
                            <div class="fg">
                                <label for="mrf_visit_date">Visit Date</label>
                                <input type="date" id="mrf_visit_date" required/>
                            </div>
                            <div class="fg">
                                <label for="mrf_visit_time">Time</label>
                                <input type="time" id="mrf_visit_time" required/>
                            </div>
                        </div>
                        <div class="fg">
                            <label for="mrf_reason">Chief Complaint</label>
                            <textarea id="mrf_reason" rows="3" maxlength="255" required></textarea>
                        </div>
                        <div class="fg">
                            <label for="mrf_treatment">Treatment</label>
                            <textarea id="mrf_treatment" rows="3" maxlength="10000"></textarea>
                        </div>

                        <div class="form-section-title">Vital Signs</div>
                        <div class="fg-row">
                            <div class="fg">
                                <label for="mrf_bp">BP</label>
                                <input type="text" id="mrf_bp" maxlength="20" placeholder="120/80"/>
                            </div>
                            <div class="fg">
                                <label for="mrf_temp">Temperature (°C)</label>
                                <input type="number" id="mrf_temp" min="25" max="50" step="0.1"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label for="mrf_pulse">Pulse (bpm)</label>
                            <input type="number" id="mrf_pulse" min="0" max="300" step="1"/>
                        </div>
                        <div class="fg">
                            <label for="mrf_notes">Remarks / Nurse’s Notes</label>
                            <textarea id="mrf_notes" rows="4" maxlength="10000"></textarea>
                        </div>
                        <button class="uh-btn uh-btn-primary" type="button" id="mrf-save"
                            style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;"
                            onclick="saveMedicalRecordForm()">
                            <i class="fa fa-floppy-disk"></i> Save Medical Record
                        </button>
                    </form>

                    <div class="doc-preview-wrap">
                        <div id="medical-record-preview" style="width:794px;height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:Arial,sans-serif;font-size:13px;color:#111;"></div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — MEDICAL CERTIFICATE
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-medcert">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="medcert_search_icon"></i>
                        <input type="text" id="medcert_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('medcert', this.value);}"
                            oninput="document.getElementById('medcert_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="medcert_search_clear"
                            onclick="var i=document.getElementById('medcert_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button"
                        onclick="clearMedCertForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="medcertPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="medcert-form" class="doc-form-panel" oninput="updateMedCertIframe()">
                        <h3><i class="fa fa-user"></i> Patient Information</h3>

                        <div class="form-section-title">Personal Details</div>

                        <div class="fg">
                            <label>Surname</label>
                            <input type="text" id="mc_surname" placeholder="Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>First Name</label>
                                <input type="text" id="mc_firstname" placeholder="Juan"/>
                            </div>
                            <div class="fg">
                                <label>Middle Name</label>
                                <input type="text" id="mc_middlename" placeholder="Santos"/>
                            </div>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="number" id="mc_age" min="0" max="120" placeholder="21"/>
                            </div>
                            <div class="fg">
                                <label>Civil Status</label>
                                <select id="mc_civil">
                                    <option value="">— Select —</option>
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Widowed</option>
                                    <option>Separated</option>
                                </select>
                            </div>
                            <div class="fg">
                                <label>Sex</label>
                                <select id="mc_sex">
                                    <option value="">—</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Address</label>
                            <input type="text" id="mc_address" placeholder="Barangay, City, Province"/>
                        </div>

                        <div class="form-section-title">Examination</div>

                        <div class="fg">
                            <label>Date of Examination</label>
                            <input type="date" id="mc_date"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>BP</label>
                                <input type="text" id="mc_bp" placeholder="120/80"/>
                            </div>
                            <div class="fg">
                                <label>Temp (°C)</label>
                                <input type="text" id="mc_temp" placeholder="36.5"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Recommendation / Diagnosis</label>
                            <textarea id="mc_assessment" rows="3" placeholder="e.g. physically fit, not fit for strenuous work, etc."></textarea>
                        </div>

                        <div class="form-section-title">Requesting Physician</div>

                        <div class="fg">
                            <label>Physician Name</label>
                            <select id="mc_sig_name" data-signing-role="physician" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select physician</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Title / Designation</label>
                            <input type="text" id="mc_sig_title" value="School Physician" placeholder="School Physician" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="mc_sig_license" value="#85606" placeholder="#85606"/>
                        </div>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('medcert-form', 'medical_certificate')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="medcert-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:Arial,sans-serif;font-size:13px;color:#111;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="medcert-iframe" src="" style="display:none;" title="Medical Certificate Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — DENTAL CERTIFICATE
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-dentalcert">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="dentalcert_search_icon"></i>
                        <input type="text" id="dentalcert_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('dentalcert', this.value);}"
                            oninput="document.getElementById('dentalcert_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="dentalcert_search_clear"
                            onclick="var i=document.getElementById('dentalcert_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button"
                        onclick="clearDentalCertForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="dentalcertPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="dentalcert-form" class="doc-form-panel" oninput="updateDentalCertIframe()" onchange="updateDentalCertIframe()">
                        <h3><i class="fa fa-user"></i> Patient Information</h3>

                        <div class="form-section-title">Personal Details</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="dc_full_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Surname</label>
                                <input type="text" id="dc_surname" placeholder="Dela Cruz"/>
                            </div>
                            <div class="fg">
                                <label>First Name</label>
                                <input type="text" id="dc_firstname" placeholder="Juan"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Middle Name</label>
                            <input type="text" id="dc_middlename" placeholder="Santos"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="number" id="dc_age" min="0" max="120" placeholder="21"/>
                            </div>
                            <div class="fg">
                                <label>Sex</label>
                                <select id="dc_sex">
                                    <option value="">—</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Address</label>
                            <input type="text" id="dc_address" placeholder="Barangay, City, Province"/>
                        </div>

                        <div class="form-section-title">Examination</div>

                        <div class="fg">
                            <label>Date of Examination</label>
                            <input type="date" id="dc_date"/>
                        </div>

                        <div class="form-section-title">Check-Up Outcome</div>
                        <div class="cb-grid">
                            <label class="cb-item"><input type="radio" name="dc_outcome" id="dc_diagnosed" value="diagnosed"/> was diagnosed that he/she need</label>
                            <label class="cb-item"><input type="radio" name="dc_outcome" id="dc_treated" value="treated"/> was treated for</label>
                        </div>

                        <div class="form-section-title">Dental Procedures</div>
                        <div class="cb-grid" style="grid-template-columns:1fr;">
                            <label class="cb-item"><input type="checkbox" id="dc_proc_oral_prophylaxis"/> Oral prophylaxis</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_temp_filling"/> Temporary filling of tooth/teeth no./nos.</label>
                            <div class="fg dentalcert-input-inset">
                                <input type="text" id="dc_tooth_temp" placeholder="Tooth no(s)."/>
                            </div>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_perm_restoration"/> Permanent Restoration of the tooth/teeth no./nos.</label>
                            <div class="fg dentalcert-input-inset">
                                <input type="text" id="dc_tooth_perm" placeholder="Tooth no(s)."/>
                            </div>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_extraction"/> Extraction of the tooth no./nos.</label>
                            <div class="fg dentalcert-input-inset">
                                <input type="text" id="dc_tooth_extract" placeholder="Tooth no(s)."/>
                            </div>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_orthodontic"/> Orthodontic treatment</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_subgingival"/> Subgingival scaling</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_impaction"/> Impaction</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_root_canal"/> Root Canal Treatment</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_prosthodontic"/> Prosthodontic Treatment</label>
                            <label class="cb-item"><input type="checkbox" id="dc_proc_other"/> Other:</label>
                            <div class="fg dentalcert-input-inset">
                                <input type="text" id="dc_proc_other_text" placeholder="Other procedure details"/>
                            </div>
                        </div>

                        <div class="fg">
                            <label>This certification is being issued upon the request of the patient for</label>
                            <input type="text" id="dc_purpose" placeholder="e.g. OJT / Employment / School Requirement"/>
                        </div>

                        <div class="form-section-title">Issuing Dentist</div>

                        <div class="fg">
                            <label>Dentist Name</label>
                            <select id="dc_sig_name" data-signing-role="dentist" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select dentist</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Title / Designation</label>
                            <input type="text" id="dc_sig_title" value="School Dentist" placeholder="School Dentist" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="dc_sig_license" value="0056828" placeholder="0056828"/>
                        </div>
                        <div class="fg">
                            <label>PTR No.</label>
                            <input type="text" id="dc_sig_ptr" value="" placeholder="PTR No."/>
                        </div>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('dentalcert-form', 'dental_certificate')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="dentalcert-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="dentalcert-iframe" src="" style="display:none;" title="Dental Certificate Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — PRESCRIPTION
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-prescription">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="prescription_search_icon"></i>
                        <input type="text" id="prescription_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('prescription', this.value);}"
                            oninput="document.getElementById('prescription_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="prescription_search_clear"
                            onclick="var i=document.getElementById('prescription_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearPrescriptionForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="prescriptionPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="prescription-form" class="doc-form-panel" oninput="renderPrescriptionPreview()" onchange="renderPrescriptionPreview()">
                        <h3><i class="fa fa-prescription"></i> Prescription Form Physician</h3>

                        <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="rx_full_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="text" id="rx_age" placeholder="21"/>
                            </div>
                            <div class="fg">
                                <label>Date</label>
                                <input type="date" id="rx_date"/>
                            </div>
                        </div>

                        <div class="form-section-title">Prescription Notes</div>
                        <div class="fg">
                            <label>Medications / Instructions (printed on all 4 cards)</label>
                            <textarea id="rx_notes" rows="8" placeholder="1. Paracetamol 500mg — 1 tab every 6 hours for fever&#10;2. Amoxicillin 500mg — 1 cap every 8 hours for 7 days&#10;3. Continue hydration and rest"></textarea>
                        </div>

                        <div class="form-section-title">Issuing Physician</div>

                        <div class="fg">
                            <label>Physician Name</label>
                            <select id="rx_physician_name" data-signing-role="physician" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select physician</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Designation</label>
                            <input type="text" id="rx_physician_role" value="School Physician" placeholder="School Physician" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="rx_physician_lic" value="85606" placeholder="85606"/>
                        </div>
                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The same details are duplicated across all 4 cards in the printed page. Use one printed page for up to 4 separate prescriptions.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('prescription-form', 'physician_prescription', 'Prescription sent to the patient’s Prescriptions page.')">
                            Send to Patient
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="prescription-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="prescription-iframe" src="" style="display:none;" title="Prescription Form Print"></iframe>
                </div>
            </div>

            <div class="uh-page" id="page-form-prescription-dentist">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="prescription_dentist_search_icon"></i>
                        <input type="text" id="prescription_dentist_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('prescription-dentist', this.value);}"
                            oninput="document.getElementById('prescription_dentist_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="prescription_dentist_search_clear"
                            onclick="var i=document.getElementById('prescription_dentist_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearPrescriptionDentistForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="prescriptionDentistPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="prescription-dentist-form" class="doc-form-panel" oninput="renderPrescriptionDentistPreview()" onchange="renderPrescriptionDentistPreview()">
                        <h3><i class="fa fa-tooth"></i> Prescription Form Dentist</h3>

                        <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="rxd_full_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="text" id="rxd_age" placeholder="21"/>
                            </div>
                            <div class="fg">
                                <label>Date</label>
                                <input type="date" id="rxd_date"/>
                            </div>
                        </div>

                        <div class="form-section-title">Prescription Notes</div>
                        <div class="fg">
                            <label>Medications / Instructions (printed on all 4 cards)</label>
                            <textarea id="rxd_notes" rows="8" placeholder="1. Mefenamic Acid 500mg — 1 tab every 8 hours for pain&#10;2. Amoxicillin 500mg — 1 cap every 8 hours for 7 days&#10;3. Warm saline gargle 3x daily"></textarea>
                        </div>

                        <div class="form-section-title">Issuing Dentist</div>

                        <div class="fg">
                            <label>Dentist Name</label>
                            <select id="rxd_physician_name" data-signing-role="dentist" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select dentist</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Designation</label>
                            <input type="text" id="rxd_physician_role" value="School Dentist" placeholder="School Dentist" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="rxd_physician_lic" value="" placeholder="e.g. 0056828"/>
                        </div>
                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The same details are duplicated across all 4 cards in the printed page. Use one printed page for up to 4 separate prescriptions.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('prescription-dentist-form', 'dentist_prescription', 'Prescription sent to the patient’s Prescriptions page.')">
                            Send to Patient
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="prescription-dentist-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="prescription-dentist-iframe" src="" style="display:none;" title="Prescription Form Dentist Print"></iframe>
                </div>
            </div>

            <div class="uh-page" id="page-form-referral">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="referral_search_icon"></i>
                        <input type="text" id="referral_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('referral', this.value);}"
                            oninput="document.getElementById('referral_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="referral_search_clear"
                            onclick="var i=document.getElementById('referral_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearReferralForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="referralPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="referral-form" class="doc-form-panel" oninput="renderReferralPreview()" onchange="renderReferralPreview()">
                        <h3><i class="fa fa-share-from-square"></i> Physician Referral Form</h3>

                        <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="rf_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="text" id="rf_age" placeholder="21"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Address</label>
                            <input type="text" id="rf_address" placeholder="Barangay, City, Province"/>
                        </div>

                        <div class="fg-row">
                            <div class="fg">
                                <label>Urgency</label>
                                <select id="rf_urgency">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-section-title">Clinical Information</div>

                        <div class="fg">
                            <label>Chief Complaint/s</label>
                            <textarea id="rf_chief_complaint" rows="2" placeholder="e.g. Persistent headache for 3 days, dizziness"></textarea>
                        </div>
                        <div class="fg">
                            <label>Vital Signs</label>
                            <input type="text" id="rf_vital_signs" placeholder="e.g. BP 120/80, T 37°C, PR 80, RR 18"/>
                        </div>
                        <div class="fg">
                            <label>Remarks</label>
                            <textarea id="rf_remarks" rows="3" placeholder="Additional notes for the receiving physician / facility"></textarea>
                        </div>

                        <div class="form-section-title">Issuing Physician</div>

                        <div class="fg">
                            <label>Physician Name</label>
                            <select id="rf_physician_name" data-signing-role="physician" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select physician</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Designation</label>
                            <input type="text" id="rf_physician_role" value="School Physician" placeholder="School Physician" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="rf_physician_lic" value="" placeholder="85606"/>
                        </div>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The same details are duplicated across all 4 cards in the printed page. Use one printed page for up to 4 separate referrals.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveReferralForm()">
                            Save Referral
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="referral-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="referral-iframe" src="" style="display:none;" title="Referral Form Print"></iframe>
                </div>
            </div>

            <div class="uh-page" id="page-form-referral-dentist">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="referral_dentist_search_icon"></i>
                        <input type="text" id="referral_dentist_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('referral-dentist', this.value);}"
                            oninput="document.getElementById('referral_dentist_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="referral_dentist_search_clear"
                            onclick="var i=document.getElementById('referral_dentist_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearReferralDentistForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="referralDentistPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="referral-dentist-form" class="doc-form-panel" oninput="renderReferralDentistPreview()" onchange="renderReferralDentistPreview()">
                        <h3><i class="fa fa-tooth"></i> Dentist Referral Form</h3>

                        <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="rfd_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="text" id="rfd_age" placeholder="21"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Address</label>
                            <input type="text" id="rfd_address" placeholder="Barangay, City, Province"/>
                        </div>

                        <div class="fg-row">
                            <div class="fg">
                                <label>Urgency</label>
                                <select id="rfd_urgency">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-section-title">Clinical Information</div>

                        <div class="fg">
                            <label>Chief Complaint/s</label>
                            <textarea id="rfd_chief_complaint" rows="2" placeholder="e.g. Severe toothache, swelling on lower right molar"></textarea>
                        </div>
                        <div class="fg">
                            <label>Vital Signs</label>
                            <input type="text" id="rfd_vital_signs" placeholder="e.g. BP 120/80, T 37°C, PR 80, RR 18"/>
                        </div>
                        <div class="fg">
                            <label>Remarks</label>
                            <textarea id="rfd_remarks" rows="3" placeholder="Additional notes for the receiving dentist / facility"></textarea>
                        </div>

                        <div class="form-section-title">Issuing Dentist</div>

                        <div class="fg">
                            <label>Dentist Name</label>
                            <select id="rfd_physician_name" data-signing-role="dentist" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select dentist</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Designation</label>
                            <input type="text" id="rfd_physician_role" value="School Dentist" placeholder="School Dentist" readonly/>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="rfd_physician_lic" value="" placeholder="0056828"/>
                        </div>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The same details are duplicated across all 4 cards in the printed page. Use one printed page for up to 4 separate referrals.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveReferralDentistForm()">
                            Save Referral
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="referral-dentist-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="referral-dentist-iframe" src="" style="display:none;" title="Referral Form Dentist Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — LABORATORY REQUEST
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-lab">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="lab_search_icon"></i>
                        <input type="text" id="lab_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('lab', this.value);}"
                            oninput="document.getElementById('lab_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="lab_search_clear"
                            onclick="var i=document.getElementById('lab_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearLabForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="labPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="lab-form" class="doc-form-panel" oninput="renderLabPreview()" onchange="renderLabPreview()">
                        <h3><i class="fa fa-vial-circle-check"></i> Laboratory Request Form</h3>

                        <div class="form-section-title">Patient Details</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="lab_name" placeholder="e.g. Mr. Juan Dela Cruz"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Age</label>
                                <input type="text" id="lab_age" placeholder="21"/>
                            </div>
                            <div class="fg">
                                <label>Sex</label>
                                <select id="lab_sex">
                                    <option value="">—</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Date of Birth</label>
                            <input type="date" id="lab_dob"/>
                        </div>
                        <div class="fg">
                            <label>Address</label>
                            <input type="text" id="lab_address" placeholder="Barangay, City, Province"/>
                        </div>

                        <div class="form-section-title">Request Metadata</div>

                        <div class="fg">
                            <label>Date Requested</label>
                            <input type="date" id="lab_date_requested"/>
                        </div>

                        <div class="form-section-title">Issuing Physician</div>

                        <div class="fg">
                            <label>Physician Name</label>
                            <select id="lab_physician_name" data-signing-role="physician" onchange="updateSigningPersonnelPreview(this)">
                                <option value="">Select physician</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label>License No.</label>
                            <input type="text" id="lab_physician_lic" value="85606" placeholder="85606"/>
                        </div>
                        <div class="fg">
                            <label>PTR No.</label>
                            <input type="text" id="lab_physician_ptr" value="" placeholder="Optional"/>
                        </div>

                        <div class="form-section-title">Tests Requested</div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:0.75rem;">
                            <div style="font-weight:700;font-size:0.7rem;color:#1B4332;text-transform:uppercase;letter-spacing:0.4px;margin-top:6px;">Hematology</div>
                            <div style="font-weight:700;font-size:0.7rem;color:#1B4332;text-transform:uppercase;letter-spacing:0.4px;margin-top:6px;">Chemistry</div>
                            <label class="cb-item"><input type="checkbox" id="lab_t_cbc" onchange="renderLabPreview()"> CBC</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_bun" onchange="renderLabPreview()"> BUN</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_cbc_plt" onchange="renderLabPreview()"> CBC WITH PLATELET</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_creatinine" onchange="renderLabPreview()"> CREATININE</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_platelet" onchange="renderLabPreview()"> PLATELET COUNT</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_sgpt" onchange="renderLabPreview()"> SGPT / SGOT</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_ctbt" onchange="renderLabPreview()"> CT, BT</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_rbs" onchange="renderLabPreview()"> RBS</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_esr" onchange="renderLabPreview()"> ESR</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_fbs" onchange="renderLabPreview()"> FBS</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_abo" onchange="renderLabPreview()"> ABO TYPING</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_lipid" onchange="renderLabPreview()"> LIPID PROFILE</label>

                            <div style="font-weight:700;font-size:0.7rem;color:#1B4332;text-transform:uppercase;letter-spacing:0.4px;margin-top:6px;">Microscopy</div>
                            <label class="cb-item"><input type="checkbox" id="lab_t_uric" onchange="renderLabPreview()"> URIC ACID</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_urinalysis" onchange="renderLabPreview()"> URINALYSIS</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_hba1c" onchange="renderLabPreview()"> HBA1C</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_fecalysis" onchange="renderLabPreview()"> FECALYSIS</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_tsh" onchange="renderLabPreview()"> TSH</label>

                            <div style="font-weight:700;font-size:0.7rem;color:#1B4332;text-transform:uppercase;letter-spacing:0.4px;margin-top:6px;">Serology</div>
                            <label class="cb-item"><input type="checkbox" id="lab_t_t3t4" onchange="renderLabPreview()"> T3, T4</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_hbsag" onchange="renderLabPreview()"> HbsAg</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_sodium" onchange="renderLabPreview()"> SODIUM</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_preg" onchange="renderLabPreview()"> Pregnancy Test</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_potassium" onchange="renderLabPreview()"> POTASSIUM</label>

                            <div style="font-weight:700;font-size:0.7rem;color:#1B4332;text-transform:uppercase;letter-spacing:0.4px;margin-top:6px;">Others</div>
                            <label class="cb-item"><input type="checkbox" id="lab_t_chloride" onchange="renderLabPreview()"> CHLORIDE</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_xray" onchange="renderLabPreview()"> CHEST XRAY-PA</label>
                            <label class="cb-item"><input type="checkbox" id="lab_t_calcium" onchange="renderLabPreview()"> CALCIUM</label>
                        </div>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            Checked tests will be marked on all 4 cards.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('lab-form', 'laboratory_request')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="lab-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="lab-iframe" src="" style="display:none;" title="Laboratory Request Form Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — X-RAY FORM
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-xray">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="xray_search_icon"></i>
                        <input type="text" id="xray_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('xray', this.value);}"
                            oninput="document.getElementById('xray_search_clear').style.display=this.value?'flex':'none';"/>
                        <button type="button" id="xray_search_clear"
                            onclick="var i=document.getElementById('xray_search');i.value='';i.focus();i.dispatchEvent(new Event('input'));"
                            style="display:none;background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:0 4px;font-size:0.95rem;line-height:1;"
                            title="Clear search">&times;</button>
                    </div>
                    <button class="btn-action" type="button" onclick="clearXrayForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="xrayPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="xray-form" class="doc-form-panel" oninput="renderXrayPreview()" onchange="renderXrayPreview()">
                        <h3><i class="fa fa-x-ray"></i> X-Ray Form</h3>

                        <div class="form-section-title">Patient Details (applied to all 4 cards)</div>

                        <div class="fg">
                            <label>Full Name</label>
                            <input type="text" id="xr_name" placeholder="e.g. Juan Dela Cruz"/>
                        </div>
                        <div class="fg">
                            <label>Course &amp; Year</label>
                            <input type="text" id="xr_course" placeholder="e.g. BSN 2"/>
                        </div>

                        <div class="form-section-title">Clinic Nurse / Staff</div>

                        <div class="fg">
                            <label>Clinic Nurse / Staff</label>
                            <select id="xr_staff" data-signing-role="nurse" onchange="renderXrayPreview()">
                                <option value="">Select nurse / staff</option>
                            </select>
                        </div>

                        <div class="form-section-title">Chest X-ray Result</div>

                        <label class="cb-item" style="font-size:0.82rem;">
                            <input type="checkbox" id="xr_checked" onchange="renderXrayPreview()">
                            Mark Chest X-ray Result as checked
                        </label>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The same details are duplicated across all 4 cards. One printed page yields 4 slips.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('xray-form', 'xray')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="xray-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:'Times New Roman',Arial,serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="xray-iframe" src="" style="display:none;" title="X-Ray Form Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — LACTATING MOTHER ACCOMMODATION
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-lactating">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="lactating_search_icon"></i>
                        <input type="text" id="lactating_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('lactating', this.value);}"/>
                    </div>
                    <button class="btn-action" type="button" onclick="clearLactatingForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="lactatingPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="lactating-form" class="doc-form-panel" oninput="renderLactatingPreview()" onchange="renderLactatingPreview()">
                        <h3><i class="fa fa-baby"></i> Lactating Mother Accommodation</h3>

                        <div class="form-section-title">Employee / Student Information</div>

                        <div class="fg">
                            <label>Employee / Student Complete Name</label>
                            <input type="text" id="lm_name" placeholder="e.g. Maria Santos"/>
                        </div>
                        <div class="fg">
                            <label>Department / College</label>
                            <input type="text" id="lm_dept" placeholder="e.g. Bachelor of Science in Nursing (BSN)"/>
                        </div>
                        <div class="fg">
                            <label>Job Title (if employee)</label>
                            <input type="text" id="lm_jobtitle" placeholder="e.g. Administrative Officer"/>
                        </div>
                        <div class="fg">
                            <label>Email Address</label>
                            <input type="email" id="lm_email" placeholder="e.g. maria@sunn.edu.ph"/>
                        </div>
                        <div class="fg">
                            <label>Contact Details</label>
                            <input type="text" id="lm_contact" placeholder="e.g. 09171234567"/>
                        </div>

                        <div class="form-section-title">Child &amp; Schedule Details</div>

                        <div class="fg">
                            <label>Child's Date of Birth</label>
                            <input type="date" id="lm_dob"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Requested Begin Date</label>
                                <input type="date" id="lm_begin"/>
                            </div>
                            <div class="fg">
                                <label>Requested End Date</label>
                                <input type="date" id="lm_end"/>
                            </div>
                        </div>
                        <div class="fg">
                            <label>Describe the accommodation requested</label>
                            <textarea id="lm_description" rows="3" placeholder="e.g. 15-minute break at 10:00 AM and 2:00 PM daily to express breast milk."></textarea>
                        </div>

                        <div class="form-section-title">Signatories</div>

                        <div class="fg">
                            <label>Requested by</label>
                            <input type="text" id="lm_requested_by" placeholder="Name &amp; signature"/>
                        </div>
                        <div class="fg">
                            <label>Recommending Approval</label>
                            <input type="text" id="lm_recommending" placeholder="Name &amp; signature"/>
                        </div>
                        <div class="fg">
                            <label>Approved by</label>
                            <input type="text" id="lm_approved_by" placeholder="Name &amp; signature"/>
                        </div>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            Submit the completed form to immediate supervisors for review and approval.
                        </p>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('lactating-form', 'lactating_accommodation')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="lactating-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:Arial,sans-serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="lactating-iframe" src="" style="display:none;" title="Lactating Mother Form Print"></iframe>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 PAGE: GENERATE FORMS — BREASTFEEDING ROOM USER AGREEMENT
            ════════════════════════════════════════ -->
            <div class="uh-page" id="page-form-breastfeeding">
                <div class="print-toolbar">
                    <div class="search-toolbar">
                        <i class="fa fa-search" id="breastfeeding_search_icon"></i>
                        <input type="text" id="breastfeeding_search"
                            placeholder="Search student by ID or name…"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();handleFormSearch('breastfeeding', this.value);}"/>
                    </div>
                    <button class="btn-action" type="button" onclick="clearBreastfeedingForm()">
                        <i class="fa fa-eraser"></i> Clear
                    </button>
                    <button class="btn-action btn-action-print" type="button" onclick="breastfeedingPrint()">
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="doc-layout">
                    <!-- LEFT: input panel -->
                    <form id="breastfeeding-form" class="doc-form-panel" oninput="renderBreastfeedingPreview()" onchange="renderBreastfeedingPreview()">
                        <h3><i class="fa fa-baby-carriage"></i> Breastfeeding Room User Agreement</h3>

                        <div class="form-section-title">User Information</div>

                        <div class="fg">
                            <label>Name</label>
                            <input type="text" id="bf_name" placeholder="e.g. Maria Santos"/>
                        </div>
                        <div class="fg">
                            <label>Department / College</label>
                            <input type="text" id="bf_dept" placeholder="e.g. Bachelor of Science in Nursing (BSN)"/>
                        </div>
                        <div class="fg">
                            <label>Job Title / Location (if employee)</label>
                            <input type="text" id="bf_jobtitle" placeholder="e.g. Administrative Officer"/>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>E-mail Address</label>
                                <input type="email" id="bf_email" placeholder="e.g. maria@sunn.edu.ph"/>
                            </div>
                            <div class="fg">
                                <label>Contact Details</label>
                                <input type="text" id="bf_contact" placeholder="e.g. 09171234567"/>
                            </div>
                        </div>

                        <p style="font-size:0.78rem;color:#888;margin-top:10px;line-height:1.5;">
                            <i class="fa fa-info-circle"></i>
                            The user must put a checkmark on each agreement statement before signing.
                        </p>

                        <div class="form-section-title">Agreement Checkboxes</div>

                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a1" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>I agree that it is my choice to use the Breastfeeding Room…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a2" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>I agree that I am responsible for keeping the room clean and orderly…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a3" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>Out of respect for other nursing mothers, access is for my use only…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a4" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>I will only use the room for expressing breast milk…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a5" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>I am responsible for storage of milk and bringing my own breast pump…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a6" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>My name may be shared with University employees for compliance…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a7" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>I understand/agree to stop using the facility three years from birth…</span>
                            </label>
                            <label class="cb-item" style="font-size:0.75rem;align-items:flex-start;gap:6px;">
                                <input type="checkbox" id="bf_a8" onchange="renderBreastfeedingPreview()" style="margin-top:2px;flex-shrink:0;">
                                <span>Failure to comply could cause loss of access to the facility…</span>
                            </label>
                        </div>
                        <button class="uh-btn uh-btn-primary" type="button" style="display:block;min-width:88px;margin:16px 0 0 auto;padding:8px 18px;" onclick="saveGeneratedForm('breastfeeding-form', 'breastfeeding_agreement')">
                            Save
                        </button>
                    </form>

                    <!-- RIGHT: A4 preview (instant JS render) -->
                    <div class="doc-preview-wrap">
                        <div id="breastfeeding-preview" style="width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 32px rgba(0,0,0,0.22);border-radius:3px;flex-shrink:0;zoom:0.72;font-family:Arial,sans-serif;font-size:13px;color:#000;"></div>
                    </div>
                    {{-- Hidden iframe used only for printing --}}
                    <iframe id="breastfeeding-iframe" src="" style="display:none;" title="Breastfeeding Room Agreement Print"></iframe>
                </div>
            </div>
