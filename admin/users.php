<?php
require "../config/database.php";
require "../config/auth.php";
require_role("admin");

$success = "";
$error   = "";

// Toggle status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $uid       = (int)$_GET['id'];
    $newStatus = $_GET['toggle'] === 'activate' ? 'active' : 'inactive';
    $self      = (int)$_SESSION['user']['id'];
    $stmt      = $conn->prepare("UPDATE users SET status=? WHERE id=? AND id != ?");
    $stmt->bind_param("sii", $newStatus, $uid, $self);
    $stmt->execute();
    header("Location: users.php?msg=updated");
    exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === (int)$_SESSION['user']['id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        header("Location: users.php?msg=deleted");
        exit;
    }
}

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role'];
    $allowed_roles = ['admin','manager','customer','kitchen'];

    if (empty($name) || empty($email) || empty($pass) || !in_array($role, $allowed_roles)) {
        $error = "All fields are required and role must be valid.";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $chk->bind_param("s", $email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $status = 'active';
            $stmt   = $conn->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $hashed, $role, $status);
            $stmt->execute();
            header("Location: users.php?msg=added");
            exit;
        }
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $uid  = (int)$_POST['uid'];
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE users SET name=?, role=?, status=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $role, $status, $uid);
    $stmt->execute();
    header("Location: users.php?msg=updated");
    exit;
}

if (isset($_GET['msg'])) {
    $msgs = ['added'=>'User added.','updated'=>'User updated.','deleted'=>'User deleted.'];
    $success = $msgs[$_GET['msg']] ?? '';
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';
$where = "WHERE 1=1";
$params = [];
$types  = '';
if ($search) {
    $s = "%$search%";
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = $s; $params[] = $s;
    $types .= 'ss';
}
if ($filter_role) {
    $where .= " AND role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

$stmt = $conn->prepare("SELECT id,name,email,role,status,created_at FROM users $where ORDER BY id DESC");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Manage Users</h1>
            <span class="badge-role">Administrator</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Add User -->
            <div class="card mb-20">
                <h2>Add New User</h2>
                <form method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name</label><input name="name" placeholder="John Smith" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@example.com" required></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Min 6 chars" required></div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="customer">Customer</option>
                                <option value="manager">Manager</option>
                                <option value="kitchen">Kitchen Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn">➕ Add User</button>
                </form>
            </div>

            <!-- Filter -->
            <div class="card mb-20">
                <form method="GET" class="form-row" style="align-items:flex-end">
                    <div class="form-group" style="flex:2">
                        <label>Search by name or email</label>
                        <input name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label>Filter by role</label>
                        <select name="role">
                            <option value="">All Roles</option>
                            <option value="admin"    <?= $filter_role==='admin'    ?'selected':'' ?>>Admin</option>
                            <option value="manager"  <?= $filter_role==='manager'  ?'selected':'' ?>>Manager</option>
                            <option value="customer" <?= $filter_role==='customer' ?'selected':'' ?>>Customer</option>
                            <option value="kitchen"  <?= $filter_role==='kitchen'  ?'selected':'' ?>>Kitchen</option>
                        </select>
                    </div>
                    <div style="padding-bottom:16px"><button class="btn btn-outline">Filter</button>
                    <a href="users.php" class="btn btn-dark" style="margin-left:8px">Clear</a></div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="section-head">
                    <h2>All Users</h2>
                </div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
                                <th>Status</th><th>Joined</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--muted)">#<?= $u['id'] ?></td>
                                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                                <td style="color:var(--muted);font-size:13px"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div class="flex gap-8">
                                        <!-- Inline edit modal trigger -->
                                        <button class="btn btn-sm btn-outline"
                                            onclick="openEdit(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= $u['role'] ?>', '<?= $u['status'] ?>')">
                                            ✏️ Edit
                                        </button>
                                        <?php if ($u['id'] !== (int)$_SESSION['user']['id']): ?>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <a class="btn btn-sm btn-warning"
                                                   href="users.php?toggle=deactivate&id=<?= $u['id'] ?>"
                                                   onclick="return confirm('Deactivate this user?')">⏸ Deactivate</a>
                                            <?php else: ?>
                                                <a class="btn btn-sm btn-success"
                                                   href="users.php?toggle=activate&id=<?= $u['id'] ?>">▶ Activate</a>
                                            <?php endif; ?>
                                            <a class="btn btn-sm btn-danger"
                                               href="users.php?delete=<?= $u['id'] ?>"
                                               onclick="return confirm('Delete this user permanently?')">🗑 Delete</a>
                                        <?php endif; ?>
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

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:420px;max-width:95vw">
        <div class="section-head"><h2>Edit User</h2>
            <button onclick="document.getElementById('editModal').style.display='none'"
                    style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="uid" id="edit_uid">
            <div class="form-group"><label>Full Name</label><input name="name" id="edit_name" required></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="edit_role">
                    <option value="customer">Customer</option>
                    <option value="manager">Manager</option>
                    <option value="kitchen">Kitchen Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-8">
                <button class="btn">Save Changes</button>
                <button type="button" class="btn btn-dark"
                    onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
function openEdit(id, name, role, status) {
    document.getElementById('edit_uid').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').style.display = 'flex';
}
</script>
</body>
</html>
