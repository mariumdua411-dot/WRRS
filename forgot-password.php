<?php
$conn = new mysqli("localhost", "root", "", "wrrsdb");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        // Remove older tokens for this email
        $stmt_del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt_del->bind_param("s", $email);
        $stmt_del->execute();

        // Save new reset token
        $stmt_ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt_ins->bind_param("sss", $email, $token, $expires);
        $stmt_ins->execute();

        // Redirect directly to the reset page for testing (or email link in production)
        header("Location: reset-password.php?token=" . $token);
        exit();
    } else {
        $message = "If that email is registered, you will be redirected to reset your password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recover Password - WRRS</title>
</head>
<body>
    <form method="POST" style="max-width: 400px; margin: 50px auto;">
        <h2>Password Recovery</h2>
        <?php if ($message): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>
        <p>Enter your registered email address to recover your account access.</p>
        <input type="email" name="email" placeholder="Enter your email" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
        <button type="submit" style="width: 100%; padding: 10px;">Continue</button>
        <p><a href="login.php">Back to Login</a></p>
    </form>
</body>
</html>