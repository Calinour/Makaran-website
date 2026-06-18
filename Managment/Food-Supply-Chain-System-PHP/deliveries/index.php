<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$deliveries = $conn->query("
    SELECT o.id as order_id, o.status, o.shipping_address, o.created_at, 
    u.name as customer_name, u.phone as customer_phone,
    d.name as driver_name, d.phone as driver_phone
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    LEFT JOIN users d ON o.driver_id = d.id 
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Master Logistics Tracker';
include __DIR__ . '/../includes/header.php';

function statusBadge($s) {
    $m = ['pending'=>'neutral','paid'=>'info','approved'=>'info','assigned'=>'warning','out_for_delivery'=>'warning','delivered'=>'success','cancelled'=>'danger'];
    return '<span class="badge badge-' . ($m[$s] ?? 'neutral') . '">' . str_replace('_', ' ', ucfirst($s)) . '</span>';
}
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚚 Master Logistics & Deliveries Tracker</h2>
        <p>Monitor real-time food delivery assignments and driver paths</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Logistics Log (<?php echo count($deliveries); ?>)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Destination</th>
                    <th>Delivery Status</th>
                    <th>Assigned Driver</th>
                    <th>Date Placed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($deliveries as $d): ?>
            <tr>
                <td style="font-weight:700;">#<?php echo $d['order_id']; ?></td>
                <td>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($d['customer_name']); ?></div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">📞 <?php echo htmlspecialchars($d['customer_phone'] ?? 'N/A'); ?></div>
                </td>
                <td style="font-size:0.9rem; max-width:220px;"><?php echo htmlspecialchars($d['shipping_address']); ?></td>
                <td><?php echo statusBadge($d['status']); ?></td>
                <td>
                    <?php if ($d['driver_name']): ?>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($d['driver_name']); ?></div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">📞 <?php echo htmlspecialchars($d['driver_phone'] ?? 'N/A'); ?></div>
                    <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.9rem;">Unassigned</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('M j, Y g:i A', strtotime($d['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $d['order_id']; ?>" class="btn-icon" title="View details">👁️</a>
                        <a href="<?php echo BASE_URL; ?>orders/assign_driver.php?id=<?php echo $d['order_id']; ?>" class="btn-icon edit" title="Reassign Driver">🚚</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deliveries)): ?>
            <tr>
                <td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No logistics entries available.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
