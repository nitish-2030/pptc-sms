<?php
// ============================================================
// index.php — Landing Page v2
// Pentium Point Technical College, Rewa (M.P.)
// ============================================================
require_once 'config/auth_check.php';    // ← Login check (v4)
require_once 'config/db.php';
require_once 'config/courses_helper.php';
$all_courses = get_all_courses($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pentium Point Group of Institutions — Rewa (M.P.)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/img/pptc_logo.png" type="image/png">
</head>
<body class="landing-page">

<!-- Background college photo -->
<div class="hero-bg"></div>
<!-- Vignette overlay -->
<div class="hero-overlay"></div>
<!-- Floating particles container -->
<div class="particles"></div>

<!-- ====================================================
     SOCIAL TOPBAR
     ==================================================== -->
<div class="social-topbar">
    <div class="topbar-left">
        <span>📞</span>
        <a href="tel:07662438035" class="topbar-phone">07662438035</a>
        &nbsp;|&nbsp;
        <span>✉️</span>
        <a href="mailto:pptcrewa@rediffmail.com" class="topbar-phone" style="font-size:0.78rem;">pptcrewa@rediffmail.com</a>
    </div>
   <div class="topbar-right">
    <a href="https://www.facebook.com/pptcrewamp" class="social-icon fb" title="Facebook" target="_blank" rel="noopener">f</a>
    <a href="#" class="social-icon tw" title="Twitter/X" target="_blank" rel="noopener">X</a>
    <a href="#" class="social-icon li" title="LinkedIn"  target="_blank" rel="noopener">in</a>
    <a href="https://www.youtube.com/@pentiumpointtechnicalcolle4660" class="social-icon yt" title="YouTube" target="_blank" rel="noopener">▶</a>
</div>
</div>

<!-- ====================================================
     HERO SECTION
     ==================================================== -->
<div class="hero">
    <div class="hero-content">

        <!-- Animated Logo -->
        <div class="hero-logo-wrap">
            <div class="logo-ring"></div>
            <div class="logo-ring"></div>
            <div class="logo-ring"></div>
            <img src="assets/img/pptc_group_logo.png" alt="PPTC Logo" class="hero-logo">
        </div>

        <p class="hero-tagline">Rewa (M.P.)&nbsp; · &nbsp;A Unit of Shiv Computer Institute Society</p>

        <h1 class="hero-title">
            Pentium Point<br>
            <span>Group of Institutions</span>
        </h1>
        <p class="hero-sub">Student Management System &mdash; Powered by Knowledge &amp; Technology</p>

        <a href="dashboard.php" class="hero-cta">
            &#9654;&nbsp; Go to Dashboard
        </a>

        <!-- Stats Strip -->
        <div class="stats-strip">
            <div class="strip-stat">
                <span class="strip-num" data-target="1200" data-suffix="+">0+</span>
                <span class="strip-lbl">Students</span>
            </div>
            <div class="strip-stat">
                <span class="strip-num" data-target="30" data-suffix="+">0+</span>
                <span class="strip-lbl">Courses</span>
            </div>
            <div class="strip-stat">
                <span class="strip-num" data-target="50" data-suffix="+">0+</span>
                <span class="strip-lbl">Faculty</span>
            </div>
            <div class="strip-stat">
                <span class="strip-num" data-target="4" data-suffix="">0</span>
                <span class="strip-lbl">Colleges</span>
            </div>
            <div class="strip-stat">
                <span class="strip-num" data-target="20" data-suffix="yrs">0yrs</span>
                <span class="strip-lbl">Experience</span>
            </div>
        </div>
    </div>

    <!-- ====================================================
         IMAGE CAROUSEL
         ==================================================== -->
    <div class="carousel-section">
        <div style="position:relative;">
            <div class="carousel-track-wrapper">
                <div class="carousel-track">
                    <div class="carousel-slide">
                        <img src="assets/img/college1.png" alt="Pentium Point Campus">
                        <div class="slide-caption">
                            <h3>Our Campus — Rewa, M.P.</h3>
                            <p>State-of-the-art learning environment in the heart of Rewa.</p>
                        </div>
                    </div>
                    <div class="carousel-slide">
                        <img src="assets/img/college3.png" alt="Modern Facilities" style="filter:brightness(0.6) hue-rotate(10deg) saturate(1.4);">
                        <div class="slide-caption">
                            <h3>Modern Facilities</h3>
                            <p>Fully equipped labs, libraries, and digital classrooms.</p>
                        </div>
                    </div>
                    <div class="carousel-slide">
                        <img src="assets/img/college4.png" alt="Green Campus" style="filter:brightness(0.65) saturate(1.8) hue-rotate(-10deg);">
                        <div class="slide-caption">
                            <h3>Green &amp; Serene Campus</h3>
                            <p>Lush surroundings to inspire creativity and academic excellence.</p>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-btn prev" aria-label="Previous">&#8249;</button>
            <button class="carousel-btn next" aria-label="Next">&#8250;</button>
        </div>
        <div class="carousel-dots"></div>
    </div>

</div><!-- /hero -->

<!-- ====================================================
     COURSES SECTION
     ==================================================== -->
<div class="landing-section" id="courses">
    <div class="section-heading">
        <span class="eyebrow">📖 What We Offer</span>
        <h2>Our Programmes</h2>
        <p>Choose from 30+ undergraduate, postgraduate, law, pharmacy and diploma programmes affiliated with AICTE, RGPV, PCI &amp; Bar Council of India.</p>
    </div>

    <!-- Category Tabs -->
    <div class="course-tabs">
        <?php
        $cat_labels = ['UG'=>'Under Graduate','PG'=>'Post Graduate','Law'=>'Law','Pharma'=>'Pharmacy','Diploma'=>'Diploma'];
        $first = true;
        foreach ($cat_labels as $cat => $label):
            if (!isset($all_courses[$cat])) continue;
        ?>
        <button class="course-tab <?= $first ? 'active' : '' ?>" data-cat="<?= $cat ?>">
            <?= $label ?> <span style="opacity:0.6;font-size:0.72rem;">(<?= count($all_courses[$cat]) ?>)</span>
        </button>
        <?php $first = false; endforeach; ?>
    </div>

    <!-- Course Panels -->
    <?php $first = true; foreach ($cat_labels as $cat => $label): if (!isset($all_courses[$cat])) continue; ?>
    <div class="course-panel <?= $first ? 'active' : '' ?>" id="panel-<?= $cat ?>">
        <?php foreach ($all_courses[$cat] as $course): ?>
        <div class="course-card">
            <span class="course-icon"><?= $course['icon'] ?? '🎓' ?></span>
            <div class="course-code"><?= htmlspecialchars($course['name']) ?></div>
            <div class="course-desc"><?= htmlspecialchars($course['full_title']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php $first = false; endforeach; ?>
</div>

<div class="section-divider"></div>

<!-- ====================================================
     OUR COLLEGES SECTION
     ==================================================== -->
<div class="landing-section" id="colleges">
    <div class="section-heading">
        <span class="eyebrow">🏛️ Our Group</span>
        <h2>Our Colleges</h2>
        <p>Pentium Point Group of Institutions runs four premier colleges under the umbrella of Shiv Computer Institute Society, Rewa.</p>
    </div>

    <div class="colleges-grid">

        <div class="college-card">
            <span class="college-emoji"> <img src="assets/img/pptc_logo.png"
         alt="PPTC Logo"
         style="width:70px;height:70px;object-fit:contain;margin:0 auto 1rem;display:block;
                filter:drop-shadow(0 2px 8px rgba(201,168,76,0.4));"></span>
            <h3>Pentium Point Technical College</h3>
            <p>Offers BCA, B.Sc, BBA and Science programmes. Affiliated with Barkatullah University, Bhopal.</p>
            <span class="college-badge">Science &amp; Technology</span>
        </div>

        <div class="college-card">
            <span class="college-emoji"><img src="assets/img/ppcm_logo.png"
         alt="PPTC Logo"
         style="width:70px;height:70px;object-fit:contain;margin:0 auto 1rem;display:block;
                filter:drop-shadow(0 2px 8px rgba(201,168,76,0.4));"></span>
            <h3>Pentium Point College of Management</h3>
            <p>Offers MBA and BBA programmes. Recognized by AICTE New Delhi &amp; affiliated with RGPV, Bhopal.</p>
            <span class="college-badge">Management · AICTE</span>
        </div>

        <div class="college-card">
            <span class="college-emoji"><img src="assets/img/ppcp_logo.png"
         alt="PPTC Logo"
         style="width:70px;height:70px;object-fit:contain;margin:0 auto 1rem;display:block;
                filter:drop-shadow(0 2px 8px rgba(201,168,76,0.4));"></span>
            <h3>Pentium Point College of Pharmacy</h3>
            <p>Offers B. Pharma &amp; D. Pharma. Recognized by Pharmacy Council of India, New Delhi &amp; affiliated RGPV Bhopal.</p>
            <span class="college-badge">Pharmacy · PCI · RGPV</span>
        </div>

        <div class="college-card">
            <span class="college-emoji"><img src="assets/img/ppcl_logo.png"
         alt="PPTC Logo"
         style="width:70px;height:70px;object-fit:contain;margin:0 auto 1rem;display:block;
                filter:drop-shadow(0 2px 8px rgba(201,168,76,0.4));"></span>
            <h3>Pentium Point College of Law</h3>
            <p>Offers LLB, LLM, BALLB, BBA LLB &amp; B.Com LLB. Recognized by Bar Council of India, New Delhi.</p>
            <span class="college-badge">Law · Bar Council of India</span>
        </div>

    </div>
</div>

<div class="section-divider"></div>

<!-- ====================================================
     ABOUT US SECTION
     ==================================================== -->
<div class="landing-section" id="about">
    <div class="section-heading">
        <span class="eyebrow">ℹ️ Who We Are</span>
        <h2>About Us</h2>
    </div>

    <div class="about-grid">
        <div class="about-img-wrap">
            <img src="assets/img/college2.png" alt="Pentium Point Technical College Campus">
            <div class="about-img-overlay"></div>
        </div>

        <div class="about-content">
            <h3>Pentium Point Group of Institutions</h3>
            <h2>Shaping Futures Since Day One</h2>
            <p>
                Pentium Point Group of Institutions, Rewa (M.P.) is a premier educational group established as
                <strong style="color:var(--gold-lt);">A Unit of Shiv Computer Institute Society</strong>.
                With a sprawling green campus in the heart of Rewa, we are committed to delivering quality education
                across science, commerce, technology, law, management and pharmacy disciplines.
            </p>
            <p>
                Our programmes are affiliated with <strong style="color:var(--gold-lt);">Barkatullah University Bhopal</strong>,
                approved by <strong style="color:var(--gold-lt);">AICTE New Delhi</strong>,
                <strong style="color:var(--gold-lt);">Pharmacy Council of India</strong> and
                <strong style="color:var(--gold-lt);">Bar Council of India</strong>
                — ensuring nationally recognized degrees for every student.
            </p>
            <p>
                We house four specialized colleges under one umbrella — Technical, Management, Pharmacy and Law —
                giving students the freedom to pursue their passion while benefiting from shared world-class infrastructure.
            </p>

            <div class="about-tags">
                <span class="about-tag">AICTE Approved</span>
                <span class="about-tag">Bar Council</span>
                <span class="about-tag">PCI Approved</span>
                <span class="about-tag">RGPV Affiliated</span>
                <span class="about-tag">30+ Courses</span>
                <span class="about-tag">Rewa, M.P.</span>
            </div>

            <div class="contact-strip" style="justify-content:flex-start;margin-top:1.25rem;">
                <div class="contact-item">
                    <span>📞</span>
                    <a href="tel:07662438035">07662438035</a>
                </div>
                <div class="contact-item">
                    <span>✉️</span>
                    <a href="mailto:pptcrewa@rediffmail.com">pptcrewa@rediffmail.com</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================
     LANDING FOOTER
     ==================================================== -->
<footer class="landing-footer">
    <div class="landing-footer-grid">

        <!-- Brand Column -->
        <div class="footer-brand">
            <img src="assets/img/pptc_group_logo.png" alt="PPTC Logo">
            <p>Pentium Point Group of Institutions<br>A Unit of Shiv Computer Institute Society<br>Rewa, Madhya Pradesh</p>
            <div class="footer-social-row">
                <a href="https://www.facebook.com/pptcrewamp" class="social-icon fb" title="Facebook" target="_blank" rel="noopener">f</a>
                <a href="#"  class="social-icon tw" title="Twitter"  target="_blank" rel="noopener">𝕏</a>
                <a href="#"  class="social-icon li" title="LinkedIn" target="_blank" rel="noopener">in</a>
                <a href="https://www.youtube.com/@pentiumpointtechnicalcolle4660" class="social-icon yt" title="YouTube" target="_blank" rel="noopener">▶</a>
            </div>
        </div>

        <!-- Featured Links -->
        <div class="footer-col">
            <h4>Featured Links</h4>
            <ul>
                <li><a href="#">From the Desk of CMD</a></li>
                <li><a href="#">Principal's Message</a></li>
                <li><a href="#">ALERT: Fee Submission</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="dashboard.php">SMS Dashboard</a></li>
            </ul>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="#">Photo Gallery</a></li>
                <li><a href="#">Video Gallery</a></li>
                <li><a href="#">Social Media</a></li>
                <li><a href="#courses">Our Courses</a></li>
                <li><a href="#colleges">Our Colleges</a></li>
            </ul>
        </div>

        <!-- Information -->
        <div class="footer-col">
            <h4>Information</h4>
            <div class="info-item">
                <span class="info-icon">📞</span>
                <div><a href="tel:07662438035">07662438035</a></div>
            </div>
            <div class="info-item">
                <span class="info-icon">✉️</span>
                <div><a href="mailto:pptcrewa@rediffmail.com">pptcrewa@rediffmail.com</a></div>
            </div>
            <div class="info-item">
                <span class="info-icon">📍</span>
                <div><a href="https://www.google.com/maps/place/Pentium+Point+Technical+College/@24.5434576,81.273314,11.75z/data=!4m6!3m5!1s0x39845a32df604725:0x9e361d6f5a5fb80c!8m2!3d24.5663647!4d81.2726734!16s%2Fg%2F12qfh1q9n?entry=ttu&g_ep=EgoyMDI2MDIyMi4wIKXMDSoASAFQAw%3D%3D"
           target="_blank"
           rel="noopener"
           style="color:rgba(255,255,255,0.6);text-decoration:none;border-bottom:1px dashed rgba(201,168,76,0.5);">
            Rewa, Madhya Pradesh, India 🗺️
        </a></div>
            </div>
            <div class="info-item">
                <span class="info-icon">🕐</span>
                <div>Mon – Sat: 9:00 AM – 5:00 PM</div>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <span>Pentium Point Group of Institutions</span> &mdash; All Rights Reserved</p>
        <p>Designed with ❤️ for <span>PPTC, Rewa</span></p>
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>
