<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$suppliers = $conn->query("
    SELECT u.id, u.name, u.email, u.phone,
    (SELECT COUNT(*) FROM products WHERE supplier_id = u.id) as total_products,
    (SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = u.id) as total_purchase_orders,
    (SELECT COALESCE(AVG(rating), 0) FROM supplier_performance WHERE supplier_id = u.id) as avg_rating,
    (SELECT COALESCE(AVG(ontime_delivery_rate), 100) FROM supplier_performance WHERE supplier_id = u.id) as avg_ontime
    FROM users u 
    WHERE u.role = 'supplier' 
    ORDER BY avg_rating DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Suppliers Performance Audit Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚜 Supplier Partner Audit Logs</h2>
        <p>Analyze supplier delivery rates, quality ratings, and reorder volumes</p>
    </div>
</div>

<div class="dashboard-content">
    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Supplier Analytics Audits (<?php echo count($suppliers); ?> partners)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Supplier Partner</th>
                    <th>Email / Contact</th>
                    <th>Catalog Products</th>
                    <th>PO Orders Handled</th>
                    <th>Quality Score</th>
                    <th>On-Time Rate</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($s['name']); ?></td>
                    <td>
                        <div><?php echo htmlspecialchars($s['email']); ?></div>
                        <div style="font-size:0.85rem; color:var(--text-muted);">📞 <?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></div>
                    </td>
                    <td><span class="badge badge-info"><?php echo $s['total_products']; ?> items</span></td>
                    <td><?php echo $s['total_purchase_orders']; ?> orders</td>
                    <td style="font-weight:700; color:var(--primary);">
                        <?php 
                        $rating = (float)$s['avg_rating'];
                        echo $rating > 0 ? number_format($rating, 1) . ' ⭐' : '<span style="color:var(--text-muted); font-weight:normal;">Unrated</span>';
                        ?>
                    </td>
                    <td style="font-weight:700;">
                        <?php echo number_format((float)$s['avg_ontime'], 1); ?>%
                    </td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>suppliers/performance.php?supplier_id=<?php echo $s['id']; ?>" class="btn btn-secondary" style="font-size:0.8rem; padding:0.3rem 0.8rem;">📈 Audit Logs</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No supplier profiles registered.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
