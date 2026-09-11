<?php
/**
 * Shared profile update logic — include in each role's profile.php
 * Expects: $conn, $_SESSION['user'], $back_url (dashboard link)
 */

$uid     = (int)$_SESSION['user']['id'];
$success = "";
$error   = "";

// Fetch fresh user data
$stmt = $conn->prepare("SELECT id, name, email, role, profile_pic FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid name and email.";
    } else {
        // Check email uniqueness
        $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ? LIMIT 1");
        $chk->bind_param("si", $email, $uid);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "That email is already used by another account.";
        } else {
            $upd = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $upd->bind_param("ssi", $name, $email, $uid);
            $upd->execute();

            // Refresh session
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['email'] = $email;
            $user['name']  = $name;
            $user['email'] = $email;
            $success = "Profile updated successfully.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    // Get current hash
    $ps = $conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $ps->bind_param("i", $uid); $ps->execute();
    $row = $ps->get_result()->fetch_assoc();

    if (!password_verify($current, $row['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid);
        $upd->execute();
        $success = "Password changed successfully.";
    }
}
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2" style="align-items:flex-start">
    <!-- Profile Info -->
    <div class="card">
        <h2>Profile Information</h2>
        <div class="avatar-upload mb-20">
            <div class="avatar-circle">
                <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/../uploads/' . $user['profile_pic'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <div style="color:var(--muted);font-size:13px;margin-top:4px"><?= htmlspecialchars($user['email']) ?></div>
                <span class="badge badge-<?= $user['role'] ?>" style="margin-top:8px;display:inline-block"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <button class="btn">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card">
        <h2>Change Password</h2>
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>New Password <span style="color:var(--muted)">(min 6 chars)</span></label>
                <input type="password" name="new_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </div>
            <button class="btn btn-outline">Update Password</button>
        </form>
    </div>
</div>
