<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    print_r($_FILES);
    echo "</pre>";
    // Also log to a file
    file_put_contents('test_upload.log', print_r($_POST, true) . "\n" . print_r($_FILES, true));
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="test" value="123">
    <input type="file" name="product_images[]" multiple>
    <button type="submit">Upload</button>
</form>
