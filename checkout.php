<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Check Cart
$stmt = $pdo->prepare("SELECT c.*, p.Price, p.StockQuantity, p.ProductName FROM Carts c JOIN Products p ON c.ProductId = p.ProductId WHERE c.UserId = ?");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Fetch Addresses
$stmtAddr = $pdo->prepare("SELECT * FROM Addresses WHERE UserId = ? ORDER BY IsDefault DESC");
$stmtAddr->execute([$userId]);
$addresses = $stmtAddr->fetchAll();

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $addressId = $_POST['address_id'] ?? null;
    if (!$addressId) {
        $error = "Please select a shipping address.";
    } else {
        try {
            // Find address snapshot
            $addrStmt = $pdo->prepare("SELECT FullAddress FROM Addresses WHERE AddressId = ?");
            $addrStmt->execute([$addressId]);
            $addrSnapshot = $addrStmt->fetchColumn();

            $pdo->beginTransaction();

            $total = 0;
            // Validate Stock first and calc total
            foreach ($cartItems as $item) {
                if ($item['Quantity'] > $item['StockQuantity']) {
                    throw new Exception("Not enough stock for {$item['ProductName']}.");
                }
                $total += ($item['Price'] * $item['Quantity']);
            }
            $shipping = 10.00;
            $grandTotal = $total + $shipping;

            $orderId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
            
            // 1. Insert Order
            $insertOrder = $pdo->prepare("INSERT INTO Orders (OrderId, UserId, AddressId, TotalAmount, OrderStatus, ShippingAddress) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $insertOrder->execute([$orderId, $userId, $addressId, $grandTotal, $addrSnapshot]);

            // 2. Insert Items & Update Stock
            foreach ($cartItems as $item) {
                $orderItemId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
                $insertItem = $pdo->prepare("INSERT INTO OrderItems (OrderItemId, OrderId, ProductId, Quantity, UnitPrice) VALUES (?, ?, ?, ?, ?)");
                $insertItem->execute([$orderItemId, $orderId, $item['ProductId'], $item['Quantity'], $item['Price']]);

                // Reduce Stock
                $updateStock = $pdo->prepare("UPDATE Products SET StockQuantity = StockQuantity - ? WHERE ProductId = ?");
                $updateStock->execute([$item['Quantity'], $item['ProductId']]);
            }

            // 3. Clear Cart
            $clearCart = $pdo->prepare("DELETE FROM Carts WHERE UserId = ?");
            $clearCart->execute([$userId]);

            $pdo->commit();

            // Redirect to Success/Order History
            header("Location: orders.php?success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Checkout Failed: " . $e->getMessage();
        }
    }
}

// Subtotal calculation for display
$subtotal = 0;
foreach ($cartItems as $item) {
    if ($item['StockQuantity'] >= $item['Quantity']) {
        $subtotal += ($item['Price'] * $item['Quantity']);
    } else {
        $subtotal += ($item['Price'] * min($item['Quantity'], $item['StockQuantity'])); // display only
    }
}
$shipping = 10.00;
$totalAmount = $subtotal + $shipping;

include 'header.php';
?>

<div class="container" style="max-width: 800px; margin-top: 2rem; margin-bottom: 4rem;">
    <h1>Checkout</h1>
    
    <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
            <h3>1. Select Shipping Address</h3>
            <?php if (empty($addresses)): ?>
                <p class="text-danger">You do not have any saved addresses. <a href="user_addresses.php">Add one now</a>.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <?php foreach ($addresses as $addr): ?>
                        <label style="display: flex; gap: 1rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: <?= $addr['IsDefault'] ? 'rgba(79, 70, 229, 0.05)' : 'transparent' ?>; transition: border-color 0.2s;">
                            <input type="radio" name="address_id" value="<?= $addr['AddressId'] ?>" <?= $addr['IsDefault'] ? 'checked' : '' ?> style="margin-top: 5px;">
                            <div>
                                <strong><?= htmlspecialchars($addr['RecipientName']) ?></strong> <?= $addr['IsDefault'] ? '<span style="font-size: 0.75rem; background: var(--primary-color); color: white; padding: 2px 6px; border-radius: 4px; margin-left: 0.5rem;">Default</span>' : '' ?><br>
                                <span class="text-muted"><?= htmlspecialchars($addr['PhoneNumber']) ?></span><br>
                                <span><?= nl2br(htmlspecialchars($addr['FullAddress'])) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
            <h3>2. Order Summary</h3>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($cartItems as $item): ?>
                    <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                        <span><?= htmlspecialchars($item['ProductName']) ?> &times; <?= $item['Quantity'] ?></span>
                        <strong>$<?= number_format($item['Price'] * $item['Quantity'], 2) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
                <span>Subtotal</span>
                <strong>$<?= number_format($subtotal, 2) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                <span>Shipping</span>
                <strong>$<?= number_format($shipping, 2) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem; font-size: 1.25rem;">
                <strong>Grand Total</strong>
                <strong style="color: var(--primary-color);">$<?= number_format($totalAmount, 2) ?></strong>
            </div>
        </div>

        <button type="submit" name="place_order" class="btn" style="width: 100%; padding: 1rem; font-size: 1.1rem; text-transform: uppercase; font-weight: bold;" <?= empty($addresses) ? 'disabled style="opacity: 0.5;"' : '' ?>>Place Order</button>
    </form>
</div>

<?php include 'footer.php'; ?>
