<?php
session_start();
require_once 'config/db.php';

// Access Control: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// 1. Fetch current user data from database
$user_stmt = $conn->prepare("SELECT user_id, username, full_name, phone_number, role, specialization, status, profile_photo, created_at FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user_data) {
    header("Location: logout.php");
    exit();
}

$is_staff = in_array(strtoupper($user_data['role']), ['DATA_COLLECTOR', 'TECHNICIAN', 'MANAGER']);
if ($is_staff) {
    $is_dashboard = true;
}

// 2. Handle Profile Photo Upload / Remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_photo') {
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        // Remove existing photo
        if (!empty($user_data['profile_photo']) && file_exists(__DIR__ . '/' . $user_data['profile_photo'])) {
            unlink(__DIR__ . '/' . $user_data['profile_photo']);
        }
        $up = $conn->prepare("UPDATE users SET profile_photo = NULL, updated_at = NOW() WHERE user_id = ?");
        $up->bind_param("i", $user_id);
        $up->execute();
        $up->close();
        $_SESSION['profile_photo'] = null;
        $user_data['profile_photo'] = null;
        $success = "Profile photo removed successfully.";
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];
        $file_size = $_FILES['profile_photo']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed_exts)) {
            $error = "Invalid image type. Please upload a JPG, PNG, or WEBP image.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $error = "Image size exceeds maximum limit of 5MB.";
        } else {
            // Verify image contents
            $img_info = @getimagesize($file_tmp);
            if ($img_info === false) {
                $error = "Uploaded file is not a valid image.";
            } else {
                $upload_dir = 'assets/uploads/avatars/';
                if (!file_exists(__DIR__ . '/' . $upload_dir)) {
                    mkdir(__DIR__ . '/' . $upload_dir, 0777, true);
                }

                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, __DIR__ . '/' . $target_path)) {
                    // Remove old photo if exists
                    if (!empty($user_data['profile_photo']) && file_exists(__DIR__ . '/' . $user_data['profile_photo'])) {
                        @unlink(__DIR__ . '/' . $user_data['profile_photo']);
                    }

                    $up = $conn->prepare("UPDATE users SET profile_photo = ?, updated_at = NOW() WHERE user_id = ?");
                    $up->bind_param("si", $target_path, $user_id);
                    $up->execute();
                    $up->close();

                    $_SESSION['profile_photo'] = $target_path;
                    $user_data['profile_photo'] = $target_path;
                    $success = "Profile photo updated successfully!";
                } else {
                    $error = "Failed to save uploaded image. Please try again.";
                }
            }
        }
    } else {
        $error = "Please select an image file to upload.";
    }
}

// 3. Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = trim($_POST['current_password'] ?? '');
    $new_pass     = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "Please complete all password fields.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Fetch current password_hash
        $pwd_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $pwd_stmt->bind_param("i", $user_id);
        $pwd_stmt->execute();
        $stored = $pwd_stmt->get_result()->fetch_assoc();
        $pwd_stmt->close();

        $hash = $stored['password_hash'] ?? '';
        if (password_verify($current_pass, $hash) || $current_pass === $hash) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $up_pwd = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
            $up_pwd->bind_param("si", $new_hash, $user_id);
            if ($up_pwd->execute()) {
                $success = "Password changed successfully! Please use your new password next time you sign in.";
            } else {
                $error = "Database error while updating password.";
            }
            $up_pwd->close();
        } else {
            $error = "Incorrect current password. Please try again.";
        }
    }
}

// 4. Handle Basic Profile Info Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    if (empty($full_name)) {
        $error = "Full Name cannot be empty.";
    } else {
        $up_info = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, updated_at = NOW() WHERE user_id = ?");
        $up_info->bind_param("ssi", $full_name, $phone_number, $user_id);
        if ($up_info->execute()) {
            $_SESSION['full_name'] = $full_name;
            $user_data['full_name'] = $full_name;
            $user_data['phone_number'] = $phone_number;
            $success = "Profile details updated successfully!";
        } else {
            $error = "Failed to update profile information.";
        }
        $up_info->close();
    }
}

$page_title = "My Profile & Settings - WRRS";
include_once 'includes/header.php';
?>

<?php if ($is_staff): ?>
<script>
    document.body.classList.add('app-layout-body');
</script>

    <!-- Main Workspace Content -->
    <main class="app-main-content">
        <div class="app-container" style="max-width: 900px;">
<?php else: ?>
<main class="main-wrapper">
    <div class="landing-content" style="max-width: 900px; margin: 0 auto; width: 100%;">
