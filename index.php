<?php
require_once __DIR__ . '/includes/functions.php';
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(EVENT_NAME) ?> — Register Now</title>
<meta name="description" content="Register for <?= e(EVENT_NAME) ?> — 5K, 10K, Half & Full Marathon. <?= e(EVENT_DATE) ?> at <?= e(EVENT_VENUE) ?>.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="#top"><i class="bi bi-lightning-charge-fill text-accent"></i> VELOCITY<span class="text-accent">26</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#categories">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="#schedule">Schedule</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <li class="nav-item"><a class="btn btn-accent px-4 ms-lg-2" href="#register">Register</a></li>
        <li class="nav-item"><a class="nav-link small text-secondary" href="admin/login.php"><i class="bi bi-shield-lock"></i> Admin</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ======= HERO ======= -->
<header class="hero d-flex align-items-center" id="top">
  <div class="hero-glow"></div>
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge-pill"><i class="bi bi-calendar-event me-2"></i><?= e(EVENT_DATE) ?> · <?= e(EVENT_VENUE) ?></span>
        <h1 class="hero-title mt-3">RUN THE<br><span class="stroke-text">CITY.</span> OWN THE<br><span class="text-accent">FINISH LINE.</span></h1>
        <p class="lead text-secondary col-lg-9 px-0 mt-3">
          Join 10,000+ runners at <?= e(EVENT_NAME) ?> — four distances, one unforgettable
          sunrise route along the waterfront. Every finisher gets a medal, tee and timing certificate.
        </p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="#register" class="btn btn-accent btn-lg px-5">Register Now <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="#categories" class="btn btn-outline-light btn-lg px-4">View Categories</a>
        </div>
      </div>
      <div class="col-lg-4 d-none d-lg-block text-center">
        <div class="hero-emoji">🏃‍♂️</div>
      </div>
    </div>
    <!-- countdown -->
    <div class="row mt-5">
      <div class="col-lg-8">
        <div class="countdown d-flex gap-3" id="countdown" data-target="2026-11-15T05:00:00+05:30">
          <div class="count-box"><span id="cd-days">--</span><small>Days</small></div>
          <div class="count-box"><span id="cd-hours">--</span><small>Hours</small></div>
          <div class="count-box"><span id="cd-mins">--</span><small>Minutes</small></div>
          <div class="count-box"><span id="cd-secs">--</span><small>Seconds</small></div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ======= STATS STRIP ======= -->
<section class="stats-strip py-4">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><h3 class="stat-num">10K+</h3><p class="stat-label">Runners Expected</p></div>
      <div class="col-6 col-md-3"><h3 class="stat-num">4</h3><p class="stat-label">Race Categories</p></div>
      <div class="col-6 col-md-3"><h3 class="stat-num">₹12L</h3><p class="stat-label">Prize Pool</p></div>
      <div class="col-6 col-md-3"><h3 class="stat-num">42.2</h3><p class="stat-label">KM Full Marathon</p></div>
    </div>
  </div>
</section>

<!-- ======= ABOUT ======= -->
<section class="section" id="about">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="section-tag">// ABOUT THE RACE</span>
        <h2 class="section-title">More Than A Marathon.<br>A Movement.</h2>
        <p class="text-secondary mt-3">
          Now in its 8th edition, Velocity Marathon is Eastern India's largest running festival.
          The AIMS-certified course winds along the riverfront, through heritage boulevards and
          finishes inside a roaring stadium.
        </p>
        <div class="row g-3 mt-2">
          <div class="col-sm-6"><div class="feature-box"><i class="bi bi-award"></i><div><strong>AIMS Certified</strong><small>Internationally measured course</small></div></div></div>
          <div class="col-sm-6"><div class="feature-box"><i class="bi bi-stopwatch"></i><div><strong>Chip Timing</strong><small>Instant results & certificates</small></div></div></div>
          <div class="col-sm-6"><div class="feature-box"><i class="bi bi-droplet"></i><div><strong>Hydration Every 2K</strong><small>Water, electrolytes & fruits</small></div></div></div>
          <div class="col-sm-6"><div class="feature-box"><i class="bi bi-heart-pulse"></i><div><strong>Medical Support</strong><small>Ambulances & physio zones</small></div></div></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="about-card">
          <h5 class="text-accent mb-4"><i class="bi bi-gift me-2"></i>Every Runner Gets</h5>
          <ul class="perk-list">
            <li><i class="bi bi-check2-circle"></i> Finisher medal & digital certificate</li>
            <li><i class="bi bi-check2-circle"></i> Dry-fit event T-shirt (size of your choice)</li>
            <li><i class="bi bi-check2-circle"></i> Personalized race bib with chip timing</li>
            <li><i class="bi bi-check2-circle"></i> Post-race breakfast & recovery zone</li>
            <li><i class="bi bi-check2-circle"></i> Official race photos — free download</li>
            <li><i class="bi bi-check2-circle"></i> QR-coded digital entry pass</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======= CATEGORIES ======= -->
