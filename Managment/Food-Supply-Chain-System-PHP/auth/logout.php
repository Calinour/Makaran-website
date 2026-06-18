<?php
require_once __DIR__ . '/../config/session.php';
logout_user();
header('Location: ' . BASE_URL . 'auth/login.php');
exit();
