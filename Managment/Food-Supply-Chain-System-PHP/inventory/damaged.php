<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
// Flag a batch as damaged via GET
if(isset($_GET['flag'])){
    $bid=(int)$_GET['flag'];
    if($bid){$conn->prepare("UPDATE inventory_batches SET status='damaged' WHERE id=?")->execute([$bid]); set_flash_message('success','Batch marked as damaged.');}
    header('Location:'.BASE_URL.'inventory/damaged.php'); exit();
}
$user=get_logged_in_user();
$supplierFilter=($user['role']==='supplier')?" AND p.supplier_id={$user['id']}":"";
$batches=$conn->query("SELECT ib.*,p.name as product_name FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE ib.status='damaged'$supplierFilter ORDER BY ib.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Damaged Batches'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>🚫 Damaged Batches</h2><p>Batches flagged as damaged or unusable</p></div><a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">← Inventory</a></div>
<div class="dashboard-content">
<?php if($msg=get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<div class="table-container">
    <div class="table-header-bar"><div class="table-title">🚫 Damaged Batches (<?php echo count($batches); ?>)</div></div>
    <table class="admin-table"><thead><tr><th>Batch #</th><th>Product</th><th>Qty Lost</th><th>Recorded</th><th>Notes</th></tr></thead>
    <tbody>
    <?php foreach($batches as $b): ?>
    <tr>
        <td style="font-weight:600;font-family:monospace;"><?php echo htmlspecialchars($b['batch_number']); ?></td>
        <td><?php echo htmlspecialchars($b['product_name']); ?></td>
        <td><span class="badge badge-danger"><?php echo $b['quantity']; ?> units</span></td>
        <td><?php echo date('M j, Y',strtotime($b['created_at'])); ?></td>
        <td style="color:var(--text-muted);"><?php echo htmlspecialchars($b['notes']??'—'); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($batches)): ?><tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No damaged batches recorded. ✅</td></tr><?php endif; ?>
    </tbody></table>
</div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