<section class="section section-alt" id="categories">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-tag">// PICK YOUR DISTANCE</span>
      <h2 class="section-title">Race Categories</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-3">
        <div class="cat-card">
          <div class="cat-dist">5K</div>
          <h5>Fun Run</h5>
          <p class="text-secondary small">Perfect first race. Walkers, families & kids (12+) welcome.</p>
          <div class="cat-price">₹499</div>
          <ul class="small text-secondary list-unstyled">
            <li><i class="bi bi-clock me-2 text-accent"></i>Start 7:00 AM</li>
            <li><i class="bi bi-person me-2 text-accent"></i>Age 12+</li>
          </ul>
          <a href="#register" class="btn btn-outline-accent w-100 mt-2" data-category="5K Fun Run">Choose 5K</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="cat-card">
          <div class="cat-dist">10K</div>
          <h5>Challenge</h5>
          <p class="text-secondary small">The crowd favourite — fast, flat and festival vibes throughout.</p>
          <div class="cat-price">₹799</div>
          <ul class="small text-secondary list-unstyled">
            <li><i class="bi bi-clock me-2 text-accent"></i>Start 6:15 AM</li>
            <li><i class="bi bi-person me-2 text-accent"></i>Age 15+</li>
          </ul>
          <a href="#register" class="btn btn-outline-accent w-100 mt-2" data-category="10K Challenge">Choose 10K</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="cat-card featured">
          <span class="cat-flag">MOST POPULAR</span>
          <div class="cat-dist">21K</div>
          <h5>Half Marathon</h5>
          <p class="text-secondary small">Test your limits on the certified half with pacer buses.</p>
          <div class="cat-price">₹1,199</div>
          <ul class="small text-secondary list-unstyled">
            <li><i class="bi bi-clock me-2 text-accent"></i>Start 5:45 AM</li>
            <li><i class="bi bi-person me-2 text-accent"></i>Age 18+</li>
          </ul>
          <a href="#register" class="btn btn-accent w-100 mt-2" data-category="21K Half Marathon">Choose 21K</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="cat-card">
          <div class="cat-dist">42K</div>
          <h5>Full Marathon</h5>
          <p class="text-secondary small">The ultimate 42.195 km — glory, grit and a roaring stadium finish.</p>
          <div class="cat-price">₹1,699</div>
          <ul class="small text-secondary list-unstyled">
            <li><i class="bi bi-clock me-2 text-accent"></i>Start 5:00 AM</li>
            <li><i class="bi bi-person me-2 text-accent"></i>Age 18+</li>
          </ul>
          <a href="#register" class="btn btn-outline-accent w-100 mt-2" data-category="42K Full Marathon">Choose 42K</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======= SCHEDULE ======= -->
<section class="section" id="schedule">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-tag">// RACE DAY</span>
      <h2 class="section-title">Event Schedule</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="timeline">
          <div class="tl-item"><div class="tl-time">4:00 AM</div><div class="tl-body"><strong>Venue Opens</strong><p>Baggage counters, warm-up zone & bib verification desks open.</p></div></div>
          <div class="tl-item"><div class="tl-time">5:00 AM</div><div class="tl-body"><strong>42K Full Marathon Flag-off</strong><p>Elite & open wave starts with pacers from 3:30 to 5:30.</p></div></div>
          <div class="tl-item"><div class="tl-time">5:45 AM</div><div class="tl-body"><strong>21K Half Marathon Flag-off</strong><p>Two waves, five minutes apart.</p></div></div>
          <div class="tl-item"><div class="tl-time">6:15 AM</div><div class="tl-body"><strong>10K Challenge Flag-off</strong><p>Single mass start with live drum line.</p></div></div>
          <div class="tl-item"><div class="tl-time">7:00 AM</div><div class="tl-body"><strong>5K Fun Run Flag-off</strong><p>Families, first-timers and fancy-dress crews.</p></div></div>
          <div class="tl-item"><div class="tl-time">9:30 AM</div><div class="tl-body"><strong>Prize Ceremony & Concert</strong><p>Podium finishes, lucky draws and a live band at the finish arena.</p></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======= FAQ ======= -->
