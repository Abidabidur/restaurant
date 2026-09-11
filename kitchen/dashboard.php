<?php
require "../config/database.php";
require "../config/auth.php";
require_role("kitchen");

// Update order status via GET
if (isset($_GET['set_status'], $_GET['id'])) {
    $oid        = (int)$_GET['id'];
    $new_status = $_GET['set_status'];
    $allowed    = ['preparing', 'ready', 'completed'];
    if (in_array($new_status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND status != 'cancelled'");
        $stmt->bind_param("si", $new_status, $oid);
        $stmt->execute();
    }
    header("Location: dashboard.php"); exit;
}

// Stats
$pending    = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$preparing  = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='preparing'")->fetch_assoc()['c'];
$ready      = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='ready'")->fetch_assoc()['c'];
$completed_today = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];

// Active orders (not completed/cancelled) — most urgent first
$orders = $conn->query(
    "SELECT o.id, o.status, o.order_type, o.created_at, u.name AS customer,
            GROUP_CONCAT(CONCAT(f.name,' ×',oi.quantity) ORDER BY f.name SEPARATOR ', ') AS items,
            GROUP_CONCAT(CONCAT(f.name,'|',oi.quantity,'|',f.description) SEPARATOR ';;') AS item_details
     FROM orders o
     JOIN users u ON u.id = o.customer_id
     JOIN order_items oi ON oi.order_id = o.id
     JOIN foods f ON f.id = oi.food_id
     WHERE o.status NOT IN ('completed','cancelled')
     GROUP BY o.id
     ORDER BY FIELD(o.status,'pending','preparing','ready'), o.created_at ASC"
);

$next_status = [
    'pending'  => 'preparing',
    'preparing'=> 'ready',
    'ready'    => 'completed',
];
$status_map = [
    'pending'  => 'badge-pending',
    'preparing'=> 'badge-preparing',
    'ready'    => 'badge-ready',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kitchen Dashboard — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
<meta http-equiv="refresh" content="30">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_kitchen.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Kitchen Operations</h1>
            <div class="flex gap-12">
                <span class="badge-role">Kitchen Staff</span>
                <span style="font-size:12px;color:var(--muted)">Auto-refresh every 30s</span>
            </div>
        </div>
        <div class="content">

            <!-- Stats -->
            <div class="stats-grid mb-28">
                <div class="stat-card">
                    <div class="stat-ico red">⏳</div>
                    <div><div class="stat-val"><?= $pending ?></div><div class="stat-lbl">Pending</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico blue">🍳</div>
                    <div><div class="stat-val"><?= $preparing ?></div><div class="stat-lbl">Preparing</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico gold">🔔</div>
                    <div><div class="stat-val"><?= $ready ?></div><div class="stat-lbl">Ready to Serve</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">✅</div>
                    <div><div class="stat-val"><?= $completed_today ?></div><div class="stat-lbl">Completed Today</div></div>
                </div>
            </div>

            <!-- Order Cards -->
            <?php if ($orders->num_rows === 0): ?>
            <div class="card text-center" style="padding:56px">
                <div style="font-size:64px;margin-bottom:16px">🎉</div>
                <h2 style="margin-bottom:8px">All caught up!</h2>
                <p style="color:var(--muted)">No active orders at the moment. Page refreshes every 30 seconds.</p>
            </div>
            <?php else: ?>
            <div class="order-cards">
                <?php while ($o = $orders->fetch_assoc()):
                    $next    = $next_status[$o['status']] ?? null;
                    $elapsed = round((time() - strtotime($o['created_at'])) / 60);
                    $urgent  = $elapsed >= 20 && $o['status'] === 'pending';
                    $items   = array_map(fn($i) => explode('|', $i), explode(';;', $o['item_details']));
                ?>
                <div class="order-card" style="<?= $urgent ? 'border-color:rgba(224,82,82,.5)' : '' ?>">
                    <div class="oc-header">
                        <div>
                            <div class="oc-id">#<?= $o['id'] ?>
                                <?php if ($urgent): ?>
                                <span style="font-size:11px;color:var(--danger);margin-left:6px">⚠ URGENT</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                                <?= htmlspecialchars($o['customer']) ?> &nbsp;·&nbsp;
                                <span class="badge badge-info" style="font-size:10px"><?= $o['order_type'] ?></span>
                            </div>
                        </div>
                        <span class="badge <?= $status_map[$o['status']] ?>">
                            <?= strtoupper($o['status']) ?>
                        </span>
                    </div>

                    <div class="oc-items">
                        <?php foreach ($items as $item): ?>
                        <div class="oc-item">
                            <span><?= htmlspecialchars($item[0]) ?> ×<?= htmlspecialchars($item[1] ?? 1) ?></span>
                            <?php if (!empty($item[2])): ?>
                            <span style="color:var(--muted);font-size:11px" title="<?= htmlspecialchars($item[2]) ?>">ℹ</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex-between" style="margin-top:10px">
                        <span style="font-size:12px;color:<?= $urgent ? 'var(--danger)' : 'var(--muted)' ?>">
                            🕐 <?= $elapsed ?>m ago
                        </span>
                        <?php if ($next): ?>
                        <a href="dashboard.php?set_status=<?= $next ?>&id=<?= $o['id'] ?>"
                           class="btn btn-sm <?= $next === 'completed' ? 'btn-success' : '' ?>"
                           onclick="return confirm('Mark as <?= strtoupper($next) ?>?')">
                            <?php
                            $labels = ['preparing'=>'🍳 Start Preparing','ready'=>'🔔 Mark Ready','completed'=>'✅ Complete'];
                            echo $labels[$next] ?? ucfirst($next);
                            ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
