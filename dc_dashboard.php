<?php
$page_title = "Data Collector Dashboard - WRRS";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || (strtoupper($_SESSION['role']) !== 'DATA_COLLECTOR' && strtoupper($_SESSION['role']) !== 'MANAGER')) {
header("Location: login.php");
exit();
}

$metrics_q = $conn->query("
SELECT 
COUNT(*) as total_received,
SUM(CASE WHEN status = 'SUBMITTED' THEN 1 ELSE 0 END) as submitted,
SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned,
SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved
FROM reports
");
$metrics = $metrics_q ? $metrics_q->fetch_assoc() : ['total_received' => 0, 'submitted' => 0, 'assigned' => 0, 'resolved' => 0];

$reports_query = $conn->query("
SELECT report_id, reference_number, problem_type, location, status, 
CASE 
WHEN image_url IS NOT NULL AND image_url != '' AND image_url NOT LIKE 'assets/uploads/%' 
THEN CONCAT('assets/uploads/', image_url)
ELSE image_url 
END AS photo_url, 
created_at 
FROM reports 
ORDER BY created_at DESC
");
?>

<script>
document.body.classList.add('app-layout-body', 'dc-theme');
</script>

<main class="app-main-content">
<div class="app-container">

<div class="panel-header">
<h2>REPORTS RECEIVED</h2>
</div>

<section class="metrics-container">
<div class="metric-col">
<span class="metric-val blue"><?php echo (int)($metrics['total_received'] ?? 0); ?></span>
<span class="metric-title">TOTAL RECEIVED</span>
</div>
<div class="metric-col">
<span class="metric-val orange"><?php echo (int)($metrics['submitted'] ?? 0); ?></span>
<span class="metric-title">SUBMITTED</span>
</div>
<div class="metric-col">
<span class="metric-val teal"><?php echo (int)($metrics['assigned'] ?? 0); ?></span>
<span class="metric-title">ASSIGNED</span>
</div>
<div class="metric-col">
<span class="metric-val green"><?php echo (int)($metrics['resolved'] ?? 0); ?></span>
<span class="metric-title">RESOLVED</span>
</div>
</section>

<section class="data-table-container">
<table class="data-table">
<thead>
<tr>
<th>Reference No</th>
<th>Problem</th>
<th>Location</th>
<th>Photo Fault</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($reports_query && $reports_query->num_rows > 0): ?>
<?php while ($row = $reports_query->fetch_assoc()): ?>
<tr>
<td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['reference_number']); ?></td>
<td><?php echo htmlspecialchars($row['problem_type']); ?></td>
<td><?php echo htmlspecialchars($row['location']); ?></td>

<td>
<?php if (!empty($row['photo_url']) && file_exists($row['photo_url'])): ?>
<button type="button" 
onclick="openImageModal('<?php echo htmlspecialchars($row['photo_url']); ?>', '<?php echo htmlspecialchars($row['reference_number']); ?>')"
class="btn" 
style="background: #3b82f6; color: #ffffff; padding: 4px 8px; font-size: 0.78rem; border-radius: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
<i class="fas fa-image"></i> View Photo
</button>
<?php else: ?>
<span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">No Photo</span>
<?php endif; ?>
</td>

<td>
<?php 
$st = strtoupper($row['status']);
$badge_class = 'badge-submitted';
if ($st === 'ASSIGNED') $badge_class = 'badge-assigned';
if ($st === 'RESOLVED') $badge_class = 'badge-resolved';
?>
<span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($st); ?></span>
</td>
<td>
<a href="dc_verify.php?id=<?php echo $row['report_id']; ?>" class="btn btn-emerald" style="padding: 4px 10px; font-size: 0.78rem;">
Verify & Assign
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No incoming citizen reports found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</section>

</div>
</main>

</div>

<div id="imageModal" style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); align-items: center; justify-content: center;">
<div style="background: #ffffff; padding: 20px; border-radius: 12px; max-width: 600px; width: 90%; position: relative; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
<span onclick="closeImageModal()" style="position: absolute; right: 16px; top: 12px; font-size: 1.5rem; font-weight: bold; cursor: pointer; color: #64748b;">&times;</span>
<h3 id="modalTitle" style="margin-top: 0; margin-bottom: 16px; font-size: 1.1rem; color: #0f172a;">Problem Photo Fault</h3>
<div style="max-height: 70vh; overflow: auto; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc;">
<img id="modalImage" src="" alt="Report Fault Image" style="width: 100%; height: auto; display: block;">
</div>
</div>
</div>

<script>
function openImageModal(imageSrc, refNum) {
document.getElementById('modalImage').src = imageSrc;
document.getElementById('modalTitle').innerText = 'Report Fault Image (' + refNum + ')';
document.getElementById('imageModal').style.display = 'flex';
}

function closeImageModal() {
document.getElementById('imageModal').style.display = 'none';
}

window.onclick = function(event) {
var modal = document.getElementById('imageModal');
if (event.target === modal) {
closeImageModal();
}
}
</script>

<script src="assets/js/wrrs.js"></script>
</body>
</html>