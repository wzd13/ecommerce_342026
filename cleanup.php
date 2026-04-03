<?php
require_once 'config/config.php';
$stmt = $pdo->query("SELECT * FROM ProductImages");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as $img) {
    if (strpos($img['ImageUrl'], 'Uploads/products/') !== false) {
        $path = $img['ImageUrl'];
        if (file_exists($path)) {
            $size = filesize($path);
            if ($size == 1024) {
                echo "Deleting fake image: " . $path . "\n";
                unlink($path);
                $pdo->prepare("DELETE FROM ProductImages WHERE ImageId = ?")->execute([$img['ImageId']]);
            }
        }
    }
}
@unlink('t1.jpg');
@unlink('t2.jpg');
@unlink('t3.jpg');
@unlink('test_upload.php');
@unlink('test_upload_loop.php');
@unlink('test_edit_upload.php');
@unlink('check.php');
@unlink('check3.php');
