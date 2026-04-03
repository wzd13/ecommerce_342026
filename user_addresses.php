<?php
require_once 'config/config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: user_login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        $recipient = $_POST['recipient_name'] ?? '';
        $phone = $_POST['phone_number'] ?? '';
        $address = $_POST['full_address'] ?? '';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        try {
            if ($isDefault) {
                // Remove default from other addresses
                $pdo->prepare("UPDATE Addresses SET IsDefault = 0 WHERE UserId = :uid")->execute([':uid' => $userId]);
            }
            // Add new address
            $stmt = $pdo->prepare("INSERT INTO Addresses (AddressId, UserId, RecipientName, PhoneNumber, FullAddress, IsDefault) VALUES (UUID(), :uid, :rname, :phone, :addr, :isdef)");
            $stmt->execute([
                ':uid' => $userId,
                ':rname' => $recipient,
                ':phone' => $phone,
                ':addr' => $address,
                ':isdef' => $isDefault
            ]);
            $message = "Address added successfully.";
        } catch (PDOException $e) {
            $error = "Error adding address: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_address'])) {
        $addressId = $_POST['address_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM Addresses WHERE AddressId = :aid AND UserId = :uid");
            $stmt->execute([':aid' => $addressId, ':uid' => $userId]);
            $message = "Address deleted.";
        } catch (PDOException $e) {
            $error = "Error deleting address.";
        }
    } elseif (isset($_POST['set_default'])) {
        $addressId = $_POST['address_id'];
        try {
            $pdo->prepare("UPDATE Addresses SET IsDefault = 0 WHERE UserId = :uid")->execute([':uid' => $userId]);
            $pdo->prepare("UPDATE Addresses SET IsDefault = 1 WHERE AddressId = :aid AND UserId = :uid")->execute([':aid' => $addressId, ':uid' => $userId]);
            $message = "Default address updated.";
        } catch(PDOException $e){
            $error = "Error updating default address.";
        }
    }
}

// Fetch Addresses
$stmt = $pdo->prepare("SELECT * FROM Addresses WHERE UserId = :uid ORDER BY IsDefault DESC");
$stmt->execute([':uid' => $userId]);
$addresses = $stmt->fetchAll();

include 'header.php';
?>

<div class="glass" style="padding: 2rem; margin-bottom: 2rem;">
    <h2>Manage Addresses</h2>
    <?php if($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <!-- Add New Address Form -->
        <div style="flex: 1; min-width: 300px;">
            <h3>Add New Address</h3>
            <form action="user_addresses.php" method="POST">
                <div class="form-group">
                    <label>Recipient Name</label>
                    <input type="text" name="recipient_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Full Address</label>
                    <textarea name="full_address" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                        <input type="checkbox" name="is_default" value="1">
                        Set as Default Address
                    </label>
                </div>
                <button type="submit" name="add_address" class="btn">Save Address</button>
            </form>
        </div>

        <!-- Saved Addresses -->
        <div style="flex: 2; min-width: 300px;">
            <h3>Saved Addresses</h3>
            <?php if(empty($addresses)): ?>
                <p class="text-muted">You haven't added any addresses yet.</p>
            <?php else: ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach($addresses as $addr): ?>
                        <div class="glass" style="padding: 1rem; border-left: 4px solid <?= $addr['IsDefault'] ? 'var(--primary-color)' : 'var(--border-color)' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <?= htmlspecialchars($addr['RecipientName']) ?>
                                        <?php if($addr['IsDefault']): ?>
                                            <span style="font-size: 0.75rem; background: var(--primary-color); color: white; padding: 2px 6px; border-radius: 4px;">Default</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p style="margin: 0.5rem 0;"><?= htmlspecialchars($addr['PhoneNumber']) ?></p>
                                    <p style="margin: 0; color: var(--text-muted);"><?= nl2br(htmlspecialchars($addr['FullAddress'])) ?></p>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if(!$addr['IsDefault']): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="address_id" value="<?= $addr['AddressId'] ?>">
                                        <button type="submit" name="set_default" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Set Default</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this address?');">
                                        <input type="hidden" name="address_id" value="<?= $addr['AddressId'] ?>">
                                        <button type="submit" name="delete_address" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
