<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role('admin');
$isAdminPage=true;
$id=(int)($_GET['id']??0);
$product=$conn->prepare("SELECT * FROM products WHERE id=?"); $product->execute([$id]); $product=$product->fetch(PDO::FETCH_ASSOC);
if(!$product){set_flash_message('error','Product not found.'); header('Location:'.BASE_URL.'products/index.php'); exit();}
$categories=$conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers=$conn->query("SELECT id,name FROM users WHERE role='supplier' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??''); $cat=(int)($_POST['category_id']??0); $supplier=(int)($_POST['supplier_id']??0)?:null;
    $description=trim($_POST['description']??''); $price=(float)($_POST['price']??0); $sku=trim($_POST['sku']??'');
    if(!$name||!$cat||$price<=0||!$sku){$error='Name, category, price and SKU are required.';}
    else{
        $chk=$conn->prepare("SELECT id FROM products WHERE sku=? AND id!=?"); $chk->execute([$sku,$id]);
        if($chk->fetch()){$error='Another product with this SKU exists.';}
        else{
            $imageUrl = $product['image_url'];
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['product_image']['tmp_name'];
                $fileName = $_FILES['product_image']['name'];
                $fileSize = $_FILES['product_image']['size'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($fileExtension, $allowedExtensions) && $fileSize <= 2 * 1024 * 1024) {
                    $uploadFileDir = __DIR__ . '/../assets/images/products/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                        $imageUrl = 'assets/images/products/' . $newFileName;
                    }
                }
            }

            $conn->prepare("UPDATE products SET category_id=?,supplier_id=?,name=?,description=?,price=?,sku=?,image_url=? WHERE id=?")->execute([$cat,$supplier,$name,$description,$price,$sku,$imageUrl,$id]);
            set_flash_message('success','Product updated.'); header('Location:'.BASE_URL.'products/index.php'); exit();
        }
    }
}
$pageTitle='Edit Product'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>✏️ Edit Product</h2><p><?php echo htmlspecialchars($product['name']); ?></p></div><a href="<?php echo BASE_URL; ?>products/index.php" class="btn btn-secondary">← Back</a></div>
<div class="dashboard-content">
<?php if($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="table-container" style="max-width:700px;padding:2rem;">
<form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Product Name *</label><input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($_POST['name']??$product['name']); ?>" required></div>
        <div class="form-group"><label class="form-label">Category *</label><select class="form-control" name="category_id" required><?php foreach($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo (($_POST['category_id']??$product['category_id'])==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Supplier</label><select class="form-control" name="supplier_id"><option value="">None</option><?php foreach($suppliers as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo (($_POST['supplier_id']??$product['supplier_id'])==$s['id'])?'selected':''; ?>><?php echo htmlspecialchars($s['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Price (USD) *</label><input class="form-control" type="number" step="0.01" min="0.01" name="price" value="<?php echo htmlspecialchars($_POST['price']??$product['price']); ?>" required></div>
        <div class="form-group"><label class="form-label">SKU *</label><input class="form-control" type="text" name="sku" value="<?php echo htmlspecialchars($_POST['sku']??$product['sku']); ?>" required></div>
        <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label">Product Image</label>
            <input class="form-control" type="file" name="product_image" accept="image/*">
            <?php if ($product['image_url']): ?>
                <div style="margin-top: 0.5rem;">
                    <img src="<?php echo BASE_URL . $product['image_url']; ?>" alt="Current Image" style="max-height: 80px; border-radius: 4px;">
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($_POST['description']??$product['description']); ?></textarea></div>
    </div>
    <div style="display:flex;gap:1rem;margin-top:1rem;"><button type="submit" class="btn btn-primary">💾 Save Changes</button><a href="<?php echo BASE_URL; ?>products/index.php" class="btn btn-secondary">Cancel</a></div>
</form>
</div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
