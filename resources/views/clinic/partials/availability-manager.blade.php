<div class="uh-page nurse-workflow-page" id="page-availability-calendar">
    <div class="availability-manager-primary">
    <section class="uh-card">
        <div class="uh-card-header">
            <div>
                <div class="uh-card-title"><i class="fa fa-calendar-days" aria-hidden="true"></i> Provider Availability &amp; Capacity</div>
                <p class="uh-text-muted uh-text-sm">Set each physician’s or dentist’s daily patient limit, clinic hours, and bookable appointment times. Each selected time is one patient slot.</p>
            </div>
            <button type="button" class="uh-btn uh-btn-ghost uh-btn-sm" onclick="nLoadAvailabilitySchedules()">
                <i class="fa fa-rotate" aria-hidden="true"></i> Refresh
            </button>
        </div>
        <div class="uh-card-body">
            <div class="availability-calendar-toolbar">
                @if(!empty($showCampusSelector))
                    <div class="uh-form-group nurse-head-only" hidden>
                        <label for="n-availability-campus">Campus</label>
                        <select id="n-availability-campus" name="campus_id">
                            <option value="">Choose a campus</option>
                        </select>
                    </div>
                @endif
                <div class="availability-calendar-navigation" aria-label="Calendar navigation">
                    <button class="uh-btn uh-btn-outline uh-btn-sm" type="button" onclick="nAvailabilityChangeMonth(-1)" aria-label="Previous month">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <h2 id="n-availability-month-label" aria-live="polite"></h2>
                    <button class="uh-btn uh-btn-outline uh-btn-sm" type="button" onclick="nAvailabilityChangeMonth(1)" aria-label="Next month">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button class="uh-btn uh-btn-ghost uh-btn-sm" type="button" onclick="nAvailabilityGoToToday()">Today</button>
                </div>
            </div>
            <div id="n-availability-calendar" class="availability-calendar" aria-label="Monthly availability calendar" aria-live="polite">
                <p class="uh-text-muted">Choose a campus to view its schedules.</p>
            </div>
            <div class="availability-calendar-legend" aria-label="Calendar legend">
                <span><i class="availability-calendar-dot availability-calendar-dot--physician"></i> Physician</span>
                <span><i class="availability-calendar-dot availability-calendar-dot--dentist"></i> Dentist</span>
                <span>Click a schedule to edit it, or select a day to publish availability.</span>
            </div>
        </div>
    </section>

    <section class="uh-card">
        <div class="uh-card-header">
            <div>
                <div class="uh-card-title"><i class="fa fa-calendar-plus" aria-hidden="true"></i> Set a provider’s daily schedule</div>
                <p class="uh-text-muted uh-text-sm">Choose a provider and date, then publish appointment times or mark the provider not available. Existing appointments are not canceled.</p>
            </div>
        </div>
        <div class="uh-card-body">
            <form id="n-availability-form" onsubmit="nSaveAvailability(event)">
                <div class="uh-grid-2 uh-mb-16">
                    <div class="uh-form-group">
                        <label for="n-availability-provider">Professional</label>
                        <select id="n-availability-provider" required>
                            <option value="physician">Physician</option>
                            <option value="dentist">Dentist</option>
                        </select>
                    </div>
                    <div class="uh-form-group">
                        <label for="n-availability-date">Date</label>
                        <input id="n-availability-date" type="date" required>
                    </div>
                    <div class="uh-form-group">
                        <label for="n-availability-status">Schedule status</label>
                        <select id="n-availability-status" onchange="nAvailabilityToggleStatus()" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Not available</option>
                        </select>
                    </div>
                </div>
                <div id="n-availability-details">
                    <div class="uh-grid-2 uh-mb-16">
                        <div class="uh-form-group">
                            <label for="n-availability-capacity">Maximum patients for this day (up to 10)</label>
                            <input id="n-availability-capacity" type="number" min="1" max="10" value="10" required>
                        </div>
                        <div class="uh-form-group">
                            <label for="n-availability-opens">Opening time</label>
                            <input id="n-availability-opens" type="time" min="08:00" max="16:59" value="08:00" required>
                        </div>
                        <div class="uh-form-group">
                            <label for="n-availability-closes">Closing time</label>
                            <input id="n-availability-closes" type="time" min="08:01" max="17:00" value="17:00" required>
                        </div>
                    </div>
                    <div class="uh-form-group uh-mb-16">
                        <label>Select bookable appointment times</label>
                        <div id="n-availability-times" class="availability-time-options" role="group" aria-label="Bookable appointment times"></div>
                        <p id="n-availability-times-count" class="uh-text-muted uh-text-sm" aria-live="polite">Select at least one time.</p>
                        <p class="uh-text-muted uh-text-sm">Times must be within opening hours. Each time can be booked by one student. Pending requests hold the time until accepted or rejected.</p>
                    </div>
                </div>
                <p id="n-availability-unavailable-note" class="uh-text-muted uh-text-sm" role="status" hidden>Marking a provider not available does not cancel or move existing appointments.</p>
                <button type="submit" class="uh-btn uh-btn-primary" id="n-availability-save">
                    <i class="fa fa-calendar-plus" aria-hidden="true"></i> Publish availability
                </button>
            </form>
        </div>
    </section>
    </div>

    <section class="uh-card uh-mt-16">
        <div class="uh-card-header">
            <div class="uh-card-title"><i class="fa fa-list" aria-hidden="true"></i> Schedule details</div>
        </div>
        <div class="uh-card-body" id="n-availability-list" aria-live="polite">Open the calendar to load schedules.</div>
    </section>
</div>
