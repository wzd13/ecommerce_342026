<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cartCount = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    $stmtCount = $pdo->prepare("SELECT SUM(Quantity) FROM Carts WHERE UserId = ?");
    $stmtCount->execute([$_SESSION['user_id']]);
    $cartCount = $stmtCount->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeng Store</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="asset/css/style.css">
</head>
<body>
    <header class="main-header glass" style="display: flex; align-items: center; padding: 1rem 2rem; justify-content: space-between;">
        <div class="brand-logo" style="flex: 1;">
            <a href="index.php">Zeng Store</a>
        </div>
        
        <div class="search-bar" style="flex: 2; display: flex; justify-content: center; padding: 0 1rem;">
            <form action="products.php" method="GET" style="display: flex; width: 100%; max-width: 400px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef;">
                <input type="text" name="search" placeholder="Search products..." style="flex: 1; border: none; padding: 0.5rem 1rem; outline: none; font-family: 'Inter', sans-serif;" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" style="border: none; background: transparent; padding: 0 1rem; color: #6f42c1; cursor: pointer;"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>

        <nav class="nav-links" style="flex: 2; justify-content: flex-end;">
            <a href="products.php">Shop</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                    <a href="admin/product_management.php" style="color: var(--danger-color); font-weight: bold;">Admin Panel</a>
                <?php endif; ?>
                <a href="cart.php">Cart <span id="cart-badge" style="background:var(--primary-color);color:white;border-radius:50%;padding:2px 6px;font-size:0.8rem; <?= $cartCount == 0 ? 'display:none;' : '' ?>"><?= $cartCount ?></span></a>
                <a href="UserProfile.php">Profile</a>
                <a href="user_addresses.php">Addresses</a>
                <a href="orders.php">Orders</a>
                <a href="user_login.php?logout=1" class="btn btn-outline" style="padding: 0.25rem 0.5rem;">Logout</a>
            <?php else: ?>
                <a href="user_login.php">Login</a>
                <a href="user_register.php" class="btn" style="padding: 0.25rem 0.5rem;">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
