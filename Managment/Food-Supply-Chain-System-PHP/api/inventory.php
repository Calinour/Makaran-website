<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (!is_logged_in() || !in_array($_SESSION['user']['role'], ['admin', 'supplier'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$user = $_SESSION['user'];
$supplierFilter = ($user['role'] === 'supplier') ? "AND p.supplier_id = {$user['id']}" : "";

// Fetch low stock items (total stock across active batches < 10)
$lowStock = $conn->query("
    SELECT p.id, p.name, p.sku, COALESCE(SUM(ib.quantity), 0) as total_quantity 
    FROM products p 
    LEFT JOIN inventory_batches ib ON p.id = ib.product_id AND ib.status = 'active'
    WHERE 1 $supplierFilter
    GROUP BY p.id, p.name, p.sku
    HAVING total_quantity < 10
    ORDER BY total_quantity ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch expiring batches (expiry date within 7 days)
$expiring = $conn->query("
    SELECT ib.id, ib.batch_number, ib.quantity, ib.expiry_date, p.name as product_name, DATEDIFF(ib.expiry_date, NOW()) as days_left 
    FROM inventory_batches ib 
    JOIN products p ON ib.product_id = p.id 
    WHERE ib.status = 'active' AND ib.expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) $supplierFilter
    ORDER BY ib.expiry_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'low_stock' => $lowStock,
    'expiring_soon' => $expiring
]);
?>
