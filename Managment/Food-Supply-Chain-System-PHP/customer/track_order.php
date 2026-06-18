<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = get_logged_in_user();
$orderId = (int)($_GET['id'] ?? 0);

// Customers can only view their own orders; admins/drivers can see all
if ($user['role'] === 'customer') {
    $stmt = $conn->prepare("SELECT o.*,u.name as driver_name,u.phone as driver_phone FROM orders o LEFT JOIN users u ON o.driver_id=u.id WHERE o.id=? AND o.customer_id=?");
    $stmt->execute([$orderId, $user['id']]);
} else {
    $stmt = $conn->prepare("SELECT o.*,u.name as driver_name,u.phone as driver_phone FROM orders o LEFT JOIN users u ON o.driver_id=u.id WHERE o.id=?");
    $stmt->execute([$orderId]);
}
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { echo "Order not found."; exit(); }

$items = $conn->prepare("SELECT oi.*,p.name as product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
$items->execute([$orderId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

// Steps definition
$steps = [
    ['key'=>'pending',          'label'=>'Order Placed',     'icon'=>'📋'],
    ['key'=>'paid',             'label'=>'Payment Confirmed', 'icon'=>'💳'],
    ['key'=>'approved',         'label'=>'Approved',         'icon'=>'✅'],
    ['key'=>'out_for_delivery', 'label'=>'Out for Delivery',  'icon'=>'🚚'],
    ['key'=>'delivered',        'label'=>'Delivered',         'icon'=>'🎉'],
];
$statusOrder = ['pending'=>0,'paid'=>1,'approved'=>2,'assigned'=>3,'out_for_delivery'=>3,'delivered'=>4,'cancelled'=>-1];
$currentStep = $statusOrder[$order['status']] ?? 0;
$progressPct = $currentStep > 0 ? min(100, ($currentStep / (count($steps)-1)) * 100) : 0;

$pageTitle = 'Track Order #' . $orderId;
include __DIR__ . '/../includes/header.php';
?>
<style>
.track-wrapper{max-width:860px;margin:2rem auto;padding:0 1.5rem;}
</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="track-wrapper">
    <?php if ($msg = get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-size:2rem;font-weight:800;">📍 Track Order #<?php echo $orderId; ?></h1>
            <p style="color:var(--text-muted);">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>
        <div style="display:flex;gap:.75rem;">
            <a href="<?php echo BASE_URL; ?>orders/receipt.php?id=<?php echo $orderId; ?>" class="btn btn-secondary" style="font-size:.9rem;">🧾 Receipt</a>
            <a href="<?php echo BASE_URL; ?>customer/orders.php" class="btn btn-secondary" style="font-size:.9rem;">← All Orders</a>
        </div>
    </div>

    <?php if ($order['status'] === 'cancelled'): ?>
    <div class="alert alert-error">❌ This order was cancelled.</div>
    <?php else: ?>

    <!-- Progress Tracker -->
    <div class="tracking-container" style="margin-bottom:2rem;">
        <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:2rem;">Delivery Progress</h3>
        <div class="steps">
            <div class="step-progress" style="width:<?php echo $progressPct; ?>%;"></div>
            <?php foreach ($steps as $i => $step):
                $done = $i <= $currentStep;
                $active = $i === $currentStep;
            ?>
            <div class="step <?php echo $done ? ($active ? 'active' : 'completed') : ''; ?>">
                <div class="step-icon"><?php echo $done && !$active ? '✓' : $step['icon']; ?></div>
                <div class="step-label"><?php echo $step['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($order['delivery_notes']): ?>
        <div style="margin-top:1rem;padding:1rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border-color);">
            <strong style="color:var(--primary);">📝 Delivery Notes:</strong>
            <p style="color:var(--text-muted);margin-top:.35rem;"><?php echo htmlspecialchars($order['delivery_notes']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Order Details Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
        <!-- Shipping Info -->
        <div class="table-container" style="padding:1.5rem;">
            <h4 style="font-weight:700;margin-bottom:1rem;">📍 Delivery Address</h4>
            <p style="color:var(--text-muted);"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>
        <!-- Driver Info -->
        <div class="table-container" style="padding:1.5rem;">
            <h4 style="font-weight:700;margin-bottom:1rem;">🚚 Driver</h4>
            <?php if ($order['driver_name']): ?>
            <p style="font-weight:600;"><?php echo htmlspecialchars($order['driver_name']); ?></p>
            <p style="color:var(--text-muted);margin-top:.25rem;"><?php echo htmlspecialchars($order['driver_phone'] ?? 'N/A'); ?></p>
            <?php else: ?>
            <p style="color:var(--text-muted);">Not yet assigned</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ordered Items -->
    <div class="table-container">
        <div class="table-header-bar"><div class="table-title">🛒 Items Ordered</div></div>
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>$<?php echo number_format($item['price'],2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td style="color:var(--primary);font-weight:700;">$<?php echo number_format($item['price']*$item['quantity'],2); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" style="text-align:right;font-weight:700;padding:1rem 1.5rem;">Total:</td>
                <td style="color:var(--primary);font-weight:800;font-size:1.1rem;padding:1rem 1.5rem;">$<?php echo number_format($order['total_amount'],2); ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
