<?php
require "../config/database.php";
require "../config/auth.php";
require_role("admin");

// Stats
$total_users   = $conn->query("SELECT COUNT(*) c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$total_foods   = $conn->query("SELECT COUNT(*) c FROM foods")->fetch_assoc()['c'];
$total_orders  = $conn->query("SELECT COUNT(*) c FROM orders WHERE status NOT IN ('cancelled')")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status='completed'")->fetch_assoc()['s'];
$pending_orders= $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$today_revenue = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetch_assoc()['s'];

// Recent orders
$recent = $conn->query(
    "SELECT o.id, o.status, o.total, o.created_at, u.name AS customer,
            GROUP_CONCAT(f.name SEPARATOR ', ') AS items
     FROM orders o
     JOIN users u ON u.id = o.customer_id
     JOIN order_items oi ON oi.order_id = o.id
     JOIN foods f ON f.id = oi.food_id
     GROUP BY o.id ORDER BY o.id DESC LIMIT 10"
);

$status_colors = [
    'pending'   => 'badge-pending',
    'preparing' => 'badge-preparing',
    'ready'     => 'badge-ready',
    'completed' => 'badge-completed',
    'cancelled' => 'badge-cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Bistro Operations</h1>
            <span class="badge-role">Administrator</span>
        </div>
        <div class="content">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-ico gold">👥</div>
                    <div>
                        <div class="stat-val"><?= $total_users ?></div>
                        <div class="stat-lbl">Total Users</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico blue">🍽️</div>
                    <div>
                        <div class="stat-val"><?= $total_foods ?></div>
                        <div class="stat-lbl">Food Items</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">📦</div>
                    <div>
                        <div class="stat-val"><?= $total_orders ?></div>
                        <div class="stat-lbl">Total Orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico gold">💰</div>
                    <div>
                        <div class="stat-val">৳<?= number_format($total_revenue, 0) ?></div>
                        <div class="stat-lbl">Total Revenue</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico red">⏳</div>
                    <div>
                        <div class="stat-val"><?= $pending_orders ?></div>
                        <div class="stat-lbl">Pending Orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">📈</div>
                    <div>
                        <div class="stat-val">৳<?= number_format($today_revenue, 0) ?></div>
                        <div class="stat-lbl">Today's Revenue</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid-2 mb-28">
                <div class="card">
                    <h2>Quick Operations</h2>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <a href="users.php" class="btn btn-outline" style="justify-content:flex-start">
                            👥 &nbsp; Add / Manage Users
                        </a>
                        <a href="foods.php" class="btn btn-outline" style="justify-content:flex-start">
                            🍽️ &nbsp; Add New Menu Item
                        </a>
                        <a href="revenue.php" class="btn btn-outline" style="justify-content:flex-start">
                            💰 &nbsp; View Revenue Report
                        </a>
                    </div>
                </div>
                <div class="card">
                    <h2>System Overview</h2>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;color:var(--muted)">
                        <div class="flex-between"><span>Active customers</span>
                            <strong style="color:var(--text)"><?= $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer' AND status='active'")->fetch_assoc()['c'] ?></strong>
                        </div>
                        <div class="flex-between"><span>Kitchen staff</span>
                            <strong style="color:var(--text)"><?= $conn->query("SELECT COUNT(*) c FROM users WHERE role='kitchen'")->fetch_assoc()['c'] ?></strong>
                        </div>
                        <div class="flex-between"><span>Available tables</span>
                            <strong style="color:var(--text)"><?= $conn->query("SELECT COUNT(*) c FROM restaurant_tables WHERE status='available'")->fetch_assoc()['c'] ?></strong>
                        </div>
                        <div class="flex-between"><span>Pending reservations</span>
                            <strong style="color:var(--text)"><?= $conn->query("SELECT COUNT(*) c FROM reservations WHERE status='pending'")->fetch_assoc()['c'] ?></strong>
                        </div>
                        <div class="flex-between"><span>Available food items</span>
                            <strong style="color:var(--text)"><?= $conn->query("SELECT COUNT(*) c FROM foods WHERE available=1")->fetch_assoc()['c'] ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="section-head">
                    <h2>Recent Active Orders</h2>
                    <a href="revenue.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['customer']) ?></td>
                                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:13px">
                                    <?= htmlspecialchars($row['items']) ?>
                                </td>
                                <td class="text-gold">৳<?= number_format($row['total'], 2) ?></td>
                                <td><span class="badge <?= $status_colors[$row['status']] ?? 'badge-pending' ?>"><?= $row['status'] ?></span></td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d, g:i A', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
