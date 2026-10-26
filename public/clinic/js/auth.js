// University Clinic System — Auth Module
'use strict';

const AUTH_API = '/api/clinic/auth';

// -------------------------------------------------------
//  CAMPUS AND COURSE CATALOG
// -------------------------------------------------------
let campusCourseCatalog = new Map();

async function loadCampusOptions() {
  const campusSel = document.getElementById('regCampus');
  if (!campusSel) return;

  campusSel.disabled = true;

  try {
    const response = await fetch(`${AUTH_API}/campuses`, {
      headers: { 'Accept': 'application/json' },
    });
    const data = await response.json();

    if (!response.ok || !data.success || !Array.isArray(data.campuses)
      || data.campuses.some(campus => !campus || campus.id == null
        || typeof campus.name !== 'string' || !Array.isArray(campus.courses)
        || campus.courses.some(course => !course || typeof course.college !== 'string'
          || typeof course.acronym !== 'string' || typeof course.name !== 'string'))) {
      throw new Error(data.message || 'The server returned an invalid campus course catalog.');
    }

    campusCourseCatalog = new Map(data.campuses.map(campus => [String(campus.id), campus]));
    campusSel.replaceChildren(
      new Option('Select Campus', ''),
      ...data.campuses.map(campus => new Option(campus.name, String(campus.id))),
    );
    campusSel.disabled = false;
    campusSel.dispatchEvent(new Event('change'));
  } catch (error) {
    console.error('Campus course catalog failed to load:', error);
    const reason = error && error.message ? error.message : 'Unknown network error';
    showToast(`Could not load campus and course options: ${reason}`, 'error');
  }
}

// -------------------------------------------------------
//  PASSWORD TOGGLE
// -------------------------------------------------------
function togglePass(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
  } else {
    input.type = 'password';
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
  }
}

