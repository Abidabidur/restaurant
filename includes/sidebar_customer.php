<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Customer Portal</small></div>
    <nav>
        <a href="/restaurant 2/customer/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="ico">🏠</span> Dashboard
        </a>
        <a href="/restaurant 2/customer/menu.php" class="<?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">
            <span class="ico">🍽️</span> Browse Menu
        </a>
        <a href="/restaurant 2/customer/orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
            <span class="ico">📦</span> My Orders
        </a>
        <a href="/restaurant 2/customer/reservation.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'active' : '' ?>">
            <span class="ico">🗓️</span> Reservations
        </a>
        <a href="/restaurant 2/customer/reviews.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
            <span class="ico">⭐</span> Reviews
        </a>
        <a href="/restaurant 2/customer/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
        <a href="/restaurant 2/logout.php">⬅ Logout</a>
    </div>
</aside>
