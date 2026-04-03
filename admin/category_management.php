<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

$message = '';
$error = '';

// Handle Create Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');

    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        try {
            $categoryId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
            $stmt = $pdo->prepare("INSERT INTO Categories (CategoryId, CategoryName) VALUES (?, ?)");
            $stmt->execute([$categoryId, $name]);
            $message = "Category added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate entry)
                $error = "Category already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Categories WHERE CategoryId = ?");
        $stmt->execute([$delId]);
        $message = "Category deleted successfully!";
        header("Location: category_management.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Fetch existing categories
$stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM Products WHERE CategoryId = c.CategoryId) as ProductCount FROM Categories c ORDER BY c.CategoryName ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Category Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../asset/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .sidebar { 
            width: 300px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            padding: 2rem; 
            border-right: none; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); 
            color: white; 
            display: flex; 
            flex-direction: column; 
        }
        .sidebar h2 { margin: 0 0 2rem 0; font-size: 1.8rem; font-weight: 700; }
        .sidebar nav a { 
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            padding: 0.75rem 1rem; 
            border-radius: 10px; 
            transition: all 0.3s ease; 
            display: block; 
            margin-bottom: 0.5rem; 
        }
        .sidebar nav a:hover, .sidebar nav a.active { 
            background: rgba(255,255,255,0.2); 
            color: white; 
        }
        .content { flex: 1; padding: 2rem; background-color: transparent; }
        .glass { 
            background: white; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-radius: 20px; 
            padding: 2rem; 
            margin-bottom: 2rem; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 0.75rem 1.5rem; 
            border-radius: 10px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s ease; 
            cursor: pointer;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
        .btn-danger:hover { box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4); }
        .btn-warning { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: #333; }
        h1 { color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .form-control { 
            padding: 0.75rem; 
            border: 1px solid #ced4da; 
            border-radius: 8px; 
            width: 100%; 
            margin-top: 0.5rem;
        }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { font-weight: 600; color: #6c757d; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 2rem;">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="category_management.php" class="active">Categories</a>
                <a href="product_management.php">Products</a>
                <a href="order_management.php">Orders</a>
                <a href="member_management.php">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 2rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <h1>Manage Categories</h1>
            
            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3>Add New Category</h3>
                <form method="POST">
                    <div style="display: flex; gap: 1rem; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label style="font-weight: 600; color: #495057;">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Electronics, Clothing..." required>
                        </div>
                        <button type="submit" name="add_category" class="btn">Add Category</button>
                    </div>
                </form>
            </div>

            <div class="glass">
                <h3>Existing Categories</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Products Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= htmlspecialchars($cat['CategoryName']) ?></td>
                                <td><span style="background: #e9ecef; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;"><?= $cat['ProductCount'] ?></span></td>
                                <td>
                                    <?php if ($cat['CategoryName'] !== 'Uncategorized'): ?>
                                    <a href="category_edit.php?id=<?= $cat['CategoryId'] ?>" class="btn btn-warning" style="padding: 0.4rem 1rem; font-size: 0.85rem; margin-right: 0.5rem; text-decoration: none;">Edit</a>
                                    <a href="?delete=<?= $cat['CategoryId'] ?>" class="btn btn-danger" style="padding: 0.4rem 1rem; font-size: 0.85rem; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this category? Products in this category will become Uncategorized or have NULL category.');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #6c757d; padding: 2rem;">No categories found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
