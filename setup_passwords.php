<?php
/**
 * ONE-TIME SETUP SCRIPT — localhost only
 * Run this once after importing restaurant.sql
 * then DELETE this file.
 */

// Restrict to localhost
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die("403 Forbidden — localhost only.");
}

require "config/database.php";
require "config/auth.php";

$accounts = [
    'admin@restaurant.com'    => '123456',
    'manager@restaurant.com'  => '123456',
    'kitchen@restaurant.com'  => '123456',
    'customer@restaurant.com' => '123456',
];

$results = [];
foreach ($accounts as $email => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    $stmt->execute();
    $results[] = [
        'email'   => $email,
        'updated' => $stmt->affected_rows > 0,
        'hash'    => substr($hash, 0, 30) . '...',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup Passwords — Aura Bistro</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #0d0d0d; color: #e8e8e8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .box { background: #1e1e1e; border: 1px solid #2a2a2a; border-radius: 14px; padding: 36px 40px; max-width: 520px; width: 95vw; }
    h1 { color: #c9a84c; margin-bottom: 6px; }
    p  { color: #888; font-size: 14px; margin-bottom: 24px; }
    .row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #2a2a2a; font-size: 14px; }
    .ok  { color: #4caf7d; font-weight: 700; }
    .fail{ color: #e05252; font-weight: 700; }
    .warn { background: #2a1a00; border: 1px solid #c9a84c44; border-radius: 10px; padding: 14px 16px; margin-top: 20px; font-size: 13px; color: #c9a84c; }
    a { display: inline-block; margin-top: 20px; background: #c9a84c; color: #0d0d0d; padding: 10px 22px; border-radius: 8px; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>
<div class="box">
    <h1>✅ Password Setup</h1>
    <p>Bcrypt hashes have been applied to all demo accounts. All passwords are <strong style="color:#e8e8e8">123456</strong>.</p>

    <?php foreach ($results as $r): ?>
    <div class="row">
        <span><?= htmlspecialchars($r['email']) ?></span>
        <?php if ($r['updated']): ?>
            <span class="ok">✓ Updated</span>
        <?php else: ?>
            <span class="fail">✗ Not found</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="warn">
        ⚠️ <strong>Security Notice:</strong> Delete this file after setup.<br>
        Path: <code><?= BASE_URL ?>setup_passwords.php</code>
    </div>

    <a href="<?= BASE_URL ?>login.php">→ Go to Login</a>
</div>
</body>
</html>
