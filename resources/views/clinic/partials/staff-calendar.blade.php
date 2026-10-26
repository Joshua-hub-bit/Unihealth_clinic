<div class="uh-page nurse-calendar-page" id="page-calendar" data-timezone="{{ config('app.timezone') }}">
    <div class="nurse-calendar-intro">
        <div>
            <span class="nurse-calendar-eyebrow"><i class="fa fa-calendar-days"></i> Clinic schedule</span>
            <h2>Calendar</h2>
            <p>See scheduled clinic check-ups and physician or dentist appointments.</p>
        </div>
        <button class="nurse-calendar-refresh" type="button" onclick="clinicCalendarLoad(true).catch(function(){})">
            <i class="fa fa-rotate"></i><span>Refresh</span>
        </button>
    </div>
    <div class="nurse-calendar-grid">
        <section class="nurse-calendar-card nurse-calendar-card-clinic" aria-labelledby="calendar-clinic-title">
            <header class="nurse-calendar-card-header">
                <span class="nurse-calendar-card-icon"><i class="fa fa-user-nurse"></i></span>
                <div>
                    <h3 id="calendar-clinic-title">Nurse &amp; Head Nurse</h3>
                    <p>Scheduled clinic check-ups</p>
                </div>
                <span class="nurse-calendar-count" id="calendar-clinic-count">—</span>
            </header>
            <div class="nurse-calendar-toolbar">
                <button type="button" aria-label="Previous month" onclick="clinicCalendarMove('clinic',-1)"><i class="fa fa-chevron-left"></i></button>
                <strong id="calendar-clinic-month" aria-live="polite"></strong>
                <button type="button" aria-label="Next month" onclick="clinicCalendarMove('clinic',1)"><i class="fa fa-chevron-right"></i></button>
                <button class="nurse-calendar-today" type="button" onclick="clinicCalendarToday('clinic')">Today</button>
            </div>
            <div class="nurse-calendar-weekdays" aria-hidden="true">
                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>
            <div class="nurse-calendar-days" id="calendar-clinic-days" role="grid" aria-label="Nurse and head nurse appointments calendar"></div>
            <div class="nurse-calendar-legend"><span class="nurse-calendar-dot"></span> Scheduled check-up</div>
            <div class="nurse-calendar-events" id="calendar-clinic-events" aria-live="polite"></div>
        </section>

        <section class="nurse-calendar-card nurse-calendar-card-provider" aria-labelledby="calendar-provider-title">
            <header class="nurse-calendar-card-header">
                <span class="nurse-calendar-card-icon"><i class="fa fa-user-doctor"></i></span>
                <div>
                    <h3 id="calendar-provider-title">Physician &amp; Dentist</h3>
                    <p>Confirmed provider appointments</p>
                </div>
                <span class="nurse-calendar-count" id="calendar-provider-count">—</span>
            </header>
            <div class="nurse-calendar-toolbar">
                <button type="button" aria-label="Previous month" onclick="clinicCalendarMove('provider',-1)"><i class="fa fa-chevron-left"></i></button>
                <strong id="calendar-provider-month" aria-live="polite"></strong>
                <button type="button" aria-label="Next month" onclick="clinicCalendarMove('provider',1)"><i class="fa fa-chevron-right"></i></button>
                <button class="nurse-calendar-today" type="button" onclick="clinicCalendarToday('provider')">Today</button>
            </div>
            <div class="nurse-calendar-weekdays" aria-hidden="true">
                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>
            <div class="nurse-calendar-days" id="calendar-provider-days" role="grid" aria-label="Physician and dentist appointments calendar"></div>
            <div class="nurse-calendar-legend">
                <span><i class="nurse-calendar-dot nurse-calendar-dot-physician"></i> Physician</span>
                <span><i class="nurse-calendar-dot nurse-calendar-dot-dentist"></i> Dentist</span>
            </div>
            <div class="nurse-calendar-events" id="calendar-provider-events" aria-live="polite"></div>
        </section>
    </div>
    <dialog class="nurse-calendar-dialog" id="calendar-day-dialog" aria-labelledby="calendar-day-dialog-title" onclick="if(event.target===this)this.close()">
        <div class="nurse-calendar-dialog-header">
            <div>
                <span class="nurse-calendar-eyebrow"><i class="fa fa-calendar-check"></i> Selected date</span>
                <h2 id="calendar-day-dialog-title">Appointments</h2>
                <p id="calendar-day-dialog-summary"></p>
            </div>
            <button class="nurse-calendar-dialog-close" type="button" onclick="document.getElementById('calendar-day-dialog').close()" aria-label="Close appointment details">
                <i class="fa fa-xmark"></i>
            </button>
        </div>
        <div class="nurse-calendar-dialog-events" id="calendar-day-dialog-events"></div>
    </dialog>
</div>
