<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = get_logged_in_user();
$isAdminPage = in_array($user['role'], ['admin', 'supplier', 'driver']);

// Re-fetch fresh user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } else {
        // Unique email check
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $user['id']]);
        if ($chk->fetch()) {
            $error = 'A user with this email address already exists.';
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, address = ? WHERE id = ?");
                $up->execute([$name, $email, $hashed, $phone, $address, $user['id']]);
            } else {
                $up = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $up->execute([$name, $email, $phone, $address, $user['id']]);
            }
            
            // Refresh session details
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            
            set_flash_message('success', 'Profile details updated successfully.');
            header('Location: ' . BASE_URL . 'users/profile.php');
            exit();
        }
    }
}

$pageTitle = 'My Profile Settings';
include __DIR__ . '/../includes/header.php';
?>
<?php if ($isAdminPage): ?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>👤 Profile Settings</h2>
        <p>Manage your account credentials and contact details</p>
    </div>
</div>
<div class="dashboard-content">
<?php else: ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div style="max-width:800px; margin:3rem auto; padding:0 1.5rem;">
    <h1 style="font-size:2rem; font-weight:800; margin-bottom:2rem;">👤 Profile Settings</h1>
<?php endif; ?>

    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-container" style="max-width:600px; padding:2.5rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input class="form-control" type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $userProfile['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input class="form-control" type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $userProfile['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Change Password (leave blank to keep current)</label>
                <input class="form-control" type="password" name="password" id="password">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $userProfile['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="address">Mailing Address / Details</label>
                <textarea class="form-control" name="address" id="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $userProfile['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">System Role</label>
                <input class="form-control" type="text" value="<?php echo ROLES[$userProfile['role']]; ?>" disabled style="opacity: 0.65; cursor: not-allowed;">
            </div>
            
            <div style="margin-top:2rem;">
                <button type="submit" class="btn btn-primary" style="width:100%; font-size:1rem; padding:.8rem;">💾 Save Profile Details</button>
            </div>
        </form>
    </div>

<?php if ($isAdminPage): ?>
</div></div></div>
<?php else: ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
