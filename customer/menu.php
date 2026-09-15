<?php
require "../config/database.php";
require "../config/auth.php";
<<<<<<< HEAD
require "../includes/image_helper.php";
=======
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
require_role("customer");

// Categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$cat_list   = [];
while ($c = $categories->fetch_assoc()) $cat_list[] = $c;

$filter_cat = (int)($_GET['cat'] ?? 0);
$search     = trim($_GET['search'] ?? '');

$where  = "WHERE f.available = 1";
$params = [];
$types  = '';

if ($filter_cat) {
    $where .= " AND f.category_id = ?";
    $params[] = $filter_cat;
    $types .= 'i';
}
if ($search) {
    $s = "%$search%";
    $where .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = $s; $params[] = $s;
    $types .= 'ss';
}

$stmt = $conn->prepare(
    "SELECT f.id, f.name, f.description, f.price, f.image, c.name AS category
     FROM foods f JOIN categories c ON c.id = f.category_id
     $where ORDER BY c.name, f.name"
);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$foods = $stmt->get_result();

<<<<<<< HEAD
=======
$emojis = ['🍕','🍔','🥤','🍰','🍜','🥗','🍗','🌮','🍣','🥘','🍱','🍛'];
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Menu — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Our Curated Menu</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <!-- Search -->
            <form method="GET" class="flex gap-8 mb-20" style="align-items:flex-end">
                <div class="form-group" style="flex:1;margin-bottom:0">
                    <input name="search" placeholder="🔍  Search food items..." value="<?= htmlspecialchars($search) ?>" style="margin-bottom:0">
                </div>
                <?php if ($filter_cat): ?><input type="hidden" name="cat" value="<?= $filter_cat ?>"><?php endif; ?>
                <button class="btn" style="flex-shrink:0">Search</button>
                <?php if ($search || $filter_cat): ?>
                <a href="menu.php" class="btn btn-dark" style="flex-shrink:0">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Category tabs -->
            <div class="cat-tabs mb-20">
                <a href="menu.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="cat-tab <?= !$filter_cat ? 'active' : '' ?>">All</a>
                <?php foreach ($cat_list as $c): ?>
                <a href="menu.php?cat=<?= $c['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="cat-tab <?= $filter_cat == $c['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Cart notice -->
            <div class="alert alert-info mb-20" id="cartNotice" style="display:none">
                🛒 <span id="cartCount">0</span> item(s) in your cart —
                <a href="place_order.php" id="cartLink" style="color:inherit;font-weight:700">Proceed to Order →</a>
            </div>

            <!-- Food Grid -->
            <?php if ($foods->num_rows === 0): ?>
            <div class="card text-center" style="padding:48px">
                <div style="font-size:48px;margin-bottom:16px">🍽️</div>
                <h2 style="margin-bottom:8px">No items found</h2>
                <p style="color:var(--muted)">Try a different category or search term.</p>
                <a href="menu.php" class="btn btn-outline mt-12">Clear Filters</a>
            </div>
            <?php else: ?>
            <div class="food-grid" id="foodGrid">
<<<<<<< HEAD
                <?php while ($f = $foods->fetch_assoc()): ?>
                <div class="food-card" data-id="<?= $f['id'] ?>" data-name="<?= htmlspecialchars(addslashes($f['name'])) ?>" data-price="<?= $f['price'] ?>">
                    <div class="food-img">
                        <img src="<?= htmlspecialchars(food_image($f, '../')) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
=======
                <?php $i = 0; while ($f = $foods->fetch_assoc()): 
                    $image_path = $f['image'] ? '../assets/images/foods/' . htmlspecialchars($f['image']) : null;
                ?>
                <div class="food-card" data-id="<?= $f['id'] ?>" data-name="<?= htmlspecialchars(addslashes($f['name'])) ?>" data-price="<?= $f['price'] ?>">
                    <div class="food-img">
                        <?php if ($image_path && file_exists($image_path)): ?>
                            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($f['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius)">
                        <?php else: ?>
                            <?= $emojis[$i % count($emojis)] ?>
                        <?php endif; ?>
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
                    </div>
                    <div class="food-body">
                        <div class="food-name"><?= htmlspecialchars($f['name']) ?></div>
                        <div class="food-desc"><?= htmlspecialchars($f['description'] ?: 'Freshly prepared with premium ingredients.') ?></div>
                        <div style="margin-bottom:10px">
                            <span style="font-size:11px;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px 10px;color:var(--muted)">
                                <?= htmlspecialchars($f['category']) ?>
                            </span>
                        </div>
                        <div class="food-footer">
                            <span class="food-price">৳<?= number_format($f['price'],2) ?></span>
                            <div class="flex gap-8 align-items-center">
                                <div class="qty-spin">
                                    <button type="button" onclick="changeQty(<?= $f['id'] ?>, -1)">−</button>
                                    <input type="number" id="qty_<?= $f['id'] ?>" value="1" min="1" max="20" readonly style="margin-bottom:0">
                                    <button type="button" onclick="changeQty(<?= $f['id'] ?>, 1)">+</button>
                                </div>
                                <button class="btn btn-sm" onclick="addToCart(<?= $f['id'] ?>)">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
<<<<<<< HEAD
                <?php endwhile; ?>
=======
                <?php $i++; endwhile; ?>
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
            </div>
            <?php endif; ?>

            <!-- Floating Cart Summary -->
            <div id="floatingCart" style="display:none;position:fixed;bottom:24px;right:24px;z-index:999">
                <a href="place_order.php" class="btn" style="padding:14px 24px;font-size:15px;box-shadow:0 8px 32px rgba(0,0,0,.5)">
                    🛒 Place Order &nbsp;<span id="cartBadge" style="background:rgba(0,0,0,.3);border-radius:20px;padding:2px 8px">0</span>
                </a>
            </div>

        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
// Cart stored in sessionStorage as {id: {name, price, qty}}
let cart = JSON.parse(sessionStorage.getItem('cart') || '{}');
updateCartUI();

function changeQty(id, delta) {
    const inp = document.getElementById('qty_' + id);
    let val = parseInt(inp.value) + delta;
    inp.value = Math.max(1, Math.min(20, val));
}

function addToCart(id) {
    const card  = document.querySelector(`.food-card[data-id="${id}"]`);
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const qty   = parseInt(document.getElementById('qty_' + id).value);

    if (cart[id]) {
        cart[id].qty += qty;
    } else {
        cart[id] = { name, price, qty };
    }
    sessionStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();

    // Flash feedback
    const btn = card.querySelector('.btn-sm');
    const orig = btn.textContent;
    btn.textContent = '✓ Added';
    btn.style.background = 'var(--success)';
    setTimeout(() => { btn.textContent = orig; btn.style.background = ''; }, 1000);
}

function updateCartUI() {
    const total = Object.values(cart).reduce((s, i) => s + i.qty, 0);
    document.getElementById('cartCount').textContent = total;
    document.getElementById('cartBadge').textContent = total;
    const notice   = document.getElementById('cartNotice');
    const floating = document.getElementById('floatingCart');
    if (total > 0) {
        notice.style.display   = 'flex';
        floating.style.display = 'block';
    } else {
        notice.style.display   = 'none';
        floating.style.display = 'none';
    }
}
</script>
</body>
</html>
