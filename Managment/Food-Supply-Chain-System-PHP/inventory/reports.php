<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
$user=get_logged_in_user();
$supplierFilter=($user['role']==='supplier')?" AND p.supplier_id={$user['id']}":"";
$summary=$conn->query("SELECT p.name,
    SUM(CASE WHEN ib.status='active' THEN ib.quantity ELSE 0 END) as active_qty,
    SUM(CASE WHEN ib.status='expired' THEN ib.quantity ELSE 0 END) as expired_qty,
    SUM(CASE WHEN ib.status='damaged' THEN ib.quantity ELSE 0 END) as damaged_qty,
    COUNT(DISTINCT ib.id) as batch_count
    FROM products p LEFT JOIN inventory_batches ib ON ib.product_id=p.id WHERE 1$supplierFilter GROUP BY p.id,p.name ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Inventory Report'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>📊 Inventory Report</h2><p>Stock levels per product</p></div></div>
<div class="dashboard-content">
<div class="table-container">
<div class="table-header-bar"><div class="table-title">Product Stock Summary</div></div>
<table class="admin-table"><thead><tr><th>Product</th><th>Batches</th><th>Active Stock</th><th>Expired</th><th>Damaged</th><th>Total</th></tr></thead>
<tbody>
<?php foreach($summary as $r): $total=(int)$r['active_qty']+(int)$r['expired_qty']+(int)$r['damaged_qty']; ?>
<tr>
    <td style="font-weight:600;"><?php echo htmlspecialchars($r['name']); ?></td>
    <td><?php echo $r['batch_count']; ?></td>
    <td><span class="badge badge-<?php echo (int)$r['active_qty']<10?'danger':((int)$r['active_qty']<30?'warning':'success'); ?>"><?php echo (int)$r['active_qty']; ?></span></td>
    <td><?php echo (int)$r['expired_qty']>0?'<span class="badge badge-danger">'.(int)$r['expired_qty'].'</span>':'<span style="color:var(--text-muted);">0</span>'; ?></td>
    <td><?php echo (int)$r['damaged_qty']>0?'<span class="badge badge-warning">'.(int)$r['damaged_qty'].'</span>':'<span style="color:var(--text-muted);">0</span>'; ?></td>
    <td style="font-weight:700;"><?php echo $total; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
