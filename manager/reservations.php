<?php
require "../config/database.php";
require "../config/auth.php";
require_role("manager");

$success = "";

// Quick approve/decline from dashboard
if (isset($_GET['approve'])) {
    $rid  = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE reservations SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $rid);
    $stmt->execute();
    header("Location: reservations.php?msg=approved"); exit;
}
if (isset($_GET['decline'])) {
    $rid  = (int)$_GET['decline'];
    $stmt = $conn->prepare("UPDATE reservations SET status='cancelled' WHERE id=?");
    $stmt->bind_param("i", $rid);
    $stmt->execute();
    header("Location: reservations.php?msg=cancelled"); exit;
}

// Bulk action from form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $rid    = (int)$_POST['reservation_id'];
    $action = $_POST['action'];
    $map    = ['approve'=>'approved','cancel'=>'cancelled','waitlist'=>'waitlist'];
    if (isset($map[$action])) {
        $status = $map[$action];
        $stmt   = $conn->prepare("UPDATE reservations SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $rid);
        $stmt->execute();
        header("Location: reservations.php?msg=$action"); exit;
    }
}

$msg = $_GET['msg'] ?? '';
$msgs = ['approved'=>'Reservation approved.','cancelled'=>'Reservation cancelled.','waitlist'=>'Moved to waitlist.'];
if ($msg) $success = $msgs[$msg] ?? '';

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = match($filter) {
    'pending'  => "WHERE r.status='pending'",
    'approved' => "WHERE r.status='approved'",
    'cancelled'=> "WHERE r.status='cancelled'",
    'waitlist' => "WHERE r.status='waitlist'",
    'today'    => "WHERE r.reservation_date = CURDATE()",
    default    => "WHERE 1=1",
};

$all = $conn->query(
    "SELECT r.*, u.name AS customer, u.email AS cust_email, t.table_no, t.seats
     FROM reservations r
     JOIN users u ON u.id = r.customer_id
     JOIN restaurant_tables t ON t.id = r.table_id
     $where
     ORDER BY r.reservation_date ASC, r.reservation_time ASC"
);

$status_map = ['pending'=>'badge-pending','approved'=>'badge-approved','cancelled'=>'badge-cancelled','waitlist'=>'badge-waitlist'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservations — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_manager.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Manage Reservations</h1>
            <span class="badge-role">Floor Manager</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

            <!-- Filter tabs -->
            <div class="cat-tabs mb-20">
                <?php $tabs = ['all'=>'All','today'=>"Today",'pending'=>'Pending','approved'=>'Approved','waitlist'=>'Waitlist','cancelled'=>'Cancelled'];
                foreach ($tabs as $k => $l): ?>
                <a href="reservations.php?filter=<?= $k ?>" class="cat-tab <?= $filter===$k?'active':'' ?>"><?= $l ?></a>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="section-head"><h2>Reservations</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Guest</th><th>Table</th><th>Date &amp; Time</th><th>Guests</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($r = $all->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['customer']) ?></strong><br>
                                    <span style="color:var(--muted);font-size:12px"><?= htmlspecialchars($r['cust_email']) ?></span>
                                </td>
                                <td>
                                    <strong>Table <?= htmlspecialchars($r['table_no']) ?></strong><br>
                                    <span style="color:var(--muted);font-size:12px"><?= $r['seats'] ?> seats</span>
                                </td>
                                <td>
                                    <?= date('D, M d Y', strtotime($r['reservation_date'])) ?><br>
                                    <span style="color:var(--muted);font-size:13px"><?= date('g:i A', strtotime($r['reservation_time'])) ?></span>
                                </td>
                                <td><?= $r['guests'] ?></td>
                                <td><span class="badge <?= $status_map[$r['status']] ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'pending' || $r['status'] === 'waitlist'): ?>
                                    <div class="flex gap-8">
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn btn-sm btn-success">✓ Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Cancel this reservation?')">✕ Cancel</button>
                                        </form>
                                        <?php if ($r['status'] !== 'waitlist'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="waitlist">
                                            <button class="btn btn-sm btn-dark">⏳ Waitlist</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Cancel approved reservation?')">Cancel</button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color:var(--muted);font-size:13px">—</span>
                                    <?php endif; ?>
                                </td>
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
