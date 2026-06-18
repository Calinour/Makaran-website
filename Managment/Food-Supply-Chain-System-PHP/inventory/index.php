<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
$user=get_logged_in_user();
// Filter by supplier if supplier role
$supplierFilter = ($user['role']==='supplier') ? "AND p.supplier_id={$user['id']}" : '';
$batches=$conn->query("SELECT ib.*,p.name as product_name FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE 1 $supplierFilter ORDER BY ib.expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);
$products=$conn->query("SELECT id,name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Inventory'; include __DIR__.'/../includes/header.php';
function statusBadge2($s){$m=['active'=>'success','expired'=>'danger','damaged'=>'danger']; return '<span class="badge badge-'.($m[$s]??'neutral').'">'.ucfirst($s).'</span>';}
$stats=['active'=>0,'expired'=>0,'damaged'=>0,'low_stock'=>0];
foreach($batches as $b){if(isset($stats[$b['status']]))$stats[$b['status']]++;if($b['quantity']<10&&$b['status']==='active')$stats['low_stock']++;}
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>🏪 Inventory</h2><p>Batch tracking and stock levels</p></div>
<a href="<?php echo BASE_URL; ?>inventory/add_stock.php" class="btn btn-primary">+ Add Stock</a></div>
<div class="dashboard-content">
<?php if($msg=get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card primary"><div><div class="stat-label">Active Batches</div><div class="stat-value"><?php echo $stats['active']; ?></div></div><div class="stat-icon">📦</div></div>
    <div class="stat-card danger"><div><div class="stat-label">Expired</div><div class="stat-value"><?php echo $stats['expired']; ?></div></div><div class="stat-icon">⚠️</div></div>
    <div class="stat-card warning"><div><div class="stat-label">Damaged</div><div class="stat-value"><?php echo $stats['damaged']; ?></div></div><div class="stat-icon">🚫</div></div>
    <div class="stat-card danger"><div><div class="stat-label">Low Stock (&lt;10)</div><div class="stat-value"><?php echo $stats['low_stock']; ?></div></div><div class="stat-icon">🔴</div></div>
</div>
<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="<?php echo BASE_URL; ?>inventory/expiry.php" class="btn btn-secondary">⏰ Expiry Alerts</a>
    <a href="<?php echo BASE_URL; ?>inventory/damaged.php" class="btn btn-secondary">🚫 Damaged Batches</a>
    <a href="<?php echo BASE_URL; ?>inventory/batches.php" class="btn btn-secondary">📋 All Batches</a>
</div>
<div class="table-container">
    <div class="table-header-bar"><div class="table-title">All Inventory Batches</div></div>
    <table class="admin-table">
        <thead><tr><th>Batch #</th><th>Product</th><th>Qty</th><th>Expiry</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($batches as $b): $expired=strtotime($b['expiry_date'])<time(); ?>
        <tr>
            <td style="font-weight:600;font-family:monospace;"><?php echo htmlspecialchars($b['batch_number']); ?></td>
            <td><?php echo htmlspecialchars($b['product_name']); ?></td>
            <td><?php $q=(int)$b['quantity']; echo '<span class="badge badge-'.($q<10?'danger':($q<30?'warning':'success')).'">'.$q.'</span>'; ?></td>
            <td><?php echo date('M j, Y',strtotime($b['expiry_date'])); ?><?php if($expired&&$b['status']==='active'): ?> <span class="badge badge-danger">EXPIRED</span><?php endif; ?></td>
            <td><?php echo statusBadge2($b['status']); ?></td>
            <td style="color:var(--text-muted);font-size:.85rem;"><?php echo htmlspecialchars(substr($b['notes']??'',0,40)); ?></td>
            <td>
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>inventory/update_stock.php?id=<?php echo $b['id']; ?>" class="btn-icon edit" title="Update">✏️</a>
                    <a href="<?php echo BASE_URL; ?>inventory/damaged.php?flag=<?php echo $b['id']; ?>" class="btn-icon delete" title="Mark Damaged" data-confirm="Mark this batch as damaged?">🚫</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
