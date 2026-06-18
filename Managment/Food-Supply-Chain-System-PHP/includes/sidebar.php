<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
$user = get_logged_in_user();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$adminMenu = [
    ['icon'=>'📊','label'=>'Dashboard','href'=>BASE_URL.'dashboard/index.php','dir'=>'dashboard','page'=>'index.php'],
    ['icon'=>'👥','label'=>'Users','href'=>BASE_URL.'users/index.php','dir'=>'users','page'=>''],
    ['icon'=>'🛒','label'=>'Orders','href'=>BASE_URL.'orders/index.php','dir'=>'orders','page'=>''],
    ['icon'=>'📦','label'=>'Products','href'=>BASE_URL.'products/index.php','dir'=>'products','page'=>''],
    ['icon'=>'🏪','label'=>'Inventory','href'=>BASE_URL.'inventory/index.php','dir'=>'inventory','page'=>''],
    ['icon'=>'🚚','label'=>'Deliveries','href'=>BASE_URL.'deliveries/index.php','dir'=>'deliveries','page'=>''],
    ['icon'=>'🤝','label'=>'Suppliers','href'=>BASE_URL.'suppliers/index.php','dir'=>'suppliers','page'=>''],
    ['icon'=>'💳','label'=>'Payments','href'=>BASE_URL.'payments/index.php','dir'=>'payments','page'=>''],
    ['icon'=>'📈','label'=>'Reports','href'=>BASE_URL.'reports/sales.php','dir'=>'reports','page'=>''],
];
$supplierMenu = [
    ['icon'=>'📊','label'=>'Dashboard','href'=>BASE_URL.'dashboard/index.php','dir'=>'dashboard','page'=>'index.php'],
    ['icon'=>'🏪','label'=>'Inventory','href'=>BASE_URL.'inventory/index.php','dir'=>'inventory','page'=>''],
    ['icon'=>'📦','label'=>'Add Stock','href'=>BASE_URL.'inventory/add_stock.php','dir'=>'inventory','page'=>'add_stock.php'],
    ['icon'=>'📋','label'=>'Purchase Orders','href'=>BASE_URL.'suppliers/purchase_orders.php','dir'=>'suppliers','page'=>'purchase_orders.php'],
];
$driverMenu = [
    ['icon'=>'📊','label'=>'Dashboard','href'=>BASE_URL.'dashboard/index.php','dir'=>'dashboard','page'=>'index.php'],
    ['icon'=>'🚚','label'=>'My Deliveries','href'=>BASE_URL.'deliveries/assigned.php','dir'=>'deliveries','page'=>'assigned.php'],
    ['icon'=>'📝','label'=>'Delivery Notes','href'=>BASE_URL.'deliveries/notes.php','dir'=>'deliveries','page'=>'notes.php'],
];
$customerMenu = [
    ['icon'=>'🏠','label'=>'Home','href'=>BASE_URL.'index.php','dir'=>'','page'=>'index.php'],
    ['icon'=>'🛍️','label'=>'Shop','href'=>BASE_URL.'customer/products.php','dir'=>'customer','page'=>'products.php'],
    ['icon'=>'🛒','label'=>'Cart','href'=>BASE_URL.'customer/cart.php','dir'=>'customer','page'=>'cart.php'],
    ['icon'=>'📋','label'=>'My Orders','href'=>BASE_URL.'customer/orders.php','dir'=>'customer','page'=>'orders.php'],
];

$menu = [];
if ($user) {
    switch ($user['role']) {
        case 'admin':    $menu = $adminMenu; break;
        case 'supplier': $menu = $supplierMenu; break;
        case 'driver':   $menu = $driverMenu; break;
        case 'customer': $menu = $customerMenu; break;
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">🌿 Sahan<span>Fresh</span></div>
    <ul class="sidebar-menu">
        <?php foreach ($menu as $item):
            $isActive = ($currentDir === $item['dir']) || ($item['page'] && $currentPage === $item['page']);
        ?>
        <li>
            <a href="<?php echo $item['href']; ?>" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                <span><?php echo $item['icon']; ?></span>
                <?php echo $item['label']; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-user">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-glow);border:2px solid var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);flex-shrink:0;">
                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div style="overflow:hidden;">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
                <div class="sidebar-user-role"><?php echo ROLES[$user['role']] ?? ''; ?></div>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="sidebar-link" style="margin-top:1rem;color:var(--danger);">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>
