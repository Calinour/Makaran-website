<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
$user=get_logged_in_user();
$supplierFilter=($user['role']==='supplier')?" AND p.supplier_id={$user['id']}":"";
// Batches expiring within 7 days
$soon=$conn->query("SELECT ib.*,p.name as product_name,DATEDIFF(ib.expiry_date,NOW()) as days_left FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE ib.status='active' AND ib.expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)$supplierFilter ORDER BY ib.expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);
// Already expired but still marked active
$expired=$conn->query("SELECT ib.*,p.name as product_name FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE ib.status='active' AND ib.expiry_date < NOW()$supplierFilter ORDER BY ib.expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Expiry Alerts'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>⏰ Expiry Alerts</h2><p>Batches needing urgent attention</p></div><a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">← Inventory</a></div>
<div class="dashboard-content">
<?php if(!empty($expired)): ?>
<div class="alert alert-error">⚠️ <?php echo count($expired); ?> batch(es) have already expired! Please remove them from active stock immediately.</div>
<?php endif; ?>

<!-- Expiring Soon -->
<div class="table-container" style="margin-bottom:1.5rem;">
    <div class="table-header-bar"><div class="table-title">⚡ Expiring Within 7 Days (<?php echo count($soon); ?>)</div></div>
    <table class="admin-table"><thead><tr><th>Batch #</th><th>Product</th><th>Qty</th><th>Expiry Date</th><th>Days Left</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($soon as $b): $days=(int)$b['days_left']; ?>
    <tr>
        <td style="font-weight:600;font-family:monospace;"><?php echo htmlspecialchars($b['batch_number']); ?></td>
        <td><?php echo htmlspecialchars($b['product_name']); ?></td>
        <td><span class="badge badge-warning"><?php echo $b['quantity']; ?> units</span></td>
        <td><?php echo date('M j, Y',strtotime($b['expiry_date'])); ?></td>
        <td><?php if($days<0): ?><span class="badge badge-danger">Expired!</span><?php elseif($days==0): ?><span class="badge badge-danger">Today!</span><?php else: ?><span class="badge badge-warning"><?php echo $days; ?> days</span><?php endif; ?></td>
        <td><a href="<?php echo BASE_URL; ?>inventory/damaged.php?flag=<?php echo $b['id']; ?>" class="btn-icon delete" data-confirm="Mark as damaged/expired?" title="Mark expired">🗑️</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($soon)): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">✅ No batches expiring within 7 days.</td></tr><?php endif; ?>
    </tbody></table>
</div>

<!-- Already Expired -->
<?php if(!empty($expired)): ?>
<div class="table-container">
    <div class="table-header-bar"><div class="table-title" style="color:var(--danger);">🚨 Already Expired — Still Active (<?php echo count($expired); ?>)</div></div>
    <table class="admin-table"><thead><tr><th>Batch #</th><th>Product</th><th>Qty</th><th>Expired On</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($expired as $b): ?>
    <tr style="background:rgba(239,68,68,.04);">
        <td style="font-weight:600;font-family:monospace;"><?php echo htmlspecialchars($b['batch_number']); ?></td>
        <td><?php echo htmlspecialchars($b['product_name']); ?></td>
        <td><span class="badge badge-danger"><?php echo $b['quantity']; ?> units</span></td>
        <td style="color:var(--danger);font-weight:600;"><?php echo date('M j, Y',strtotime($b['expiry_date'])); ?></td>
        <td><a href="<?php echo BASE_URL; ?>inventory/damaged.php?flag=<?php echo $b['id']; ?>" class="btn btn-danger" style="font-size:.8rem;padding:.3rem .7rem;" data-confirm="Mark as expired/damaged?">Remove</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
<?php endif; ?>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
