<?php
require "config/database.php";
require "config/auth.php";

// If already logged in, redirect
if (isset($_SESSION['user'])) {
    redirect_dashboard();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role, status
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $user['status'] === 'inactive') {
            $error = "Your account has been deactivated. Please contact admin.";
        } elseif ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            redirect_dashboard();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Aura Bistro</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrap">
    <!-- Left panel -->
    <div class="auth-left">
        <div class="al-logo">Aura Bistro</div>
        <div>
            <div class="al-headline">Crafting <em>unforgettable</em> dining rituals, from kitchen to guest.</div>
            <p class="al-sub">Access the digital hub of Aura Bistro — manage reservations, curate seasonal menus, masterworks, and more.</p>
        </div>
        <div class="al-footer">Grand Horizon Hotel, 4th Floor &nbsp;|&nbsp; v4.4 960242</div>
    </div>

    <!-- Right panel -->
    <div class="auth-right">
        <div class="auth-box">
            <h2>Welcome back</h2>
            <p class="auth-sub">Please enter your credentials to access the Bistro management console.</p>

            <div class="auth-tabs">
                <a href="login.php" class="active">Sign In</a>
                <a href="register.php">Create Account</a>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="e.g. admin@aurabistro.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••••••" required>
                </div>

                <button class="btn" style="width:100%;justify-content:center;padding:12px">
                    🔒 Secure Sign In
                </button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--muted)">
                Authorized personnel only. Sessions are fully audited.
            </p>

            <div class="divider"></div>

            <div style="background:var(--bg3);border-radius:var(--radius);padding:14px 16px;font-size:12px;color:var(--muted)">
                <strong style="color:var(--text)">Demo Credentials:</strong><br>
                Admin: admin@restaurant.com / 123456<br>
                Manager: manager@restaurant.com / 123456<br>
                Kitchen: kitchen@restaurant.com / 123456<br>
                Customer: customer@restaurant.com / 123456
            </div>
        </div>
    </div>
</div>

</body>
</html>
