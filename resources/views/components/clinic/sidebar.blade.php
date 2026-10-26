@props(['role' => 'student', 'user' => null])
<div class="uh-sidebar-wrap">
    <aside class="uh-sidebar">
        <div class="uh-sidebar-logo">
            <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Clinic logo" class="uh-sidebar-brand-mark"/>
            <div>
                <div class="uh-logo-name">UniHealth</div>
                <div class="uh-logo-sub">SUNN Clinic System</div>
            </div>
        </div>

        <div class="uh-sidebar-profile">
            <div class="uh-user-avatar" id="uh-user-avatar">J</div>
            <div class="uh-user-info">
                <div class="uh-user-name" id="uh-user-name">Juan Dela Cruz</div>
                <div class="uh-user-role" id="uh-user-role">{{ $role === 'employee' ? 'Employee' : 'Patient / Student' }}</div>
                <div class="uh-user-id" id="uh-user-id">ID: —</div>
            </div>
        </div>

        <nav class="uh-nav" id="uh-nav">
            {{ $slot }}
            <button class="uh-nav-item" onclick="uhOpenNotificationsPage(this)" data-title="Notifications" id="nav-notifications">
                <i class="fa fa-bell"></i><span>Notifications</span>
                <span class="uh-nav-badge uh-hidden" id="notif-badge" aria-live="polite" aria-label="0 unread notifications">0</span>
            </button>
        </nav>

        <div class="uh-sidebar-message">
            <span class="uh-sidebar-message-leaf uh-sidebar-message-leaf-one" aria-hidden="true"></span>
            <span class="uh-sidebar-message-leaf uh-sidebar-message-leaf-two" aria-hidden="true"></span>
            <strong id="uh-sidebar-greeting">Welcome!</strong>
            <p>Take care of your health.<br/>Your well-being matters.</p>
        </div>
    </aside>
    <div class="uh-sidebar-backdrop" onclick="uhToggleDrawer(false)" aria-hidden="true"></div>
</div>