// -------------------------------------------------------
//  TOAST
// -------------------------------------------------------
function showToast(msg, type = 'success', duration = 3200) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    const t = document.getElementById('toast');
    if (t) { t.textContent = msg; t.className = `toast ${type}`; setTimeout(() => { t.className = 'toast hidden'; }, duration); }
    return;
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
  toast.innerHTML = `<i class="fa ${icons[type] || 'fa-bell'}"></i><span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(30px)';
    toast.style.transition = 'all 0.3s';
    setTimeout(() => toast.remove(), 320);
  }, duration);
}

// -------------------------------------------------------
//  BUTTON LOADING STATE
// -------------------------------------------------------
function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  if (loading) btn.classList.add('loading');
  else         btn.classList.remove('loading');
  btn.disabled = loading;
}

// -------------------------------------------------------
//  LOGIN
// -------------------------------------------------------
async function handleLogin(e) {
  e.preventDefault();

  const email    = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;

  if (!email || !password) {
    showToast('Enter your email and password.', 'error');
    return;
  }

  setLoading('loginBtn', true);
  try {
    const res  = await fetch(`${AUTH_API}/login`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify({ email, password }),
    });

    // Read the body as text first so we can include it in the error message
    // if it's not JSON. Laravel returns HTML 4xx/5xx pages when the URL is
    // wrong (e.g. 405 Method Not Allowed), and `res.json()` throws without
    // giving us any clue what came back.
    const raw = await res.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      console.error('Login: non-JSON response', res.status, res.headers.get('content-type'), raw.slice(0, 200));
      const snippet = raw.replace(/\s+/g, ' ').slice(0, 120);
      showToast(`Server returned ${res.status} (${res.headers.get('content-type') || 'no content-type'}): ${snippet}`, 'error');
      return;
    }

    if (!res.ok || data.error) {
      // Prefer the server's `error` field, then `message` (Laravel validation
      // uses `message` + `errors`), then a generic fallback. The previous
      // version only checked `data.error` and fell back to a useless string
      // whenever Laravel returned a 422 with `message` instead.
      const msg = data.error
        || (data.message && Object.values(data.errors || {}).flat()[0])
        || data.message
        || 'Login failed. Please try again.';
      showToast(msg, 'error');
      return;
    }

    const role = data.user?.role;
    if (!role) {
      showToast('Login failed: the account has no valid role.', 'error');
      return;
    }

    sessionStorage.setItem('clinic_user', JSON.stringify(data.user));
    sessionStorage.removeItem('clinic_session_expired');
    // SameSite=Lax (not Strict) is required so the cookie is sent on the
    // top-level GET navigation to the dashboard after login redirect.
    document.cookie = `clinic_role=${role}; path=/; SameSite=Lax`;
    sessionStorage.removeItem('clinic_redirect_after_login');
    showToast(`Welcome back, ${data.user.firstName}!`, 'success');
    setTimeout(() => { redirectDashboard(role, data.user?.patientType); }, 700);

  } catch (err) {
    // The fetch itself threw (network error, DNS, server down, mixed content, etc.)
    // and we never got a response. Show the actual error so it's diagnosable —
    // the old "Cannot reach the server. Check Apache is running." message was
    // misleading because it also fired for any non-JSON server response.
    console.error('Login fetch failed:', err);
    const reason = err && err.message ? err.message : 'Unknown network error';
    showToast(`Cannot reach the server: ${reason}`, 'error');
  } finally {
    setLoading('loginBtn', false);
  }
}

// -------------------------------------------------------
//  REGISTER
// -------------------------------------------------------
async function handleRegister(e) {
  e.preventDefault();

  const fullName  = document.getElementById('regName').value.trim();
  const studentId = document.getElementById('regStudentId').value.trim();
  const deptKey   = document.getElementById('regDept').value;
  const dept      = document.getElementById('regCourse').value; // full course name (e.g. "Bachelor of Science in Information Technology (BSIT)")
  const yearLevel = document.getElementById('regYearLevel').value;
  const section   = document.getElementById('regSection').value.trim();
  const email     = document.getElementById('regEmail').value.trim().toLowerCase();
  const contact   = document.getElementById('regContact').value.trim();
  const age       = document.getElementById('regAge').value;
  const sex       = document.getElementById('regSex').value;
  const status    = document.getElementById('regStatus').value;
  const password  = document.getElementById('regPassword').value;
  const confirm   = document.getElementById('regConfirm').value;
  const termsAccepted = document.getElementById('agreeTerms')?.checked ?? false;

  // Read campus directly from the select element
  const campusEl = document.getElementById('regCampus');
  const campusId = campusEl ? campusEl.value : '';

  // Validations
  if (password !== confirm) { showToast('Passwords do not match!', 'error'); return; }
  if (password.length < 6)  { showToast('Password must be at least 6 characters.', 'error'); return; }
  if (!termsAccepted) { showToast('Please agree to the Terms of Service before creating your account.', 'error'); return; }
  if (!deptKey)   { showToast('Please select your department.', 'error'); return; }
  if (!dept)      { showToast('Please select your course.', 'error'); return; }
  if (!yearLevel) { showToast('Please select your year level.', 'error'); return; }
  if (!section)   { showToast('Please enter your section.', 'error'); return; }
  if (!sex)       { showToast('Please select your sex.', 'error'); return; }
  if (!status)    { showToast('Please select your civil status.', 'error'); return; }
  if (!campusId)  { showToast('Please select your home campus.', 'error'); return; }

  const campusIdInt = parseInt(campusId, 10);
  if (!campusIdInt || campusIdInt <= 0) {
    showToast('Please select your home campus.', 'error');
    return;
  }

  // Parse name: "Last, First Middle" or "First Middle Last"
  let lastName = '', firstName = '', middleName = '';
  if (fullName.includes(',')) {
    const parts = fullName.split(',').map(s => s.trim());
    lastName  = parts[0] || '';
    const rest = (parts[1] || '').split(' ').filter(Boolean);
    firstName  = rest[0] || '';
    middleName = rest.slice(1).join(' ');
  } else {
    const parts = fullName.split(' ').filter(Boolean);
    lastName   = parts.at(-1) || fullName;
    firstName  = parts[0] || '';
    middleName = parts.slice(1, -1).join(' ');
  }

  setLoading('registerBtn', true);
  try {
    const res = await fetch(`${AUTH_API}/register`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        student_id:   studentId,
        last_name:    lastName,
        first_name:   firstName,
        middle_name:  middleName,
        email,
        password,
        course_year:  yearLevel, // "1st Year", "2nd Year", ...
        department:   dept,      // full course name with acronym in parens, e.g. "Bachelor of Science in Information Technology (BSIT)"
        section,
        contact,
        age:          age ? parseInt(age, 10) : null,
        sex,
        civil_status: status,
        campus_id:    campusIdInt,
        password_confirmation: confirm,
        terms_accepted: termsAccepted,
      }),
    });
    const raw = await res.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      console.error('Register: non-JSON response', res.status, res.headers.get('content-type'), raw.slice(0, 200));
      const snippet = raw.replace(/\s+/g, ' ').slice(0, 120);
      showToast(`Server returned ${res.status} (${res.headers.get('content-type') || 'no content-type'}): ${snippet}`, 'error');
      return;
    }

    if (!res.ok || data.error) {
      const msg = data.error
        || (data.message && Object.values(data.errors || {}).flat()[0])
        || data.message
        || 'Registration failed.';
      showToast(msg, 'error');
      return;
    }

    showToast('Registration successful! You can now sign in.', 'success');
    document.getElementById('registerForm').reset();
    setTimeout(() => switchToLogin(), 1600);

  } catch (err) {
    console.error('Register fetch failed:', err);
    const reason = err && err.message ? err.message : 'Unknown network error';
    showToast(`Cannot reach the server: ${reason}`, 'error');
  } finally {
    setLoading('registerBtn', false);
  }
}

// -------------------------------------------------------
//  REDIRECT
// -------------------------------------------------------
function redirectDashboard(role, patientType) {
  switch (role) {
    case 'student': {
      const typeMap = {
        faculty:    '/dashboard/faculty',
        staff:      '/dashboard/staff',
        faculty_staff: '/dashboard/staff',
        instructor: '/dashboard/instructor',
      };
      window.location.href = typeMap[patientType] || '/dashboard/student';
      break;
    }
    case 'staff':      window.location.href = '/dashboard/staff';       break;
    case 'nurse':      window.location.href = '/dashboard/nurse';       break;
    case 'nurse_head': window.location.href = '/dashboard/nurse-head';  break;
    case 'physician':  window.location.href = '/dashboard/physician';   break;
    case 'dentist':    window.location.href = '/dashboard/dentist';     break;
    case 'admin':      window.location.href = '/dashboard/admin';       break;
    default:           window.location.href = '/dashboard/student';
  }
}

// -------------------------------------------------------
//  PANEL SWAP - Flip card animation
// -------------------------------------------------------
let isRegisterMode = false;
let registerPanelParent = null;
let registerPanelNextSibling = null;
let formTransitionTimer = null;

function swapPanels(toRegister) {
  const flipWrapper = document.getElementById('flipCardWrapper');
  const loginPanel = document.getElementById('loginPanel');
  const registerPanel = document.getElementById('registerPanel');
  const signinContent = document.getElementById('welcome-signin-content');
  const registerContent = document.getElementById('welcome-register-content');
  const transitionDuration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 760;

  if (formTransitionTimer) {
    clearTimeout(formTransitionTimer);
  }

  document.body.classList.remove(
    'form-transition',
    'form-transition-to-register',
    'form-transition-to-login',
  );
  if (transitionDuration > 0) {
    document.body.classList.add(
      'form-transition',
      toRegister ? 'form-transition-to-register' : 'form-transition-to-login',
    );
  }
  
  if (toRegister && loginPanel?.contains(document.activeElement)) {
    document.activeElement.blur();
  }
  document.body.classList.toggle('register-view', toRegister);
  if (loginPanel) loginPanel.setAttribute('aria-hidden', toRegister ? 'true' : 'false');
  if (flipWrapper) flipWrapper.setAttribute('aria-hidden', toRegister ? 'false' : 'true');

  if (toRegister) {
    // Flip to show register form (back side)
    flipWrapper.classList.add('flipped');
    if (registerPanel) {
      if (!registerPanelParent) {
        registerPanelParent = registerPanel.parentElement;
        registerPanelNextSibling = registerPanel.nextElementSibling;
      }
      document.body.appendChild(registerPanel);
      registerPanel.style.setProperty('position', 'fixed', 'important');
      registerPanel.style.setProperty('top', '50%', 'important');
      registerPanel.style.setProperty('left', '50%', 'important');
      registerPanel.style.setProperty('right', 'auto', 'important');
      registerPanel.style.setProperty('bottom', 'auto', 'important');
      registerPanel.style.setProperty('width', 'min(560px, calc(100vw - 24px))', 'important');
      registerPanel.style.setProperty('height', 'min(78vh, 600px)', 'important');
      registerPanel.style.setProperty('margin', '0', 'important');
      registerPanel.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
    }
    
    // Update welcome panel content for when it flips back
    if (signinContent) signinContent.style.display = 'none';
    if (registerContent) registerContent.style.display = 'block';
  } else {
    // Flip back to show welcome panel (front side)
    flipWrapper.classList.remove('flipped');
    if (registerPanel) {
      registerPanel.removeAttribute('style');
      if (registerPanelParent) {
        registerPanelParent.insertBefore(registerPanel, registerPanelNextSibling);
      }
    }
    
    // Update welcome panel content
    if (registerContent) registerContent.style.display = 'none';
    if (signinContent) signinContent.style.display = 'block';
  }

  formTransitionTimer = setTimeout(() => {
    document.body.classList.remove(
      'form-transition',
      'form-transition-to-register',
      'form-transition-to-login',
    );
  }, transitionDuration);
}

function switchToRegister() {
  if (isRegisterMode) return;
  isRegisterMode = true;
  swapPanels(true);
}

function switchToLogin() {
  if (!isRegisterMode) return;
  isRegisterMode = false;
  swapPanels(false);
}

// -------------------------------------------------------
//  DOM READY
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

  initDoorAnimation();
  loadCampusOptions();

  if (sessionStorage.getItem('clinic_session_expired') === '1') {
    sessionStorage.removeItem('clinic_session_expired');
    showToast('Your session expired. Please sign in again.', 'error');
  }

  const user = sessionStorage.getItem('clinic_user');
  if (user) {
    try { const u = JSON.parse(user); redirectDashboard(u.role, u.patientType); } catch {}
  }

  // Initialize welcome panel - show sign-in content by default
  const signinContent = document.getElementById('welcome-signin-content');
  const registerContent = document.getElementById('welcome-register-content');
  if (signinContent) signinContent.style.display = 'block';
  if (registerContent) registerContent.style.display = 'none';

  const pwInput = document.getElementById('regPassword');
  const fill    = document.querySelector('.strength-fill');
  const txt     = document.getElementById('passStrengthText');
  if (pwInput && fill) {
    pwInput.addEventListener('input', () => {
      const val = pwInput.value;
      let score = 0;
      if (val.length >= 8)          score++;
      if (/[A-Z]/.test(val))        score++;
      if (/[0-9]/.test(val))        score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      const colors = ['#e74c3c','#e67e22','#f1c40f','#27ae60'];
      const labels = ['Weak','Fair','Good','Strong'];
      fill.style.width      = `${score * 25}%`;
      fill.style.background = colors[score - 1] || '#e0e0e0';
      if (txt) txt.textContent = score > 0 ? labels[score - 1] : 'Password strength';
    });
  }

  const confirmInput = document.getElementById('regConfirm');
  const matchEl      = document.getElementById('passMatch');
  if (confirmInput && matchEl && pwInput) {
    confirmInput.addEventListener('input', () => {
      if (!confirmInput.value) { matchEl.textContent = ''; return; }
      if (confirmInput.value === pwInput.value) {
        matchEl.textContent = '✓ Passwords match';
        matchEl.className   = 'match-indicator ok';
      } else {
        matchEl.textContent = '✗ Passwords do not match';
        matchEl.className   = 'match-indicator no-match';
      }
    });
  }

  document.querySelectorAll('.pass-toggle').forEach(btn => {
    btn.addEventListener('click', () => btn.classList.toggle('visible'));
  });

  initDeptCourseCascade();
});

function initDeptCourseCascade() {
  const deptSel   = document.getElementById('regDept');
  const courseSel = document.getElementById('regCourse');
  const campusSel = document.getElementById('regCampus');
  if (!deptSel || !courseSel || !campusSel) return;

  const departmentOptions = Array.from(deptSel.options).slice(1);
  deptSel.disabled = true;
  courseSel.disabled = true;

  const updateCourseOptions = () => {
    const campus = campusCourseCatalog.get(campusSel.value);
    const courses = campus?.courses.filter(course => course.college === deptSel.value) || [];
    courseSel.replaceChildren(
      new Option(deptSel.value ? 'Select Course' : 'Select Department first', ''),
      ...courses.map(course => new Option(
        `${course.acronym} — ${course.name.replace(/\s*\([^)]+\)\s*$/, '')}`,
        course.name,
      )),
    );
    courseSel.disabled = courses.length === 0;
  };

  campusSel.addEventListener('change', () => {
    const campus = campusCourseCatalog.get(campusSel.value);
    const availableDepartments = new Set((campus?.courses || []).map(course => course.college));
    const options = departmentOptions.filter(option => availableDepartments.has(option.value));

    deptSel.replaceChildren(
      new Option('Select Department', ''),
      ...options.map(option => new Option(option.textContent, option.value)),
    );
    deptSel.disabled = options.length === 0;
    updateCourseOptions();
  });

  deptSel.addEventListener('change', updateCourseOptions);
}

// -------------------------------------------------------
//  SLIDING DOOR ANIMATION
// -------------------------------------------------------
let doorsAnimated = false;
let compactLayout = window.matchMedia('(max-width: 980px)').matches;

function collapseDoors() {
  if (doorsAnimated) return;
  doorsAnimated = true;

  const loginPanel   = document.querySelector('.login-panel');
  const welcomePanel = document.querySelector('.welcome-panel');
  const clickzone    = document.getElementById('doorClickzone');
  if (!loginPanel || !welcomePanel) return;

  // The panels use normal document flow on compact screens.
  if (window.matchMedia('(max-width: 980px)').matches) {
    loginPanel.style.transition = 'none';
    welcomePanel.style.transition = 'none';
    loginPanel.style.transform = 'none';
    welcomePanel.style.transform = 'none';
    if (clickzone) clickzone.classList.add('hidden');
    return;
  }

  // Step 1: snap both panels to off-screen instantly (no transition)
  loginPanel.style.transition   = 'none';
  welcomePanel.style.transition = 'none';
  loginPanel.style.transform    = 'translateY(-50%) translateX(-110%)';
  welcomePanel.style.transform  = 'translateY(-50%) translateX(110%)';
  loginPanel.classList.remove('doors-closed');
  welcomePanel.classList.remove('doors-closed');

  // Step 2: force a reflow so the browser commits the off-screen position
  // before we apply the transition. Reading offsetWidth flushes the style queue.
  void loginPanel.offsetWidth;
  void welcomePanel.offsetWidth;

  // Step 3: calculate target on-screen positions
  const vw       = window.innerWidth;
  const loginW   = loginPanel.offsetWidth;
  const welcomeW = welcomePanel.offsetWidth;
  const gap      = 24;
  const totalW   = loginW + welcomeW + gap;
  const startX   = (vw - totalW) / 2;
  const loginTX  = startX;
  const welcomeTX = (startX + loginW + gap) - (vw - welcomeW);

  // Step 4: apply transition and slide in
  loginPanel.style.transition   = 'transform 1.1s cubic-bezier(0.16,1,0.3,1)';
  welcomePanel.style.transition = 'transform 1.1s cubic-bezier(0.16,1,0.3,1)';
  loginPanel.style.transform    = `translateY(-50%) translateX(${loginTX}px)`;
  welcomePanel.style.transform  = `translateY(-50%) translateX(${welcomeTX}px)`;
  loginPanel.classList.add('doors-closed');
  welcomePanel.classList.add('doors-closed');
  loginPanel.dataset.loginTx     = `translateY(-50%) translateX(${loginTX}px)`;
  welcomePanel.dataset.welcomeTx = `translateY(-50%) translateX(${welcomeTX}px)`;
  if (clickzone) clickzone.classList.add('hidden');

  // Step 5: bounce settle after slide-in completes
  setTimeout(() => {
    const bounce = 6;
    loginPanel.style.transition   = 'transform 0.1s ease-out';
    welcomePanel.style.transition = 'transform 0.1s ease-out';
    loginPanel.style.transform    = `translateY(-50%) translateX(${loginTX + bounce}px)`;
    welcomePanel.style.transform  = `translateY(-50%) translateX(${welcomeTX - bounce}px)`;
    setTimeout(() => {
      loginPanel.style.transition   = 'transform 0.08s ease-out';
      welcomePanel.style.transition = 'transform 0.08s ease-out';
      loginPanel.style.transform    = `translateY(-50%) translateX(${loginTX - 2}px)`;
      welcomePanel.style.transform  = `translateY(-50%) translateX(${welcomeTX + 2}px)`;
      setTimeout(() => {
        loginPanel.style.transition   = 'transform 0.25s ease';
        welcomePanel.style.transition = 'transform 0.25s ease';
        loginPanel.style.transform    = `translateY(-50%) translateX(${loginTX}px)`;
        welcomePanel.style.transform  = `translateY(-50%) translateX(${welcomeTX}px)`;
      }, 80);
    }, 100);
  }, 1080);
}

function initDoorAnimation() {
  // Wait for fonts so layout measurements are accurate,
  // then run on the next paint frame.
  const run = () => requestAnimationFrame(collapseDoors);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(run);
  } else {
    run();
  }
}

window.addEventListener('resize', () => {
  const nextCompactLayout = window.matchMedia('(max-width: 980px)').matches;
  if (nextCompactLayout === compactLayout) return;

  compactLayout = nextCompactLayout;
  doorsAnimated = false;
  requestAnimationFrame(collapseDoors);
});

// -------------------------------------------------------
//  BFCACHE RESTORE — restart video when navigating back
//  pageshow fires on both normal load AND bfcache restore.
//  e.persisted is true only for bfcache. We only need to
//  restart the video there; collapseDoors handles its own
//  reset internally now.
// -------------------------------------------------------
window.addEventListener('pageshow', (e) => {
  // Always restart the video — bfcache pauses it
  const video = document.querySelector('.bg-video');
  if (video && (e.persisted || video.paused)) {
    video.currentTime = 0;
    video.play().catch(() => {});
  }

  if (e.persisted) {
    doorsAnimated = false;
    requestAnimationFrame(collapseDoors);
  }
});
