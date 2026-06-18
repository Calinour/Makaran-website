<?php
require_once __DIR__ . '/../config/session.php';
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to access this page.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}
