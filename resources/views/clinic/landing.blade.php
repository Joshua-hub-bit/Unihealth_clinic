<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="UNIHEALTH Medical & Dental Services Office — Your Health, Our Priority"/>
  <title>UNIHEALTH — Medical & Dental Services</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <link rel="stylesheet" href="{{ asset('clinic/css/landing.css') }}"/>
</head>
<body>

  <!-- VIDEO BACKGROUND -->
  <video class="bg-video" autoplay muted loop playsinline>
    <source src="{{ asset('clinic/images/clinic.mp4') }}" type="video/mp4">
  </video>
  <div class="bg-video-overlay"></div>

  <!-- BG BLOBS -->
  <div class="bg-layers" aria-hidden="true">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <div class="bg-blob blob-3"></div>
    <div class="bg-grid"></div>
  </div>

  <!-- NAVBAR -->
  <header class="navbar" id="navbar">
    <div class="nav-inner">
      <a class="nav-brand" href="{{ route('clinic.landing') }}">
        <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="UNIHEALTH Logo" class="nav-logo"/>
        <div class="nav-brand-text">
          <span class="brand-name">UNIHEALTH</span>
          <span class="brand-sub">Medical &amp; Dental Services</span>
        </div>
      </a>
      <nav class="nav-links" id="navLinks">
        <a href="#home"     class="nav-link active">Home</a>
        <a href="#about"    class="nav-link">About Us</a>
        <a href="#services" class="nav-link">Services</a>
        <a href="#staff"    class="nav-link">Our Team</a>
        <a href="#contact"  class="nav-link">Contact</a>
      </nav>
      <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i class="fa fa-sun"></i><i class="fa fa-moon"></i>
        </button>
        <a class="btn-nav-login" href="{{ '/login' }}">
          <i class="fa fa-right-to-bracket"></i> Sign In
        </a>
      </div>
      <button class="hamburger" id="hamburger" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="hero-inner">
      <div class="hero-text">
        <div class="hero-badge">
          <i class="fa fa-shield-heart"></i> Trusted Campus Healthcare
        </div>
        <h1 class="hero-heading">
          Excellence<br>in <span class="gold">Campus</span><br>Healthcare
        </h1>
        <p class="hero-desc">
          The SUNN Medical &amp; Dental Services Office provides comprehensive
          health care to students and staff of the State University of Northern Negros.
          Your well-being is our top priority.
        </p>
        <div class="hero-btns">
          <a href="{{ '/login?role=student' }}" class="btn-primary">
            <i class="fa fa-calendar-plus"></i> Book Appointment
          </a>
          <a href="#services" class="btn-ghost">
            <i class="fa fa-stethoscope"></i> Our Services
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-num">{{ number_format($activeStudents) }}</span>
            <span class="stat-label">Active Students</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-num">{{ number_format($activeStaff) }}</span>
            <span class="stat-label">Active Medical Staff</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <span class="stat-num">{{ number_format($activeCampuses) }}</span>
            <span class="stat-label">Active Campuses</span>
          </div>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-card-wrap">
          <div class="hero-circle-bg"></div>
          <div class="hero-image-wrap">
            <img src="{{ asset('clinic/images/BG-CLINIC.jpg') }}" alt="SUNN Clinic" class="hero-img"/>
          </div>
          <div class="float-card float-card-top">
            <div class="fc-icon"><i class="fa fa-video"></i></div>
            <div class="fc-text">
              <strong>Online Records</strong>
              <span>Access your health records anytime</span>
            </div>
          </div>
          <div class="float-card float-card-bottom">
            <div class="fc-avatars">
              <img src="{{ asset('clinic/images/1st image.png') }}" alt="UNIHEALTH student" class="fc-avatar"/>
              <img src="{{ asset('clinic/images/2nd image.png') }}" alt="UNIHEALTH student" class="fc-avatar"/>
              <div class="fc-avatar-more">+</div>
            </div>
            <div class="fc-text">
              <strong>{{ number_format($activeStudents) }} Active Students</strong>
              <span>Currently registered in UNIHEALTH</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="hero-search-bar">
      <div class="search-item">
        <i class="fa fa-user-doctor"></i>
        <div>
          <span class="si-label">Service Type</span>
          <select class="si-select">
            <option>Medical Check-up</option>
            <option>Dental Check-up</option>
            <option>Medical Certificate</option>
          </select>
        </div>
      </div>
      <div class="search-divider"></div>
      <div class="search-item">
        <i class="fa fa-building-columns"></i>
        <div>
          <span class="si-label">Course</span>
          <select class="si-select">
            <option>All Courses</option>
            <option>Bachelor of Science in Nursing (BSN)</option>
            <option>Bachelor of Science in Information Technology (BSIT)</option>
            <option>Bachelor of Science in Fisheries (BSF)</option>
            <option>Bachelor of Secondary Education Major in English (BSEd-English)</option>
            <option>Bachelor of Science in Business Administration major in Marketing Management (BSBA-MM)</option>
          </select>
        </div>
      </div>
      <div class="search-divider"></div>
      <div class="search-item">
        <i class="fa fa-calendar"></i>
        <div>
          <span class="si-label">Schedule</span>
          <select class="si-select">
            <option>Mon–Fri  8AM–5PM</option>
            <option>Emergency Only</option>
          </select>
        </div>
      </div>
      <a href="{{ '/login?role=student' }}" class="search-btn">
        <i class="fa fa-arrow-right"></i>
      </a>
    </div>
  </section>

  <!-- TRUST BAR -->
  <section class="trust-bar">
    <div class="trust-inner">
      <span class="trust-label">Accredited &amp; Affiliated With</span>
      <div class="trust-logos">
        <span class="trust-logo-item"><i class="fa fa-building-columns"></i> CHED</span>
        <span class="trust-logo-item"><i class="fa fa-kit-medical"></i> DOH</span>
        <span class="trust-logo-item"><i class="fa fa-shield-halved"></i> PhilHealth</span>
        <span class="trust-logo-item"><i class="fa fa-heart-pulse"></i> PRC</span>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="about section" id="about">
    <div class="container">
      <div class="about-grid">
        <div class="about-img-col">
          <div class="about-img-wrap">
            <img src="{{ asset('clinic/images/BG-CLINIC.jpg') }}" alt="Clinic" class="about-img"/>
            <div class="about-badge">
              <i class="fa fa-award"></i>
              <div>
                <strong>Quality</strong>
                <span>Campus Healthcare</span>
              </div>
            </div>
          </div>
        </div>
        <div class="about-text-col">
          <div class="section-tag"><i class="fa fa-info-circle"></i> About Us</div>
          <h2 class="section-title">Committed to Your <span class="gold">Health &amp; Wellness</span></h2>
          <p class="section-desc">
            The SUNN Medical &amp; Dental Services Office has been serving the university community
            for over a decade. We provide free medical and dental consultations, health certificates,
            laboratory referrals, and emergency care to all enrolled students and faculty.
          </p>
          <ul class="about-list">
            <li><i class="fa fa-circle-check"></i> Free consultation for all enrolled students</li>
            <li><i class="fa fa-circle-check"></i> Licensed physicians, nurses &amp; dentists on duty</li>
            <li><i class="fa fa-circle-check"></i> Electronic health records management</li>
            <li><i class="fa fa-circle-check"></i> Referral system to partner hospitals</li>
          </ul>
          <a href="{{ '/login?role=student' }}" class="btn-primary">
            <i class="fa fa-user-plus"></i> Register Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services section" id="services">
    <div class="container">
      <div class="section-header">
        <div class="section-tag"><i class="fa fa-stethoscope"></i> What We Offer</div>
        <h2 class="section-title">Our <span class="gold">Medical Services</span></h2>
        <p class="section-sub">Comprehensive healthcare services available to all SUNN students and staff.</p>
      </div>
      <div class="services-grid">
        <div class="service-card">
          <div class="sc-icon"><i class="fa fa-user-doctor"></i></div>
          <h3>Medical Consultation</h3>
          <p>General check-ups, illness treatment, and health monitoring by licensed physicians.</p>
          <a href="{{ '/login' }}" class="sc-link">Book Now <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="service-card featured">
          <div class="sc-badge">Most Used</div>
          <div class="sc-icon"><i class="fa fa-tooth"></i></div>
          <h3>Dental Services</h3>
          <p>Oral exams, tooth extraction, and dental health education by our school dentist.</p>
          <a href="{{ '/login' }}" class="sc-link">Book Now <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="service-card">
          <div class="sc-icon"><i class="fa fa-file-medical"></i></div>
          <h3>Health Certificate</h3>
          <p>Official health certificates for OJT, scholarship, enrollment, and employment.</p>
          <a href="{{ '/login' }}" class="sc-link">Request Now <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="service-card">
          <div class="sc-icon"><i class="fa fa-flask"></i></div>
          <h3>Laboratory Referral</h3>
          <p>Referrals to accredited partner laboratories for blood work and diagnostics.</p>
          <a href="{{ '/login' }}" class="sc-link">Learn More <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="service-card">
          <div class="sc-icon"><i class="fa fa-kit-medical"></i></div>
          <h3>First Aid &amp; Emergency</h3>
          <p>Immediate care for injuries, accidents, and health emergencies on campus.</p>
          <a href="{{ '/login' }}" class="sc-link">Learn More <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="service-card">
          <div class="sc-icon"><i class="fa fa-heart-pulse"></i></div>
          <h3>Health Records</h3>
          <p>Digital management of your medical history, vaccinations, and clinic visits.</p>
          <a href="{{ '/login' }}" class="sc-link">View Portal <i class="fa fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- STAFF -->
  <section class="staff section" id="staff">
    <div class="container">
      <div class="section-header">
        <div class="section-tag"><i class="fa fa-users"></i> Meet the Team</div>
        <h2 class="section-title">Our <span class="gold">Medical Team</span></h2>
        <p class="section-sub">Licensed professionals dedicated to keeping the SUNN community healthy.</p>
      </div>
      <div class="staff-grid">
        <div class="staff-card">
          <div class="staff-img-wrap">
            <div class="staff-avatar"><i class="fa fa-user-doctor"></i></div>
          </div>
          <div class="staff-info">
            <h3>Dr. Cyrille S. Adraneda</h3>
            <span class="staff-role"><i class="fa fa-stethoscope"></i> School Physician</span>
            <span class="staff-lic">License No. MD-85606</span>
            <p>Provides general medical consultation and oversees campus health programs.</p>
          </div>
        </div>
        <div class="staff-card featured">
          <div class="staff-img-wrap">
            <div class="staff-avatar"><i class="fa fa-tooth"></i></div>
          </div>
          <div class="staff-info">
            <h3>Rhizza Faye T. Barcenilla, D.M.D</h3>
            <span class="staff-role"><i class="fa fa-tooth"></i> School Dentist</span>
            <span class="staff-lic">License No. DMD-0006828</span>
            <p>Handles all dental consultations, procedures, and oral health education.</p>
          </div>
        </div>
        <div class="staff-card">
          <div class="staff-img-wrap">
            <div class="staff-avatar"><i class="fa fa-user-nurse"></i></div>
          </div>
          <div class="staff-info">
            <h3>Nurse Maria Cruz Santos</h3>
            <span class="staff-role"><i class="fa fa-heart-pulse"></i> School Nurse</span>
            <span class="staff-lic">License No. RN-12345</span>
            <p>Manages daily clinic operations, health assessments, and first aid services.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-banner">
    <div class="container">
      <div class="cta-inner">
        <div class="cta-text">
          <h2>Ready to take care of your health?</h2>
          <p>Create your free account and access your health records, book appointments, and more.</p>
        </div>
        <div class="cta-btns">
          <a href="{{ '/login' }}" class="btn-primary">
            <i class="fa fa-user-plus"></i> Register for Free
          </a>
          <a href="{{ '/login' }}" class="btn-ghost-light">
            <i class="fa fa-right-to-bracket"></i> Sign In
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="contact section" id="contact">
    <div class="container">
      <div class="section-header">
        <div class="section-tag"><i class="fa fa-envelope"></i> Get in Touch</div>
        <h2 class="section-title">Contact <span class="gold">the Clinic</span></h2>
      </div>
      <div class="contact-grid">
        <div class="contact-card">
          <div class="cc-icon"><i class="fa fa-location-dot"></i></div>
          <h3>Address</h3>
          <p>SUNN Main Campus, Sagay City, Negros Occidental</p>
        </div>
        <div class="contact-card">
          <div class="cc-icon"><i class="fa fa-phone"></i></div>
          <h3>Phone</h3>
          <p>(034) 703 8802</p>
        </div>
        <div class="contact-card">
          <div class="cc-icon"><i class="fa fa-envelope"></i></div>
          <h3>Email</h3>
          <p>info@sunn.edu.ph</p>
        </div>
        <div class="contact-card">
          <div class="cc-icon"><i class="fa fa-clock"></i></div>
          <h3>Office Hours</h3>
          <p>Monday – Friday<br>8:00 AM – 5:00 PM</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <img src="{{ asset('clinic/images/sunn-logo.png') }}" alt="SUNN Logo" class="footer-logo"/>
          <p>State University of Northern Negros<br>Medical &amp; Dental Services Office</p>
          <p class="footer-tagline">Your Health, Our Priority.</p>
        </div>
        <div class="footer-links-col">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#staff">Our Team</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
        <div class="footer-links-col">
          <h4>Patient Portal</h4>
          <ul>
            <li><a href="{{ '/login' }}">Sign In</a></li>
            <li><a href="{{ '/login' }}">Register</a></li>
            <li><a href="{{ '/login' }}">Book Appointment</a></li>
            <li><a href="{{ '/login' }}">Health Records</a></li>
          </ul>
        </div>
        <div class="footer-links-col">
          <h4>Services</h4>
          <ul>
            <li><a href="#services">Medical Consultation</a></li>
            <li><a href="#services">Dental Services</a></li>
            <li><a href="#services">Health Certificate</a></li>
            <li><a href="#services">Laboratory Referral</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; {{ date('Y') }} State University of Northern Negros — Medical &amp; Dental Services Office. All rights reserved.</p>
        <p>Powered by <span class="gold">SUNN Clinic System</span></p>
      </div>
    </div>
  </footer>

  <div class="toast-container" id="toastContainer"></div>
  <script src="{{ asset('clinic/js/landing.js') }}"></script>
</body>
</html>
