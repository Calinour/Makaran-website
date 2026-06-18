<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role('admin');
$id=(int)($_GET['id']??0);
if($id){$conn->prepare("DELETE FROM products WHERE id=?")->execute([$id]); set_flash_message('success','Product deleted.');}
header('Location:'.BASE_URL.'products/index.php'); exit();
