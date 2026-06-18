<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('customer');

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { header('Location: ' . BASE_URL . 'customer/cart.php'); exit(); }

$user = get_logged_in_user();
$total = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $method  = $_POST['payment_method'] ?? 'cash';
    if (empty($address)) { $error = 'Please enter your delivery address.'; }
    elseif (!in_array($method, ['cash','card','mobile_money'])) { $error = 'Invalid payment method.'; }
    else {
        $conn->beginTransaction();
        try {
            // Create order
            $os = $conn->prepare("INSERT INTO orders (customer_id,total_amount,status,payment_status,shipping_address) VALUES (?,?,'paid','paid',?)");
            $os->execute([$user['id'], $total, $address]);
            $orderId = $conn->lastInsertId();
            // Insert items & reduce stock
            foreach ($cart as $pid => $item) {
                $conn->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)")->execute([$orderId,$pid,$item['qty'],$item['price']]);
                // Deduct from inventory batches (FIFO)
                $remaining = $item['qty'];
                $batches = $conn->prepare("SELECT id,quantity FROM inventory_batches WHERE product_id=? AND status='active' AND quantity>0 ORDER BY expiry_date ASC");
                $batches->execute([$pid]); $batches = $batches->fetchAll(PDO::FETCH_ASSOC);
                foreach ($batches as $b) {
                    if ($remaining <= 0) break;
                    $deduct = min($remaining, $b['quantity']);
                    $conn->prepare("UPDATE inventory_batches SET quantity=quantity-? WHERE id=?")->execute([$deduct,$b['id']]);
                    $remaining -= $deduct;
                }
            }
            // Record payment
            $conn->prepare("INSERT INTO payments (order_id,amount,payment_method,status) VALUES (?,?,?,'completed')")->execute([$orderId,$total,$method]);
            $conn->commit();
            $_SESSION['cart'] = [];
            set_flash_message('success', 'Order #'.$orderId.' placed successfully! Thank you for shopping with SahanFresh.');
            header('Location: ' . BASE_URL . 'customer/track_order.php?id=' . $orderId); exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Failed to place order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>
<style>
.checkout-wrapper { max-width:1000px; margin:2rem auto; padding:0 1.5rem; }
.checkout-layout { display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start; }
@media(max-width:768px){ .checkout-layout{grid-template-columns:1fr;} }
.payment-option { display:flex; gap:.75rem; margin-bottom:.75rem; }
.payment-option input[type=radio] { accent-color:var(--primary); width:18px; height:18px; margin-top:2px; }
.payment-option label { font-weight:500; cursor:pointer; }
.payment-option label span { display:block; font-size:.82rem; color:var(--text-muted); }
</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="checkout-wrapper">
    <h1 style="font-size:2rem;font-weight:800;margin-bottom:2rem;">✅ Checkout</h1>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST">
    <div class="checkout-layout">
        <!-- Left: Details -->
        <div>
            <!-- Delivery Address -->
            <div class="table-container" style="padding:1.5rem;margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">📍 Delivery Address</h3>
                <div class="form-group">
                    <label class="form-label" for="address">Full Delivery Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"
                        placeholder="e.g. House 4B, Wadajir District, Mogadishu" required
                        style="resize:vertical;"><?php echo htmlspecialchars($_POST['address'] ?? $user['address'] ?? ''); ?></textarea>
                </div>
            </div>
            <!-- Payment Method -->
            <div class="table-container" style="padding:1.5rem;">
                <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">💳 Payment Method</h3>
                <?php $methods=[['cash','💵','Cash on Delivery','Pay when your order arrives'],['card','💳','Debit / Credit Card','Visa, Mastercard accepted'],['mobile_money','📱','Mobile Money','EVC Plus, Sahal, Zaad']]; ?>
                <?php foreach ($methods as [$val,$icon,$label,$desc]): ?>
                <div class="payment-option">
                    <input type="radio" id="pm_<?php echo $val; ?>" name="payment_method" value="<?php echo $val; ?>"
                           <?php echo (($_POST['payment_method'] ?? 'cash') === $val) ? 'checked' : ''; ?>>
                    <label for="pm_<?php echo $val; ?>"><?php echo $icon.' '.$label; ?><span><?php echo $desc; ?></span></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="cart-totals">
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">🧾 Order Summary</h3>
            <?php foreach ($cart as $item): ?>
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;font-size:.9rem;">
                <span style="color:var(--text-muted);"><?php echo htmlspecialchars($item['name']); ?> ×<?php echo $item['qty']; ?></span>
                <span>$<?php echo number_format($item['price']*$item['qty'],2); ?></span>
            </div>
            <?php endforeach; ?>
            <div style="border-top:1px solid var(--border-color);margin:1rem 0;"></div>
            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.15rem;margin-bottom:1.5rem;">
                <span>Total</span>
                <span style="color:var(--primary);">$<?php echo number_format($total,2); ?></span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1rem;padding:.85rem;">🎉 Place Order</button>
            <a href="<?php echo BASE_URL; ?>customer/cart.php" class="btn btn-secondary" style="width:100%;margin-top:.75rem;font-size:.9rem;">← Back to Cart</a>
        </div>
    </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
