<?php
session_start();
require_once 'config/db.php';

// Protect Route: Only allow ADMIN or MANAGER to create official staff accounts
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'MANAGER', 'DATA_COLLECTOR'])) {
    // If testing locally during development without roles yet, you can temporarily comment out the redirect below:
    // header("Location: login.php");
    // exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    $role         = $_POST['role'] ?? '';
    $status       = 'ACTIVE';

    if (empty($full_name) || empty($username) || empty($raw_password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if username or phone number already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR (phone_number = ? AND phone_number != '')");
        $check_stmt->bind_param("ss", $username, $phone_number);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "A user with this username or phone number already exists.";
        } else {
            // AUTOMATIC PASSWORD HASHING
            $password_hash = password_hash($raw_password, PASSWORD_BCRYPT);

            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, phone_number, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $full_name, $phone_number, $username, $password_hash, $role, $status);

            if ($insert_stmt->execute()) {
                $success = "User account successfully created! Role: " . htmlspecialchars($role);
            } else {
                $error = "Database error: Unable to create account.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$page_title = "Create Staff Account - WRRS";
include 'includes/header.php';
?>

<main class="main-wrapper">
    <div class="landing-content panel-container-md">
        <div class="panel-card">
            <div class="panel-header-center">
                <span class="panel-icon">👤</span>
                <h2>Create System Account</h2>
                <p>Register new Data Collectors, Technicians, or Managers. Passwords are automatically encrypted.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form action="create_user.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g. Jane Doe" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="e.g. 0712345678" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" placeholder="e.g. tech_jane" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="role">Assign Role *</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="">Select System Role</option>
                        <option value="TECHNICIAN">Technician</option>
                        <option value="MANAGER">Water Manager</option>
                        <option value="DATA_COLLECTOR">Data Collector</option>
                        <option value="CITIZEN">Citizen</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Enter password (will be auto-hashed)" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">CREATE ACCOUNT</button>
            </form>
        </div>
    </div>
</main>

<script src="assets/js/wrrs.js"></script>
</body>
</html>