<?php
require_once '../config/config.php';
require_once '../includes/image_helper.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if (!isset($_GET['id'])) {
    header("Location: product_management.php");
    exit();
}

$productId = $_GET['id'];
$message = '';
$error = '';

// Handle Product Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['product_name'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    if (empty($categoryId)) $categoryId = null;
    $desc = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE Products SET CategoryId = ?, ProductName = ?, Description = ?, Price = ?, StockQuantity = ? WHERE ProductId = ?");
    if($stmt->execute([$categoryId, $name, $desc, $price, $stock, $productId])) {
        $message = "Product details updated successfully.";
    } else {
        $error = "Failed to update product details.";
    }
}

// Handle Image Set Primary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_primary'])) {
    $imgId = $_POST['image_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE ProductImages SET IsPrimary = 0 WHERE ProductId = ?")->execute([$productId]);
        $pdo->prepare("UPDATE ProductImages SET IsPrimary = 1 WHERE ImageId = ? AND ProductId = ?")->execute([$imgId, $productId]);
        $pdo->commit();
        $message = "Primary image updated.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update primary image.";
    }
}

// Handle Image Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imgId = $_POST['image_id'];
    // Get image url to delete file
    $stmt = $pdo->prepare("SELECT ImageUrl FROM ProductImages WHERE ImageId = ? AND ProductId = ?");
    $stmt->execute([$imgId, $productId]);
    $img = $stmt->fetch();
    
    if ($img) {
        $filePath = '../' . $img['ImageUrl'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $pdo->prepare("DELETE FROM ProductImages WHERE ImageId = ?")->execute([$imgId]);
        $message = "Image deleted successfully.";
        
        // If it was the only primary image, make another one primary if exists
        $checkPrimary = $pdo->prepare("SELECT COUNT(*) FROM ProductImages WHERE ProductId = ? AND IsPrimary = 1");
        $checkPrimary->execute([$productId]);
        if ($checkPrimary->fetchColumn() == 0) {
            $pdo->prepare("UPDATE ProductImages SET IsPrimary = 1 WHERE ProductId = ? LIMIT 1")->execute([$productId]);
        }
    }
}

// Handle New Image Uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
        $uploadDir = '../Uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // check if has primary
        $checkPrimary = $pdo->prepare("SELECT COUNT(*) FROM ProductImages WHERE ProductId = ? AND IsPrimary = 1");
        $checkPrimary->execute([$productId]);
        $hasPrimary = $checkPrimary->fetchColumn() > 0;
        
        $added = 0;
        $warnings = [];
        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmpName) {
            $fileName = $_FILES['new_images']['name'][$key];
            $errCode = $_FILES['new_images']['error'][$key];
            
            if ($errCode === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'tiff', 'avif'];
                if (in_array($ext, $allowed)) {
                    $newFileName = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4)) . "." . $ext;
                    $dest = $uploadDir . $newFileName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $webpPath = convertToWebP($dest, $uploadDir);
                        $imgId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
                        $dbPath = 'Uploads/products/' . basename($webpPath);
                        
                        $isPrimary = (!$hasPrimary && $added === 0) ? 1 : 0;
                        
                        $imgStmt = $pdo->prepare("INSERT INTO ProductImages (ImageId, ProductId, ImageUrl, IsPrimary) VALUES (?, ?, ?, ?)");
                        $imgStmt->execute([$imgId, $productId, $dbPath, $isPrimary]);
                        $added++;
                    } else {
                        $warnings[] = "Failed to save file for $fileName.";
                    }
                } else {
                    $warnings[] = "Invalid extension for $fileName.";
                }
            } else {
                $warnings[] = "Upload error $errCode for $fileName.";
            }
        }
        if ($added > 0) {
            $message = "$added image(s) uploaded successfully.";
            if(!empty($warnings)) $message .= " Warnings: " . implode(" ", $warnings);
        } else {
            $error = "No valid images were uploaded.";
            if(!empty($warnings)) $error .= " Reasons: " . implode(" ", $warnings);
        }
    }
}

