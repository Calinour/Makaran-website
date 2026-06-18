<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('customer');

$user = get_logged_in_user();
$stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';

function statusBadge($s){ $m=['pending'=>'neutral','paid'=>'info','approved'=>'info','assigned'=>'warning','out_for_delivery'=>'warning','delivered'=>'success','cancelled'=>'danger']; return '<span class="badge badge-'.($m[$s]??'neutral').'">'.str_replace('_',' ',ucfirst($s)).'</span>'; }
?>
<style>.orders-wrapper{max-width:1000px;margin:2rem auto;padding:0 1.5rem;}</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="orders-wrapper">
    <?php if ($msg = get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <h1 style="font-size:2rem;font-weight:800;">📋 My Orders</h1>
        <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-primary">🛍️ Shop More</a>
    </div>
    <?php if (empty($orders)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
        <div style="font-size:4rem;margin-bottom:1rem;">📦</div>
        <h2>No orders yet</h2>
        <p style="margin:.75rem 0 2rem;">Your order history will appear here once you place an order.</p>
        <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $ord):
                $items = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?"); $items->execute([$ord['id']]); $itemCount = $items->fetchColumn();
            ?>
            <tr>
                <td style="font-weight:700;">#<?php echo $ord['id']; ?></td>
                <td><?php echo date('M j, Y', strtotime($ord['created_at'])); ?></td>
                <td><?php echo $itemCount; ?> item<?php echo $itemCount!=1?'s':''; ?></td>
                <td style="font-weight:700;color:var(--primary);">$<?php echo number_format($ord['total_amount'],2); ?></td>
                <td><?php echo statusBadge($ord['status']); ?></td>
                <td><?php echo statusBadge($ord['payment_status']); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>customer/track_order.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary" style="font-size:.8rem;padding:.3rem .7rem;">📍 Track</a>
                        <a href="<?php echo BASE_URL; ?>orders/receipt.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary" style="font-size:.8rem;padding:.3rem .7rem;">🧾 Receipt</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
