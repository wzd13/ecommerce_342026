<?php

include_once 'config/config.php';

$message = "";
$user = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT * FROM Users WHERE ResetToken = :token AND ResetTokenExpiry > NOW() LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "Invalid or expired token.";
    }
} else {
    $message = "Token not provided.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $update_sql = "UPDATE Users SET PasswordHash = :password, ResetToken = NULL, ResetTokenExpiry = NULL WHERE UserId = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            'password' => $passwordHash,
            'id' => $user['UserId']
        ]);

        $message = "Password has been successfully reset. <a href='user_login.php'>Login here</a>";
        $user = null; // Hide form after success
    }
}

?>

<?php include 'header.php'; ?>

<div class="container" style="max-width: 400px; margin-top: 6rem; margin-bottom: 6rem;">
    <div class="glass" style="padding: 2.5rem;">
        <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--primary-color);">Reset Password</h2>
        
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?>" style="text-align: center;">
                <?php echo $message; // Note: Not using htmlspecialchars here because we output a link on success ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p class="text-muted" style="text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                Please enter your new desired password below.
            </p>
            <form action="" method="post">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="New Password" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; font-size: 1.05rem;">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>