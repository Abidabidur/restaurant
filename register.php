<?php
require "config/database.php";
require "config/auth.php";

if (isset($_SESSION['user'])) {
    redirect_dashboard();
}

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"]     ?? '');
    $email    = trim($_POST["email"]    ?? '');
    $password = $_POST["password"]      ?? '';
    $confirm  = $_POST["confirm"]       ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare(
                "INSERT INTO users (name, email, password, role, status)
                 VALUES (?, ?, ?, 'customer', 'active')"
            );
            $stmt->bind_param("sss", $name, $email, $hashed);
            if ($stmt->execute()) {
                // Auto-login
                $uid = $conn->insert_id;
                $_SESSION['user'] = [
                    'id'   => $uid,
                    'name' => $name,
                    'email'=> $email,
                    'role' => 'customer',
                ];
                header("Location: " . BASE_URL . "customer/dashboard.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Aura Bistro</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrap">
    <!-- Left panel -->
    <div class="auth-left">
        <div class="al-logo">Aura Bistro</div>
        <div>
            <div class="al-headline">Your table is<br><em>waiting</em> for you.</div>
            <p class="al-sub">Join Aura Bistro — browse curated menus, book tables, place orders, and track every experience online.</p>
        </div>
        <div class="al-footer">AIUB — CSC 3215 Web Technologies — Group 08</div>
    </div>

    <!-- Right panel -->
    <div class="auth-right">
        <div class="auth-box">
            <h2>Create Account</h2>
            <p class="auth-sub">Sign up as a customer to start ordering and reserving tables.</p>

            <div class="auth-tabs">
                <a href="login.php">Sign In</a>
                <a href="register.php" class="active">Create Account</a>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="e.g. John Smith"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password <span style="color:var(--muted)">(min 6 characters)</span></label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" placeholder="••••••••" required>
                </div>

                <button class="btn" style="width:100%;justify-content:center;padding:12px">
                    Create My Account
                </button>
            </form>

            <p style="text-align:center;margin-top:18px;font-size:13px;color:var(--muted)">
                Already have an account? <a href="login.php">Sign In</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
