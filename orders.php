<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch Orders
$stmt = $pdo->prepare("SELECT * FROM Orders WHERE UserId = ? ORDER BY OrderDate DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

include 'header.php';
?>

<div class="container" style="max-width: 1000px; margin-top: 2rem; margin-bottom: 4rem;">
    <h1 style="margin-bottom: 2rem;">My Orders</h1>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Order placed successfully! Thank you for your purchase.</div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="glass" style="padding: 4rem; text-align: center;">
            <h3 style="color: var(--text-muted); margin-bottom: 1.5rem;">You have not placed any orders yet.</h3>
            <a href="products.php" class="btn">Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <?php foreach ($orders as $order): ?>
                <?php
                    // Fetch items for this order
                    $itemStmt = $pdo->prepare("SELECT oi.*, p.ProductName, (SELECT ImageUrl FROM ProductImages WHERE ProductId = p.ProductId AND IsPrimary = 1 LIMIT 1) as PrimaryImage FROM OrderItems oi JOIN Products p ON oi.ProductId = p.ProductId WHERE oi.OrderId = ?");
                    $itemStmt->execute([$order['OrderId']]);
                    $items = $itemStmt->fetchAll();
                ?>
                <div class="glass" style="overflow: hidden;">
                    <div style="background: rgba(0,0,0,0.03); padding: 1.5rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <span class="text-muted" style="font-size: 0.875rem; text-transform: uppercase; font-weight: 600;">Order Placed</span><br>
                            <span><?= date('F j, Y, g:i a', strtotime($order['OrderDate'])) ?></span>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size: 0.875rem; text-transform: uppercase; font-weight: 600;">Total</span><br>
                            <span style="font-weight: bold; color: var(--primary-color);">$<?= number_format($order['TotalAmount'], 2) ?></span>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size: 0.875rem; text-transform: uppercase; font-weight: 600;">Status</span><br>
                            <span style="font-weight: bold; color: <?= $order['OrderStatus'] == 'Pending' ? '#f59e0b' : '#10b981' ?>;"><?= htmlspecialchars($order['OrderStatus']) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span class="text-muted" style="font-size: 0.875rem; text-transform: uppercase; font-weight: 600;">Order #</span><br>
                            <span style="font-family: monospace; font-size: 0.85rem;"><?= substr($order['OrderId'], 0, 8) ?>...</span>
                        </div>
                    </div>

                    <div style="padding: 1.5rem;">
                        <h4 style="margin-top: 0; margin-bottom: 1rem;">Shipping To:</h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; white-space: pre-wrap;"><?= htmlspecialchars($order['ShippingAddress']) ?></p>

                        <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Items:</h4>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($items as $item): ?>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <img src="<?= $item['PrimaryImage'] ? $item['PrimaryImage'] : 'asset/image/default_product.png' ?>" loading="lazy" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                    <div style="flex: 1;">
                                        <a href="product_detail.php?id=<?= $item['ProductId'] ?>" style="color: inherit; text-decoration: none; font-weight: 500;"><?= htmlspecialchars($item['ProductName']) ?></a>
                                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-muted);">Qty: <?= $item['Quantity'] ?></p>
                                    </div>
                                    <div style="font-weight: 600;">
                                        $<?= number_format($item['UnitPrice'] * $item['Quantity'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
