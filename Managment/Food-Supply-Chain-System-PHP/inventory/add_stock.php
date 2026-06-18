<?php
require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth_check.php';
check_role(['admin','supplier']); $isAdminPage = true;
$user = get_logged_in_user();
$error = '';

// Load ALL products for the dropdown
$stmt = $conn->prepare(
    "SELECT p.id, p.name, p.sku, c.name AS cat
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY c.name ASC, p.name ASC"
);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by category
$grouped = [];
foreach ($products as $p) {
    $cat = $p['cat'] ?? 'Uncategorised';
    $grouped[$cat][] = $p;
}

// Category icon & color map
$catMeta = [
    'Bakery'              => ['icon' => '🍞', 'color' => '#f59e0b'],
    'Dairy & Eggs'        => ['icon' => '🥛', 'color' => '#60a5fa'],
    'Eggs & Dairy'        => ['icon' => '🥚', 'color' => '#fbbf24'],
    'Fruits'              => ['icon' => '🍌', 'color' => '#34d399'],
    'Fruits & Vegetables' => ['icon' => '🥬', 'color' => '#4ade80'],
    'Grains & Pulses'     => ['icon' => '🌾', 'color' => '#d97706'],
    'Grains & Staples'    => ['icon' => '🍚', 'color' => '#e2a73b'],
    'Meat & Poultry'      => ['icon' => '🥩', 'color' => '#f87171'],
    'Sweets & Snacks'     => ['icon' => '🍫', 'color' => '#c084fc'],
    'Vegetables'          => ['icon' => '🥔', 'color' => '#22d3ee'],
    'Uncategorised'       => ['icon' => '📦', 'color' => '#94a3b8'],
];

// Product-specific icons
$productIcons = [
    'egg'       => '🥚', 'milk'      => '🥛', 'ghee'   => '🧈', 'butter' => '🧈',
    'chocolate' => '🍫', 'banana'    => '🍌', 'mango'  => '🥭', 'tomato' => '🍅',
    'potato'    => '🥔', 'onion'     => '🧅', 'rice'   => '🍚', 'bread'  => '🍞',
    'muufo'     => '🫓', 'sorghum'   => '🌾', 'goat'   => '🥩', 'meat'   => '🥩',
    'camel'     => '🐪', 'yoghurt'   => '🥣', 'cheese' => '🧀',
];

function getProductIcon($name, $fallback = '📦') {
    global $productIcons;
    $lower = strtolower($name);
    foreach ($productIcons as $keyword => $icon) {
        if (strpos($lower, $keyword) !== false) return $icon;
    }
    return $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)($_POST['product_id'] ?? 0);
    $batch  = trim($_POST['batch_number'] ?? '');
    $qty    = (int)($_POST['quantity'] ?? 0);
    $expiry = trim($_POST['expiry_date'] ?? '');
    $notes  = trim($_POST['notes'] ?? '');

    if (!$pid || !$batch || $qty <= 0 || !$expiry) {
        $error = 'All required fields must be filled.';
    } else {
        $conn->prepare(
            "INSERT INTO inventory_batches
             (product_id, batch_number, quantity, expiry_date, status, notes)
             VALUES (?, ?, ?, ?, 'active', ?)"
        )->execute([$pid, $batch, $qty, $expiry, $notes]);

        set_flash_message('success', 'Batch ' . htmlspecialchars($batch) . ' added with ' . $qty . ' units.');
        header('Location: ' . BASE_URL . 'inventory/index.php');
        exit();
    }
}

$pageTitle = 'Add Stock';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ========== Custom Dropdown Styles ========== */
.custom-select-wrap { position: relative; width: 100%; }