// Fetch Product Details
$stmt = $pdo->prepare("SELECT * FROM Products WHERE ProductId = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch categories for dropdown
$stmtCat = $pdo->query("SELECT * FROM Categories ORDER BY CategoryName ASC");
$allCategories = $stmtCat->fetchAll();

// Fetch Images
$imgStmt = $pdo->prepare("SELECT * FROM ProductImages WHERE ProductId = ? ORDER BY IsPrimary DESC");
$imgStmt->execute([$productId]);
$images = $imgStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Edit Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../asset/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .sidebar { 
            width: 300px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            padding: 2rem; 
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
        .content { flex: 1; padding: 2rem; }
        .glass { background: white; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
        .btn-outline { background: transparent; border: 1px solid #6f42c1; color: #6f42c1; }
        .form-control { padding: 0.5rem; border: 1px solid #ced4da; border-radius: 5px; width: 100%; margin-bottom: 1rem; box-sizing: border-box; }
        .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .img-card { position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .img-card img { width: 100%; height: 150px; object-fit: cover; display: block; }
        .img-actions { padding: 0.5rem; background: #f8f9fa; display: flex; flex-direction: column; gap: 0.5rem; }
        .badge-primary { position: absolute; top: 0.5rem; left: 0.5rem; background: var(--primary-color); color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h1 style="color: #6f42c1; font-size: 2.5rem; font-weight: 700; margin: 0;">Edit Product</h1>
                <a href="product_management.php" class="btn btn-outline">Back to Products</a>
            </div>
            
            <?php if($message): ?><div class="alert alert-success" style="padding: 1rem; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #c3e6cb;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger" style="padding: 1rem; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div style="display: flex; gap: 2rem; align-items: flex-start;">
                
                <!-- Product Info Form -->
                <div class="glass" style="flex: 1;">
                    <h3 style="margin-top: 0; margin-bottom: 1rem; color: #333;">Product Details</h3>
                    <form method="POST">
                        <div style="display: flex; gap: 1rem;">
                            <div style="flex: 2;">
                                <label>Product Name</label>
                                <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['ProductName']) ?>" required>
                            </div>
                            <div style="flex: 1;">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">-- No Category --</option>
                                    <?php foreach($allCategories as $cat): ?>
                                        <option value="<?= $cat['CategoryId'] ?>" <?= ($product['CategoryId'] === $cat['CategoryId']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['CategoryName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="6" required><?= htmlspecialchars($product['Description']) ?></textarea>
                        
                        <div style="display: flex; gap: 1rem;">
                            <div style="flex: 1;">
                                <label>Price</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['Price'] ?>" required>
                            </div>
                            <div style="flex: 1;">
                                <label>Stock</label>
                                <input type="number" name="stock" class="form-control" value="<?= $product['StockQuantity'] ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_product" class="btn" style="width: 100%; margin-top: 1rem;">Save Changes</button>
                    </form>
                </div>

                <!-- Product Images Management -->
                <div class="glass" style="flex: 1.5;">
                    <h3 style="margin-top: 0; margin-bottom: 1rem; color: #333;">Manage Images</h3>
                    
                    <div class="img-grid" style="margin-bottom: 2rem;">
                        <?php foreach($images as $img): ?>
                            <div class="img-card">
                                <?php if($img['IsPrimary']): ?>
                                    <span class="badge-primary">Primary</span>
                                <?php endif; ?>
                                <img src="../<?= htmlspecialchars($img['ImageUrl']) ?>" alt="Product Image" loading="lazy">
                                <div class="img-actions">
                                    <?php if(!$img['IsPrimary']): ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="image_id" value="<?= $img['ImageId'] ?>">
                                            <button type="submit" name="set_primary" class="btn btn-outline" style="width: 100%; font-size: 0.8rem; padding: 0.4rem; cursor: pointer;">Set as Primary</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                        <input type="hidden" name="image_id" value="<?= $img['ImageId'] ?>">
                                        <button type="submit" name="delete_image" class="btn btn-danger" style="width: 100%; font-size: 0.8rem; padding: 0.4rem; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($images)): ?>
                            <p style="color: #666; grid-column: 1 / -1;">No images uploaded yet.</p>
                        <?php endif; ?>
                    </div>

                    <h4 style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1.5rem; color: #333;">Upload Additional Images</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" id="new_images_input" name="new_images[]" class="form-control" accept="image/*" multiple required>
                        <small class="text-muted" style="display: block; margin-bottom: 1rem;">Hold <strong>Ctrl/Cmd</strong> to select multiple files.</small>
                        <div id="new_image_preview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 1rem;"></div>
                        <button type="submit" name="upload_images" class="btn btn-outline" style="cursor: pointer;">Upload Images</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const fileInput = document.getElementById('new_images_input');
        const previewContainer = document.getElementById('new_image_preview');
        let selectedFiles = [];

        fileInput.addEventListener('change', function(e) {
            const newFiles = Array.from(e.target.files);
            
            newFiles.forEach(file => {
                if (file.type.match('image.*')) {
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
                imgDiv.style.width = '60px';
                imgDiv.style.height = '60px';
                imgDiv.style.borderRadius = '6px';
                imgDiv.style.overflow = 'hidden';
                imgDiv.style.border = '1px solid #ccc';
                imgDiv.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                
                const img = document.createElement('img');
                const reader = new FileReader();
                reader.onload = e => img.src = e.target.result;
                reader.readAsDataURL(file);
                
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';

                const removeBtn = document.createElement('button');
                removeBtn.innerHTML = '&times;';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '2px';
                removeBtn.style.right = '2px';
                removeBtn.style.background = 'rgba(255,0,0,0.8)';
                removeBtn.style.color = 'white';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '16px';
                removeBtn.style.height = '16px';
                removeBtn.style.fontSize = '12px';
                removeBtn.style.lineHeight = '14px';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.padding = '0';
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
