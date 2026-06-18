<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['admin', 'driver']);
$isAdminPage = true;

$user = get_logged_in_user();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, d.name as driver_name, d.phone as driver_phone 
                        FROM orders o 
                        JOIN users u ON o.customer_id = u.id 
                        LEFT JOIN users d ON o.driver_id = d.id 
                        WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    set_flash_message('error', 'Order not found.');
    header('Location: ' . BASE_URL . 'orders/index.php');
    exit();
}

// Check driver access permission
if ($user['role'] === 'driver' && $order['driver_id'] !== $user['id']) {
    set_flash_message('error', 'Access denied to this order.');
    header('Location: ' . BASE_URL . 'orders/index.php');
    exit();
}

$itemsStmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.sku FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$paymentsStmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
$paymentsStmt->execute([$orderId]);
$payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Status or Notes Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_logistics'])) {
        $status = $_POST['status'] ?? $order['status'];
        $notes = trim($_POST['delivery_notes'] ?? '');
        
        $up = $conn->prepare("UPDATE orders SET status = ?, delivery_notes = ? WHERE id = ?");
        $up->execute([$status, $notes, $orderId]);
        
        set_flash_message('success', 'Order logistics and status updated successfully.');
        header('Location: ' . BASE_URL . 'orders/details.php?id=' . $orderId);
        exit();
    }
}

$pageTitle = 'Order #' . $orderId . ' Details';
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
        <h2>🧾 Order Details #<?php echo $orderId; ?></h2>
        <p>Created on <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
    </div>
    <div style="display:flex;gap:.75rem;">
        <a href="<?php echo BASE_URL; ?>orders/receipt.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">🧾 View Receipt</a>
        <a href="<?php echo BASE_URL; ?>orders/index.php" class="btn btn-secondary">← Back</a>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem; align-items:start;">
        <!-- Left Column: Details -->
        <div>
            <!-- Order Items Card -->
            <div class="table-container" style="margin-bottom:1.5rem;">
                <div class="table-header-bar"><div class="table-title">Ordered Items</div></div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td style="font-family:monospace;"><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="font-weight:700;color:var(--primary);">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;font-weight:700;padding:1rem;">Total Amount:</td>
                            <td style="font-weight:800;font-size:1.1rem;color:var(--primary);padding:1rem;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payments History Card -->
            <div class="table-container">
                <div class="table-header-bar"><div class="table-title">Transaction & Payments</div></div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>#<?php echo $pay['id']; ?></td>
                            <td><?php echo strtoupper(str_replace('_', ' ', $pay['payment_method'])); ?></td>
                            <td style="font-weight:600;">$<?php echo number_format($pay['amount'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $pay['status'] === 'completed' ? 'success' : 'danger'; ?>"><?php echo ucfirst($pay['status']); ?></span></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($pay['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($payments)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No payments recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Status & Customer & Driver -->
        <div>
            <!-- Logistics Form -->
            <div class="table-container" style="padding:1.5rem; margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem;">🚚 Update Status</h3>
                <form method="POST">
                    <input type="hidden" name="update_logistics" value="1">
                    <div class="form-group">
                        <label class="form-label" for="status">Order Status</label>
                        <select class="form-control" name="status" id="status">
                            <?php 
                            $statuses = ['pending', 'paid', 'approved', 'assigned', 'out_for_delivery', 'delivered', 'cancelled'];
                            foreach ($statuses as $st): 
                            ?>
                            <option value="<?php echo $st; ?>" <?php echo $order['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="delivery_notes">Delivery & Route Notes</label>
                        <textarea class="form-control" name="delivery_notes" id="delivery_notes" rows="3"><?php echo htmlspecialchars($order['delivery_notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">💾 Save Logistics</button>
                </form>
            </div>

            <!-- Customer Card -->
            <div class="table-container" style="padding:1.5rem; margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem;">👤 Customer Details</h3>
                <p style="font-weight:600; margin-bottom:0.25rem;"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p style="font-size:.9rem; color:var(--text-muted); margin-bottom:0.25rem;">📧 <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <p style="font-size:.9rem; color:var(--text-muted); margin-bottom:1rem;">📞 <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                <h4 style="font-size:0.95rem; font-weight:700; margin-bottom:0.5rem;">Shipping Address</h4>
                <p style="font-size:.9rem; color:var(--text-muted); line-height:1.4;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>

            <!-- Driver Card -->
            <div class="table-container" style="padding:1.5rem;">
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem;">🚚 Assigned Logistics Driver</h3>
                <?php if ($order['driver_id']): ?>
                <p style="font-weight:600; margin-bottom:0.25rem;"><?php echo htmlspecialchars($order['driver_name']); ?></p>
                <p style="font-size:.9rem; color:var(--text-muted);">📞 <?php echo htmlspecialchars($order['driver_phone'] ?? 'N/A'); ?></p>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>orders/assign_driver.php?id=<?php echo $orderId; ?>" class="btn btn-secondary" style="font-size:.85rem; padding:.3rem .8rem; margin-top:.75rem; width:100%; text-align:center;">Change Driver</a>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">No driver assigned to this delivery.</p>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>orders/assign_driver.php?id=<?php echo $orderId; ?>" class="btn btn-primary" style="font-size:.85rem; padding:.4rem 1rem; width:100%; text-align:center;">🚚 Assign Driver Now</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
