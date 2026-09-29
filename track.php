<?php
session_start();
require_once 'config/db.php';

$ref = trim($_GET['ref'] ?? '');
$report = null;
$error = '';

if (!empty($ref)) {
$stmt = $conn->prepare("
SELECT 
r.reference_number, 
r.problem_type, 
r.location, 
r.description, 
r.status, 
r.created_at,
u.full_name AS tech_name,
u.phone_number AS tech_phone,
u.specialization AS tech_specialty
FROM reports r
LEFT JOIN users u ON r.assigned_technician_id = u.user_id
WHERE r.reference_number = ?
");
$stmt->bind_param("s", $ref);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
$report = $result->fetch_assoc();
} else {
$error = "No report found matching reference number: " . htmlspecialchars($ref);
}
$stmt->close();
}

$page_title = "Track Report - WRRS";
include 'includes/header.php';
?>

<main class="main-wrapper">
<div class="landing-content panel-container-md">
<div class="panel-card">
<div class="panel-header-center">
<span class="panel-icon">🔍</span>
<h2>Track Report Status</h2>
<p>Enter your unique Reference Number below to check the current progress of your issue.</p>
</div>

<form action="track.php" method="GET" class="auth-form">
<div class="form-group">
<label for="ref">Reference Number *</label>
<div class="search-input-group">
<input type="text" id="ref" name="ref" placeholder="e.g. REF-8F3A21" required value="<?php echo htmlspecialchars($ref); ?>">
<button type="submit" class="btn btn-primary">SEARCH</button>
</div>
</div>
</form>

<?php if (!empty($error)): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($report): ?>
<?php
$status = $report['status'];
$badge_class = 'badge-unassigned';
if ($status === 'Assigned' || $status === 'In Progress') $badge_class = 'badge-assigned';
if ($status === 'Resolved') $badge_class = 'badge-resolved';
?>
<div class="tracking-card">
<div class="tracking-card-header">
<span class="tracking-card-ref"><?php echo htmlspecialchars($report['reference_number']); ?></span>
<span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
</div>

<div class="tracking-card-body">
<p><strong>Problem:</strong> <?php echo htmlspecialchars($report['problem_type']); ?></p>
<p><strong>Location:</strong> <?php echo htmlspecialchars($report['location']); ?></p>
<?php if (!empty($report['description'])): ?>
<p><strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?></p>
<?php endif; ?>
<p class="tracking-card-date">Submitted on: <?php echo date('M d, Y - h:i A', strtotime($report['created_at'])); ?></p>
</div>

<?php if (!empty($report['tech_name'])): ?>
<div class="tracking-card" style="border-left: 4px solid var(--primary-color); margin-top: 16px; background-color: #f8fafc; padding: 14px; border-radius: 6px;">
<div class="tracking-card-header" style="margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0;">
<strong style="color: #0284c7; font-size: 0.92rem;">Assigned Specialist Details</strong>
<span class="badge badge-assigned" style="font-size: 0.7rem;">Verified & Assigned</span>
</div>
<div class="tracking-card-body" style="font-size: 0.88rem; line-height: 1.6;">
<p style="margin-bottom: 4px;"><strong>Specialist:</strong> <?php echo htmlspecialchars($report['tech_name']); ?></p>

<?php if (!empty($report['tech_specialty'])): ?>
<p style="margin-bottom: 4px;"><strong>Specialization:</strong> <?php echo htmlspecialchars($report['tech_specialty']); ?></p>
<?php endif; ?>

<?php if (!empty($report['tech_phone'])): ?>
<p style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
<strong>Direct Contact:</strong> 
<a href="tel:<?php echo htmlspecialchars($report['tech_phone']); ?>" class="btn btn-sm btn-emerald" style="text-decoration: none; padding: 4px 10px; font-size: 0.8rem; background-color: #059669; color: #fff; border-radius: 4px;">
📞 Call <?php echo htmlspecialchars($report['tech_phone']); ?>
</a>
</p>
<?php endif; ?>

<p style="font-size: 0.78rem; color: #64748b; margin-top: 8px;">
* You may contact this specialist directly for updates regarding any delayed resolution.
</p>
</div>
</div>
<?php else: ?>
<div style="margin-top: 14px; padding: 10px 12px; background: #f1f5f9; border-radius: 6px; font-size: 0.82rem; color: #64748b;">
ℹ️ <em>Report pending data collector verification. Assigned specialist contact details will appear here once dispatched.</em>
</div>
<?php endif; ?>

<?php if (strtoupper($status) === 'RESOLVED'): ?>
<div class="tracking-resolved-proof" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; align-items: center;">
<img src="assets/images/fixed.jpg" alt="Verified Resolution Proof" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 2px solid #22c55e;">
<div>
<strong style="color: #15803d; display: block;">Issue Successfully Resolved</strong>
<span style="font-size: 0.82rem; color: #64748b;">Technician repairs have been verified and sealed. Potable water flow has been restored.</span>
</div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</main>

<script src="assets/js/wrrs.js"></script>
</body>
</html>