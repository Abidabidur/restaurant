<?php
require "../config/database.php";
require "../config/auth.php";
require_role("manager");

// Stats
$pending_res  = $conn->query("SELECT COUNT(*) c FROM reservations WHERE status='pending'")->fetch_assoc()['c'];
$total_tables = $conn->query("SELECT COUNT(*) c FROM restaurant_tables")->fetch_assoc()['c'];
$avail_tables = $conn->query("SELECT COUNT(*) c FROM restaurant_tables WHERE status='available'")->fetch_assoc()['c'];
$pending_inv  = $conn->query("SELECT COUNT(*) c FROM payments WHERE status='unpaid'")->fetch_assoc()['c'];
$total_rev    = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='paid'")->fetch_assoc()['s'];

// Today's reservations
$today_res = $conn->query(
    "SELECT r.id, r.reservation_time, r.guests, r.status, u.name AS customer, t.table_no
     FROM reservations r
     JOIN users u ON u.id = r.customer_id
     JOIN restaurant_tables t ON t.id = r.table_id
     WHERE r.reservation_date = CURDATE()
     ORDER BY r.reservation_time ASC LIMIT 8"
);

// Pending quick-approvals
$quick_approvals = $conn->query(
    "SELECT r.id, r.reservation_date, r.reservation_time, r.guests, u.name AS customer, t.table_no
     FROM reservations r
     JOIN users u ON u.id = r.customer_id
     JOIN restaurant_tables t ON t.id = r.table_id
     WHERE r.status = 'pending'
     ORDER BY r.reservation_date, r.reservation_time
     LIMIT 3"
);

$status_map = ['pending'=>'badge-pending','approved'=>'badge-approved','cancelled'=>'badge-cancelled','waitlist'=>'badge-waitlist'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manager Dashboard — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_manager.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Floor Operations</h1>
            <span class="badge-role">Floor Manager</span>
        </div>
        <div class="content">

            <!-- Stats -->
            <div class="stats-grid mb-28">
                <div class="stat-card">
                    <div class="stat-ico gold">🗓️</div>
                    <div><div class="stat-val"><?= $pending_res ?></div><div class="stat-lbl">Pending Reservations</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico green">🪑</div>
                    <div><div class="stat-val"><?= $avail_tables ?> / <?= $total_tables ?></div><div class="stat-lbl">Available Tables</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico red">🧾</div>
                    <div><div class="stat-val"><?= $pending_inv ?></div><div class="stat-lbl">Pending Invoices</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico gold">💰</div>
                    <div><div class="stat-val">৳<?= number_format($total_rev,0) ?></div><div class="stat-lbl">Total Collected</div></div>
                </div>
            </div>

            <div class="grid-2 mb-28">
                <!-- Today's Reservations -->
                <div class="card">
                    <div class="section-head">
                        <h2>Today's Reservations</h2>
                        <a href="reservations.php" class="btn btn-sm btn-outline">Manage All</a>
                    </div>
                    <?php if ($today_res->num_rows === 0): ?>
                    <p style="color:var(--muted);font-size:14px">No reservations scheduled for today.</p>
                    <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <?php while ($r = $today_res->fetch_assoc()): ?>
                        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px">
                            <div class="flex-between">
                                <div>
                                    <strong><?= htmlspecialchars($r['customer']) ?></strong>
                                    <span style="color:var(--muted);font-size:13px;margin-left:8px">Table <?= htmlspecialchars($r['table_no']) ?></span>
                                </div>
                                <span class="badge <?= $status_map[$r['status']] ?>"><?= ucfirst($r['status']) ?></span>
                            </div>
                            <div style="color:var(--muted);font-size:13px;margin-top:4px">
                                <?= date('g:i A', strtotime($r['reservation_time'])) ?> &nbsp;·&nbsp; <?= $r['guests'] ?> guests
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Approvals -->
                <div class="card">
                    <div class="section-head">
                        <h2>Quick Approvals</h2>
                    </div>
                    <?php if ($quick_approvals->num_rows === 0): ?>
                    <p style="color:var(--muted);font-size:14px">No pending approvals.</p>
                    <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <?php while ($r = $quick_approvals->fetch_assoc()): ?>
                        <div style="background:var(--bg3);border:1px solid rgba(201,168,76,.3);border-radius:var(--radius);padding:14px">
                            <div class="flex-between mb-8">
                                <div>
                                    <strong><?= htmlspecialchars($r['customer']) ?></strong>
                                    <span class="badge badge-pending" style="margin-left:8px">NEW</span>
                                </div>
                                <span style="color:var(--muted);font-size:12px">Table <?= htmlspecialchars($r['table_no']) ?></span>
                            </div>
                            <div style="color:var(--muted);font-size:13px;margin-bottom:12px">
                                <?= date('D, M d', strtotime($r['reservation_date'])) ?> at <?= date('g:i A', strtotime($r['reservation_time'])) ?>
                                &nbsp;·&nbsp; <?= $r['guests'] ?> guests
                            </div>
                            <div class="flex gap-8">
                                <a href="reservations.php?approve=<?= $r['id'] ?>" class="btn btn-sm btn-success">✓ Approve</a>
                                <a href="reservations.php?decline=<?= $r['id'] ?>" class="btn btn-sm btn-danger">✕ Decline</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table Status Grid -->
            <div class="card">
                <div class="section-head">
                    <h2>Real-Time Floor Layout</h2>
                    <a href="tables.php" class="btn btn-sm btn-outline">Manage Tables</a>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;font-size:13px">
                    <span><span style="color:var(--success)">●</span> Available</span>
                    <span><span style="color:var(--warning)">●</span> Reserved</span>
                    <span><span style="color:var(--danger)">●</span> Occupied</span>
                    <span><span style="color:var(--muted)">●</span> Maintenance</span>
                </div>
                <?php $tables = $conn->query("SELECT * FROM restaurant_tables ORDER BY table_no"); ?>
                <div class="table-grid">
                    <?php while ($t = $tables->fetch_assoc()):
                        $dot_colors = ['available'=>'var(--success)','reserved'=>'var(--warning)','occupied'=>'var(--danger)','maintenance'=>'var(--muted)'];
                        $dot = $dot_colors[$t['status']] ?? 'var(--muted)';
                    ?>
                    <div class="table-tile <?= $t['status'] ?>">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $dot ?>;margin:0 auto 8px"></div>
                        <div class="t-no"><?= htmlspecialchars($t['table_no']) ?></div>
                        <div class="t-seats"><?= $t['seats'] ?> seats</div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:<?= $dot ?>;margin-top:4px"><?= $t['status'] ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
