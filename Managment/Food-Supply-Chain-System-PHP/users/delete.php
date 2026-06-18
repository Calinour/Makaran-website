<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');

$userId = (int)($_GET['id'] ?? 0);

if ($userId > 0) {
    if ($userId == $_SESSION['user']['id']) {
        set_flash_message('error', 'You cannot delete your own admin account.');
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        set_flash_message('success', 'User profile deleted successfully.');
    }
}

header('Location: ' . BASE_URL . 'users/index.php');
exit();
?>
