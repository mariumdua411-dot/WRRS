<?php
$page_title = "About WRRS - Water Resource Reporting System";
require_once 'includes/header.php';
require_once 'config/db.php';
?>

<main class="main-wrapper about-wrapper">
    <div class="landing-content about-content">
<section class="about-hero">
<div class="about-hero-grid">
 <div class="about-hero-text">
<h2 class="about-title">Empowering Communities Through Water Resource Reporting System</h2>
<p class="about-lead"> The <strong>Water Resource Reporting System (WRRS)</strong> is an innovative, municipal-grade digital management platform dedicated to conserving clean water, mitigating infrastructure breakdowns, and accelerating maintenance response across communities.</p>
<div class="btn-group" style="margin-top: 20px;">
 <a href="report.php" class="btn btn-primary">
<span>📝</span> Report a Problem</a>
<a href="track.php" class="btn btn-outline">
 <span>🔍</span> Track Status</a>
</div>
</div>
</div>
</section>

            
<section class="mission-section">
<div class="section-heading-center">
<h2>Our Mission & Core Objectives</h2>
<p>Built with purpose to solve modern water utility challenges through technology.</p></div>
<div class="mission-grid">
<div class="mission-card">
<div class="mission-icon">💧</div>
<h3>Eliminate Water Loss</h3>
<p>Rapid detection and prompt technician assignment directly reduce non-revenue water loss caused by aging pipes, high-pressure bursts, and unattended leaks.</p></div>
<div class="mission-card">
<div class="mission-icon">🛡️</div>
<h3>Safeguard Water Quality</h3>
<p>Protect public health by enabling rapid escalation of contamination, chemical runoff, or discolored supply before it affects homes and schools.</p></div>

<div class="mission-card">
<div class="mission-icon">⚡</div>
<h3>Accelerate Field Response</h3>
<p>Equip Data Collectors and Technicians with real-time operational workspaces, eliminating bureaucracy and dispatching field experts immediately.</p>
</div>

<div class="mission-card">
<div class="mission-icon">🔍</div>
<h3>Public Transparency</h3>
<p>Every submitted problem receives a unique tracking reference code, giving citizens complete visibility from initial verification to photographic work proof.</p>
</div>
</div>
</section>

        
<section class="lifecycle-section">
<div class="section-heading-center">
<h2>How WRRS Works: The Resolution Lifecycle</h2>
<p>A closed-loop operational flow guaranteeing fast, verified, and accountable maintenance.</p>
</div>

<div class="lifecycle-steps-grid">
                <!-- Step 1 -->
                <div class="lifecycle-card">
                    <div class="lifecycle-img-wrap">
                        <img src="assets/images/leak.jpg" alt="Reporting Water Leaks and Bursts">
                        <span class="step-badge">Step 1</span>
                    </div>
                    <div class="lifecycle-body">
                        <h4>1. Citizen Reports Issue</h4>
                        <p>Citizens spot a pipe leakage, low pressure, or contamination and submit a report with GPS/location details and photographic evidence.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="lifecycle-card">
                    <div class="lifecycle-img-wrap">
                        <img src="assets/images/dc verify.jpg" alt="Data Collector Verification">
                        <span class="step-badge">Step 2</span>
                    </div>
                    <div class="lifecycle-body">
                        <h4>2. Verification & Assignment</h4>
                        <p>Data Collectors inspect the incoming report, assess priority levels (Low, Medium, High, Urgent), and assign the job to certified technicians.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="lifecycle-card">
                    <div class="lifecycle-img-wrap">
                        <img src="assets/images/fixing.jpg" alt="Technician On-Site Repair">
                        <span class="step-badge">Step 3</span>
                    </div>
                    <div class="lifecycle-body">
                        <h4>3. Technical Field Repair</h4>
                        <p>Specialized plumbers and hydraulic operators receive the job notification, proceed to the location, and carry out on-site repairs.</p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="lifecycle-card">
                    <div class="lifecycle-img-wrap">
                        <img src="assets/images/fixed.jpg" alt="Verified Resolution and Proof">
                        <span class="step-badge">Step 4</span>
                    </div>
                    <div class="lifecycle-body">
                        <h4>4. Resolution & Proof</h4>
                        <p>Technicians upload completion proof photos and log work notes. The issue is officially marked Resolved and citizens can verify the fix.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="contact-card-container">
    <div class="contact-card">
        <h2 class="contact-card-title">Contact Us</h2>
        <div class="contact-grid">
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="contact-details">
                    <span class="contact-label">Phone</span>
                    <a href="tel:+255624947107" class="contact-value">+255 624 947 107</a>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-details">
                    <span class="contact-label">Email</span>
                    <a href="mailto:info@wrrs.co.tz" class="contact-value">info@wrrs.co.tz</a>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="contact-details">
                    <span class="contact-label">Location</span>
                    <span class="contact-value">Dodoma, Tanzania</span>
                </div>
            </div>

        </div>
    </div>
</div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
<script src="assets/js/wrrs.js"></script>
