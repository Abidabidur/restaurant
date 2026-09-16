<?php
require "../config/database.php";
require "../config/auth.php";
require_role("customer");

$uid     = (int)$_SESSION['user']['id'];
$success = "";
$error   = "";

// Cancel reservation
if (isset($_GET['cancel'])) {
    $rid  = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE reservations SET status='cancelled' WHERE id=? AND customer_id=? AND status IN ('pending','waitlist')");
    $stmt->bind_param("ii", $rid, $uid);
    $stmt->execute();
    header("Location: reservation.php?msg=cancelled"); exit;
}

$msg = $_GET['msg'] ?? '';

// New reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $table_id = (int)$_POST['table_id'];
    $date     = $_POST['date'];
    $time     = $_POST['time'];
    $guests   = max(1, (int)$_POST['guests']);

    if (!$table_id || !$date || !$time) {
        $error = "All fields are required.";
    } elseif ($date < date('Y-m-d')) {
        $error = "Please select a future date.";
    } else {
        // Check table capacity
        $tc = $conn->prepare("SELECT seats FROM restaurant_tables WHERE id=? LIMIT 1");
        $tc->bind_param("i", $table_id); $tc->execute();
        $table = $tc->get_result()->fetch_assoc();

        if (!$table) {
            $error = "Table not found.";
        } elseif ($guests > $table['seats']) {
            $error = "This table only seats {$table['seats']} guests.";
        } else {
            // Check for conflict (same table, same date/time)
            $cx = $conn->prepare(
                "SELECT id FROM reservations WHERE table_id=? AND reservation_date=? AND reservation_time=? AND status NOT IN ('cancelled') LIMIT 1"
            );
            $cx->bind_param("iss", $table_id, $date, $time);
            $cx->execute();
            $status = $cx->get_result()->num_rows > 0 ? 'waitlist' : 'pending';

            $ins = $conn->prepare(
                "INSERT INTO reservations(customer_id,table_id,reservation_date,reservation_time,guests,status)
                 VALUES(?,?,?,?,?,?)"
            );
            $ins->bind_param("iissis", $uid, $table_id, $date, $time, $guests, $status);
            $ins->execute();

            if ($status === 'waitlist') {
                $success = "That slot is taken. You've been added to the waiting list.";
            } else {
                $success = "Reservation submitted! Pending approval from the manager.";
            }
        }
    }
}

// Available tables
$tables = $conn->query("SELECT * FROM restaurant_tables WHERE status IN ('available','reserved') ORDER BY table_no");

// My reservations
$my_res = $conn->query(
    "SELECT r.*, t.table_no, t.seats
     FROM reservations r JOIN restaurant_tables t ON t.id=r.table_id
     WHERE r.customer_id=$uid ORDER BY r.reservation_date DESC, r.id DESC"
);

$status_map = ['pending'=>'badge-pending','approved'=>'badge-approved','cancelled'=>'badge-cancelled','waitlist'=>'badge-waitlist'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Table Reservation — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Table Reservation</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($msg === 'cancelled'): ?><div class="alert alert-info">ℹ️ Reservation cancelled.</div><?php endif; ?>

            <div class="grid-2 mb-28" style="align-items:flex-start">
                <!-- Booking form -->
                <div class="card">
                    <h2>Request Reservation</h2>
                    <p style="color:var(--muted);font-size:14px;margin-bottom:20px">Complete the details below and our team will confirm your booking.</p>
                    <form method="POST">
                        <input type="hidden" name="book" value="1">
                        <div class="form-group">
                            <label>Select Table</label>
                            <select name="table_id" required>
                                <option value="">Choose a table...</option>
                                <?php while ($t = $tables->fetch_assoc()): ?>
                                <option value="<?= $t['id'] ?>">
                                    Table <?= htmlspecialchars($t['table_no']) ?> — <?= $t['seats'] ?> seats (<?= $t['status'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Time</label>
                                <input type="time" name="time" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Number of Guests</label>
                            <input type="number" name="guests" min="1" max="20" placeholder="e.g. 2" required>
                        </div>
                        <button class="btn" style="width:100%;justify-content:center;padding:12px">
                            Confirm Table Reservation
                        </button>
                    </form>
                </div>

                <!-- Policies -->
                <div class="card">
                    <h2>Important Policies</h2>
                    <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;color:var(--muted)">
                        <div style="background:var(--bg3);border-radius:var(--radius);padding:12px 14px">
                            📋 We must receive tables for a minimum of 30 minutes prior to scheduled booking time.
                        </div>
                        <div style="background:var(--bg3);border-radius:var(--radius);padding:12px 14px">
                            👥 For groups of 8 or more, a refundable appetizer standard service charge may apply.
                        </div>
                        <div style="background:var(--bg3);border-radius:var(--radius);padding:12px 14px">
                            ⚠️ Special requests (e.g. rooftop view, open booth) attempted but cannot be 100% guaranteed.
                        </div>
                        <div style="background:var(--bg3);border-radius:var(--radius);padding:12px 14px">
                            ⏳ If the slot is already taken, you'll be placed on the <strong style="color:var(--gold)">waiting list</strong>.
                        </div>
                    </div>
                </div>
            </div>

            <!-- My reservations -->
            <div class="card">
                <div class="section-head">
                    <h2>My Reservations</h2>
                </div>
                <?php if ($my_res->num_rows === 0): ?>
                <p style="color:var(--muted);padding:16px 0">No reservations yet.</p>
                <?php else: ?>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Table</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($r = $my_res->fetch_assoc()): ?>
                            <tr>
                                <td><strong>Table <?= htmlspecialchars($r['table_no']) ?></strong><br><span style="color:var(--muted);font-size:12px"><?= $r['seats'] ?> seats</span></td>
                                <td><?= date('D, M d Y', strtotime($r['reservation_date'])) ?></td>
                                <td><?= date('g:i A', strtotime($r['reservation_time'])) ?></td>
                                <td><?= $r['guests'] ?></td>
                                <td><span class="badge <?= $status_map[$r['status']] ?? 'badge-pending' ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td>
                                    <?php if (in_array($r['status'], ['pending','waitlist'])): ?>
                                    <a href="reservation.php?cancel=<?= $r['id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Cancel this reservation?')">Cancel</a>
                                    <?php else: ?>
                                    <span style="color:var(--muted);font-size:13px">—</span>
                                    <?php endif; ?>
                                </td>
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
