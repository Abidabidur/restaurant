<?php
require "../config/database.php";
require "../config/auth.php";
require_role("manager");

$success = "";
$error   = "";

// Mark payment as paid (also saves payment method)
if (isset($_GET['mark_paid'])) {
    $pid    = (int)$_GET['mark_paid'];
    $method = in_array($_GET['method'] ?? '', ['cash','card','mobile']) ? $_GET['method'] : 'cash';
    $stmt   = $conn->prepare("UPDATE payments SET status='paid', method=?, paid_at=NOW() WHERE id=?");
    $stmt->bind_param("si", $method, $pid);
    $stmt->execute();
    header("Location: invoices.php?msg=paid"); exit;
}

// Create invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $oid      = (int)$_POST['order_id'];
    $discount = max(0, (float)$_POST['discount']);

    // Get order total
    $os = $conn->prepare("SELECT total FROM orders WHERE id=? LIMIT 1");
    $os->bind_param("i", $oid); $os->execute();
    $order = $os->get_result()->fetch_assoc();

    if (!$order) {
        $error = "Order not found.";
    } else {
        $subtotal = $order['total'];
        $total    = max(0, round($subtotal - $discount, 2));

        // Check if invoice already exists
        $ec = $conn->prepare("SELECT id FROM invoices WHERE order_id=? LIMIT 1");
        $ec->bind_param("i", $oid); $ec->execute();
        if ($ec->get_result()->num_rows > 0) {
            $error = "Invoice already exists for Order #$oid.";
        } else {
            $ins = $conn->prepare("INSERT INTO invoices(order_id, subtotal, discount, total) VALUES(?,?,?,?)");
            $ins->bind_param("iddd", $oid, $subtotal, $discount, $total);
            $ins->execute();
            header("Location: invoices.php?msg=created"); exit;
        }
    }
}

// Apply voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
    $inv_id = (int)$_POST['invoice_id'];
    $code   = trim($_POST['voucher_code']);

    $vs = $conn->prepare("SELECT * FROM vouchers WHERE code=? AND status='active' AND valid_from <= CURDATE() AND valid_to >= CURDATE() LIMIT 1");
    $vs->bind_param("s", $code); $vs->execute();
    $voucher = $vs->get_result()->fetch_assoc();

    if (!$voucher) {
        $error = "Invalid or expired voucher code.";
    } else {
        // Get invoice
        $iv = $conn->prepare("SELECT * FROM invoices WHERE id=? LIMIT 1");
        $iv->bind_param("i", $inv_id); $iv->execute();
        $inv = $iv->get_result()->fetch_assoc();

        if (!$inv) { $error = "Invoice not found."; }
        elseif ($inv['subtotal'] < $voucher['min_order_amount']) {
            $error = "Order total is below the minimum ৳{$voucher['min_order_amount']} required for this voucher.";
        } else {
            $disc = $voucher['discount_type'] === 'percentage'
                ? round($inv['subtotal'] * $voucher['discount_value'] / 100, 2)
                : $voucher['discount_value'];
            $new_total = max(0, $inv['subtotal'] - $disc);
            $upd = $conn->prepare("UPDATE invoices SET discount=?, total=? WHERE id=?");
            $upd->bind_param("ddi", $disc, $new_total, $inv_id);
            $upd->execute();
            $success = "Voucher applied! Discount: ৳" . number_format($disc,2);
        }
    }
}

$msg = $_GET['msg'] ?? '';
if ($msg === 'paid')    $success = "Payment marked as paid.";
if ($msg === 'created') $success = "Invoice created.";

// Stats
$total_collected = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='paid'")->fetch_assoc()['s'];
$total_pending   = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='unpaid'")->fetch_assoc()['s'];
$total_inv       = $conn->query("SELECT COUNT(*) c FROM invoices")->fetch_assoc()['c'];

// Orders without invoices
$orders_no_inv = $conn->query(
    "SELECT o.id, o.total, u.name AS customer
     FROM orders o JOIN users u ON u.id=o.customer_id
     WHERE o.id NOT IN (SELECT order_id FROM invoices)
     AND o.status NOT IN ('cancelled')
     ORDER BY o.id DESC"
);

// All invoices
$invoices = $conn->query(
    "SELECT i.*, o.order_type, u.name AS customer, p.id AS pay_id, p.method, p.status AS pay_status, p.paid_at
     FROM invoices i
     JOIN orders o ON o.id = i.order_id
     JOIN users u ON u.id = o.customer_id
     LEFT JOIN payments p ON p.order_id = i.order_id
     ORDER BY i.id DESC"
);

