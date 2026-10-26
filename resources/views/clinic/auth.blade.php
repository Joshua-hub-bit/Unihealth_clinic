<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="University Clinic System - Your Health, Our Priority" />
  <meta name="theme-color" content="#2a7a6f" />
  <title>UNIHEALTH — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="{{ asset('clinic/css/auth.css') }}?v=20260925" />
</head>
<body>
  <video class="bg-video" autoplay muted loop playsinline disablepictureinpicture preload="auto">
    <source src="{{ asset('clinic/images/clinic.mp4') }}" type="video/mp4">
  </video>
  <div class="bg-video-overlay"></div>

  <div class="bg-layers" aria-hidden="true">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <div class="bg-blob blob-3"></div>
    <div class="bg-grid"></div>
  </div>

  <div class="door-clickzone" id="doorClickzone"></div>

  <a class="auth-back-home" href="{{ route('clinic.landing') }}">
    <i class="fa fa-arrow-left" aria-hidden="true"></i>
    <span>Back to home</span>
  </a>

  <main class="auth-main" role="main">

    <!-- LEFT PANEL — login + register -->
    <div class="panel login-panel" id="loginPanel">

      <!-- LOGIN FORM -->
      <div class="auth-form" id="loginForm-wrap">
        <div class="logo-area">
          <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH institutional logo" class="sunn-logo-img"/>
        </div>
        <h2 class="signin-title">SIGN IN</h2>

        <form id="loginForm" method="post" action="/api/clinic/auth/login" onsubmit="handleLogin(event)" novalidate>
          <div class="form-group">
            <label class="floating-label"><span>Email</span><i class="fa fa-envelope"></i></label>
            <input type="email" id="loginEmail" placeholder="Enter your email" autocomplete="off" required />
          </div>
          <div class="form-group">
            <label class="floating-label"><span>Password</span><i class="fa fa-lock"></i></label>
            <div class="pass-wrap">
              <input type="password" id="loginPassword" placeholder="Enter your password" autocomplete="new-password" required />
              <button type="button" class="pass-toggle" id="loginPassToggle" onclick="togglePass('loginPassword','loginPassToggle')">
                <i class="fa fa-eye"></i><i class="fa fa-eye-slash"></i>
              </button>
            </div>
          </div>
          <div class="form-options">
            <label class="checkbox-wrapper">
              <input type="checkbox" id="rememberMe"/>
              <span class="checkmark"><i class="fa fa-check"></i></span>
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-link" onclick="showToast('Coming soon','info')">Forgot password?</a>
          </div>
          <button type="submit" class="btn-signin" id="loginBtn">
            <span class="btn-text">Sign In</span>
            <span class="btn-loader"><svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="32" stroke-dashoffset="0" stroke-linecap="round"/></svg></span>
          </button>
        </form>
        <p class="switch-link signup-prompt">Don't have an account? <span role="button" tabindex="0" onclick="switchToRegister()">Sign up here</span></p>
      </div>

    </div><!-- end login-panel -->

    <!-- RIGHT PANEL — FLIP CARD CONTAINER -->
    <div class="flip-card-wrapper" id="flipCardWrapper">
      <div class="flip-card-inner" id="flipCardInner">
        
        <!-- FRONT SIDE: Welcome Panel -->
        <div class="flip-card-front">
          <div class="panel welcome-panel" id="welcomePanel">
      <div class="welcome-overlay"></div>
      <div class="welcome-shapes">
        <div class="shape shape-1"></div><div class="shape shape-2"></div>
        <div class="shape shape-3"></div><div class="shape shape-4"></div>
      </div>

      <div id="welcome-signin-content">
        <div class="welcome-top">
          <div class="welcome-logo-badge">
            <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH institutional logo" class="sunn-logo-img welcome-logo-img"/>
          </div>
          <div>
            <div class="welcome-sunn-name">State University of</div>
            <div class="welcome-sunn-name bold">Northern Negros</div>
            <div class="welcome-sunn-sub">Medical &amp; Dental Services</div>
          </div>
        </div>
        <div class="welcome-content">
          <h1>WELCOME BACK!</h1>
          <p>Access your university clinic account.</p>
        </div>
        <div class="welcome-illustration-wrap">
          <svg viewBox="0 0 260 220" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="130" cy="200" rx="70" ry="20" fill="rgba(255,255,255,0.06)"/>
            <path d="M90 100 Q80 130 75 200 L185 200 Q180 130 170 100 Q150 115 130 115 Q110 115 90 100Z" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <path d="M100 112 Q130 125 160 112 L155 200 L105 200Z" fill="rgba(212,160,23,0.15)" stroke="rgba(212,160,23,0.3)" stroke-width="1"/>
            <rect x="124" y="135" width="12" height="4" rx="2" fill="rgba(212,160,23,0.7)"/>
            <rect x="127" y="132" width="6" height="10" rx="2" fill="rgba(212,160,23,0.7)"/>
            <circle cx="130" cy="72" r="28" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
            <path d="M102 68 Q130 52 158 68" stroke="rgba(212,160,23,0.6)" stroke-width="2.5" fill="none"/>
            <rect x="122" y="56" width="16" height="4" rx="2" fill="rgba(212,160,23,0.5)"/>
          </svg>
        </div>
        <div class="welcome-pills">
          <div class="pill"><i class="fa fa-calendar-check"></i> Appointments</div>
          <div class="pill"><i class="fa fa-file-medical"></i> Health Records</div>
        </div>
        <div class="welcome-cta">
          <button class="btn-outline" onclick="switchToRegister()">
            <span>Sign Up</span><i class="fa fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <div id="welcome-register-content" class="uh-hidden">
        <div class="welcome-top">
          <div class="welcome-logo-badge">
            <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH institutional logo" class="sunn-logo-img welcome-logo-img"/>
          </div>
          <div>
            <div class="welcome-sunn-name">State University of</div>
            <div class="welcome-sunn-name bold">Northern Negros</div>
            <div class="welcome-sunn-sub">Medical &amp; Dental Services</div>
          </div>
        </div>
        <div class="welcome-content">
          <h1>HELLO, FRIEND!</h1>
          <p>Start your health journey with us.</p>
        </div>
        <div class="welcome-illustration-wrap">
          <svg viewBox="0 0 260 220" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="130" cy="200" rx="70" ry="20" fill="rgba(255,255,255,0.06)"/>
            <path d="M90 100 Q80 130 75 200 L185 200 Q180 130 170 100 Q150 115 130 115 Q110 115 90 100Z" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <rect x="124" y="135" width="12" height="4" rx="2" fill="rgba(212,160,23,0.7)"/>
            <rect x="127" y="132" width="6" height="10" rx="2" fill="rgba(212,160,23,0.7)"/>
            <circle cx="130" cy="72" r="28" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
            <path d="M102 68 Q130 52 158 68" stroke="rgba(212,160,23,0.6)" stroke-width="2.5" fill="none"/>
          </svg>
        </div>
        <div class="welcome-pills">
          <div class="pill"><i class="fa fa-shield-heart"></i> Secure &amp; Private</div>
          <div class="pill"><i class="fa fa-user-plus"></i> Free to Join</div>
        </div>
        <div class="welcome-cta">
          <button class="btn-outline" onclick="switchToLogin()">
            <span>Sign In</span><i class="fa fa-arrow-left"></i>
          </button>
        </div>
      </div>

          </div><!-- end welcome-panel -->
        </div><!-- end flip-card-front -->

        <!-- BACK SIDE: Register Form -->
        <div class="flip-card-back">
          <div class="panel register-panel" id="registerPanel">
            <!-- REGISTER FORM -->
            <div class="auth-form" id="registerForm-wrap">
              <div class="logo-area">
                <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH institutional logo" class="sunn-logo-img"/>
              </div>
              <h2 class="signin-title">CREATE ACCOUNT</h2>
              <p class="signin-subtitle">Fill in your details to create your university clinic account.</p>

              <form id="registerForm" method="post" action="/api/clinic/auth/register" onsubmit="handleRegister(event)" novalidate>
                <div class="form-row">
                  <div class="form-group">
                    <label class="reg-label" for="regName">Complete Name <span class="req">*</span></label>
                    <input type="text" id="regName" placeholder="Last, First Middle" required autocomplete="name"/>
                    <span class="form-hint">Format: Last, First Middle</span>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regStudentId">Student ID <span class="req">*</span></label>
                    <input type="text" id="regStudentId" placeholder="e.g. 2024-00001" required autocomplete="off"/>
                  </div>
                </div>
                <div class="form-group">
                  <label class="reg-label" for="regCampus">Home Campus <span class="req">*</span></label>
                  <div class="select-wrap">
                    <select id="regCampus" required disabled>
                      <option value="">Loading campuses...</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="reg-label" for="regDept">Department <span class="req">*</span></label>
                    <div class="select-wrap">
                      <select id="regDept" required>
                        <option value="">Select Department</option>
                        <option value="CALSS">College of Arts, Literature and Social Sciences (CALSS)</option>
                        <option value="CCIS">College of Computing and Information Sciences (CCIS)</option>
                        <option value="CBM">College of Business and Management (CBM)</option>
                        <option value="COED">College of Education (COED)</option>
                        <option value="CAAS">College of Agriculture and Allied Sciences (CAAS)</option>
                        <option value="CFNS">College of Fisheries and Natural Sciences (CFNS)</option>
                        <option value="CHAS">College of Health and Allied Sciences (CHAS)</option>
                        <option value="CCJE">College of Criminal Justice Education (CCJE)</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regCourse">Course <span class="req">*</span></label>
                    <div class="select-wrap">
                      <select id="regCourse" required disabled>
                        <option value="">Select Department first</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regYearLevel">Year Level <span class="req">*</span></label>
                    <div class="select-wrap">
                      <select id="regYearLevel" required>
                        <option value="">Select Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regSection">Section <span class="req">*</span></label>
                    <input type="text" id="regSection" placeholder="e.g. 3-B" required autocomplete="off"/>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="reg-label" for="regEmail">Email <span class="req">*</span></label>
                    <input type="email" id="regEmail" placeholder="student@university.edu" required autocomplete="email"/>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regContact">Contact No. <span class="req">*</span></label>
                    <input type="tel" id="regContact" placeholder="09XX-XXX-XXXX" required autocomplete="tel"/>
                  </div>
                </div>
                <div class="form-row three-col">
                  <div class="form-group">
                    <label class="reg-label" for="regAge">Age <span class="req">*</span></label>
                    <input type="number" id="regAge" placeholder="—" min="15" max="60" required/>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regSex">Sex <span class="req">*</span></label>
                    <div class="select-wrap">
                      <select id="regSex" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regStatus">Civil Status <span class="req">*</span></label>
                    <div class="select-wrap">
                      <select id="regStatus" required>
                        <option value="">Select</option>
                        <option>Single</option>
                        <option>Married</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="reg-label" for="regPassword">Password <span class="req">*</span></label>
                    <div class="pass-wrap">
                      <input type="password" id="regPassword" placeholder="Create password" required autocomplete="new-password"/>
                      <button type="button" class="pass-toggle" id="regPassToggle1" onclick="togglePass('regPassword','regPassToggle1')">
                        <i class="fa fa-eye"></i><i class="fa fa-eye-slash"></i>
                      </button>
                    </div>
                    <div class="password-strength">
                      <div class="strength-bar"><span class="strength-fill"></span></div>
                      <span class="strength-text" id="passStrengthText">Strength</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="reg-label" for="regConfirm">Confirm Password <span class="req">*</span></label>
                    <div class="pass-wrap">
                      <input type="password" id="regConfirm" placeholder="Repeat password" required autocomplete="new-password"/>
                      <button type="button" class="pass-toggle" id="regPassToggle2" onclick="togglePass('regConfirm','regPassToggle2')">
                        <i class="fa fa-eye"></i><i class="fa fa-eye-slash"></i>
                      </button>
                    </div>
                    <span class="match-indicator" id="passMatch"></span>
                  </div>
                </div>
                <div class="form-agreement">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" id="agreeTerms" required/>
                    <span class="checkmark"><i class="fa fa-check"></i></span>
                    <span>I agree to the <a href="#" onclick="showToast('Coming soon','info')">Terms of Service</a></span>
                  </label>
                </div>
                <button type="submit" class="btn-signin full-btn" id="registerBtn">
                  <span class="btn-text">Create Account</span>
                  <span class="btn-loader"><svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="32" stroke-dashoffset="0" stroke-linecap="round"/></svg></span>
                </button>
                <p class="switch-link">Already have an account? <span onclick="switchToLogin()">Sign in here</span></p>
              </form>
            </div>
          </div><!-- end register-panel -->
        </div><!-- end flip-card-back -->

      </div><!-- end flip-card-inner -->
    </div><!-- end flip-card-wrapper -->

    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true" role="status"></div>

  </main>

  <script src="{{ asset('clinic/js/auth.js') }}?v={{ time() }}"></script>
</body>
</html>