.cs-trigger {
    display: flex; align-items: center; gap: .75rem;
    width: 100%; padding: .8rem 1rem;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: var(--radius);
    color: var(--text-main); cursor: pointer;
    font-family: inherit; font-size: .95rem;
    transition: all .25s ease;
    user-select: none;
}
.cs-trigger:hover { border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.05); }
.cs-trigger.open { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); border-radius: var(--radius) var(--radius) 0 0; }
.cs-trigger .cs-icon { font-size: 1.3rem; flex-shrink: 0; }
.cs-trigger .cs-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cs-trigger .cs-placeholder { color: var(--text-muted); }
.cs-trigger .cs-arrow {
    width: 20px; height: 20px; flex-shrink: 0;
    transition: transform .25s ease; opacity: .5;
}
.cs-trigger.open .cs-arrow { transform: rotate(180deg); }

.cs-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    background: rgba(13,18,32,.97);
    border: 1px solid rgba(255,255,255,.1);
    border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    max-height: 340px; overflow-y: auto;
    z-index: 200;
    backdrop-filter: blur(20px);
    display: none;
    box-shadow: 0 20px 50px rgba(0,0,0,.6);
}
.cs-dropdown.show { display: block; animation: csSlide .2s ease; }
@keyframes csSlide { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

.cs-search-box {
    position: sticky; top: 0; padding: .6rem .75rem;
    background: rgba(13,18,32,.98);
    border-bottom: 1px solid rgba(255,255,255,.06);
    z-index: 2;
}
.cs-search {
    width: 100%; padding: .55rem .8rem .55rem 2.2rem;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 8px; color: var(--text-main);
    font-family: inherit; font-size: .85rem;
    outline: none; transition: border .2s;
}
.cs-search:focus { border-color: var(--primary); }
.cs-search-icon {
    position: absolute; left: 1.2rem; top: 50%;
    transform: translateY(-50%); opacity: .4;
    pointer-events: none; font-size: .85rem;
}

.cs-group-label {
    display: flex; align-items: center; gap: .5rem;
    padding: .5rem 1rem;
    font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em;
    position: sticky; top: 42px;
    background: rgba(13,18,32,.95);
    border-bottom: 1px solid rgba(255,255,255,.04);
    z-index: 1;
}
.cs-group-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0;
}

.cs-option {
    display: flex; align-items: center; gap: .7rem;
    padding: .65rem 1rem .65rem 1.5rem;
    cursor: pointer; transition: all .15s;
    border-left: 3px solid transparent;
}
.cs-option:hover {
    background: rgba(255,255,255,.05);
}
.cs-option.selected {
    background: rgba(16,185,129,.08);
    border-left-color: var(--primary);
}
.cs-option .cs-opt-icon {
    font-size: 1.2rem; flex-shrink: 0;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.06);
}
.cs-option .cs-opt-info { flex: 1; min-width: 0; }
.cs-option .cs-opt-name {
    font-size: .9rem; font-weight: 600;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.cs-option .cs-opt-sku {
    font-size: .72rem; color: var(--text-muted);
    margin-top: .1rem;
}
.cs-option .cs-opt-cat-badge {
    font-size: .65rem; font-weight: 700;
    padding: .15rem .5rem; border-radius: 999px;
    white-space: nowrap; flex-shrink: 0;
}

.cs-empty {
    padding: 1.5rem; text-align: center;
    color: var(--text-muted); font-size: .85rem;
}

/* scrollbar */
.cs-dropdown::-webkit-scrollbar { width: 5px; }
.cs-dropdown::-webkit-scrollbar-track { background: transparent; }
.cs-dropdown::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 999px; }
</style>

<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">

<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>📥 Add Stock</h2>
        <p>Record a new inventory batch arrival</p>
    </div>
    <a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">← Inventory</a>
</div>