// Unpaid payments (without invoice)
$unpaid = $conn->query(
    "SELECT p.id, p.amount, p.method, p.status, o.id AS order_id, u.name AS customer
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     JOIN users u ON u.id = o.customer_id
     WHERE p.status = 'unpaid'
     ORDER BY p.id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoices &amp; Payments — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_manager.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Invoice &amp; Payment Management</h1>
            <span class="badge-role">Floor Manager</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid mb-28">
                <div class="stat-card">
                    <div class="stat-ico green">💰</div>
                    <div><div class="stat-val">৳<?= number_format($total_collected,2) ?></div><div class="stat-lbl">Total Collected</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico red">⏳</div>
                    <div><div class="stat-val">৳<?= number_format($total_pending,2) ?></div><div class="stat-lbl">Outstanding</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-ico blue">🧾</div>
                    <div><div class="stat-val"><?= $total_inv ?></div><div class="stat-lbl">Total Invoices</div></div>
                </div>
            </div>

            <!-- Create Invoice + Apply Voucher -->
            <div class="grid-2 mb-28">
                <div class="card">
                    <h2>Create Invoice</h2>
                    <?php if ($orders_no_inv->num_rows === 0): ?>
                    <p style="color:var(--muted);font-size:14px">All orders already have invoices.</p>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="create_invoice" value="1">
                        <div class="form-group">
                            <label>Select Order</label>
                            <select name="order_id" required>
                                <option value="">Choose order...</option>
                                <?php while ($o = $orders_no_inv->fetch_assoc()): ?>
                                <option value="<?= $o['id'] ?>">Order #<?= $o['id'] ?> — <?= htmlspecialchars($o['customer']) ?> — ৳<?= number_format($o['total'],2) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Manual Discount (৳)</label>
                            <input type="number" name="discount" step="0.01" min="0" value="0">
                        </div>
                        <button class="btn">🧾 Create Invoice</button>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Apply Voucher to Invoice</h2>
                    <form method="POST">
                        <input type="hidden" name="apply_voucher" value="1">
                        <div class="form-group">
                            <label>Invoice ID</label>
                            <input type="number" name="invoice_id" placeholder="Invoice #" required>
                        </div>
                        <div class="form-group">
                            <label>Voucher Code</label>
                            <input name="voucher_code" placeholder="e.g. SAVE20" required>
                        </div>
                        <button class="btn">Apply Voucher</button>
                    </form>
                </div>
            </div>

            <!-- Unpaid Payments -->
            <?php if ($unpaid->num_rows > 0): ?>
            <div class="card mb-20">
                <div class="section-head"><h2>Outstanding Payments</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Payment ID</th><th>Order</th><th>Customer</th><th>Amount</th><th>Method</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($p = $unpaid->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $p['id'] ?></td>
                                <td>Order #<?= $p['order_id'] ?></td>
                                <td><?= htmlspecialchars($p['customer']) ?></td>
                                <td class="text-gold">৳<?= number_format($p['amount'],2) ?></td>
                                <td>
                                    <select id="method_<?= $p['id'] ?>" style="width:auto;margin:0;padding:6px 10px;font-size:12px;background:var(--bg3);color:var(--text);border:1px solid var(--border);border-radius:6px">
                                        <option value="cash"   <?= $p['method']==='cash'  ?'selected':''?>>Cash</option>
                                        <option value="card"   <?= $p['method']==='card'  ?'selected':''?>>Card</option>
                                        <option value="mobile" <?= $p['method']==='mobile'?'selected':''?>>Mobile</option>
                                    </select>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-success"
                                       onclick="markPaid(<?= $p['id'] ?>); return false;"
                                       href="#">✅ Mark Paid</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Invoices -->
            <div class="card">
                <div class="section-head"><h2>All Invoices</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Invoice</th><th>Order</th><th>Customer</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($inv = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $inv['id'] ?></strong></td>
                                <td>Order #<?= $inv['order_id'] ?></td>
                                <td><?= htmlspecialchars($inv['customer']) ?></td>
                                <td style="color:var(--muted)">৳<?= number_format($inv['subtotal'],2) ?></td>
                                <td style="color:var(--success)">
                                    <?= $inv['discount'] > 0 ? '-৳'.number_format($inv['discount'],2) : '—' ?>
                                </td>
                                <td class="text-gold"><strong>৳<?= number_format($inv['total'],2) ?></strong></td>
                                <td>
                                    <?php if ($inv['pay_status']): ?>
                                    <span class="badge badge-<?= $inv['pay_status'] ?>">
                                        <?= ucfirst($inv['method'] ?? '') ?> <?= ucfirst($inv['pay_status']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-unpaid">No Payment Record</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d Y', strtotime($inv['created_at'])) ?></td>
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
function markPaid(pid) {
    const method = document.getElementById('method_' + pid).value;
    if (!confirm('Mark payment #' + pid + ' as paid via ' + method + '?')) return;
    window.location.href = 'invoices.php?mark_paid=' + pid + '&method=' + method;
}
</script>
</html>
