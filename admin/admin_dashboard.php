<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

// Fetch some quick stats
$stats = [
    'members' => $pdo->query("SELECT COUNT(*) FROM Users u JOIN Roles r ON u.RoleId = r.RoleId WHERE r.RoleName = 'Member'")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM Products")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM Orders")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(TotalAmount) FROM Orders WHERE OrderStatus = 'Completed'")->fetchColumn() ?? 0
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Dashboard</title>
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
        }
        .sidebar h2 { margin: 0 0 2rem 0; font-size: 1.8rem; font-weight: 700; }
        .sidebar nav a { 
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            padding: 0.75rem 1rem; 
            border-radius: 10px; 
            transition: all 0.3s ease; 
            display: block; 
            margin-bottom: 0.5rem; 
        }
        .sidebar nav a:hover, .sidebar nav a.active { 
            background: rgba(255,255,255,0.2); 
            color: white; 
        }
        .content { flex: 1; padding: 2rem; background-color: transparent; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem; }
        .stat-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 20px; 
            text-align: center; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border: none; 
            position: relative; 
            overflow: hidden; 
        }
        .stat-card::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 5px; 
            background: linear-gradient(90deg, #667eea, #764ba2); 
        }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: #6f42c1; margin: 0.5rem 0; }
        .stat-label { color: #6c757d; opacity: 1; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px; }
        .glass { 
            background: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            padding: 2rem; 
            margin-top: 2rem; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 0.75rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s ease; 
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .btn-danger { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); 
        }
        .btn-danger:hover { 
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4); 
        }
        h1 { color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 2rem;">
                <a href="admin_dashboard.php" class="active">Dashboard</a>
                <a href="category_management.php">Categories</a>
                <a href="product_management.php">Products</a>
                <a href="order_management.php">Orders</a>
                <a href="member_management.php">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 2rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <h1>Admin Dashboard</h1>
            
            <div class="stat-grid">
                <div class="glass stat-card">
                    <div class="stat-label">Total Members</div>
                    <div class="stat-value"><?= number_format($stats['members']) ?></div>
                </div>
                <div class="glass stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= number_format($stats['products']) ?></div>
                </div>
                <div class="glass stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= number_format($stats['orders']) ?></div>
                </div>
                <div class="glass stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">$<?= number_format($stats['revenue'], 2) ?></div>
                </div>
            </div>

            <div style="margin-top: 3rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="glass" style="padding: 2rem; border-radius: 12px;">
                    <h3 style="margin-top: 0; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; display: inline-block;">Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                        <a href="product_management.php" class="btn" style="text-align: center; text-decoration: none;">Manage Products</a>
                        <a href="member_management.php" class="btn" style="text-align: center; text-decoration: none;">Manage Members</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
