<?php
session_start();
require_once 'config/db.php';

// Redirect logged-in users away from registration
if (isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or phone already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR (phone_number = ? AND phone_number != '')");
        $check_stmt->bind_param("ss", $username, $phone_number);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Username or phone number is already registered.";
        } else {
            // Hash password & insert citizen
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $role = 'CITIZEN';
            $status = 'ACTIVE';

            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, phone_number, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $full_name, $phone_number, $username, $password_hash, $role, $status);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$page_title = "Citizen Registration - WRRS";
include 'includes/header.php';
?>

<main class="auth-bg">
    <div class="auth-container" style="max-width: 480px;">
        <div class="auth-card">
            <div class="brand-logo">💧</div>
            <h2>Citizen Registration</h2>
            <p class="role-subtitle">Create an account to track your submitted water reports and receive updates.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert" style="background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; text-align: left;">
                    <?php echo htmlspecialchars($success); ?> <a href="login.php" style="color: #15803d; font-weight: 700;">Login Here</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="John Doe" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="Optional" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="username">Username or Email *</label>
                    <input type="text" id="username" name="username" placeholder="Enter username or email" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">REGISTER ACCOUNT</button>
            </form>
            
            <p class="helper-text">
                Already have an account? <a href="login.php">Login Here</a>
            </p>
        </div>
    </div>
</main>

<script src="assets/js/wrrs.js"></script>
</body>
</html>