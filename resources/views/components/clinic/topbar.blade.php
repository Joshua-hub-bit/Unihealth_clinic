@props(['initialRole' => 'Patient / Student'])

<div class="uh-topbar">
    <button type="button" class="uh-hamburger" onclick="uhToggleDrawer()" aria-label="Open menu" title="Open menu">
        <i class="fa fa-bars" aria-hidden="true"></i>
    </button>
    <div class="uh-topbar-heading">
        <h1 class="uh-topbar-title" id="uh-page-title">Dashboard</h1>
        <div class="uh-topbar-date"><i class="fa fa-calendar-day" aria-hidden="true"></i><span id="uh-topbar-date-value"></span><span aria-hidden="true">·</span><span id="uh-topbar-time-value"></span></div>
    </div>
    <div class="uh-topbar-right">
        <div class="uh-campus-selector">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span id="uh-campus-name">Main Campus</span>
        </div>
        <div class="uh-notif-wrap">
            <button class="uh-notif-btn" id="uh-notif-btn" title="Notifications" aria-label="Notifications" aria-expanded="false">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="uh-notif-count" id="uh-notif-count" style="display:none">0</span>
            </button>
            <div class="uh-notif-panel" id="uh-notif-panel" hidden>
                <div class="uh-notif-panel-header">
                    <strong>Notifications</strong>
                    <button type="button" id="uh-notif-read-all">Mark all read</button>
                </div>
                <div id="uh-notif-list" class="uh-notif-list">
                    <p class="uh-notif-empty">Loading notifications...</p>
                </div>
            </div>
        </div>
        <div class="uh-account-menu">
            <button type="button" class="uh-role-dropdown" id="uh-role-dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="uh-role-dropdown-text">{{ $initialRole }}</span>
                <span class="uh-role-dropdown-caret" aria-hidden="true">▾</span>
            </button>
            <div class="uh-account-panel" id="uh-account-panel" hidden>
                <div class="uh-account-panel-heading">
                    <strong id="uh-account-panel-name">Account</strong>
                    <small id="uh-account-panel-id"></small>
                </div>
                <div class="uh-account-panel-actions">
                    <button type="button" class="uh-account-action" data-account-action="profile">
                        <i class="fa fa-user" aria-hidden="true"></i>
                        <span>Profile</span>
                    </button>
                    <button type="button" class="uh-account-action" data-account-action="password">
                        <i class="fa fa-key" aria-hidden="true"></i>
                        <span>Change password</span>
                    </button>
                    <button type="button" class="uh-account-action uh-account-action-danger" data-account-action="logout">
                        <i class="fa fa-right-from-bracket" aria-hidden="true"></i>
                        <span>Log out</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="uh-account-modal" id="uh-account-modal" hidden>
    <div class="uh-account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="uh-account-modal-title">
        <div class="uh-modal-header">
            <div class="uh-modal-title" id="uh-account-modal-title">Profile</div>
            <button type="button" class="uh-modal-close" id="uh-account-modal-close" aria-label="Close">×</button>
        </div>
        <div id="uh-account-modal-content">
            <div class="uh-profile-photo-editor">
                <div class="uh-profile-avatar" id="uh-profile-avatar">
                    <span id="uh-profile-avatar-initial">U</span>
                    <img id="uh-profile-avatar-image" alt="Profile photo" hidden>
                </div>
                <div>
                    <strong>Profile photo</strong>
                    <small>JPG, PNG, or WEBP up to 2 MB</small>
                    <input type="file" id="uh-profile-photo-input" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="uh-btn uh-btn-ghost uh-btn-sm" id="uh-profile-photo-button">Add photo</button>
                </div>
            </div>
            <form id="uh-profile-form" class="uh-profile-form">
                <div class="uh-profile-form-grid">
                    <label>First name<input type="text" name="first_name" required maxlength="80"></label>
                    <label>Last name<input type="text" name="last_name" required maxlength="80"></label>
                    <label>Middle name<input type="text" name="middle_name" maxlength="80"></label>
                    <label>Contact number<input type="text" name="contact" maxlength="30"></label>
                    <label class="uh-profile-form-wide" data-student-profile-field>Address<input type="text" name="address" maxlength="255"></label>
                    <label data-student-profile-field>Department<input type="text" name="department" maxlength="120"></label>
                    <label data-student-profile-field data-student-academic-profile-field>Course / year<input type="text" name="course_year" maxlength="100"></label>
                    <label data-student-profile-field data-student-academic-profile-field>Section<input type="text" name="section" maxlength="30"></label>
                    <label data-student-profile-field>Sex
                        <select name="sex">
                            <option value="">Prefer not to say</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </label>
                    <label data-student-profile-field>Civil status
                        <select name="civil_status">
                            <option value="">Prefer not to say</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </label>
                    <label data-staff-profile-field hidden>Specialization<input type="text" name="specialization" maxlength="150"></label>
                </div>
                <div class="uh-profile-readonly">
                    <span><strong>Account ID</strong><small id="uh-profile-account-id">—</small></span>
                    <span><strong>Email</strong><small id="uh-profile-email">—</small></span>
                    <span><strong>Campus</strong><small id="uh-profile-campus">—</small></span>
                </div>
                <div class="uh-profile-form-actions">
                    <button type="submit" class="uh-btn uh-btn-primary" id="uh-profile-save-button">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="uh-account-modal" id="uh-password-modal" hidden>
        <div class="uh-account-modal-dialog uh-password-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="uh-password-modal-title">
            <div class="uh-modal-header">
                <div class="uh-modal-title" id="uh-password-modal-title">Change password</div>
                <button type="button" class="uh-modal-close" id="uh-password-modal-close" aria-label="Close">×</button>
            </div>
            <form id="uh-password-form" class="uh-password-form">
                <p class="uh-password-intro">Keep your account secure by choosing a new password.</p>
                <label>Current password
                    <input type="password" name="current_password" autocomplete="current-password" minlength="6" required>
                </label>
                <label>New password
                    <input type="password" name="new_password" autocomplete="new-password" minlength="6" maxlength="128" required>
                </label>
                <label>Confirm new password
                    <input type="password" name="new_password_confirmation" autocomplete="new-password" minlength="6" maxlength="128" required>
                </label>
                <div class="uh-profile-form-actions">
                    <button type="submit" class="uh-btn uh-btn-primary" id="uh-password-save-button">Update password</button>
                </div>
            </form>
        </div>
    </div>
