<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role('admin'); $isAdminPage=true;
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    if($action==='add'){
        $name=trim($_POST['name']??''); $desc=trim($_POST['description']??'');
        if(!$name){$error='Category name required.';}
        else{$conn->prepare("INSERT INTO categories(name,description) VALUES(?,?)")->execute([$name,$desc]); $success='Category added.';}
    } elseif($action==='delete'){
        $cid=(int)($_POST['cat_id']??0);
        if($cid){$conn->prepare("DELETE FROM categories WHERE id=?")->execute([$cid]); $success='Category deleted.';}
    }
}
$categories=$conn->query("SELECT c.*,(SELECT COUNT(*) FROM products WHERE category_id=c.id) as product_count FROM categories c ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Categories'; include __DIR__.'/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header"><div class="dashboard-title"><h2>🗂️ Categories</h2><p>Manage food product categories</p></div><a href="<?php echo BASE_URL; ?>products/index.php" class="btn btn-secondary">← Products</a></div>
<div class="dashboard-content">
<?php if($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
<div class="table-container">
    <div class="table-header-bar"><div class="table-title">All Categories</div></div>
    <table class="admin-table"><thead><tr><th>Name</th><th>Products</th><th>Action</th></tr></thead><tbody>
    <?php foreach($categories as $c): ?>
    <tr><td style="font-weight:600;"><?php echo htmlspecialchars($c['name']); ?></td><td><?php echo $c['product_count']; ?></td>
    <td><form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="cat_id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn-icon delete" data-confirm="Delete this category?">🗑️</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
<div class="table-container" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">➕ Add Category</h3>
    <form method="POST"><input type="hidden" name="action" value="add">
        <div class="form-group"><label class="form-label">Category Name *</label><input class="form-control" type="text" name="name" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" style="resize:vertical;"></textarea></div>
        <button type="submit" class="btn btn-primary">Add Category</button>
    </form>
</div>
</div>
</div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
