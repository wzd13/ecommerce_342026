<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch Cart Data
$stmt = $pdo->prepare("
    SELECT c.CartId, c.Quantity, p.ProductId, p.ProductName, p.Price, p.StockQuantity,
    (SELECT ImageUrl FROM ProductImages WHERE ProductId = p.ProductId AND IsPrimary = 1 LIMIT 1) as PrimaryImage
    FROM Carts c
    JOIN Products p ON c.ProductId = p.ProductId
    WHERE c.UserId = ?
    ORDER BY c.AddedDate DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    if ($item['StockQuantity'] >= $item['Quantity']) {
        $subtotal += ($item['Price'] * $item['Quantity']);
    } else {
        // If stock is less than quantity, calculate only available stock or handle error
        // For simplicity, we fallback to max stock for subtotal if they proceed
        $subtotal += ($item['Price'] * min($item['Quantity'], $item['StockQuantity']));
    }
}
$shipping = $subtotal > 0 ? 10.00 : 0;
$total = $subtotal + $shipping;

include 'header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 4rem;">
    <h1 style="margin-bottom: 2rem;">Your Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="glass" style="padding: 4rem; text-align: center;">
            <h2 style="color: var(--text-muted); margin-bottom: 1.5rem;">Your cart is empty</h2>
            <a href="products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            
            <!-- Cart Items -->
            <div style="flex: 2; min-width: 300px;">
                <?php foreach ($cartItems as $item): ?>
                    <div class="glass cart-row" id="cart-row-<?= $item['CartId'] ?>" style="display: flex; padding: 1.5rem; margin-bottom: 1.5rem; gap: 1.5rem; align-items: center; justify-self: stretch;">
                        <img src="<?= $item['PrimaryImage'] ? $item['PrimaryImage'] : 'asset/image/default_product.png' ?>" loading="lazy" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                        
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 0.5rem 0;">
                                <a href="product_detail.php?id=<?= $item['ProductId'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($item['ProductName']) ?></a>
                            </h3>
                            <p style="margin: 0; color: var(--primary-color); font-weight: 600; font-size: 1.2rem;">$<?= number_format($item['Price'], 2) ?></p>
                            <?php if ($item['StockQuantity'] < $item['Quantity']): ?>
                                <p style="color: var(--danger-color); font-size: 0.875rem; margin-top: 0.5rem;">Only <?= $item['StockQuantity'] ?> left in stock!</p>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 1rem;">
                            <input type="number" onchange="updateCartQty('<?= $item['CartId'] ?>', this.value)" value="<?= $item['Quantity'] ?>" min="1" max="<?= $item['StockQuantity'] ?>" class="form-control" style="width: 70px; text-align: center;">
                            <button onclick="removeFromCart('<?= $item['CartId'] ?>')" class="btn btn-outline" style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.25rem 0.5rem; font-size: 0.8rem;">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div style="flex: 1; min-width: 300px;">
                <div class="glass" style="padding: 2rem; position: sticky; top: 100px;">
                    <h2 style="margin-top: 0;">Order Summary</h2>
                    <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span class="text-muted">Subtotal</span>
                        <span id="summary-subtotal" style="font-weight: 600;">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                        <span class="text-muted">Estimated Shipping</span>
                        <span>$<?= number_format($shipping, 2) ?></span>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <span style="font-size: 1.2rem; font-weight: bold;">Total</span>
                        <span id="summary-total" style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">$<?= number_format($total, 2) ?></span>
                    </div>

                    <a href="checkout.php" class="btn" style="width: 100%; text-align: center; padding: 1rem; font-size: 1.1rem; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; box-sizing: border-box;">Proceed to Checkout</a>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script>
function updateCartQty(cartId, qty) {
    if(qty < 1) return;
    fetch('cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&cart_id=${cartId}&quantity=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            window.location.reload(); // Reload to recalculate totals safely Server-side
        }
    });
}

function removeFromCart(cartId) {
    if(!confirm("Are you sure you want to remove this item?")) return;
    fetch('cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&cart_id=${cartId}`
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById(`cart-row-${cartId}`).remove();
            window.location.reload();
        }
    });
}
</script>

<?php include 'footer.php'; ?>
