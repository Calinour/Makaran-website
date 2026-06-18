<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = get_logged_in_user();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                        (SELECT payment_method FROM payments WHERE order_id = o.id LIMIT 1) as payment_method,
                        (SELECT status FROM payments WHERE order_id = o.id LIMIT 1) as payment_status_detail,
                        (SELECT id FROM payments WHERE order_id = o.id LIMIT 1) as transaction_id
                        FROM orders o 
                        JOIN users u ON o.customer_id = u.id 
                        WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Receipt not found.";
    exit();
}

// Customers can only see their own receipts
if ($user['role'] === 'customer' && $order['customer_id'] !== $user['id']) {
    echo "Access denied.";
    exit();
}

$itemsStmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.sku FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Receipt for Order #' . $orderId;
include __DIR__ . '/../includes/header.php';
?>
<style>
    .receipt-container { max-width: 600px; margin: 3rem auto; padding: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); }
    .receipt-header { text-align: center; border-bottom: 1px dashed var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
    .receipt-header h1 { color: var(--primary); font-size: 2.25rem; font-weight: 800; margin-bottom: 0.25rem; }
    .receipt-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.95rem; }
    .receipt-divider { border-bottom: 1px dashed var(--border-color); margin: 1.5rem 0; }
</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div style="padding: 0 1.5rem;">
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🥬 SahanFresh</h1>
            <p style="color:var(--text-muted); font-size:0.9rem;">Thank you for your purchase!</p>
            <h3 style="margin-top:1rem; font-size:1.1rem;">Official Payment Receipt</h3>
        </div>

        <div class="receipt-row">
            <span style="color:var(--text-muted);">Receipt ID:</span>
            <span style="font-weight:600; font-family:monospace;">REC-<?php echo str_pad($order['transaction_id'] ?? $orderId, 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Order ID:</span>
            <span style="font-weight:600;">#<?php echo $orderId; ?></span>
        </div>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Date:</span>
            <span style="font-weight:600;"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Payment Method:</span>
            <span style="font-weight:600; text-transform: uppercase;"><?php echo str_replace('_', ' ', $order['payment_method'] ?? 'CASH'); ?></span>
        </div>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Payment Status:</span>
            <span class="badge badge-success"><?php echo strtoupper($order['payment_status_detail'] ?? 'COMPLETED'); ?></span>
        </div>

        <div class="receipt-divider"></div>

        <h4 style="margin-bottom:1rem; font-weight:700;">Customer Details</h4>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Name:</span>
            <span style="font-weight:600;"><?php echo htmlspecialchars($order['customer_name']); ?></span>
        </div>
        <div class="receipt-row">
            <span style="color:var(--text-muted);">Email:</span>
            <span style="font-weight:600;"><?php echo htmlspecialchars($order['customer_email']); ?></span>
        </div>

        <div class="receipt-divider"></div>

        <h4 style="margin-bottom:1rem; font-weight:700;">Items Purchased</h4>
        <?php foreach ($items as $item): ?>
        <div class="receipt-row">
            <span><?php echo htmlspecialchars($item['product_name']); ?> <span style="color:var(--text-muted); font-size:0.85rem;">(x<?php echo $item['quantity']; ?>)</span></span>
            <span style="font-weight:600;">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
        </div>
        <?php endforeach; ?>

        <div class="receipt-divider"></div>

        <div class="receipt-row" style="font-size:1.2rem; font-weight:800;">
            <span>Total Paid</span>
            <span style="color:var(--primary);">$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>

        <div style="margin-top:2.5rem; text-align:center; display:flex; flex-direction:column; gap:0.75rem;">
            <a href="<?php echo BASE_URL; ?>orders/invoice.php?id=<?php echo $orderId; ?>" class="btn btn-primary" style="width:100%;">🖨️ View & Print Invoice</a>
            <a href="javascript:window.history.back()" class="btn btn-secondary" style="width:100%;">← Go Back</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
