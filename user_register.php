<?php

include_once 'config/config.php';
require_once 'includes/image_helper.php';


// Generate CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF validation
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errors[] = "Invalid CSRF token. Please refresh the page and try again.";
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_pass'] ?? '';

    // Password Validation
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    // Input sanitization
    $first_name = trim(strip_tags($_POST['first_name'] ?? ''));
    $last_name = trim(strip_tags($_POST['last_name'] ?? ''));
    $phone_number = trim(strip_tags($_POST['phone_number'] ?? ''));

    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and Last name are required.";
    }
    if (empty($phone_number) || !preg_match('/^[0-9\-\+\s\(\)]+$/', $phone_number)) {
        $errors[] = "Please provide a valid phone number.";
    }

    $targetDir = "Uploads/avatars/";
    $defaultAvatar = "asset/image/default_avatar.png";
    $profilePhoto = $defaultAvatar;

    // File validation and upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error code: " . $_FILES['avatar']['error'];
        } else {
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $fileSize = $_FILES['avatar']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Max size: 2MB
            if ($fileSize > 2 * 1024 * 1024) {
                $errors[] = "Avatar file size must not exceed 2MB.";
            }

            // Image authenticity check
            $imageInfo = @getimagesize($fileTmpPath);
            if ($imageInfo === false) {
                $errors[] = "The uploaded file is not a valid image.";
            }

            $allowedExt = array("jpg", "jpeg", "png", "gif", "webp", "bmp", "svg", "heic", "tiff");
            if (!in_array($fileExtension, $allowedExt)) {
                $errors[] = "Invalid file type. Only standard image formats are allowed.";
            }

            if (empty($errors)) {
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $newFileName = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)), 4)) . "." . $fileExtension;
                $destPath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $webpPath = convertToWebP($destPath, $targetDir);
                    $profilePhoto = $webpPath;
                } else {
                    $errors[] = "Failed to save the uploaded image.";
                }
            }
        }
    } else {
        $errors[] = "Avatar image is required.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $userId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)), 4));
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $roleStmt = $pdo->prepare("SELECT RoleId FROM Roles WHERE RoleName = 'Member'");
            $roleStmt->execute();
            $role = $roleStmt->fetchColumn();

            $inUser = $pdo->prepare("INSERT INTO Users (UserId, Email, PasswordHash, RoleId, IsActive) VALUES (:userId, :email, :passwordHash, :roleId, :isActive)");

            $inUser->execute([
                ":userId" => $userId,
                ":email" => $email,
                ":passwordHash" => $passwordHash,
                ":roleId" => $role,
                ":isActive" => 1
            ]);

            $profileId = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex(random_bytes(16)), 4));
            
            $inUserProfile = $pdo->prepare("INSERT INTO UserProfile (ProfileId, UserId, FirstName, LastName, PhoneNumber, ProfilePhotoUrl) VALUES (:profileId, :userId, :firstName, :lastName, :phone, :photoUrl)");

            $inUserProfile->execute([
                ":profileId" => $profileId,
                ":userId" => $userId,
                ":firstName" => $first_name,
                ":lastName" => $last_name,
                ":phone" => $phone_number,
                ":photoUrl" => $profilePhoto
            ]);

            $pdo->commit();
            header("Location: user_login.php?registered=success");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                 $errors[] = "Registration failed. The email already exists.";
            } else {
                 $errors[] = "Registration failed. Please try again later.";
            }
        }
    }
}


?>
<?php include 'header.php'; ?>

<div class="container" style="max-width: 500px; margin-top: 4rem; margin-bottom: 4rem;">
    <div class="glass" style="padding: 2rem;">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Create Account</h2>
        
        <?php if(!empty($errors)): ?>
             <div class="alert alert-danger" style="text-align: left; background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #dc3545;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="user_register.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" placeholder="Enter your email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" placeholder="Enter your password" name="password" id="password" required>
                <div id="password-reqs" style="margin-top: 0.5rem; font-size: 0.85rem; display: none;">
                    <ul style="list-style-type: none; padding-left: 0; margin-bottom: 0;">
                        <li id="req-length" style="color: #dc3545;">❌ At least 8 characters</li>
                        <li id="req-upper" style="color: #dc3545;">❌ At least one uppercase letter</li>
                        <li id="req-lower" style="color: #dc3545;">❌ At least one lowercase letter</li>
                        <li id="req-num" style="color: #dc3545;">❌ At least one number</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_pass">Confirm Password</label>
                <input type="password" class="form-control" placeholder="Confirm your password" name="confirm_pass" id="confirm_pass" required>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
            
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>First Name</label>
                    <input type="text" class="form-control" placeholder="First Name" name="first_name" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Last Name</label>
                    <input type="text" class="form-control" placeholder="Last Name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" class="form-control" placeholder="Enter your Phone Number" name="phone_number" required>
            </div>
            
            <div class="form-group">
                <label>Avatar / Profile Photo</label>
                <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem; padding: 1rem; font-size: 1.1rem;">Register</button>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <span class="text-muted">Already have an account?</span> 
                <a href="user_login.php" style="color: var(--primary-color); font-weight: 500; text-decoration: none;">Login here</a>
            </div>
        </form>
    </div>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const reqBox = document.getElementById('password-reqs');
    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqLower = document.getElementById('req-lower');
    const reqNum = document.getElementById('req-num');

    passwordInput.addEventListener('focus', function() {
        reqBox.style.display = 'block';
    });

    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        
        // Check Length
        if (val.length >= 8) {
            reqLength.style.color = '#28a745';
            reqLength.innerText = '✅ At least 8 characters';
        } else {
            reqLength.style.color = '#dc3545';
            reqLength.innerText = '❌ At least 8 characters';
        }

        // Check Uppercase
        if (/[A-Z]/.test(val)) {
            reqUpper.style.color = '#28a745';
            reqUpper.innerText = '✅ At least one uppercase letter';
        } else {
            reqUpper.style.color = '#dc3545';
            reqUpper.innerText = '❌ At least one uppercase letter';
        }

        // Check Lowercase
        if (/[a-z]/.test(val)) {
            reqLower.style.color = '#28a745';
            reqLower.innerText = '✅ At least one lowercase letter';
        } else {
            reqLower.style.color = '#dc3545';
            reqLower.innerText = '❌ At least one lowercase letter';
        }

        // Check Number
        if (/[0-9]/.test(val)) {
            reqNum.style.color = '#28a745';
            reqNum.innerText = '✅ At least one number';
        } else {
            reqNum.style.color = '#dc3545';
            reqNum.innerText = '❌ At least one number';
        }
    });

    // Also check if confirm password matches
    const confirmInput = document.getElementById('confirm_pass');
    confirmInput.addEventListener('input', function() {
        if(confirmInput.value !== '' && confirmInput.value !== passwordInput.value) {
            confirmInput.style.borderColor = '#dc3545';
        } else if (confirmInput.value !== '') {
            confirmInput.style.borderColor = '#28a745';
        } else {
            confirmInput.style.borderColor = '';
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if(confirmInput.value !== '') {
            if(confirmInput.value !== passwordInput.value) {
                confirmInput.style.borderColor = '#dc3545';
            } else {
                confirmInput.style.borderColor = '#28a745';
            }
        }
    });
</script>

<?php include 'footer.php'; ?>