<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
$user = get_logged_in_user();
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<header>
    <div class="nav-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="logo">🌿 Sahan<span>Fresh</span></a>
        <ul class="nav-links">
            <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
            <?php if ($user): ?>
                <li><a href="<?php echo BASE_URL; ?>customer/products.php">Shop</a></li>
                <li><a href="<?php echo BASE_URL; ?>dashboard/index.php">Dashboard</a></li>
                <?php if ($user['role'] === 'customer'): ?>
                <li><a href="<?php echo BASE_URL; ?>customer/cart.php">🛒 Cart (<?php echo $cartCount; ?>)</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>users/profile.php">👤 <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?></a></li>
                <li><a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-secondary" style="padding:0.4rem 1rem;font-size:0.9rem;">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>customer/products.php">Shop</a></li>
                <li><a href="<?php echo BASE_URL; ?>auth/login.php">Login</a></li>
                <li><a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary" style="padding:0.4rem 1rem;font-size:0.9rem;">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>
