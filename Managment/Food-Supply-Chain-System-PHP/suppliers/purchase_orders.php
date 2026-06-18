<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['admin', 'supplier']);
$isAdminPage = true;

$user = get_logged_in_user();
$error = '';
$success = '';

// Handle marking as received by supplier (or admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_po_id'])) {
    $poId = (int)$_POST['receive_po_id'];
    
    $poStmt = $conn->prepare("SELECT * FROM purchase_orders WHERE id = ?");
    $poStmt->execute([$poId]);
    $po = $poStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($po && $po['status'] === 'pending') {
        // Confirm supplier role authorization
        if ($user['role'] === 'supplier' && $po['supplier_id'] !== $user['id']) {
            $error = 'Unauthorized operation.';
        } else {
            $conn->beginTransaction();
            try {
                // Update PO status
                $up = $conn->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?");
                $up->execute([$poId]);
                
                // Create a new inventory batch automatically
                $batchNumber = 'PO-B-' . str_pad($poId, 5, '0', STR_PAD_LEFT);
                $expiryDate = date('Y-m-d', strtotime('+30 days')); // Default 30 days expiry
                $notes = 'Received from Purchase Order #' . $poId;
                
                $invStmt = $conn->prepare("INSERT INTO inventory_batches (product_id, batch_number, quantity, expiry_date, status, notes) VALUES (?, ?, ?, ?, 'active', ?)");
                $invStmt->execute([$po['product_id'], $batchNumber, $po['quantity'], $expiryDate, $notes]);
                
                $conn->commit();
                set_flash_message('success', 'Purchase Order #' . $poId . ' received. Stock batch ' . $batchNumber . ' created successfully!');
                header('Location: ' . BASE_URL . 'suppliers/purchase_orders.php');
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Error receiving stock: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Purchase Order not found or already received.';
    }
}

// Handle creating new PO by admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_po'])) {
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    
    if (!$supplierId || !$productId || $qty <= 0) {
        $error = 'Please fill out all required fields with positive quantities.';
    } else {
        // Verify product belongs to supplier
        $pStmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND supplier_id = ?");
        $pStmt->execute([$productId, $supplierId]);
        if (!$pStmt->fetch()) {
            $error = 'Selected product does not belong to the selected supplier.';
        } else {
            $conn->prepare("INSERT INTO purchase_orders (supplier_id, product_id, quantity, status) VALUES (?, ?, ?, 'pending')")
                 ->execute([$supplierId, $productId, $qty]);
            
            set_flash_message('success', 'Purchase Request submitted to supplier successfully.');
            header('Location: ' . BASE_URL . 'suppliers/purchase_orders.php');
            exit();
        }
    }
}

// Fetch lists
if ($user['role'] === 'supplier') {
    $posStmt = $conn->prepare("SELECT po.*, p.name as product_name, p.sku FROM purchase_orders po JOIN products p ON po.product_id = p.id WHERE po.supplier_id = ? ORDER BY po.created_at DESC");
    $posStmt->execute([$user['id']]);
    $purchaseOrders = $posStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $purchaseOrders = $conn->query("SELECT po.*, p.name as product_name, p.sku, u.name as supplier_name FROM purchase_orders po JOIN products p ON po.product_id = p.id JOIN users u ON po.supplier_id = u.id ORDER BY po.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $suppliers = $conn->query("SELECT id, name FROM users WHERE role='supplier' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $products = $conn->query("SELECT id, name, supplier_id FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Purchase Orders';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>📋 Purchase Orders System</h2>
        <p><?php echo $user['role'] === 'supplier' ? 'Fulfill purchase request stock arrivals' : 'Send requests and monitor stock restocks'; ?></p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns: <?php echo $user['role'] === 'admin' ? '2fr 1fr' : '1fr'; ?>; gap:1.5rem; align-items:start;">
        <!-- Left: Purchase Orders Directory -->
        <div class="table-container">
            <div class="table-header-bar"><div class="table-title">Purchase Orders List</div></div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <?php if ($user['role'] === 'admin'): ?><th>Supplier</th><?php endif; ?>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Ordered Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchaseOrders as $po): ?>
                    <tr>
                        <td style="font-weight:700;">#<?php echo $po['id']; ?></td>
                        <?php if ($user['role'] === 'admin'): ?><td><?php echo htmlspecialchars($po['supplier_name']); ?></td><?php endif; ?>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($po['product_name']); ?></td>
                        <td style="font-family:monospace;"><?php echo htmlspecialchars($po['sku']); ?></td>
                        <td><?php echo $po['quantity']; ?> units</td>
                        <td>
                            <span class="badge badge-<?php echo $po['status'] === 'received' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($po['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($po['created_at'])); ?></td>
                        <td>
                            <?php if ($po['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;" data-confirm="Confirm receipt of this order? Doing so will add <?php echo $po['quantity']; ?> units to active stock.">
                                <input type="hidden" name="receive_po_id" value="<?php echo $po['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="font-size:0.8rem; padding:0.3rem 0.7rem;">Fulfill / Receive</button>
                            </form>
                            <?php else: ?>
                            <span style="color:var(--text-muted); font-size:0.85rem;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($purchaseOrders)): ?>
                    <tr>
                        <td colspan="<?php echo $user['role'] === 'admin' ? 8 : 7; ?>" style="text-align:center;color:var(--text-muted);padding:2rem;">No purchase orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Right: Admin Create PO Form -->
        <?php if ($user['role'] === 'admin'): ?>
        <div class="table-container" style="padding:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem;">➕ Request Restock</h3>
            <form method="POST">
                <input type="hidden" name="create_po" value="1">
                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier Partner *</label>
                    <select class="form-control" name="supplier_id" id="supplier_id" required onchange="filterProductsBySupplier(this.value)">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="product_id">Product Item *</label>
                    <select class="form-control" name="product_id" id="product_id" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-supplier-id="<?php echo $p['supplier_id']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="quantity">Order Quantity (units) *</label>
                    <input class="form-control" type="number" name="quantity" id="quantity" min="1" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">✅ Send Restock Request</button>
            </form>
        </div>
        <script>
            function filterProductsBySupplier(supplierId) {
                const productSelect = document.getElementById('product_id');
                const options = productSelect.options;
                
                // Clear selection
                productSelect.value = "";
                
                for (let i = 1; i < options.length; i++) {
                    const optionSupplierId = options[i].getAttribute('data-supplier-id');
                    if (!supplierId || optionSupplierId === supplierId) {
                        options[i].style.display = 'block';
                    } else {
                        options[i].style.display = 'none';
                    }
                }
            }
        </script>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
