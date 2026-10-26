<div class="uh-page" id="page-medical-records" data-clinic-logo="{{ asset('clinic/images/sunn-logo.png') }}">
    <section id="mre-campus-section" class="mre-card">
        <div class="mre-header">
            <div>
                <h2 class="mre-title">Medical Records</h2>
                <p class="mre-subtitle">Choose a campus to view its registered students.</p>
            </div>
        </div>
        <div id="mre-campus-grid" class="mre-card-grid" aria-live="polite">
            <div class="mre-state">Loading campuses…</div>
        </div>
    </section>

    <section id="dre-campus-section" class="mre-card">
        <div class="mre-header">
            <div>
                <h2 class="mre-title">Dental Records</h2>
                <p class="mre-subtitle">Choose a campus to view its dental record list.</p>
            </div>
        </div>
        <div id="dre-campus-grid" class="mre-card-grid" aria-live="polite">
            <div class="mre-state">Loading campuses…</div>
        </div>
        <section id="dre-record-section" class="mre-nested-section" hidden>
            <div class="mre-header">
                <div>
                    <button type="button" id="dre-back-campuses" class="mre-back-button">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to campuses
                    </button>
                    <h2 id="dre-campus-title" class="mre-title">Dental Records</h2>
                    <p class="mre-subtitle">Saved dental records for this campus.</p>
                </div>
                <div class="mre-filter-controls">
                    <label class="mre-course-filter">
                        <span class="sr-only">Filter dental records by course</span>
                        <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                        <select id="dre-course-filter" aria-label="Filter dental records by course">
                            <option value="">All courses</option>
                        </select>
                    </label>
                    <label class="mre-search">
                        <span class="sr-only">Search dental records by student name or ID</span>
                        <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
                        <input id="dre-record-search" type="search" autocomplete="off" placeholder="Search by student name or ID…">
                    </label>
                </div>
            </div>
            <div class="uh-table-wrap">
                <table class="uh-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Dentist</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dre-record-body" aria-live="polite">
                        <tr><td colspan="6" class="no-data">Choose a campus to view its dental records.</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="dre-record-pagination" class="mre-pagination"></div>
        </section>
        <section id="dre-student-records" class="mre-nested-section" hidden>
            <div class="mre-header">
                <div>
                    <button type="button" id="dre-student-records-back" class="mre-back-button">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to campus dental records
                    </button>
                    <h2 id="dre-student-records-title" class="mre-title">Student Dental Records</h2>
                    <p class="mre-subtitle">All saved dental records for this student at this campus.</p>
                </div>
            </div>
            <div class="uh-table-wrap">
                <table class="uh-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Dentist</th>
                            <th>Findings and Recommendations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dre-student-records-body" aria-live="polite">
                        <tr><td colspan="4" class="no-data">Select a student to view their dental history.</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="dre-student-records-pagination" class="mre-pagination"></div>
        </section>
        <section id="dre-record-detail" class="mre-card" hidden aria-live="polite">
            <div class="mre-header">
                <div>
                    <button type="button" id="dre-detail-back" class="mre-back-button">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to student records
                    </button>
                    <h2 class="mre-title">Dental Record Details</h2>
                </div>
            </div>
            <div id="dre-record-detail-content" class="dre-record-form-view"></div>
        </section>
    </section>

    <section id="mre-student-section" class="mre-nested-section" hidden>
        <div class="mre-header">
            <div>
                <button type="button" id="mre-back-campuses" class="mre-back-button">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to campuses
                </button>
                <h2 id="mre-campus-title" class="mre-title">Students</h2>
                <p class="mre-subtitle">Select a student to view their medical record.</p>
            </div>
            <div class="mre-filter-controls">
                <label class="mre-course-filter">
                    <span class="sr-only">Filter students by course</span>
                    <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                    <select id="mre-course-filter" aria-label="Filter students by course">
                        <option value="">All courses</option>
                    </select>
                </label>
                <label class="mre-search">
                    <span class="sr-only">Search by student name or student ID in this campus</span>
                    <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="mre-student-search" type="search" autocomplete="off" placeholder="Search by name or student ID…">
                </label>
            </div>
        </div>
        <div class="uh-table-wrap">
            <table class="uh-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Age</th>
                        <th>Course / Year</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="mre-student-body" aria-live="polite">
                    <tr><td colspan="7" class="no-data">Choose a campus to view registered students.</td></tr>
                </tbody>
            </table>
        </div>
        <div id="mre-student-pagination" class="mre-pagination"></div>
    </section>

    <section id="mre-record-detail-panel" class="mre-card" hidden>
        <div class="mre-header">
            <div>
                <button type="button" id="mre-detail-back-students" class="mre-back-button">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to students
                </button>
                <h2 class="mre-title">Student Medical Record</h2>
                <p class="mre-subtitle">Visit history and clinical notes for this student.</p>
            </div>
            <button type="button" id="mre-add-record" class="uh-btn uh-btn-primary uh-btn-sm">
                <i class="fa fa-plus" aria-hidden="true"></i> Add Medical Record
            </button>
        </div>
        <div id="mre-record-student-profile" class="mre-student-preview"></div>
        <section class="mre-history-section">
            <div class="mre-history-heading">
                <h3>Visit History</h3>
                <p>Newest visits are shown first.</p>
            </div>
            <div id="mre-visit-list" class="mre-visit-list" aria-live="polite">
                <div class="mre-state">Choose a student to view their visit history.</div>
            </div>
            <div id="mre-visit-pagination" class="mre-pagination"></div>
        </section>
    </section>

    <section id="mre-form-panel" class="mre-card" hidden>
        <div class="mre-header">
            <div>
                <button type="button" id="mre-back-record" class="mre-back-button">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to medical record
                </button>
                <h2 class="mre-title">Create Medical Record</h2>
                <p class="mre-subtitle">Student information is filled from their registered profile.</p>
            </div>
        </div>

        <div id="mre-student-preview" class="mre-student-preview" aria-live="polite"></div>
        <form id="mre-form" class="mre-form">
            <input id="mre-student-id" name="student_id" type="hidden">

            <section class="mre-section">
                <h3>Visit Information</h3>
                <div class="mre-grid mre-grid-2">
                    <div class="mre-field">
                        <label for="mre-visit-date">Date <span aria-hidden="true">*</span></label>
                        <input id="mre-visit-date" name="visit_date" type="date" required>
                    </div>
                    <div class="mre-field">
                        <label for="mre-visit-time">Time recorded automatically</label>
                        <input id="mre-visit-time" name="visit_time" type="time" required readonly>
                    </div>
                    <div class="mre-field mre-span-2">
                        <label for="mre-reason">Chief Complaint <span aria-hidden="true">*</span></label>
                        <textarea id="mre-reason" name="reason" rows="3" maxlength="255" required></textarea>
                    </div>
                    <div class="mre-field mre-span-2">
                        <label for="mre-treatment">Treatment</label>
                        <textarea id="mre-treatment" name="treatment" rows="3" maxlength="10000"></textarea>
                    </div>
                </div>
            </section>

            <section class="mre-section">
                <h3>Vital Signs</h3>
                <div class="mre-grid mre-grid-4">
                    <div class="mre-field">
                        <label for="mre-bp">BP</label>
                        <input id="mre-bp" name="bp" type="text" maxlength="20" placeholder="120/80">
                    </div>
                    <div class="mre-field">
                        <label for="mre-temp">Temperature (°C)</label>
                        <input id="mre-temp" name="temp" type="number" min="25" max="50" step="0.1">
                    </div>
                    <div class="mre-field">
                        <label for="mre-pulse">PR (bpm)</label>
                        <input id="mre-pulse" name="pulse" type="number" min="0" max="300" step="1">
                    </div>
                    <div class="mre-field">
                        <label for="mre-rr">RR (breaths/min)</label>
                        <input id="mre-rr" name="rr" type="number" min="0" max="100" step="1">
                    </div>
                </div>
            </section>

            <section class="mre-section">
                <h3>Nurse’s Notes / Remarks</h3>
                <div class="mre-field">
                    <label for="mre-notes">Remarks and follow-up notes</label>
                    <textarea id="mre-notes" name="notes" rows="4" maxlength="10000"></textarea>
                </div>
            </section>

            <div id="mre-form-message" class="mre-message" role="status" aria-live="polite"></div>
            <div class="mre-actions">
                <button type="reset" class="uh-btn uh-btn-ghost">Clear Form</button>
                <button type="submit" class="uh-btn uh-btn-primary" id="mre-submit">
                    <i class="fa fa-floppy-disk" aria-hidden="true"></i> Save Medical Record
                </button>
            </div>
        </form>
    </section>
</div>
