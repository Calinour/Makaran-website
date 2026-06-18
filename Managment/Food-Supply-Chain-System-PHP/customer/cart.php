<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('customer');

// Handle quantity update or remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][$pid]);
    } elseif (isset($_POST['update'])) {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['qty'] = $qty;
    } elseif (isset($_POST['clear'])) {
        $_SESSION['cart'] = [];
    }
    header('Location: ' . BASE_URL . 'customer/cart.php'); exit();
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];

$pageTitle = 'Shopping Cart';
include __DIR__ . '/../includes/header.php';
?>
<style>
.cart-wrapper { max-width:1100px; margin:2rem auto; padding:0 1.5rem; }
.cart-layout { display:grid; grid-template-columns:1fr 350px; gap:2rem; align-items:start; }
@media(max-width:768px){ .cart-layout{grid-template-columns:1fr;} }
.empty-cart { text-align:center; padding:5rem 2rem; }
</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="cart-wrapper">
    <h1 style="font-size:2rem;font-weight:800;margin-bottom:2rem;">🛒 Shopping Cart</h1>
    <?php if ($msg = get_flash_message('success')): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <?php if (empty($cart)): ?>
    <div class="empty-cart">
        <div style="font-size:4rem;margin-bottom:1rem;">🧺</div>
        <h2>Your cart is empty</h2>
        <p style="color:var(--text-muted);margin:.75rem 0 2rem;">Browse our fresh produce and add items to get started.</p>
        <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-primary">🛍️ Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
        <!-- Cart Items -->
        <div>
            <div class="table-container">
                <table class="cart-table">
                    <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cart as $pid => $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="color:var(--text-muted);font-size:.85rem;">SKU #<?php echo $pid; ?></div>
                        </td>
                        <td>$<?php echo number_format($item['price'],2); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                <div class="quantity-control">
                                    <input class="quantity-input" type="number" name="qty" value="<?php echo $item['qty']; ?>" min="1" max="100">
                                    <button type="submit" name="update" class="btn btn-secondary" style="padding:.3rem .6rem;font-size:.8rem;">Update</button>
                                </div>
                            </form>
                        </td>
                        <td style="font-weight:700;color:var(--primary);">$<?php echo number_format($item['price']*$item['qty'],2); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                <button type="submit" name="remove" class="btn-icon delete" title="Remove" data-confirm="Remove this item from cart?">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form method="POST" style="margin-top:.75rem;">
                <button type="submit" name="clear" class="btn btn-danger" style="font-size:.85rem;" data-confirm="Clear entire cart?">🗑️ Clear Cart</button>
            </form>
        </div>

        <!-- Cart Summary -->
        <div class="cart-totals">
            <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:1.5rem;">Order Summary</h3>
            <div style="display:flex;justify-content:space-between;margin-bottom:.75rem;color:var(--text-muted);">
                <span>Items (<?php echo array_sum(array_column($cart,'qty')); ?>)</span>
                <span>$<?php echo number_format($total,2); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:.75rem;color:var(--text-muted);">
                <span>Delivery</span>
                <span style="color:var(--primary);font-weight:600;">Free</span>
            </div>
            <div style="border-top:1px solid var(--border-color);margin:.75rem 0;"></div>
            <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:800;margin-bottom:1.5rem;">
                <span>Total</span>
                <span style="color:var(--primary);">$<?php echo number_format($total,2); ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>customer/checkout.php" class="btn btn-primary" style="width:100%;font-size:1rem;">✅ Proceed to Checkout</a>
            <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-secondary" style="width:100%;margin-top:.75rem;font-size:.9rem;">← Continue Shopping</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
