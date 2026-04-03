<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to add items to cart.']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (empty($productId) || $quantity < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity.']);
        exit();
    }

    try {
        // Get product stock
        $stockStmt = $pdo->prepare("SELECT StockQuantity FROM Products WHERE ProductId = ?");
        $stockStmt->execute([$productId]);
        $stock = $stockStmt->fetchColumn();

        if ($stock === false) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            exit();
        }

        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT CartId, Quantity FROM Carts WHERE UserId = ? AND ProductId = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();

        $currentInCart = $existing ? $existing['Quantity'] : 0;
        $newTotalQuantity = $currentInCart + $quantity;

        if ($newTotalQuantity > $stock) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot add more items. Only ' . ($stock - $currentInCart) . ' available.']);
            exit();
        }

        if ($existing) {
            $update = $pdo->prepare("UPDATE Carts SET Quantity = ? WHERE CartId = ?");
            $update->execute([$newTotalQuantity, $existing['CartId']]);
        } else {
            $cartId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
            $insert = $pdo->prepare("INSERT INTO Carts (CartId, UserId, ProductId, Quantity) VALUES (?, ?, ?, ?)");
            $insert->execute([$cartId, $userId, $productId, $quantity]);
        }

        // Get total cart count
        $countStmt = $pdo->prepare("SELECT SUM(Quantity) as total FROM Carts WHERE UserId = ?");
        $countStmt->execute([$userId]);
        $count = $countStmt->fetchColumn() ?: 0;

        echo json_encode(['status' => 'success', 'cart_count' => $count]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }

} elseif ($action === 'update') {
    $cartId = $_POST['cart_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE Carts SET Quantity = ? WHERE CartId = ? AND UserId = ?");
        $stmt->execute([$quantity, $cartId, $userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM Carts WHERE CartId = ? AND UserId = ?");
        $stmt->execute([$cartId, $userId]);
    }
    
    // Calculate new total
    $totalStmt = $pdo->prepare("
        SELECT SUM(c.Quantity * p.Price) as TotalAmount 
        FROM Carts c 
        JOIN Products p ON c.ProductId = p.ProductId 
        WHERE c.UserId = ?
    ");
    $totalStmt->execute([$userId]);
    $totalAmount = $totalStmt->fetchColumn() ?: 0;

    echo json_encode(['status' => 'success', 'total_amount' => number_format($totalAmount, 2)]);

} elseif ($action === 'remove') {
    $cartId = $_POST['cart_id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM Carts WHERE CartId = ? AND UserId = ?");
    $stmt->execute([$cartId, $userId]);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
