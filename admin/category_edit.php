<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: category_management.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Categories SET CategoryName = ? WHERE CategoryId = ?");
            if ($stmt->execute([$name, $id])) {
                $message = "Category updated successfully.";
            } else {
                $error = "Failed to update category.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Another category with this name already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM Categories WHERE CategoryId = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category || $category['CategoryName'] === 'Uncategorized') {
    die("Category not found or cannot be edited.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Edit Category</title>
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
            color: rgba(255,255,255,0.8); text-decoration: none; padding: 0.75rem 1rem; 
            border-radius: 10px; transition: all 0.3s ease; display: block; margin-bottom: 0.5rem; 
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .content { flex: 1; padding: 2rem; background-color: transparent; }
        .glass { background: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s ease; cursor: pointer;}
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
        .btn-outline { background: transparent; border: 1px solid #6f42c1; color: #6f42c1; }
        h1 { color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .form-control { padding: 0.75rem; border: 1px solid #ced4da; border-radius: 8px; width: 100%; margin-top: 0.5rem; box-sizing: border-box;}
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 style="margin: 0;">Edit Category</h1>
                <a href="category_management.php" class="btn btn-outline" style="text-decoration: none;">Back to Categories</a>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="glass" style="max-width: 600px;">
                <form method="POST">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600; color: #495057; display: block;">Category Name</label>
                        <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($category['CategoryName']) ?>" required>
                    </div>
                    <button type="submit" name="update_category" class="btn" style="width: 100%;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
