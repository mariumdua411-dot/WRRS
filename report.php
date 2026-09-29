<?php
session_start();
require_once 'config/db.php';

$error = '';
$success_ref = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$problem_type = trim($_POST['problem_type'] ?? '');
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$citizen_id = $_SESSION['user_id'] ?? null;

if (empty($problem_type) || empty($location)) {
$error = "Please fill in all required fields marked with *.";
} else {
$reference_number = 'WRRS-' . date('Y') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 1, 6));
$status = 'Submitted';
$image_url = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
$target_dir = "assets/uploads/";
if (!file_exists($target_dir)) {
mkdir($target_dir, 0777, true);
}
$file_name = time() . '_' . basename($_FILES["image"]["name"]);
$target_file = $target_dir . $file_name;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
$image_url = $target_file;
}
}

$stmt = $conn->prepare("INSERT INTO reports (reference_number, citizen_id, problem_type, location, description, status, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sisssss", $reference_number, $citizen_id, $problem_type, $location, $description, $status, $image_url);

if ($stmt->execute()) {
$success_ref = $reference_number;
} else {
$error = "Failed to submit report. Please try again.";
}
$stmt->close();
}
}

$page_title = "Report Problem - WRRS";
include 'includes/header.php';
?>

<main class="main-wrapper">
<div class="landing-content panel-container-md">
<div class="panel-card">
<div class="panel-header-center">
<span class="panel-icon">📝</span>
<h2>Report a Water Problem</h2>
<p>Submit details regarding leaks, pipe bursts, contamination, or water supply interruptions.</p>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!empty($success_ref)): ?>
<div class="alert alert-success">
<h3>Report Submitted successfully!</h3>
<p class="alert-success-text">Your Tracking Reference Number is:</p>
<div class="reference-display-box">
<?php echo htmlspecialchars($success_ref); ?>
</div>
<p class="alert-subtext">Save this reference number to track the status of your report.</p>
<div class="btn-row-center">
<a href="track.php?ref=<?php echo urlencode($success_ref); ?>" class="btn btn-primary btn-sm">Track Progress</a>
<a href="report.php" class="btn btn-outline btn-sm">Submit Another Report</a>
</div>
</div>
<?php else: ?>

<div class="report-visual-guide" style="display: flex; gap: 14px; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
<img src="assets/images/leak.jpg" alt="Report Pipe Leaks & Contamination" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">
<div style="font-size: 0.85rem; color: #166534;">
<strong style="display: block; font-size: 0.92rem; margin-bottom: 2px;">Prompt Reporting Saves Clean Water</strong>
Attaching photos of bursts, leaks, or contaminated water expedites technician assignment and emergency isolation.
</div>
</div>

<form action="report.php" method="POST" enctype="multipart/form-data" class="auth-form">
<div class="form-group">
<label for="problem_type">Problem Type *</label>
<select name="problem_type" id="problem_type" class="form-control" required>
<option value="">Select Problem Type</option>
<option value="Pipe Leakage / Burst">Pipe Leakage / Burst</option>
<option value="Water Contamination">Water Contamination</option>
<option value="No Water Supply">No Water Supply</option>
<option value="Low Pressure">Low Water Pressure</option>
<option value="Illegal Connection">Illegal Connection</option>
<option value="Other">Other Issue</option>
</select>
</div>

<div class="form-group">
<label for="location">Location / Address *</label>
<input type="text" id="location" name="location" class="form-control" placeholder="Street Name, Area, or Landmark" required value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
</div>

<div class="form-group">
<label for="description">Detailed Description</label>
<textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe the severity or any additional details..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
</div>

<div class="form-group">
<label for="image">Upload Image (Optional)</label>
<input type="file" id="image" name="image" class="form-control" accept="image/*">
</div>

<button type="submit" class="btn btn-primary btn-block">SUBMIT REPORT</button>
</form>

<?php endif; ?>
</div>
</div>
</main>

<script src="assets/js/wrrs.js"></script>
</body>
</html>