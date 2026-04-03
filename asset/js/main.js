document.addEventListener("DOMContentLoaded", () => {
    // Setup generic AJAX handling for cart if needed.
    
    // Simple fade in animation for main content
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transition = 'opacity 0.5s ease-in-out';
        setTimeout(() => {
            mainContent.style.opacity = '1';
        }, 100);
    }
});

function addToCart(productId, quantity = 1) {
    if (!productId) return;
    
    fetch('cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Added to cart!');
            // Update cart badge if existing
            const cartBadge = document.getElementById('cart-badge');
            if (cartBadge) {
                cartBadge.innerText = data.cart_count;
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        alert('An error occurred. Please try again.');
    });
}
