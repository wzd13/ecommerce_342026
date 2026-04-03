<?php
require_once 'config/config.php';

$categoryId = $_GET['category'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

// Fetch categories for filter
$stmtCat = $pdo->query("SELECT * FROM Categories ORDER BY CategoryName ASC");
$allCategories = $stmtCat->fetchAll();

// Fetch all categories for the 'Shop by Category' section (since we don't need images anymore, we just fetch them directly)
$stmtCatIcons = $pdo->query("
    SELECT * 
    FROM Categories 
    WHERE CategoryName != 'Uncategorized' 
    ORDER BY CategoryName ASC
");
$categoriesWithIcons = $stmtCatIcons->fetchAll();

// Construct base query for fetching products
$sql = "
    SELECT p.*, 
    (SELECT ImageUrl FROM ProductImages WHERE ProductId = p.ProductId AND IsPrimary = 1 LIMIT 1) as PrimaryImage 
    FROM Products p 
    WHERE 1=1
";
$params = [];

// Apply category filter
if (!empty($categoryId)) {
    $sql .= " AND p.CategoryId = ?";
    $params[] = $categoryId;
}

// Apply search filter
if (!empty($searchQuery)) {
    $sql .= " AND (p.ProductName LIKE ? OR p.Description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$sql .= " ORDER BY CreateDate DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'header.php';
?>

<?php if(empty($searchQuery)): ?>
    <!-- Shop by Category Section -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 2.5rem; color: var(--primary-color); text-align: center; margin-bottom: 2rem;">Shop by Category</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.5rem;">
            <!-- All Products Card -->
            <a href="products.php" style="text-decoration: none; color: inherit; display: block;">
                <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; <?= empty($categoryId) ? 'border: 2px solid var(--primary-color);' : '' ?>" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(111, 66, 193, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)';">
                    <div style="height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, <?= empty($categoryId) ? '#6f42c1 0%, #8e2de2 100%' : '#fdfbfb 0%, #ebedee 100%' ?>); border-bottom: 2px solid var(--primary-color);">
                        <div style="width: 70px; height: 70px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 12px;">
                            <i class="fa-solid fa-list" style="font-size: 2rem; color: var(--primary-color);"></i>
                        </div>
                        <h3 style="margin: 0; color: <?= empty($categoryId) ? 'white' : '#495057' ?>; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">All Products</h3>
                    </div>
                </div>
            </a>
            
            <?php foreach($categoriesWithIcons as $cat): ?>
                <?php
                    $iconClass = 'fa-solid fa-box'; // default
                    $catNameLower = strtolower($cat['CategoryName']);
                    if (strpos($catNameLower, 'electronic') !== false || strpos($catNameLower, 'tech') !== false || strpos($catNameLower, 'computer') !== false) $iconClass = 'fa-solid fa-laptop';
                    elseif (strpos($catNameLower, 'cloth') !== false || strpos($catNameLower, 'apparel') !== false || strpos($catNameLower, 'fashion') !== false) $iconClass = 'fa-solid fa-shirt';
                    elseif (strpos($catNameLower, 'book') !== false) $iconClass = 'fa-solid fa-book';
                    elseif (strpos($catNameLower, 'home') !== false || strpos($catNameLower, 'furniture') !== false) $iconClass = 'fa-solid fa-couch';
                    elseif (strpos($catNameLower, 'sport') !== false || strpos($catNameLower, 'fitness') !== false) $iconClass = 'fa-solid fa-dumbbell';
                    elseif (strpos($catNameLower, 'beauty') !== false || strpos($catNameLower, 'cosmetic') !== false) $iconClass = 'fa-solid fa-spa';
                    elseif (strpos($catNameLower, 'toy') !== false || strpos($catNameLower, 'game') !== false) $iconClass = 'fa-solid fa-gamepad';
                    elseif (strpos($catNameLower, 'food') !== false || strpos($catNameLower, 'grocery') !== false) $iconClass = 'fa-solid fa-apple-whole';
                    elseif (strpos($catNameLower, 'car') !== false || strpos($catNameLower, 'auto') !== false) $iconClass = 'fa-solid fa-car';
                    
                    $isActive = ($categoryId === $cat['CategoryId']);
                ?>
                <a href="products.php?category=<?= $cat['CategoryId'] ?>" style="text-decoration: none; color: inherit; display: block;">
                    <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; <?= $isActive ? 'border: 2px solid var(--primary-color);' : '' ?>" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(111, 66, 193, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)';">
                        <div style="height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, <?= $isActive ? '#6f42c1 0%, #8e2de2 100%' : '#fdfbfb 0%, #ebedee 100%' ?>); border-bottom: 2px solid var(--primary-color);">
                            <div style="width: 70px; height: 70px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 12px;">
                                <i class="<?= $iconClass ?>" style="font-size: 2rem; color: var(--primary-color);"></i>
                            </div>
                            <h3 style="margin: 0; color: <?= $isActive ? 'white' : '#495057' ?>; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($cat['CategoryName']) ?></h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div style="text-align: center; margin-bottom: 2rem;">
    <?php if(!empty($searchQuery)): ?>
        <h1 style="font-size: 2.5rem; color: var(--primary-color);">Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h1>
        <p class="text-muted">Showing results matching your search query.</p>
    <?php else: ?>
        <h1 style="font-size: 2.5rem; color: var(--primary-color);"><?= empty($categoryId) ? 'Our Latest Collection' : 'Filtered Products' ?></h1>
        <p class="text-muted"><?= empty($categoryId) ? 'Discover premium quality products crafted just for you.' : 'Viewing products in selected category.' ?></p>
    <?php endif; ?>
</div>



<div class="product-grid">
    <?php foreach($products as $product): ?>
        <a href="product_detail.php?id=<?= $product['ProductId'] ?>" style="text-decoration: none; color: inherit;">
            <div class="product-card glass">
                <?php $img = $product['PrimaryImage'] ? htmlspecialchars($product['PrimaryImage']) : 'asset/image/default_product.png'; ?>
                <img src="<?= $img ?>" class="product-img" alt="<?= htmlspecialchars($product['ProductName']) ?>" loading="lazy">
                <div class="product-title"><?= htmlspecialchars($product['ProductName']) ?></div>
                <div class="product-price">$<?= number_format($product['Price'], 2) ?></div>
                <?php if($product['StockQuantity'] > 0): ?>
                    <button class="btn btn-outline" style="width: 100%; border-radius: 20px; text-transform: uppercase; font-size: 0.8rem; font-weight: bold;">View Details</button>
                <?php else: ?>
                    <button class="btn" style="width: 100%; background: var(--text-muted); cursor: not-allowed;" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
    <?php if(empty($products)): ?>
        <p style="text-align: center; grid-column: 1 / -1; color: var(--text-muted);">No products available at the moment.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
