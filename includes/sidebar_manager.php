<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Floor Manager</small></div>
    <nav>
<<<<<<< HEAD
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
=======
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
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
<<<<<<< HEAD
        <a href="<?= BASE_URL ?>logout.php">⬅ Logout</a>
=======
        <a href="/restaurant 2/logout.php">⬅ Logout</a>
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
    </div>
</aside>
