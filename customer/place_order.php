<?php
require "../config/database.php";
require "../config/auth.php";
require_role("customer");

$uid     = (int)$_SESSION['user']['id'];
$success = "";
$error   = "";
$order_id = null;

// Handle POST: place the order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['items'])) {
    $items      = $_POST['items'];       // array of food_id
    $quantities = $_POST['quantities'];  // array of qty
    $order_type = in_array($_POST['order_type'], ['dine-in','takeaway']) ? $_POST['order_type'] : 'dine-in';

    if (empty($items)) {
        $error = "Your cart is empty.";
    } else {
        // Verify all food items exist and are available
        $total = 0.0;
        $verified = [];

        foreach ($items as $idx => $fid) {
            $fid = (int)$fid;
            $qty = max(1, (int)($quantities[$idx] ?? 1));
            $fs  = $conn->prepare("SELECT id, price, name FROM foods WHERE id=? AND available=1 LIMIT 1");
            $fs->bind_param("i", $fid);
            $fs->execute();
            $food = $fs->get_result()->fetch_assoc();
            if (!$food) {
                $error = "One or more items are no longer available.";
                break;
            }
            $subtotal = round($food['price'] * $qty, 2);
            $total   += $subtotal;
            $verified[] = ['food_id' => $fid, 'qty' => $qty, 'price' => $food['price'], 'subtotal' => $subtotal, 'name' => $food['name']];
        }

        if (!$error) {
            $conn->begin_transaction();
            try {
                // Insert order
                $os = $conn->prepare("INSERT INTO orders(customer_id, total, status, order_type) VALUES(?,?,'pending',?)");
                $os->bind_param("ids", $uid, $total, $order_type);
                $os->execute();
                $order_id = $conn->insert_id;

                // Insert order items
                $is = $conn->prepare("INSERT INTO order_items(order_id, food_id, quantity, price) VALUES(?,?,?,?)");
                foreach ($verified as $item) {
                    $is->bind_param("iiid", $order_id, $item['food_id'], $item['qty'], $item['price']);
                    $is->execute();
                }

                // Create payment record (unpaid)
                $ps = $conn->prepare("INSERT INTO payments(order_id, amount, method, status) VALUES(?,?,'cash','unpaid')");
                $ps->bind_param("id", $order_id, $total);
                $ps->execute();

                $conn->commit();
                $success = "Order #$order_id placed successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to place order. Please try again.";
                $order_id = null;
            }
        }
    }
}

