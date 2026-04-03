<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

$message = '';
$error = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';

    if ($orderId && $newStatus) {
        try {
            $stmt = $pdo->prepare("UPDATE Orders SET OrderStatus = ? WHERE OrderId = ?");
            if ($stmt->execute([$newStatus, $orderId])) {
                $message = "Order status updated successfully.";
            } else {
                $error = "Failed to update order status.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Orders
$query = "SELECT o.*, u.Email, up.FirstName, up.LastName 
          FROM Orders o 
          JOIN Users u ON o.UserId = u.UserId 
          LEFT JOIN UserProfile up ON u.UserId = up.UserId 
          ORDER BY o.OrderDate DESC";
$orders = $pdo->query($query)->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Order Management</title>
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
        .glass { 
            background: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            padding: 2rem; 
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
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .btn-success:hover { box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
        .btn-danger:hover { box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4); }
        h1 { color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background-color: #f8f9fa; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8f9fa; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; color: white; display: inline-block; }
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-accepted { background-color: #17a2b8; }
        .badge-shipped { background-color: #007bff; }
        .badge-delivered { background-color: #28a745; }
        .badge-canceled { background-color: #dc3545; }
        .form-select { padding: 0.4rem; border: 1px solid #ced4da; border-radius: 5px; font-size: 0.9rem; margin-right: 0.5rem; }
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
                <a href="order_management.php" class="active">Orders</a>
                <a href="member_management.php">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 1rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <h1>Manage Orders</h1>
            
            <?php if($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="glass">
                <?php if (empty($orders)): ?>
                    <p class="text-muted">No orders found.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $statusClass = 'badge-pending';
                                    $statusMatch = strtolower($order['OrderStatus']);
                                    if ($statusMatch === 'accepted') $statusClass = 'badge-accepted';
                                    elseif ($statusMatch === 'shipped') $statusClass = 'badge-shipped';
                                    elseif ($statusMatch === 'delivered') $statusClass = 'badge-delivered';
                                    elseif ($statusMatch === 'canceled') $statusClass = 'badge-canceled';
                                    
                                    $customerName = trim(($order['FirstName'] ?? '') . ' ' . ($order['LastName'] ?? ''));
                                    if (!$customerName) $customerName = $order['Email'];
                                ?>
                                    <tr>
                                        <td><span style="font-family: monospace; font-size: 0.9em;"><?= substr($order['OrderId'], 0, 8) ?>...</span></td>
                                        <td>
                                            <?= htmlspecialchars($customerName) ?><br>
                                            <span style="font-size: 0.8em; color: #6c757d;"><?= htmlspecialchars($order['Email']) ?></span>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($order['OrderDate'])) ?></td>
                                        <td style="font-weight: 600;">$<?= number_format($order['TotalAmount'], 2) ?></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($order['OrderStatus'] ?? 'Pending') ?></span></td>
                                        <td>
                                            <form method="POST" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                                <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                                
                                                <?php if (strtolower($order['OrderStatus']) === 'pending'): ?>
                                                    <!-- Quick Accept Button for Pending Orders -->
                                                    <input type="hidden" name="new_status" value="Accepted">
                                                    <button type="submit" name="update_status" class="btn btn-success">Accept</button>
                                                <?php else: ?>
                                                    <!-- Standard Status Update Dropdown -->
                                                    <select name="new_status" class="form-select" onchange="this.form.submit()" style="max-width: 120px;">
                                                        <option value="Pending" <?= $order['OrderStatus'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Accepted" <?= $order['OrderStatus'] == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                                        <option value="Shipped" <?= $order['OrderStatus'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                        <option value="Delivered" <?= $order['OrderStatus'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        <option value="Canceled" <?= $order['OrderStatus'] == 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
