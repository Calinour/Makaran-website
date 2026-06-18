<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

// Fetch low stock items (quantity < 10)
$lowStock = $conn->query("
    SELECT p.id, p.name, p.sku, p.price, p.supplier_id, c.name as category_name, u.name as supplier_name,
    COALESCE(SUM(CASE WHEN ib.status='active' THEN ib.quantity ELSE 0 END), 0) as active_qty
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.supplier_id = u.id
    LEFT JOIN inventory_batches ib ON p.id = ib.product_id
    GROUP BY p.id, p.name, p.sku, p.price, p.supplier_id, c.name, u.name
    HAVING active_qty < 10
    ORDER BY active_qty ASC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Low Stock Reorder Alerts';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>⚠️ Low Stock Reorder Alerts</h2>
        <p>Monitor products with critical inventory counts and generate purchase orders</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Reorder Alerts (<?php echo count($lowStock); ?> products)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product Item</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier Partner</th>
                    <th>Available Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lowStock as $row): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td style="font-family:monospace;"><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'No supplier connected'); ?></td>
                    <td style="font-weight:700; color:var(--danger);"><?php echo $row['active_qty']; ?> units</td>
                    <td>
                        <span class="badge badge-danger">
                            <?php echo $row['active_qty'] == 0 ? 'Out of Stock' : 'Critical Low'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['supplier_id']): ?>
                        <a href="<?php echo BASE_URL; ?>suppliers/purchase_orders.php?supplier_id=<?php echo $row['supplier_id']; ?>" class="btn btn-primary" style="font-size:0.8rem; padding:0.3rem 0.8rem;">📦 Order Restock</a>
                        <?php else: ?>
                        <span style="color:var(--text-muted); font-size:0.85rem;">No Supplier Link</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lowStock)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--primary);padding:2rem;">🎉 Excellent! All products have healthy stock levels.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
