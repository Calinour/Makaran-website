<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
$pageTitle = 'Reset Password';
include __DIR__ . '/../includes/header.php';
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $done = true; }
?>
<style>body{display:flex;flex-direction:column;justify-content:center;align-items:center;min-height:100vh;padding:2rem;}</style>
<div class="auth-container">
    <div class="auth-header">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🔒</div>
        <h2>Reset Password</h2>
        <p>Choose a new password for your account</p>
    </div>
    <?php if ($done): ?>
    <div class="alert alert-success">✅ Password updated successfully!</div>
    <div class="auth-footer"><a href="<?php echo BASE_URL; ?>auth/login.php">Sign In Now</a></div>
    <?php else: ?>
    <form method="POST">
        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <input class="form-control" type="password" id="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Update Password</button>
    </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
