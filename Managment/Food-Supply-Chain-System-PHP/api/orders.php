<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$user = $_SESSION['user'];
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['error' => 'Valid Order ID required.']);
    exit();
}

// Fetch order
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, d.name as driver_name 
                        FROM orders o 
                        JOIN users u ON o.customer_id = u.id 
                        LEFT JOIN users d ON o.driver_id = d.id 
                        WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found.']);
    exit();
}

// Validate access
if ($user['role'] === 'customer' && $order['customer_id'] !== $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

if ($user['role'] === 'driver' && $order['driver_id'] !== $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

echo json_encode([
    'id' => $order['id'],
    'status' => $order['status'],
    'payment_status' => $order['payment_status'],
    'total_amount' => (float)$order['total_amount'],
    'shipping_address' => $order['shipping_address'],
    'driver_name' => $order['driver_name'] ?? 'Not assigned',
    'delivery_notes' => $order['delivery_notes'] ?? '',
    'updated_at' => $order['updated_at']
]);
?>
