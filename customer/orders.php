<?php
require "../config/database.php";
require "../config/auth.php";
require_role("customer");

$uid = (int)$_SESSION['user']['id'];

// Cancel an order (only if still pending)
if (isset($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND customer_id=? AND status='pending'");
    $stmt->bind_param("ii", $oid, $uid);
    $stmt->execute();
    header("Location: orders.php?msg=cancelled"); exit;
}

$msg = $_GET['msg'] ?? '';

// All orders for this customer
$orders = $conn->query(
    "SELECT o.id, o.total, o.status, o.order_type, o.created_at,
            p.method AS pay_method, p.status AS pay_status
     FROM orders o
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.customer_id = $uid
     ORDER BY o.id DESC"
);

$status_map = ['pending'=>'badge-pending','preparing'=>'badge-preparing','ready'=>'badge-ready','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];

// Fetch order items for expand
$all_orders_data = [];
while ($o = $orders->fetch_assoc()) {
    $oid = $o['id'];
    $items_q = $conn->query(
        "SELECT f.name, oi.quantity, oi.price
         FROM order_items oi JOIN foods f ON f.id=oi.food_id
         WHERE oi.order_id=$oid"
    );
    $items = [];
    while ($it = $items_q->fetch_assoc()) $items[] = $it;
    $o['items'] = $items;
    $all_orders_data[] = $o;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>My Orders</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <?php if ($msg === 'cancelled'): ?>
            <div class="alert alert-success">✅ Order cancelled successfully.</div>
            <?php endif; ?>

            <?php if (empty($all_orders_data)): ?>
            <div class="card text-center" style="padding:56px">
                <div style="font-size:56px;margin-bottom:18px">📦</div>
                <h2 style="margin-bottom:10px">No orders yet</h2>
                <p style="color:var(--muted);margin-bottom:20px">Head to the menu to place your first order.</p>
                <a href="menu.php" class="btn">Browse Menu</a>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($all_orders_data as $o): ?>
                <div class="card" style="padding:0;overflow:hidden">
                    <!-- Order header -->
                    <div class="flex-between" style="padding:16px 20px;cursor:pointer;user-select:none"
                         onclick="toggleOrder(<?= $o['id'] ?>)">
                        <div class="flex gap-16">
                            <div>
                                <strong style="font-size:16px">#<?= $o['id'] ?></strong>
                                <span style="color:var(--muted);font-size:13px;margin-left:10px">
                                    <?= date('M d, Y — g:i A', strtotime($o['created_at'])) ?>
                                </span>
                            </div>
                            <span class="badge <?= $status_map[$o['status']] ?? 'badge-pending' ?>"><?= ucfirst($o['status']) ?></span>
                            <span class="badge badge-info"><?= ucfirst($o['order_type'] ?? 'dine-in') ?></span>
                        </div>
                        <div class="flex gap-12 align-items-center">
                            <strong class="text-gold" style="font-size:16px">৳<?= number_format($o['total'],2) ?></strong>
                            <span style="color:var(--muted);font-size:18px" id="arrow_<?= $o['id'] ?>">▼</span>
                        </div>
                    </div>

                    <!-- Expanded details -->
                    <div id="order_<?= $o['id'] ?>" style="display:none;border-top:1px solid var(--border);padding:16px 20px">
                        <div class="grid-2" style="gap:24px">
                            <div>
                                <h3 style="margin-bottom:12px">Order Items</h3>
                                <?php foreach ($o['items'] as $item): ?>
                                <div class="flex-between" style="padding:8px 0;border-bottom:1px solid var(--border);font-size:14px">
                                    <span><?= htmlspecialchars($item['name']) ?> <span style="color:var(--muted)">×<?= $item['quantity'] ?></span></span>
                                    <span class="text-gold">৳<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </div>
                                <?php endforeach; ?>
                                <div class="flex-between" style="margin-top:12px;font-weight:700">
                                    <span>Total</span>
                                    <span class="text-gold">৳<?= number_format($o['total'],2) ?></span>
                                </div>
                            </div>
                            <div>
                                <h3 style="margin-bottom:12px">Order Info</h3>
                                <div style="font-size:14px;display:flex;flex-direction:column;gap:8px">
                                    <div class="flex-between">
                                        <span style="color:var(--muted)">Order Type</span>
                                        <span><?= ucfirst($o['order_type'] ?? 'dine-in') ?></span>
                                    </div>
                                    <div class="flex-between">
                                        <span style="color:var(--muted)">Payment</span>
                                        <span>
                                            <?php if ($o['pay_method']): ?>
                                                <?= ucfirst($o['pay_method']) ?> —
                                                <span class="badge badge-<?= $o['pay_status'] ?? 'unpaid' ?>"><?= ucfirst($o['pay_status'] ?? 'unpaid') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-unpaid">Pending</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="flex-between">
                                        <span style="color:var(--muted)">Kitchen Status</span>
                                        <span class="badge <?= $status_map[$o['status']] ?>"><?= ucfirst($o['status']) ?></span>
                                    </div>
                                </div>

                                <!-- Progress bar -->
                                <div style="margin-top:16px">
                                    <?php
                                    $steps  = ['pending','preparing','ready','completed'];
                                    $cur    = array_search($o['status'], $steps);
                                    $cur    = $cur === false ? 0 : $cur;
                                    ?>
                                    <div style="display:flex;gap:0;margin-bottom:8px">
                                        <?php foreach ($steps as $si => $step): ?>
                                        <div style="flex:1;text-align:center;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:<?= $si <= $cur && $o['status']!=='cancelled' ? 'var(--gold)' : 'var(--muted)' ?>">
                                            <?= $step ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="height:4px;background:var(--bg3);border-radius:2px;overflow:hidden">
                                        <?php $pct = $o['status']==='cancelled' ? 0 : min(100, ($cur/(count($steps)-1))*100); ?>
                                        <div style="height:100%;width:<?= $pct ?>%;background:var(--gold);transition:width .3s"></div>
                                    </div>
                                </div>

                                <?php if ($o['status'] === 'pending'): ?>
                                <a href="orders.php?cancel=<?= $o['id'] ?>"
                                   class="btn btn-danger btn-sm mt-16"
                                   onclick="return confirm('Cancel this order?')">Cancel Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script>
function toggleOrder(id) {
    const el    = document.getElementById('order_' + id);
    const arrow = document.getElementById('arrow_' + id);
    const open  = el.style.display !== 'none';
    el.style.display    = open ? 'none' : 'block';
    arrow.textContent   = open ? '▼' : '▲';
}
</script>
</body>
</html>