<section class="section section-alt" id="faq">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-tag">// GOOD TO KNOW</span>
      <h2 class="section-title">Frequently Asked Questions</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion accordion-flush" id="faqAcc">
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q1">How does registration work?</button></h2>
            <div id="q1" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body">Fill the form below, verify your email with the OTP we send you, and you'll instantly receive your unique Registration ID and QR-coded entry pass by email.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q2">What is the QR code for?</button></h2>
            <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body">Your QR code is your digital entry pass. Show it at the race-kit collection desk — our crew scans it to verify your registration and check you in on race day.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q3">Can I change my category or T-shirt size later?</button></h2>
            <div id="q3" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body">Category and size changes are allowed until 25 October 2026. Email our helpdesk with your Registration ID.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q4">Is there an age limit?</button></h2>
            <div id="q4" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body">5K: 12 years and above. 10K: 15+. 21K and 42K: 18+ on race day. Carry a photo ID during kit collection.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q5">I didn't receive the OTP / confirmation email. What now?</button></h2>
            <div id="q5" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body">Check your spam folder first. You can hit "Resend OTP" up to 3 times. Still stuck? Write to support@velocitymarathon.in.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======= REGISTER ======= -->
<section class="section" id="register">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-tag">// SECURE YOUR BIB</span>
      <h2 class="section-title">Register Now</h2>
      <p class="text-secondary">Fill in your details — we'll email you an OTP to verify, then your QR entry pass lands in your inbox.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        <form id="regForm" class="reg-card needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

          <h6 class="form-section-title"><i class="bi bi-person-badge me-2"></i>Personal Details</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="first_name">First Name *</label>
              <input type="text" class="form-control" id="first_name" name="first_name" maxlength="60" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="last_name">Last Name *</label>
              <input type="text" class="form-control" id="last_name" name="last_name" maxlength="60" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email Address *</label>
              <input type="email" class="form-control" id="email" name="email" maxlength="190" required>
              <div class="invalid-feedback"></div>
              <small class="text-secondary">OTP & entry pass will be sent here.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Mobile Number *</label>
              <input type="tel" class="form-control" id="phone" name="phone" maxlength="10" inputmode="numeric" pattern="[6-9][0-9]{9}" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="gender">Gender *</label>
              <select class="form-select" id="gender" name="gender" required>
                <option value="">Select…</option>
                <option>Male</option><option>Female</option><option>Other</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="dob">Date of Birth *</label>
              <input type="date" class="form-control" id="dob" name="dob" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="blood_group">Blood Group *</label>
              <select class="form-select" id="blood_group" name="blood_group" required>
                <option value="">Select…</option>
                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <h6 class="form-section-title mt-4"><i class="bi bi-flag me-2"></i>Race Details</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="category">Race Category *</label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Select…</option>
                <?php foreach (CATEGORY_FEES as $cat => $fee): ?>
                <option value="<?= e($cat) ?>" data-fee="<?= (int)$fee ?>"><?= e($cat) ?> — ₹<?= number_format($fee) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback"></div>
              <small class="text-secondary" id="feeHint">Registration fee is payable online after email verification.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tshirt_size">T-Shirt Size *</label>
              <select class="form-select" id="tshirt_size" name="tshirt_size" required>
                <option value="">Select…</option>
                <option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <h6 class="form-section-title mt-4"><i class="bi bi-telephone-plus me-2"></i>Emergency Contact</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="emergency_name">Contact Name *</label>
              <input type="text" class="form-control" id="emergency_name" name="emergency_name" maxlength="120" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="emergency_phone">Contact Number *</label>
              <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" maxlength="10" inputmode="numeric" pattern="[6-9][0-9]{9}" required>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <h6 class="form-section-title mt-4"><i class="bi bi-geo-alt me-2"></i>Address</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="city">City *</label>
              <input type="text" class="form-control" id="city" name="city" maxlength="80" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="state">State *</label>
              <input type="text" class="form-control" id="state" name="state" maxlength="80" required>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-12">
              <label class="form-label" for="address">Full Address *</label>
              <textarea class="form-control" id="address" name="address" rows="2" maxlength="255" required></textarea>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required>
            <label class="form-check-label small text-secondary" for="terms">
              I confirm I am medically fit to participate and accept the <a href="#" class="text-accent" onclick="return false;">terms, conditions & liability waiver</a>. *
            </label>
            <div class="invalid-feedback">You must accept the terms & waiver.</div>
          </div>

          <div class="alert alert-danger d-none mt-3 mb-0" id="formAlert" role="alert"></div>

          <button type="submit" class="btn btn-accent btn-lg w-100 mt-4" id="submitBtn">
            <span class="btn-text"><i class="bi bi-envelope-check me-2"></i>Verify Email & Proceed to Payment</span>
            <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ======= FOOTER ======= -->
