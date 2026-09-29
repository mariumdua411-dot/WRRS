<?php
$page_title = "Manage Users - WRRS Manager";
$is_dashboard = true;

require_once 'includes/header.php';
require_once 'config/db.php';

// Access Control: Manager Only
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// --- 1. HANDLE SINGLE USER CREATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $role      = strtoupper(trim($_POST['role'] ?? ''));
    $password  = trim($_POST['password'] ?? '');

    if (empty($full_name) || empty($username) || empty($role) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_res && $check_res->num_rows > 0) {
            $error = "Username '$username' already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("
                INSERT INTO users (full_name, username, password_hash, role, status, created_at) 
                VALUES (?, ?, ?, ?, 'ACTIVE', NOW())
            ");
            $insert_stmt->bind_param("ssss", $full_name, $username, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                $success = "Employee account created successfully!";
            } else {
                $error = "Failed to create user account.";
            }
            $insert_stmt->close();
        }
    }
}

// --- 2. HANDLE BATCH EXCEL / CSV UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_upload') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['excel_file']['tmp_name'];
        $file_name = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['csv', 'txt'])) {
            $handle = fopen($file_tmp, "r");
            $imported_count = 0;
            $row_index = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_index++;
                if ($row_index === 1 && (strtolower($data[0]) === 'full_name' || strtolower($data[0]) === 'name')) {
                    continue;
                }

                $full_name = trim($data[0] ?? '');
                $username  = trim($data[1] ?? '');
                $role      = strtoupper(trim($data[2] ?? ''));
                $raw_pass  = trim($data[3] ?? 'WRRS1234');

                if (!empty($full_name) && !empty($username) && !empty($role)) {
                    $role = str_replace(' ', '_', $role);

                    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
                    $check_stmt->bind_param("s", $username);
                    $check_stmt->execute();
                    $exists = $check_stmt->get_result()->num_rows > 0;
                    $check_stmt->close();

                    if (!$exists) {
                        $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
                        $ins = $conn->prepare("INSERT INTO users (full_name, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, 'ACTIVE', NOW())");
                        $ins->bind_param("ssss", $full_name, $username, $hashed_pass, $role);
                        if ($ins->execute()) {
                            $imported_count++;
                        }
                        $ins->close();
                    }
                }
            }
            fclose($handle);
            $success = "Successfully imported $imported_count employee account(s) from CSV.";
        } else {
            $error = "Please upload a valid CSV file (.csv).";
        }
    } else {
        $error = "File upload failed. Please try again.";
    }
}

