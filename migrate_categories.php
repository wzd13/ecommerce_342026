<?php
require_once 'config/config.php';

try {
    echo "Starting category migration...<br>";

    // 1. Create Categories table
    $createCategoriesSql = "
    CREATE TABLE IF NOT EXISTS Categories (
        CategoryId CHAR(36) PRIMARY KEY,
        CategoryName VARCHAR(255) NOT NULL UNIQUE
    );
    ";
    $pdo->exec($createCategoriesSql);
    echo "Categories table validated/created.<br>";

    // 2. Insert a Default Category if it doesn't exist
    $checkCategory = $pdo->query("SELECT COUNT(*) FROM Categories")->fetchColumn();
    $defaultCategoryId = null;
    if ($checkCategory == 0) {
        $defaultCategoryId = $pdo->query("SELECT UUID()")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO Categories (CategoryId, CategoryName) VALUES (?, 'Uncategorized')");
        $stmt->execute([$defaultCategoryId]);
        echo "Default 'Uncategorized' category inserted.<br>";
    } else {
        $defaultCategoryId = $pdo->query("SELECT CategoryId FROM Categories LIMIT 1")->fetchColumn();
    }

    // 3. Add CategoryId column to Products table
    // Check if the column exists
    $columnExists = false;
    $result = $pdo->query("SHOW COLUMNS FROM Products LIKE 'CategoryId'");
    if ($result->rowCount() > 0) {
        $columnExists = true;
    }

    if (!$columnExists) {
        // Add CategoryId column
        $alterProductsSql = "ALTER TABLE Products ADD COLUMN CategoryId CHAR(36);";
        $pdo->exec($alterProductsSql);
        echo "Added CategoryId column to Products table.<br>";

        // Update existing products to have the default category
        if ($defaultCategoryId) {
            $stmt = $pdo->prepare("UPDATE Products SET CategoryId = ? WHERE CategoryId IS NULL");
            $stmt->execute([$defaultCategoryId]);
            echo "Updated existing products to default category.<br>";
        }

        // Add foreign key constraint
        $addFkSql = "ALTER TABLE Products ADD CONSTRAINT FK_ProductCategory FOREIGN KEY (CategoryId) REFERENCES Categories(CategoryId) ON DELETE SET NULL;";
        $pdo->exec($addFkSql);
        echo "Added foreign key constraint to Products table.<br>";
    } else {
        echo "CategoryId column already exists in Products table.<br>";
    }

    echo "<br><strong>Migration completed successfully.</strong>";

} catch (PDOException $e) {
    die("Error during migration: " . $e->getMessage());
}
?>
