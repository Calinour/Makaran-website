<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('driver');
$isAdminPage = true;

$user = get_logged_in_user();

// Fetch assigned deliveries
$stmt = $conn->prepare("
    SELECT o.id as order_id, o.status, o.shipping_address, o.created_at, o.delivery_notes,
    u.name as customer_name, u.phone as customer_phone
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    WHERE o.driver_id = ? 
    ORDER BY o.updated_at DESC
");
$stmt->execute([$user['id']]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Assigned Deliveries';
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
        <h2>🚚 My Assigned Deliveries</h2>
        <p>Manage and log details for your assigned food logistics routes</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">My Deliveries List (<?php echo count($deliveries); ?>)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer Name</th>
                    <th>Destination</th>
                    <th>Status</th>
                    <th>Date Assigned</th>
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
                <td style="font-size:0.9rem; max-width:260px;"><?php echo htmlspecialchars($d['shipping_address']); ?></td>
                <td><?php echo statusBadge($d['status']); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($d['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $d['order_id']; ?>" class="btn btn-secondary" style="font-size:0.8rem; padding:0.3rem 0.7rem;">👁️ View Detail</a>
                        <a href="<?php echo BASE_URL; ?>deliveries/update_status.php?id=<?php echo $d['order_id']; ?>" class="btn btn-primary" style="font-size:0.8rem; padding:0.3rem 0.7rem;">✏️ Update Status</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deliveries)): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No delivery assignments found.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