<footer class="footer py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <a class="navbar-brand" href="#top"><i class="bi bi-lightning-charge-fill text-accent"></i> VELOCITY<span class="text-accent">26</span></a>
        <p class="text-secondary small mt-3 col-lg-9">
          <?= e(EVENT_NAME) ?> · <?= e(EVENT_DATE) ?><br><?= e(EVENT_VENUE) ?><br>
          Organized by Velocity Sports Foundation.
        </p>
      </div>
      <div class="col-6 col-lg-3">
        <h6 class="text-white mb-3">Quick Links</h6>
        <ul class="list-unstyled small footer-links">
          <li><a href="#about">About the race</a></li>
          <li><a href="#categories">Categories & pricing</a></li>
          <li><a href="#schedule">Race-day schedule</a></li>
          <li><a href="#register">Register</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-4">
        <h6 class="text-white mb-3">Contact</h6>
        <ul class="list-unstyled small text-secondary">
          <li class="mb-2"><i class="bi bi-envelope me-2 text-accent"></i>support@velocitymarathon.in</li>
          <li class="mb-2"><i class="bi bi-telephone me-2 text-accent"></i>+91 98300 00000</li>
          <li><i class="bi bi-geo-alt me-2 text-accent"></i><?= e(EVENT_VENUE) ?></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary-subtle mt-4">
    <p class="small text-secondary text-center mb-0">© 2026 Velocity Sports Foundation. All rights reserved.</p>
  </div>
</footer>

<!-- ======= OTP MODAL ======= -->
<div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="otpModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="otpModalTitle"><i class="bi bi-shield-check text-accent me-2"></i>Verify Your Email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-0">
        <p class="text-secondary small" id="otpSentTo">We've sent a 6-digit code to your email.</p>
        <div class="d-flex justify-content-center gap-2 my-4 otp-inputs" id="otpInputs">
          <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" class="form-control otp-digit" maxlength="1" inputmode="numeric" autocomplete="one-time-code" aria-label="OTP digit <?= $i + 1 ?>">
          <?php endfor; ?>
        </div>
        <div class="alert alert-danger d-none small py-2" id="otpAlert" role="alert"></div>
        <button class="btn btn-accent w-100" id="verifyBtn">
          <span class="btn-text">Verify & Complete Registration</span>
          <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
        </button>
        <button class="btn btn-link text-secondary small mt-2" id="resendBtn" disabled>Resend OTP (<span id="resendTimer">30</span>s)</button>
      </div>
    </div>
  </div>
</div>

<!-- ======= PAYMENT MODAL ======= -->
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="payModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="payModalTitle"><i class="bi bi-credit-card text-accent me-2"></i>Complete Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-0">
        <p class="text-secondary small mb-1">Email verified <i class="bi bi-patch-check-fill text-success"></i> — one last step:</p>
        <div class="reg-id-box my-3">
          <small class="text-secondary d-block" id="payCategory">RACE CATEGORY</small>
          <span class="reg-id" id="payAmount">₹0</span>
        </div>
        <div class="alert alert-danger d-none small py-2" id="payAlert" role="alert"></div>
        <button class="btn btn-accent w-100" id="payBtn">
          <span class="btn-text"><i class="bi bi-shield-lock me-2"></i>Pay Securely with Razorpay</span>
          <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
        </button>
        <p class="small text-secondary mt-3 mb-0"><i class="bi bi-lock me-1"></i>Your registration is confirmed only after successful payment.</p>
      </div>
    </div>
  </div>
</div>

<!-- ======= SUCCESS MODAL ======= -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="successTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-body p-4 p-md-5">
        <div class="success-check mb-3"><i class="bi bi-check-lg"></i></div>
        <h4 id="successTitle" class="mb-1">You're In! 🎉</h4>
        <p class="text-secondary small">Registration confirmed for <strong id="successName" class="text-white"></strong></p>
        <div class="reg-id-box my-3">
          <small class="text-secondary d-block">YOUR REGISTRATION ID</small>
          <span id="successRegId" class="reg-id"></span>
        </div>
        <p class="small mb-2"><span class="text-secondary">Payment received:</span> <strong id="successAmount" class="text-success"></strong>
          <span class="text-secondary d-block" style="font-size:.75rem;" id="successPayId"></span></p>
        <img id="successQr" src="" alt="Your registration QR code" class="success-qr mb-3">
        <p class="small text-secondary" id="successEmailNote">A confirmation email with your QR entry pass has been sent to your inbox.</p>
        <button class="btn btn-accent w-100" data-bs-dismiss="modal">Done</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
