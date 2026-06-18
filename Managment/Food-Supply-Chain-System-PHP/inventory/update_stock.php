<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
$id=(int)($_GET['id']??0);
$batch=$conn->prepare("SELECT ib.*,p.name as product_name FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE ib.id=?");
$batch->execute([$id]); $batch=$batch->fetch(PDO::FETCH_ASSOC);
if(!$batch){set_flash_message('error','Batch not found.'); header('Location:'.BASE_URL.'inventory/index.php'); exit();}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $qty=(int)($_POST['quantity']??0); $status=$_POST['status']??'active'; $notes=trim($_POST['notes']??'');
    if($qty<0){$error='Quantity cannot be negative.';}
    else{$conn->prepare("UPDATE inventory_batches SET quantity=?,status=?,notes=? WHERE id=?")->execute([$qty,$status,$notes,$id]);
    set_flash_message('success','Batch updated.'); header('Location:'.BASE_URL.'inventory/index.php'); exit();}
}
$pageTitle='Update Stock'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>✏️ Update Batch</h2><p><?php echo htmlspecialchars($batch['product_name'].' — '.$batch['batch_number']); ?></p></div><a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">← Back</a></div>
<div class="dashboard-content">
<?php if($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="table-container" style="max-width:500px;padding:2rem;">
<form method="POST">
    <div class="form-group"><label class="form-label">Current Quantity</label><input class="form-control" type="number" name="quantity" min="0" value="<?php echo $batch['quantity']; ?>" required></div>
    <div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status"><option value="active" <?php echo $batch['status']==='active'?'selected':''; ?>>Active</option><option value="expired" <?php echo $batch['status']==='expired'?'selected':''; ?>>Expired</option><option value="damaged" <?php echo $batch['status']==='damaged'?'selected':''; ?>>Damaged</option></select></div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($batch['notes']??''); ?></textarea></div>
    <div style="display:flex;gap:1rem;"><button type="submit" class="btn btn-primary">💾 Save</button><a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">Cancel</a></div>
</form>
</div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
