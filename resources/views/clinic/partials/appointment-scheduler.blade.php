<div class="uh-page nurse-workflow-page" id="page-appointment-scheduler">
    <section class="uh-card">
        <div class="uh-card-header">
            <div>
                <div class="uh-card-title"><i class="fa fa-calendar-check" aria-hidden="true"></i> Schedule Appointment</div>
                <p class="uh-text-muted uh-text-sm" id="appointment-schedule-instructions">Set the initial clinic check-up time or schedule the required provider visit.</p>
            </div>
            <button type="button" class="uh-btn uh-btn-ghost uh-btn-sm" onclick="nReturnFromAppointmentScheduler()">
                <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to appointments
            </button>
        </div>
        <div class="uh-card-body">
            <div class="uh-grid-2 uh-mb-16" aria-live="polite">
                <div><strong>Patient</strong><p id="appointment-schedule-patient" class="uh-text-muted">—</p></div>
                <div><strong>Service</strong><p id="appointment-schedule-service" class="uh-text-muted">—</p></div>
                <div><strong>Campus</strong><p id="appointment-schedule-campus" class="uh-text-muted">—</p></div>
            </div>
            <p id="appointment-schedule-message" class="uh-text-muted" role="status">Choose an appointment to load published times.</p>
            <div id="appointment-schedule-empty" class="uh-mb-16" hidden>
                <p>No published provider times with remaining capacity are available for this appointment.</p>
                <button type="button" class="uh-btn uh-btn-outline" onclick="nOpenAvailabilityForAppointment()">
                    <i class="fa fa-calendar-plus" aria-hidden="true"></i> Open Availability Calendar
                </button>
            </div>
            <form id="appointment-checkup-form" onsubmit="nSubmitClinicCheckup(event)" hidden>
                <div class="uh-form-group">
                    <label for="appointment-checkup-date">Clinic check-up date</label>
                    <input type="date" id="appointment-checkup-date" required />
                </div>
                <div class="uh-form-group">
                    <label for="appointment-checkup-time">Clinic check-up time</label>
                    <input type="time" id="appointment-checkup-time" required />
                </div>
                <button type="submit" class="uh-btn uh-btn-primary" id="appointment-checkup-submit">
                    <i class="fa fa-calendar-check" aria-hidden="true"></i> Schedule Clinic Check-up
                </button>
            </form>
            <form id="appointment-schedule-form" onsubmit="nSubmitAppointmentSchedule(event)" hidden>
                <div class="uh-form-group">
                    <label for="appointment-schedule-slot">Provider appointment date and time</label>
                    <select id="appointment-schedule-slot" required>
                        <option value="">Loading available times…</option>
                    </select>
                </div>
                <button type="submit" class="uh-btn uh-btn-primary" id="appointment-schedule-submit">
                    <i class="fa fa-calendar-check" aria-hidden="true"></i> Confirm Schedule
                </button>
            </form>
        </div>
    </section>
</div>
