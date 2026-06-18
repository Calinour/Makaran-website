<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileSize = $_FILES['product_image']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize <= 2 * 1024 * 1024) {
                $uploadFileDir = __DIR__ . '/../assets/images/products/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imagePath = 'assets/images/products/' . $newFileName;
                    $stmt = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                    $stmt->execute([$imagePath, $productId]);
                    
                    set_flash_message('success', 'Image uploaded successfully!');
                } else {
                    set_flash_message('error', 'Error moving the uploaded file.');
                }
            } else {
                set_flash_message('error', 'Upload failed. Size must be under 2MB.');
            }
        } else {
            set_flash_message('error', 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.');
        }
    } else {
        set_flash_message('error', 'No file uploaded or upload error occurred.');
    }
    
    header('Location: ' . BASE_URL . 'products/index.php');
    exit();
}
?>
