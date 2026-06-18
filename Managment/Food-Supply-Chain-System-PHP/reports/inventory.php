<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$inventory = $conn->query("
    SELECT p.id, p.name, p.sku, p.price, c.name as category_name, u.name as supplier_name,
    COALESCE(SUM(CASE WHEN ib.status='active' THEN ib.quantity ELSE 0 END), 0) as active_qty,
    COALESCE(SUM(CASE WHEN ib.status='expired' THEN ib.quantity ELSE 0 END), 0) as expired_qty,
    COALESCE(SUM(CASE WHEN ib.status='damaged' THEN ib.quantity ELSE 0 END), 0) as damaged_qty
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.supplier_id = u.id
    LEFT JOIN inventory_batches ib ON p.id = ib.product_id
    GROUP BY p.id, p.name, p.sku, p.price, c.name, u.name
    ORDER BY p.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$totalValuation = 0.00;
foreach ($inventory as $item) {
    $totalValuation += $item['price'] * $item['active_qty'];
}

$pageTitle = 'Inventory Valuation Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🏪 Inventory Valuation Report</h2>
        <p>Monitor current warehouse assets, stock level volumes, and asset valuations</p>
    </div>
</div>

<div class="dashboard-content">
    <!-- Valuation Widget -->
    <div class="stats-grid" style="margin-bottom:1.5rem; grid-template-columns: 1fr;">
        <div class="stat-card primary" style="justify-content:center; text-align:center; padding:2rem;">
            <div>
                <div class="stat-label" style="font-size:1.1rem;">Total Est. Active Stock Valuation</div>
                <div class="stat-value" style="font-size:3rem; color:var(--primary);">$<?php echo number_format($totalValuation, 2); ?></div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Inventory Valuation Log (<?php echo count($inventory); ?> products)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product Item</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Active Qty</th>
                    <th>Price</th>
                    <th>Est. Asset Value</th>
                    <th>Lost (Damaged/Exp)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory as $row): 
                    $value = $row['price'] * $row['active_qty'];
                    $lost = (int)$row['expired_qty'] + (int)$row['damaged_qty'];
                ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td style="font-family:monospace;"><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'None'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $row['active_qty'] < 10 ? 'danger' : ($row['active_qty'] < 30 ? 'warning' : 'success'); ?>">
                            <?php echo $row['active_qty']; ?> units
                        </span>
                    </td>
                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                    <td style="font-weight:700; color:var(--primary);">$<?php echo number_format($value, 2); ?></td>
                    <td>
                        <?php if ($lost > 0): ?>
                        <span class="badge badge-danger"><?php echo $lost; ?> units</span>
                        <?php else: ?>
                        <span style="color:var(--text-muted);">0</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
