<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Floor Manager</small></div>
    <nav>
        <a href="<?= BASE_URL ?>manager/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="ico">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>manager/reservations.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active' : '' ?>">
            <span class="ico">🗓️</span> Reservations
        </a>
        <a href="<?= BASE_URL ?>manager/tables.php" class="<?= basename($_SERVER['PHP_SELF']) === 'tables.php' ? 'active' : '' ?>">
            <span class="ico">🪑</span> Tables
        </a>
        <a href="<?= BASE_URL ?>manager/invoices.php" class="<?= basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : '' ?>">
            <span class="ico">🧾</span> Invoices & Payments
        </a>
        <a href="<?= BASE_URL ?>manager/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
        <a href="<?= BASE_URL ?>logout.php">⬅ Logout</a>
    </div>
</aside>
