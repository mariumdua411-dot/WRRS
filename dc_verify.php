<?php
$page_title = "Verify & Assign Report - WRRS";
$is_dashboard = true; 

require_once 'includes/header.php';
require_once 'config/db.php';

// Session Validation
if (!isset($_SESSION['user_id']) || (strtoupper($_SESSION['role']) !== 'DATA_COLLECTOR' && strtoupper($_SESSION['role']) !== 'MANAGER')) {
    header("Location: login.php");
    exit();
}

$error = '';
$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Fallback to first unassigned report if no ID in URL
if (!$report_id) {
    $first_unassigned = $conn->query("SELECT report_id AS id FROM reports WHERE status = 'SUBMITTED' ORDER BY created_at DESC LIMIT 1");
    if ($first_unassigned && $first_unassigned->num_rows > 0) {
        $report_id = $first_unassigned->fetch_assoc()['id'];
    } else {
        header("Location: dc_dashboard.php");
        exit();
    }
}

// Fetch Selected Report Details
$stmt = $conn->prepare("
    SELECT report_id AS id, reference_number, citizen_id, problem_type, location, description, status 
    FROM reports 
    WHERE report_id = ?
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    header("Location: dc_dashboard.php");
    exit();
}

// Fetch Active Technicians
$tech_query = $conn->query("
    SELECT user_id AS id, full_name, username 
    FROM users 
    WHERE role = 'TECHNICIAN' AND status = 'ACTIVE' 
    ORDER BY full_name ASC
");

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $technician_id = filter_input(INPUT_POST, 'technician_id', FILTER_VALIDATE_INT);
    $priority      = strtoupper(trim($_POST['priority'] ?? ''));

    if (!$technician_id || empty($priority)) {
        $error = "Please select both a technician and a priority level.";
    } else {
        $collector_id = $_SESSION['user_id'];

        $update_stmt = $conn->prepare("
            UPDATE reports 
            SET assigned_technician_id = ?, data_collector_id = ?, priority = ?, status = 'ASSIGNED', updated_at = NOW() 
            WHERE report_id = ?
        ");
        $update_stmt->bind_param("iisi", $technician_id, $collector_id, $priority, $report_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();
            
            // Log to report history updates table if available
            $history_stmt = $conn->prepare("
                INSERT INTO report_updates (report_id, status, notes, updated_by_user_id) 
                VALUES (?, 'ASSIGNED', 'Assigned to technician by Data Collector', ?)
            ");
            if ($history_stmt) {
                $history_stmt->bind_param("ii", $report_id, $collector_id);
                $history_stmt->execute();
                $history_stmt->close();
            }

            header("Location: dc_dashboard.php?status=assigned");
            exit();
        } else {
            $error = "Failed to assign technician. Please try again.";
            $update_stmt->close();
        }
    }
}
?>

<script>
    document.body.classList.add('app-layout-body', 'dc-theme');
</script>

    <!-- Main Content Section -->
    <main class="app-main-content">
        <div class="app-container container-narrow">
            
            <div class="panel-header">
                <h2>Verify & Assign to Technician</h2>
            </div>

            <!-- Maintenance & Dispatch Visual Guide -->
            <div class="panel-card dashboard-guide-card" style="margin-bottom: 20px; display: flex; gap: 16px; align-items: center;">
                <img src="assets/images/mantainance.jpg" alt="Preventative Maintenance" class="guide-banner-thumb">
                <div>
                    <h4 style="color: #0f172a; margin-bottom: 4px;">Infrastructure Maintenance Verification</h4>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Confirm reported location and assign a certified specialist with the appropriate priority urgency.</p>
                </div>
            </div>

            <div class="panel-card">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="dc_verify.php?id=<?php echo $report_id; ?>" method="POST">
                    
                    <!-- Report Details Summary -->
                    <div class="form-group">
                        <label>
                            Ref: <?php echo htmlspecialchars($report['reference_no'] ?? ''); ?> | 
                            Citizen ID: <?php echo htmlspecialchars($report['citizen_id'] ?? 'N/A'); ?>
                        </label>
                        <input 
                            type="text" 
                            class="readonly-input" 
                            readonly 
                            value='<?php echo htmlspecialchars(($report['problem_type'] ?? '') . ' — ' . ($report['location'] ?? '') . ' — "' . ($report['description'] ?? '') . '"'); ?>'
                        >
                    </div>

                    <!-- Required Technician Dropdown -->
                    <div class="form-group">
                        <label for="technician_id">Assign to Technician *</label>
                        <select name="technician_id" id="technician_id" required>
                            <option value="">Select Field Technician</option>
                            <?php if ($tech_query && $tech_query->num_rows > 0): ?>
                                <?php while ($tech = $tech_query->fetch_assoc()): ?>
                                    <option value="<?php echo $tech['id']; ?>">
                                        <?php echo htmlspecialchars($tech['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Priority Level Selection -->
                    <div class="form-group">
                        <label for="priority">Priority Level *</label>
                        <select name="priority" id="priority" required>
                            <option value="">Select Priority Level</option>
                            <option value="LOW">Low</option>
                            <option value="MEDIUM">Medium</option>
                            <option value="HIGH">High</option>
                            <option value="URGENT">Urgent</option>
                        </select>
                    </div>

                    <div class="form-actions-row">
                        <button type="submit" class="btn btn-emerald">
                            ASSIGN TO TECHNICIAN
                        </button>
                        <a href="dc_dashboard.php" class="btn-secondary-cancel">
                            Cancel & Back to Dashboard
                        </a>
                    </div>
                </form>

            </div>

        </div>
    </main>

</div>

<script src="assets/js/wrrs.js"></script>
</body>
</html>