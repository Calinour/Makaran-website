<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$bestSelling = $conn->query("
    SELECT p.id, p.name, p.sku, p.price, c.name as category_name, u.name as supplier_name,
    COALESCE(SUM(oi.quantity), 0) as units_sold,
    COALESCE(SUM(oi.quantity * oi.price), 0) as gross_revenue
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.supplier_id = u.id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id, p.name, p.sku, p.price, c.name, u.name
    ORDER BY units_sold DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Best Selling Produce Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🥗 Best Selling Fresh Produce</h2>
        <p>Analyze top performing food products by sales volumes and gross margins</p>
    </div>
</div>

<div class="dashboard-content">
    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Top 20 Best Selling Products</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Item</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Units Sold</th>
                    <th>Gross Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                foreach ($bestSelling as $row): 
                ?>
                <tr>
                    <td style="font-weight:700; color:var(--primary); font-size:1.1rem; width:60px;">#<?php echo $rank++; ?></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td style="font-family:monospace;"><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? '—'); ?></td>
                    <td style="font-weight:700;"><?php echo $row['units_sold']; ?> units</td>
                    <td style="font-weight:700; color:var(--primary);">$<?php echo number_format($row['gross_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bestSelling)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No sales logs recorded yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
