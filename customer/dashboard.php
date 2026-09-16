<?php
require "../config/database.php";
require "../config/auth.php";
require_role("customer");

$uid = (int)$_SESSION['user']['id'];

$total_orders  = $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid")->fetch_assoc()['c'];
$pending_orders= $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid AND status NOT IN ('completed','cancelled')")->fetch_assoc()['c'];
$reservations  = $conn->query("SELECT COUNT(*) c FROM reservations WHERE customer_id=$uid AND status NOT IN ('cancelled')")->fetch_assoc()['c'];
$reviews_given = $conn->query("SELECT COUNT(*) c FROM reviews WHERE customer_id=$uid")->fetch_assoc()['c'];

// Recent orders
$recent = $conn->query(
    "SELECT o.id, o.total, o.status, o.created_at,
            GROUP_CONCAT(f.name SEPARATOR ', ') AS items
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN foods f ON f.id = oi.food_id
     WHERE o.customer_id = $uid
     GROUP BY o.id ORDER BY o.id DESC LIMIT 5"
);

$status_map = ['pending'=>'badge-pending','preparing'=>'badge-preparing','ready'=>'badge-ready','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?> 👋</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <!-- Stats -->
            <div class="stats-grid mb-28">
                <div class="stat-card">
                    <div class="stat-ico blue">📦</div>
                    <div><div class="stat-val"><?= $total_orders ?></div><div class="stat-lbl">Total Orders</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico gold">⏳</div>
                    <div><div class="stat-val"><?= $pending_orders ?></div><div class="stat-lbl">Active Orders</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">🗓️</div>
                    <div><div class="stat-val"><?= $reservations ?></div><div class="stat-lbl">Reservations</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico gold">⭐</div>
                    <div><div class="stat-val"><?= $reviews_given ?></div><div class="stat-lbl">Reviews Given</div></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid-2 mb-28">
                <div class="card">
                    <h2>Quick Actions</h2>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <a href="menu.php" class="btn" style="justify-content:flex-start">🍽️ &nbsp; Browse Menu &amp; Order</a>
                        <a href="reservation.php" class="btn btn-outline" style="justify-content:flex-start">🗓️ &nbsp; Reserve a Table</a>
                        <a href="orders.php" class="btn btn-outline" style="justify-content:flex-start">📦 &nbsp; Track My Orders</a>
                        <a href="reviews.php" class="btn btn-outline" style="justify-content:flex-start">⭐ &nbsp; Rate &amp; Review Food</a>
                    </div>
                </div>
                <div class="card">
                    <h2>Upcoming Reservations</h2>
                    <?php
                    $upcoming = $conn->query(
                        "SELECT r.reservation_date, r.reservation_time, r.guests, r.status, t.table_no
                         FROM reservations r JOIN restaurant_tables t ON t.id=r.table_id
                         WHERE r.customer_id=$uid AND r.reservation_date >= CURDATE()
                         ORDER BY r.reservation_date, r.reservation_time LIMIT 3"
                    );
                    $count = 0;
                    while ($rv = $upcoming->fetch_assoc()):
                        $count++;
                    ?>
                    <div style="border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin-bottom:10px">
                        <div class="flex-between">
                            <strong>Table <?= htmlspecialchars($rv['table_no']) ?></strong>
                            <span class="badge badge-<?= $rv['status'] ?>"><?= ucfirst($rv['status']) ?></span>
                        </div>
                        <div style="color:var(--muted);font-size:13px;margin-top:4px">
                            <?= date('D, M d Y', strtotime($rv['reservation_date'])) ?> at <?= date('g:i A', strtotime($rv['reservation_time'])) ?>
                            &nbsp;·&nbsp; <?= $rv['guests'] ?> guests
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$count): ?>
                    <p style="color:var(--muted);font-size:14px">No upcoming reservations. <a href="reservation.php">Book one now →</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="section-head">
                    <h2>Recent Orders</h2>
                    <a href="orders.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <?php if ($recent->num_rows === 0): ?>
                <p style="color:var(--muted);padding:16px 0">No orders yet. <a href="menu.php">Start ordering →</a></p>
                <?php else: ?>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Order</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($o = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $o['id'] ?></strong></td>
                                <td style="color:var(--muted);font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['items']) ?></td>
                                <td class="text-gold">৳<?= number_format($o['total'],2) ?></td>
                                <td><span class="badge <?= $status_map[$o['status']] ?? 'badge-pending' ?>"><?= $o['status'] ?></span></td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d, g:i A', strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
