@props(['role' => 'student', 'user' => null])
<aside class="uh-sidebar">
    <div class="uh-sidebar-logo">
        <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Clinic logo" class="uh-sidebar-logo-img"/>
        <div>
            <div class="uh-logo-name">SUNN Clinic</div>
            <div class="uh-logo-sub">Unified Health System</div>
        </div>
    </div>
    <div class="uh-role-badge" id="uh-role-badge">{{ ucfirst($role) }}</div>
    <nav class="uh-nav" id="uh-nav">
        {{ $slot }}
    </nav>
    <div class="uh-sidebar-user">
        <div class="uh-user-avatar" id="uh-user-avatar">U</div>
        <div class="uh-user-info">
            <div class="uh-user-name" id="uh-user-name">User</div>
            <div class="uh-user-role" id="uh-user-role">{{ ucfirst($role) }}</div>
            <div class="uh-user-id" id="uh-user-id">ID: —</div>
        </div>
        <button class="uh-logout-btn" onclick="uhLogout()" title="Logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </button>
    </div>
</aside>

