<?php
require_once __DIR__ . '/../config/constants.php';
?>
<footer>
    <div class="footer-inner">
        <div class="footer-brand">🌿 Sahan<span>Fresh</span></div>
        <div class="footer-links">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>customer/products.php">Shop</a>
            <a href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>auth/register.php">Register</a>
        </div>
        <div class="footer-copy">&copy; <?php echo date('Y'); ?> SahanFresh &mdash; Connecting Farms to Tables Across Somalia.</div>
    </div>
</footer>
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/validation.js"></script>
<?php if (isset($isAdminPage) && $isAdminPage): ?>
<script src="<?php echo BASE_URL; ?>assets/js/charts.js"></script>
<?php endif; ?>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