<div class="dashboard-content">
<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="table-container" style="max-width:640px; padding:2rem;">
<form method="POST" id="addStockForm">

    <!-- Hidden real input for form submission -->
    <input type="hidden" name="product_id" id="product_id_input" value="<?php echo htmlspecialchars($_POST['product_id'] ?? ''); ?>" required>

    <!-- Product Custom Dropdown -->
    <div class="form-group">
        <label class="form-label">Product *</label>
        <?php if (empty($products)): ?>
            <p style="color:var(--text-muted);font-size:.9rem;">
                ⚠️ No products found.
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>products/create.php" style="color:var(--primary);">Add a product first →</a>
                <?php endif; ?>
            </p>
        <?php else: ?>
        <div class="custom-select-wrap" id="productSelect">
            <!-- Trigger Button -->
            <div class="cs-trigger" id="csTrigger" tabindex="0">
                <span class="cs-icon" id="csIcon">📦</span>
                <span class="cs-text"><span class="cs-placeholder" id="csTextInner">— Select a product —</span></span>
                <svg class="cs-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <!-- Dropdown Panel -->
            <div class="cs-dropdown" id="csDropdown">
                <div class="cs-search-box">
                    <span class="cs-search-icon">🔍</span>
                    <input type="text" class="cs-search" id="csSearch" placeholder="Search products..." autocomplete="off">
                </div>

                <div id="csOptions">
                <?php foreach ($grouped as $catName => $items):
                    $meta = $catMeta[$catName] ?? $catMeta['Uncategorised'];
                ?>
                <div class="cs-group" data-cat="<?php echo htmlspecialchars(strtolower($catName)); ?>">
                    <div class="cs-group-label" style="color: <?php echo $meta['color']; ?>;">
                        <span class="cs-group-dot" style="background: <?php echo $meta['color']; ?>; box-shadow: 0 0 8px <?php echo $meta['color']; ?>40;"></span>
                        <?php echo $meta['icon'] . ' ' . htmlspecialchars($catName); ?>
                    </div>
                    <?php foreach ($items as $p):
                        $pIcon = getProductIcon($p['name'], $meta['icon']);
                        $selected = (($_POST['product_id'] ?? '') == $p['id']);
                    ?>
                    <div class="cs-option <?php echo $selected ? 'selected' : ''; ?>"
                         data-value="<?php echo $p['id']; ?>"
                         data-name="<?php echo htmlspecialchars($p['name']); ?>"
                         data-sku="<?php echo htmlspecialchars($p['sku'] ?? ''); ?>"
                         data-icon="<?php echo $pIcon; ?>"
                         data-cat="<?php echo htmlspecialchars($catName); ?>"
                         data-color="<?php echo $meta['color']; ?>">
                        <span class="cs-opt-icon" style="background: <?php echo $meta['color']; ?>15; border-color: <?php echo $meta['color']; ?>30;"><?php echo $pIcon; ?></span>
                        <div class="cs-opt-info">
                            <div class="cs-opt-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <?php if (!empty($p['sku'])): ?>
                            <div class="cs-opt-sku">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="cs-opt-cat-badge" style="background: <?php echo $meta['color']; ?>18; color: <?php echo $meta['color']; ?>; border: 1px solid <?php echo $meta['color']; ?>30;">
                            <?php echo $meta['icon'] . ' ' . htmlspecialchars($catName); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <div class="cs-empty" id="csEmpty" style="display:none;">No products match your search.</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Batch Number -->
    <div class="form-group">
        <label class="form-label">Batch Number *</label>
        <input class="form-control" type="text" name="batch_number"
               value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>"
               placeholder="e.g. B-TOM-2024-001" required>
        <small style="color:var(--text-muted);font-size:.8rem;margin-top:.3rem;display:block;">
            Unique identifier for this delivery batch
        </small>
    </div>

    <!-- Quantity & Expiry -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
        <div class="form-group">
            <label class="form-label">Quantity (units) *</label>
            <input class="form-control" type="number" name="quantity" min="1"
                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>"
                   placeholder="0" required>
        </div>
        <div class="form-group">
            <label class="form-label">Expiry Date *</label>
            <input class="form-control" type="date" name="expiry_date"
                   value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>"
                   min="<?php echo date('Y-m-d'); ?>" required>
        </div>
    </div>

    <!-- Notes -->
    <div class="form-group">
        <label class="form-label">Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
        <textarea class="form-control" name="notes" rows="3" style="resize:vertical;"
                  placeholder="Storage conditions, arrival notes, supplier reference..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
    </div>

    <div style="display:flex; gap:1rem; margin-top:1.25rem;">
        <button type="submit" class="btn btn-primary" <?php echo empty($products) ? 'disabled' : ''; ?>>
            📥 Add Batch
        </button>
        <a href="<?php echo BASE_URL; ?>inventory/index.php" class="btn btn-secondary">Cancel</a>
    </div>