// Load all available foods for order review / direct order
$foods = $conn->query(
    "SELECT f.id, f.name, f.price, c.name AS category
     FROM foods f JOIN categories c ON c.id = f.category_id
     WHERE f.available = 1 ORDER BY c.name, f.name"
);
$food_list = [];
while ($f = $foods->fetch_assoc()) $food_list[] = $f;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Place Order — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Place Order</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?>
                <a href="orders.php" style="margin-left:12px;color:inherit;font-weight:700">Track Order →</a>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <div class="cart-wrap">
                <!-- Food selection -->
                <div style="flex:1">
                    <div class="card mb-20">
                        <h2>Select Items</h2>
                        <div id="foodList" style="display:flex;flex-direction:column;gap:10px">
                            <?php foreach ($food_list as $f): ?>
                            <div class="flex-between" style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px">
                                <div>
                                    <strong><?= htmlspecialchars($f['name']) ?></strong>
                                    <span style="font-size:12px;color:var(--muted);margin-left:8px"><?= htmlspecialchars($f['category']) ?></span>
                                    <div class="text-gold" style="font-size:14px;margin-top:2px">৳<?= number_format($f['price'],2) ?></div>
                                </div>
                                <div class="qty-spin">
                                    <button type="button" onclick="changeQty(<?= $f['id'] ?>, -1)">−</button>
                                    <input type="number" id="qty_<?= $f['id'] ?>" value="0" min="0" max="20"
                                           data-price="<?= $f['price'] ?>" data-name="<?= htmlspecialchars(addslashes($f['name'])) ?>"
                                           onchange="updateCart()" style="margin-bottom:0">
                                    <button type="button" onclick="changeQty(<?= $f['id'] ?>, 1)">+</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Summary (cart) -->
                <div class="cart-sidebar">
                    <div class="card">
                        <h2>Your Order Summary</h2>
                        <div id="cartItems" style="min-height:60px">
                            <p style="color:var(--muted);font-size:14px">No items selected yet.</p>
                        </div>
                        <div class="divider"></div>
                        <div class="cart-total">
                            <span>Total</span>
                            <span id="cartTotal">৳0.00</span>
                        </div>
                        <div class="divider"></div>
                        <form method="POST" id="orderForm">
                            <div class="form-group">
                                <label>Order Type</label>
                                <select name="order_type" style="margin-bottom:0">
                                    <option value="dine-in">Dine-In</option>
                                    <option value="takeaway">Takeaway</option>
                                </select>
                            </div>
                            <div id="hiddenInputs"></div>
                            <button class="btn" id="placeBtn" disabled style="width:100%;justify-content:center;margin-top:12px;opacity:.5">
                                Proceed to Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card text-center" style="padding:48px;max-width:480px;margin:0 auto">
                <div style="font-size:64px;margin-bottom:20px">🎉</div>
                <h2 style="margin-bottom:10px">Order Confirmed!</h2>
                <p style="color:var(--muted);margin-bottom:24px">Your order has been sent to the kitchen. You can track the status below.</p>
                <div class="flex gap-12" style="justify-content:center;flex-wrap:wrap">
                    <a href="orders.php" class="btn">Track Order #<?= $order_id ?></a>
                    <a href="menu.php" class="btn btn-outline">Order More</a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
// Pre-load cart from sessionStorage (from menu.php)
let cart = JSON.parse(sessionStorage.getItem('cart') || '{}');

// If cart has items, pre-fill quantities
document.addEventListener('DOMContentLoaded', () => {
    for (const [id, item] of Object.entries(cart)) {
        const inp = document.getElementById('qty_' + id);
        if (inp) inp.value = item.qty;
    }
    updateCart();
});

function changeQty(id, delta) {
    const inp = document.getElementById('qty_' + id);
    let val = parseInt(inp.value || 0) + delta;
    inp.value = Math.max(0, Math.min(20, val));
    updateCart();
}

function updateCart() {
    const allInputs = document.querySelectorAll('#foodList input[type=number]');
    let total = 0;
    let cartHTML = '';
    const hiddenInputs = document.getElementById('hiddenInputs');
    hiddenInputs.innerHTML = '';
    cart = {};
    let idx = 0;

    allInputs.forEach(inp => {
        const qty = parseInt(inp.value || 0);
        if (qty > 0) {
            const price   = parseFloat(inp.dataset.price);
            const name    = inp.dataset.name;
            const fid     = inp.id.replace('qty_', '');
            const subtotal = price * qty;
            total += subtotal;
            cart[fid] = { name, price, qty };

            cartHTML += `<div class="cart-item">
                <span>${name} ×${qty}</span>
                <span class="text-gold">৳${subtotal.toFixed(2)}</span>
            </div>`;

            hiddenInputs.innerHTML += `<input type="hidden" name="items[]" value="${fid}">`;
            hiddenInputs.innerHTML += `<input type="hidden" name="quantities[]" value="${qty}">`;
            idx++;
        }
    });

    sessionStorage.setItem('cart', JSON.stringify(cart));
    document.getElementById('cartItems').innerHTML = cartHTML || '<p style="color:var(--muted);font-size:14px">No items selected yet.</p>';
    document.getElementById('cartTotal').textContent = '৳' + total.toFixed(2);

    const btn = document.getElementById('placeBtn');
    btn.disabled = (idx === 0);
    btn.style.opacity = idx > 0 ? '1' : '0.5';
}
</script>
</body>
</html>
