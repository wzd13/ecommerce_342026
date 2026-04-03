<?php 
require_once '../config/config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. 查询用户及其关联的角色名称
    $sql = "SELECT u.UserId, u.PasswordHash, r.RoleName 
            FROM Users u 
            JOIN Roles r ON u.RoleId = r.RoleId 
            WHERE u.Email = :email LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. 验证密码哈希
        if (password_verify($password, $user['PasswordHash'])) {
            
            // 3. 核心权限校验：如果是 Member 尝试登录 Admin 页面
            if ($user['RoleName'] !== 'Admin') {
                $error = "Error: You do not have administrator privileges to access this page.";
            } else {
                // 4. 登录成功，设置管理员 Session
                $_SESSION['user_id'] = $user['UserId']; // Assuming main app uses user_id
                $_SESSION['admin_id'] = $user['UserId'];
                $_SESSION['role'] = $user['RoleName'];
                
                header("Location: admin_dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "User does not exist.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Zeng Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../asset/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .sidebar { 
            width: 300px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            padding: 2rem; 
            border-right: none; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            text-align: center; 
        }
        .sidebar h2 { margin: 0; font-size: 2rem; font-weight: 700; }
        .sidebar p { margin-top: 1rem; opacity: 0.9; font-size: 1.1rem; }
        .content { 
            flex: 1; 
            padding: 2rem; 
            background-color: transparent; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-form { max-width: 450px; width: 100%; }
        .glass { 
            background: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            border-radius: 20px; 
            color: #6f42c1; 
            padding: 3rem; 
            position: relative; 
            overflow: hidden; 
        }
        .glass::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 5px; 
            background: linear-gradient(90deg, #667eea, #764ba2); 
        }
        .glass input { 
            background: #f8f9fa; 
            color: #6f42c1; 
            border: 2px solid #e9ecef; 
            border-radius: 10px; 
            padding: 0.75rem; 
            transition: all 0.3s ease; 
        }
        .glass input:focus { 
            border-color: #6f42c1; 
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1); 
            outline: none; 
        }
        .glass label { 
            color: #6f42c1; 
            font-weight: 600; 
            margin-bottom: 0.5rem; 
            display: block; 
        }
        .alert { 
            text-align: center; 
            border: none; 
            background: #f8d7da; 
            color: #721c24; 
            padding: 0.75rem; 
            border-radius: 10px; 
            margin-bottom: 1.5rem; 
            border-left: 4px solid #dc3545; 
        }
        h2 { 
            color: #6f42c1 !important; 
            font-size: 2.5rem; 
            font-weight: 700; 
            margin: 0; 
            text-align: center; 
        }
        h4 { 
            color: #6c757d !important; 
            font-size: 1.2rem; 
            margin: 0.5rem 0 2rem 0; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            text-align: center; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 0.75rem 2rem; 
            border-radius: 10px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            width: 100%; 
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .form-group { margin-bottom: 1.5rem; }
        .back-link { 
            text-align: center; 
            margin-top: 2rem; 
        }
        .back-link a { 
            color: #6c757d; 
            text-decoration: none; 
            font-size: 0.9rem; 
            transition: color 0.3s ease; 
        }
        .back-link a:hover { 
            color: #6f42c1; 
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <p>Please log in to access the dashboard.</p>
        </div>
        <div class="content">
            <div class="login-form">
                <div class="glass">
                    <h2>Zeng Store</h2>
                    <h4>Admin Portal</h4>
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="email">Admin Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn">Access Dashboard</button>
                        
                        <div class="back-link">
                            <a href="../index.php">&larr; Back to Main Store</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>