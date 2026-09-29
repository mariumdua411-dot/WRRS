<?php
$page_title = "View Report Details - WRRS";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
header("Location: login.php");
exit();
}

$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$report_id) {
header("Location: reports.php");
exit();
}

$stmt = $conn->prepare("
SELECT r.*, u.full_name AS reported_by 
FROM reports r 
LEFT JOIN users u ON r.citizen_id = u.user_id 
WHERE r.report_id = ?
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
echo "<div class='app-container error-container'><h3>Report not found.</h3><a href='javascript:history.back()'>Go Back</a></div>";
exit();
}
?>

<link rel="stylesheet" href="assets/css/style.css">

<div class="app-layout-wrapper">
<main class="app-main-content">
<div class="app-container">

<div class="btn-back-link">
<a href="javascript:history.back()" class="btn-back">&larr; Back to Reports</a>
</div>

<div class="report-card">
<div class="report-header">
<h2 class="report-title">Report Details</h2>
<span class="report-status-badge">
<?php echo htmlspecialchars($report['status'] ?? 'SUBMITTED'); ?>
</span>
</div>

<table class="report-details-table">
<tr>
<td class="report-label">Reference Number:</td>
<td class="report-val"><?php echo htmlspecialchars($report['reference_number']); ?></td>
</tr>
<tr>
<td class="report-label">Problem Type:</td>
<td class="report-val"><?php echo htmlspecialchars($report['problem_type'] ?? 'General Issue'); ?></td>
</tr>
<tr>
<td class="report-label">Location:</td>
<td class="report-val"><?php echo htmlspecialchars($report['location'] ?? 'N/A'); ?></td>
</tr>
<tr>
<td class="report-label">Priority:</td>
<td class="report-val"><?php echo htmlspecialchars($report['priority'] ?? 'MEDIUM'); ?></td>
</tr>
<tr>
<td class="report-label">Submitted By:</td>
<td class="report-val"><?php echo htmlspecialchars($report['reported_by'] ?? 'CITIZEN'); ?></td>
</tr>
<tr>
<td class="report-label">Date Submitted:</td>
<td class="report-val"><?php echo htmlspecialchars($report['created_at'] ?? 'N/A'); ?></td>
</tr>
</table>

<div class="report-description-section">
<h4 class="report-description-title">Description:</h4>
<p class="report-description-box">
<?php echo nl2br(htmlspecialchars($report['description'] ?? 'No description provided.')); ?>
</p>
</div>
</div>

</div>
</main>
</div>

<script src="assets/js/wrrs.js"></script>
</body>
</html>