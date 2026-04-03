<?php
require_once 'config/config.php';
require_once 'includes/image_helper.php';


if(!isset($_SESSION['user_id'])){
    header("Location: user_login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Form Submission for Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
    if($_POST['action'] === 'update_profile') {
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $phone = $_POST['phone_number'] ?? '';

        try {
            // Check if profile exists
            $stmtCheck = $pdo->prepare("SELECT ProfileId FROM UserProfile WHERE UserId = ?");
            $stmtCheck->execute([$userId]);
            
            if ($stmtCheck->rowCount() == 0) {
                // Insert new profile
                $profileId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4));
                $stmt = $pdo->prepare("INSERT INTO UserProfile (ProfileId, UserId, FirstName, LastName, PhoneNumber) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$profileId, $userId, $firstName, $lastName, $phone]);
            } else {
                // Update existing profile
                $stmt = $pdo->prepare("UPDATE UserProfile SET FirstName = :fname, LastName = :lname, PhoneNumber = :phone WHERE UserId = :userId");
                $stmt->execute([
                    ':fname' => $firstName,
                    ':lname' => $lastName,
                    ':phone' => $phone,
                    ':userId' => $userId
                ]);
            }

            // Handle file upload if provided
            if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK){
                $targetDir = "Uploads/avatars/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowedExt = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "svg", "heic", "tiff"];
                if(in_array($fileExtension, $allowedExt)){
                    $newFileName = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)),4)) . "." . $fileExtension;
                    $destPath = $targetDir . $newFileName;
                    if(move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)){
                        $webpPath = convertToWebP($destPath, $targetDir);
                        $stmtImg = $pdo->prepare("UPDATE UserProfile SET ProfilePhotoUrl = :url WHERE UserId = :userId");
                        $stmtImg->execute([':url' => $webpPath, ':userId' => $userId]);
                    }
                }
            }

            $message = "Profile updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch current user and profile data
$stmt = $pdo->prepare("
    SELECT u.Email, u.CreatedDate, p.FirstName, p.LastName, p.PhoneNumber, p.ProfilePhotoUrl 
    FROM Users u 
    LEFT JOIN UserProfile p ON u.UserId = p.UserId 
    WHERE u.UserId = :userId
");
$stmt->execute([':userId' => $userId]);
$user = $stmt->fetch();

include 'header.php';
?>

<div class="glass" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
    <h2>My Profile</h2>
    <?php if($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 2rem;">
        <?php 
            $avatar = !empty($user['ProfilePhotoUrl']) ? htmlspecialchars($user['ProfilePhotoUrl']) : "asset/image/default_avatar.png"; 
            // Append timestamp to break browser cache if the file was just updated
            $avatar_with_cache_buster = $avatar . "?t=" . time();
        ?>
        <img src="<?= $avatar_with_cache_buster ?>" alt="Avatar" loading="lazy" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); background-color: var(--border-color);">
        <p><strong>Email:</strong> <?= htmlspecialchars($user['Email'] ?? '') ?></p>
        <p><small class="text-muted">Member since: <?= htmlspecialchars($user['CreatedDate'] ?? '') ?></small></p>
    </div>

    <form action="UserProfile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['FirstName'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['LastName'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['PhoneNumber'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Update Profile Photo</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
    </form>
</div>

<?php include 'footer.php'; ?>