<?php endif; ?>

        <div class="panel-header" style="margin-bottom: 24px;">
            <div>
                <h2>MY ACCOUNT & PROFILE SETTINGS</h2>
                <p class="text-muted" style="font-size: 0.9rem;">Manage your credentials, profile picture, and account details</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <span>✅</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid-container">

            <!-- Card 1: Profile Photo Card -->
            <div class="panel-card profile-card">
                <div class="profile-card-header">
                    <span class="panel-icon">📸</span>
                    <h3>Profile Photo</h3>
                </div>
                
                <div class="profile-avatar-preview-area">
                    <div class="avatar-large-wrapper">
                        <?php if (!empty($user_data['profile_photo']) && file_exists(__DIR__ . '/' . $user_data['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($user_data['profile_photo']); ?>" alt="Profile Photo" id="avatarPreview" class="avatar-large-img">
                        <?php else: ?>
                            <div id="avatarFallback" class="avatar-large-fallback">
                                <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                            </div>
                            <img src="" alt="Profile Photo Preview" id="avatarPreview" class="avatar-large-img" style="display: none;">
                        <?php endif; ?>
                    </div>

                    <div class="avatar-info-text">
                        <strong><?php echo htmlspecialchars($user_data['full_name']); ?></strong>
                        <span class="badge badge-assigned" style="margin-top: 4px; display: inline-block;">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $user_data['role'])); ?>
                        </span>
                        <p class="text-muted" style="font-size: 0.8rem; margin-top: 6px;">Supported: JPG, PNG, WEBP (Max 5MB)</p>
                    </div>
                </div>

                <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-photo-form">
                    <input type="hidden" name="action" value="update_photo">
                    
                    <div class="form-group">
                        <label for="profile_photo_input" class="btn btn-outline btn-block" style="cursor: pointer;">
                            <span>📁</span> Choose New Photo
                        </label>
                        <input type="file" id="profile_photo_input" name="profile_photo" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewProfileImage(this)">
                        <span id="selectedFileName" style="font-size: 0.82rem; color: #64748b; display: block; margin-top: 4px; text-align: center;"></span>
                    </div>

                    <div class="btn-group" style="justify-content: stretch;">
                        <button type="submit" class="btn btn-primary btn-block">Upload & Save Photo</button>
                    </div>
                </form>

                <?php if (!empty($user_data['profile_photo'])): ?>
                    <form action="profile.php" method="POST" style="margin-top: 10px;" onsubmit="return confirm('Are you sure you want to remove your profile photo?');">
                        <input type="hidden" name="action" value="update_photo">
                        <input type="hidden" name="remove_photo" value="1">
                        <button type="submit" class="btn btn-sm-remove btn-block" style="padding: 8px; text-align: center; border: none; cursor: pointer;">
                            🗑️ Remove Photo
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Card 2: Personal Details Card -->
            <div class="panel-card profile-card">
                <div class="profile-card-header">
                    <span class="panel-icon">👤</span>
                    <h3>Personal Information</h3>
                </div>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_info">

                    <div class="form-group">
                        <label for="username_disp">Username (Fixed)</label>
                        <input type="text" id="username_disp" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled class="form-control" style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" placeholder="e.g. 0712345678" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Assigned System Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_data['role']); ?>" disabled class="form-control" style="background-color: #f1f5f9; cursor: not-allowed;">
                    </div>

                    <button type="submit" class="btn btn-emerald btn-block" style="margin-top: 8px;">Update Details</button>
                </form>
            </div>

            <!-- Card 3: Change Password Card -->
            <div class="panel-card profile-card" style="grid-column: 1 / -1;">
                <div class="profile-card-header">
                    <span class="panel-icon">🔒</span>
                    <h3>Change Account Password</h3>
                </div>
                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 16px;">
                    Ensure your account is using a strong and unique password to protect municipal reporting data.
                </p>

                <form action="profile.php" method="POST" class="password-change-form">
                    <input type="hidden" name="action" value="change_password">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" placeholder="••••••••" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password * (Min 6 chars)</label>
                            <input type="password" id="new_password" name="new_password" placeholder="••••••••" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required class="form-control">
                        </div>
                    </div>

                    <div class="form-actions-row" style="margin-top: 8px;">
                        <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">
                            <span>💾</span> Save New Password
                        </button>
                    </div>
                </form>
            </div>

        </div>

<?php if ($is_staff): ?>
        </div>
    </main>
</div>
<?php else: ?>
    </div>
</main>
<?php include_once 'includes/footer.php'; ?>
<?php endif; ?>

<script>
function previewProfileImage(input) {
    const file = input.files[0];
    const preview = document.getElementById('avatarPreview');
    const fallback = document.getElementById('avatarFallback');
    const fileNameSpan = document.getElementById('selectedFileName');

    if (file) {
        fileNameSpan.textContent = 'Selected: ' + file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (fallback) {
                fallback.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}
</script>
<script src="assets/js/wrrs.js"></script>
</body>
</html>
