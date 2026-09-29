<?php
$page_title = "Admin Dashboard - WRRS";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

// Allow both ADMIN and MANAGER roles to access this dashboard
if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role']), ['ADMIN', 'MANAGER'])) {
    header("Location: login.php");
    exit();
}

/* REPORTS SUMMARY */
$rep = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status IN ('SUBMITTED','RECEIVED','UNASSIGNED') THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) AS assigned,
        SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status IN ('RESOLVED','COMPLETED') THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN priority = 'URGENT' AND status NOT IN ('RESOLVED','COMPLETED') THEN 1 ELSE 0 END) AS urgent_open,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
    FROM reports
")->fetch_assoc();

$total        = (int)($rep['total'] ?? 0);
$pending      = (int)($rep['pending'] ?? 0);
$assigned     = (int)($rep['assigned'] ?? 0);
$progress     = (int)($rep['in_progress'] ?? 0);
$resolved     = (int)($rep['resolved'] ?? 0);
$urgent       = (int)($rep['urgent_open'] ?? 0);
$today        = (int)($rep['today'] ?? 0);
$resolve_rate = $total > 0 ? round(($resolved / $total) * 100) : 0;

/* USERS SUMMARY (Includes ADMIN role) */
$usr = $conn->query("
    SELECT
        COUNT(*) AS total_users,
        SUM(CASE WHEN role='CITIZEN' THEN 1 ELSE 0 END) AS citizens,
        SUM(CASE WHEN role='DATA_COLLECTOR' THEN 1 ELSE 0 END) AS collectors,
        SUM(CASE WHEN role='TECHNICIAN' THEN 1 ELSE 0 END) AS technicians,
        SUM(CASE WHEN role='MANAGER' THEN 1 ELSE 0 END) AS managers,
        SUM(CASE WHEN role='ADMIN' THEN 1 ELSE 0 END) AS admins,
        SUM(CASE WHEN status='ACTIVE' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN status='INACTIVE' THEN 1 ELSE 0 END) AS inactive
    FROM users
")->fetch_assoc();

$total_users  = (int)($usr['total_users'] ?? 0);
$citizens     = (int)($usr['citizens'] ?? 0);
$collectors   = (int)($usr['collectors'] ?? 0);
$technicians  = (int)($usr['technicians'] ?? 0);
$managers     = (int)($usr['managers'] ?? 0);
$admins       = (int)($usr['admins'] ?? 0);
$active_users = (int)($usr['active'] ?? 0);
$inactive     = (int)($usr['inactive'] ?? 0);
?>

<script>
    document.body.classList.add('app-layout-body', 'admin-theme');
</script>

<div class="app-layout-wrapper">
    <main class="app-main-content">
        <div class="app-container">

            <div class="panel-header">
                <h2>SYSTEM OVERVIEW</h2>
                <span class="panel-header-date"><?php echo date('D, M d Y'); ?></span>
            </div>

            <!-- REPORTS -->
            <div class="section-header">
                <span class="section-icon">📊</span>
                <h3 class="section-title">Reports Summary</h3>
                <span class="section-divider"></span>
                <a href="reports.php" class="btn btn-primary btn-sm-action">View All →</a>
            </div>

            <div class="metrics-row">
                <div class="metric-card">
                    <span class="metric-number blue"><?php echo $total; ?></span>
                    <span class="metric-label">TOTAL REPORTS</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number amber"><?php echo $pending; ?></span>
                    <span class="metric-label">PENDING</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number blue"><?php echo $assigned + $progress; ?></span>
                    <span class="metric-label">IN HAND</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number emerald"><?php echo $resolved; ?></span>
                    <span class="metric-label">RESOLVED</span>
                </div>
                <div class="metric-card">
                    <span class="metric-number emerald"><?php echo $resolve_rate; ?>%</span>
                    <span class="metric-label">RESOLUTION RATE</span>
                </div>
            </div>

            <div class="panel-card">
                <div class="split-grid-2-1">
                    <div>
                        <div class="mini-title">Status Breakdown</div>
                        <?php
                            $statuses = [
                                ['Pending',     $pending,   '#f59e0b'],
                                ['Assigned',    $assigned,  '#3b82f6'],
                                ['In Progress', $progress,  '#d97706'],
                                ['Resolved',    $resolved,  '#059669'],
                            ];
                            foreach ($statuses as $s):
                                $pct = $total > 0 ? round(($s[1] / $total) * 100) : 0;
                        ?>
                            <div style="margin-bottom:10px;">
                                <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-weight:600; color:#64748b; margin-bottom:4px;">
                                    <span><?php echo $s[0]; ?></span>
                                    <span><?php echo $s[1]; ?> (<?php echo $pct; ?>%)</span>
                                </div>
                                <div style="background:#f1f5f9; height:7px; border-radius:4px; overflow:hidden;">
                                    <div style="width:<?php echo $pct; ?>%; background:<?php echo $s[2]; ?>; height:100%; border-radius:4px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="background:<?php echo $urgent > 0 ? '#fef2f2' : '#f0fdf4'; ?>; padding:14px 16px; border-radius:10px; border-left:4px solid <?php echo $urgent > 0 ? '#ef4444' : '#585bbd'; ?>;">
                            <div style="font-size:0.68rem; font-weight:700; color:<?php echo $urgent > 0 ? '#b91c1c' : '#4e56bf'; ?>; text-transform:uppercase; letter-spacing:0.05em;">
                                <?php echo $urgent > 0 ? '⚠ Urgent Open' : '✓ No Urgent Issues'; ?>
                            </div>
                            <div style="font-size:1.7rem; font-weight:800; color:<?php echo $urgent > 0 ? '#dc2626' : '#624ec7'; ?>; line-height:1.1; margin-top:2px;">
                                <?php echo $urgent; ?>
                            </div>
                        </div>
                        <div style="background:#eff6ff; padding:14px 16px; border-radius:10px; border-left:4px solid #3b82f6;">
                            <div style="font-size:0.68rem; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:0.05em;">
                                Received Today
                            </div>
                            <div style="font-size:1.7rem; font-weight:800; color:#2563eb; line-height:1.1; margin-top:2px;">
                                <?php echo $today; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USERS -->
            <div class="section-header">
                <span class="section-icon">👥</span>
                <h3 class="section-title">Users Summary</h3>
                <span class="section-divider"></span>
                <a href="manage_users.php" class="btn btn-purple btn-sm-action">Manage →</a>
            </div>

            <div class="two-col-grid">
                <div class="panel-card">
                    <h4 class="mini-title">Users by Role</h4>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php
                            $roles = [
                                ['Administrators',  $admins,      '🛡️', '#dc2626', '#fee2e2'],
                                ['Managers',        $managers,    '👑', '#8b5cf6', '#f3e8ff'],
                                ['Data Collectors', $collectors,  '📥', '#059669', '#dcfce7'],
                                ['Technicians',     $technicians, '🔧', '#0d9488', '#ccfbf1'],
                                ['Citizens',        $citizens,    '💧', '#0284c7', '#e0f2fe'],
                            ];
                            foreach ($roles as $r):
                        ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:<?php echo $r[4]; ?>; border-radius:10px;">
                                <span style="display:flex; align-items:center; gap:10px; font-weight:600; color:<?php echo $r[3]; ?>; font-size:0.88rem;">
                                    <span style="font-size:1.1rem;"><?php echo $r[2]; ?></span>
                                    <?php echo $r[0]; ?>
                                </span>
                                <span style="font-size:1.35rem; font-weight:800; color:<?php echo $r[3]; ?>;">
                                    <?php echo $r[1]; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel-card">
                    <h4 class="mini-title">Account Status</h4>
                    <div style="text-align:center; padding:16px; background:#f8fafc; border-radius:10px; margin-bottom:14px; border:1px dashed #cbd5e1;">
                        <div style="font-size:2.2rem; font-weight:800; color:#0f172a; line-height:1;">
                            <?php echo $total_users; ?>
                        </div>
                        <div style="font-size:0.68rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-top:6px;">
                            Total Registered
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div style="text-align:center; padding:14px; background:#dcfce7; border-radius:10px;">
                            <div style="font-size:1.5rem; font-weight:800; color:#059669; line-height:1;">
                                <?php echo $active_users; ?>
                            </div>
                            <div style="font-size:0.68rem; font-weight:700; color:#166534; text-transform:uppercase; margin-top:6px; letter-spacing:0.05em;">
                                Active
                            </div>
                        </div>
                        <div style="text-align:center; padding:14px; background:#fee2e2; border-radius:10px;">
                            <div style="font-size:1.5rem; font-weight:800; color:#dc2626; line-height:1;">
                                <?php echo $inactive; ?>
                            </div>
                            <div style="font-size:0.68rem; font-weight:700; color:#991b1b; text-transform:uppercase; margin-top:6px; letter-spacing:0.05em;">
                                Inactive
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>