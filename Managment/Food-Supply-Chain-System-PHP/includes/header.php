<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';

// Page title can be set before including header
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
$fullTitle = $pageTitle . ' | ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SahanFresh Food Supply Chain Management System - Connecting farms to tables across Somalia.">
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <?php endif; ?>
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>
