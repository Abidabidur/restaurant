<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Admin Console</small></div>
    <nav>
        <a href="/restaurant 2/admin/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="ico">📊</span> Dashboard
        </a>
        <a href="/restaurant 2/admin/users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
            <span class="ico">👥</span> Manage Users
        </a>
        <a href="/restaurant 2/admin/foods.php" class="<?= basename($_SERVER['PHP_SELF']) === 'foods.php' ? 'active' : '' ?>">
            <span class="ico">🍽️</span> Food Menu
        </a>
        <a href="/restaurant 2/admin/revenue.php" class="<?= basename($_SERVER['PHP_SELF']) === 'revenue.php' ? 'active' : '' ?>">
            <span class="ico">💰</span> Revenue
        </a>
        <a href="/restaurant 2/admin/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
        <a href="/restaurant 2/logout.php">⬅ Logout</a>
    </div>
</aside>
