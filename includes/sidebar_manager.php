<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Floor Manager</small></div>
    <nav>
        <a href="/restaurant 2/manager/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="ico">📊</span> Dashboard
        </a>
        <a href="/restaurant 2/manager/reservations.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active' : '' ?>">
            <span class="ico">🗓️</span> Reservations
        </a>
        <a href="/restaurant 2/manager/tables.php" class="<?= basename($_SERVER['PHP_SELF']) === 'tables.php' ? 'active' : '' ?>">
            <span class="ico">🪑</span> Tables
        </a>
        <a href="/restaurant 2/manager/invoices.php" class="<?= basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : '' ?>">
            <span class="ico">🧾</span> Invoices & Payments
        </a>
        <a href="/restaurant 2/manager/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
        <a href="/restaurant 2/logout.php">⬅ Logout</a>
    </div>
</aside>
