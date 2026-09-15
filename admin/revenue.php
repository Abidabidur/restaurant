<?php
require "../config/database.php";
require "../config/auth.php";
require_role("admin");

// Summary stats
$total_rev   = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status='completed'")->fetch_assoc()['s'];
$today_rev   = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetch_assoc()['s'];
$month_rev   = $conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status='completed' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['s'];
$total_orders= $conn->query("SELECT COUNT(*) c FROM orders WHERE status='completed'")->fetch_assoc()['c'];

// Filter
$filter = $_GET['filter'] ?? 'all';
$date_from = $_GET['from'] ?? '';
$date_to   = $_GET['to']   ?? '';

$where = "WHERE 1=1";
$args  = [];
$types = '';

if ($filter === 'today') {
    $where .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
} elseif ($filter === 'range' && $date_from && $date_to) {
    $where .= " AND DATE(o.created_at) BETWEEN ? AND ?";
    $args[] = $date_from; $args[] = $date_to;
    $types .= 'ss';
}

$sql = "SELECT o.id, o.total, o.status, o.order_type, o.created_at,
               u.name AS customer,
               p.method AS pay_method, p.status AS pay_status
        FROM orders o
        JOIN users u ON u.id = o.customer_id
        LEFT JOIN payments p ON p.order_id = o.id
        $where
        ORDER BY o.id DESC";

$stmt = $conn->prepare($sql);
if ($args) { $stmt->bind_param($types, ...$args); }
$stmt->execute();
$orders = $stmt->get_result();

$status_map = ['pending'=>'badge-pending','preparing'=>'badge-preparing','ready'=>'badge-ready','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revenue Report — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Earnings & Revenue</h1>
            <span class="badge-role">Administrator</span>
        </div>
        <div class="content">

            <!-- Stats -->
            <div class="stats-grid mb-28">
                <div class="stat-card">
                    <div class="stat-ico gold">💰</div>
                    <div><div class="stat-val">৳<?= number_format($total_rev,2) ?></div><div class="stat-lbl">Total Revenue</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">📅</div>
                    <div><div class="stat-val">৳<?= number_format($today_rev,2) ?></div><div class="stat-lbl">Today's Revenue</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico blue">📆</div>
                    <div><div class="stat-val">৳<?= number_format($month_rev,2) ?></div><div class="stat-lbl">This Month</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">✅</div>
                    <div><div class="stat-val"><?= $total_orders ?></div><div class="stat-lbl">Completed Orders</div></div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-20">
                <form method="GET" class="form-row" style="align-items:flex-end">
                    <div class="form-group">
                        <label>Quick Filter</label>
                        <select name="filter" onchange="toggleDateRange(this.value)">
                            <option value="all"   <?= $filter==='all'   ?'selected':'' ?>>All Orders</option>
                            <option value="today" <?= $filter==='today' ?'selected':'' ?>>Today</option>
                            <option value="month" <?= $filter==='month' ?'selected':'' ?>>This Month</option>
                            <option value="range" <?= $filter==='range' ?'selected':'' ?>>Date Range</option>
                        </select>
                    </div>
                    <div class="form-group" id="date_range" style="<?= $filter==='range' ? '' : 'display:none' ?>">
                        <label>From</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="form-group" id="date_range2" style="<?= $filter==='range' ? '' : 'display:none' ?>">
                        <label>To</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div style="padding-bottom:16px">
                        <button class="btn">Apply Filter</button>
                        <a href="revenue.php" class="btn btn-dark" style="margin-left:8px">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="section-head"><h2>Order Records</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th><th>Customer</th><th>Total</th><th>Order Type</th>
                                <th>Payment</th><th>Order Status</th><th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($o = $orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $o['id'] ?></strong></td>
                                <td><?= htmlspecialchars($o['customer']) ?></td>
                                <td class="text-gold"><strong>৳<?= number_format($o['total'],2) ?></strong></td>
                                <td><span class="badge badge-info"><?= ucfirst($o['order_type'] ?? 'dine-in') ?></span></td>
                                <td>
                                    <?php if ($o['pay_method']): ?>
                                        <span class="badge badge-<?= $o['pay_status'] ?? 'unpaid' ?>">
                                            <?= ucfirst($o['pay_method']) ?> — <?= ucfirst($o['pay_status'] ?? '') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-unpaid">No Payment</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $status_map[$o['status']] ?? 'badge-pending' ?>"><?= $o['status'] ?></span></td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d Y, g:i A', strtotime($o['created_at'])) ?></td>
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
<script>
function toggleDateRange(val) {
    const show = val === 'range';
    document.getElementById('date_range').style.display  = show ? '' : 'none';
    document.getElementById('date_range2').style.display = show ? '' : 'none';
}
</script>
</body>
</html>
