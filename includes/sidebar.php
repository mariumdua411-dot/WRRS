<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = strtoupper($_SESSION['role'] ?? '');
$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff Member';
$username  = $_SESSION['username'] ?? 'user';

// Ensure latest profile photo
$profile_photo = $_SESSION['profile_photo'] ?? null;
if (empty($profile_photo) && isset($conn) && $user_id) {
    $p_stmt = $conn->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
    if ($p_stmt) {
        $p_stmt->bind_param("i", $user_id);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        if ($p_row = $p_res->fetch_assoc()) {
            $profile_photo = $p_row['profile_photo'];
            $_SESSION['profile_photo'] = $profile_photo;
        }
        $p_stmt->close();
    }
}

$current_script = basename($_SERVER['PHP_SELF']);
$current_query  = $_SERVER['QUERY_STRING'] ?? '';
?>

<!-- Top Control Bar (Replaces Full-Height Dark Sidebar) -->
<header class="top-navbar">
    <div class="top-nav-container">
        
        <!-- Left Section: Hamburger Toggle & Portal Branding -->
        <div class="top-nav-left">
            <button type="button" id="sidebarToggle" class="sidebar-hamburger" aria-label="Toggle Navigation Menu" aria-expanded="false">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
            <span class="portal-brand">
                <span class="brand-dot">💧</span> WRRS PORTAL
            </span>
        </div>

        <!-- Right Section: Staff Profile Info -->
        <div class="top-nav-right">
            <div class="profile-info-text">
                <span class="profile-name" title="<?php echo htmlspecialchars($full_name); ?>">
                    <?php echo htmlspecialchars($full_name); ?>
                </span>
                <span class="role-badge">
                    <?php echo htmlspecialchars(str_replace('_', ' ', $user_role)); ?>
                </span>
            </div>
            
            <div class="profile-avatar-wrap">
                <a href="profile.php" title="Update Profile & Photo">
                    <?php if (!empty($profile_photo) && file_exists(__DIR__ . '/../' . $profile_photo)): ?>
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" class="profile-avatar-img">
                    <?php else: ?>
                        <div class="profile-avatar-initial">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>

    </div>

    <!-- Hidden Dropdown Navigation Menu (Toggled via Hamburger Button) -->
    <nav class="collapsible-dropdown-menu" id="sidebarNav">
        <div class="menu-section-label">NAVIGATION</div>

        <?php if ($user_role === 'MANAGER'): ?>
            <a href="manager_dashboard.php" class="menu-item <?php echo ($current_script === 'manager_dashboard.php') ? 'active' : ''; ?>">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Dashboard Overview</span>
            </a>
            <a href="manage_users.php" class="menu-item <?php echo ($current_script === 'manage_users.php') ? 'active' : ''; ?>">
                <span class="menu-icon">👥</span>
                <span class="menu-text">Manage Users & Staff</span>
            </a>

        <?php elseif ($user_role === 'DATA_COLLECTOR'): ?>
            <a href="dc_dashboard.php" class="menu-item <?php echo ($current_script === 'dc_dashboard.php') ? 'active' : ''; ?>">
                <span class="menu-icon">📋</span>
                <span class="menu-text">Incoming Reports</span>
            </a>
            <a href="dc_verify.php" class="menu-item <?php echo ($current_script === 'dc_verify.php') ? 'active' : ''; ?>">
                <span class="menu-icon">⚡</span>
                <span class="menu-text">Verify & Assign</span>
            </a>

        <?php elseif ($user_role === 'TECHNICIAN'): ?>
            <a href="tech_dashboard.php" class="menu-item <?php echo ($current_script === 'tech_dashboard.php' && $current_query !== 'filter=completed') ? 'active' : ''; ?>">
                <span class="menu-icon">🔧</span>
                <span class="menu-text">Active Assigned Jobs</span>
            </a>
            <a href="tech_dashboard.php?filter=completed" class="menu-item <?php echo ($current_script === 'tech_dashboard.php' && $current_query === 'filter=completed') ? 'active' : ''; ?>">
                <span class="menu-icon">✅</span>
                <span class="menu-text">Completed Tasks</span>
            </a>
        <?php endif; ?>

        <div class="menu-section-label" style="margin-top: 12px;">ACCOUNT</div>
        
        <a href="profile.php" class="menu-item <?php echo ($current_script === 'profile.php') ? 'active' : ''; ?>">
            <span class="menu-icon">⚙️</span>
            <span class="menu-text">Profile & Password</span>
        </a>

        <a href="index.php" class="menu-item" target="_blank">
            <span class="menu-icon">🌐</span>
            <span class="menu-text">Public Website</span>
        </a>

        <a href="logout.php" class="menu-item logout-link">
            <span class="menu-icon">🚪</span>
            <span class="menu-text">Sign Out</span>
        </a>
    </nav>
</header>