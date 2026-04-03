<?php
require_once 'config/config.php';

try {
    // Read the SQL commands
    $sql = "
    -- Create Roles Table
    CREATE TABLE IF NOT EXISTS Roles (
        RoleId CHAR(36) PRIMARY KEY,
        RoleName VARCHAR(50) NOT NULL UNIQUE
    );

    -- Create Users Table
    CREATE TABLE IF NOT EXISTS Users (
        UserId CHAR(36) PRIMARY KEY,
        RoleId CHAR(36),
        Email VARCHAR(255) NOT NULL UNIQUE,
        PasswordHash VARCHAR(255) NOT NULL,
        IsActive BOOLEAN DEFAULT true,
        LastLogin DATETIME DEFAULT CURRENT_TIMESTAMP,
        ResetToken CHAR(36),
        ResetTokenExpiry DATETIME,
        CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (RoleId) REFERENCES Roles(RoleId)
    );

    -- Create UserProfile Table
    CREATE TABLE IF NOT EXISTS UserProfile (
        ProfileId CHAR(36) PRIMARY KEY,
        UserId CHAR(36) UNIQUE,
        FirstName VARCHAR(100),
        LastName VARCHAR(100),
        PhoneNumber VARCHAR(20),
        ProfilePhotoUrl VARCHAR(255),
        CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        UpdateDate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (UserId) REFERENCES Users(UserId) ON DELETE CASCADE
    );

    -- Create Addresses Table
    CREATE TABLE IF NOT EXISTS Addresses (
        AddressId CHAR(36) PRIMARY KEY,
        UserId CHAR(36),
        RecipientName VARCHAR(100),
        PhoneNumber VARCHAR(20),
        FullAddress TEXT,
        IsDefault BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (UserId) REFERENCES Users(UserId) ON DELETE CASCADE
    );

    -- Create Categories Table
    CREATE TABLE IF NOT EXISTS Categories (
        CategoryId CHAR(36) PRIMARY KEY,
        CategoryName VARCHAR(255) NOT NULL UNIQUE
    );

    -- Create Products Table
    CREATE TABLE IF NOT EXISTS Products (
        ProductId CHAR(36) PRIMARY KEY,
        CategoryId CHAR(36),
        ProductName VARCHAR(255) NOT NULL,
        Description TEXT,
        Price DECIMAL(10, 2) NOT NULL,
        StockQuantity INT DEFAULT 0,
        CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (CategoryId) REFERENCES Categories(CategoryId) ON DELETE SET NULL
    );

    -- Create ProductImages Table
    CREATE TABLE IF NOT EXISTS ProductImages (
        ImageId CHAR(36) PRIMARY KEY,
        ProductId CHAR(36),
        ImageUrl VARCHAR(255) NOT NULL,
        IsPrimary BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (ProductId) REFERENCES Products(ProductId) ON DELETE CASCADE
    );

    -- Create Carts Table
    CREATE TABLE IF NOT EXISTS Carts (
        CartId CHAR(36) PRIMARY KEY,
        UserId CHAR(36),
        ProductId CHAR(36),
        Quantity INT DEFAULT 1,
        AddedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (UserId) REFERENCES Users(UserId) ON DELETE CASCADE,
        FOREIGN KEY (ProductId) REFERENCES Products(ProductId) ON DELETE CASCADE
    );

    -- Create Orders Table
    CREATE TABLE IF NOT EXISTS Orders (
        OrderId CHAR(36) PRIMARY KEY,
        UserId CHAR(36),
        AddressId CHAR(36),
        TotalAmount DECIMAL(10, 2) NOT NULL,
        OrderStatus VARCHAR(50),
        OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        ShippingAddress TEXT,
        FOREIGN KEY (UserId) REFERENCES Users(UserId),
        FOREIGN KEY (AddressId) REFERENCES Addresses(AddressId) ON DELETE SET NULL
    );

    -- Create OrderItems Table
    CREATE TABLE IF NOT EXISTS OrderItems (
        OrderItemId CHAR(36) PRIMARY KEY,
        OrderId CHAR(36),
        ProductId CHAR(36),
        Quantity INT NOT NULL,
        UnitPrice DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (OrderId) REFERENCES Orders(OrderId) ON DELETE CASCADE,
        FOREIGN KEY (ProductId) REFERENCES Products(ProductId)
    );
    ";

    $pdo->exec($sql);
    echo "Tables created successfully.<br>";

    // Insert Default Roles if they don't exist
    $checkRoles = $pdo->query("SELECT COUNT(*) FROM Roles")->fetchColumn();
    if ($checkRoles == 0) {
        $insertRoles = "
        INSERT INTO Roles (RoleId, RoleName) VALUES (UUID(), 'Admin'), (UUID(), 'Member');
        ";
        $pdo->exec($insertRoles);
        echo "Default roles inserted.<br>";
    }

    echo "Database setup complete.";

} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>