// --- 3. HANDLE USER STATUS TOGGLE & DELETE ---
if (isset($_GET['user_action']) && isset($_GET['target_id'])) {
    $target_id = filter_input(INPUT_GET, 'target_id', FILTER_VALIDATE_INT);
    $user_action = $_GET['user_action'];

    if ($target_id && $target_id !== $_SESSION['user_id']) {
        if ($user_action === 'deactivate') {
            $stmt = $conn->prepare("UPDATE users SET status = 'INACTIVE' WHERE user_id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($user_action === 'activate') {
            $stmt = $conn->prepare("UPDATE users SET status = 'ACTIVE' WHERE user_id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($user_action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: manage_users.php");
        exit();
    }
}

// --- 4. PAGINATION LOGIC ---
$limit = 10; // Number of users per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total staff
$admin_id = $_SESSION['user_id'];
$users_query = $conn->query("
SELECT user_id, full_name, email, role, created_at 
FROM users 
WHERE user_id != '$admin_id'
ORDER BY created_at DESC
");

// Fetch Paginated Staff
$users_stmt = $conn->prepare("
    SELECT user_id, full_name, username, role, status, created_at 
    FROM users 
    WHERE role IN ('DATA_COLLECTOR', 'TECHNICIAN', 'MANAGER')
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$users_stmt->bind_param("ii", $limit, $offset);
$users_stmt->execute();
$users_query = $users_stmt->get_result();
?>

<script>
    document.body.classList.add('app-layout-body', 'manager-theme');
</script>

<div class="app-layout-wrapper">

<!-- Main Content Workspace -->
    <main class="app-main-content">
        <div class="app-container" style="padding-bottom: 40px;">
            
            <div class="panel-header">
                <h2>MANAGE STAFF</h2>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

                <!-- Form 1: Add Single User Card -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 16px; color: #1e293b; font-size: 1.1rem;">Add Single Employee</h3>
                    <form action="manage_users.php" method="POST">
                        <input type="hidden" name="action" value="create_user">

                        <div class="form-group">
                            <label for="full_name">Full Name & Designation *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Georgina Matogoro (Plumber)" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="e.g. g.matogoro" required>
                        </div>

                        <div class="form-group">
                            <label for="role">System Role *</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="CITIZEN">Citizen</option>
                                <option value="DATA_COLLECTOR">Data Collector</option>
                                <option value="TECHNICIAN">Technician</option>
                                <option value="MANAGER">Manager</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-actions-row" style="margin-top: 16px;">
                            <button type="submit" class="btn btn-emerald">
                                + Add User Account
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Form 2: Batch Upload Excel/CSV Card -->
                <div class="panel-card">
                    <h3 style="margin-bottom: 8px; color: #1e293b; font-size: 1.1rem;">Batch Upload Employees</h3>
                    <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 16px;">
                        Upload a CSV file: <strong>full_name, username, role, password</strong>
                    </p>

                    <form action="manage_users.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="batch_upload">

                        <div class="form-group">
                            <label for="excel_file">Select CSV File *</label>
                            <input type="file" id="excel_file" name="excel_file" accept=".csv, .txt" class="form-control" required>
                        </div>

                        <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px dashed #cbd5e1; font-size: 0.8rem; color: #475569; margin-bottom: 16px;">
                            <strong>CSV Format Example:</strong><br>
                            <code>Georgina Matogoro,g.matogoro,TECHNICIAN,Pass123!</code><br>
                            <code>Frank Joseph,f.joseph,DATA_COLLECTOR,Pass123!</code>
                        </div>

                        <div class="form-actions-row">
                            <button type="submit" class="btn btn-purple">
                                📤 Upload & Import Users
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Existing Users Data Table (Scrollable + Paginated) -->
            <section class="data-table-container">
                <div style="padding: 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: #1e293b; font-size: 1.1rem; margin: 0;">Existing Staff Accounts</h3>
                    <span style="color: #64748b; font-size: 0.85rem;">Total Staff: <?php echo $total_rows; ?></span>
                </div>

                <!-- Scrollable Table Wrapper -->
                <div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
                    <table class="data-table">
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users_query && $users_query->num_rows > 0): ?>
                                <?php while ($u = $users_query->fetch_assoc()): ?>
                                    <tr>
                                        <td class="table-ref-col">#<?php echo $u['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($u['role'] === 'TECHNICIAN') ? 'badge-assigned' : 'badge-in-progress'; ?>">
                                                <?php echo str_replace('_', ' ', $u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($u['status'] === 'ACTIVE') ? 'badge-resolved' : 'badge-priority-high'; ?>">
                                                <?php echo htmlspecialchars($u['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <?php if ($u['status'] === 'ACTIVE'): ?>
                                                    <a href="manage_users.php?user_action=deactivate&target_id=<?php echo $u['user_id']; ?>&page=<?php echo $page; ?>" 
                                                       class="btn btn-sm-action" style="background: #f59e0b; color: white;">Deactivate</a>
                                                <?php else: ?>
                                                    <a href="manage_users.php?user_action=activate&target_id=<?php echo $u['user_id']; ?>&page=<?php echo $page; ?>" 
                                                       class="btn btn-sm-action" style="background: #10b981; color: white;">Activate</a>
                                                <?php endif; ?>

                                                <a href="manage_users.php?user_action=delete&target_id=<?php echo $u['user_id']; ?>&page=<?php echo $page; ?>" 
                                                   onclick="return confirm('Are you sure you want to permanently delete this account?');"
                                                   class="btn btn-sm-action" style="background: #ef4444; color: white;">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="table-empty-msg">No staff user accounts found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Navigation Controls -->
                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <span style="font-size: 0.85rem; color: #64748b;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                        <div style="display: flex; gap: 6px;">
                            <?php if ($page > 1): ?>
                                <a href="manage_users.php?page=<?php echo $page - 1; ?>" class="btn btn-sm-action" style="background: #cbd5e1; color: #1e293b;">&laquo; Previous</a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="manage_users.php?page=<?php echo $page + 1; ?>" class="btn btn-sm-action" style="background: #2563eb; color: white;">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </section>

        </div>
    </main>

</div>

<script src="assets/js/wrrs.js"></script>
</body>
</html>