</form>
</div>
</div>
</div>
</div>

<script>
(function() {
    const wrap     = document.getElementById('productSelect');
    if (!wrap) return; // no products
    const trigger  = document.getElementById('csTrigger');
    const dropdown = document.getElementById('csDropdown');
    const search   = document.getElementById('csSearch');
    const options  = document.getElementById('csOptions');
    const empty    = document.getElementById('csEmpty');
    const hidden   = document.getElementById('product_id_input');
    const iconEl   = document.getElementById('csIcon');
    const textEl   = document.getElementById('csTextInner');
    const allOpts  = options.querySelectorAll('.cs-option');
    const allGroups= options.querySelectorAll('.cs-group');

    // Pre-select if value exists (e.g. form resubmit)
    if (hidden.value) {
        const pre = options.querySelector('.cs-option[data-value="'+hidden.value+'"]');
        if (pre) selectOption(pre, false);
    }

    // Toggle dropdown
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('show');
        if (isOpen) { close(); } else { open(); }
    });

    function open() {
        dropdown.classList.add('show');
        trigger.classList.add('open');
        search.value = '';
        filterOptions('');
        setTimeout(() => search.focus(), 50);
    }
    function close() {
        dropdown.classList.remove('show');
        trigger.classList.remove('open');
    }

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target)) close();
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    // Search filter
    search.addEventListener('input', () => {
        filterOptions(search.value.trim().toLowerCase());
    });

    function filterOptions(q) {
        let anyVisible = false;
        allGroups.forEach(group => {
            const opts = group.querySelectorAll('.cs-option');
            let groupHasVisible = false;
            opts.forEach(opt => {
                const name = (opt.dataset.name || '').toLowerCase();
                const sku  = (opt.dataset.sku || '').toLowerCase();
                const cat  = (opt.dataset.cat || '').toLowerCase();
                const match = !q || name.includes(q) || sku.includes(q) || cat.includes(q);
                opt.style.display = match ? '' : 'none';
                if (match) { groupHasVisible = true; anyVisible = true; }
            });
            group.style.display = groupHasVisible ? '' : 'none';
        });
        empty.style.display = anyVisible ? 'none' : 'block';
    }

    // Click option
    allOpts.forEach(opt => {
        opt.addEventListener('click', () => selectOption(opt, true));
    });

    function selectOption(opt, doClose) {
        // Remove previous selection
        allOpts.forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');

        // Update hidden input
        hidden.value = opt.dataset.value;

        // Update trigger display
        iconEl.textContent = opt.dataset.icon;
        textEl.className = ''; // Remove placeholder class
        textEl.innerHTML = '<strong>' + escHtml(opt.dataset.name) + '</strong>' +
            (opt.dataset.sku ? ' <span style="color:var(--text-muted);font-size:.82rem;">(' + escHtml(opt.dataset.sku) + ')</span>' : '');

        // Color accent on trigger
        trigger.style.borderColor = opt.dataset.color + '60';

        if (doClose) close();
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // Form validation
    document.getElementById('addStockForm').addEventListener('submit', (e) => {
        if (!hidden.value) {
            e.preventDefault();
            trigger.style.borderColor = '#ef4444';
            trigger.style.boxShadow = '0 0 0 3px rgba(239,68,68,.25)';
            trigger.focus();
            setTimeout(() => {
                trigger.style.borderColor = '';
                trigger.style.boxShadow = '';
            }, 2000);
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
