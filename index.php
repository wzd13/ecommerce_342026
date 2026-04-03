<?php
include_once 'config/config.php';
include 'header.php';
?>

<div style="text-align: center; padding: 4rem 2rem; background: var(--card-bg); border-radius: 16px; margin-top: 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
    <h1 style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">Welcome to Zeng Store</h1>
    <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 2rem auto;">
        Experience premium quality products with an intuitive and modern shopping experience.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="products.php" class="btn" style="padding: 1rem 2rem; font-size: 1.1rem; border-radius: 8px;">Shop Now</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="user_register.php" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 1.1rem; border-radius: 8px;">Create Account</a>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top: 4rem; margin-bottom: 4rem;">
    <h2 style="text-align: center; margin-bottom: 2rem;">Why Choose Us?</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
        <div class="glass" style="padding: 2rem; text-align: center;">
            <h3 style="color: var(--primary-color);">Premium Quality</h3>
            <p class="text-muted">We source only the best materials to ensure our products exceed your expectations.</p>
        </div>
        <div class="glass" style="padding: 2rem; text-align: center;">
            <h3 style="color: var(--primary-color);">Fast Shipping</h3>
            <p class="text-muted">Get your items quickly with our trusted delivery partners.</p>
        </div>
        <div class="glass" style="padding: 2rem; text-align: center;">
            <h3 style="color: var(--primary-color);">Secure Checkout</h3>
            <p class="text-muted">Your payment and personal details are encrypted and entirely safe with us.</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>