<?php
/**
 * Image Helper for WebP conversion and Lazy Loading improvements.
 */

/**
 * Convert an image file to WebP format if GD is available.
 * 
 * @param string $sourcePath Path to the source image file.
 * @param string $targetDir Directory where the WebP image should be saved.
 * @param int $quality Quality of the WebP image (0-100).
 * @return string The path to the WebP image, or original path if conversion fails.
 */
function convertToWebP($sourcePath, $targetDir, $quality = 80) {
    if (!extension_loaded('gd')) {
        return $sourcePath; // GD not loaded, return original
    }

    $info = getimagesize($sourcePath);
    if (!$info) {
        return $sourcePath; // Not a valid image
    }

    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
    $webpPath = rtrim($targetDir, '/') . '/' . $baseName . '.webp';

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            // Handle transparency for PNG
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            return $sourcePath; // Already webp
        default:
            return $sourcePath; // Unsupported format
    }

    if (!$image) {
        return $sourcePath;
    }

    // Attempt to save as WebP
    if (imagewebp($image, $webpPath, $quality)) {
        imagedestroy($image);
        // Optionally delete the original file to save space
        if (file_exists($sourcePath) && $sourcePath !== $webpPath) {
            unlink($sourcePath);
        }
        return $webpPath;
    }

    imagedestroy($image);
    return $sourcePath;
}
?>
