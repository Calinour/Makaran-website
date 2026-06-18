<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$payments = $conn->query("
    SELECT p.*, u.name as customer_name, o.status as order_status 
    FROM payments p 
    JOIN orders o ON p.order_id = o.id 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Payments Transactions Audit';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>💳 Financial Transactions Log</h2>
        <p>Monitor customer checkouts, payments, and refunds</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash_message('error')): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Transaction Records (<?php echo count($payments); ?>)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tx ID</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Payment Method</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                    <th>Transaction Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr>
                <td style="font-family:monospace; font-weight:600;">TX-<?php echo str_pad($pay['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td style="font-weight:700;">#<?php echo $pay['order_id']; ?></td>
                <td><?php echo htmlspecialchars($pay['customer_name']); ?></td>
                <td style="text-transform:uppercase; font-size:0.85rem; font-weight:600;"><?php echo str_replace('_', ' ', $pay['payment_method']); ?></td>
                <td style="font-weight:700; color:var(--primary);">$<?php echo number_format($pay['amount'], 2); ?></td>
                <td>
                    <span class="badge badge-<?php echo $pay['status'] === 'completed' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($pay['status']); ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y g:i A', strtotime($pay['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>orders/receipt.php?id=<?php echo $pay['order_id']; ?>" class="btn-icon" title="View receipt">👁️</a>
                        <?php if ($pay['status'] === 'completed'): ?>
                        <a href="<?php echo BASE_URL; ?>payments/refunds.php?id=<?php echo $pay['id']; ?>" class="btn-icon delete" style="color:var(--danger);" title="Issue Refund" data-confirm="Issue a full refund for this transaction? This will mark the order as cancelled and reverse stock count.">↩️ Refund</a>
                        <?php else: ?>
                        <span style="font-size:0.85rem; color:var(--text-muted);">Refunded</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
            <tr>
                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No transaction logs available.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
