<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['admin', 'driver']);
$isAdminPage = true;

$user = get_logged_in_user();
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, u.name as customer_name, d.name as driver_name 
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        LEFT JOIN users d ON o.driver_id = d.id 
        WHERE 1";
$params = [];

if ($user['role'] === 'driver') {
    $sql .= " AND o.driver_id = ?";
    $params[] = $user['id'];
}

if ($statusFilter) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (u.name LIKE ? OR o.id = ?)";
    $params[] = "%$search%";
    $params[] = (int)$search;
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Orders Management';
include __DIR__ . '/../includes/header.php';

function statusBadge($s) {
    $m = [
        'pending' => 'neutral',
        'paid' => 'info',
        'approved' => 'info',
        'assigned' => 'warning',
        'out_for_delivery' => 'warning',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return '<span class="badge badge-' . ($m[$s] ?? 'neutral') . '">' . str_replace('_', ' ', ucfirst($s)) . '</span>';
}
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🛒 Orders Management</h2>
        <p><?php echo $user['role'] === 'driver' ? 'Your assigned delivery orders' : 'Manage all customer orders and logistics'; ?></p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash_message('error')): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <input class="form-control" style="max-width:250px;" type="text" name="search" placeholder="🔍 Search customer or Order ID..." value="<?php echo htmlspecialchars($search); ?>">
        <select class="form-control" style="max-width:200px;" name="status">
            <option value="">All Statuses</option>
            <?php foreach (['pending','paid','approved','assigned','out_for_delivery','delivered','cancelled'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:.6rem 1.2rem;">Filter</button>
        <a href="<?php echo BASE_URL; ?>orders/index.php" class="btn btn-secondary" style="padding:.6rem 1.2rem;">Clear</a>
    </form>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Orders Listing (<?php echo count($orders); ?>)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Driver</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $ord): ?>
            <tr>
                <td style="font-weight:700;">#<?php echo $ord['id']; ?></td>
                <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                <td style="font-weight:700;color:var(--primary);">$<?php echo number_format($ord['total_amount'], 2); ?></td>
                <td><?php echo statusBadge($ord['status']); ?></td>
                <td><?php echo statusBadge($ord['payment_status']); ?></td>
                <td><?php echo $ord['driver_name'] ? htmlspecialchars($ord['driver_name']) : '<span style="color:var(--text-muted);">Unassigned</span>'; ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($ord['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $ord['id']; ?>" class="btn-icon" title="View details">👁️</a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <?php if ($ord['status'] === 'paid' || $ord['status'] === 'approved'): ?>
                            <a href="<?php echo BASE_URL; ?>orders/assign_driver.php?id=<?php echo $ord['id']; ?>" class="btn-icon edit" title="Assign Driver">🚚</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>orders/receipt.php?id=<?php echo $ord['id']; ?>" class="btn-icon" title="Receipt">Receipt</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr>
                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No orders found.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
