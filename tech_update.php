<?php
$page_title = "Update Job Status - WRRS";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

// Access Control Guard: Technicians & Managers
if (!isset($_SESSION['user_id']) || (strtoupper($_SESSION['role']) !== 'TECHNICIAN' && strtoupper($_SESSION['role']) !== 'MANAGER')) {
    header("Location: login.php");
    exit();
}

$tech_id = (int)$_SESSION['user_id'];
$report_id = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);

if (!$report_id) {
    header("Location: tech_dashboard.php");
    exit();
}

$error = '';
$success = '';

// 1. Fetch Job Details from `reports` table
$stmt = $conn->prepare("
    SELECT r.* 
    FROM reports r 
    WHERE r.report_id = ? AND (r.assigned_technician_id = ? OR ? = 0)
");
$stmt->bind_param("iii", $report_id, $tech_id, $tech_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) {
    die("Job not found or access denied.");
}

// 2. Handle Work Proof & Status Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_job') {
    $new_status      = trim($_POST['status'] ?? '');
    $work_notes      = trim($_POST['work_notes'] ?? '');
    $previous_status = $job['status'];
    $proof_image_url = null;

    if (empty($new_status) || empty($work_notes)) {
        $error = "Please provide both an updated status and work notes.";
    } else {
        // Upload Proof Image if provided
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_filename    = 'proof_' . $report_id . '_' . time() . '.' . $file_ext;
                $target_file     = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $target_file)) {
                    $proof_image_url = $target_file;
                } else {
                    $error = "Failed to upload proof image.";
                }
            } else {
                $error = "Invalid file type. Please upload a JPG, PNG, or WEBP image.";
            }
        }

        if (empty($error)) {
            // Begin Transaction
            $conn->begin_transaction();

            try {
                // Update main report status
                $update_stmt = $conn->prepare("
                    UPDATE reports 
                    SET status = ?, 
                        updated_at = NOW()
                    WHERE report_id = ?
                ");
                $update_stmt->bind_param("si", $new_status, $report_id);
                $update_stmt->execute();
                $update_stmt->close();

                // Insert log into `report_work_logs` table
                $log_stmt = $conn->prepare("
                    INSERT INTO report_work_logs 
                        (report_id, technician_id, previous_status, new_status, work_notes, proof_image_url, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $log_stmt->bind_param("iissss", $report_id, $tech_id, $previous_status, $new_status, $work_notes, $proof_image_url);
                $log_stmt->execute();
                $log_stmt->close();

                // Commit Transaction
                $conn->commit();

                $success = "Work proof and status updated successfully!";
                $job['status'] = $new_status;

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to save update: " . $e->getMessage();
            }
        }
    }
}

// 3. Fetch Existing Work Logs for History View
$logs_stmt = $conn->prepare("
    SELECT * 
    FROM report_work_logs 
    WHERE report_id = ? 
    ORDER BY created_at DESC
");
$logs_stmt->bind_param("i", $report_id);
$logs_stmt->execute();
$work_logs = $logs_stmt->get_result();
$logs_stmt->close();
?>

<script>
    document.body.classList.add('app-layout-body', 'tech-theme');
</script>

<div class="app-layout-wrapper">

<!-- Main Content Workspace -->
    <main class="app-main-content">
        <div class="app-container">
            
            <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>UPDATE JOB — <?php echo htmlspecialchars($job['reference_number'] ?? '#' . $job['report_id']); ?></h2>
                <a href="tech_dashboard.php" class="btn btn-secondary btn-sm-action">&larr; Back to Dashboard</a>
            </div>

            <!-- Active Pipeline Repair Visual Guide -->
            <div class="panel-card dashboard-guide-card" style="margin-bottom: 20px; display: flex; gap: 16px; align-items: center;">
                <img src="assets/images/fixing.jpg" alt="Active Pipeline Repair" class="guide-banner-thumb">
                <div>
                    <h4 style="color: #8ea3d5; margin-bottom: 4px;">Active Repair Documentation</h4>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Log precise technical notes on replaced parts, pressure checks, and upload high-resolution photographic proof of resolution.</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Job Summary Card -->
            <div class="panel-card" style="margin-bottom: 24px; background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="color: #1e293b; font-size: 1rem; margin-bottom: 12px;">Job Details</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem; color: #334155;">
                    <div><strong>Problem Type:</strong> <?php echo htmlspecialchars($job['problem_type'] ?? 'N/A'); ?></div>
                    <div><strong>Location:</strong> <?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></div>
                    <div>
                        <strong>Priority:</strong> 
                        <?php 
                            $p = strtoupper($job['priority'] ?? 'MEDIUM');
                            $p_class = ($p === 'HIGH' || $p === 'URGENT') ? 'badge-priority-high' : (($p === 'LOW') ? 'badge-priority-low' : 'badge-priority-medium');
                        ?>
                        <span class="badge <?php echo $p_class; ?>"><?php echo htmlspecialchars($p); ?></span>
                    </div>
                    <div>
                        <strong>Current Status:</strong> 
                        <?php 
                            $st = strtoupper($job['status']);
                            $st_class = ($st === 'RESOLVED' || $st === 'COMPLETED') ? 'badge-resolved' : (($st === 'IN_PROGRESS') ? 'badge-in-progress' : 'badge-assigned');
                        ?>
                        <span class="badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($st); ?></span>
                    </div>
                </div>
            </div>

            <!-- Work Update Form -->
            <div class="panel-card" style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px; color: #1e293b; font-size: 1.1rem;">Submit Work Proof & Update Status</h3>

                <form action="tech_update.php?report_id=<?php echo $report_id; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_job">
                    <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="status" style="font-weight: 600; display: block; margin-bottom: 6px;">New Status *</label>
                        <select name="status" id="status" class="form-control" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;" required>
                            <option value="IN_PROGRESS" <?php echo ($job['status'] === 'IN_PROGRESS') ? 'selected' : ''; ?>>In Progress (Work ongoing)</option>
                            <option value="RESOLVED" <?php echo ($job['status'] === 'RESOLVED' || $job['status'] === 'COMPLETED') ? 'selected' : ''; ?>>Resolved / Completed</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="work_notes" style="font-weight: 600; display: block; margin-bottom: 6px;">Work Notes / Progress Details *</label>
                        <textarea 
                            name="work_notes" 
                            id="work_notes" 
                            rows="4" 
                            class="form-control" 
                            placeholder="Describe work completed or steps taken..." 
                            style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;" 
                            required
                        ></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="proof_image" style="font-weight: 600; display: block; margin-bottom: 6px;">Upload Proof Photo</label>
                        <input type="file" name="proof_image" id="proof_image" accept="image/*" class="form-control" style="width: 100%; padding: 8px;">
                    </div>

                    <button type="submit" class="btn btn-emerald btn-sm-action" style="padding: 10px 20px; font-size: 0.95rem;">
                        Save Update & Submit Proof
                    </button>
                </form>
            </div>

            <!-- Activity Logs / History Table -->
            <?php if ($work_logs && $work_logs->num_rows > 0): ?>
                <section class="data-table-container">
                    <div style="padding: 16px; border-bottom: 1px solid #e2e8f0;">
                        <h3 style="color: #1e293b; font-size: 1rem; margin: 0;">Work Log History</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Status Change</th>
                                <th>Work Notes</th>
                                <th>Proof Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $work_logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-assigned"><?php echo htmlspecialchars($log['previous_status'] ?? 'N/A'); ?></span>
                                        &rarr;
                                        <span class="badge badge-resolved"><?php echo htmlspecialchars($log['new_status']); ?></span>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($log['work_notes'])); ?></td>
                                    <td>
                                        <?php if (!empty($log['proof_image_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($log['proof_image_url']); ?>" target="_blank" style="color: #2563eb; font-weight: 600;">View Photo</a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">No photo attached</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

        </div>
    </main>

</div>

<script src="assets/js/wrrs.js"></script>
</body>
</html>