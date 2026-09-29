<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_staff = isset($_SESSION['role']) && !empty($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'WRRS'; ?></title>
    <link rel="stylesheet" href="assets/css/wrrs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?php echo (isset($is_dashboard) && $is_dashboard) || $is_staff ? 'dashboard-body' : 'public-body'; ?>">

<?php if ($is_staff): ?>
    <!-- STAFF TOPBAR & HAMBURGER -->
    <header class="app-topbar">
        <div class="topbar-left">
            <button type="button" id="menuToggleBtn" class="menu-toggle-btn" onclick="toggleDashboardNav(event)">
                <i class="fas fa-bars"></i>
            </button>
            <span class="brand-title" style="margin-left: 12px; font-weight: 700; color: #0f172a;">WRRS System</span>
        </div>

        <div class="topbar-right">
            <?php if (isset($_SESSION['username'])): ?>
                <?php 
                    $user_display_name = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
                    $user_role = $_SESSION['role'] ?? 'USER';
                    $profile_photo = $_SESSION['profile_photo'] ?? null;
                ?>
                <a href="profile.php" class="user-profile-widget" title="My Profile & Settings">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($user_display_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                    </div>
                    
                    <!-- UPDATED AVATAR CIRCLE: RENDERS UPLOADED PHOTO OR INITIAL FALLBACK -->
                    <div class="avatar-circle" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($profile_photo) && file_exists($profile_photo)): ?>
                            <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_display_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </a>
                <a href="logout.php" class="btn-logout-icon" title="Sign Out">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
        </div>

        <!-- DROPDOWN MENU -->
        <nav id="dropdownNav" class="dropdown-menu-overlay">
            <div class="nav-section-label">NAVIGATION</div>
            <a href="index.php" class="nav-item-link"><i class="fas fa-home"></i> Home</a>
            
            <?php if (isset($_SESSION['role'])): ?>
                <?php $role = strtoupper($_SESSION['role']); ?>
                <?php if (isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'MANAGER'): ?>
             <a href="manage_staff.php" class="nav-item">Manage Staff</a>
              <?php endif; ?>

               <?php if (isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'ADMIN'): ?>
               <a href="manage_users.php" class="nav-item">Manage Users</a>
               <?php endif; ?>
                
                <?php if ($role === 'ADMIN'): ?>
                    <a href="admin.php" class="nav-item-link"><i class="fas fa-user-shield"></i> Admin Dashboard</a>
                    <a href="manage_users.php" class="nav-item-link"><i class="fas fa-users-cog"></i> Manage Users</a>
                    <a href="reports.php" class="nav-item-link"><i class="fas fa-file-alt"></i> View All Reports</a>

                <?php elseif ($role === 'MANAGER'): ?>
                    <a href="manager_dashboard.php" class="nav-item-link"><i class="fas fa-chart-pie"></i> Manager Dashboard</a>
                    <a href="manage_staff.php" class="nav-item-link"><i class="fas fa-users"></i> Manage Staff</a>
                    <a href="reports.php" class="nav-item-link"><i class="fas fa-file-alt"></i> View Reports</a>

                <?php elseif ($role === 'DATA_COLLECTOR'): ?>
                    <a href="dc_dashboard.php" class="nav-item-link"><i class="fas fa-inbox"></i> Incoming Reports</a>
                    <a href="dc_verify.php" class="nav-item-link"><i class="fas fa-tasks"></i> Verify & Assign</a>

                <?php elseif ($role === 'TECHNICIAN'): ?>
                    <a href="tech_dashboard.php" class="nav-item-link"><i class="fas fa-wrench"></i> Active Assigned Jobs</a>
                    <a href="tech_dashboard.php?filter=completed" class="nav-item-link"><i class="fas fa-check-square"></i> Completed Tasks</a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="nav-section-label" style="margin-top: 10px;">ACCOUNT</div>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="profile.php" class="nav-item-link"><i class="fas fa-user-cog"></i> Profile & Password</a>
                <a href="logout.php" class="nav-item-link text-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            <?php endif; ?>
        </nav>
    </header>

    <script>
    function toggleDashboardNav(event) {
        if (event) event.stopPropagation();
        var nav = document.getElementById('dropdownNav');
        if (nav) {
            nav.classList.toggle('show-nav');
        }
    }

    document.addEventListener('click', function(e) {
        var nav = document.getElementById('dropdownNav');
        var btn = document.getElementById('menuToggleBtn');
        if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
            nav.classList.remove('show-nav');
        }
    });
    </script>

<?php else: ?>
    <!-- PUBLIC HEADER -->
    <header class="public-navbar-header">
        <div class="public-nav-container">
            <a href="index.php" class="public-brand-logo">
                <i class="fas fa-tint"></i> WRRS PORTAL
            </a>

            <nav class="public-main-nav">
                <a href="index.php" class="public-nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="about.php" class="public-nav-link"><i class="fas fa-info-circle"></i> About Us</a>
                <a href="report.php" class="public-nav-link"><i class="fas fa-exclamation-triangle"></i> Report Problem</a>
                <a href="track.php" class="public-nav-link"><i class="fas fa-search-location"></i> Track Report</a>
            </nav>

            <div class="public-auth-actions">
                <a href="login.php" class="btn btn-outline-primary btn-sm">Login</a>
                <a href="register.php" class="btn btn-primary btn-sm">Register</a>
            </div>
        </div>
    </header>
<?php endif; ?>