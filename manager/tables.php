<?php
require "../config/database.php";
require "../config/auth.php";
require_role("manager");

$success = "";
$error   = "";

// Add table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $table_no = trim($_POST['table_no']);
    $seats    = max(1, (int)$_POST['seats']);
    $status   = $_POST['status'];
    $location = trim($_POST['location'] ?? '');
    $allowed  = ['available','reserved','occupied','maintenance'];

    if (empty($table_no)) { $error = "Table number is required."; }
    elseif (!in_array($status, $allowed)) { $error = "Invalid status."; }
    else {
        $chk = $conn->prepare("SELECT id FROM restaurant_tables WHERE table_no=? LIMIT 1");
        $chk->bind_param("s", $table_no); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Table number already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO restaurant_tables(table_no,seats,status,location) VALUES(?,?,?,?)");
            $stmt->bind_param("siss", $table_no, $seats, $status, $location);
            $stmt->execute();
            header("Location: tables.php?msg=added"); exit;
        }
    }
}

// Update table status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $tid    = (int)$_POST['table_id'];
    $status = $_POST['status'];
    $seats  = max(1,(int)$_POST['seats']);
    $loc    = trim($_POST['location'] ?? '');
    $stmt   = $conn->prepare("UPDATE restaurant_tables SET status=?, seats=?, location=? WHERE id=?");
    $stmt->bind_param("sisi", $status, $seats, $loc, $tid);
    $stmt->execute();
    header("Location: tables.php?msg=updated"); exit;
}

// Delete
if (isset($_GET['delete'])) {
    $tid  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id=?");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    header("Location: tables.php?msg=deleted"); exit;
}

$msg = $_GET['msg'] ?? '';
if ($msg) {
    $msgs = ['added'=>'Table added.','updated'=>'Table updated.','deleted'=>'Table deleted.'];
    $success = $msgs[$msg] ?? '';
}

$tables = $conn->query("SELECT * FROM restaurant_tables ORDER BY table_no");
$table_list = [];
while ($t = $tables->fetch_assoc()) $table_list[] = $t;

$status_map = ['available'=>'badge-available','reserved'=>'badge-reserved','occupied'=>'badge-occupied','maintenance'=>'badge-maintenance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Tables — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_manager.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Manage Tables</h1>
            <span class="badge-role">Floor Manager</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="grid-2 mb-28" style="align-items:flex-start">
                <!-- Add Table -->
                <div class="card">
                    <h2>Add New Table</h2>
                    <form method="POST">
                        <input type="hidden" name="add_table" value="1">
                        <div class="form-row">
                            <div class="form-group"><label>Table Number</label><input name="table_no" placeholder="e.g. T-05" required></div>
                            <div class="form-group"><label>Seats</label><input type="number" name="seats" min="1" value="4" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="available">Available</option>
                                    <option value="reserved">Reserved</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Location (optional)</label><input name="location" placeholder="e.g. Rooftop, Main Floor"></div>
                        </div>
                        <button class="btn">➕ Add Table</button>
                    </form>
                </div>

                <!-- Summary -->
                <div class="card">
                    <h2>Table Summary</h2>
                    <?php
                    $summary = $conn->query("SELECT status, COUNT(*) c FROM restaurant_tables GROUP BY status");
                    $counts  = ['available'=>0,'reserved'=>0,'occupied'=>0,'maintenance'=>0];
                    while ($s = $summary->fetch_assoc()) $counts[$s['status']] = $s['c'];
                    $icons = ['available'=>'✅','reserved'=>'🔸','occupied'=>'🔴','maintenance'=>'🔧'];
                    ?>
                    <div class="table-grid" style="grid-template-columns:repeat(2,1fr)">
                        <?php foreach ($counts as $status => $count): ?>
                        <div class="table-tile <?= $status ?>" style="cursor:default">
                            <div style="font-size:24px;margin-bottom:6px"><?= $icons[$status] ?></div>
                            <div class="t-no"><?= $count ?></div>
                            <div class="t-seats"><?= ucfirst($status) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Tables List -->
            <div class="card">
                <div class="section-head"><h2>All Tables</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Table No</th><th>Seats</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($table_list as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['table_no']) ?></strong></td>
                                <td><?= $t['seats'] ?></td>
                                <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($t['location'] ?? '—') ?></td>
                                <td><span class="badge <?= $status_map[$t['status']] ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td>
                                    <div class="flex gap-8">
                                        <button class="btn btn-sm btn-outline"
                                            onclick="openEdit(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['table_no'])) ?>', <?= $t['seats'] ?>, '<?= $t['status'] ?>', '<?= htmlspecialchars(addslashes($t['location'] ?? '')) ?>')">
                                            ✏️ Edit
                                        </button>
                                        <a href="tables.php?delete=<?= $t['id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete table <?= htmlspecialchars($t['table_no']) ?>?')">🗑</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:420px;max-width:95vw">
        <div class="section-head">
            <h2>Edit Table</h2>
            <button onclick="document.getElementById('editModal').style.display='none'"
                    style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_table" value="1">
            <input type="hidden" name="table_id" id="et_id">
            <div class="form-group"><label>Table Number (readonly)</label><input id="et_no" readonly style="opacity:.5"></div>
            <div class="form-row">
                <div class="form-group"><label>Seats</label><input type="number" name="seats" id="et_seats" min="1" required></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="et_status">
                        <option value="available">Available</option>
                        <option value="reserved">Reserved</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Location</label><input name="location" id="et_loc" placeholder="e.g. Rooftop"></div>
            <div class="flex gap-8">
                <button class="btn">Save Changes</button>
                <button type="button" class="btn btn-dark" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
function openEdit(id, no, seats, status, loc) {
    document.getElementById('et_id').value     = id;
    document.getElementById('et_no').value     = no;
    document.getElementById('et_seats').value  = seats;
    document.getElementById('et_status').value = status;
    document.getElementById('et_loc').value    = loc;
    document.getElementById('editModal').style.display = 'flex';
}
</script>
</body>
</html>
