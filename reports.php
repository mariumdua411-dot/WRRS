<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ACCESS CONTROL: Allow Staff roles to access reports
$allowed_roles = ['ADMIN', 'MANAGER', 'DATA_COLLECTOR', 'TECHNICIAN'];
if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role'] ?? ''), $allowed_roles)) {
    header("Location: login.php");
    exit();
}

$page_title = "All Reports - WRRS";
$is_dashboard = true;

require_once 'config/db.php';
require_once 'includes/header.php';

// 2. FETCH REPORTS DATA FROM DATABASE
$query = "
    SELECT 
        r.report_id, 
        r.reference_number, 
        r.problem_type, 
        r.status, 
        r.priority, 
        r.created_at, 
        r.location,
        u.full_name AS reported_by
    FROM reports r
    LEFT JOIN users u ON r.citizen_id = u.user_id
    ORDER BY r.created_at DESC
";
$result = $conn->query($query);
?>

<script>
    document.body.classList.add('app-layout-body', 'admin-theme');
</script>

<div class="app-layout-wrapper">
    <main class="app-main-content">
        <div class="app-container" style="padding: 24px; max-width: 1200px; margin: 0 auto;">

            <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">ALL REPORTS</h2>
                    <p style="color: #64748b; font-size: 0.88rem; margin: 4px 0 0 0;">Manage and review system submitted issues</p>
                </div>
                <a href="admin.php" class="btn btn-outline-primary btn-sm">← Back to Dashboard</a>
            </div>

            <!-- REPORTS TABLE CARD -->
            <div class="panel-card" style="background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 12px 16px;">Tracking Code</th>
                                <th style="padding: 12px 16px;">Issue Type</th>
                                <th style="padding: 12px 16px;">Location</th>
                                <th style="padding: 12px 16px;">Priority</th>
                                <th style="padding: 12px 16px;">Status</th>
                                <th style="padding: 12px 16px;">Date Submitted</th>
                                <th style="padding: 12px 16px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                        <td style="padding: 14px 16px; font-weight: 700; color: #0f172a;">
                                            #<?php echo htmlspecialchars($row['tracking_code'] ?? $row['report_id']); ?>
                                        </td>
                                        <td style="padding: 14px 16px; color: #334155;">
                                            <?php echo htmlspecialchars($row['issue_type'] ?? 'General Issue'); ?>
                                        </td>
                                        <td style="padding: 14px 16px; color: #64748b;">
                                            <?php echo htmlspecialchars($row['location_address'] ?? 'N/A'); ?>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <?php 
                                                $prio = strtoupper($row['priority'] ?? 'MEDIUM');
                                                $prio_color = ($prio === 'URGENT' || $prio === 'HIGH') ? '#dc2626' : '#2563eb';
                                                $prio_bg = ($prio === 'URGENT' || $prio === 'HIGH') ? '#fee2e2' : '#dbeafe';
                                            ?>
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; background: <?php echo $prio_bg; ?>; color: <?php echo $prio_color; ?>;">
                                                <?php echo htmlspecialchars($prio); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <?php 
                                                $status = strtoupper($row['status'] ?? 'SUBMITTED');
                                                $status_color = '#d97706';
                                                $status_bg = '#fef3c7';

                                                if (in_array($status, ['RESOLVED', 'COMPLETED'])) {
                                                    $status_color = '#059669';
                                                    $status_bg = '#dcfce7';
                                                } elseif ($status === 'ASSIGNED' || $status === 'IN_PROGRESS') {
                                                    $status_color = '#2563eb';
                                                    $status_bg = '#dbeafe';
                                                }
                                            ?>
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px; color: #64748b; font-size: 0.85rem;">
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right;">
                                            <a href="view_report.php?id=<?php echo $row['report_id']; ?>" class="btn btn-sm btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: #94a3b8;">
                                        No reports found in the database.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </main>
</div>

</body>
</html>