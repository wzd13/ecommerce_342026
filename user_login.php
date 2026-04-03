<?php

include_once 'config/config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: user_login.php");
    exit();
}

$error = '';

if($_SERVER ['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT u.UserId, u.PasswordHash, u.IsActive, r.RoleName 
            FROM Users u 
            JOIN Roles r ON u.RoleId = r.RoleId 
            WHERE u.Email = :email LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user){
        if($user['IsActive'] == 0){
            $error = "Your account is inactive. Please contact the administrator.";
        } else {
            if(password_verify($password, $user['PasswordHash'])){
                $_SESSION['user_id'] = $user['UserId'];
                $_SESSION['role'] = $user['RoleName'];
                header("Location: index.php");
                exit;
            }else{
                $error = "Invalid email or password.";
            }
        }
    }else{
        $error = "User does not exist.";
    }
}

?>

<?php include 'header.php'; ?>

<div class="container" style="max-width: 400px; margin-top: 6rem; margin-bottom: 6rem;">
    <div class="glass" style="padding: 2.5rem;">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Welcome Back</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger" style="text-align: center;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['registered'])): ?>
            <div class="alert alert-success" style="text-align: center;">Registration successful! Please login.</div>
        <?php endif; ?>
        
        <form action="user_login.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="enter your email" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="enter your password" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; font-size: 1.1rem; margin-bottom: 1rem;">Login</button>
            
            <div style="text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="forget_password.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">Forgot password?</a>
                <div>
                    <span class="text-muted" style="font-size: 0.9rem;">Need an account?</span>
                    <a href="user_register.php" style="color: var(--primary-color); font-weight: 500; text-decoration: none; font-size: 0.9rem;">Register</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>