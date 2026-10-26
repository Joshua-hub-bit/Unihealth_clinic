/**
 * SUNN Clinic — Client-Side Auth Guard
 *
 * Add this as the FIRST <script> in every protected page's <head>.
 * It runs synchronously before the DOM is painted, so unauthorized
 * users never see any page content — they are redirected immediately.
 *
 * Usage:
 *   <script src="../js/auth-guard.js" data-role="student"></script>
 *   <script src="../js/auth-guard.js" data-role="staff"></script>
 *   <script src="../js/auth-guard.js" data-role="admin"></script>
 *
 * data-role values:
 *   "student" — allows role === 'student'
 *   "staff"   — allows role === 'nurse' | 'nurse_head'
 *   "admin"   — allows role === 'admin' only
 */

(function () {
  'use strict';

  // -- Determine required role from the script tag's data-role attribute --
  var scripts = document.getElementsByTagName('script');
  var requiredRole = 'student'; // safe default
  for (var i = 0; i < scripts.length; i++) {
    if (scripts[i].src && scripts[i].src.indexOf('auth-guard.js') !== -1) {
      requiredRole = scripts[i].getAttribute('data-role') || 'student';
      break;
    }
  }

  // -- Read session --
  var raw = sessionStorage.getItem('clinic_user');
  var user = null;
  try {
    user = raw ? JSON.parse(raw) : null;
  } catch (e) {
    user = null;
  }

  // -- Role membership helpers --
  var STAFF_ROLES = ['nurse', 'nurse_head'];

  function isAllowed(user, requiredRole) {
    if (!user || !user.role) return false;
    var role = (user.role || '').toLowerCase();
    if (requiredRole === 'student') return role === 'student';
    if (requiredRole === 'staff')   return STAFF_ROLES.indexOf(role) !== -1;
    if (requiredRole === 'admin')   return role === 'admin';
    return false;
  }

  // -- Redirect to login if not authorized --
  if (!isAllowed(user, requiredRole)) {
    // Preserve the page they were trying to reach so login can redirect back
    var attempted = window.location.pathname + window.location.search;
    sessionStorage.setItem('clinic_redirect_after_login', attempted);
    // Clear any stale session
    sessionStorage.removeItem('clinic_user');
    window.location.replace('/login');
  }

  // -- Expose user globally so dashboard JS can use it directly --
  window.__clinicUser = user;
})();
