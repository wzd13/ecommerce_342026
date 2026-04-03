<?php
require_once 'config/config.php';
$stmt = $pdo->query("SELECT * FROM ProductImages");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($images);

$stmt2 = $pdo->query("SELECT p.ProductId, p.ProductName, (SELECT ImageUrl FROM ProductImages WHERE ProductId = p.ProductId AND IsPrimary = 1 LIMIT 1) as PrimaryImage FROM Products p");
$products = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($products);
