<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

$memberId = $_GET['id'] ?? '';

if (!$memberId) {
    header("Location: member_management.php");
    exit;
}

$sql = "
    SELECT 
        u.UserId, 
        u.Email, 
        u.IsActive, 
        u.CreatedDate, 
        u.LastLogin,
        p.FirstName, 
        p.LastName, 
        p.PhoneNumber, 
        p.ProfilePhotoUrl,
        r.RoleName
    FROM Users u 
    LEFT JOIN UserProfile p ON u.UserId = p.UserId 
    INNER JOIN Roles r ON u.RoleId = r.RoleId
    WHERE u.UserId = :userId
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':userId' => $memberId]);
$member = $stmt->fetch();

if (!$member) {
    die("Member not found.");
}

// Fetch member's default address (if any)
$addrStmt = $pdo->prepare("SELECT * FROM Addresses WHERE UserId = :userId AND IsDefault = 1 LIMIT 1");
$addrStmt->execute([':userId' => $memberId]);
$address = $addrStmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Member Detail</title>
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
        .sidebar nav a:hover { 
            background: rgba(255,255,255,0.2); 
            color: white; 
        }
        .content { flex: 1; padding: 2rem; background-color: transparent; }
        .detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 2rem; }
        .info-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem; }
        .info-label { font-weight: 600; color: #6f42c1; }
        .status-active { color: #10b981; font-weight: 600; }
        .status-inactive { color: #ef4444; font-weight: 600; }
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
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="category_management.php">Categories</a>
                <a href="product_management.php">Products</a>
                <a href="order_management.php">Orders</a>
                <a href="member_management.php">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 2rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Member Detail</h1>
                <a href="member_management.php" class="btn" style="text-decoration: none; background: var(--secondary-color);">Back to List</a>
            </div>

            <div class="detail-grid">
                <!-- Left Column: Profile Card -->
                <div class="glass info-card" style="text-align: center;">
                    <?php 
                        $avatar = !empty($member['ProfilePhotoUrl']) ? '../' . $member['ProfilePhotoUrl'] : '../asset/image/default_avatar.png'; 
                    ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" loading="lazy" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); margin-bottom: 1.5rem;">
                    <h2 style="margin: 0;"><?= htmlspecialchars(trim(($member['FirstName'] ?? '') . ' ' . ($member['LastName'] ?? ''))) ?: 'N/A' ?></h2>
                    <p class="text-muted" style="margin-top: 0.5rem;"><?= htmlspecialchars($member['RoleName']) ?></p>
                    
                    <div style="margin-top: 1rem;">
                        <?php if($member['IsActive']): ?>
                            <span class="status-active" style="background: rgba(16, 185, 129, 0.1); padding: 0.5rem 1rem; border-radius: 20px;">Active Account</span>
                        <?php else: ?>
                            <span class="status-inactive" style="background: rgba(239, 68, 68, 0.1); padding: 0.5rem 1rem; border-radius: 20px;">Inactive Account</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Details -->
                <div class="glass info-card">
                    <h3 style="margin-top: 0; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; display: inline-block;">Account Information</h3>
                    
                    <div class="info-row" style="margin-top: 1.5rem;">
                        <span class="info-label">Email Address</span>
                        <span><?= htmlspecialchars($member['Email']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span><?= htmlspecialchars($member['PhoneNumber'] ?? 'N/A') ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Registration Date</span>
                        <span><?= date('F j, Y, g:i a', strtotime($member['CreatedDate'])) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Last Login</span>
                        <span><?= $member['LastLogin'] ? date('F j, Y, g:i a', strtotime($member['LastLogin'])) : 'Never' ?></span>
                    </div>

                    <h3 style="margin-top: 2rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; display: inline-block;">Default Address</h3>
                    <?php if($address): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px;">
                            <p style="margin: 0 0 0.5rem 0;"><strong><?= htmlspecialchars($address['RecipientName']) ?></strong> (<?= htmlspecialchars($address['PhoneNumber']) ?>)</p>
                            <p style="margin: 0; color: var(--text-color); opacity: 0.8;"><?= nl2br(htmlspecialchars($address['FullAddress'])) ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-muted" style="margin-top: 1rem;">No default address set.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
