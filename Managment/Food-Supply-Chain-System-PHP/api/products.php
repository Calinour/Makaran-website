<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$catId = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "SELECT p.id, p.name, p.description, p.price, p.sku, p.image_url, c.name as category_name,
        (SELECT COALESCE(SUM(ib.quantity), 0) FROM inventory_batches ib WHERE ib.product_id = p.id AND ib.status = 'active') as stock
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE 1";
$params = [];

if ($catId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catId;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map floats
foreach ($products as &$p) {
    $p['price'] = (float)$p['price'];
    $p['stock'] = (int)$p['stock'];
}

echo json_encode($products);
?>
