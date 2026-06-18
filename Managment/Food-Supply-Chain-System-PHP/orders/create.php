<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$error = '';
$success = '';

$customers = $conn->query("SELECT id, name, email FROM users WHERE role='customer' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query("SELECT p.id, p.name, p.price, 
                          (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches WHERE product_id=p.id AND status='active') as stock 
                          FROM products p ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $selectedProducts = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    if (!$customerId || empty($shippingAddress) || empty($selectedProducts)) {
        $error = 'Please select a customer, shipping address, and at least one product.';
    } else {
        $conn->beginTransaction();
        try {
            $totalAmount = 0.00;
            $itemsToInsert = [];
            
            // Calculate total and validate stock
            foreach ($selectedProducts as $productId) {
                $qty = (int)($quantities[$productId] ?? 0);
                if ($qty <= 0) continue;
                
                // Fetch product price
                $pStmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
                $pStmt->execute([$productId]);
                $prod = $pStmt->fetch(PDO::FETCH_ASSOC);
                
                // Get available stock
                $sStmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches WHERE product_id = ? AND status = 'active'");
                $sStmt->execute([$productId]);
                $stock = (int)$sStmt->fetchColumn();
                
                if ($qty > $stock) {
                    throw new Exception("Insufficient stock for " . $prod['name'] . ". Available: " . $stock);
                }
                
                $itemSubtotal = $prod['price'] * $qty;
                $totalAmount += $itemSubtotal;
                
                $itemsToInsert[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => $prod['price']
                ];
            }
            
            if (empty($itemsToInsert)) {
                throw new Exception("Please specify quantity for selected products.");
            }
            
            // Create Order
            $oStmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, status, payment_status, shipping_address) VALUES (?, ?, 'paid', 'paid', ?)");
            $oStmt->execute([$customerId, $totalAmount, $shippingAddress]);
            $orderId = $conn->lastInsertId();
            
            // Insert order items & update stock (FIFO)
            foreach ($itemsToInsert as $item) {
                $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")
                     ->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                
                // FIFO stock reduction
                $remaining = $item['quantity'];
                $bStmt = $conn->prepare("SELECT id, quantity FROM inventory_batches WHERE product_id = ? AND status = 'active' AND quantity > 0 ORDER BY expiry_date ASC");
                $bStmt->execute([$item['product_id']]);
                $batches = $bStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($remaining, $batch['quantity']);
                    $conn->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?")->execute([$deduct, $batch['id']]);
                    $remaining -= $deduct;
                }
            }
            
            // Log Payment
            $pStmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, status) VALUES (?, ?, 'cash', 'completed')");
            $pStmt->execute([$orderId, $totalAmount]);
            
            $conn->commit();
            set_flash_message('success', 'Manual order #' . $orderId . ' created successfully.');
            header('Location: ' . BASE_URL . 'orders/index.php');
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Create Order Manually';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>➕ Create Manual Order</h2>
        <p>Place an order on behalf of a customer</p>
    </div>
    <a href="<?php echo BASE_URL; ?>orders/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="table-container" style="max-width:800px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="customer_id">Select Customer *</label>
                <select class="form-control" name="customer_id" id="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="shipping_address">Delivery Address *</label>
                <textarea class="form-control" name="shipping_address" id="shipping_address" rows="2" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Select Products & Quantities *</label>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 1rem; background: rgba(0,0,0,0.15);">
                    <?php foreach ($products as $p): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:.5rem;">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <input type="checkbox" name="products[]" value="<?php echo $p['id']; ?>" id="prod_<?php echo $p['id']; ?>" style="width:18px;height:18px;accent-color:var(--primary);">
                            <label for="prod_<?php echo $p['id']; ?>" style="cursor:pointer; font-weight:500;">
                                <?php echo htmlspecialchars($p['name']); ?> 
                                <span style="color:var(--text-muted); font-size:.85rem;">($<?php echo number_format($p['price'],2); ?>)</span>
                            </label>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span style="font-size:0.8rem; color:<?php echo $p['stock'] < 10 ? 'var(--danger)' : 'var(--text-muted)'; ?>;">Stock: <?php echo $p['stock']; ?></span>
                            <input class="form-control" type="number" name="quantities[<?php echo $p['id']; ?>]" min="0" max="<?php echo $p['stock']; ?>" placeholder="Qty" style="width:70px; padding:0.25rem 0.5rem; text-align:center;">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">✅ Create Order</button>
                <a href="<?php echo BASE_URL; ?>orders/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
