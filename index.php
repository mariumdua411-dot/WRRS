<?php
session_start();
require_once 'config/db.php';

$page_title = "Home - Water Resource Reporting System (WRRS)";
include_once 'includes/header.php';
?>

<main class="main-wrapper home-main-wrapper">
    <div class="landing-content">

        <section class="hero-container">
            <div class="hero-left">
                <h2>Help Us Protect<br>Our Water Resources</h2>
                <p>
                    Report leaks, pipe bursts, low water pressure, and contamination in your community. WRRS connects citizens directly to municipal data collectors and certified field technicians for rapid repair.
                </p>

                <div class="btn-group">
                    <a href="report.php" class="btn btn-primary btn-lg">
                        <span>📝</span> REPORT A PROBLEM
                    </a>
                    <a href="track.php" class="btn btn-outline btn-lg">
                        <span>🔍</span> TRACK MY REPORT
                    </a>
                </div>

            </div>

            <div class="hero-right">
                <div class="hero-image-card">
                    <img src="assets/images/problem_type.jpg" alt="WRRS Water Conservation and Community Supply" class="hero-land-img">
                    <div class="hero-image-overlay-badge">
                        <span class="pulse-dot"></span>
                        <span>Active Community Monitoring</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Common Problems We Address Grid -->
        <section class="home-problems-section">
            <div class="section-heading-center">
                <h2>Common Water Problems Handled by WRRS</h2>
                <p>Select your issue and submit a report with accurate location for immediate dispatch.</p>
            </div>

            <div class="problems-grid">
                <div class="problem-card">
                    <div class="problem-icon">🌊</div>
                    <h3>Pipe Leakage & Bursts</h3>
                    <p>High-pressure main line ruptures and visible street leaks losing precious potable water.</p>
                    <a href="report.php" class="card-action-link">Report Leak &rarr;</a>
                </div>

                <div class="problem-card">
                    <div class="problem-icon">🧪</div>
                    <h3>Water Contamination</h3>
                    <p>Discolored, foul-smelling, or sedimented supply threatening public sanitation.</p>
                    <a href="report.php" class="card-action-link">Report Contamination &rarr;</a>
                </div>

                <div class="problem-card">
                    <div class="problem-icon">🚫</div>
                    <h3>Total Supply Outage</h3>
                    <p>Sudden loss of water flow affecting homes, schools, hospitals, and businesses.</p>
                    <a href="report.php" class="card-action-link">Report Outage &rarr;</a>
                </div>

                <div class="problem-card">
                    <div class="problem-icon">📉</div>
                    <h3>Low Water Pressure</h3>
                    <p>Insufficient line pressure caused by hidden leaks, airlocks, or valve malfunctions.</p>
                    <a href="report.php" class="card-action-link">Report Pressure Issue &rarr;</a>
                </div>
            </div>
        </section>

    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
<script src="assets/js/wrrs.js"></script>