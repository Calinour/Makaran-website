<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage=true;
$user=get_logged_in_user();
$pidFilter=(int)($_GET['product_id']??0);
$supplierFilter=($user['role']==='supplier')?" AND p.supplier_id={$user['id']}":"";
$sql="SELECT ib.*,p.name as product_name FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE 1$supplierFilter";
$params=[];
if($pidFilter){$sql.=" AND ib.product_id=?";$params[]=$pidFilter;}
$sql.=" ORDER BY ib.expiry_date ASC";
$stmt=$conn->prepare($sql);$stmt->execute($params);
$batches=$stmt->fetchAll(PDO::FETCH_ASSOC);
$products=$conn->query("SELECT id,name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Inventory Batches'; include __DIR__.'/../includes/header.php';
function sb($s){$m=['active'=>'success','expired'=>'danger','damaged'=>'danger'];return '<span class="badge badge-'.($m[$s]??'neutral').'">'.ucfirst($s).'</span>';}
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>📋 Inventory Batches</h2><p>All stock batches by product</p></div>
<a href="<?php echo BASE_URL; ?>inventory/add_stock.php" class="btn btn-primary">+ Add Batch</a></div>
<div class="dashboard-content">
<form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <select class="form-control" style="max-width:280px;" name="product_id">
        <option value="">All Products</option>
        <?php foreach($products as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $pidFilter==$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary" style="padding:.6rem 1.2rem;">Filter</button>
    <a href="<?php echo BASE_URL; ?>inventory/batches.php" class="btn btn-secondary" style="padding:.6rem 1.2rem;">Clear</a>
</form>
<div class="table-container">
    <div class="table-header-bar"><div class="table-title">Batches (<?php echo count($batches); ?>)</div></div>
    <table class="admin-table"><thead><tr><th>Batch #</th><th>Product</th><th>Qty</th><th>Expiry Date</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($batches as $b): $isExp=strtotime($b['expiry_date'])<time()&&$b['status']==='active'; ?>
    <tr style="<?php echo $isExp?'opacity:.7':''; ?>">
        <td style="font-weight:600;font-family:monospace;"><?php echo htmlspecialchars($b['batch_number']); ?></td>
        <td><?php echo htmlspecialchars($b['product_name']); ?></td>
        <td><?php $q=(int)$b['quantity']; echo '<span class="badge badge-'.($q<10?'danger':($q<30?'warning':'success')).'">'.$q.'</span>'; ?></td>
        <td><?php echo date('M j, Y',strtotime($b['expiry_date'])); ?><?php if($isExp): ?> <span class="badge badge-danger" style="font-size:.7rem;">EXPIRED</span><?php endif; ?></td>
        <td><?php echo sb($b['status']); ?></td>
        <td style="color:var(--text-muted);font-size:.85rem;max-width:200px;"><?php echo htmlspecialchars(substr($b['notes']??'',0,50)); ?></td>
        <td><div class="action-buttons">
            <a href="<?php echo BASE_URL; ?>inventory/update_stock.php?id=<?php echo $b['id']; ?>" class="btn-icon edit" title="Update">✏️</a>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($batches)): ?><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No batches found.</td></tr><?php endif; ?>
    </tbody></table>
</div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
