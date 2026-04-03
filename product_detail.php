<?php
require_once 'config/config.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$productId = $_GET['id'];

// Fetch Product details
$stmt = $pdo->prepare("SELECT * FROM Products WHERE ProductId = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch all images for this product
$imgStmt = $pdo->prepare("SELECT ImageUrl, IsPrimary FROM ProductImages WHERE ProductId = ? ORDER BY IsPrimary DESC");
$imgStmt->execute([$productId]);
$images = $imgStmt->fetchAll();

include 'header.php';
?>

<div class="glass flex-container" style="display: flex; gap: 3rem; padding: 2rem; margin-top: 2rem;">
    
    <!-- Image Gallery -->
    <div style="flex: 1;">
        <?php $primaryImage = !empty($images) ? $images[0]['ImageUrl'] : 'asset/image/default_product.png'; ?>
        <img id="main-image" src="<?= htmlspecialchars($primaryImage) ?>" loading="lazy" style="width: 100%; height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        
        <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem;">
            <?php foreach($images as $img): ?>
                <img src="<?= htmlspecialchars($img['ImageUrl']) ?>" class="thumbnail" loading="lazy" onclick="document.getElementById('main-image').src=this.src" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: border 0.3s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="this.style.borderColor='transparent'">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Product Info -->
    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <h1 style="font-size: 2.5rem; margin-top: 0; color: var(--text-main); margin-bottom: 1rem;"><?= htmlspecialchars($product['ProductName']) ?></h1>
        <div class="product-price" style="font-size: 2rem; margin-bottom: 1rem;">$<?= number_format($product['Price'], 2) ?></div>
        
        <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.8; margin-bottom: 2rem;">
            <?= nl2br(htmlspecialchars($product['Description'])) ?>
        </p>

        <div style="margin-bottom: 2rem;">
            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: <?= $product['StockQuantity'] > 0 ? '#d1fae5' : '#fee2e2' ?>; color: <?= $product['StockQuantity'] > 0 ? '#065f46' : '#991b1b' ?>; border-radius: 20px; font-weight: 500; font-size: 0.875rem;">
                <?= $product['StockQuantity'] > 0 ? 'In Stock (' . $product['StockQuantity'] . ' available)' : 'Out of Stock' ?>
            </span>
        </div>

        <?php if($product['StockQuantity'] > 0): ?>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <input type="number" id="qty" value="1" min="1" max="<?= $product['StockQuantity'] ?>" class="form-control" style="width: 80px; font-size: 1.25rem; text-align: center;">
                <button onclick="addToCart('<?= $product['ProductId'] ?>', document.getElementById('qty').value)" class="btn" style="flex: 1; font-size: 1.1rem; padding: 1rem; border-radius: 8px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">
                    Add to Cart
                </button>
            </div>
        <?php else: ?>
            <button class="btn" style="width: 100%; background: var(--text-muted); cursor: not-allowed; padding: 1rem;" disabled>Out of Stock</button>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
