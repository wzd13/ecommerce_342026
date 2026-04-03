<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

// Basic Searching
$searchQuery = $_GET['search'] ?? '';

$sql = "
    SELECT 
        u.UserId, 
        u.Email, 
        u.IsActive, 
        u.CreatedDate, 
        p.FirstName, 
        p.LastName, 
        p.PhoneNumber, 
        p.ProfilePhotoUrl 
    FROM Users u 
    LEFT JOIN UserProfile p ON u.UserId = p.UserId 
    INNER JOIN Roles r ON u.RoleId = r.RoleId
    WHERE r.RoleName = 'Member'
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (u.Email LIKE ? OR p.FirstName LIKE ? OR p.LastName LIKE ? OR p.PhoneNumber LIKE ?)";
    $params = array_fill(0, 4, '%' . $searchQuery . '%');
}

$sql .= " ORDER BY u.CreatedDate DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Member Management</title>
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
        .member-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .member-table th, .member-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        .member-table th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; }
        .member-table tbody tr:hover { background: #f8f9fa; }
        .status-active { color: #10b981; font-weight: 600; }
        .status-inactive { color: #ef4444; font-weight: 600; }
        .glass { 
            background: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            padding: 1.5rem; 
            margin-bottom: 2rem; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 0.5rem 1rem; 
            border-radius: 10px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s ease; 
            font-size: 0.875rem; 
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
        .form-control { 
            padding: 0.5rem; 
            border: 1px solid #ced4da; 
            border-radius: 5px; 
            width: 100%; 
        }
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
                <a href="member_management.php" class="active">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 2rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <h1>Manage Members</h1>
            
            <div class="glass" style="padding: 1.5rem; margin-bottom: 2rem;">
                <form method="GET" style="display: flex; gap: 1rem;">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($searchQuery) ?>" style="flex: 1;">
                    <button type="submit" class="btn">Search</button>
                    <?php if($searchQuery): ?>
                         <a href="member_management.php" class="btn btn-danger" style="text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass" style="overflow-x: auto;">
                <table class="member-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($members) > 0): ?>
                            <?php foreach($members as $member): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $avatar = !empty($member['ProfilePhotoUrl']) ? '../' . $member['ProfilePhotoUrl'] : '../asset/image/default_avatar.png'; 
                                        ?>
                                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" loading="lazy" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    </td>
                                    <td><?= htmlspecialchars(trim(($member['FirstName'] ?? '') . ' ' . ($member['LastName'] ?? ''))) ?: 'N/A' ?></td>
                                    <td><?= htmlspecialchars($member['Email']) ?></td>
                                    <td><?= htmlspecialchars($member['PhoneNumber'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if($member['IsActive']): ?>
                                            <span class="status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($member['CreatedDate'])) ?></td>
                                    <td>
                                        <a href="member_detail.php?id=<?= urlencode($member['UserId']) ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; text-decoration: none;">View Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">No members found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
