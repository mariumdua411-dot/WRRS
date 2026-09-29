<?php
session_start();
$ref = trim($_GET['ref'] ?? '');

if (empty($ref)) {
    header("Location: index.php");
    exit();
}

$page_title = "Report Submitted - WRRS";
include 'includes/header.php';
?>

<main class="main-wrapper">
    <div class="landing-content panel-container-sm">
        <div class="panel-card panel-header-center">
            <div class="success-badge-large">🎉</div>
            <h2>Report Submitted!</h2>
            <p>Thank you for contributing to water resource management. Your report has been dispatched to our data collectors.</p>

            <div class="alert-success">
                <span class="reference-label-sm">Your Tracking Reference</span>
                <span class="reference-display-box"><?php echo htmlspecialchars($ref); ?></span>
            </div>

            <div style="margin: 16px 0; border-radius: 8px; overflow: hidden; max-height: 140px;">
                <img src="assets/images/fixed.jpg" alt="Water Infrastructure Repaired" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px;">
            </div>

            <div class="btn-row-center">
                <a href="track.php?ref=<?php echo urlencode($ref); ?>" class="btn btn-primary">Track Status</a>
                <a href="index.php" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/wrrs.js"></script>
</body>
</html>