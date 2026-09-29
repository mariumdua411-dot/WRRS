<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow Staff roles to access reports
$allowed_roles = ['ADMIN', 'MANAGER', 'DATA_COLLECTOR', 'TECHNICIAN'];

$page_title = "All Reports - WRRS";
$is_dashboard = true;

require_once 'config/db.php';
require_once 'includes/header.php';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, username, password_hash, role, status, profile_photo FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] !== 'ACTIVE') {
                $error = "Your account is inactive. Please contact system administrator.";
            } elseif (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) { 
                // Supports hashed passwords & fallback legacy plain text
                $_SESSION['user_id']       = $user['user_id'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'];

                $role = strtoupper($user['role']);
                if ($role === 'MANAGER') {
                    header("Location: manager_dashboard.php");
                } elseif ($role === 'TECHNICIAN') {
                    header("Location: tech_dashboard.php");
                }
                elseif ($role === 'ADMIN') {
                    header("Location: admin.php"); 
                }
                elseif ($role === 'DATA_COLLECTOR') {
                    header("Location: dc_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>

<div style="min-height: calc(100vh - 70px); background: #f1f5f9; display: flex; align-items: center; justify-content: center; padding: 24px;">

    <div class="panel-card" style="width: 100%; max-width: 420px; background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);">
        
        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="color: #0f172a; font-size: 1.5rem; margin-bottom: 6px;">WRRS Login</h2>
            <p style="color: #64748b; font-size: 0.9rem;">Sign in to access your staff workspace</p>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'logged_out'): ?>
            <div style="background: #e0f2fe; color: #0369a1; padding: 10px 14px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 16px; text-align: center;">
                You have been logged out successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: #fef2f2; color: #dc2626; padding: 10px 14px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 16px; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group" style="margin-bottom: 16px;">
                <label for="username" style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="Enter your username" 
                    style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;"
                    required
                >
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="password" style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;"
                    required
                >
            </div>

            <button 
                type="submit" 
                class="btn btn-purple" 
                style="width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer;"
            >
                Log In
            </button>
        </form>
        <form action="login.php" method="POST">

    <div style="margin-top: 10px; text-align: right;">
        <a href="forgot-password.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">
            Forgot Password?
        </a>
    </div>

</form>

    </div>

</div>

</body>
</html>