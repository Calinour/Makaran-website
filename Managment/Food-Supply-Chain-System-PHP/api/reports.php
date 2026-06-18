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

// Monthly revenue (completed payments)
$monthlyRevenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as revenue 
    FROM payments 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Category distribution
$categorySales = $conn->query("
    SELECT c.name as category, COUNT(oi.id) as sales_count, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'monthly_revenue' => $monthlyRevenue,
    'category_sales' => $categorySales
]);
?>
