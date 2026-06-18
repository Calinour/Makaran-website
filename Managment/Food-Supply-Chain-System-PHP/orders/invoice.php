<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = get_logged_in_user();
$orderId = (int)($_GET['id'] ?? 0);

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                        FROM orders o 
                        JOIN users u ON o.customer_id = u.id 
                        WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit();
}

// Customers can only view their own invoices
if ($user['role'] === 'customer' && $order['customer_id'] !== $user['id']) {
    echo "Access denied.";
    exit();
}

$itemsStmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.sku FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SahanFresh - Invoice #<?php echo $orderId; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 20px; line-height: 1.5; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; border-radius: 8px; }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--primary, #10b981); padding-bottom: 20px; margin-bottom: 20px; }
        .invoice-header h1 { margin: 0; color: #10b981; font-size: 28px; }
        .invoice-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .invoice-details h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 30px; }
        th { background: #f9fafb; padding: 12px; border-bottom: 2px solid #eee; font-weight: bold; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .totals { float: right; width: 250px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .totals-row.grand { font-weight: bold; font-size: 18px; border-top: 2px solid #eee; padding-top: 10px; color: #10b981; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; }
        .print-btn { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .print-btn:hover { background: #059669; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .invoice-box { border: none; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" class="print-btn">🖨️ Print Invoice</button>
            <a href="javascript:window.history.back()" class="print-btn" style="background:#6b7280;">Go Back</a>
        </div>

        <div class="invoice-header">
            <div>
                <h1>🥬 SahanFresh</h1>
                <p>Mogadishu, Somalia<br>Email: billing@sahanfresh.com</p>
            </div>
            <div style="text-align: right;">
                <h2>INVOICE</h2>
                <p>Invoice #: <strong><?php echo $orderId; ?></strong><br>Date: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="invoice-details">
            <div>
                <h3>Billed To</h3>
                <p>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    Email: <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    Phone: <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                </p>
            </div>
            <div>
                <h3>Shipping Address</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td style="font-family: monospace;"><?php echo htmlspecialchars($item['sku']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="overflow: hidden;">
            <div class="totals">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="totals-row">
                    <span>Shipping</span>
                    <span>$0.00</span>
                </div>
                <div class="totals-row grand">
                    <span>Total Due</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for supporting sustainable local food supply chains with SahanFresh!</p>
            <p>&copy; <?php echo date('Y'); ?> SahanFresh. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
