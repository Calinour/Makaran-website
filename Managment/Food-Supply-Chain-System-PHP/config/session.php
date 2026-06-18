<?php
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user data
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'phone' => isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '',
        'address' => isset($_SESSION['user_address']) ? $_SESSION['user_address'] : ''
    ];
}

/**
 * Restrict page access to specific roles. Redirects to login if not authorized.
 * @param array|string $allowedRoles
 */
function check_role($allowedRoles) {
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to access this page.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $user = get_logged_in_user();
    
    if (!in_array($user['role'], $allowed)) {
        set_flash_message('error', 'Unauthorized access. You do not have permission to view that page.');
        header('Location: ' . BASE_URL . 'dashboard/index.php');
        exit();
    }
}

/**
 * Establish a user session
 */
function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_phone'] = $user['phone'];
    $_SESSION['user_address'] = $user['address'];
}

/**
 * Destroy user session
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Set flash messages
 */
function set_flash_message($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message
 */
function get_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

/**
 * Check if a flash message of a type exists
 */
function has_flash_message($type) {
    return isset($_SESSION['flash'][$type]);
}
?>
