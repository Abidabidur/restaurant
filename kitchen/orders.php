<?php
require "../config/database.php";
require "../config/auth.php";
require_role("kitchen");

// Update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','preparing','ready','completed','cancelled'];
    if (in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $oid);
        $stmt->execute();
    }
    header("Location: orders.php?msg=updated"); exit;
}

$msg = $_GET['msg'] ?? '';

// Filter
$filter = $_GET['filter'] ?? 'active';
$where  = match($filter) {
    'pending'   => "WHERE o.status = 'pending'",
    'preparing' => "WHERE o.status = 'preparing'",
    'ready'     => "WHERE o.status = 'ready'",
    'completed' => "WHERE o.status = 'completed'",
    'cancelled' => "WHERE o.status = 'cancelled'",
    default     => "WHERE o.status NOT IN ('completed','cancelled')",
};

$all_orders = $conn->query(
    "SELECT o.id, o.status, o.total, o.order_type, o.created_at, u.name AS customer
     FROM orders o JOIN users u ON u.id = o.customer_id
     $where ORDER BY o.id DESC"
);

// Collect with items
$orders_data = [];
while ($o = $all_orders->fetch_assoc()) {
    $oid = $o['id'];
    $items_q = $conn->query(
        "SELECT f.name, f.description, oi.quantity, oi.price
         FROM order_items oi JOIN foods f ON f.id = oi.food_id
         WHERE oi.order_id = $oid"
    );
    $o['items'] = [];
    while ($it = $items_q->fetch_assoc()) $o['items'][] = $it;
    $orders_data[] = $o;
}

$status_map = ['pending'=>'badge-pending','preparing'=>'badge-preparing','ready'=>'badge-ready','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders Queue — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_kitchen.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Orders Queue</h1>
            <span class="badge-role">Kitchen Staff</span>
        </div>
        <div class="content">

            <?php if ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Order status updated.</div>
            <?php endif; ?>

            <!-- Filter tabs -->
            <div class="cat-tabs mb-20">
                <?php
                $tabs = ['active'=>'Active','pending'=>'Pending','preparing'=>'Preparing','ready'=>'Ready','completed'=>'Completed','cancelled'=>'Cancelled'];
                foreach ($tabs as $key => $label):
                ?>
                <a href="orders.php?filter=<?= $key ?>" class="cat-tab <?= $filter===$key ? 'active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Orders -->
            <?php if (empty($orders_data)): ?>
            <div class="card text-center" style="padding:48px">
                <div style="font-size:48px;margin-bottom:16px">📋</div>
                <p style="color:var(--muted)">No orders in this category.</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($orders_data as $o): ?>
                <div class="card" style="padding:0;overflow:hidden">
                    <!-- Header -->
                    <div class="flex-between" style="padding:16px 20px;cursor:pointer"
                         onclick="toggleOrder(<?= $o['id'] ?>)">
                        <div class="flex gap-16 flex-wrap">
                            <strong style="font-size:16px">#<?= $o['id'] ?></strong>
                            <span style="color:var(--muted);font-size:13px"><?= htmlspecialchars($o['customer']) ?></span>
                            <span class="badge <?= $status_map[$o['status']] ?>"><?= ucfirst($o['status']) ?></span>
                            <span class="badge badge-info"><?= ucfirst($o['order_type'] ?? 'dine-in') ?></span>
                        </div>
                        <div class="flex gap-12">
                            <span class="text-gold">৳<?= number_format($o['total'],2) ?></span>
                            <span style="color:var(--muted);font-size:13px"><?= date('M d, g:i A', strtotime($o['created_at'])) ?></span>
                            <span style="color:var(--muted)" id="arr_<?= $o['id'] ?>">▼</span>
                        </div>
                    </div>

                    <!-- Expanded -->
                    <div id="ord_<?= $o['id'] ?>" style="display:none;border-top:1px solid var(--border);padding:16px 20px">
                        <div class="grid-2" style="gap:24px">
                            <!-- Items -->
                            <div>
                                <h3 style="margin-bottom:12px">Food Preparation Details</h3>
                                <?php foreach ($o['items'] as $item): ?>
                                <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin-bottom:8px">
                                    <div class="flex-between">
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        <span class="text-gold">×<?= $item['quantity'] ?></span>
                                    </div>
                                    <?php if ($item['description']): ?>
                                    <div style="font-size:12px;color:var(--muted);margin-top:4px">
                                        📝 <?= htmlspecialchars($item['description']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div style="font-size:12px;color:var(--muted);margin-top:4px">
                                        Unit price: ৳<?= number_format($item['price'],2) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Status update -->
                            <div>
                                <h3 style="margin-bottom:12px">Update Order Status</h3>
                                <?php if (!in_array($o['status'], ['completed','cancelled'])): ?>
                                <form method="POST">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <div class="form-group">
                                        <label>New Status</label>
                                        <select name="status">
                                            <option value="pending"   <?= $o['status']==='pending'   ? 'selected':'' ?>>⏳ Pending</option>
                                            <option value="preparing" <?= $o['status']==='preparing' ? 'selected':'' ?>>🍳 Preparing</option>
                                            <option value="ready"     <?= $o['status']==='ready'     ? 'selected':'' ?>>🔔 Ready</option>
                                            <option value="completed" <?= $o['status']==='completed' ? 'selected':'' ?>>✅ Completed</option>
                                        </select>
                                    </div>
                                    <button class="btn" style="width:100%;justify-content:center">Update Status</button>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-<?= $o['status']==='completed' ? 'success' : 'error' ?>">
                                    Order is <?= ucfirst($o['status']) ?>.
                                </div>
                                <?php endif; ?>

                                <div class="divider"></div>
                                <div style="font-size:13px;color:var(--muted);display:flex;flex-direction:column;gap:6px">
                                    <div class="flex-between">
                                        <span>Ordered at</span>
                                        <span><?= date('g:i A', strtotime($o['created_at'])) ?></span>
                                    </div>
                                    <div class="flex-between">
                                        <span>Elapsed</span>
                                        <span><?= round((time()-strtotime($o['created_at']))/60) ?>min</span>
                                    </div>
                                    <div class="flex-between">
                                        <span>Items count</span>
                                        <span><?= array_sum(array_column($o['items'], 'quantity')) ?></span>
                                    </div>
                                </div>
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
    const el    = document.getElementById('ord_' + id);
    const arrow = document.getElementById('arr_' + id);
    const open  = el.style.display !== 'none';
    el.style.display  = open ? 'none' : 'block';
    arrow.textContent = open ? '▼' : '▲';
}
</script>
</body>
</html>
