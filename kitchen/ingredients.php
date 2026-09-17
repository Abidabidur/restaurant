<?php
require "../config/database.php";
require "../config/auth.php";
require_role("kitchen");

$success = "";
$error   = "";

// Add ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
    $name  = trim($_POST['name']);
    $qty   = (float)$_POST['quantity'];
    $unit  = trim($_POST['unit']);
    $reorder = (float)($_POST['reorder_level'] ?? 0);
    if (empty($name) || empty($unit)) {
        $error = "Name and unit are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ingredients(name, quantity, unit, reorder_level) VALUES(?,?,?,?)");
        $stmt->bind_param("sdsd", $name, $qty, $unit, $reorder);
        $stmt->execute();
        header("Location: ingredients.php?msg=added"); exit;
    }
}

// Update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id  = (int)$_POST['ingredient_id'];
    $qty = (float)$_POST['quantity'];
    $stmt = $conn->prepare("UPDATE ingredients SET quantity=? WHERE id=?");
    $stmt->bind_param("di", $qty, $id);
    $stmt->execute();
    header("Location: ingredients.php?msg=updated"); exit;
}

// Delete
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ingredients WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ingredients.php?msg=deleted"); exit;
}

$msg = $_GET['msg'] ?? '';
if ($msg) {
    $msgs = ['added'=>'Ingredient added.','updated'=>'Stock updated.','deleted'=>'Ingredient deleted.'];
    $success = $msgs[$msg] ?? '';
}

$ingredients = $conn->query("SELECT * FROM ingredients ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingredients — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_kitchen.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Ingredient Stock Management</h1>
            <span class="badge-role">Kitchen Staff</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Add -->
            <div class="card mb-20">
                <h2>Add Ingredient</h2>
                <form method="POST">
                    <input type="hidden" name="add_ingredient" value="1">
                    <div class="form-row">
                        <div class="form-group"><label>Ingredient Name</label><input name="name" placeholder="e.g. Chicken" required></div>
                        <div class="form-group"><label>Quantity</label><input type="number" name="quantity" step="0.01" min="0" placeholder="0" required></div>
                        <div class="form-group"><label>Unit</label><input name="unit" placeholder="kg / L / pcs" required></div>
                        <div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" step="0.01" min="0" value="0"></div>
                    </div>
                    <button class="btn">➕ Add Ingredient</button>
                </form>
            </div>

            <!-- List -->
            <div class="card">
                <div class="section-head"><h2>Current Stock</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>Ingredient</th><th>Quantity</th><th>Unit</th><th>Reorder Level</th><th>Status</th><th>Last Updated</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($ing = $ingredients->fetch_assoc()):
                            $low = isset($ing['reorder_level']) && $ing['quantity'] <= $ing['reorder_level'] && $ing['reorder_level'] > 0;
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ing['name']) ?></strong></td>
                                <td><strong style="color:<?= $low ? 'var(--danger)' : 'var(--text)' ?>"><?= number_format($ing['quantity'], 2) ?></strong></td>
                                <td style="color:var(--muted)"><?= htmlspecialchars($ing['unit']) ?></td>
                                <td style="color:var(--muted)"><?= isset($ing['reorder_level']) ? number_format($ing['reorder_level'],2) : '—' ?></td>
                                <td>
                                    <?php if ($low): ?>
                                    <span class="badge badge-cancelled">⚠ Low Stock</span>
                                    <?php else: ?>
                                    <span class="badge badge-available">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d, Y', strtotime($ing['updated_at'])) ?></td>
                                <td>
                                    <div class="flex gap-8">
                                        <button class="btn btn-sm btn-outline"
                                            onclick="openUpdate(<?= $ing['id'] ?>, '<?= htmlspecialchars(addslashes($ing['name'])) ?>', <?= $ing['quantity'] ?>)">
                                            📦 Update Stock
                                        </button>
                                        <a href="ingredients.php?delete=<?= $ing['id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this ingredient?')">🗑</a>
                                    </div>
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

<!-- Update Stock Modal -->
<div id="updateModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:380px;max-width:95vw">
        <div class="section-head">
            <h2>Update Stock</h2>
            <button onclick="document.getElementById('updateModal').style.display='none'"
                    style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer">✕</button>
        </div>
        <p style="color:var(--muted);font-size:14px;margin-bottom:16px" id="update_name_label"></p>
        <form method="POST">
            <input type="hidden" name="update_stock" value="1">
            <input type="hidden" name="ingredient_id" id="upd_id">
            <div class="form-group">
                <label>New Quantity</label>
                <input type="number" name="quantity" id="upd_qty" step="0.01" min="0" required>
            </div>
            <div class="flex gap-8">
                <button class="btn">Save</button>
                <button type="button" class="btn btn-dark"
                    onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
function openUpdate(id, name, qty) {
    document.getElementById('upd_id').value = id;
    document.getElementById('upd_qty').value = qty;
    document.getElementById('update_name_label').textContent = 'Ingredient: ' + name;
    document.getElementById('updateModal').style.display = 'flex';
}
</script>
</body>
</html>
