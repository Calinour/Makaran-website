<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to add items to cart.');
        header('Location: ' . BASE_URL . 'auth/login.php'); exit();
    }
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] += $qty;
    } else {
        $ps = $conn->prepare("SELECT id,name,price FROM products WHERE id=?");
        $ps->execute([$pid]);
        $prod = $ps->fetch(PDO::FETCH_ASSOC);
        if ($prod) $_SESSION['cart'][$pid] = ['qty'=>$qty, 'name'=>$prod['name'], 'price'=>$prod['price']];
    }
    set_flash_message('success', 'Item added to cart!');
    header('Location: ' . BASE_URL . 'customer/products.php'); exit();
}

// Filter
$catFilter = (int)($_GET['cat'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, c.name as category_name,
        (SELECT COALESCE(SUM(ib.quantity),0) FROM inventory_batches ib WHERE ib.product_id=p.id AND ib.status='active') as stock
        FROM products p JOIN categories c ON p.category_id=c.id WHERE 1";
$params = [];
if ($catFilter) { $sql .= " AND p.category_id=?"; $params[] = $catFilter; }
if ($search)    { $sql .= " AND p.name LIKE ?"; $params[] = "%$search%"; }
$sql .= " ORDER BY p.name";
$stmt = $conn->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Shop Fresh Produce';
include __DIR__ . '/../includes/header.php';
?>
<style>
.shop-wrapper { max-width:1200px; margin:0 auto; padding:2rem 1.5rem; }
.shop-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; }
.shop-header h1 { font-size:2rem; font-weight:800; }
.filter-bar { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:2rem; }
.filter-bar select, .filter-bar input { padding:.6rem 1rem; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius); color:var(--text-main); font-family:inherit; font-size:.9rem; }
.filter-bar select:focus, .filter-bar input:focus { outline:none; border-color:var(--primary); }
</style>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="shop-wrapper">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash_message('error')): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="shop-header">
        <h1>🛍️ Fresh Produce</h1>
        <span style="color:var(--text-muted);"><?php echo count($products); ?> items found</span>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search products..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="cat">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $catFilter==$cat['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:.6rem 1.2rem;">Filter</button>
        <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-secondary" style="padding:.6rem 1.2rem;">Clear</a>
    </form>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">🥬</div>
        <p>No products found. Try adjusting your filters.</p>
    </div>
    <?php else: ?>
    <div class="catalog-grid">
        <?php foreach ($products as $p):
            $stock = (int)$p['stock'];
        ?>
        <div class="product-card">
            <div class="product-image">
                <?php if ($p['image_url'] && file_exists(__DIR__.'/../'.$p['image_url'])): ?>
                <img src="<?php echo BASE_URL.$p['image_url']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <?php else: ?>
                <span style="font-size:3rem;">🥗</span>
                <?php endif; ?>
                <?php if ($stock > 0): ?>
                <span class="product-badge fresh">✅ Fresh</span>
                <?php else: ?>
                <span class="product-badge" style="color:var(--danger);">Out of Stock</span>
                <?php endif; ?>
            </div>
            <div class="product-content">
                <div style="font-size:.78rem;color:var(--primary);font-weight:600;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em;"><?php echo htmlspecialchars($p['category_name']); ?></div>
                <div class="product-title"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="product-desc"><?php echo htmlspecialchars($p['description']); ?></div>
                <div class="product-meta">
                    <div class="product-price">$<?php echo number_format($p['price'],2); ?></div>
                    <div class="product-stock <?php echo $stock<10?'low':''; ?>"><?php echo $stock; ?> in stock</div>
                </div>
                <?php if ($stock > 0): ?>
                <form class="add-cart-form" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="add_to_cart" value="1">
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.75rem;">🛒 Add to Cart</button>
                </form>
                <?php else: ?>
                <button class="btn btn-secondary" style="width:100%;margin-top:.75rem;opacity:.5;cursor:not-allowed;" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
