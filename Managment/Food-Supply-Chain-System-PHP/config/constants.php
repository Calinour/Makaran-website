<?php
if (!defined('BASE_URL')) {
    // Determine protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Find the relative path from server root to the project directory
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    // The config file is at config/constants.php. So we go up 1 level to find the root folder path.
    $projectRootPath = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
    
    // Fallback if document root is empty or not in project path
    if (!empty($documentRoot) && strpos($projectRootPath, $documentRoot) === 0) {
        $relativePath = substr($projectRootPath, strlen($documentRoot));
    } else {
        // Fallback guess based on typical paths
        $relativePath = '/Food-Supply-Chain-System-PHP/Food-Supply-Chain-System-PHP';
    }
    
    $relativePath = '/' . trim($relativePath, '/') . '/';
    if ($relativePath === '//') {
        $relativePath = '/';
    }
    
    define('BASE_URL', $relativePath);
    define('FULL_BASE_URL', $protocol . $host . $relativePath);
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'SahanFresh FSCMS');
}

// Global Role mappings
define('ROLES', [
    'admin' => 'Administrator',
    'supplier' => 'Supplier Partner',
    'customer' => 'Customer',
    'driver' => 'Delivery Logistics'
]);
?>
