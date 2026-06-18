<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['admin','supplier']);
$isAdminPage = true;

$search = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);

$sql = "SELECT p.*,c.name as cat_name,u.name as supplier_name,
        (SELECT COALESCE(SUM(ib.quantity),0) FROM inventory_batches ib WHERE ib.product_id=p.id AND ib.status='active') as stock
        FROM products p JOIN categories c ON p.category_id=c.id LEFT JOIN users u ON p.supplier_id=u.id WHERE 1";
$params = [];

$user = get_logged_in_user();
if ($user['role'] === 'supplier') { $sql .= " AND p.supplier_id=?"; $params[] = $user['id']; }
if ($search) { $sql .= " AND p.name LIKE ?"; $params[] = "%$search%"; }
if ($catFilter) { $sql .= " AND p.category_id=?"; $params[] = $catFilter; }
$sql .= " ORDER BY p.name";
$stmt = $conn->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Products';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title"><h2>📦 Products</h2><p>Manage your food product catalogue</p></div>
    <?php if ($user['role']==='admin'): ?><a href="<?php echo BASE_URL; ?>products/create.php" class="btn btn-primary">+ Add Product</a><?php endif; ?>
</div>
<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <!-- Filter -->
    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <input class="form-control" style="max-width:250px;" type="text" name="search" placeholder="🔍 Search products..." value="<?php echo htmlspecialchars($search); ?>">
        <select class="form-control" style="max-width:200px;" name="cat">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $catFilter==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:.6rem 1.2rem;">Filter</button>
        <a href="<?php echo BASE_URL; ?>products/index.php" class="btn btn-secondary" style="padding:.6rem 1.2rem;">Clear</a>
    </form>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">All Products (<?php echo count($products); ?>)</div>
            <?php if ($user['role']==='admin'): ?>
            <a href="<?php echo BASE_URL; ?>products/categories.php" class="btn btn-secondary" style="font-size:.85rem;padding:.4rem .9rem;">🗂️ Categories</a>
            <?php endif; ?>
        </div>
        <table class="admin-table" id="products-table">
            <thead><tr><th>Name</th><th>Category</th><th>Supplier</th><th>Price</th><th>Stock</th><th>SKU</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): $stock=(int)$p['stock']; ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['cat_name']); ?></td>
                <td><?php echo htmlspecialchars($p['supplier_name'] ?? '—'); ?></td>
                <td>$<?php echo number_format($p['price'],2); ?></td>
                <td><?php echo '<span class="badge badge-'.($stock<10?'danger':($stock<30?'warning':'success')).'">'.$stock.' units</span>'; ?></td>
                <td style="color:var(--text-muted);font-size:.85rem;"><?php echo htmlspecialchars($p['sku']); ?></td>
                <td>
                    <div class="action-buttons">
                        <?php if ($user['role']==='admin'): ?>
                        <a href="<?php echo BASE_URL; ?>products/edit.php?id=<?php echo $p['id']; ?>" class="btn-icon edit" title="Edit">✏️</a>
                        <a href="<?php echo BASE_URL; ?>products/delete.php?id=<?php echo $p['id']; ?>" class="btn-icon delete" title="Delete" data-confirm="Delete this product permanently?">🗑️</a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>inventory/batches.php?product_id=<?php echo $p['id']; ?>" class="btn-icon" title="Batches">📋</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
