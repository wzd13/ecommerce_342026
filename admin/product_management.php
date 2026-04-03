<?php
require_once '../config/config.php';
require_once '../includes/image_helper.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    if (empty($categoryId)) $categoryId = null;
    $desc = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;

    try {
        $pdo->beginTransaction();
        
        $productId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
        $stmt = $pdo->prepare("INSERT INTO Products (ProductId, CategoryId, ProductName, Description, Price, StockQuantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productId, $categoryId, $name, $desc, $price, $stock]);

        // Handle Image Uploads
        $warnings = [];
        if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
            $uploadDir = '../Uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $isPrimary = 1; // First image uploaded will be primary
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmpName) {
                $fileName = $_FILES['product_images']['name'][$key];
                $error = $_FILES['product_images']['error'][$key];
                
                if ($error === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'tiff', 'avif'];
                    if (in_array($ext, $allowed)) {
                        $newFileName = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4)) . "." . $ext;
                        $dest = $uploadDir . $newFileName;
                            if (move_uploaded_file($tmpName, $dest)) {
                                $webpPath = convertToWebP($dest, $uploadDir);
                                $imgId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
                                // Store relative path from domain root or just 'Uploads/products/...'
                                $dbPath = 'Uploads/products/' . basename($webpPath);
                                $imgStmt = $pdo->prepare("INSERT INTO ProductImages (ImageId, ProductId, ImageUrl, IsPrimary) VALUES (?, ?, ?, ?)");
                                $imgStmt->execute([$imgId, $productId, $dbPath, $isPrimary]);
                                $isPrimary = 0; // Only first is primary
                            } else {
                            $warnings[] = "Failed to move uploaded file for $fileName.";
                        }
                    } else {
                        $warnings[] = "Invalid file extension for $fileName.";
                    }
                } else {
                    $warnings[] = "Upload failed with error code $error for $fileName.";
                }
            }
        }

        $pdo->commit();
        $message = "Product added successfully!";
        if (!empty($warnings)) {
            $message .= " However, there were image issues: " . implode(" ", $warnings);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch categories for dropdown
$stmtCat = $pdo->query("SELECT * FROM Categories ORDER BY CategoryName ASC");
$allCategories = $stmtCat->fetchAll();

// Fetch existing products
$stmt = $pdo->query("SELECT p.*, c.CategoryName, (SELECT ImageUrl FROM ProductImages WHERE ProductId = p.ProductId AND IsPrimary = 1 LIMIT 1) as PrimaryImage FROM Products p LEFT JOIN Categories c ON p.CategoryId = c.CategoryId ORDER BY p.CreateDate DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Product Management</title>
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
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .btn-danger { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); 
        }
        .btn-danger:hover { 
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4); 
        }
        h1 { color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .form-control { 
            padding: 0.5rem; 
            border: 1px solid #ced4da; 
            border-radius: 5px; 
            width: 100%; 
        }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; }
        .product-card { 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            overflow: hidden; 
            transition: transform 0.3s ease; 
        }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { width: 100%; height: 200px; object-fit: cover; }
        .product-info { padding: 1rem; }
        .product-name { font-weight: 600; color: #6f42c1; margin: 0; }
        .product-price { color: #28a745; font-weight: 600; }
        .product-stock { color: #6c757d; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 2rem;">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="category_management.php">Categories</a>
                <a href="product_management.php" class="active">Products</a>
                <a href="order_management.php">Orders</a>
                <a href="member_management.php">Members</a>
                <a href="../index.php">Back to Store</a>
                <a href="../user_login.php?logout=1" class="btn btn-danger" style="text-align: center; margin-top: 2rem;">Logout</a>
            </nav>
        </div>
        <div class="content container">
            <h1>Manage Products</h1>
            <?php if($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            
            <div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
                <h3>Add New Product</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex:2;">
                            <label>Product Name</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- No Category --</option>
                                <?php foreach($allCategories as $cat): ?>
                                    <option value="<?= $cat['CategoryId'] ?>"><?= htmlspecialchars($cat['CategoryName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex:1;">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Product Images (Select multiple)</label>
                        <input type="file" id="product_images_input" name="product_images[]" class="form-control" accept="image/*" multiple required>
                        <small class="text-muted">The first image will be used as the primary display image. Hold <strong>Ctrl/Cmd</strong> to select multiple files.</small>
                        <div id="image_preview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;"></div>
                    </div>
                    <button type="submit" name="add_product" class="btn">Add Product</button>
                </form>
            </div>

            <h3>Existing Products</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach($products as $prod): ?>
                    <div class="glass" style="display: flex; gap: 1rem; padding: 1rem;">
                        <img src="<?= $prod['PrimaryImage'] ? '../' . $prod['PrimaryImage'] : '../asset/image/default_product.png' ?>" loading="lazy" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <div>
                            <h4 style="margin: 0; font-size: 1rem;"><?= htmlspecialchars($prod['ProductName']) ?></h4>
                            <p style="margin: 0; color: #6c757d; font-size: 0.85rem;"><?= htmlspecialchars($prod['CategoryName'] ?? 'Uncategorized') ?></p>
                            <p style="margin: 0.25rem 0 0 0; color: var(--primary-color); font-weight: bold;">$<?= number_format($prod['Price'], 2) ?></p>
                            <p style="margin: 0; font-size: 0.875rem; margin-bottom: 0.5rem;">Stock: <?= $prod['StockQuantity'] ?></p>
                            <a href="product_edit.php?id=<?= $prod['ProductId'] ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; border: 1px solid #6f42c1; color: #ffffffff; border-radius: 4px; text-decoration: none; display: inline-block;">Edit Product</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        const fileInput = document.getElementById('product_images_input');
        const previewContainer = document.getElementById('image_preview');
        let selectedFiles = [];

        fileInput.addEventListener('change', function(e) {
            const newFiles = Array.from(e.target.files);
            
            newFiles.forEach(file => {
                if (file.type.match('image.*')) {
                    // Avoid duplicates by name (simple check)
                    if (!selectedFiles.find(f => f.name === file.name)) {
                        selectedFiles.push(file);
                    }
                }
            });
            
            renderPreviews();
            updateFileInput();
        });

        function renderPreviews() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const imgDiv = document.createElement('div');
                imgDiv.style.position = 'relative';
                imgDiv.style.width = '80px';
                imgDiv.style.height = '80px';
                imgDiv.style.borderRadius = '8px';
                imgDiv.style.overflow = 'hidden';
                imgDiv.style.border = index === 0 ? '2px solid #6f42c1' : '1px solid #ccc';
                imgDiv.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                
                const img = document.createElement('img');
                const reader = new FileReader();
                reader.onload = e => img.src = e.target.result;
                reader.readAsDataURL(file);
                
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                
                if(index === 0) {
                    const badge = document.createElement('span');
                    badge.textContent = 'Primary';
                    badge.style.position = 'absolute';
                    badge.style.bottom = '0';
                    badge.style.left = '0';
                    badge.style.width = '100%';
                    badge.style.background = '#6f42c1';
                    badge.style.color = 'white';
                    badge.style.fontSize = '10px';
                    badge.style.textAlign = 'center';
                    badge.style.padding = '2px 0';
                    badge.style.fontWeight = 'bold';
                    imgDiv.appendChild(badge);
                }

                const removeBtn = document.createElement('button');
                removeBtn.innerHTML = '&times;';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '2px';
                removeBtn.style.right = '2px';
                removeBtn.style.background = 'rgba(255,0,0,0.8)';
                removeBtn.style.color = 'white';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '18px';
                removeBtn.style.height = '18px';
                removeBtn.style.fontSize = '12px';
                removeBtn.style.lineHeight = '14px';
                removeBtn.style.cursor = 'pointer';
                removeBtn.onclick = function(ev) {
                    ev.preventDefault();
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                    updateFileInput();
                };
                
                imgDiv.appendChild(img);
                imgDiv.appendChild(removeBtn);
                previewContainer.appendChild(imgDiv);
            });
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }
    </script>
</body>
</html>
