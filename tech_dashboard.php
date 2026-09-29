<?php
$page_title = "Technician Dashboard - WRRS";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

// Access Control Guard
if (!isset($_SESSION['user_id']) || (strtoupper($_SESSION['role']) !== 'TECHNICIAN' && strtoupper($_SESSION['role']) !== 'MANAGER')) {
    header("Location: login.php");
    exit();
}

$tech_id = (int)$_SESSION['user_id'];

// Filter Handling (Active Jobs vs Completed)
$filter = trim($_GET['filter'] ?? 'active');

if ($filter === 'completed') {
    $where_clause = "WHERE assigned_technician_id = ? AND status IN ('RESOLVED', 'COMPLETED')";
} else {
    $where_clause = "WHERE assigned_technician_id = ? AND status IN ('ASSIGNED', 'IN_PROGRESS')";
}

// Fetch Dynamic Counts for KPI Cards
$counts_query = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned_cnt,
        SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as progress_cnt,
        SUM(CASE WHEN status IN ('RESOLVED', 'COMPLETED') THEN 1 ELSE 0 END) as completed_cnt
    FROM reports 
    WHERE assigned_technician_id = ?
");
$counts_query->bind_param("i", $tech_id);
$counts_query->execute();
$counts = $counts_query->get_result()->fetch_assoc();
$counts_query->close();

$assigned_cnt  = (int)($counts['assigned_cnt'] ?? 0);
$progress_cnt  = (int)($counts['progress_cnt'] ?? 0);
$completed_cnt = (int)($counts['completed_cnt'] ?? 0);

// Fetch Jobs Assigned to Logged-In Technician
$jobs_stmt = $conn->prepare("
    SELECT report_id, reference_number, problem_type, location, priority, status 
    FROM reports 
    $where_clause 
    ORDER BY created_at DESC
");
$jobs_stmt->bind_param("i", $tech_id);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();
?>

<script>
    document.body.classList.add('app-layout-body', 'tech-theme');
</script>

<div class="app-layout-wrapper">

    <!-- Main Content Workspace -->
    <main class="app-main-content">
        <div class="app-container">
            
            <!-- Technician Welcome & Field Specialist Showcase Card -->
            <div class="panel-card dashboard-guide-card" style="margin-bottom: 24px; display: flex; gap: 20px; align-items: center;">
                <img src="assets/images/mantainance.jpg" alt="WRRS Certified Technician" class="guide-banner-thumb" style="width: 85px; height: 85px; object-fit: cover; border-radius: 8px;">
                <div>
                    <span class="badge badge-emerald">FIELD OPERATIONS WORKSPACE</span>
                    <h3 style="color: #0f172a; margin: 4px 0 6px 0;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></h3>
                    <p style="color: #9cb8df; font-size: 0.88rem; margin: 0;">Review your active repair orders below. Once repairs are completed on site, submit photographic proof to verify resolution.</p>
                </div>
            </div>

            <div class="panel-header">
                <h2>MY ASSIGNED JOBS</h2>
            </div>

            <!-- KPI Metrics Cards -->
            <div class="metrics-row">
                <div class="metric-card">
                    <span class="metric-number blue"><?php echo $assigned_cnt; ?></span>
                    <span class="metric-label">ASSIGNED</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number amber"><?php echo $progress_cnt; ?></span>
                    <span class="metric-label">IN PROGRESS</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number emerald"><?php echo $completed_cnt; ?></span>
                    <span class="metric-label">COMPLETED / RESOLVED</span>
                </div>
            </div>

            <!-- Assigned Jobs Data Table -->
            <section class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Problem</th>
                            <th>Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($jobs_result && $jobs_result->num_rows > 0): ?>
                            <?php while ($job = $jobs_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="table-ref-col"><?php echo htmlspecialchars($job['reference_number'] ?? '#' . $job['report_id']); ?></td>
                                    <td><?php echo htmlspecialchars($job['problem_type']); ?></td>
                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td>
                                        <?php 
                                            $p = strtoupper($job['priority'] ?? 'MEDIUM');
                                            $p_class = ($p === 'HIGH' || $p === 'URGENT') ? 'badge-priority-high' : (($p === 'LOW') ? 'badge-priority-low' : 'badge-priority-medium');
                                        ?>
                                        <span class="badge <?php echo $p_class; ?>"><?php echo htmlspecialchars($p); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = strtoupper($job['status']);
                                            $st_class = ($st === 'RESOLVED' || $st === 'COMPLETED') ? 'badge-resolved' : (($st === 'IN_PROGRESS') ? 'badge-in-progress' : 'badge-assigned');
                                        ?>
                                        <span class="badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($st); ?></span>
                                    </td>
                                    <td>
                                        <div class="table-action-cell">
                                            <?php if ($st === 'RESOLVED' || $st === 'COMPLETED'): ?>
                                                <a href="tech_update.php?report_id=<?php echo $job['report_id']; ?>" class="btn btn-secondary btn-sm-action">
                                                    View Details / Proof
                                                </a>
                                            <?php else: ?>
                                                <a href="tech_update.php?report_id=<?php echo $job['report_id']; ?>" class="btn btn-emerald btn-sm-action">
                                                    Update / Upload Proof
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="table-empty-msg">No jobs found in this view.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </div>
    </main>

</div>

<script src="assets/js/wrrs.js"></script>
</body>
</html>